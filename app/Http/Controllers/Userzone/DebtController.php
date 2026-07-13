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
            ->values();

        $debts = $this->sortDebts($debts);

        $grandTotal = $debts->sum('total');

        return view('userzone.debts.index', compact('debts', 'grandTotal'));
    }

    // Default: highest debt first.
    private function sortDebts(\Illuminate\Support\Collection $debts): \Illuminate\Support\Collection
    {
        $sort = request('sort');
        $direction = request('direction', 'asc');

        $keyBy = match ($sort) {
            'customer' => fn (array $row) => strtolower($row['customer']->name),
            'total' => fn (array $row) => $row['total'],
            default => null,
        };

        if (! $keyBy) {
            return $debts->sortByDesc('total')->values();
        }

        return $direction === 'desc' ? $debts->sortByDesc($keyBy)->values() : $debts->sortBy($keyBy)->values();
    }
}
