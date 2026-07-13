<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;

/**
 * "How much does each customer still owe us" — a simple overview built on
 * top of Order::outstandingBalance(), which already accounts for
 * out-of-stock items (they don't count towards the debt, see that method's
 * docblock). Separate from RefundController: refunds are money we owe
 * customers back, this page is money customers still owe us.
 */
class DebtController extends Controller
{
    /**
     * List every customer who still owes money, highest debt first.
     * Cancelled/refused orders never counted in the first place — they
     * never became a real obligation to pay.
     */
    public function index()
    {
        $customers = Customer::with(['orders' => function ($query) {
            $query->where('paid', false)
                ->whereNotIn('status', ['cancelled', 'refused'])
                ->with('items');
        }])->get();

        $debts = $customers
            ->map(function (Customer $customer) {
                $orders = $customer->orders
                    ->filter(fn (Order $order) => $order->outstandingBalance() > 0)
                    ->values();

                return [
                    'customer' => $customer,
                    'orders' => $orders,
                    'total' => $orders->sum(fn (Order $order) => $order->outstandingBalance()),
                ];
            })
            ->filter(fn (array $row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->values();

        $grandTotal = $debts->sum('total');

        return view('userzone.debts.index', compact('debts', 'grandTotal'));
    }
}
