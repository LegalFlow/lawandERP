<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LawyerFeeDetail extends Model
{
    protected $table = 'TblLawyerFeeDetail';
    protected $primaryKey = 'idx';
    public $timestamps = false;

    protected $fillable = [
        'idx',
        'case_idx',
        'detail',
        'alarm_dt'
    ];

    protected $appends = [
        'scheduled_date',
        'settlement_date',
        'money',
        'state'
    ];

    // LawyerFee와의 관계
    public function lawyerFee()
    {
        return $this->belongsTo(LawyerFee::class, 'case_idx', 'case_idx');
    }

    // Target과의 관계
    public function target()
    {
        return $this->belongsTo(Target::class, 'case_idx', 'idx_TblCase');
    }

    // fee_type에 따른 표시 텍스트 반환
    public function getFeeTypeText()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        
        if (!isset($detail['fee_type'])) {
            return '미지정';
        }

        switch ($detail['fee_type']) {
            case -1:
                return '미지정';
            case 0:
                return '송달료 등 부대비용';
            case 1:
                return '착수금';
            case 2:
                // 분할납부의 경우 별도 처리 필요
                return '분할납부'; // 기본값으로 설정, 컨트롤러에서 차수 계산
            case 3:
                return '성공보수';
            default:
                return '미지정';
        }
    }

    // 납부 상태 텍스트 반환
    public function getStateText()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        
        if (!isset($detail['state'])) {
            return '미납';
        }

        return $detail['state'] == 1 ? '완납' : '미납';
    }

    // 분할납부 차수 계산 및 텍스트 반환
    public function getInstallmentText()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        
        if (!isset($detail['fee_type']) || $detail['fee_type'] != 2) {
            return $this->getFeeTypeText();
        }

        // 컨트롤러에서 계산하므로 여기서는 기본값만 반환
        return '분할납부';
    }

    // 날짜 형식 변환 (YYYY-MM-DD)
    public function getScheduledDateAttribute()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        return isset($detail['scheduled_date']) ? $detail['scheduled_date'] : null;
    }

    public function getSettlementDateAttribute()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        return isset($detail['settlement_date']) ? $detail['settlement_date'] : null;
    }

    // 금액 정보 반환
    public function getMoneyAttribute()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        return isset($detail['money']) ? $detail['money'] : 0;
    }

    // 상태 정보 반환
    public function getStateAttribute()
    {
        $detail = is_array($this->detail) ? $this->detail : json_decode($this->detail, true);
        return isset($detail['state']) ? $detail['state'] : 0;
    }

    /**
     * detail 접근자
     */
    public function getDetailAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // 이중 인코딩 확인
            $decodedValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 디코딩 성공
                return $decodedValue;
            } else {
                // 이중 인코딩일 수 있음, 한 번 더 시도
                $decodedOnce = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_string($decodedOnce)) {
                    $decodedTwice = json_decode($decodedOnce, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decodedTwice;
                    }
                }
            }
            
            // 디코딩 실패 시 빈 배열 반환
            return [];
        }
        
        return [];
    }
    
    /**
     * detail 변경자
     */
    public function setDetailAttribute($value)
    {
        // 이미 JSON 문자열이면 그대로 사용, 아니면 JSON으로 변환
        if (is_string($value)) {
            // JSON 문자열인지 확인
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                // 유효한 JSON 문자열이면 그대로 사용
                $this->attributes['detail'] = $value;
            } else {
                // JSON이 아니면 인코딩
                $this->attributes['detail'] = json_encode($value);
            }
        } else if (is_array($value)) {
            // 배열이면 인코딩
            $this->attributes['detail'] = json_encode($value);
        } else {
            // 그 외의 경우 빈 객체로 설정
            $this->attributes['detail'] = '{}';
        }
    }
} 