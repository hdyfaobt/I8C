<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Role-aware dashboard overview
    public function index()
    {
        $isPureOrderpicker = Auth::user()->hasRole('orderpicker') && ! Auth::user()->hasAnyRole(['admin', 'manager']);

        if ($isPureOrderpicker) {
            return view('dashboard', [
                'isPureOrderpicker' => true,
                'toPick' => Order::where('status', 'sent')->count(),
                'inProgress' => Order::where('status', 'sent')->where('prepared_by', Auth::id())->count(),
                'readyForPickup' => Order::where('status', 'ready_for_pickup')->count(),
            ]);
        }

        return view('dashboard', [
            'isPureOrderpicker' => false,
            'needsAttention' => Order::whereIn('status', ['awaiting_review', 'failed'])->count(),
            'atOrderpicker' => Order::where('status', 'sent')->count(),
            'readyForPickup' => Order::where('status', 'ready_for_pickup')->count(),
            'debtorsTotal' => $this->debtorsTotal(),
            'refundsTotal' => $this->refundsTotal(),
            'usersCount' => Auth::user()->hasAnyRole(['admin', 'manager']) ? User::count() : null,
            'productsCount' => Auth::user()->hasAnyRole(['admin', 'manager']) ? Product::count() : null,
        ]);
    }

    // Total unpaid, active orders
    private function debtorsTotal(): float
    {
        return Customer::with(['orders' => function ($query) {
            $query->where('paid', false)
                ->whereNotIn('status', ['cancelled', 'refused'])
                ->with('items');
        }])->get()
            ->sum(fn (Customer $customer) => $customer->orders->sum(fn (Order $order) => $order->outstandingBalance()));
    }

    // Total pending refunds
    private function refundsTotal(): float
    {
        $itemsTotal = OrderItem::whereNotNull('out_of_stock_at')
            ->whereNull('refunded_at')
            ->with('order')
            ->get()
            ->sum(fn (OrderItem $item) => $item->refundAmount());

        $ordersTotal = Order::where('paid', true)
            ->whereIn('status', ['refused', 'failed'])
            ->whereNull('refunded_at')
            ->with('items')
            ->get()
            ->sum(fn (Order $order) => $order->totalPrice());

        return $itemsTotal + $ordersTotal;
    }
}
