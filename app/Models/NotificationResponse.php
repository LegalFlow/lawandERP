<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'content',
        'responded_by',
        'responded_at'
    ];

    protected $casts = [
        'responded_at' => 'datetime'
    ];

    // 답변자와의 관계
    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // 통지와의 관계
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}