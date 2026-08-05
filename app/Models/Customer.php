<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    // Mass-assignable fields
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'address',
        'salesforce_id',
    ];

    // Has many orders
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Human-friendly customer number
    public function customerNumber(): string
    {
        return 'KLT-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
