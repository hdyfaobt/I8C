<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Mass-assignable fields
    protected $fillable = [
        'customer_id',
        'created_by',
        'notes',
        'status',
        'paid',
        'paid_at',
        'payment_method',
        'salesforce_id',
        'sent_at',
        'failed_at',
        'cancelled_at',
        'accepted_at',
        'refused_at',
        'refunded_at',
        'picking_comment',
        'ready_at',
        'prepared_by',
        'received_at',
        'received_by',
    ];

    // Type casts
    protected $casts = [
        'paid' => 'boolean',
        'paid_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'accepted_at' => 'datetime',
        'refused_at' => 'datetime',
        'refunded_at' => 'datetime',
        'ready_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Who placed the order
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Full order value
    public function totalPrice(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->lineTotal());
    }

    // Amount still owed
    public function outstandingBalance(): float
    {
        if ($this->paid) {
            return 0.0;
        }

        $outOfStockTotal = $this->items->sum(fn (OrderItem $item) => $item->isOutOfStock() ? $item->lineTotal() : 0);

        return $this->totalPrice() - $outOfStockTotal;
    }

    // Paid but refused/failed
    public function refundEligible(): bool
    {
        return $this->paid && in_array($this->status, ['refused', 'failed'], true);
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    // All items picked or out-of-stock
    public function allItemsResolved(): bool
    {
        return $this->items->isNotEmpty() && $this->items->every(fn (OrderItem $item) => $item->isResolved());
    }

    // Sum of all item quantities
    public function totalArticleCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    // Human-friendly invoice number
    public function invoiceNumber(): string
    {
        return 'FACT-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    // Belgian OGM-VCS payment reference
    public function paymentReference(): string
    {
        $base = str_pad((string) $this->id, 10, '0', STR_PAD_LEFT);

        $checksum = (int) $base % 97;
        $checksum = $checksum === 0 ? 97 : $checksum;

        $full = $base.str_pad((string) $checksum, 2, '0', STR_PAD_LEFT);

        return '+++'.substr($full, 0, 3).'/'.substr($full, 3, 4).'/'.substr($full, 7, 5).'+++';
    }
}
