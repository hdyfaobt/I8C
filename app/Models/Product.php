<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Catalog product
class Product extends Model
{
    use HasFactory;

    // Mass-assignable fields
    protected $fillable = [
        'article_number',
        'name',
        'price',
    ];

    // Type casts
    protected $casts = [
        'price' => 'decimal:2',
    ];
}
