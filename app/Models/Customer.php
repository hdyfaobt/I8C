<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * The fields that are mass-assignable.
     * These can be set via create() or update().
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'address',
        'salesforce_id',
    ];

    /**
     * A customer can have many orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * A human-friendly customer number, derived from the id — same idea as
     * Order::invoiceNumber(). Purely presentational, not stored, so it never
     * needs to be kept in sync separately.
     */
    public function customerNumber(): string
    {
        return 'KLT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
