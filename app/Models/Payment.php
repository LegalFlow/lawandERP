<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'payment_date',
        'payment_status',
        'payment_amount',
        'cancel_amount',
        'cancel_date',
        'memo',
        'manager',
        'account',
        'note'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cancel_date' => 'date'
    ];
}
