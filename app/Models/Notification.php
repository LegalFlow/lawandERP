<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'type',
        'title',
        'content',
        'notified_user_id',
        'via_user_id',
        'created_by',
        'response_required',
        'response_deadline',
        'status'
    ];

    protected $casts = [
        'response_required' => 'boolean',
        'response_deadline' => 'integer',
        'responded_at' => 'datetime',
    ];

    // 상태값 상수 정의
    const STATUS_WAITING = '답변대기';
    const STATUS_COMPLETED = '답변완료';
    const STATUS_NOT_REQUIRED = '답변불요';

    // 기본값 설정
    protected $attributes = [
        'status' => self::STATUS_WAITING
    ];

    // 가능한 상태값들 조회
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_WAITING,
            self::STATUS_COMPLETED,
            self::STATUS_NOT_REQUIRED
        ];
    }

    // 가능한 타입들 조회
    public static function getAvailableTypes()
    {
        return [
            '연차촉진',
            '사건기각에 대한 경위서',
            '지각에 대한 경위서',
            '제재',
            '보상'
            // 새로운 타입은 여기에 추가만 하면 됨
        ];
    }

    // 피통지자와의 관계
    public function notifiedUser()
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }

    // 경유자와의 관계
    public function viaUser()
    {
        return $this->belongsTo(User::class, 'via_user_id');
    }

    // 작성자와의 관계
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 답변과의 관계
    public function response()
    {
        return $this->hasOne(NotificationResponse::class);
    }

    // deadline이 언제인지 계산하여 반환
    public function getDeadlineAttribute()
    {
        if (!$this->response_deadline) {
            return null;
        }
        return $this->created_at->addDays($this->response_deadline);
    }

    // 남은 기간 계산
    public function getRemainingDaysAttribute()
    {
        if (!$this->response_deadline) {
            return null;
        }

        // 생성일로부터 답변기한까지의 날짜 계산
        $deadline = $this->created_at->copy()->addDays($this->response_deadline);
        
        // 현재 날짜와의 차이를 일 단위로 계산 (소수점 제거)
        return (int)now()->startOfDay()->diffInDays($deadline, false);
    }
}