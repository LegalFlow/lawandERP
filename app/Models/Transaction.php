<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'time',
        'amount',
        'payment',
        'description',
        'account',
        'manager',
        'memo',
        'cash_receipt'
    ];

   protected $casts = [
    'date' => 'date:Y-m-d',  // 시간 부분 없이 날짜만 표시
    'cash_receipt' => 'boolean'
];
}