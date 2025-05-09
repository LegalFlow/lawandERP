<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_approved',
        'approved_at',
        'approved_by',
        'is_admin',
        'resident_id_front',
        'resident_id_back',
        'bank',
        'account_number',
        'phone_number',
        'postal_code',
        'address_main',
        'address_detail',
        'join_date',
        'leave_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'is_admin' => 'boolean',
        'join_date' => 'date',
        'leave_date' => 'date',
    ];

    /**
     * Check if the user is approved
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Get the user who approved this user
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve this user
     */
    public function approve(int $approverId): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approverId
        ]);
    }

    /**
     * Reject this user's approval
     */
    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null
        ]);
    }

    /**
     * User와 Member 간의 관계 정의
     */
    public function member()
    {
        return $this->hasOne(Member::class, 'name', 'name');
    }

    /**
     * 사용자가 피통지자로 지정된 통지들
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'notified_user_id');
    }

    /**
     * 사용자가 경유자로 지정된 통지들
     */
    public function viaNotifications()
    {
        return $this->hasMany(Notification::class, 'via_user_id');
    }

    /**
     * 사용자가 작성한 통지들
     */
    public function createdNotifications()
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    /**
     * 사용자가 작성한 답변들
     */
    public function notificationResponses()
    {
        return $this->hasMany(NotificationResponse::class, 'responded_by');
    }

    /**
     * 사용자가 업로드한 문서들
     */
    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * 사용자의 업무 리스트를 가져옵니다.
     */
    public function taskLists()
    {
        return $this->hasMany(TaskList::class);
    }
}
