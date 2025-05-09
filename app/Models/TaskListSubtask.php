<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskListSubtask extends Model
{
    use HasFactory;

    protected $table = 'task_list_subtasks';
    
    protected $fillable = [
        'task_list_id',
        'description',
        'order',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];
    
    /**
     * 'completed' 필드를 'is_completed' 속성으로 접근할 수 있도록 함
     */
    public function getIsCompletedAttribute()
    {
        return $this->completed;
    }
    
    /**
     * 'is_completed' 속성을 통해 'completed' 필드를 설정
     */
    public function setIsCompletedAttribute($value)
    {
        $this->attributes['completed'] = $value;
    }

    /**
     * 이 하위업무가 속한 상위 업무를 가져옵니다.
     */
    public function taskList()
    {
        return $this->belongsTo(TaskList::class);
    }
} 