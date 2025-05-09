<?php

namespace App\Http\Controllers;

use App\Models\Workhour;
use App\Models\Member;
use App\Models\User;
use App\Helpers\WorkhourScheduleGenerator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WorkhourController extends Controller
{
    private const REGIONS = ['서울', '대전', '부산'];

    public function index()
    {
        // 현재 로그인한 사용자 정보 가져오기
        $user = Auth::user();
        
        // 선택 가능한 담당자 목록 가져오기
        if ($user->is_admin) {
            // 관리자인 경우 모든 담당자 목록
            $members = Member::select('name')->get();
        } else {
            // 일반 사용자인 경우 자신의 정보만
            $members = Member::select('name')
                           ->where('name', $user->name)
                           ->get();
        }
        
        // 기본 선택값 설정 (로그인한 사용자의 이름)
        $defaultMember = $user->name;
        
        return view('workhours.index', compact('members', 'defaultMember'));
    }

    // 나 메서드들은 동일...
    public function getScheduleStats(Request $request)
    {
        try {
            $startDate = Carbon::parse(substr($request->start_date, 0, 10))->format('Y-m-d');
            $endDate = Carbon::parse(substr($request->end_date, 0, 10))->format('Y-m-d');
            $filterType = $request->filter_type;
            $taskType = $request->task_type;
            $myScheduleOnly = $request->my_schedule === 'true';

            \Log::info('Request Parameters', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'filterType' => $filterType,
                'taskType' => $taskType
            ]);

            $query = Workhour::query()
                ->whereBetween('work_date', [$startDate, $endDate])
                ->where('status', '!=', '공휴일')
                ->filterByAffiliation($filterType)
                ->filterByTask($taskType);

            // 내 일정만 보기가 체크된 경우
            if ($myScheduleOnly) {
                $query->where('member', Auth::user()->name);
            }

            $schedules = $query->get()
                ->groupBy(function($item) {
                    return Carbon::parse($item->work_date)->format('Y-m-d');
                });

            $result = $schedules->map(function ($daySchedules) {
                // 시간대별로 그룹화하기 전에 정렬
                $sortedSchedules = $daySchedules->sort(function ($a, $b) {
                    // 휴무와 연차는 항상 마지막으로
                    if (in_array($a->status, ['휴무', '연차']) && !in_array($b->status, ['휴무', '연차'])) {
                        return 1;
                    }
                    if (!in_array($a->status, ['휴무', '연차']) && in_array($b->status, ['휴무', '연차'])) {
                        return -1;
                    }
                    
                    // 휴무와 연차 사이의 정렬
                    if ($a->status === '휴무' && $b->status === '연차') {
                        return -1;
                    }
                    if ($a->status === '연차' && $b->status === '휴무') {
                        return 1;
                    }
                    
                    // 일반 일정들의 정렬
                    if (!in_array($a->status, ['휴무', '연차']) && !in_array($b->status, ['휴무', '연차'])) {
                        // start_time으로 먼저 정렬
                        if ($a->start_time !== $b->start_time) {
                            return $a->start_time <=> $b->start_time;
                        }
                        
                        // start_time이 같으면 end_time으로 정렬
                        if ($a->end_time !== $b->end_time) {
                            return $a->end_time <=> $b->end_time;
                        }
                        
                        // start_time과 end_time이 같으면 재택이 아닌 것을 먼저
                        if ($a->status === '재택' && $b->status !== '재택') {
                            return 1;
                        }
                        if ($a->status !== '재택' && $b->status === '재택') {
                            return -1;
                        }
                    }
                    
                    return 0;
                });

                // 정렬된 데이터로 그룹화 진행
                return $sortedSchedules->groupBy(function ($schedule) {
                    if (in_array($schedule->status, ['휴무', '연차'])) {
                        return $schedule->status;
                    }
                    return $schedule->start_time . '-' . $schedule->end_time;
                })->map(function ($timeGroup) {
                    $firstSchedule = $timeGroup->first();
                    
                    // 버 정보 수집 부분 수정
                    $members = $timeGroup->map(function ($schedule) {
                        return [
                            'name' => $schedule->member,
                            'status' => match($schedule->status) {
                                '재택' => 'remote',
                                '오전반차' => 'morning-half',
                                '오후반차' => 'afternoon-half',
                                '휴무' => 'off',
                                '연차' => 'vacation',
                                default => ''
                            }
                        ];
                    })->sortBy('name')->values();  // 이름순으로 정렬
                    
                    if (in_array($firstSchedule->status, ['휴무', '연차'])) {
                        return [
                            'time' => $firstSchedule->status,
                            'count' => $timeGroup->count(),
                            'displayText' => $firstSchedule->status . ' ' . $timeGroup->count() . '명',
                            'members' => $members
                        ];
                    }
                    
                    $remoteCount = $timeGroup->where('status', '재택')->count();
                    $morningHalfCount = $timeGroup->where('status', '오전반차')->count();
                    $afternoonHalfCount = $timeGroup->where('status', '오후반차')->count();
                    
                    $timeDisplay = Carbon::parse($firstSchedule->start_time)->format('H:i') . 
                                 ' - ' . 
                                 Carbon::parse($firstSchedule->end_time)->format('H:i');
                    
                    $displayText = $timeDisplay . ' ' . $timeGroup->count() . '명';
                    
                    // 상태 정보를 배열로 수집
                    $statusInfo = [];
                    if ($remoteCount > 0) {
                        $statusInfo[] = '재택 ' . $remoteCount;
                    }
                    if ($morningHalfCount > 0) {
                        $statusInfo[] = '오전반차 ' . $morningHalfCount;
                    }
                    if ($afternoonHalfCount > 0) {
                        $statusInfo[] = '오후반차 ' . $afternoonHalfCount;
                    }

                    // 상태 정보가 있으면 괄호로 묶어서 추가
                    if (!empty($statusInfo)) {
                        $displayText .= ' (' . implode(', ', $statusInfo) . ')';
                    }
                    
                    return [
                        'time' => $timeDisplay,
                        'count' => $timeGroup->count(),
                        'remoteCount' => $remoteCount,
                        'morningHalfCount' => $morningHalfCount,
                        'afternoonHalfCount' => $afternoonHalfCount,
                        'displayText' => $displayText,
                        'members' => $members
                    ];
                })->values();
            });

            \Log::info('Final Data Structure', [
                'data' => $result->toArray()
            ]);

            // Collection을 배열로 변환하여 반환
            return response()->json($result->toArray());

        } catch (\Exception $e) {
            \Log::error('Schedule stats error: ' . $e->getMessage());
            return response()->json((object)[], 200);
        }
    }

    private function getIndividualStats($startDate, $endDate, $member)
    {
        $schedules = Workhour::whereBetween('work_date', [$startDate, $endDate])
            ->where('member', $member)
            ->orderBy('work_date')
            ->get();
            
        return response()->json([
            'type' => 'individual',
            'data' => $schedules
        ]);
    }

    private function getGroupStats($startDate, $endDate, $filterType)
    {
        $query = Workhour::whereBetween('work_date', [$startDate, $endDate])
            ->filterByAffiliation($filterType);
        
        $workTimeStats = $query->clone()
            ->workingHours()
            ->where('status', Workhour::STATUS_WORK)
            ->select(
                'work_date',
                'start_time',
                'end_time',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('work_date', 'start_time', 'end_time')
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->get();
            
        $vacationStats = $query->clone()
            ->vacation()
            ->select(
                'work_date',
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('work_date', 'status')
            ->get();
            
        $remoteStats = $query->clone()
            ->workFromHome()
            ->select(
                'work_date',
                'start_time',
                'end_time',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('work_date', 'start_time', 'end_time')
            ->get();

        return response()->json([
            'type' => 'group',
            'workTime' => $workTimeStats,
            'vacation' => $vacationStats,
            'remote' => $remoteStats
        ]);
    }

    public function getWeeklySchedule(Request $request)
    {
        try {
            // 권한 체크
            $user = Auth::user();
            
            $validated = $request->validate([
                'week' => 'required|date',
                'member' => 'required|exists:members,name'
            ]);

            // 관리자가 아니고 자신의 일정이 아닌 경우 권한 없음
            if (!$user->is_admin && $validated['member'] !== $user->name) {
                return response()->json(['message' => '권한이 없습니다.'], 403);
            }

            $startDate = Carbon::parse($validated['week']);
            $endDate = $startDate->copy()->addDays(6);

            $schedules = Workhour::whereBetween('work_date', [$startDate, $endDate])
                ->where('member', $validated['member'])
                ->orderBy('work_date')
                ->get()
                ->map(function ($schedule) {
                    return [
                        'work_date' => $schedule->work_date,
                        'member' => $schedule->member,
                        'work_time' => $this->getWorkTimeOption($schedule),
                        'status' => $this->getStatusOption($schedule),
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time
                    ];
                });

            return response()->json($schedules);
        } catch (\Exception $e) {
            \Log::error('Weekly Schedule Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json(['message' => '조회에 실패했습니다.'], 500);
        }
    }

    // 근무시간 옵션 변환 헬퍼 메서드
    private function getWorkTimeOption($schedule)
    {
        // work_time 필드 값만 확인
        if ($schedule->work_time && in_array($schedule->work_time, Workhour::WORK_TIME_OPTIONS)) {
            return $schedule->work_time;
        }

        // 휴무/연차 체크
        if (in_array($schedule->status, ['휴무', '연차'])) {
            return $schedule->status === '휴무' ? 'off' : 'vacation';
        }

        return '';
    }

    // 상태 옵션 변환 헬퍼 메서드
    private function getStatusOption($schedule)
    {
        return match($schedule->status) {
            '재택' => 'remote',
            '오전반차' => 'morning-half',
            '오후반차' => 'afternoon-half',
            default => ''
        };
    }

    public function store(Request $request)
    {
        try {
            // 권한 체크
            $user = Auth::user();
            if (!$user->is_admin && $request->member !== $user->name) {
                return response()->json(['message' => '권한이 없습니다.'], 403);
            }

            $validated = $request->validate([
                'work_date' => 'required|date',
                'member' => 'required|exists:members,name',
                'work_time' => 'required|in:' . implode(',', Workhour::WORK_TIME_OPTIONS),
                'start_time' => [
                    'nullable',
                    'date_format:H:i',
                    'required_if:status,'.Workhour::STATUS_WORK.','.Workhour::STATUS_REMOTE
                ],
                'end_time' => [
                    'nullable',
                    'date_format:H:i',
                    'required_if:status,'.Workhour::STATUS_WORK.','.Workhour::STATUS_REMOTE
                ],
                'status' => 'required|in:'.implode(',', Workhour::getValidStatuses())
            ]);

            $result = Workhour::updateOrCreate(
                [
                    'work_date' => $validated['work_date'],
                    'member' => $validated['member']
                ],
                [
                    'work_time' => $validated['work_time'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => $validated['status']
                ]
            );

            return response()->json([
                'message' => '저장되었습니다.',
                'data' => $result
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => '입력값이 올바르지 않습니다.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['message' => '저장에 실패했습니다.'], 500);
        }
    }

    public function storeWeekly(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'schedules' => 'required|array',
                'schedules.*.work_date' => 'required|date',
                'schedules.*.member' => 'required|string|max:50',
                'schedules.*.work_time' => 'required|in:' . implode(',', Workhour::WORK_TIME_OPTIONS),
                'schedules.*.start_time' => 'nullable|date_format:H:i',
                'schedules.*.end_time' => 'nullable|date_format:H:i',
                'schedules.*.status' => 'nullable|string'
            ]);

            // 권한 체크
            if (!$user->is_admin) {
                $memberNames = collect($validated['schedules'])->pluck('member')->unique();
                if ($memberNames->count() > 1 || $memberNames->first() !== $user->name) {
                    return response()->json(['message' => '권한이 없습니다.'], 403);
                }
            }

            DB::beginTransaction();
            
            // 주간 데이터를 멤버별로 그룹화
            $schedulesByMember = collect($validated['schedules'])
                ->groupBy('member');
                
            foreach ($schedulesByMember as $memberName => $memberSchedules) {
                // 멤버의 house_work 제한 조회
                $member = Member::where('name', $memberName)->first();
                if (!$member) {
                    throw new \Exception("멤버를 찾을 수 없습니다: {$memberName}");
                }

                // 재택 사용 여부 확인 및 카운트
                $remoteCount = collect($memberSchedules)
                    ->filter(fn($schedule) => ($schedule['status'] ?? '') === 'remote')
                    ->count();

                // 재택을 사용하는 경우에만 연차와의 합계 검사
                if ($remoteCount > 0) {
                    $vacationCount = collect($memberSchedules)
                        ->filter(fn($schedule) => $schedule['work_time'] === 'vacation')
                        ->count();

                    if (($remoteCount + $vacationCount) > $member->house_work) {
                        DB::rollBack();
                        return response()->json([
                            'message' => "{$memberName}님의 재택근무와 연차 합계가 허용된 횟수({$member->house_work}회)를 초과했습니다.",
                            'error_type' => 'house_work_limit_exceeded',
                            'member' => $memberName,
                            'limit' => $member->house_work,
                            'current' => [
                                'remote' => $remoteCount,
                                'vacation' => $vacationCount,
                                'total' => $remoteCount + $vacationCount
                            ]
                        ], 422);
                    }
                }

                foreach ($memberSchedules as $schedule) {
                    $status = $this->determineStatus($schedule['work_time'], $schedule['status'] ?? null);
                    
                    $startTime = null;
                    $endTime = null;
                    if (!in_array($status, ['휴무', '연차', '공휴일'])) {
                        $startTime = $schedule['start_time'];
                        $endTime = $schedule['end_time'];
                    }

                    // 데이터 저장
                    $result = Workhour::updateOrCreate(
                        [
                            'work_date' => $schedule['work_date'],
                            'member' => $schedule['member']
                        ],
                        [
                            'work_time' => $schedule['work_time'],
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'status' => $status
                        ]
                    );

                    $results[] = $result;
                }
            }

            DB::commit();
            return response()->json([
                'message' => '주간 일정이 저장되었습니다.',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Weekly Schedule Save Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => '저장에 실패했습니다.'], 500);
        }
    }

    private function determineStatus($workTime, $status)
    {
        if ($workTime === 'off') return '휴무';
        if ($workTime === 'vacation') return '연차';
        if ($workTime === 'holiday') return '공휴일';
        if ($status === 'remote') return '재택';
        if ($status === 'morning-half') return '오전반차';
        if ($status === 'afternoon-half') return '오후반차';
        return '근무';
    }

    public function resetWeekly(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validated = $request->validate([
                'week_start' => 'required|date',
                'member' => 'required|exists:members,name'
            ]);

            // 권한 체크
            if (!$user->is_admin && $validated['member'] !== $user->name) {
                return response()->json(['message' => '권한이 없습니다.'], 403);
            }

            $startDate = Carbon::parse($validated['week_start']);
            $endDate = $startDate->copy()->addDays(6);

            // 해당 주의 데이터 삭제
            Workhour::whereBetween('work_date', [$startDate, $endDate])
                ->where('member', $validated['member'])
                ->delete();

            return response()->json(['message' => '초기화가 완료되었습니다.']);

        } catch (\Exception $e) {
            \Log::error('Weekly Schedule Reset Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => '초기화에 실패했습니다.'], 500);
        }
    }

    /**
     * 선택된 주의 공휴일 유효성 검사
     */
    public function checkHolidays(Request $request)
    {
        try {
            // 관리자 권한 한번 더 체크
            if (!Auth::user()->is_admin) {
                return response()->json(['message' => '권한이 없습니다.'], 403);
            }

            $validated = $request->validate([
                'weekStart' => 'required|date',
                'holidays' => 'required|array',
                'holidays.*' => 'date_format:Y-m-d|distinct'
            ]);

            // 선택된 날짜들이 해당 주의 월~금 사이인지 인
            $weekStart = Carbon::parse($validated['weekStart']);
            $weekEnd = $weekStart->copy()->addDays(4); // 월~금까지만

            foreach ($validated['holidays'] as $holiday) {
                $holidayDate = Carbon::parse($holiday);
                if ($holidayDate->lessThan($weekStart) || $holidayDate->greaterThan($weekEnd)) {
                    return response()->json([
                        'message' => '선택된 공휴일이 해당 주의 범위를 벗어났습니다.'
                    ], 422);
                }
            }

            return response()->json([
                'message' => '공휴일 확인 완료',
                'holidays' => $validated['holidays']
            ]);

        } catch (\Exception $e) {
            \Log::error('공휴일 확인 오류: ' . $e->getMessage());
            return response()->json(['message' => '공휴일 확인 중 오류가 발생했습니다.'], 500);
        }
    }

    /**
     * 근무 스케줄 자동 생성
     */
    public function autoGenerate(Request $request)
    {
        try {
            // 관리자 권한 체크
            if (!Auth::user()->is_admin) {
                return response()->json(['message' => '권한이 없습니다.'], 403);
            }

            $validated = $request->validate([
                'weekStart' => 'required|date',
                'holidays' => 'array',
                'holidays.*' => 'date_format:Y-m-d|distinct',
                'selectedMembers' => 'required|array',
                'selectedMembers.*' => 'exists:members,id'
            ]);

            // holidays가 없으면 빈 배열로 초기화
            $holidays = $validated['holidays'] ?? [];

            // 트랜잭션 시작
            DB::beginTransaction();

            try {
                // 1. 해당 주의 기존 데이터 삭제 (선택된 멤버들에 대해서만)
                $weekStart = Carbon::parse($validated['weekStart']);
                $weekEnd = $weekStart->copy()->addDays(6);
                
                // 선택된 멤버들의 이름 목록 조회
                $selectedMembers = Member::whereIn('id', $validated['selectedMembers'])
                    ->get();

                Workhour::whereBetween('work_date', [
                    $weekStart->format('Y-m-d'), 
                    $weekEnd->format('Y-m-d')
                ])
                ->whereIn('member', $selectedMembers->pluck('name'))
                ->delete();

                // 2. 법률컨설팅팀 자동생성
                $this->generateTeamSchedule(
                    '법률컨설팅팀', 
                    $weekStart, 
                    $holidays, 
                    $selectedMembers->where('task', '법률컨설팅팀')
                );

                // 3. 사건관리팀 자동생성
                $this->generateTeamSchedule(
                    '사건관리팀', 
                    $weekStart, 
                    $holidays,
                    $selectedMembers->where('task', '사건관리팀')
                );

                DB::commit();
                return response()->json(['message' => '근무 스케줄이 자동으로 생성되었습니다.']);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('스케줄 자동생성 오류: ' . $e->getMessage());
            return response()->json(['message' => '스케줄 생성 중 오류가 발생했습니다.'], 500);
        }
    }

    /**
     * 팀별 스케줄 생성
     */
    private function generateTeamSchedule($teamTask, $weekStart, $holidays, $selectedMembers)
    {
        \Log::info('generateTeamSchedule 시작', [
            'team' => $teamTask,
            'weekStart' => $weekStart,
            'holidays' => $holidays,
            'selectedMembers' => $selectedMembers->pluck('name')
        ]);

        // Collection 랜덤 정렬
        $members = $selectedMembers->shuffle();

        \Log::info('랜덤 정렬 후 멤버 순서', [
            'shuffledMembers' => $members->pluck('name')
        ]);

        // 스케줄 생성 서비스 호출
        $scheduleGenerator = new WorkhourScheduleGenerator($members, $weekStart, $holidays);
        
        \Log::info('스케줄 생성 시작', [
            'workDays' => $scheduleGenerator->getWorkDays()
        ]);
        
        $scheduleGenerator->generate();
        
        \Log::info('스케줄 생성 완료');
    }

    public function getEligibleMembers()
    {
        try {
            // 조건을 만족하는 구성원 목록 조회
            $members = Member::where('status', '재직')
                ->whereIn('task', ['법률컨설팅팀', '사건관리팀'])
                ->select('id', 'name', 'task', 'affiliation', 'status')
                ->orderBy('task')
                ->orderBy('name')
                ->get();
            
            return response()->json($members);
        } catch (\Exception $e) {
            \Log::error('Eligible members fetch error: ' . $e->getMessage());
            return response()->json(['message' => '구성원 목록 조회에 실패했습니다.'], 500);
        }
    }
}
