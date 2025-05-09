<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Workhour extends Model
{
    // timestamps 사용하지 않음
    public $timestamps = false;
    
    // 테이블 이름 지정
    protected $table = 'work_hours';
    
    // 복합 키 처리를 위한 설정
    protected $primaryKey = ['work_date', 'member'];
    public $incrementing = false;
    
    // 대량 할당 가능한 필드 정의
    protected $fillable = [
        'work_date',
        'member',
        'task',
        'affiliation',
        'work_time',
        'start_time',
        'end_time',
        'status',
        'WSTime',
        'WCTime',
        'attendance',
        'working_hours'
    ];
    
    // 날짜 형식으로 다룰 속성 지정
    protected $dates = [
        'work_date'
    ];
    
    // 시간 형식으로 다룰 속성 지정
    protected $casts = [
        'work_date' => 'date:Y-m-d',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'WSTime' => 'datetime:H:i:s',
        'WCTime' => 'datetime:H:i:s'
    ];
    
    // Member 모델과의 관계 설정
    public function memberInfo()
    {
        return $this->belongsTo(Member::class, 'member', 'name')
            ->withDefault(['affiliation' => null]);
    }

    // User 모델과의 관계 설정
    public function user()
    {
        return $this->belongsTo(User::class, 'member', 'name');
    }

    // 관리자 여부 확인 메서드
    public function isAdminUser()
    {
        return $this->user && $this->user->is_admin;
    }

    // 스코프 메서드 추가가 유용할 것 같습니다:
    public function scopeForDate($query, $date)
    {
        return $query->where('work_date', $date);
    }

    public function scopeForMember($query, $member)
    {
        return $query->where('member', $member);
    }

    // 기존 코드에 추가
    public function scopeFilterByAffiliation($query, $affiliation)
    {
        if ($affiliation && $affiliation !== '전체') {
            return $query->where('affiliation', $affiliation);
        }
        return $query;
    }

    public function scopeFilterByTask($query, $task)
    {
        if ($task && $task !== '전체') {
            return $query->where('task', $task);
        }
        return $query;
    }

    // 재택 데이터 조회용 스코프
    public function scopeWorkFromHome($query)
    {
        return $query->where('status', '재택');
    }

    // 휴가 데이터 조회용 스코프 개선
    public function scopeVacation($query, $type = null)
    {
        if ($type) {
            return $query->where('status', $type);
        }
        return $query->whereIn('status', [self::STATUS_OFF, self::STATUS_VACATION]);
    }

    // 근무 데이터 조회용 스코프 추가
    public function scopeWorkingHours($query)
    {
        return $query->whereIn('status', [self::STATUS_WORK, self::STATUS_REMOTE])
                     ->whereNotNull('start_time')
                     ->whereNotNull('end_time');
    }

    // 상단에 상수 추가
    public const STATUS_WORK = '근무';
    public const STATUS_REMOTE = '재택';
    public const STATUS_OFF = '휴무';
    public const STATUS_VACATION = '연차';
    public const STATUS_MORNING_HALF = '오전반차';
    public const STATUS_AFTERNOON_HALF = '오후반차';
    public const STATUS_HOLIDAY = '공휴일';  // 추가

    // 근무 시간 상수 추가
    public const WORK_TIME_8_17 = '8-17';
    public const WORK_TIME_9_18 = '9-18';
    public const WORK_TIME_10_19 = '10-19';
    public const WORK_TIME_9_16 = '9-16';

    // 유효한 상태값 목록
    public static function getValidStatuses()
    {
        return [
            self::STATUS_WORK,
            self::STATUS_REMOTE,
            self::STATUS_OFF,
            self::STATUS_VACATION,
            self::STATUS_MORNING_HALF,
            self::STATUS_AFTERNOON_HALF,
            self::STATUS_HOLIDAY,  // 추가
        ];
    }

    // 시간 유효성 검사
    public function hasValidTimes()
    {
        if (in_array($this->status, [self::STATUS_WORK, self::STATUS_REMOTE])) {
            return !empty($this->start_time) && !empty($this->end_time);
        }
        return true;
    }

    // 복합 키를 위한 메서드 추가
    protected function setKeysForSaveQuery($query)
    {
        $query->where('work_date', '=', $this->getAttribute('work_date'))
              ->where('member', '=', $this->getAttribute('member'));
        return $query;
    }

    // 시작 시간 계산 메서드
    public function calculateStartTime($workTimeOption, $isHalfDay = false)
    {
        if (!in_array($workTimeOption, [
            self::WORK_TIME_8_17,
            self::WORK_TIME_9_18,
            self::WORK_TIME_10_19,
            self::WORK_TIME_9_16
        ])) {
            return null;
        }

        if (in_array($this->status, [self::STATUS_OFF, self::STATUS_VACATION])) {
            return null;
        }

        $baseTime = match($workTimeOption) {
            self::WORK_TIME_8_17 => '08:00:00',
            self::WORK_TIME_9_18 => '09:00:00',
            self::WORK_TIME_10_19 => '10:00:00',
            self::WORK_TIME_9_16 => '09:00:00',
            default => null
        };

        if ($isHalfDay && $baseTime) {
            // 오전반차인 경우 시작 시간 조정
            $hours = (int)substr($baseTime, 0, 2);
            $adjustment = ($workTimeOption === self::WORK_TIME_9_16) ? 4 : 5;
            return sprintf('%02d:00:00', $hours + $adjustment);
        }

        return $baseTime;
    }

    // 종료 시간 계산 메서드
    public function calculateEndTime($workTimeOption, $isHalfDay = false)
    {
        if (in_array($this->status, [self::STATUS_OFF, self::STATUS_VACATION])) {
            return null;
        }

        $baseTime = match($workTimeOption) {
            self::WORK_TIME_8_17 => '17:00:00',
            self::WORK_TIME_9_18 => '18:00:00',
            self::WORK_TIME_10_19 => '19:00:00',
            self::WORK_TIME_9_16 => '16:00:00',
            default => null
        };

        if ($isHalfDay && $baseTime) {
            // 오후반차인 경우 종료 시간 조정
            $hours = (int)substr($baseTime, 0, 2);
            $adjustment = ($workTimeOption === self::WORK_TIME_9_16) ? 4 : 5;
            return sprintf('%02d:00:00', $hours - $adjustment);
        }

        return $baseTime;
    }

    // 상태값 결정 메서드
    public function determineStatus($workTimeOption, $workStatus)
    {
        if ($workStatus === 'remote') {
            return self::STATUS_REMOTE;
        }

        return match($workTimeOption) {
            'off' => self::STATUS_OFF,
            'vacation' => self::STATUS_VACATION,
            'holiday' => self::STATUS_HOLIDAY,  // 추가
            default => self::STATUS_WORK
        };
    }

    // Workhour 모델에 추가
    public function validateWorkStatus($workTimeOption, $workStatus)
    {
        if ($workStatus === self::STATUS_REMOTE && !$this->hasValidTimes()) {
            throw new \InvalidArgumentException('재택근무는 시작/종료 시간이 필요합니다.');
        }
        
        if (in_array($workTimeOption, ['off', 'vacation']) && 
            ($this->start_time || $this->end_time)) {
            throw new \InvalidArgumentException('휴가/휴무는 시간을 입력할 수 없습니다.');
        }
    }

    // Workhour 모델에 추가
    public function validate()
    {
        if (!in_array($this->status, self::getValidStatuses())) {
            throw new \InvalidArgumentException('유효하지 않은 상태값입니다.');
        }
        
        if ($this->hasValidTimes()) {
            if (strtotime($this->end_time) <= strtotime($this->start_time)) {
                throw new \InvalidArgumentException('종료 시간은 시작 시간보다 늦어야 합니다.');
            }
        }
    }

    // Workhour 모델에 추가
    public static function getScheduleStats($startDate, $endDate, $filterType)
    {
        $cacheKey = "schedule_stats_{$startDate}_{$endDate}_{$filterType}";
        return Cache::remember($cacheKey, now()->addMinutes(5), function() use ($startDate, $endDate, $filterType) {
            return self::query()
                ->filterByAffiliation($filterType)
                ->whereBetween('work_date', [$startDate, $endDate])
                ->get();
        });
    }

    // 유효한 work_time 옵션 상수 추가
    public const WORK_TIME_OPTIONS = [
        '8-17',
        '9-18',
        '10-19',
        '9-16',
        'custom',
        'off',
        'vacation',
        'holiday',  // 추가
    ];

    // work_time 옵션 가져오는 메서드
    public static function getWorkTimeOptions()
    {
        return self::WORK_TIME_OPTIONS;
    }

    // work_time 옵션 검증을 위한 메서드 추가
    public function validateWorkTime($workTime)
    {
        if (!in_array($workTime, self::WORK_TIME_OPTIONS)) {
            throw new \InvalidArgumentException('유효하지 않은 근무시간 옵션입니다.');
        }
    }
}
