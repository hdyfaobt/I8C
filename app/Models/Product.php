<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A catalog product — managed by admins/managers, browsed by receptionists
 * when placing an order. See ProductController.
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The fields that are mass-assignable.
     */
    protected $fillable = [
        'article_number',
        'name',
        'price',
    ];

    /**
     * Cast fields to proper PHP types.
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];
}
