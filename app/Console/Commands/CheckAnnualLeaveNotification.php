<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckAnnualLeaveNotification extends Command
{
    protected $signature = 'notification:check-annual-leave';
    protected $description = '연차 종료 6개월 전인 구성원들에게 자동으로 통지를 보냅니다.';

    public function handle()
    {
        $today = Carbon::now('Asia/Seoul');
        $oneYearAgo = $today->copy()->subYear();
        $sixMonthsFromNow = $today->copy()->addMonths(6)->startOfDay();
        
        Log::info('연차촉진 통지 체크 시작', [
            '기준일' => $today->format('Y-m-d'),
            '1년전 기준일' => $oneYearAgo->format('Y-m-d'),
            '6개월후 기준일' => $sixMonthsFromNow->format('Y-m-d')
        ]);

        $members = Member::whereNotNull('annual_start_period')
            ->where('annual_start_period', '<=', $oneYearAgo)
            ->get();

        Log::info('조회된 1년이상 멤버', [
            '멤버 수' => $members->count(),
            '멤버 목록' => $members->pluck('name', 'annual_start_period')->toArray()
        ]);

        $admin = User::where('name', '김충환')->where('is_admin', 1)->first();
        if (!$admin) {
            Log::error('관리자 사용자를 찾을 수 없습니다.');
            $this->error('관리자 사용자를 찾을 수 없습니다.');
            return;
        }

        foreach ($members as $member) {
            if ($member->annual_end_period) {
                // 연차 종료일 계산 로직
                $endDate = Carbon::parse($member->annual_end_period)
                    ->setYear($today->year);
                
                if ($endDate->isPast()) {
                    $endDate->addYear();
                }
                
                Log::info('멤버 연차 종료일 체크', [
                    '멤버' => $member->name,
                    '원본연차종료일' => $member->annual_end_period,
                    '계산된연차종료일' => $endDate->format('Y-m-d'),
                    '6개월후날짜' => $sixMonthsFromNow->format('Y-m-d'),
                    '종료일일치여부' => $endDate->startOfDay()->eq($sixMonthsFromNow)
                ]);

                if ($endDate->startOfDay()->eq($sixMonthsFromNow)) {
                    $user = User::where('name', $member->name)->first();
                    
                    if ($user) {
                        Notification::create([
                            'type' => '연차촉진',
                            'title' => '연차휴가 사용 촉진 제도 (근로기준법 제61조 의거)',
                            'content' => $this->getNotificationContent(),
                            'notified_user_id' => $user->id,
                            'via_user_id' => null,
                            'created_by' => $admin->id,
                            'response_required' => true,
                            'response_deadline' => 10,
                            'status' => '답변대기'
                        ]);

                        Log::info("통지 생성 완료", ['멤버' => $member->name]);
                        $this->info("{$member->name} 님에게 연차촉진 통지가 생성되었습니다.");
                    } else {
                        Log::warning("User 정보를 찾을 수 없음", ['멤버' => $member->name]);
                    }
                }
            }
        }

        $this->info('연차촉진 통지 확인이 완료되었습니다.');
    }

    private function getNotificationContent()
    {
        return "안녕하세요. 법무법인 로앤입니다.\n"
            . "근로기준법 제61조에 따라, 귀하의 연차유급휴가의 사용을 촉진하고자 합니다.\n\n"
            . "제61조(연차 유급휴가의 사용 촉진) ① 사용자가 제60조제1항ㆍ제2항 및 제4항에 따른 유급휴가(계속하여 근로한 기간이 1년 미만인 근로자의 제60조제2항에 따른 유급휴가는 제외한다)의 사용을 촉진하기 위하여 다음 각 호의 조치를 하였음에도 불구하고 근로자가 휴가를 사용하지 아니하여 제60조제7항 본문에 따라 소멸된 경우에는 사용자는 그 사용하지 아니한 휴가에 대하여 보상할 의무가 없고, 제60조제7항 단서에 따른 사용자의 귀책사유에 해당하지 아니하는 것으로 본다. <개정 2012. 2. 1., 2017. 11. 28., 2020. 3. 31.>\n"
            . "1. 제60조제7항 본문에 따른 기간이 끝나기 6개월 전을 기준으로 10일 이내에 사용자가 근로자별로 사용하지 아니한 휴가 일수를 알려주고, 근로자가 그 사용 시기를 정하여 사용자에게 통보하도록 서면으로 촉구할 것\n"
            . "2. 제1호에 따른 촉구에도 불구하고 근로자가 촉구를 받은 때부터 10일 이내에 사용하지 아니한 휴가의 전부 또는 일부의 사용 시기를 정하여 사용자에게 통보하지 아니하면 제60조제7항 본문에 따른 기간이 끝나기 2개월 전까지 사용자가 사용하지 아니한 휴가의 사용 시기를 정하여 근로자에게 서면으로 통보할 것\n"
            . "② 사용자가 계속하여 근로한 기간이 1년 미만인 근로자의 제60조제2항에 따른 유급휴가의 사용을 촉진하기 위하여 다음 각 호의 조치를 하였음에도 불구하고 근로자가 휴가를 사용하지 아니하여 제60조제7항 본문에 따라 소멸된 경우에는 사용자는 그 사용하지 아니한 휴가에 대하여 보상할 의무가 없고, 같은 항 단서에 따른 사용자의 귀책사유에 해당하지 아니하는 것으로 본다. <신설 2020. 3. 31.>\n"
            . "1. 최초 1년의 근로기간이 끝나기 3개월 전을 기준으로 10일 이내에 사용자가 근로자별로 사용하지 아니한 휴가 일수를 알려주고, 근로자가 그 사용 시기를 정하여 사용자에게 통보하도록 서면으로 촉구할 것. 다만, 사용자가 서면 촉구한 후 발생한 휴가에 대해서는 최초 1년의 근로기간이 끝나기 1개월 전을 기준으로 5일 이내에 촉구하여야 한다.\n"
            . "2. 제1호에 따른 촉구에도 불구하고 근로자가 촉구를 받은 때부터 10일 이내에 사용하지 아니한 휴가의 전부 또는 일부의 사용 시기를 정하여 사용자에게 통보하지 아니하면 최초 1년의 근로기간이 끝나기 1개월 전까지 사용자가 사용하지 아니한 휴가의 사용 시기를 정하여 근로자에게 서면으로 통보할 것. 다만, 제1호 단서에 따라 촉구한 휴가에 대해서는 최초 1년의 근로기간이 끝나기 10일 전까지 서면으로 통보하여야 한다.\n\n"
            . "현재 귀하의 미사용 연차휴가 일수는 근태관리에서 확인할 수 있습니다.\n"
            . "이에 따라, 귀하께서는 해당 연차휴가의 사용시기를 정하여 발송일자로부터 10일 이내에 연차휴가 사용계획을 제출하여 주시기 바랍니다.\n\n"
            . "[예시]\n"
            . "다음과 같이 연차사용계획을 제출합니다.\n"
            . "2025-03-21, 2025-03-26, 2025-04-03 ...\n\n"
            . "단, 근로자는 연차휴가 사용계획에 따른 연차사용일을 변경할 수 있으니, 계획이 정해지지 않은 경우에는 임의로 작성하여 제출해도 무방합니다.\n"
            . "하지만, 연차종료일까지는 위 연차일수를 모두 소진해 주시기 바랍니다.";
    }

    // 메모리 사용량을 보기 좋게 변환하는 헬퍼 함수
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
