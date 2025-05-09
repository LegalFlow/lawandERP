<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    use HasFactory;

    protected $table = 'work_logs';
    
    protected $fillable = [
        'user_id',
        'log_date',
        'total_duration_minutes',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    /**
     * 이 업무일지를 작성한 사용자를 가져옵니다.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 이 업무일지에 속한 모든 태스크를 가져옵니다.
     */
    public function tasks()
    {
        return $this->hasMany(WorkLogTask::class);
    }

    /**
     * 이 업무일지에 속한 최상위 태스크만 가져옵니다.
     */
    public function rootTasks()
    {
        return $this->hasMany(WorkLogTask::class)->whereNull('parent_id')->orderBy('order');
    }
} 