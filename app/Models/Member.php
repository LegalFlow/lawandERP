<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Member extends Model
{
    use HasFactory;

    // 대량 할당을 허용할 필드 목록
    protected $fillable = [
        'name',
        'position',
        'task',
        'affiliation',
        'status',
        'bank',
        'account_number',
        'notes',
        'flexible_working',
        'block_8_17',
        'block_9_18',
        'block_10_19',
        'block_9_16',
        'paid_holiday',
        'car_cost',
        'childcare',
        'annual_start_period',
        'annual_end_period',
        'house_work',
        'standard'
    ];

    protected $casts = [
        'flexible_working' => 'boolean',
        'annual_start_period' => 'date:Y-m-d',
        'annual_end_period' => 'date:Y-m-d'
    ];

    /**
     * 이 멤버와 관련된 income_entries를 가져옵니다.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function incomeEntries()
    {
        return $this->hasMany(IncomeEntry::class, 'representative_id', 'id');
    }

    /**
     * 특정 월의 매출 합계를 계산합니다.
     * 
     * @param string $yearMonth 'YYYY-MM' 형식의 년월
     * @return float
     */
    public function monthlyIncome($yearMonth)
    {
        try {
            return $this->incomeEntries()
                ->whereRaw("DATE_FORMAT(deposit_date, '%Y-%m') = ?", [$yearMonth])
                ->where('account_type', '서비스매출')
                ->sum('amount');
        } catch (\Exception $e) {
            \Log::error('Monthly income calculation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 현재 월의 매출 합계를 계산합니다.
     * 
     * @return float
     */
    public function currentMonthIncome()
    {
        return $this->monthlyIncome(Carbon::now()->format('Y-m'));
    }

    /**
     * 멤버의 활성 상태를 확인합니다..
     * 
     * @return bool
     */
    public function isActive()
    {
        return $this->status === '재직중';
    }

    /**
     * 멤버와 연결된 사용자 정보를 가져옵니다.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 멤버의 연봉계약서 정보를 가져옵니다.
     */
    public function salaryContract()
    {
        return $this->hasMany(SalaryContract::class);
    }
}
