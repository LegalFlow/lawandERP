<?php

namespace App\Helpers;

use App\Models\Member;
use App\Models\Workhour;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkhourScheduleGenerator
{
    private Collection $members;
    private Carbon $weekStart;
    private array $holidays;
    private array $workDays;
    private array $blockCounts;

    private const WORK_TIMES = [
        Workhour::WORK_TIME_8_17 => 'block_8_17',
        Workhour::WORK_TIME_9_18 => 'block_9_18',
        Workhour::WORK_TIME_10_19 => 'block_10_19',
        Workhour::WORK_TIME_9_16 => 'block_9_16'
    ];

    public function __construct(Collection $members, Carbon $weekStart, array $holidays)
    {
        $this->members = $members;
        
        // weekStart가 일요일인 경우 다음날(월요일)로, 그 외의 경우 해당 주의 월요일로 설정
        if ($weekStart->isDayOfWeek(Carbon::SUNDAY)) {
            $this->weekStart = $weekStart->copy()->addDay();
        } else {
            $this->weekStart = $weekStart->copy()->previous(Carbon::MONDAY);
        }
        
        $this->holidays = $holidays;
        $this->workDays = $this->calculateWorkDays();
        $this->blockCounts = [];
        
        foreach (self::WORK_TIMES as $blockField) {
            $this->blockCounts[$blockField] = array_fill(0, 5, 0);
        }
    }

    private function calculateWorkDays(): array
    {
        $workDays = [];
        for ($i = 0; $i < 5; $i++) { // 월~금만 처리
            $date = $this->weekStart->copy()->addDays($i);
            if (!in_array($date->format('Y-m-d'), $this->holidays)) {
                $workDays[] = $date;
            }
        }
        return $workDays;
    }

    public function generate(): void
    {
        // 1. 공휴일 처리 - 전체 직원에 대해 먼저 처리
        $shuffledMembers = $this->members->shuffle();
        foreach ($shuffledMembers as $member) {
            $this->generateHolidays($member);
        }

        // 2. 휴무일 배정
        $shuffledMembers = $this->members->shuffle();
        foreach ($shuffledMembers as $member) {
            // 총 근무블록 수 계산
            $totalWorkBlocks = $this->calculateTotalWorkBlocks($member);
            // 실제 근무 가능한 날짜 수 계산 (전체 평일 - 공휴일 수)
            $availableWorkDays = count($this->workDays);
            // 휴무일 수 계산 (가용 근무일 - 총근무블록 수)
            $offDaysCount = $availableWorkDays - $totalWorkBlocks;
            // 휴무일이 음수가 되지 않도록 보정
            $offDaysCount = max(0, $offDaysCount);
            
            $this->assignOffDays($member, $offDaysCount);
        }

        // 3. 근무블록 배정
        $shuffledMembers = $this->members->shuffle();
        foreach ($shuffledMembers as $member) {
            $this->assignWorkBlocks($member);
        }

        // 4. 재택근무 배정
        $shuffledMembers = $this->members->shuffle();
        foreach ($shuffledMembers as $member) {
            $this->assignRemoteWork($member);
        }
    }

    private function calculateTotalWorkBlocks(Member $member): int
    {
        // 공휴일이 있는 경우, 근무블록 수를 조정
        $totalBlocks = $member->block_8_17 + 
                       $member->block_9_18 + 
                       $member->block_10_19 + 
                       $member->block_9_16;
        
        // 가용 근무일보다 근무블록이 많으면 가용 근무일로 제한
        return min($totalBlocks, count($this->workDays));
    }

    private function generateHolidays(Member $member): void
    {
        foreach ($this->holidays as $holiday) {
            Workhour::create([
                'work_date' => $holiday,
                'member' => $member->name,
                'work_time' => 'holiday',
                'status' => Workhour::STATUS_HOLIDAY,
                'affiliation' => $member->affiliation,
                'task' => $member->task
            ]);
        }
    }

    private function assignWorkBlocks(Member $member): void
    {
        $blocks = collect(self::WORK_TIMES)
            ->map(function ($blockField, $timeSlot) use ($member) {
                return [
                    'timeSlot' => $timeSlot,
                    'count' => $member->$blockField,
                    'blockField' => $blockField
                ];
            })
            ->filter(function ($block) {
                return $block['count'] > 0;
            })
            ->sortBy('count')
            ->values();

        foreach ($blocks as $block) {
            $this->assignWorkTime($member, $block['timeSlot'], $block['count']);
        }
    }

    private function assignWorkTime(Member $member, string $timeSlot, int $maxCount): void
    {
        $blockField = self::WORK_TIMES[$timeSlot];
        $assignedCount = 0;

        // 1차 시도: 겹치지 않는 시간대 우선 할당
        $sortedWorkDays = collect($this->workDays)
            ->filter(function ($day) use ($member) {
                return !Workhour::where('work_date', $day->format('Y-m-d'))
                    ->where('member', $member->name)
                    ->exists();
            })
            ->sortBy(function ($day) use ($blockField, $member, $timeSlot) {
                $dayIndex = $day->dayOfWeek - 1;
                $sameRegionCount = Workhour::where('work_date', $day->format('Y-m-d'))
                    ->where('affiliation', $member->affiliation)
                    ->where('work_time', $timeSlot)
                    ->count();
                return [$sameRegionCount, $this->blockCounts[$blockField][$dayIndex]];
            });

        // 겹치지 않는 날짜에 우선 할당
        foreach ($sortedWorkDays as $day) {
            if ($assignedCount >= $maxCount) break;

            $sameRegionCount = Workhour::where('work_date', $day->format('Y-m-d'))
                ->where('affiliation', $member->affiliation)
                ->where('work_time', $timeSlot)
                ->count();

            if ($sameRegionCount === 0) {
                $this->createWorkhour($member, $day, $timeSlot);
                $assignedCount++;
            }
        }

        // 2차 시도: 남은 근무블록 할당
        if ($assignedCount < $maxCount) {
            foreach ($sortedWorkDays as $day) {
                if ($assignedCount >= $maxCount) break;

                if (!Workhour::where('work_date', $day->format('Y-m-d'))
                    ->where('member', $member->name)
                    ->exists()) {
                    $this->createWorkhour($member, $day, $timeSlot);
                    $assignedCount++;
                }
            }
        }
    }

    private function getTimeFromSlot(string $timeSlot): array
    {
        [$start, $end] = explode('-', $timeSlot);
        return [
            sprintf('%02d:00:00', $start),
            sprintf('%02d:00:00', $end)
        ];
    }

    private function getPreviousWeekSchedule(Member $member): array
    {
        // 지난주 월~금 날짜 계산
        $prevWeekStart = $this->weekStart->copy()->subWeek();
        $prevWeekEnd = $prevWeekStart->copy()->addDays(4);  // 금요일까지

        // 지난주 스케줄 조회
        $previousSchedule = Workhour::where('member', $member->name)
            ->whereBetween('work_date', [
                $prevWeekStart->format('Y-m-d'),
                $prevWeekEnd->format('Y-m-d')
            ])
            ->get();

        // 휴무일과 재택일 요일 추출
        $offDayOfWeek = null;
        $remoteDaysOfWeek = [];

        foreach ($previousSchedule as $schedule) {
            $dayOfWeek = Carbon::parse($schedule->work_date)->dayOfWeek;
            
            if ($schedule->status === Workhour::STATUS_OFF) {
                $offDayOfWeek = $dayOfWeek;
            } elseif ($schedule->status === Workhour::STATUS_REMOTE) {
                $remoteDaysOfWeek[] = $dayOfWeek;
            }
        }

        return [
            'off_day' => $offDayOfWeek,
            'remote_days' => $remoteDaysOfWeek
        ];
    }

    private function assignOffDays(Member $member, int $offDaysCount): void
    {
        if ($offDaysCount <= 0) return;

        // 이전 주 스케줄 확인
        $previousSchedule = $this->getPreviousWeekSchedule($member);
        $previousOffDay = $previousSchedule['off_day'];

        // 1. 현재 요일별 휴무자 수 카운트
        $dayOffCounts = collect($this->workDays)->mapWithKeys(function ($day) {
            return [
                $day->format('Y-m-d') => Workhour::where('work_date', $day->format('Y-m-d'))
                    ->where('status', Workhour::STATUS_OFF)
                    ->count()
            ];
        });

        // 최소 휴무자 수 계산 추가
        $minCount = $dayOffCounts->min();

        // 2. 가능한 날짜들 필터링
        $availableDays = collect($this->workDays)
            ->filter(function ($day) use ($member) {
                return !Workhour::where('work_date', $day->format('Y-m-d'))
                    ->where('member', $member->name)
                    ->exists();
            });

        // 3. 후보 날짜 선정
        $candidateDays = $availableDays
            ->filter(function ($day) use ($member, $dayOffCounts, $minCount, $previousOffDay) {
                $date = $day->format('Y-m-d');
                $dayOfWeek = $day->dayOfWeek;
                
                // 지난주와 같은 요일 제외
                if ($previousOffDay && $dayOfWeek === $previousOffDay) {
                    return false;
                }
                
                // 같은 지역&팀 체크
                $sameTeamOffCount = Workhour::where('work_date', $date)
                    ->where('affiliation', $member->affiliation)
                    ->where('task', $member->task)
                    ->where('status', Workhour::STATUS_OFF)
                    ->count();

                return $sameTeamOffCount == 0 && 
                       ($dayOffCounts[$date] == $minCount || 
                        $dayOffCounts[$date] == $minCount + 1);
            })
            ->values();

        // 4. 후보가 없는 경우 제한조건 완화 (이전 주 요일 제외는 유지)
        if ($candidateDays->isEmpty()) {
            $candidateDays = $availableDays
                ->filter(function ($day) use ($previousOffDay) {
                    return !$previousOffDay || $day->dayOfWeek !== $previousOffDay;
                })
                ->values();
        }

        // 5. 후보 중에서 랜덤하게 선택
        if ($candidateDays->isNotEmpty()) {
            $selectedDay = $candidateDays->random();
            
            Workhour::create([
                'work_date' => $selectedDay->format('Y-m-d'),
                'member' => $member->name,
                'work_time' => 'off',
                'status' => Workhour::STATUS_OFF,
                'affiliation' => $member->affiliation,
                'task' => $member->task
            ]);
        }
    }

    private function assignRemoteWork(Member $member): void
    {
        // 이전 주 스케줄 확인
        $previousSchedule = $this->getPreviousWeekSchedule($member);
        $previousRemoteDays = $previousSchedule['remote_days'];

        // 공휴일이 2일 이상인 경우에만 재택 횟수 조정
        $holidayCount = count($this->holidays);
        $maxRemote = $member->house_work;
        
        if ($holidayCount >= 2) {
            $maxRemote = $member->house_work - $holidayCount;
        }
        
        if ($maxRemote <= 0) return;

        // 재택일수만큼 반복
        for ($i = 0; $i < $maxRemote; $i++) {
            // 1. 현재 요일별 재택근무자 수 카운트
            $dayRemoteCounts = collect($this->workDays)->mapWithKeys(function ($day) {
                return [
                    $day->format('Y-m-d') => Workhour::where('work_date', $day->format('Y-m-d'))
                        ->where('status', Workhour::STATUS_REMOTE)
                        ->count()
                ];
            });

            // 최소 재택근무자 수 계산 추가
            $minCount = $dayRemoteCounts->min();

            // 2. 가능한 날짜 필터링
            $availableDays = collect($this->workDays)
                ->filter(function ($day) use ($member, $previousRemoteDays) {
                    $dayOfWeek = $day->dayOfWeek;
                    
                    // 이전 주 재택일과 같은 요일 제외
                    if (in_array($dayOfWeek, $previousRemoteDays)) {
                        return false;
                    }
                    
                    // 일반 근무인 날만 선택
                    return Workhour::where('work_date', $day->format('Y-m-d'))
                        ->where('member', $member->name)
                        ->where('status', Workhour::STATUS_WORK)
                        ->exists();
                });

            // 3. 후보 날짜 선정
            $candidateDays = $availableDays
                ->filter(function ($day) use ($member, $dayRemoteCounts, $minCount) {
                    $date = $day->format('Y-m-d');
                    
                    $sameRegionRemoteCount = Workhour::where('work_date', $date)
                        ->where('affiliation', $member->affiliation)
                        ->where('status', Workhour::STATUS_REMOTE)
                        ->count();

                    return $sameRegionRemoteCount < 2 && 
                           ($dayRemoteCounts[$date] == $minCount || 
                            $dayRemoteCounts[$date] == $minCount + 1);
                })
                ->values();

            // 4. 후보가 없는 경우 제한조건 완화 (이전 주 요일 제외는 유지)
            if ($candidateDays->isEmpty()) {
                $candidateDays = $availableDays
                    ->filter(function ($day) use ($previousRemoteDays) {
                        return !in_array($day->dayOfWeek, $previousRemoteDays);
                    })
                    ->values();
            }

            // 5. 후보 중에서 랜덤하게 선택하여 재택으로 변경
            if ($candidateDays->isNotEmpty()) {
                $selectedDay = $candidateDays->random();
                
                Workhour::where('work_date', $selectedDay->format('Y-m-d'))
                    ->where('member', $member->name)
                    ->where('status', Workhour::STATUS_WORK)
                    ->update(['status' => Workhour::STATUS_REMOTE]);
            }
        }
    }

    private function createWorkhour(Member $member, Carbon $day, string $timeSlot): void
    {
        [$startTime, $endTime] = $this->getTimeFromSlot($timeSlot);
        
        Workhour::create([
            'work_date' => $day->format('Y-m-d'),
            'member' => $member->name,
            'work_time' => $timeSlot,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => Workhour::STATUS_WORK,
            'affiliation' => $member->affiliation,
            'task' => $member->task
        ]);

        $dayIndex = $day->dayOfWeek - 1;
        $blockField = self::WORK_TIMES[$timeSlot];
        $this->blockCounts[$blockField][$dayIndex]++;
    }

    public function getWorkDays(): array
    {
        return array_map(function($day) {
            return $day->format('Y-m-d');
        }, $this->workDays);
    }
}