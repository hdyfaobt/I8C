<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;

// Customer debt overview
class DebtController extends Controller
{
    // List customers who owe money
    public function index()
    {
        $customers = Customer::with(['unpaidActiveOrders.items'])->get();

        $debts = $customers
            ->map(function (Customer $customer) {
                $orders = $customer->unpaidActiveOrders
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

    // Sort debts by column
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
