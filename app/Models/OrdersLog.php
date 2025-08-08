<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdersLog extends Model
{
    protected $fillable = [
        'name',
        'price',
        'quantity',
        'photo',
        "order_id",
        "product_id"
    ];
}