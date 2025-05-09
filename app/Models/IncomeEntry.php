<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_date',
        'depositor_name',
        'amount',
        'representative_id',
        'account_type',
        'memo',
    ];

    public function representative()
    {
        return $this->belongsTo(Member::class, 'representative_id');
    }
}
