<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class Transfer extends Model
{
    // 상수 정의
    const PAYMENT_TYPES = ['계좌이체', '서면납부'];
    const PAYMENT_STATUS = ['납부대기', '납부완료'];
    const APPROVAL_STATUS = ['승인대기', '승인완료'];
    const PAYMENT_TARGETS = [
        '신건접수', '민사예납', '송달료환급', '환불', '과오납', '즉시항고', '추완항고', 
        '금지명령', '중지명령', '소송구조', '집행정지', 
        '압류중지', '기타'
    ];

    protected $fillable = [
        'payment_type',
        'payment_target',
        'client_name',
        'court_name',
        'case_number',
        'consultant',
        'manager',
        'virtual_account',
        'payment_amount',
        'memo',
        'payment_status',
        'payment_completed_at',
        'approval_status',
        'approved_at',
        'created_by',
        'updated_by',
        'approved_by',
        'del_flag',
        'bank',
        'error_code'
    ];

    protected $casts = [
        'payment_completed_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'del_flag' => 'boolean',
        'virtual_account' => 'string',
        'bank' => 'string'
    ];

    // 파일 관계 설정
    public function files()
    {
        return $this->hasMany(TransferFile::class);
    }

    // 생성자 관계 설정
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 수정자 관계 설정
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 승인자 관계 설정
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // 상태 변경 로깅
    protected static function boot()
    {
        parent::boot();

        // 글로벌 스코프 추가
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $builder->where('del_flag', false);
        });

        static::created(function ($transfer) {
            Log::info("이체요청 생성", [
                'id' => $transfer->id,
                'created_by' => $transfer->created_by
            ]);
        });

        static::updated(function ($transfer) {
            if ($transfer->isDirty('approval_status')) {
                Log::info("이체요청 승인상태 변경", [
                    'id' => $transfer->id,
                    'old_status' => $transfer->getOriginal('approval_status'),
                    'new_status' => $transfer->approval_status,
                    'updated_by' => $transfer->updated_by
                ]);
            }
            Log::info("이체요청 수정", [
                'id' => $transfer->id,
                'updated_by' => $transfer->updated_by
            ]);
        });

        static::deleted(function ($transfer) {
            Log::info("이체요청 삭제", [
                'id' => $transfer->id,
                'deleted_by' => auth()->id()
            ]);
        });
    }
}