<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;

/**
 * Refund requests — either a single item marked "out of stock" (see
 * OrderController::markItemOutOfStock()), or a whole order that got
 * refused/failed after already being paid (see Order::refundEligible()).
 * Both mean the customer paid for something they never got.
 */
class RefundController extends Controller
{
    // Item refunds (out-of-stock) and order refunds (refused/failed), split pending/done.
    public function index()
    {
        $items = OrderItem::whereNotNull('out_of_stock_at')
            ->with(['order.customer'])
            ->latest('out_of_stock_at')
            ->get();

        $orders = Order::where('paid', true)
            ->whereIn('status', ['refused', 'failed'])
            ->with('customer')
            ->latest('id')
            ->get();

        $pending = $this->sortItems($items->filter(fn (OrderItem $item) => ! $item->isRefunded())->values());
        $done = $this->sortItems($items->filter(fn (OrderItem $item) => $item->isRefunded())->values());

        $pendingOrders = $this->sortOrders($orders->filter(fn (Order $order) => ! $order->isRefunded())->values());
        $doneOrders = $this->sortOrders($orders->filter(fn (Order $order) => $order->isRefunded())->values());

        $pendingTotal = $pending->sum(fn (OrderItem $item) => $item->refundAmount())
            + $pendingOrders->sum(fn (Order $order) => $order->totalPrice());

        return view('userzone.refunds.index', compact('pending', 'done', 'pendingOrders', 'doneOrders', 'pendingTotal'));
    }

    // Newest refused/failed first, unless a column sort was requested.
    private function sortOrders(\Illuminate\Support\Collection $orders): \Illuminate\Support\Collection
    {
        $sort = request('orderSort');
        $direction = request('orderDirection', 'asc');

        $keyBy = match ($sort) {
            'customer' => fn (Order $o) => strtolower($o->customer->name),
            'order' => fn (Order $o) => $o->id,
            'amount' => fn (Order $o) => $o->totalPrice(),
            default => null,
        };

        if (! $keyBy) {
            return $orders;
        }

        return $direction === 'desc' ? $orders->sortByDesc($keyBy)->values() : $orders->sortBy($keyBy)->values();
    }

    // Default: most recently out-of-stock first.
    private function sortItems(\Illuminate\Support\Collection $items): \Illuminate\Support\Collection
    {
        $sort = request('sort');
        $direction = request('direction', 'asc');

        $keyBy = match ($sort) {
            'customer' => fn (OrderItem $i) => strtolower($i->order->customer->name),
            'order' => fn (OrderItem $i) => $i->order_id,
            'product' => fn (OrderItem $i) => strtolower($i->product),
            'quantity' => fn (OrderItem $i) => $i->quantity,
            'amount' => fn (OrderItem $i) => $i->refundAmount(),
            default => null,
        };

        if (! $keyBy) {
            return $items;
        }

        return $direction === 'desc' ? $items->sortByDesc($keyBy)->values() : $items->sortBy($keyBy)->values();
    }

    /**
     * Toggle whether this item's refund has been processed. A simple
     * on/off flag — same pattern as Order's "paid" toggle — rather than a
     * whole approval workflow, since this is just a bookkeeping checkbox
     * for the receptionist/manager/admin.
     */
    public function markRefunded(OrderItem $item)
    {
        // Grab the amount (and whether the order was paid) before toggling,
        // purely to phrase the flash message correctly below.
        $refundAmount = $item->refundAmount();

        $item->update([
            'refunded_at' => $item->isRefunded() ? null : now(),
        ]);

        if (! $item->isRefunded()) {
            $message = "Terugbetaling voor '{$item->product}' (bestelling #{$item->order_id}) teruggezet naar openstaand.";
        } elseif ($refundAmount > 0) {
            $message = "Terugbetaling voor '{$item->product}' (bestelling #{$item->order_id}) genoteerd.";
        } else {
            // Order was never paid — nothing was actually refunded, just acknowledged.
            $message = "'{$item->product}' (bestelling #{$item->order_id}) gemarkeerd als verwerkt — bestelling was niet betaald, dus geen terugbetaling nodig.";
        }

        return redirect()->back()->with('success', $message);
    }

    // Same toggle, but for a whole refused/failed paid order.
    public function markOrderRefunded(Order $order)
    {
        $order->update([
            'refunded_at' => $order->isRefunded() ? null : now(),
        ]);

        $message = $order->isRefunded()
            ? "Terugbetaling voor bestelling #{$order->id} genoteerd."
            : "Terugbetaling voor bestelling #{$order->id} teruggezet naar openstaand.";

        return redirect()->back()->with('success', $message);
    }
}
