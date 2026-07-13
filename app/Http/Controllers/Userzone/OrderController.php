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

        $orders = $allOrders->reject(fn (Order $order) => $order->status === 'cancelled')->values();
        $cancelledOrders = $allOrders->filter(fn (Order $order) => $order->status === 'cancelled')->values();

        return view('userzone.orders.index', compact('orders', 'cancelledOrders', 'isPureOrderpicker'));
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
     * Validate and store a new order.
     * Status starts as 'pending' — RabbitMQ will process it next.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Status is always 'pending' on creation — will be updated after RabbitMQ processing
        $validated['status'] = 'pending';

        $order = Order::create($validated);

        // Load the customer relation needed by the publisher
        $order->load('customer');

        // Publish the order to RabbitMQ — consumer will forward it to Salesforce
        $published = app(RabbitMQPublisher::class)->publishOrder($order);

        $message = $published
            ? 'Bestelling #'.$order->id.' aangemaakt en verzonden naar de wachtrij.'
            : 'Bestelling #'.$order->id.' aangemaakt, maar kon niet naar de wachtrij worden gestuurd.';

        return redirect()->route('orders.index')->with('success', $message);
    }

    /**
     * Show the details of a single order.
     */
    public function show(Order $order)
    {
        $order->load('customer');

        return view('userzone.orders.show', compact('order'));
    }

    /**
     * Recreate a previous order for the same customer — same product,
     * quantity, unit price and notes. Handy for repeat customers who
     * order the same thing regularly. Immediately published to RabbitMQ,
     * just like a normal new order.
     */
    public function repeat(Order $order)
    {
        $newOrder = Order::create([
            'customer_id' => $order->customer_id,
            'product' => $order->product,
            'quantity' => $order->quantity,
            'unit_price' => $order->unit_price,
            'notes' => $order->notes,
            'status' => 'pending',
        ]);

        $newOrder->load('customer');

        $published = app(RabbitMQPublisher::class)->publishOrder($newOrder);

        $message = $published
            ? 'Bestelling #'.$newOrder->id.' aangemaakt (herhaling van #'.$order->id.') en verzonden naar de wachtrij.'
            : 'Bestelling #'.$newOrder->id.' aangemaakt, maar kon niet naar de wachtrij worden gestuurd.';

        return redirect()->back()->with('success', $message);
    }
}
