<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
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
}
