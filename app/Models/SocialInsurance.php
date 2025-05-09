<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SocialInsurance extends Model
{
    use HasFactory;

    protected $table = 'social_insurance';
    
    protected $fillable = [
        'statement_date',
        'resident_id',
        'name',
        'health_insurance',
        'national_pension',
        'long_term_care',
        'employment_insurance',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'health_insurance' => 'decimal:2',
        'national_pension' => 'decimal:2',
        'long_term_care' => 'decimal:2',
        'employment_insurance' => 'decimal:2',
    ];
    
    /**
     * 모델이 생성될 때 호출되는 이벤트
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            Log::emergency('SocialInsurance 모델 생성 중', [
                'name' => $model->name,
                'data' => $model->toArray()
            ]);
        });
        
        static::created(function ($model) {
            Log::emergency('SocialInsurance 모델 생성됨', [
                'id' => $model->id,
                'name' => $model->name
            ]);
        });
        
        static::updating(function ($model) {
            Log::emergency('SocialInsurance 모델 업데이트 중', [
                'id' => $model->id,
                'name' => $model->name,
                'data' => $model->toArray()
            ]);
        });
    }
} 