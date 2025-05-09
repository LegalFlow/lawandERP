<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskList extends Model
{
    use HasFactory;

    protected $table = 'task_lists';
    
    protected $fillable = [
        'user_id',
        'plan_date',
        'deadline',
        'category_type',
        'category_detail',
        'description',
        'status',
        'completion_date',
        'importance',
        'priority',
        'memo',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'deadline' => 'date',
        'completion_date' => 'date',
    ];

    /**
     * 이 업무 리스트를 작성한 사용자를 가져옵니다.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 완료 상태인지 확인합니다.
     */
    public function isCompleted()
    {
        return $this->status === '완료';
    }

    /**
     * 진행 중인지 확인합니다.
     */
    public function isInProgress()
    {
        return $this->status === '진행중';
    }

    /**
     * 진행 예정인지 확인합니다.
     */
    public function isPending()
    {
        return $this->status === '진행예정';
    }
} 