<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SalaryContract;
use App\Models\SalaryStatement;
use App\Models\Request;

class NotificationService
{
    public function getNotificationCounts($userId)
    {
        $counts = [
            'notifications' => Notification::where(function($query) use ($userId) {
                    $query->where('notified_user_id', $userId)
                          ->orWhere('via_user_id', $userId);
                })
                ->where('status', '답변대기')
                ->count(),
                
            'salary_contracts' => SalaryContract::where('user_id', $userId)
                ->whereNull('approved_at')
                ->count(),
                
            'salary_statements' => SalaryStatement::where('user_id', $userId)
                ->whereNull('approved_at')
                ->where('statement_date', '<=', now()->subDays(32))
                ->count(),
                
            'pending_requests' => Request::where('status', '승인대기')->count()
        ];

        $counts['total'] = $counts['notifications'] + 
                          $counts['salary_contracts'] + 
                          $counts['salary_statements'];
                          
        // 관리자인 경우 승인대기 신청서도 total에 포함
        if ($this->isAdmin($userId)) {
            $counts['total'] += $counts['pending_requests'];
        }

        return $counts;
    }
    
    // 사용자가 관리자인지 확인하는 메서드
    private function isAdmin($userId)
    {
        return \App\Models\User::where('id', $userId)
                              ->where('is_admin', true)
                              ->exists();
    }
}