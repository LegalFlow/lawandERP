<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawyerFee extends Model
{
    protected $table = 'TblLawyerFee';
    protected $primaryKey = 'case_idx';
    public $timestamps = false;

    protected $fillable = [
        'case_idx',
        'bank',
        'memo'
    ];

    // TblLawyerFeeDetail과의 관계
    public function details()
    {
        return $this->hasMany(LawyerFeeDetail::class, 'case_idx', 'case_idx');
    }

    // Target과의 관계
    public function target()
    {
        return $this->belongsTo(Target::class, 'case_idx', 'idx_TblCase');
    }
} 