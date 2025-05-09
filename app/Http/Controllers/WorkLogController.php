<?php

namespace App\Http\Controllers;

use App\Models\WorkLog;
use App\Models\WorkLogTask;
use App\Models\Workhour;
use App\Models\Target;
use App\Models\CaseAssignment;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Transaction2;
use App\Models\Transaction3;
use App\Models\IncomeEntry;
use App\Models\Member;
use App\Helpers\CaseStateHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkLogController extends Controller
{
    /**
     * 업무일지 메인 페이지를 표시합니다.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $user = Auth::user();
        $userId = $user->id;
        
        // 다른 직원의 업무일지를 볼 수 있음
        $selectedUserId = $request->input('user_id', $userId);
        
        // 모든 직원 목록
        $allUsers = \App\Models\User::where('is_approved', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $selectedUser = $selectedUserId == $userId ? $user : \App\Models\User::find($selectedUserId);
        
        $workLog = WorkLog::where('user_id', $selectedUserId)
            ->where('log_date', $date)
            ->first();
            
        if (!$workLog) {
            $workLog = new WorkLog([
                'user_id' => $selectedUserId,
                'log_date' => $date,
            ]);
            $workLog->save();
        }
        
        // 해당 날짜의 근로시간 정보 가져오기
        $workHour = null;
        $expectedWorkHours = 0;
        $expectedWorkMinutes = 0;
        
        if ($selectedUser && $selectedUser->name) {
            $workHour = Workhour::where('work_date', $date)
                ->where('member', $selectedUser->name)
                ->first();
                
            if ($workHour && $workHour->start_time && $workHour->end_time) {
                // 시작 시간과 종료 시간 처리
                $startTime = $workHour->start_time;
                $endTime = $workHour->end_time;
                
                // 디버깅을 위한 원본 시간 값 출력
                \Log::debug("원본 시간 - 시작: " . (is_object($startTime) ? $startTime->format('H:i:s') : $startTime));
                \Log::debug("원본 시간 - 종료: " . (is_object($endTime) ? $endTime->format('H:i:s') : $endTime));
                
                try {
                    // Carbon 객체로 변환 (이미 Carbon 객체인 경우 그대로 사용)
                    if (!($startTime instanceof \Carbon\Carbon)) {
                        $startTime = \Carbon\Carbon::parse($startTime);
                    }
                    
                    if (!($endTime instanceof \Carbon\Carbon)) {
                        $endTime = \Carbon\Carbon::parse($endTime);
                    }
                    
                    // 시간만 추출하여 새로운 Carbon 객체 생성
                    $startHour = $startTime->format('H');
                    $startMinute = $startTime->format('i');
                    $endHour = $endTime->format('H');
                    $endMinute = $endTime->format('i');
                    
                    \Log::debug("시간 추출 - 시작: {$startHour}:{$startMinute}, 종료: {$endHour}:{$endMinute}");
                    
                    // 분으로 변환
                    $startMinutes = ($startHour * 60) + $startMinute;
                    $endMinutes = ($endHour * 60) + $endMinute;
                    
                    \Log::debug("분 변환 - 시작: {$startMinutes}, 종료: {$endMinutes}");
                    
                    // 종료 시간이 시작 시간보다 작으면 다음 날로 간주
                    if ($endMinutes < $startMinutes) {
                        $endMinutes += 24 * 60; // 24시간(1440분) 추가
                        \Log::debug("종료 시간 조정 (다음 날): {$endMinutes}");
                    }
                    
                    // 총 근무 시간 (분)
                    $totalMinutes = $endMinutes - $startMinutes;
                    \Log::debug("총 근무 시간(분): {$totalMinutes}");
                    
                    // 중식시간 제외
                    if ($totalMinutes >= 360) { // 6시간 이상
                        $totalMinutes -= 60; // 1시간 제외
                        \Log::debug("중식시간 1시간 제외 후: {$totalMinutes}");
                    }
                    
                    if ($totalMinutes >= 720) { // 12시간 이상
                        $totalMinutes -= 60; // 추가 1시간 제외
                        \Log::debug("추가 1시간 제외 후: {$totalMinutes}");
                    }
                    
                    // 시간으로 변환
                    $expectedWorkHours = floor($totalMinutes / 60);
                    $expectedWorkMinutes = $totalMinutes % 60;
                    \Log::debug("최종 근로예정시간: {$expectedWorkHours}시간 {$expectedWorkMinutes}분");
                } catch (\Exception $e) {
                    \Log::error("시간 계산 오류: " . $e->getMessage());
                    $expectedWorkHours = 0;
                    $expectedWorkMinutes = 0;
                }
            }
        }
        
        // 시간 정보를 기반으로 태스크 정렬
        $rootTasks = WorkLogTask::where('work_log_id', $workLog->id)
            ->whereNull('parent_id')
            ->orderByRaw('CASE WHEN start_time IS NOT NULL THEN 0 ELSE 1 END') // 시작시각이 있는 태스크 우선
            ->orderBy('start_time') // 시작시각 기준 정렬
            ->orderBy('order') // 기존 순서 유지
            ->get();
        
        // 신규 태스크 추가 시 시작시각 기본값 설정을 위한 정보
        $defaultStartTime = null;
        
        // 1. 해당 날짜의 태스크 중 종료시각이 있는 태스크 확인
        $tasksWithEndTime = WorkLogTask::where('work_log_id', $workLog->id)
            ->whereNull('parent_id')
            ->whereNotNull('end_time')
            ->orderBy('end_time', 'desc')
            ->get();
            
        if ($tasksWithEndTime->count() > 0) {
            // 종료시각이 있는 태스크가 있으면 가장 늦은 종료시각을 기본값으로 설정
            $latestTask = $tasksWithEndTime->first();
            // 초 단위 정보 제거하고 HH:MM 형식으로 변환
            $defaultStartTime = is_object($latestTask->end_time) 
                ? $latestTask->end_time->format('H:i') 
                : Carbon::parse($latestTask->end_time)->format('H:i');
        } else if ($workHour && $workHour->start_time) {
            // 종료시각이 있는 태스크가 없으면 근무시간 시작시각을 기본값으로 설정
            $defaultStartTime = is_object($workHour->start_time) 
                ? $workHour->start_time->format('H:i') 
                : Carbon::parse($workHour->start_time)->format('H:i');
        }
        
        // 카테고리 데이터
        $categories = [
            '개발' => ['새 기능 개발', '버그 수정', '리팩토링', '코딩', 'API 개발 및 연동', '시스템 설계', 'DB 설계', 'UI/UX 설계', '단위 테스트', '통합 테스트', 'QA 테스트 지원', '코드 문서화', '기술 문서 작성', 'API 문서 작성', '팀 미팅', '고객 응대', '이해관계자 미팅', '코드 리뷰', '기술 학습', '리서치', 'POC 작업', '배포', '인프라 관리', 'CI/CD 인프라', '모니터링', '성능 최적화', '보안 패치'],
            '회생' => ['신건상담', '신건계약', '방문상담', '서류발급', '신청서작성', '신청서제출', '고객 응대', '법원 응대', '보정서작성', '보정서제출', '기타보정작성', '기타보정제출', '외근', '기타'],
            '지원' => ['급여', '퇴직급여', '입퇴사', '세무', '인사', '면담', '서류작업', '금융', '사대보험', '재무', '기타']
        ];
        
        // 사용자의 기본 카테고리 설정
        $defaultCategory = '회생'; // 기본값
        
        // 사용자와 연결된 멤버 정보 가져오기
        $member = $selectedUser->member;
        
        if ($member && $member->task) {
            // 멤버의 task 값에 따라 기본 카테고리 설정
            if (strpos($member->task, '개발') !== false) {
                $defaultCategory = '개발';
            } elseif (strpos($member->task, '지원') !== false) {
                $defaultCategory = '지원';
            }
        }
        
        // 해당 날짜의 신규상담 데이터 가져오기
        $consultations = [];
        if ($selectedUser && $selectedUser->name) {
            $consultations = Target::where('Member', $selectedUser->name)
                ->whereDate('create_dt', $date)
                ->where(function($query) {
                    $query->where('del_flag', '!=', 1)
                          ->orWhereNull('del_flag');
                })
                ->orderBy('create_dt', 'asc')
                ->get();
        }
        
        // 해당 날짜의 신규계약 데이터 가져오기
        $contracts = [];
        if ($selectedUser && $selectedUser->name) {
            $contracts = Target::where('Member', $selectedUser->name)
                ->where('contract_date', $date)
                ->where(function($query) {
                    $query->where('del_flag', '!=', 1)
                          ->orWhereNull('del_flag');
                })
                ->where('lawyer_fee', '>', 0)
                ->orderBy('create_dt', 'asc')
                ->get();
        }
        
        // 해당 날짜의 배당 데이터 가져오기
        $assignments = [];
        if ($selectedUser && $selectedUser->name) {
            $assignments = CaseAssignment::where('assignment_date', $date)
                ->where(function($query) use ($selectedUser) {
                    $query->where('consultant', $selectedUser->name)
                          ->orWhere('case_manager', $selectedUser->name);
                })
                ->orderBy('assignment_date', 'asc')
                ->get();
        }
        
        // 해당 날짜의 신건제출 데이터 가져오기
        $submissions = [];
        if ($selectedUser && $selectedUser->name) {
            $submissions = CaseAssignment::where('summit_date', $date)
                ->where('case_manager', $selectedUser->name)
                ->orderBy('summit_date', 'asc')
                ->get();
        }
        
        // 해당 날짜의 보정서제출 데이터 가져오기
        $corrections = [];
        if ($selectedUser && $selectedUser->name) {
            $corrections = \App\Models\CorrectionDiv::where('summit_date', $date)
                ->where('case_manager', $selectedUser->name)
                ->where('document_type', '보정')
                ->orderBy('summit_date', 'asc')
                ->get();
        }
        
        // 해당 날짜의 기타보정제출 데이터 가져오기
        $otherCorrections = [];
        if ($selectedUser && $selectedUser->name) {
            $otherCorrections = \App\Models\CorrectionDiv::where('summit_date', $date)
                ->where('case_manager', $selectedUser->name)
                ->whereIn('document_type', ['명령', '기타', '예외'])
                ->where('submission_status', '!=', '미제출')
                ->orderBy('summit_date', 'asc')
                ->get();
        }
        
        // 해당 날짜의 입금내역 데이터 가져오기
        $payments = new Collection();
        if ($selectedUser && $selectedUser->name) {
            // 1. 효성CMS 데이터
            $cmsPayments = Payment::where('manager', $selectedUser->name)
                ->where('payment_date', $date)
                ->get()
                ->map(function($item) {
                    return [
                        'time' => Carbon::parse($item->created_at)->format('H시 i분'),
                        'account_info' => '효성CMS',
                        'customer_name' => $item->name,
                        'account_type' => $item->account,
                        'amount' => $item->payment_amount,
                        'source' => 'payments',
                        'created_at' => $item->created_at
                    ];
                });
            
            // 2. 서울신한 데이터
            $seoulTransactions = Transaction::where('manager', $selectedUser->name)
                ->where('date', $date)
                ->get()
                ->map(function($item) {
                    // 날짜 형식 확인 및 처리
                    $dateStr = is_string($item->date) ? $item->date : $item->date->format('Y-m-d');
                    // 날짜에 이미 시간이 포함되어 있는지 확인
                    if (strpos($dateStr, ' ') !== false) {
                        $dateStr = substr($dateStr, 0, 10); // 'YYYY-MM-DD' 부분만 추출
                    }
                    
                    return [
                        'time' => Carbon::parse($item->time)->format('H시 i분'),
                        'account_info' => '서울신한',
                        'customer_name' => $item->description,
                        'account_type' => $item->account,
                        'amount' => $item->amount,
                        'source' => 'transactions',
                        'created_at' => Carbon::parse($dateStr . ' ' . $item->time)
                    ];
                });
            
            // 3. 대전신한 데이터
            $daejeonTransactions = Transaction2::where('manager', $selectedUser->name)
                ->where('date', $date)
                ->get()
                ->map(function($item) {
                    // 날짜 형식 확인 및 처리
                    $dateStr = is_string($item->date) ? $item->date : $item->date->format('Y-m-d');
                    // 날짜에 이미 시간이 포함되어 있는지 확인
                    if (strpos($dateStr, ' ') !== false) {
                        $dateStr = substr($dateStr, 0, 10); // 'YYYY-MM-DD' 부분만 추출
                    }
                    
                    return [
                        'time' => Carbon::parse($item->time)->format('H시 i분'),
                        'account_info' => '대전신한',
                        'customer_name' => $item->description,
                        'account_type' => $item->account,
                        'amount' => $item->amount,
                        'source' => 'transactions2',
                        'created_at' => Carbon::parse($dateStr . ' ' . $item->time)
                    ];
                });
            
            // 4. 부산신한 데이터
            $busanTransactions = Transaction3::where('manager', $selectedUser->name)
                ->where('date', $date)
                ->get()
                ->map(function($item) {
                    // 날짜 형식 확인 및 처리
                    $dateStr = is_string($item->date) ? $item->date : $item->date->format('Y-m-d');
                    // 날짜에 이미 시간이 포함되어 있는지 확인
                    if (strpos($dateStr, ' ') !== false) {
                        $dateStr = substr($dateStr, 0, 10); // 'YYYY-MM-DD' 부분만 추출
                    }
                    
                    return [
                        'time' => Carbon::parse($item->time)->format('H시 i분'),
                        'account_info' => '부산신한',
                        'customer_name' => $item->description,
                        'account_type' => $item->account,
                        'amount' => $item->amount,
                        'source' => 'transactions3',
                        'created_at' => Carbon::parse($dateStr . ' ' . $item->time)
                    ];
                });
            
            // 5. 매출직접입력 데이터
            // 먼저 사용자와 연결된 Member 찾기
            $memberIds = Member::where('name', $selectedUser->name)->pluck('id')->toArray();
            
            $incomeEntries = new Collection();
            if (!empty($memberIds)) {
                $incomeEntries = IncomeEntry::whereIn('representative_id', $memberIds)
                    ->where('deposit_date', $date)
                    ->get()
                    ->map(function($item) {
                        return [
                            'time' => '', // 시간 정보 없음
                            'account_info' => '직접입력',
                            'customer_name' => $item->depositor_name,
                            'account_type' => $item->account_type,
                            'amount' => $item->amount,
                            'source' => 'income_entries',
                            'created_at' => $item->created_at
                        ];
                    });
            }
            
            // 모든 데이터 합치기
            $payments = $cmsPayments
                ->concat($seoulTransactions)
                ->concat($daejeonTransactions)
                ->concat($busanTransactions)
                ->concat($incomeEntries)
                ->sortBy('created_at'); // 시간순으로 정렬
        }
        
        return view('work_logs.index', [
            'workLog' => $workLog,
            'rootTasks' => $rootTasks,
            'selectedDate' => $date,
            'categories' => $categories,
            'defaultCategory' => $defaultCategory,
            'workHour' => $workHour,
            'expectedWorkHours' => $expectedWorkHours,
            'expectedWorkMinutes' => $expectedWorkMinutes,
            'consultations' => $consultations,
            'contracts' => $contracts,
            'assignments' => $assignments,
            'submissions' => $submissions,
            'corrections' => $corrections,
            'otherCorrections' => $otherCorrections,
            'payments' => $payments,
            'allUsers' => $allUsers,
            'selectedUserId' => $selectedUserId,
            'defaultStartTime' => $defaultStartTime,
        ]);
    }
    
    /**
     * 업무일지의 총 업무시간을 계산하여 업데이트합니다.
     */
    private function updateTotalDuration($workLogId)
    {
        $workLog = WorkLog::findOrFail($workLogId);
        
        // 모든 루트 태스크의 duration_minutes 합계 계산
        $totalDuration = WorkLogTask::where('work_log_id', $workLogId)
            ->whereNull('parent_id') // 루트 태스크만 계산 (하위 태스크는 시간 입력 필드가 없음)
            ->sum('duration_minutes');
        
        // 총 업무시간 업데이트
        $workLog->total_duration_minutes = $totalDuration;
        $workLog->save();
        
        return $totalDuration;
    }
    
    /**
     * 새 태스크를 추가합니다.
     */
    public function addTask(Request $request)
    {
        $request->validate([
            'work_log_id' => 'required|exists:work_logs,id',
            'description' => 'required|string',
            'parent_id' => 'nullable|exists:work_log_tasks,id',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
        ]);
        
        // 권한 체크 추가
        $workLog = WorkLog::findOrFail($request->work_log_id);
        if ($workLog->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['error' => '자신의 업무일지만 수정할 수 있습니다.'], 403);
            }
            return redirect()->back()->with('error', '자신의 업무일지만 수정할 수 있습니다.');
        }
        
        $maxOrder = WorkLogTask::where('work_log_id', $request->work_log_id)
            ->where('parent_id', $request->parent_id)
            ->max('order') ?? 0;
            
        $task = new WorkLogTask([
            'work_log_id' => $request->work_log_id,
            'parent_id' => $request->parent_id,
            'category_type' => $request->category_type,
            'category_detail' => $request->category_detail,
            'description' => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'order' => $maxOrder + 1,
        ]);
        
        // 시작시각과 종료시각이 있으면 소요시간 자동 계산
        if ($request->start_time && $request->end_time) {
            $task->duration_minutes = $this->calculateDuration($request->start_time, $request->end_time);
        }
        
        $task->save();
        
        // 상위 태스크이고 소요 시간이 입력된 경우 총 업무시간 업데이트
        if (!$request->parent_id && ($request->duration_minutes || ($request->start_time && $request->end_time))) {
            $this->updateTotalDuration($request->work_log_id);
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task]);
        }
        
        return redirect()->back();
    }
    
    /**
     * 태스크를 삭제합니다.
     */
    public function deleteTask(Request $request, $id)
    {
        $task = WorkLogTask::findOrFail($id);
        
        // 권한 체크 추가
        if ($task->workLog->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['error' => '자신의 업무일지만 수정할 수 있습니다.'], 403);
            }
            return redirect()->back()->with('error', '자신의 업무일지만 수정할 수 있습니다.');
        }
        
        $workLogId = $task->work_log_id;
        
        // 하위 태스크는 CASCADE 설정으로 자동 삭제됨
        $task->delete();
        
        // 상위 태스크인 경우 총 업무시간 업데이트
        if (!$task->parent_id) {
            $this->updateTotalDuration($workLogId);
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->back();
    }
    
    /**
     * 태스크를 업데이트합니다.
     */
    public function updateTask(Request $request, $id)
    {
        $task = WorkLogTask::findOrFail($id);
        
        // 권한 체크 추가
        if ($task->workLog->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['error' => '자신의 업무일지만 수정할 수 있습니다.'], 403);
            }
            return redirect()->back()->with('error', '자신의 업무일지만 수정할 수 있습니다.');
        }
        
        $workLogId = $task->work_log_id;
        
        // 업데이트할 필드 목록
        $fields = ['category_type', 'category_detail', 'description', 'duration_minutes', 'start_time', 'end_time'];
        
        // 요청에 포함된 필드만 업데이트
        $updateData = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->input($field);
            }
        }
        
        // 시작시각과 종료시각이 있으면 소요시간 자동 계산
        if (isset($updateData['start_time']) || isset($updateData['end_time'])) {
            $startTime = isset($updateData['start_time']) ? $updateData['start_time'] : $task->start_time;
            $endTime = isset($updateData['end_time']) ? $updateData['end_time'] : $task->end_time;
            
            if ($startTime && $endTime) {
                $updateData['duration_minutes'] = $this->calculateDuration($startTime, $endTime);
            }
        }
        
        if (!empty($updateData)) {
            $task->update($updateData);
        }
        
        // 상위 태스크이고 소요 시간이 변경된 경우 총 업무시간 업데이트
        if (!$task->parent_id && (isset($updateData['duration_minutes']) || isset($updateData['start_time']) || isset($updateData['end_time']))) {
            $this->updateTotalDuration($workLogId);
        }
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'task' => $task]);
        }
        
        return redirect()->back();
    }
    
    /**
     * 시작시각과 종료시각으로 소요시간을 계산합니다.
     */
    private function calculateDuration($startTime, $endTime)
    {
        if (!$startTime || !$endTime) {
            return 0;
        }
        
        try {
            // 시간 문자열 파싱 (HH:MM 형식)
            $startParts = explode(':', $startTime);
            $endParts = explode(':', $endTime);
            
            $startHours = (int)$startParts[0];
            $startMinutes = (int)$startParts[1];
            $endHours = (int)$endParts[0];
            $endMinutes = (int)$endParts[1];
            
            // 분으로 변환
            $startTotalMinutes = ($startHours * 60) + $startMinutes;
            $endTotalMinutes = ($endHours * 60) + $endMinutes;
            
            // 종료 시간이 시작 시간보다 작으면 다음 날로 간주
            if ($endTotalMinutes < $startTotalMinutes) {
                $endTotalMinutes += 24 * 60; // 24시간(1440분) 추가
            }
            
            // 소요 시간 계산 (분)
            return $endTotalMinutes - $startTotalMinutes;
        } catch (\Exception $e) {
            \Log::error("시간 계산 오류: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * 업무 리스트에서 태스크를 가져오는 모달을 표시합니다.
     */
    public function showImportTasksModal(Request $request)
    {
        $workLogId = $request->input('work_log_id');
        $workLog = WorkLog::findOrFail($workLogId);
        
        // 권한 체크 추가
        if ($workLog->user_id !== Auth::id()) {
            if ($request->ajax()) {
                return response()->json(['error' => '자신의 업무일지에만 업무를 가져올 수 있습니다.'], 403);
            }
            return redirect()->back()->with('error', '자신의 업무일지에만 업무를 가져올 수 있습니다.');
        }
        
        // 진행예정, 진행중 상태의 업무 리스트 항목만 가져오기
        $taskLists = \App\Models\TaskList::where('user_id', Auth::id())
            ->whereNotIn('status', ['완료', '보류', '기각'])
            ->orderBy('plan_date', 'asc')
            ->get();
        
        return view('work_logs.partials.import_tasks_modal', [
            'workLog' => $workLog,
            'taskLists' => $taskLists
        ]);
    }
    
    /**
     * 업무 리스트에서 선택한 태스크를 가져옵니다.
     */
    public function importTasks(Request $request)
    {
        $request->validate([
            'work_log_id' => 'required|exists:work_logs,id',
            'task_list_ids' => 'required|array',
            'task_list_ids.*' => 'exists:task_lists,id',
            'mark_as_completed' => 'nullable|array',
            'mark_as_completed.*' => 'boolean',
        ]);
        
        $workLogId = $request->input('work_log_id');
        $taskListIds = $request->input('task_list_ids');
        $markAsCompleted = $request->input('mark_as_completed', []);
        
        $workLog = WorkLog::findOrFail($workLogId);
        
        // 권한 확인
        if ($workLog->user_id !== Auth::id()) {
            return response()->json(['error' => '권한이 없습니다.'], 403);
        }
        
        $importedCount = 0;
        $maxOrder = WorkLogTask::where('work_log_id', $workLogId)
            ->whereNull('parent_id')
            ->max('order') ?? 0;
        
        foreach ($taskListIds as $taskListId) {
            $taskList = \App\Models\TaskList::findOrFail($taskListId);
            
            // 권한 확인
            if ($taskList->user_id !== Auth::id()) {
                continue;
            }
            
            // 업무일지에 태스크 추가
            $task = new WorkLogTask([
                'work_log_id' => $workLogId,
                'parent_id' => null,
                'category_type' => $taskList->category_type,
                'category_detail' => $taskList->category_detail,
                'description' => $taskList->description,
                'duration_minutes' => 0, // 기본값 0으로 설정
                'start_time' => null, // 시작시각은 null로 설정
                'end_time' => null, // 종료시각은 null로 설정
                'order' => ++$maxOrder,
            ]);
            
            $task->save();
            $importedCount++;
            
            // 업무 리스트 항목을 완료로 표시
            if (isset($markAsCompleted[$taskListId]) && $markAsCompleted[$taskListId]) {
                $taskList->status = '완료';
                $taskList->completion_date = Carbon::today();
                $taskList->save();
            }
        }
        
        // 총 업무시간 업데이트
        $this->updateTotalDuration($workLogId);
        
        return response()->json([
            'success' => true,
            'message' => "{$importedCount}개의 업무가 가져와졌습니다.",
            'imported_count' => $importedCount
        ]);
    }
} 