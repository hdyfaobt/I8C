<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * product/quantity/unit_price no longer live here — an order can have
     * several products now, see the items() relation and OrderItem.
     */
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

    /**
     * The *_at fields are cast to datetime so they behave like Carbon
     * instances in views (->format(), ->diffForHumans(), ...), just like
     * the built-in created_at/updated_at.
     */
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

    /**
     * Who's accountable at each of the three key hand-offs in this order's
     * lifecycle — who's the receptionist who took/placed it, who's the
     * orderpicker who prepared it, and who's the receptionist who handed
     * it over to the customer. All three are nullable: an order might not
     * have reached that step yet, or might predate this tracking.
     */
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

    /**
     * Full order value regardless of stock issues — see outstandingBalance()
     * below for what the customer actually still owes.
     */
    public function totalPrice(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->lineTotal());
    }

    /**
     * How much the customer still actually owes for this order.
     *
     * - Already paid: nothing is owed anymore (0) — an item going out of
     *   stock afterwards becomes a refund instead (money handed back), see
     *   OrderItem::refundAmount() and RefundController.
     * - Not yet paid: the full total, minus any out-of-stock item. Those
     *   items will never be delivered, so the customer was never going to
     *   be charged for them in the first place — no separate "refund" step
     *   needed, the debt is simply smaller. Used by DebtController to show
     *   how much each customer still owes.
     */
    public function outstandingBalance(): float
    {
        if ($this->paid) {
            return 0.0;
        }

        $outOfStockTotal = $this->items->sum(fn (OrderItem $item) => $item->isOutOfStock() ? $item->lineTotal() : 0);

        return $this->totalPrice() - $outOfStockTotal;
    }

    // Paid but never delivered — refused or failed, needs money back.
    public function refundEligible(): bool
    {
        return $this->paid && in_array($this->status, ['refused', 'failed'], true);
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    /**
     * True once every item on this order has an outcome — either actually
     * picked, or reported out of stock (see OrderItem::isResolved()). Used
     * to gate OrderController::markReady() — an order can't be marked
     * ready for pickup while some items still have neither outcome. An
     * out-of-stock item doesn't block this: it's a valid resolution, not
     * an open task.
     */
    public function allItemsResolved(): bool
    {
        return $this->items->isNotEmpty() && $this->items->every(fn (OrderItem $item) => $item->isResolved());
    }

    /**
     * Total number of articles (units) on this order — the sum of every
     * item's quantity, not just the number of distinct product lines.
     * Shown on the printable invoice (see orders/print.blade.php).
     */
    public function totalArticleCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * A human-friendly invoice number, derived from the order id.
     * Purely presentational — not stored in the database, so it always
     * stays in sync with the order it belongs to.
     */
    public function invoiceNumber(): string
    {
        return 'FACT-'.$this->created_at->format('Y').'-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Belgian structured payment communication ("gestructureerde
     * mededeling" / OGM-VCS), generated from the order id so every order
     * gets a unique, verifiable reference for the bank transfer.
     *
     * Format: +++XXX/XXXX/XXXXX+++ where the first 10 digits are the order
     * id (zero-padded) and the last 2 digits are a checksum equal to
     * (first 10 digits) mod 97 — with 0 replaced by 97, since a real OGM
     * checksum is never 00 (this is the standard Belgian algorithm).
     */
    public function paymentReference(): string
    {
        $base = str_pad((string) $this->id, 10, '0', STR_PAD_LEFT);

        $checksum = (int) $base % 97;
        $checksum = $checksum === 0 ? 97 : $checksum;

        $full = $base.str_pad((string) $checksum, 2, '0', STR_PAD_LEFT);

        return '+++'.substr($full, 0, 3).'/'.substr($full, 3, 4).'/'.substr($full, 7, 5).'+++';
    }
}
