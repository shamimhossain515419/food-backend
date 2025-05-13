<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'customer_id',
        'address',
        "total_price",
        "quantity",
        "transaction_id",
        "status",
    ];
}