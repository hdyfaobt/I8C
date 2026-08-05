<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Single order product line
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
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

    // Source catalog product, if any
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    // Already picked?
    public function isPicked(): bool
    {
        return $this->picked_at !== null;
    }

    // Reported out of stock?
    public function isOutOfStock(): bool
    {
        return $this->out_of_stock_at !== null;
    }

    // Picked or out-of-stock
    public function isResolved(): bool
    {
        return $this->isPicked() || $this->isOutOfStock();
    }

    // Amount owed back to customer
    public function refundAmount(): float
    {
        return $this->order->paid ? $this->lineTotal() : 0.0;
    }

    // Refund already processed?
    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }
}
