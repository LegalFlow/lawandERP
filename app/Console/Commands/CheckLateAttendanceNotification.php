<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\Notification;
use App\Models\User;
use App\Models\Workhour;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckLateAttendanceNotification extends Command
{
    protected $signature = 'notification:check-late-attendance';
    protected $description = '분기별 지각 3회 이상인 구성원들에게 자동으로 통지를 보냅니다.';

    public function handle()
    {
        $today = Carbon::now('Asia/Seoul');
        
        // 특별 체크 기간 (2025-01-21 ~ 2025-03-31) 확인
        $specialStartDate = Carbon::create(2025, 1, 21);
        $specialEndDate = Carbon::create(2025, 3, 31);
        
        if ($today->between($specialStartDate, $specialEndDate)) {
            $quarterStart = $specialStartDate;
            $quarterEnd = $specialEndDate;
        } else {
            // 일반 분기 계산
            $currentQuarter = ceil($today->month / 3);
            $quarterStart = Carbon::create($today->year, ($currentQuarter - 1) * 3 + 1, 1)->startOfMonth();
            $quarterEnd = Carbon::create($today->year, $currentQuarter * 3, 1)->endOfMonth();
        }

        Log::info('지각 통지 체크 시작', [
            '기준일' => $today->format('Y-m-d'),
            '분기시작일' => $quarterStart->format('Y-m-d'),
            '분기종료일' => $quarterEnd->format('Y-m-d')
        ]);

        $admin = User::where('name', '김충환')->where('is_admin', 1)->first();
        if (!$admin) {
            Log::error('관리자 사용자를 찾을 수 없습니다.');
            return;
        }

        $members = Member::all();
        foreach ($members as $member) {
            // 분기 지각 횟수 계산
            $quarterlyLateCount = Workhour::where('member', $member->name)
                ->whereBetween('work_date', [
                    $quarterStart->format('Y-m-d'),
                    $quarterEnd->format('Y-m-d')
                ])
                ->where('attendance', '지각')
                ->count();

            if ($quarterlyLateCount >= 3) {
                $user = User::where('name', $member->name)->first();
                
                if ($user) {
                    $quarter = ceil($today->month / 3);
                    $year = $today->year;
                    
                    $title = "{$year}년 {$quarter}분기 지각 {$quarterlyLateCount}회에 대한 경위서 제출 요구";
                    
                    // 동일한 분기, 동일한 지각횟수, 동일한 사용자에 대한 통지가 있는지 확인
                    $existingNotification = Notification::where('notified_user_id', $user->id)
                        ->where('type', '지각에 대한 경위서')
                        ->where('title', $title)
                        ->exists();
                    
                    if (!$existingNotification) {
                        $content = "회사는 원활한 업무 수행과 동료 간의 상호 존중을 위해 근무시간 준수를 매우 중요하게 생각합니다.\n"
                            . "잦은 지각은 제때 출근하는 구성원들에게 부정적 영향을 미치고 근로 의욕을 떨어뜨릴 수 있습니다.\n"
                            . "귀하께서는 {$year}년 {$quarter}분기 중 {$quarterlyLateCount}회 지각하였으므로 이에 대한 지각 사유 및 재발 방지 대책 등을 성실하게 기재하여 제출해 주시기 바랍니다.";

                        Notification::create([
                            'type' => '지각에 대한 경위서',
                            'title' => $title,
                            'content' => $content,
                            'notified_user_id' => $user->id,
                            'via_user_id' => null,
                            'created_by' => $admin->id,
                            'response_required' => true,
                            'response_deadline' => 10,
                            'status' => '답변대기'
                        ]);

                        Log::info("지각 통지 생성 완료", [
                            '멤버' => $member->name,
                            '분기' => "{$year}년 {$quarter}분기",
                            '지각횟수' => $quarterlyLateCount
                        ]);
                        
                        $this->info("{$member->name} 님에게 지각 경위서 통지가 생성되었습니다.");
                    }
                } else {
                    Log::warning("User 정보를 찾을 수 없음", ['멤버' => $member->name]);
                }
            }
        }

        $this->info('지각 통지 확인이 완료되었습니다.');
    }
}
