<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Law extends Model
{
    protected $table = 'laws';

    protected $fillable = [
        'title',
        'content',
        'registration_date',
        'enforcement_date',
        'status',
        'abolition_date'
    ];

    protected $casts = [
        'registration_date' => 'date',
        'enforcement_date' => 'date',
        'abolition_date' => 'date',
    ];

    // 시행여부 상태값 상수 정의
    const STATUS_IN_FORCE = '시행중';
    const STATUS_ABOLISHED = '폐기';

    // 가능한 상태값 목록
    public static function getStatusOptions()
    {
        return [
            self::STATUS_IN_FORCE => '시행중',
            self::STATUS_ABOLISHED => '폐기',
        ];
    }

    // 현재 로그인한 사용자가 관리자인지 확인하는 메소드
    public static function isAdminUser()
    {
        return Auth::check() && Auth::user()->is_admin;
    }

    // 관리자 권한 필요 작업에 대한 권한 체크 메소드
    public static function authorizeAdmin()
    {
        if (!self::isAdminUser()) {
            abort(403, '관리자 권한이 필요합니다.');
        }
    }
}