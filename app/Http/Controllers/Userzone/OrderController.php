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
    /**
     * Statuses visible to a pure orderpicker — only orders that have
     * actually reached Salesforce onwards. An orderpicker has nothing to
     * do with an order that's still awaiting review, queued, refused,
     * cancelled or failed, so those are hidden from their list entirely.
     */
    private const ORDERPICKER_VISIBLE_STATUSES = ['sent', 'ready_for_pickup', 'received'];

    /**
     * Display the list of all orders, newest first.
     * An orderpicker only sees orders already synced to Salesforce (see
     * ORDERPICKER_VISIBLE_STATUSES) — receptionist/admin/manager see
     * everything, since they handle the full lifecycle.
     *
     * Cancelled orders are split out into their own $cancelledOrders
     * collection rather than mixed into $orders — the view renders them in
     * a separate "Geannuleerde bestellingen" table below the main one, so
     * a dead-end order doesn't clutter the list of orders that still need
     * attention, without actually disappearing (its Details link still
     * works). Reordering it happens from the create-order form instead of
     * a button here — see CustomerController::orders() and repeat() below.
     */
    public function index()
    {
        $isPureOrderpicker = $this->isPureOrderpicker();

        $query = Order::with(['customer', 'items'])->latest();

        if ($isPureOrderpicker) {
            $query->whereIn('status', self::ORDERPICKER_VISIBLE_STATUSES);
        }

        $allOrders = $query->get();

        $orders = $this->sortOrders($allOrders->reject(fn (Order $order) => $order->status === 'cancelled')->values());
        $cancelledOrders = $this->sortOrders($allOrders->filter(fn (Order $order) => $order->status === 'cancelled')->values());

        return view('userzone.orders.index', compact('orders', 'cancelledOrders', 'isPureOrderpicker'));
    }

    // Column sort for the orders list, 3-state cycle from the header links.
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

    /**
     * True when the logged-in user is an orderpicker and nothing more
     * privileged (admin/manager already see everything, so the filter
     * doesn't apply to them even if they also happen to hold the
     * orderpicker role).
     */
    private function isPureOrderpicker(): bool
    {
        $user = Auth::user();

        return $user->hasRole('orderpicker') && ! $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Show the form to create a new order.
     * Customers are no longer preloaded here — the form fetches them on-demand
     * via the customers.search endpoint (see CustomerController::search()).
     */
    public function create()
    {
        // If we're redisplaying the form after a validation error, reload the
        // previously selected customer so the search combobox can show it again.
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

    /**
     * Validate and store a new order with one or more product lines.
     * Status starts as 'awaiting_review' — nothing is sent to RabbitMQ yet.
     * A receptionist (see accept()/refuse() below) has to review the order
     * first. This is a manual business decision, separate from the
     * technical Salesforce sync outcome ('sent'/'failed').
     *
     * Every line has to reference a real catalog product — `product_id`
     * must exist in the `products` table, and the product's name is looked
     * up here (not trusted from the request) so a line can never be
     * something typed by hand that isn't actually in the catalog. The name
     * is still copied onto the OrderItem as a snapshot rather than a live
     * foreign key, same as before — so it stays accurate even if the
     * product is later renamed or removed from the catalog.
     *
     * 'created_by' records who took/placed the order — the logged-in
     * receptionist submitting this form — for the "who did what" tracking
     * shown on the order detail page.
     */
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
            'status' => 'awaiting_review',
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

        return redirect()->route('orders.index')
            ->with('success', 'Bestelling #'.$order->id.' aangemaakt en wacht op validatie door een receptionist.');
    }

    // Same visibility rule as index() for orderpickers.
    public function show(Order $order)
    {
        if ($this->isPureOrderpicker() && ! in_array($order->status, self::ORDERPICKER_VISIBLE_STATUSES, true)) {
            abort(403);
        }

        $order->load(['customer', 'items', 'createdBy', 'preparedBy', 'receivedBy']);

        // Admin/manager always can. Orderpicker only after "Overnemen".
        $canWorkOnPicking = Auth::user()->hasAnyRole(['admin', 'manager'])
            || $order->prepared_by === Auth::id();

        return view('userzone.orders.show', compact('order', 'canWorkOnPicking'));
    }

    /**
     * Recreate a previous order for the same customer — same notes and the
     * exact same product lines. Handy for repeat customers who order the
     * same things regularly. Just like a brand new order, it starts as
     * 'awaiting_review' and needs a receptionist to accept it.
     *
     * 'created_by' is the person triggering THIS reorder, not whoever
     * placed the original order being copied — they're the one taking the
     * order right now.
     */
    public function repeat(Order $order)
    {
        $order->load('items');

        $newOrder = Order::create([
            'customer_id' => $order->customer_id,
            'created_by' => Auth::id(),
            'notes' => $order->notes,
            'status' => 'awaiting_review',
        ]);

        foreach ($order->items as $item) {
            $newOrder->items()->create([
                'product_id' => $item->product_id,
                'product' => $item->product,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Bestelling #'.$newOrder->id.' aangemaakt (herhaling van #'.$order->id.') en wacht op validatie door een receptionist.');
    }

    /**
     * Accept an order awaiting review.
     * This is the human "go ahead" decision. The order is published to
     * RabbitMQ (the queue is still genuinely used) and then synced to
     * Salesforce immediately, in this same request — nobody has to keep a
     * separate `rabbitmq:consume` process running in a terminal for orders
     * to actually go through; it all happens right from the website.
     * Restricted to receptionist/admin/manager (see routes/web.php) —
     * orderpicker only steps in once the order has already been accepted
     * and synced.
     */
    public function accept(Order $order)
    {
        if ($order->status !== 'awaiting_review') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet meer "wacht op validatie" en kan niet meer geaccepteerd worden.');
        }

        // Can't accept an order with no products at all.
        if ($order->items()->count() === 0) {
            return redirect()->back()->with('error', 'Bestelling #'.$order->id.' heeft geen producten en kan niet geaccepteerd worden. Verwijder ze in plaats daarvan.');
        }

        $order->update(['status' => 'pending', 'accepted_at' => now()]);

        $order->load(['customer', 'items']);

        // Publish to RabbitMQ for the audit trail / architecture, then
        // process it right away — see OrderSyncService for why this is
        // safe even if a `rabbitmq:consume` worker also picks it up later.
        app(RabbitMQPublisher::class)->publishOrder($order);

        $synced = app(OrderSyncService::class)->process($order);

        $message = $synced
            ? 'Bestelling #'.$order->id.' geaccepteerd en succesvol verzonden naar Salesforce.'
            : 'Bestelling #'.$order->id.' geaccepteerd, maar de verzending naar Salesforce is mislukt.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Refuse an order awaiting review.
     * A business decision, not a technical failure — a refused order never
     * reaches RabbitMQ or Salesforce. Restricted to receptionist/admin/manager.
     */
    public function refuse(Order $order)
    {
        if ($order->status !== 'awaiting_review') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet meer "wacht op validatie" en kan niet meer geweigerd worden.');
        }

        $order->update(['status' => 'refused', 'refused_at' => now()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' geweigerd.');
    }

    /**
     * Manually retry a previously failed order — or nudge one that's stuck
     * on 'pending' (e.g. from before this app started processing orders
     * synchronously, or if a message was published but the request got cut
     * off before the sync ran). Republishes to RabbitMQ and processes it
     * immediately, exactly like accept().
     */
    public function retry(Order $order)
    {
        if (! in_array($order->status, ['pending', 'failed'], true)) {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet op "mislukt" of "in afwachting" en kan niet opnieuw verwerkt worden.');
        }

        // Reset to 'pending' first if it had failed — OrderSyncService
        // only processes orders that are 'pending'.
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

    /**
     * Cancel an order that is still active — either 'awaiting_review'
     * (not yet looked at by a receptionist) or 'pending' (accepted and
     * queued, but not yet synced to Salesforce). Once an order is 'sent'
     * it already exists as an Opportunity in Salesforce, and
     * 'failed'/'refused'/'cancelled' orders are already out of the active
     * flow, so cancelling no longer applies.
     * The RabbitMQ message may still be sitting in the queue at this point;
     * ConsumeOrders::processOrder() re-checks the status before syncing to
     * Salesforce, so a cancelled order is safely skipped even if it was
     * already published.
     */
    public function cancel(Order $order)
    {
        if (! in_array($order->status, ['awaiting_review', 'pending'], true)) {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' kan niet meer geannuleerd worden.');
        }

        $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' geannuleerd.');
    }

    // Admin only, e.g. to clean up an empty/broken order.
    public function destroy(Order $order)
    {
        $orderId = $order->id;

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Bestelling #'.$orderId.' definitief verwijderd.');
    }

    /**
     * Toggle whether a single order item has been physically picked.
     * Restricted to orderpicker/admin/manager (see routes/web.php).
     * A simple toggle rather than a one-way action, so a mistaken click
     * can be undone without any extra UI.
     *
     * Picked and out-of-stock are mutually exclusive: marking an item
     * picked also clears any out-of-stock flag on it (it clearly wasn't
     * out of stock after all).
     */
    public function pickItem(Order $order, OrderItem $item)
    {
        // Make sure the item actually belongs to this order — without this
        // check, someone could pick an item from order #5 through a request
        // pointing at order #6's URL.
        abort_unless($item->order_id === $order->id, 404);

        $item->update([
            'picked_at' => $item->picked_at ? null : now(),
            'out_of_stock_at' => null,
        ]);

        return redirect()->back();
    }

    /**
     * Report a single order item as out of stock instead of picked — the
     * orderpicker found it's actually not available. Restricted to
     * orderpicker/admin/manager (see routes/web.php).
     *
     * Mutually exclusive with "picked", same reasoning as pickItem().
     * Marking it (not un-marking) also appends a note to the order for the
     * customer, since this item will need to be refunded — see
     * OrderController::updatePickingComment() for where that note lives.
     */
    public function markItemOutOfStock(Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id, 404);

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

    /**
     * Claim an order for preparation and jump straight to its detail page,
     * where the actual work happens: tick each item off as picked (or
     * report it out of stock), then mark the whole order ready once every
     * line has an outcome — see Order::allItemsResolved() and markReady().
     * Restricted to orderpicker/admin/manager (see routes/web.php).
     */
    public function takeCharge(Order $order)
    {
        if ($order->status === 'sent') {
            $order->update(['prepared_by' => Auth::id()]);
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * Save the orderpicker's free-text note about this order (e.g. a
     * substitution, a missing item, ...). Restricted to
     * orderpicker/admin/manager.
     */
    public function updatePickingComment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'picking_comment' => 'nullable|string|max:1000',
        ]);

        $order->update(['picking_comment' => $validated['picking_comment'] ?? null]);

        return redirect()->back()->with('success', 'Opmerking opgeslagen voor bestelling #'.$order->id.'.');
    }

    /**
     * Orderpicker confirms the order is fully picked and ready for pickup.
     * Only allowed once Salesforce sync succeeded ('sent') and every item
     * has actually been picked — this prevents marking an order ready by
     * mistake while items are still missing.
     *
     * 'prepared_by' records which orderpicker actually assembled it — the
     * "who took care of preparing it" half of the order's accountability
     * trail, alongside created_by and received_by.
     */
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

    /**
     * Toggle whether an order has been paid.
     * Deliberately independent from the order's `status` lifecycle
     * (awaiting_review/pending/sent/.../received) — payment can happen at
     * any point and never gates or blocks any of the other actions above.
     *
     * Marking an order as paid (receptionist/admin/manager, per the route
     * middleware) requires a payment method — "bank_transfer" (covers both
     * a regular transfer and Bancontact) or "cash" — so there's a record of
     * how the customer paid, not just that they did.
     *
     * Undoing a payment once it's already marked paid is admin/manager
     * only: a receptionist can register a payment but can't reverse one,
     * so a mistaken "Betaald" click can't just be quietly undone by
     * whoever placed it. Enforced here (not just hidden in the view) so
     * it can't be bypassed by posting to the route directly.
     *
     * A cancelled order can never be paid — it's a dead end in the
     * workflow, there's nothing left to collect payment for. Rejected
     * up front, before even checking the paid/unpaid branches below.
     */
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

    /**
     * Render a standalone, printable invoice sheet for an order (seller +
     * buyer details, line items, article count, payment reference and
     * bank account). Opened in a new tab so the order page stays open
     * behind it. Restricted to receptionist/admin/manager, same as the
     * other order-management actions.
     */
    public function print(Order $order)
    {
        $order->load(['customer', 'items']);

        return view('userzone.orders.print', compact('order'));
    }

    /**
     * Receptionist confirms the customer actually received/collected the
     * order. This is the final, successful step of the order lifecycle.
     * Restricted to receptionist/admin/manager.
     *
     * 'received_by' records who actually handed the order over — the last
     * leg of the created_by/prepared_by/received_by accountability trail.
     */
    public function markReceived(Order $order)
    {
        if ($order->status !== 'ready_for_pickup') {
            return redirect()->back()->with('success', 'Bestelling #'.$order->id.' staat niet op "klaar om op te halen".');
        }

        $order->update(['status' => 'received', 'received_at' => now(), 'received_by' => Auth::id()]);

        return redirect()->back()->with('success', 'Bestelling #'.$order->id.' gemarkeerd als ontvangen door de klant.');
    }
}
