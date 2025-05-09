<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionDiv extends Model
{
    // timestamps 비활성화
    public $timestamps = false;

    // 테이블 이름 지정
    protected $table = 'correction_div';

    // Mass Assignment 허용 필드
    protected $fillable = [
        'court_name',
        'court_department',
        'case_number',
        'case_idx',
        'name',
        'document_name',
        'shipment_date',
        'deadline',
        'submission_status',
        'receipt_date',
        'receipt_status',
        'case_manager',
        'case_state',
        'consultant',
        'pdf_path',
        'summit_date',
        'document_type',
        'command',
        'order'
    ];

    // 날짜 형식으로 다룰 필드 지정
    protected $dates = [
        'shipment_date',
        'receipt_date',
        'summit_date',
        'deadline'
    ];

    // 기본 정렬 설정 - 구글 시트 순서 유지를 위해 발송일자 내림차순만 설정
    protected $orderBy = [
        'shipment_date' => 'desc'
    ];

    // target_table과의 관계 설정을 위한 메서드
    public function target()
    {
        return $this->belongsTo(Target::class, 'case_number', 'case_number');
    }
} 