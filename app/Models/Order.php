<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    /**
     * The fields that are mass-assignable.
     */
    protected $fillable = [
        'customer_id',
        'product',
        'quantity',
        'unit_price',
        'notes',
        'status',
        'salesforce_id',
    ];

    /**
     * Cast fields to proper PHP types.
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'integer',
    ];

    /**
     * An order belongs to one customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Calculate the total price for this order.
     */
    public function totalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
