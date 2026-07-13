<?php

namespace App\Http\Controllers\Userzone;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;

/**
 * Refund requests — every order item marked "out of stock" (see
 * OrderController::markItemOutOfStock()) means the customer paid for
 * something they never got, so it needs to be refunded. This controller
 * gives receptionist/manager/admin a single place to see who's owed money,
 * for which order, and how much — instead of having to dig through every
 * order's picking comment to find out (see routes/web.php for the
 * receptionist|admin|manager restriction).
 */
class RefundController extends Controller
{
    /**
     * List every out-of-stock item, split into what's still owed
     * ("pending") and what's already been paid back ("done"). Pending
     * items are shown first/prominently since they're the open task.
     */
    public function index()
    {
        $items = OrderItem::whereNotNull('out_of_stock_at')
            ->with(['order.customer'])
            ->latest('out_of_stock_at')
            ->get();

        $pending = $items->filter(fn (OrderItem $item) => ! $item->isRefunded())->values();
        $done = $items->filter(fn (OrderItem $item) => $item->isRefunded())->values();

        $pendingTotal = $pending->sum(fn (OrderItem $item) => $item->refundAmount());

        return view('userzone.refunds.index', compact('pending', 'done', 'pendingTotal'));
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
}
