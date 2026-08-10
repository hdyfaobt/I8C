<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderSyncService;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    // Statuses visible to orderpicker
    private const ORDERPICKER_VISIBLE_STATUSES = ['sent', 'ready_for_pickup', 'received'];

    // Statuses where picking actions are allowed
    private const PICKING_ACTIVE_STATUSES = ['sent', 'ready_for_pickup'];

    // List all orders
    public function index()
    {
        $isPureOrderpicker = $this->isPureOrderpicker();

        // Sort by id, not date
        $query = Order::with(['customer', 'items'])->latest('id');

        if ($isPureOrderpicker) {
            $query->whereIn('status', self::ORDERPICKER_VISIBLE_STATUSES);
        }

        // Dashboard shortcut filter
        $statusFilter = array_filter(explode(',', (string) request('status', '')));
        if ($statusFilter !== []) {
            $query->whereIn('status', $statusFilter);
        }
        if (request()->boolean('mine')) {
            $query->where('prepared_by', Auth::id());
        }
        $hasActiveFilter = $statusFilter !== [] || request()->boolean('mine');

        $allOrders = $query->get();

        $orders = $this->sortOrders($allOrders->reject(fn (Order $order) => $order->status === 'cancelled')->values());
        $cancelledOrders = $this->sortOrders($allOrders->filter(fn (Order $order) => $order->status === 'cancelled')->values());

        return view('userzone.orders.index', compact('orders', 'cancelledOrders', 'isPureOrderpicker', 'hasActiveFilter'));
    }

    // Column sort for orders list
    private function sortOrders(Collection $orders): Collection
    {
        $direction = request('direction', 'asc');

        $keyBy = match (request('sort')) {
            'id' => fn (Order $o) => $o->id,
            'customer' => fn (Order $o) => strtolower($o->customer->name),
            'created_at' => fn (Order $o) => $o->created_at,
            'status' => fn (Order $o) => $o->status,
            'total' => fn (Order $o) => $o->totalPrice(),
            'paid' => fn (Order $o) => $o->paid ? 1 : 0,
            default => null,
        };

        if (! $keyBy) {
            return $orders;
        }

        return $direction === 'desc' ? $orders->sortByDesc($keyBy)->values() : $orders->sortBy($keyBy)->values();
    }

    // Orderpicker only, no other role
    private function isPureOrderpicker(): bool
    {
        $user = Auth::user();

        return $user->hasRole('orderpicker') && ! $user->hasAnyRole(['admin', 'manager']);
    }

    // Show order creation form
    public function create()
    {
        // Reload previously selected customer
        $selectedCustomer = null;

        if (old('customer_id')) {
            $customer = Customer::find(old('customer_id'));

            if ($customer) {
                $selectedCustomer = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'company' => $customer->company,
                ];
            }
        }

        return view('userzone.orders.create', compact('selectedCustomer'));
    }

    // Create a new order
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order = Order::create([
            'customer_id' => $validated['customer_id'],
            'created_by' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);

            $order->items()->create([
                'product_id' => $product->id,
                'product' => $product->name,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }

        $synced = $this->syncOrderNow($order);

        $message = $synced
            ? 'Bestelling #'.$order->id.' aangemaakt en verzonden naar de orderpicker.'
            : 'Bestelling #'.$order->id.' aangemaakt, maar de verzending naar Salesforce is mislukt.';

        return redirect()->route('orders.index')->with('success', $message);
    }

    // Sync order immediately
    private function syncOrderNow(Order $order): bool
    {
        $order->update(['status' => 'pending', 'accepted_at' => now()]);
        $order->load(['customer', 'items']);

        app(RabbitMQPublisher::class)->publishOrder($order);

        return app(OrderSyncService::class)->process($order);
    }

    // Show order details
    public function show(Order $order)
    {
        if ($this->isPureOrderpicker() && ! in_array($order->status, self::ORDERPICKER_VISIBLE_STATUSES, true)) {
            abort(403);
        }

        $order->load(['customer', 'items', 'createdBy', 'preparedBy', 'receivedBy']);

        // Admin/manager, or assigned orderpicker
        $canWorkOnPicking = Auth::user()->hasAnyRole(['admin', 'manager'])
            || $order->prepared_by === Auth::id();

        return view('userzone.orders.show', compact('order', 'canWorkOnPicking'));
    }

    // Duplicate a past order
    public function repeat(Order $order)
    {
        $order->load('items');

        if ($order->items->isEmpty()) {
            return redirect()->back()->with('error', 'Bestelling #'.$order->id.' heeft geen producten, niets om te herhalen.');
        }

        $newOrder = Order::create([
            'customer_id' => $order->customer_id,
            'created_by' => Auth::id(),
            'notes' => $order->notes,
            'status' => 'pending',
        ]);

        foreach ($order->items as $item) {
            $newOrder->items()->create([
                'product_id' => $item->product_id,
                'product' => $item->product,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
        }

        $synced = $this->syncOrderNow($newOrder);

        $message = $synced
            ? 'Bestelling #'.$newOrder->id.' aangemaakt (herhaling van #'.$order->id.') en verzonden naar de orderpicker.'
            : 'Bestelling #'.$newOrder->id.' aangemaakt (herhaling van #'.$order->id.'), maar de verzending naar Salesforce is mislukt.';

        return redirect()->back()->with('success', $message);
    }

    // Accept an order for review
    public function accept(Order $order)
    {
        if ($order->status !== 'awaiting_review') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet meer "wacht op validatie" en kan niet meer geaccepteerd worden.');
        }

        if ($order->items()->count() === 0) {
            return redirect()->back()->with('error', 'Bestelling #'.$order->id.' heeft geen producten en kan niet geaccepteerd worden. Verwijder ze in plaats daarvan.');
        }

        $synced = $this->syncOrderNow($order);

        $message = $synced
            ? 'Bestelling #'.$order->id.' geaccepteerd en succesvol verzonden naar Salesforce.'
            : 'Bestelling #'.$order->id.' geaccepteerd, maar de verzending naar Salesforce is mislukt.';

        return redirect()->back()->with('success', $message);
    }

    // Refuse an order under review
    public function refuse(Order $order)
    {
        if ($order->status !== 'awaiting_review') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet meer "wacht op validatie" en kan niet meer geweigerd worden.');
        }

        $order->update(['status' => 'refused', 'refused_at' => now()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' geweigerd.');
    }

    // Retry a failed order
    public function retry(Order $order)
    {
        if (! in_array($order->status, ['pending', 'failed'], true)) {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet op "mislukt" of "in afwachting" en kan niet opnieuw verwerkt worden.');
        }

        // Reset to pending first
        if ($order->status === 'failed') {
            $order->update(['status' => 'pending']);
        }

        $order->load(['customer', 'items']);

        app(RabbitMQPublisher::class)->publishOrder($order);

        $synced = app(OrderSyncService::class)->process($order);

        $message = $synced
            ? 'Bestelling #'.$order->id.' succesvol verzonden naar Salesforce.'
            : 'Bestelling #'.$order->id.' kon niet naar Salesforce verzonden worden.';

        return redirect()->back()->with('success', $message);
    }

    // Cancel a still-active order
    public function cancel(Order $order)
    {
        if (! in_array($order->status, ['awaiting_review', 'pending'], true)) {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' kan niet meer geannuleerd worden.');
        }

        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' geannuleerd.');
    }

    // Permanently delete an order
    public function destroy(Order $order)
    {
        $orderId = $order->id;

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Bestelling #'.$orderId.' definitief verwijderd.');
    }

    // Toggle item picked status
    public function pickItem(Order $order, OrderItem $item)
    {
        // Verify item belongs to order
        abort_unless($item->order_id === $order->id, 404);

        // Only while order is being picked
        abort_unless(in_array($order->status, self::PICKING_ACTIVE_STATUSES, true), 403);

        $item->update([
            'picked_at' => $item->picked_at ? null : now(),
            'out_of_stock_at' => null,
        ]);

        return redirect()->back();
    }

    // Toggle item out-of-stock status
    public function markItemOutOfStock(Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

        // Only while order is being picked
        abort_unless(in_array($order->status, self::PICKING_ACTIVE_STATUSES, true), 403);

        $wasOutOfStock = $item->isOutOfStock();

        $item->update([
            'out_of_stock_at' => $wasOutOfStock ? null : now(),
            'picked_at' => null,
        ]);

        if (! $wasOutOfStock) {
            $note = "Product '{$item->product}' is niet meer op voorraad — dit artikel moet aan de klant terugbetaald worden.";

            $order->update([
                'picking_comment' => trim(($order->picking_comment ? $order->picking_comment."\n" : '').$note),
            ]);
        }

        return redirect()->back();
    }

    // Claim order for preparation
    public function takeCharge(Order $order)
    {
        if ($order->status === 'sent') {
            $order->update(['prepared_by' => Auth::id()]);
        }

        return redirect()->route('orders.show', $order);
    }

    // Save picking note
    public function updatePickingComment(Request $request, Order $order)
    {
        // Only while order is being picked
        abort_unless(in_array($order->status, self::PICKING_ACTIVE_STATUSES, true), 403);

        $validated = $request->validate([
            'picking_comment' => 'nullable|string|max:1000',
        ]);

        $order->update(['picking_comment' => $validated['picking_comment'] ?? null]);

        return redirect()->back()->with('success', 'Opmerking opgeslagen voor bestelling #'.$order->id.'.');
    }

    // Mark order ready for pickup
    public function markReady(Order $order)
    {
        if ($order->status !== 'sent') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet op "verzonden" en kan niet klaargemaakt worden.');
        }

        $order->load('items');

        if (! $order->allItemsResolved()) {
            return redirect()->back()->with('success', 'Niet alle producten van bestelling #'.$order->id.' zijn al opgehaald of als niet op voorraad gemarkeerd.');
        }

        $order->update(['status' => 'ready_for_pickup', 'ready_at' => now(), 'prepared_by' => Auth::id()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' is klaar om opgehaald te worden.');
    }

    // Toggle order paid status
    public function togglePaid(Request $request, Order $order)
    {
        if ($order->status === 'cancelled') {
            return redirect()->back()->with('success', 'Geannuleerde bestelling #'.$order->id.' kan niet betaald worden.');
        }

        if ($order->paid) {
            abort_unless(Auth::user()->hasAnyRole(['admin', 'manager']), 403);

            $order->update([
                'paid' => false,
                'paid_at' => null,
                'payment_method' => null,
            ]);

            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' gemarkeerd als niet betaald.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash',
        ]);

        $order->update([
            'paid' => true,
            'paid_at' => now(),
            'payment_method' => $validated['payment_method'],
        ]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' gemarkeerd als betaald.');
    }

    // Render printable invoice
    public function print(Order $order)
    {
        $order->load(['customer', 'items']);

        return view('userzone.orders.print', compact('order'));
    }

    // Confirm order received by customer
    public function markReceived(Order $order)
    {
        if ($order->status !== 'ready_for_pickup') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet op "klaar om op te halen".');
        }

        $order->update(['status' => 'received', 'received_at' => now(), 'received_by' => Auth::id()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' gemarkeerd als ontvangen door de klant.');
    }
}
