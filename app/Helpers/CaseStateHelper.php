<?php

namespace App\Helpers;

class CaseStateHelper
{
    const REVIVAL_STATES = [
        5 => '상담대기',
        10 => '상담완료',
        11 => '재상담필요',
        15 => '계약',
        20 => '서류준비',
        21 => '부채증명서 발급중',
        22 => '부채증명서 발급완료',
        25 => '신청서 작성 진행중',
        30 => '신청서 제출',
        35 => '금지명령',
        40 => '보정기간',
        45 => '개시결정',
        50 => '채권자 집회기일',
        55 => '인가결정',
    ];

    const BANKRUPTCY_STATES = [
        5 => '상담대기',
        10 => '상담완료',
        11 => '재상담필요',
        15 => '계약',
        20 => '서류준비',
        21 => '부채증명서 발급중',
        22 => '부채증명서 발급완료',
        25 => '신청서 작성 진행중',
        30 => '신청서 제출',
        40 => '보정기간',
        100 => '파산선고',
        105 => '의견청취기일',
        110 => '재산환가 및 배당',
        115 => '파산폐지',
        120 => '면책결정',
        125 => '면책불허가',
    ];

    public static function getStateLabel($caseType, $stateValue)
    {
        $states = ($caseType == 2) ? self::BANKRUPTCY_STATES : self::REVIVAL_STATES;
        return $states[$stateValue] ?? $stateValue;
    }
} 