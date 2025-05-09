<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CorrectionDiv;
use App\Models\Notification;
use App\Models\User;
use App\Models\Member;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckDismissalNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:check-dismissal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '개인회생절차개시신청 기각결정 및 면책신청 불허가결정에 대한 경위서 제출 통지를 자동으로 생성합니다.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::now('Asia/Seoul');
        $startDate = Carbon::create(2025, 1, 1);
        
        Log::info('기각/불허가결정 통지 체크 시작', [
            '기준일' => $today->format('Y-m-d')
        ]);

        // 관리자 조회
        $admin = User::where('is_admin', 1)->first();
        if (!$admin) {
            Log::error('관리자 사용자를 찾을 수 없습니다.');
            return Command::FAILURE;
        }

        // 기각결정과 불허가결정 보정서 조회
        $dismissals = CorrectionDiv::whereIn('document_name', [
                '개인회생절차개시신청 기각결정',
                '면책신청 불허가결정'
            ])
            ->where('shipment_date', '>=', $startDate)
            ->whereNotNull('case_manager')
            ->whereNotNull('consultant')
            ->get();

        foreach ($dismissals as $dismissal) {
            // 문서 종류에 따른 제목 설정
            $documentType = $dismissal->document_name === '개인회생절차개시신청 기각결정' 
                ? '개인회생절차개시신청 기각결정' 
                : '면책신청 불허가결정';
            
            $title = "{$dismissal->shipment_date} {$dismissal->case_number} {$documentType}에 대한 경위서 제출 요구";
            
            // 중복 통지 체크
            $existingNotification = Notification::where('title', $title)->exists();
            if ($existingNotification) {
                Log::info("이미 존재하는 통지 건너뛰기", [
                    '제목' => $title,
                    '사건번호' => $dismissal->case_number
                ]);
                continue;
            }

            // 담당자와 상담자의 user_id 조회
            $manager = Member::where('name', $dismissal->case_manager)
                ->first();
            $consultant = Member::where('name', $dismissal->consultant)
                ->first();

            if (!$manager || !$consultant) {
                Log::warning("담당자 또는 상담자 정보를 찾을 수 없음", [
                    '사건번호' => $dismissal->case_number,
                    '담당자' => $dismissal->case_manager,
                    '상담자' => $dismissal->consultant
                ]);
                continue;
            }

            $managerUser = User::where('name', $manager->name)->first();
            $consultantUser = User::where('name', $consultant->name)->first();

            if (!$managerUser || !$consultantUser) {
                Log::warning("사용자 정보를 찾을 수 없음", [
                    '담당자' => $manager->name,
                    '상담자' => $consultant->name,
                    '사건번호' => $dismissal->case_number
                ]);
                continue;
            }

            // 통지 생성
            try {
                Notification::create([
                    'type' => '사건기각에 대한 경위서',
                    'title' => $title,
                    'content' => "위 사건에 대한 {$documentType}이 되었는 바, 결정이 된 경위와 추후 사건관리 계획에 대해 구체적으로 답변해 주시기 바랍니다.",
                    'notified_user_id' => $managerUser->id,
                    'via_user_id' => $consultantUser->id,
                    'created_by' => $admin->id,
                    'response_required' => true,
                    'response_deadline' => 10,
                    'status' => '답변대기'
                ]);

                Log::info("기각/불허가결정 통지 생성 완료", [
                    '사건번호' => $dismissal->case_number,
                    '문서종류' => $documentType,
                    '담당자' => $dismissal->case_manager,
                    '상담자' => $dismissal->consultant
                ]);

                $this->info("{$dismissal->case_number} 사건의 {$documentType} 통지가 생성되었습니다.");

            } catch (\Exception $e) {
                Log::error("통지 생성 실패", [
                    '사건번호' => $dismissal->case_number,
                    '문서종류' => $documentType,
                    '에러' => $e->getMessage()
                ]);
                continue;
            }
        }

        $this->info('기각/불허가결정 통지 확인이 완료되었습니다.');
        return Command::SUCCESS;
    }
}