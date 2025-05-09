<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $table = 'target_table';
    
    // 기본 키 설정
    protected $primaryKey = 'idx_TblCase';
    
    // 기본 키가 auto-increment가 아님을 명시
    public $incrementing = false;
    
    // 기본 키의 타입이 문자열인 경우 지정
    protected $keyType = 'string';  // idx_TblCase가 문자열인 경우
    
    public $timestamps = false;  // 타임스탬프 비활성화
    
    protected $fillable = [
        'idx_TblCase',
        'create_dt',
        'name',
        'living_place',
        'Member',
        'case_state',
        'court_name',
        'case_number',
        'contract_date',
        'note',
        'div_case',
        'case_type',
        'lawyer_fee',
        'total_const_delivery',
        'stamp_fee',
        'total_debt_cert_cost',
        'phone'
    ];

    // Member 모델과의 관계 설정
    public function member()
    {
        return $this->belongsTo(Member::class, 'Member');
    }

    // CaseAssignment 모델과의 관계 설정 추가
    public function caseAssignment()
    {
        return $this->hasOne(CaseAssignment::class, 'case_idx', 'idx_TblCase');
    }
} 