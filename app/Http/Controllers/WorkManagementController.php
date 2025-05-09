<?php

namespace App\Http\Controllers;

use App\Models\Workhour;
use App\Models\Member;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkManagementController extends Controller
{
    public function index(Request $request)
    {
        // 디버깅을 위한 로그 추가
        \Log::info('Work Management Filter Request:', $request->all());
        
        $yesterday = Carbon::now('Asia/Seoul')->subDay()->endOfDay();
        $members = Member::orderBy('name')->pluck('name');

        // 기간 필터의 기본값 설정 (오늘을 포함한 최근 3개월)
        $today = Carbon::now('Asia/Seoul')->format('Y-m-d');
        $threeMonthsAgo = Carbon::now('Asia/Seoul')->subMonths(3)->format('Y-m-d');
        
        // 기간 필터가 설정되지 않은 경우 기본값 적용
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $request->merge([
                'start_date' => $threeMonthsAgo,
                'end_date' => $today
            ]);
            \Log::info('Default date range applied: ' . $threeMonthsAgo . ' to ' . $today);
        }
        
        // 지역이나 팀 필터가 활성화된 경우 담당자 필터를 비활성화
        if ($request->filled('location') || $request->filled('team')) {
            $request->merge(['member' => '']);
            \Log::info('Member filter reset due to location/team filter');
        }
        // 담당자 필터가 활성화된 경우 지역과 팀 필터를 비활성화
        elseif ($request->filled('member')) {
            $request->merge(['location' => '', 'team' => '']);
            \Log::info('Location/team filters reset due to member filter');
        }
        
        // member 파라미터가 없을 경우 로그인한 사용자로 설정
        // 지역이나 팀 필터가 활성화된 경우에는 member 자동 설정을 하지 않음
        $hasLocationOrTeamFilter = $request->filled('location') || $request->filled('team');
                          
        // 지역이나 팀 필터가 없고, 담당자가 설정되지 않은 경우 기본 담당자 설정
        if (!$hasLocationOrTeamFilter && !$request->filled('member')) {
            $request->merge(['member' => auth()->user()->name]);
            \Log::info('Default member filter applied: ' . auth()->user()->name);
        }

        $selectedMemberAnnualPeriod = null;
        $totalAnnualLeave = null;
        $usedAnnualLeave = null;
        $adjustedAnnualLeave = null;
        $remainingAnnualLeave = null;

        if ($request->filled('member')) {
            $selectedMember = Member::where('name', $request->member)->first();
            if ($selectedMember) {
                $today = Carbon::now('Asia/Seoul');
                
                $adjustedEndDate = Carbon::parse($selectedMember->annual_end_period)
                    ->setYear($today->year);
                
                // 현재 날짜가 연차기간 마지막 날인 경우를 고려하여 로직 수정
                if ($adjustedEndDate->isPast() && !$today->isSameDay($adjustedEndDate)) {
                    $adjustedEndDate->addYear();
                }
                
                $adjustedStartDate = $adjustedEndDate->copy()
                    ->subYear()
                    ->addDay();
                
                $selectedMemberAnnualPeriod = [
                    'start_date' => $adjustedStartDate->format('Y-m-d'),
                    'end_date' => $adjustedEndDate->format('Y-m-d')
                ];

                $annualStartDate = Carbon::parse($selectedMember->annual_start_period);
                $yearsDiff = abs($today->diffInYears($annualStartDate));

                // 근속 기간에 따른 연차 일수 계산
                if ($yearsDiff < 1) {
                    // 1년 미만: 단순히 완료된 근무 월수만 계산 (정수로 변환)
                    $monthsDiff = (int)$annualStartDate->diffInMonths($today);
                    $totalAnnualLeave = min($monthsDiff, 11);
                } 
                elseif ($yearsDiff < 2) {
                    // 1-2년차 직원
                    // 직전 연차적용기간 계산
                    $previousPeriodStart = $annualStartDate;
                    $previousPeriodEnd = Carbon::parse($selectedMemberAnnualPeriod['start_date'])->subDay();

                    // 직전 기간 사용 연차 계산
                    $previousFullDayLeaves = Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [
                            $previousPeriodStart->format('Y-m-d'),
                            $previousPeriodEnd->format('Y-m-d')
                        ])
                        ->where('status', '연차')
                        ->count();

                    $previousHalfDayLeaves = Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [
                            $previousPeriodStart->format('Y-m-d'),
                            $previousPeriodEnd->format('Y-m-d')
                        ])
                        ->whereIn('status', ['오전반차', '오후반차'])
                        ->count();

                    $previousUsedLeave = $previousFullDayLeaves + ($previousHalfDayLeaves * 0.5);
                    
                    // 27일에서 직전 기간 사용 연차를 직접 차감
                    $totalAnnualLeave = 27 - $previousUsedLeave;
                } 
                else {
                    // 2년 이상 직원
                    if ($yearsDiff < 3) {
                        $totalAnnualLeave = 15;
                    } else {
                        $additionalYears = $yearsDiff - 3;
                        $additionalDays = floor($additionalYears / 2);
                        $totalAnnualLeave = min(16 + $additionalDays, 25);
                    }

                    // 직전 기간의 잔여 연차가 음수인 경우 반영
                    $previousPeriodStart = Carbon::parse($selectedMemberAnnualPeriod['start_date'])->subYear();
                    $previousPeriodEnd = Carbon::parse($selectedMemberAnnualPeriod['start_date'])->subDay();
                    
                    $previousRemainingLeave = $this->calculateRemainingLeave(
                        $request->member, 
                        $previousPeriodStart, 
                        $previousPeriodEnd, 
                        $yearsDiff - 1
                    );

                    if ($previousRemainingLeave < 0) {
                        $totalAnnualLeave += $previousRemainingLeave;
                    }
                }

                // 현재 기간 사용 연차 계산
                $fullDayLeaves = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [
                        $selectedMemberAnnualPeriod['start_date'],
                        $selectedMemberAnnualPeriod['end_date']
                    ])
                    ->where('status', '연차')
                    ->count();

                $halfDayLeaves = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [
                        $selectedMemberAnnualPeriod['start_date'],
                        $selectedMemberAnnualPeriod['end_date']
                    ])
                    ->whereIn('status', ['오전반차', '오후반차'])
                    ->count();

                $usedAnnualLeave = $fullDayLeaves + ($halfDayLeaves * 0.5);

                // 조정연차 계산
                $adjustedAnnualLeave = 0;
                $rewards = \App\Models\Reward::where('member_id', $selectedMember->id)
                    ->whereBetween('usable_date', [
                        $selectedMemberAnnualPeriod['start_date'],
                        $selectedMemberAnnualPeriod['end_date']
                    ])
                    ->get();

                foreach ($rewards as $reward) {
                    switch ($reward->reward_type) {
                        case '연차추가':
                            $adjustedAnnualLeave += 1;
                            break;
                        case '반차추가':
                            $adjustedAnnualLeave += 0.5;
                            break;
                        case '연차차감':
                            $adjustedAnnualLeave -= 1;
                            break;
                        case '반차차감':
                            $adjustedAnnualLeave -= 0.5;
                            break;
                    }
                }

                // 잔여연차 계산식 수정
                $remainingAnnualLeave = $totalAnnualLeave + max(0, $adjustedAnnualLeave) - $usedAnnualLeave;

                // 월별 통계 계산 추가
                $currentMonth = Carbon::now('Asia/Seoul');
                $startOfMonth = $currentMonth->copy()->startOfMonth();
                $today = $currentMonth->copy()->endOfDay();

                $monthlyStats = [
                    'normal' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('attendance', '정상')
                        ->count(),
                    'late' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('attendance', '지각')
                        ->count(),
                    'missing' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('attendance', '누락')
                        ->count(),
                    'remote' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '재택')
                        ->count(),
                    'dayoff' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '휴무')
                        ->count(),
                    'annual' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '연차')
                        ->count(),
                    'morning_half' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '오전반차')
                        ->count(),
                    'afternoon_half' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '오후반차')
                        ->count(),
                    'holiday' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfMonth, $today])
                        ->where('status', '공휴일')
                        ->count(),
                ];
                
                $monthlyStats['total'] = array_sum($monthlyStats);

                $totalWorkingMinutes = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfMonth, $today])
                    ->sum('working_hours');

                $hours = floor($totalWorkingMinutes / 60);
                $minutes = $totalWorkingMinutes % 60;

                $monthlyStats['total_working_time'] = [
                    'hours' => $hours,
                    'minutes' => $minutes
                ];

                // 설정근무시간 계산
                $workHoursData = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfMonth, $today])
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                $totalSetMinutes = 0;
                foreach ($workHoursData as $data) {
                    if ($data->status === '연차') {
                        $totalSetMinutes += 480; // 연차는 8시간(480분) 추가
                    } elseif (in_array($data->status, ['오전반차', '오후반차'])) {
                        $totalSetMinutes += 240; // 반차는 4시간(240분) 추가
                    } elseif ($data->start_time && $data->end_time) {
                        $startTime = Carbon::parse($data->start_time);
                        $endTime = Carbon::parse($data->end_time);
                        $diffMinutes = $startTime->diffInMinutes($endTime);
                        
                        // 식사시간 공제 로직
                        if ($diffMinutes > 720) { // 12시간 초과
                            $diffMinutes -= 120;
                        } elseif ($diffMinutes > 360) { // 6시간 초과
                            $diffMinutes -= 60;
                        }
                        
                        $totalSetMinutes += $diffMinutes;
                    }
                }

                $monthlyStats['set_working_time'] = [
                    'hours' => floor($totalSetMinutes / 60),
                    'minutes' => $totalSetMinutes % 60
                ];

                // 법정근로시간 계산
                $totalLegalMinutes = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfMonth, $today])
                    ->sum('working_hours'); // 기본 근무시간

                // 연차 시간 추가 (480분/일)
                $annualDays = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfMonth, $today])
                    ->where('status', '연차')
                    ->count();
                $totalLegalMinutes += ($annualDays * 480);

                // 반차 시간 추가 (240분/일)
                $halfDays = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfMonth, $today])
                    ->whereIn('status', ['오전반차', '오후반차'])
                    ->count();
                $totalLegalMinutes += ($halfDays * 240);

                $monthlyStats['legal_working_time'] = [
                    'hours' => floor($totalLegalMinutes / 60),
                    'minutes' => $totalLegalMinutes % 60
                ];

                $workHours = Workhour::query()
                    ->select([
                        'work_date',
                        'member',
                        'task',
                        'affiliation',
                        'work_time',
                        \DB::raw('TIME_FORMAT(start_time, "%H:%i:%s") as start_time'),
                        \DB::raw('TIME_FORMAT(end_time, "%H:%i:%s") as end_time'),
                        'status',
                        \DB::raw('TIME_FORMAT(WSTime, "%H:%i:%s") as WSTime'),
                        \DB::raw('TIME_FORMAT(WCTime, "%H:%i:%s") as WCTime'),
                        'attendance',
                        'working_hours'
                    ]);
                    
                // 디버깅을 위해 쿼리 로그 활성화
                \DB::enableQueryLog();
                
                // 기간 필터 적용
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $workHours->whereBetween('work_date', [$request->start_date, $request->end_date]);
                    \Log::info('Date range filter applied: ' . $request->start_date . ' to ' . $request->end_date);
                } elseif ($request->filled('start_date')) {
                    $workHours->where('work_date', '>=', $request->start_date);
                    \Log::info('Start date filter applied: ' . $request->start_date);
                } elseif ($request->filled('end_date')) {
                    $workHours->where('work_date', '<=', $request->end_date);
                    \Log::info('End date filter applied: ' . $request->end_date);
                }
                
                // 담당자 필터 적용
                if ($request->filled('member')) {
                    $workHours->where('member', $request->member);
                    \Log::info('Member filter applied: ' . $request->member);
                }
                
                // 지역 필터 적용
                if ($request->filled('location')) {
                    $workHours->where('affiliation', 'like', '%' . $request->location . '%');
                    \Log::info('Location filter applied: ' . $request->location);
                }
                
                // 팀 필터 적용
                if ($request->filled('team')) {
                    $workHours->where('task', 'like', '%' . $request->team . '%');
                    \Log::info('Team filter applied: ' . $request->team);
                }
                
                // 상태 필터 적용
                if ($request->filled('status')) {
                    $workHours->where('status', $request->status);
                    \Log::info('Status filter applied: ' . $request->status);
                }
                
                // 정렬 및 페이지네이션 적용
                $workHours = $workHours->orderBy('work_date', 'desc')
                    ->orderBy('member')
                    ->paginate(15);
                    
                // 실행된 SQL 쿼리 로그
                \Log::info('Work Management SQL Query:', \DB::getQueryLog());

                $lastMonth = Carbon::now('Asia/Seoul')->subMonth();
                $startOfLastMonth = $lastMonth->copy()->startOfMonth();
                $endOfLastMonth = $lastMonth->copy()->endOfMonth();

                $lastMonthStats = [
                    'normal' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('attendance', '정상')
                        ->count(),
                    'late' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('attendance', '지각')
                        ->count(),
                    'missing' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('attendance', '누락')
                        ->count(),
                    'remote' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '재택')
                        ->count(),
                    'dayoff' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '휴무')
                        ->count(),
                    'annual' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '연차')
                        ->count(),
                    'morning_half' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '오전반차')
                        ->count(),
                    'afternoon_half' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '오후반차')
                        ->count(),
                    'holiday' => Workhour::where('member', $request->member)
                        ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                        ->where('status', '공휴일')
                        ->count(),
                ];

                $lastMonthStats['total'] = array_sum($lastMonthStats);

                $lastMonthTotalWorkingMinutes = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                    ->sum('working_hours');

                $lastMonthHours = floor($lastMonthTotalWorkingMinutes / 60);
                $lastMonthMinutes = $lastMonthTotalWorkingMinutes % 60;

                $lastMonthStats['total_working_time'] = [
                    'hours' => $lastMonthHours,
                    'minutes' => $lastMonthMinutes
                ];

                // 설정근무시간 계산
                $lastMonthWorkHoursData = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                    ->whereNotNull('start_time')
                    ->whereNotNull('end_time')
                    ->get();

                $lastMonthTotalSetMinutes = 0;
                foreach ($lastMonthWorkHoursData as $data) {
                    if ($data->status === '연차') {
                        $lastMonthTotalSetMinutes += 480;
                    } elseif (in_array($data->status, ['오전반차', '오후반차'])) {
                        $lastMonthTotalSetMinutes += 240;
                    } elseif ($data->start_time && $data->end_time) {
                        $startTime = Carbon::parse($data->start_time);
                        $endTime = Carbon::parse($data->end_time);
                        $diffMinutes = $startTime->diffInMinutes($endTime);
                        
                        if ($diffMinutes > 720) {
                            $diffMinutes -= 120;
                        } elseif ($diffMinutes > 360) {
                            $diffMinutes -= 60;
                        }
                        
                        $lastMonthTotalSetMinutes += $diffMinutes;
                    }
                }

                $lastMonthStats['set_working_time'] = [
                    'hours' => floor($lastMonthTotalSetMinutes / 60),
                    'minutes' => $lastMonthTotalSetMinutes % 60
                ];

                // 법정근로시간 계산
                $lastMonthTotalLegalMinutes = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                    ->sum('working_hours');

                $lastMonthAnnualDays = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                    ->where('status', '연차')
                    ->count();
                $lastMonthTotalLegalMinutes += ($lastMonthAnnualDays * 480);

                $lastMonthHalfDays = Workhour::where('member', $request->member)
                    ->whereBetween('work_date', [$startOfLastMonth, $endOfLastMonth])
                    ->whereIn('status', ['오전반차', '오후반차'])
                    ->count();
                $lastMonthTotalLegalMinutes += ($lastMonthHalfDays * 240);

                $lastMonthStats['legal_working_time'] = [
                    'hours' => floor($lastMonthTotalLegalMinutes / 60),
                    'minutes' => $lastMonthTotalLegalMinutes % 60
                ];

                return view('work-management.index', compact(
                    'workHours', 
                    'members',
                    'selectedMemberAnnualPeriod',
                    'totalAnnualLeave',
                    'adjustedAnnualLeave',
                    'usedAnnualLeave',
                    'remainingAnnualLeave',
                    'monthlyStats',
                    'lastMonthStats'
                ));
            }
        }

        $workHours = Workhour::query()
            ->select([
                'work_date',
                'member',
                'task',
                'affiliation',
                'work_time',
                \DB::raw('TIME_FORMAT(start_time, "%H:%i:%s") as start_time'),
                \DB::raw('TIME_FORMAT(end_time, "%H:%i:%s") as end_time'),
                'status',
                \DB::raw('TIME_FORMAT(WSTime, "%H:%i:%s") as WSTime'),
                \DB::raw('TIME_FORMAT(WCTime, "%H:%i:%s") as WCTime'),
                'attendance',
                'working_hours'
            ])
            ->when($request->filled('start_date') && $request->filled('end_date'), function($query) use ($request) {
                return $query->whereBetween('work_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->filled('start_date') && !$request->filled('end_date'), function($query) use ($request) {
                return $query->where('work_date', '>=', $request->start_date);
            })
            ->when(!$request->filled('start_date') && $request->filled('end_date'), function($query) use ($request) {
                return $query->where('work_date', '<=', $request->end_date);
            })
            ->when($request->filled('member'), function($query) use ($request) {
                return $query->where('member', $request->member);
            })
            ->when($request->filled('location'), function($query) use ($request) {
                // 지역 필터 적용
                return $query->where('affiliation', 'like', '%' . $request->location . '%');
            })
            ->when($request->filled('team'), function($query) use ($request) {
                // 팀 필터 적용
                return $query->where('task', 'like', '%' . $request->team . '%');
            })
            ->when($request->filled('status'), function($query) use ($request) {
                // 상태 필터 적용
                return $query->where('status', $request->status);
            })
            ->orderBy('work_date', 'desc')
            ->orderBy('member')
            ->paginate(15);

        return view('work-management.index', compact(
            'workHours', 
            'members',
            'selectedMemberAnnualPeriod',
            'totalAnnualLeave',
            'adjustedAnnualLeave',
            'usedAnnualLeave',
            'remainingAnnualLeave'
        ));
    }

    private function calculateRemainingLeave($member, $startDate, $endDate, $previousYearsDiff)
    {
        // 직전 기간 사용 연차 계산
        $fullDayLeaves = Workhour::where('member', $member)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->where('status', '연차')
            ->count();

        $halfDayLeaves = Workhour::where('member', $member)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->whereIn('status', ['오전반차', '오후반차'])
            ->count();

        $usedLeave = $fullDayLeaves + ($halfDayLeaves * 0.5);

        // 직전 기간의 총 연차 계산
        if ($previousYearsDiff < 1) {
            $completedMonths = floor($endDate->diffInMonths($startDate));
            $totalLeave = min($completedMonths, 11);
        } 
        elseif ($previousYearsDiff < 2) {
            $totalLeave = 27;
        } 
        elseif ($previousYearsDiff < 3) {
            $totalLeave = 15;
        } 
        else {
            $additionalYears = $previousYearsDiff - 3;
            $additionalDays = floor($additionalYears / 2);
            $totalLeave = min(16 + $additionalDays, 25);
        }

        // 조정연차 계산 추가
        $memberId = Member::where('name', $member)->value('id');
        $adjustedLeave = 0;
        
        if ($memberId) {
            $rewards = \App\Models\Reward::where('member_id', $memberId)
                ->whereBetween('usable_date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ])
                ->get();
                
            foreach ($rewards as $reward) {
                switch ($reward->reward_type) {
                    case '연차추가':
                        $adjustedLeave += 1;
                        break;
                    case '반차추가':
                        $adjustedLeave += 0.5;
                        break;
                    case '연차차감':
                        $adjustedLeave -= 1;
                        break;
                    case '반차차감':
                        $adjustedLeave -= 0.5;
                        break;
                }
            }
        }
        
        // 잔여연차 계산 (조정연차 포함)
        return $totalLeave + max(0, $adjustedLeave) - $usedLeave;
    }

    public function getAllAnnualLeaves()
    {
        try {
            $excludedMembers = ['김충환', '허남관', '윤미라'];
            $today = Carbon::now('Asia/Seoul');
            
            $members = Member::whereNotIn('name', $excludedMembers)
                ->orderBy('name')
                ->get();
            
            $annualLeaveData = [];
            
            foreach ($members as $member) {
                $adjustedEndDate = Carbon::parse($member->annual_end_period)
                    ->setYear($today->year);
                
                // 현재 날짜가 연차기간 마지막 날인 경우를 고려하여 로직 수정
                if ($adjustedEndDate->isPast() && !$today->isSameDay($adjustedEndDate)) {
                    $adjustedEndDate->addYear();
                }
                
                $adjustedStartDate = $adjustedEndDate->copy()
                    ->subYear()
                    ->addDay();
                
                $annualPeriod = [
                    'start_date' => $adjustedStartDate->format('Y-m-d'),
                    'end_date' => $adjustedEndDate->format('Y-m-d')
                ];

                $annualStartDate = Carbon::parse($member->annual_start_period);
                $yearsDiff = abs($today->diffInYears($annualStartDate));

                // 근속 기간에 따른 연차 일수 계산
                if ($yearsDiff < 1) {
                    $monthsDiff = (int)$annualStartDate->diffInMonths($today);
                    $totalAnnualLeave = min($monthsDiff, 11);
                } 
                elseif ($yearsDiff < 2) {
                    // 직전 연차적용기간 계산
                    $previousPeriodStart = $annualStartDate;
                    $previousPeriodEnd = Carbon::parse($annualPeriod['start_date'])->subDay();

                    // 직전 기간 사용 연차 계산
                    $previousFullDayLeaves = Workhour::where('member', $member->name)
                        ->whereBetween('work_date', [
                            $previousPeriodStart->format('Y-m-d'),
                            $previousPeriodEnd->format('Y-m-d')
                        ])
                        ->where('status', '연차')
                        ->count();

                    $previousHalfDayLeaves = Workhour::where('member', $member->name)
                        ->whereBetween('work_date', [
                            $previousPeriodStart->format('Y-m-d'),
                            $previousPeriodEnd->format('Y-m-d')
                        ])
                        ->whereIn('status', ['오전반차', '오후반차'])
                        ->count();

                    $previousUsedLeave = $previousFullDayLeaves + ($previousHalfDayLeaves * 0.5);
                    
                    // 27일에서 직전 기간 사용 연차를 직접 차감
                    $totalAnnualLeave = 27 - $previousUsedLeave;
                } 
                else {
                    // 2년 이상 직원
                    if ($yearsDiff < 3) {
                        $totalAnnualLeave = 15;
                    } else {
                        $additionalYears = $yearsDiff - 3;
                        $additionalDays = floor($additionalYears / 2);
                        $totalAnnualLeave = min(16 + $additionalDays, 25);
                    }

                    // 직전 기간의 잔여 연차가 음수인 경우 반영
                    $previousPeriodStart = Carbon::parse($annualPeriod['start_date'])->subYear();
                    $previousPeriodEnd = Carbon::parse($annualPeriod['start_date'])->subDay();
                    
                    $previousRemainingLeave = $this->calculateRemainingLeave(
                        $member->name, 
                        $previousPeriodStart, 
                        $previousPeriodEnd, 
                        $yearsDiff - 1
                    );

                    if ($previousRemainingLeave < 0) {
                        $totalAnnualLeave += $previousRemainingLeave;
                    }
                }

                // 현재 기간 사용 연차 계산
                $fullDayLeaves = Workhour::where('member', $member->name)
                    ->whereBetween('work_date', [
                        $annualPeriod['start_date'],
                        $annualPeriod['end_date']
                    ])
                    ->where('status', '연차')
                    ->count();

                $halfDayLeaves = Workhour::where('member', $member->name)
                    ->whereBetween('work_date', [
                        $annualPeriod['start_date'],
                        $annualPeriod['end_date']
                    ])
                    ->whereIn('status', ['오전반차', '오후반차'])
                    ->count();

                $usedAnnualLeave = $fullDayLeaves + ($halfDayLeaves * 0.5);

                // 조정연차 계산
                $adjustedAnnualLeave = 0;
                $rewards = \App\Models\Reward::where('member_id', $member->id)
                    ->whereBetween('usable_date', [
                        $annualPeriod['start_date'],
                        $annualPeriod['end_date']
                    ])
                    ->get();

                foreach ($rewards as $reward) {
                    switch ($reward->reward_type) {
                        case '연차추가':
                            $adjustedAnnualLeave += 1;
                            break;
                        case '반차추가':
                            $adjustedAnnualLeave += 0.5;
                            break;
                        case '연차차감':
                            $adjustedAnnualLeave -= 1;
                            break;
                        case '반차차감':
                            $adjustedAnnualLeave -= 0.5;
                            break;
                    }
                }

                // 잔여연차 계산식
                $remainingAnnualLeave = $totalAnnualLeave + max(0, $adjustedAnnualLeave) - $usedAnnualLeave;

                // 상태 계산
                $status = $this->calculateStatus($remainingAnnualLeave, $adjustedEndDate, $today);
                
                // 특별 체크 기간과 일반 분기 구분
                $specialStartDate = Carbon::create(2025, 1, 21)->startOfDay();
                $specialEndDate = Carbon::create(2025, 3, 31)->endOfDay();
                $todayDate = $today->copy()->startOfDay();
                
                // 분기 지각 횟수 계산
                if ($todayDate->greaterThanOrEqualTo($specialStartDate) && $todayDate->lessThanOrEqualTo($specialEndDate)) {
                    // 특별 기간 동안의 지각 횟수 체크
                    $quarterlyLateCount = Workhour::where('member', $member->name)
                        ->whereBetween('work_date', [
                            $specialStartDate->format('Y-m-d'),
                            $specialEndDate->format('Y-m-d')
                        ])
                        ->where('attendance', '지각')
                        ->count();
                } else {
                    // 일반 분기 계산
                    $currentQuarter = ceil($today->month / 3);
                    $quarterStart = Carbon::create($today->year, ($currentQuarter - 1) * 3 + 1, 1)->startOfMonth();
                    $quarterEnd = Carbon::create($today->year, $currentQuarter * 3, 1)->endOfMonth();
                    
                    $quarterlyLateCount = Workhour::where('member', $member->name)
                        ->whereBetween('work_date', [
                            $quarterStart->format('Y-m-d'),
                            $quarterEnd->format('Y-m-d')
                        ])
                        ->where('attendance', '지각')
                        ->count();
                }

                $annualLeaveData[] = [
                    'member' => $member->name,
                    'task' => $member->task,
                    'affiliation' => $member->affiliation,
                    'period' => $annualPeriod['start_date'] . ' ~ ' . $annualPeriod['end_date'],
                    'total_leave' => $totalAnnualLeave,
                    'adjusted_leave' => $adjustedAnnualLeave,
                    'used_leave' => $usedAnnualLeave,
                    'remaining_leave' => $remainingAnnualLeave,
                    'status' => $status,
                    'quarterly_late' => $quarterlyLateCount
                ];
            }
            
            return response()->json($annualLeaveData);
        } catch (\Exception $e) {
            \Log::error('Annual leaves calculation error: ' . $e->getMessage());
            return response()->json(['error' => '데이터를 불러오는 중 오류가 발생했습니다.'], 500);
        }
    }

    private function calculateStatus($remainingLeave, $endDate, $today)
    {
        if ($remainingLeave < 0) {
            return 'warning'; // 주의
        }
        
        $monthsUntilEnd = $today->diffInMonths($endDate, false);
        if ($monthsUntilEnd >= 0 && $monthsUntilEnd <= 1) {
            return 'renewal'; // 갱신
        }
        
        return 'normal'; // 정상
    }

    private function calculateTotalAnnualLeave($member, $yearsDiff, $today, $annualStartDate, $annualPeriod)
    {
        if ($yearsDiff < 1) {
            $monthsDiff = (int)$annualStartDate->diffInMonths($today);
            return min($monthsDiff, 11);
        } 
        elseif ($yearsDiff < 2) {
            return 27;
        } 
        else {
            if ($yearsDiff < 3) {
                return 15;
            } else {
                $additionalYears = $yearsDiff - 3;
                $additionalDays = floor($additionalYears / 2);
                return min(16 + $additionalDays, 25);
            }
        }
    }

    private function calculateUsedAnnualLeave($memberName, $period)
    {
        $fullDayLeaves = Workhour::where('member', $memberName)
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->where('status', '연차')
            ->count();

        $halfDayLeaves = Workhour::where('member', $memberName)
            ->whereBetween('work_date', [$period['start_date'], $period['end_date']])
            ->whereIn('status', ['오전반차', '오후반차'])
            ->count();

        return $fullDayLeaves + ($halfDayLeaves * 0.5);
    }

    private function calculateAdjustedAnnualLeave($memberId, $period)
    {
        $adjustedAnnualLeave = 0;
        $rewards = \App\Models\Reward::where('member_id', $memberId)
            ->whereBetween('usable_date', [
                $period['start_date'],
                $period['end_date']
            ])
            ->get();

        foreach ($rewards as $reward) {
            switch ($reward->reward_type) {
                case '연차추가':
                    $adjustedAnnualLeave += 1;
                    break;
                case '반차추가':
                    $adjustedAnnualLeave += 0.5;
                    break;
                case '연차차감':
                    $adjustedAnnualLeave -= 1;
                    break;
                case '반차차감':
                    $adjustedAnnualLeave -= 0.5;
                    break;
            }
        }

        return $adjustedAnnualLeave;
    }
}
