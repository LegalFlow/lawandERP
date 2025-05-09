<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'user_id',
        'request_type',
        'date_type',
        'start_date',
        'end_date',
        'specific_date',
        'content',
        'status',
        'admin_comment',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'specific_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // 사용자와의 관계 (신청자)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 처리자와의 관계
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // 첨부 파일과의 관계
    public function files()
    {
        return $this->hasMany(RequestFile::class);
    }
    
    // 랜덤 신청서 번호 생성 메서드
    public static function generateRequestNumber()
    {
        $prefix = 'REQ';
        $date = date('ymd');
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        
        return $prefix . $date . $random;
    }
} 