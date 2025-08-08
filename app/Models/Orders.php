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
        "payment_id",
        "payment_type",
        "transaction_id",
        "status",
    ];
}