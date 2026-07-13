<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single product line on an order (product + quantity + unit price).
 * An order can have several of these — see Order::items().
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product',
        'quantity',
        'unit_price',
        'picked_at',
        'out_of_stock_at',
        'refunded_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'picked_at' => 'datetime',
        'out_of_stock_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lineTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Whether the orderpicker has already picked this item.
     */
    public function isPicked(): bool
    {
        return $this->picked_at !== null;
    }

    /**
     * Whether this item was reported out of stock instead of picked — see
     * OrderController::markItemOutOfStock().
     */
    public function isOutOfStock(): bool
    {
        return $this->out_of_stock_at !== null;
    }

    /**
     * An item is "resolved" once the orderpicker has done something with
     * it — either actually picked it, or reported it's out of stock. Used
     * by Order::allItemsResolved() to decide when an order can be marked
     * ready for pickup: every line needs an outcome, not necessarily a
     * successful pick.
     */
    public function isResolved(): bool
    {
        return $this->isPicked() || $this->isOutOfStock();
    }

    /**
     * Amount owed back to the customer for this line.
     *
     * Only meaningful if the order was actually paid — if it wasn't, no
     * money ever changed hands for this item, so there's nothing to give
     * back. In that case the order simply owes less overall (see
     * Order::outstandingBalance()), rather than triggering an actual
     * refund. Used on the refunds page (see RefundController).
     */
    public function refundAmount(): float
    {
        return $this->order->paid ? $this->lineTotal() : 0.0;
    }

    /**
     * Whether the refund for this out-of-stock item has already been
     * processed by a receptionist/manager/admin. See RefundController.
     */
    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }
}
