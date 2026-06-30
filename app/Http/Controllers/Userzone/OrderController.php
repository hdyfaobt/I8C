<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display the list of all orders, newest first.
     */
    public function index()
    {
        // Eager-load the customer relation to avoid N+1 queries
        $orders = Order::with('customer')->latest()->get();

        return view('userzone.orders.index', compact('orders'));
    }

    /**
     * Show the form to create a new order.
     * Pass the list of customers for the dropdown.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('userzone.orders.create', compact('customers'));
    }

    /**
     * Validate and store a new order.
     * Status starts as 'pending' — RabbitMQ will process it next.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product'     => 'required|string|max:255',
            'quantity'    => 'required|integer|min:1',
            'unit_price'  => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:1000',
        ]);

        // Status is always 'pending' on creation — will be updated after RabbitMQ processing
        $validated['status'] = 'pending';

        $order = Order::create($validated);

        // Load the customer relation needed by the publisher
        $order->load('customer');

        // Publish the order to RabbitMQ — consumer will forward it to Salesforce
        $published = app(RabbitMQPublisher::class)->publishOrder($order);

        $message = $published
            ? 'Bestelling #' . $order->id . ' aangemaakt en verzonden naar de wachtrij.'
            : 'Bestelling #' . $order->id . ' aangemaakt, maar kon niet naar de wachtrij worden gestuurd.';

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
}
