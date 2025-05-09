<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLogTask extends Model
{
    use HasFactory;

    protected $table = 'work_log_tasks';
    
    protected $fillable = [
        'work_log_id',
        'parent_id',
        'category_type',
        'category_detail',
        'description',
        'duration_minutes',
        'order',
        'start_time',
        'end_time',
    ];

    /**
     * 이 태스크가 속한 업무일지를 가져옵니다.
     */
    public function workLog()
    {
        return $this->belongsTo(WorkLog::class);
    }

    /**
     * 이 태스크의 상위 태스크를 가져옵니다.
     */
    public function parent()
    {
        return $this->belongsTo(WorkLogTask::class, 'parent_id');
    }

    /**
     * 이 태스크의 하위 태스크들을 가져옵니다.
     */
    public function children()
    {
        return $this->hasMany(WorkLogTask::class, 'parent_id')->orderBy('order');
    }

    /**
     * 이 태스크가 최상위 태스크인지 확인합니다.
     */
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    /**
     * 소요 시간을 시간:분 형식으로 변환합니다.
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_minutes) {
            return '';
        }
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        return $hours . '시간 ' . $minutes . '분';
    }
} 