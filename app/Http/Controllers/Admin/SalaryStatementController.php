<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryStatement;
use App\Models\User;
use App\Models\Member;
use App\Models\SalaryContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SalaryStatementController extends Controller
{
    /**
     * 급여명세서 목록을 표시
     */
    public function index()
    {
        $statements = SalaryStatement::with(['user'])
            ->orderBy('statement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // 직전 달의 통계 데이터 가져오기
        $lastMonth = now()->subMonth();
        $defaultStats = $this->getStatistics([
            'statement_date' => $lastMonth->format('Y-m')
        ]);

        return view('admin.salary-statements.index', compact('statements', 'defaultStats'));
    }

    /**
     * 필터링된 급여명세서 목록과 통계 조회
     */
    public function filter(Request $request)
    {
        try {
            $query = SalaryStatement::query()
                ->with(['user.member']);

            // 귀속년월 필터
            if ($request->filled('statement_date')) {
                $date = $request->statement_date;
                $query->whereYear('statement_date', substr($date, 0, 4))
                      ->whereMonth('statement_date', substr($date, 5, 2));
            }

            // 직급 필터
            if ($request->filled('position')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('user.member', function($q) use ($request) {
                        $q->where('position', $request->position);
                    })->when($request->position === '기타', function($q) {
                        $q->orWhereDoesntHave('user')
                          ->orWhereHas('user.member', function($q) {
                              $q->whereNull('position');
                          });
                    });
                });
            }

            // 업무 필터
            if ($request->filled('task')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('user.member', function($q) use ($request) {
                        $q->where('task', $request->task);
                    })->when($request->task === '기타', function($q) {
                        $q->orWhereDoesntHave('user')
                          ->orWhereHas('user.member', function($q) {
                              $q->whereNull('task');
                          });
                    });
                });
            }

            // 지역 필터
            if ($request->filled('affiliation')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('user.member', function($q) use ($request) {
                        $q->where('affiliation', $request->affiliation);
                    })->when($request->affiliation === '기타', function($q) {
                        $q->orWhereDoesntHave('user')
                          ->orWhereHas('user.member', function($q) {
                              $q->whereNull('affiliation');
                          });
                    });
                });
            }

            // 이름 검색
            if ($request->filled('name')) {
                $query->where(function($q) use ($request) {
                    $q->whereHas('user', function($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->name . '%');
                    })->orWhere('name', 'like', '%' . $request->name . '%');
                });
            }

            // 별산 등 포함 여부
            if (!$request->boolean('include_others')) {
                $query->where(function($q) {
                    $q->whereHas('user.member', function($q) {
                        $q->whereNotNull('task')
                          ->where('task', '!=', '기타');
                    });
                });
            }

            // 통계 데이터 계산
            $statistics = [
                'total_payment' => $query->sum('total_payment'),
                'total_deduction' => $query->sum('total_deduction'),
                'net_payment' => $query->sum('net_payment')
            ];

            // 페이지네이션된 결과 가져오기
            $statements = $query->orderBy('statement_date', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->paginate(15);

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
                'statements' => $statements
            ]);

        } catch (\Exception $e) {
            Log::error('급여명세서 필터링 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '데이터 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 통계 데이터 조회
     */
    private function getStatistics($filters = [])
    {
        $query = SalaryStatement::query();

        // 필터 적용
        if (!empty($filters['statement_date'])) {
            $date = $filters['statement_date'];
            $query->whereYear('statement_date', substr($date, 0, 4))
                  ->whereMonth('statement_date', substr($date, 5, 2));
        }

        return [
            'total_payment' => $query->sum('total_payment'),
            'total_deduction' => $query->sum('total_deduction'),
            'net_payment' => $query->sum('net_payment')
        ];
    }

    /**
     * 급여명세서 상세 수정 페이지
     */
    public function edit(SalaryStatement $salaryStatement)
    {
        $salaryStatement->load(['user']);
        
        return view('admin.salary-statements.edit', compact('salaryStatement'));
    }

    /**
     * 급여명세서 수정 처리
     */
    public function update(Request $request, SalaryStatement $salaryStatement)
    {
        $validated = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'meal_allowance' => 'nullable|numeric|min:0',
            'vehicle_allowance' => 'nullable|numeric|min:0',
            'child_allowance' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'performance_pay' => 'nullable|numeric|min:0',
            'vacation_pay' => 'nullable|numeric|min:0',
            'adjustment_pay' => 'nullable|numeric',
            'income_tax' => 'nullable|numeric',
            'local_income_tax' => 'nullable|numeric',
            'national_pension' => 'nullable|numeric',
            'health_insurance' => 'nullable|numeric',
            'long_term_care' => 'nullable|numeric',
            'employment_insurance' => 'nullable|numeric',
            'student_loan_repayment' => 'nullable|numeric',
            'other_deductions' => 'nullable|numeric',
            'year_end_tax' => 'nullable|numeric',
            'year_end_local_tax' => 'nullable|numeric',
            'health_insurance_adjustment' => 'nullable|numeric',
            'long_term_adjustment' => 'nullable|numeric',
            'interim_tax' => 'nullable|numeric',
            'interim_local_tax' => 'nullable|numeric',
            'agriculture_tax' => 'nullable|numeric',
            'memo' => 'nullable|string'
        ]);

        // 수정 시 승인 상태 초기화
        $validated['approved_at'] = null;
        $validated['approved_by'] = null;

        $salaryStatement->fill($validated);
        $salaryStatement->calculateNetPayment(); // 총액 계산
        $salaryStatement->save();

        return redirect()
            ->route('admin.salary-statements.index')
            ->with('success', '급여명세서가 수정되었습니다.');
    }

    /**
     * 일괄 생성을 위한 재직자 목록 조회
     */
    public function create()
    {
        try {
            $activeMembers = Member::whereIn('status', ['재직', '휴직'])
                ->select('id', 'name', 'position', 'affiliation', 'task', 'status', 'car_cost', 'childcare')
                ->get();

            $membersWithUsers = $activeMembers->map(function ($member) {
                $user = User::where('name', $member->name)->first();
                $latestContract = $user ? SalaryContract::where('user_id', $user->id)
                    ->whereNotNull('approved_at')
                    ->orderBy('created_at', 'desc')
                    ->first() : null;

                if ($latestContract) {
                    // 고정 식대 200,000원
                    $mealAllowance = 200000;
                    
                    // 차량유지비: car_cost가 1이면 200,000원, 아니면 0원
                    $vehicleAllowance = $member->car_cost == 1 ? 200000 : 0;
                    
                    // 보육수당: childcare가 1이면 200,000원, 아니면 0원
                    $childAllowance = $member->childcare == 1 ? 200000 : 0;
                    
                    // 실제 기본급은 연봉계약서의 base_salary에서 각종 수당을 뺀 금액
                    $baseSalary = $latestContract->base_salary - ($mealAllowance + $vehicleAllowance + $childAllowance);
                }

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'position' => $member->position,
                    'affiliation' => $member->affiliation,
                    'task' => $member->task,
                    'user_id' => $user ? $user->id : null,
                    'salary_contract' => $latestContract ? [
                        'monthly_salary' => $baseSalary,
                        'meal_allowance' => $mealAllowance,
                        'vehicle_allowance' => $vehicleAllowance,
                        'child_allowance' => $childAllowance
                    ] : null
                ];
            })->filter(function ($member) {
                return $member['user_id'] !== null;
            });

            return response()->json([
                'members' => $membersWithUsers
            ]);
        } catch (\Exception $e) {
            Log::error('급여명세서 구성원 목록 조회 실패: ' . $e->getMessage());
            return response()->json([
                'message' => '구성원 목록을 불러오는데 실패했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 근로소득공제액 계산
     */
    private function calculateIncomeTaxDeduction($annualIncome)
    {
        if ($annualIncome <= 12000000) {
            return $annualIncome * 0.7;
        } elseif ($annualIncome <= 46000000) {
            return 8400000 + ($annualIncome - 12000000) * 0.4;
        } elseif ($annualIncome <= 88000000) {
            return 17600000 + ($annualIncome - 46000000) * 0.15;
        } elseif ($annualIncome <= 150000000) {
            return 23200000 + ($annualIncome - 88000000) * 0.05;
        } else {
            return 26000000 + ($annualIncome - 150000000) * 0.02;
        }
    }

    /**
     * 소득세 계산
     */
    private function calculateIncomeTax($taxableIncome)
    {
        $tax = 0;

        // 1구간: 1400만원 이하 (6%)
        if ($taxableIncome <= 14000000) {
            $tax = $taxableIncome * 0.06;
        } else {
            $tax = 14000000 * 0.06;
            
            // 2구간: 1400만원 초과 5000만원 이하 (15%)
            if ($taxableIncome <= 50000000) {
                $tax += ($taxableIncome - 14000000) * 0.15;
            } else {
                $tax += (50000000 - 14000000) * 0.15;
                
                // 3구간: 5000만원 초과 8800만원 이하 (24%)
                if ($taxableIncome <= 88000000) {
                    $tax += ($taxableIncome - 50000000) * 0.24;
                } else {
                    $tax += (88000000 - 50000000) * 0.24;
                    
                    // 4구간: 8800만원 초과 1.5억원 이하 (35%)
                    if ($taxableIncome <= 150000000) {
                        $tax += ($taxableIncome - 88000000) * 0.35;
                    } else {
                        $tax += (150000000 - 88000000) * 0.35;
                        
                        // 5구간: 1.5억원 초과 3억원 이하 (38%)
                        if ($taxableIncome <= 300000000) {
                            $tax += ($taxableIncome - 150000000) * 0.38;
                        } else {
                            $tax += (300000000 - 150000000) * 0.38;
                            
                            // 6구간: 3억원 초과 5억원 이하 (40%)
                            if ($taxableIncome <= 500000000) {
                                $tax += ($taxableIncome - 300000000) * 0.40;
                            } else {
                                $tax += (500000000 - 300000000) * 0.40;
                                // 7구간: 5억원 초과 (42%)
                                $tax += ($taxableIncome - 500000000) * 0.42;
                            }
                        }
                    }
                }
            }
        }

        return floor($tax);
    }

    /**
     * 월 소득세 계산
     */
    private function calculateMonthlyIncomeTax($statement)
    {
        // 과세대상 월급여 합계 (비과세 항목 제외)
        $monthlyTaxableIncome = $statement['base_salary'] +
                               ($statement['performance_pay'] ?? 0) +
                               ($statement['adjustment_pay'] ?? 0) +
                               ($statement['vacation_pay'] ?? 0);

        // 연봉 계산
        $annualIncome = $monthlyTaxableIncome * 12;

        // 근로소득공제 계산
        $taxDeduction = $this->calculateIncomeTaxDeduction($annualIncome);

        // 과세표준 계산
        $taxableIncome = $annualIncome - $taxDeduction;

        // 연간 소득세 계산
        $annualTax = $this->calculateIncomeTax($taxableIncome);

        // 월 소득세 계산 및 추가 공제율(0.85) 적용
        $monthlyTax = ($annualTax / 12) * 0.80;
    
        
        // 원단위 절사 (10원 단위로 절사)
        return floor($monthlyTax / 10) * 10;
    }

    /**
     * 급여명세서 일괄 생성 처리
     */
    public function bulkCreate(Request $request)
    {
        try {
            DB::beginTransaction();

            $statements = $request->input('statements', []);
            $statementDate = $request->input('statement_date');
            $createdCount = 0;

            foreach ($statements as $statement) {
                // 필수 필드 검증
                if (empty($statement['user_id'])) {
                    continue;
                }

                // 이번 달 급여명세서가 이미 있는지 확인
                $exists = SalaryStatement::where('user_id', $statement['user_id'])
                    ->whereYear('statement_date', now()->year)
                    ->whereMonth('statement_date', now()->month)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // 최신 연봉계약서 조회
                $contract = SalaryContract::where('user_id', $statement['user_id'])
                    ->whereNotNull('approved_at')  // 승인된 연봉계약서만 조회하도록 복원
                    ->orderBy('created_at', 'desc')
                    ->first();

                if (!$contract) {  // 승인된 연봉계약서가 없으면 건너뛰도록 복원
                    continue;
                }

                // 총 세전금액 (모든 수당 포함)
                $totalPayment = $statement['base_salary'] +
                               ($statement['meal_allowance'] ?? 0) +
                               ($statement['vehicle_allowance'] ?? 0) +
                               ($statement['child_allowance'] ?? 0) +
                               ($statement['performance_pay'] ?? 0) +
                               ($statement['adjustment_pay'] ?? 0) +
                               ($statement['vacation_pay'] ?? 0);

                // 과세대상 금액 (비과세 항목 제외)
                $taxableAmount = $statement['base_salary'] +
                                ($statement['performance_pay'] ?? 0) +
                                ($statement['adjustment_pay'] ?? 0) +
                                ($statement['vacation_pay'] ?? 0);

                // 소득세 계산
                $incomeTax = $this->calculateMonthlyIncomeTax($statement);
                
                // 지방소득세 계산 (소득세의 10%, 원단위 절사)
                $localIncomeTax = floor(($incomeTax * 0.1) / 10) * 10;

                // 4대보험 계산 (과세대상 금액 기준)
                $nationalPension = floor($taxableAmount * 0.045);
                $healthInsurance = floor($taxableAmount * 0.0343);
                $longTermCare = floor($healthInsurance * 0.1227);
                
                // 사용자 정보 조회
                $user = User::find($statement['user_id']);
                
                // 대표자(김충환)인 경우 고용보험 0으로 설정
                if ($user && $user->name === '김충환') {
                    $employmentInsurance = 0;
                } else {
                    // 과세대상 금액의 0.9%를 정수 연산으로 정확히 계산
                    // 0.009는 소수점 오차가 있을 수 있으므로 정수 연산으로 우회
                    $employmentInsurance = (int)(($taxableAmount * 9) / 1000);
                    // 정확히 10원 단위로 내림 처리
                    $employmentInsurance = (int)($employmentInsurance / 10) * 10;
                }

                // 총 공제금액 (모든 공제항목 포함)
                $totalDeduction = $incomeTax +
                                 $localIncomeTax +
                                 $nationalPension +
                                 $healthInsurance +
                                 $longTermCare +
                                 $employmentInsurance +
                                 ($statement['other_deductions'] ?? 0) +
                                 ($statement['year_end_tax'] ?? 0) +
                                 ($statement['year_end_local_tax'] ?? 0) +
                                 ($statement['health_insurance_adjustment'] ?? 0) +
                                 ($statement['long_term_adjustment'] ?? 0) +
                                 ($statement['interim_tax'] ?? 0) +
                                 ($statement['interim_local_tax'] ?? 0) +
                                 ($statement['agriculture_tax'] ?? 0);

                // 실 지급액
                $netPayment = $totalPayment - $totalDeduction;

                // 급여명세서 생성
                SalaryStatement::create([
                    'user_id' => $statement['user_id'],
                    'contract_id' => $contract->id,
                    'statement_date' => $statementDate,
                    'base_salary' => $statement['base_salary'],
                    'meal_allowance' => $statement['meal_allowance'] ?? 0,
                    'vehicle_allowance' => $statement['vehicle_allowance'] ?? 0,
                    'child_allowance' => $statement['child_allowance'] ?? 0,
                    'performance_pay' => $statement['performance_pay'] ?? 0,
                    'adjustment_pay' => $statement['adjustment_pay'] ?? 0,
                    'vacation_pay' => $statement['vacation_pay'] ?? 0,
                    'income_tax' => $incomeTax,
                    'local_income_tax' => $localIncomeTax,
                    'national_pension' => $nationalPension,
                    'health_insurance' => $healthInsurance,
                    'long_term_care' => $longTermCare,
                    'employment_insurance' => $employmentInsurance,
                    'total_payment' => $totalPayment,
                    'total_deduction' => $totalDeduction,
                    'net_payment' => $netPayment,
                    'created_by' => auth()->id()
                ]);

                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "{$createdCount}개의 급여명세서가 생성되었습니다.",
                'count' => $createdCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('급여명세서 일괄 생성 실패: ' . $e->getMessage());
            
            return response()->json([
                'message' => '급여명세서 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 급여명세서 승인 처리
     */
    public function approve(SalaryStatement $salaryStatement)
    {
        $salaryStatement->update([
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);

        return redirect()
            ->back()
            ->with('success', '급여명세서가 승인되었습니다.');
    }

    /**
     * 급여명세서 삭제
     */
    public function destroy(SalaryStatement $salaryStatement)
    {
        try {
            $salaryStatement->delete();
            
            return response()->json([
                'success' => true,
                'message' => '급여명세서가 삭제되었습니다.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '급여명세서 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 급여명세서 상세 표시
     */
    public function show(SalaryStatement $salaryStatement)
    {
        $salaryStatement->load(['user', 'creator']);
        return view('admin.salary-statements.show', compact('salaryStatement'));
    }

    /**
     * 전체 직원 성과금 현황 조회
     */
    public function getPerformanceStatus(Request $request)
    {
        try {
            Log::info('성과금 현황 조회 시작');
            
            // standard 값이 있는 멤버만 조회
            $members = Member::where('status', '재직')
                ->where('standard', '>', 0)
                ->with('user')
                ->get();
            
            Log::info('멤버 조회 완료', ['count' => $members->count()]);

            // 기본값으로 현재 날짜 사용
            $now = now()->startOfDay();
            
            // 요청에서 연도와 분기 받기
            $requestYear = $request->input('year');
            $requestQuarter = $request->input('quarter');
            
            // 요청된 연도와 분기가 있으면 해당 분기의 시작일과 종료일 계산
            if ($requestYear && $requestQuarter) {
                // 분기 시작일 계산
                $quarterStart = Carbon::createFromDate($requestYear, ($requestQuarter - 1) * 3 + 1, 1)->startOfDay();
                // 분기 종료일 계산
                $quarterEnd = $quarterStart->copy()->endOfQuarter();
                
                // 분기가 완전히 지났는지 확인
                $isCompletedQuarter = $quarterEnd->lt($now);
                
                // 과거 분기의 경우 분기 마지막 날로 설정, 현재 진행 중인 분기의 경우 오늘로 설정
                if (!$isCompletedQuarter && $quarterEnd->gt($now)) {
                    $quarterEnd = $now;
                }
            } else {
                // 요청된 분기 정보가 없으면 현재 분기 사용
                $quarterStart = $now->copy()->startOfQuarter()->startOfDay();
                $quarterEnd = $now;
                $isCompletedQuarter = false;
            }
            
            $totalDays = $quarterStart->daysInQuarter;
            
            // 분기가 완료되었으면 경과일을 총 일수와 동일하게 설정
            if (isset($isCompletedQuarter) && $isCompletedQuarter) {
                $elapsedDays = $totalDays;
            } else {
                $elapsedDays = $quarterStart->diffInDays($quarterEnd) + 1;
            }

            Log::info('분기 계산', [
                'year' => $requestYear ?? $now->year,
                'quarter' => $requestQuarter ?? ceil($now->month / 3),
                'quarterStart' => $quarterStart->format('Y-m-d'),
                'quarterEnd' => $quarterEnd->format('Y-m-d'),
                'totalDays' => $totalDays,
                'elapsedDays' => $elapsedDays,
                'isCompletedQuarter' => $isCompletedQuarter ?? false
            ]);

            $performanceData = $members->map(function ($member) use ($quarterEnd, $quarterStart, $totalDays, $elapsedDays, $isCompletedQuarter) {
                try {
                    Log::info('멤버 데이터 처리 시작', [
                        'name' => $member->name,
                        'standard' => $member->standard
                    ]);

                    // 현재 매출액 조회 - floor() 함수를 사용하여 소수점 버림
                    $currentAmount = floor(DB::table('service_sales')
                        ->where('manager', $member->name)
                        ->where('account', '서비스매출')
                        ->whereBetween('date', [
                            $quarterStart->format('Y-m-d'),
                            $quarterEnd->format('Y-m-d')
                        ])
                        ->sum('amount'));

                    Log::info('매출액 조회 완료', [
                        'name' => $member->name,
                        'amount' => $currentAmount,
                        'period' => [
                            'start' => $quarterStart->format('Y-m-d'),
                            'end' => $quarterEnd->format('Y-m-d')
                        ]
                    ]);

                    // 분기별 기준액 계산
                    $quarterlyStandard = $member->standard * 3;
                    
                    // 분기가 완료된 경우
                    if (isset($isCompletedQuarter) && $isCompletedQuarter) {
                        // 이미 완료된 분기는 일일 평균 계산 불필요, 실제 금액을 그대로 사용
                        $estimatedAmount = $currentAmount;
                        $currentStandard = $quarterlyStandard; // 분기 기준액과 동일하게 설정
                    } else {
                        // 진행 중인 분기는 일일 평균으로 예상 매출액 계산
                        $dailyAverage = $elapsedDays > 0 ? ($currentAmount / $elapsedDays) : 0;
                        $estimatedAmount = round($dailyAverage * $totalDays);
                        
                        // 현재 기준 매출액 계산 (분기 기준 × 경과율)
                        $currentStandard = round($quarterlyStandard * ($elapsedDays / $totalDays));
                    }

                    return [
                        'name' => $member->name,
                        'monthly_standard' => $member->standard,
                        'quarterly_standard' => $quarterlyStandard,
                        'current_standard' => $currentStandard,
                        'current_amount' => $currentAmount,
                        'estimated_amount' => $estimatedAmount,
                        'expected_bonus' => $this->calculateExpectedBonus($estimatedAmount, $quarterlyStandard),
                        'reward' => $this->determineRewardOrPenalty($estimatedAmount, $quarterlyStandard)
                    ];
                } catch (\Exception $e) {
                    Log::error('멤버 데이터 처리 중 오류', [
                        'name' => $member->name,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            })
            ->filter()
            ->sortByDesc('current_amount')
            ->values();

            // 분기 정보 계산
            $quarterNumber = ceil($quarterStart->month / 3);
            $yearNumber = $quarterStart->year;
            
            // 경과율 계산 - 분기가 완료된 경우 정확히 100%로 설정
            $elapsedRate = isset($isCompletedQuarter) && $isCompletedQuarter 
                ? 100 
                : round(($elapsedDays / $totalDays) * 100, 2);
            
            $elapsedInfo = [
                'date' => $quarterEnd->format('Y년 m월 d일'),
                'year' => $yearNumber,
                'quarter' => $quarterNumber,
                'quarter_name' => "{$yearNumber}년 {$quarterNumber}분기",
                'elapsed_days' => $elapsedDays,
                'total_days' => $totalDays,
                'elapsed_rate' => $elapsedRate
            ];

            // 현재부터 2025년 1분기까지의 분기 목록 작성
            $availableQuarters = [];
            $currentYear = now()->year;
            $currentQuarter = ceil(now()->month / 3);
            
            // 2025년 1분기부터 시작
            $startYear = 2025;
            $startQuarter = 1;
            
            // 현재 연도/분기부터 시작해서 2025년 1분기까지 거슬러 올라감
            for ($y = $currentYear; $y >= $startYear; $y--) {
                $maxQuarter = ($y == $currentYear) ? $currentQuarter : 4;
                $minQuarter = ($y == $startYear) ? $startQuarter : 1;
                
                for ($q = $maxQuarter; $q >= $minQuarter; $q--) {
                    $availableQuarters[] = [
                        'year' => $y,
                        'quarter' => $q,
                        'name' => "{$y}년 {$q}분기"
                    ];
                }
            }

            // 합계 계산
            $totals = [
                'monthly_standard' => $performanceData->sum('monthly_standard'),
                'quarterly_standard' => $performanceData->sum('quarterly_standard'),
                'current_standard' => $performanceData->sum('current_standard'),
                'current_amount' => $performanceData->sum('current_amount'),
                'estimated_amount' => $performanceData->sum('estimated_amount'),
                'expected_bonus' => $performanceData->sum('expected_bonus'),
            ];

            return response()->json([
                'success' => true,
                'elapsed_info' => $elapsedInfo,
                'totals' => $totals,  // 합계 데이터 추가
                'performance_data' => $performanceData,
                'available_quarters' => $availableQuarters
            ]);

        } catch (\Exception $e) {
            Log::error('성과금 현황 조회 실패', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '성과금 현황을 불러오는데 실패했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 성과금 계산 (기존 MyPage 컨트롤러의 메소드 재사용)
     */
    private function calculateExpectedBonus($estimated, $standard)
    {
        if ($estimated <= $standard) {
            return 0;
        }

        $excess = $estimated - $standard;
        $standardTenPercent = $standard * 0.1;
        $bonus = 0;

        $ranges = [
            [0, 10, 0.1],
            [10, 20, 0.2],
            [20, 30, 0.3],
            [30, 40, 0.4],
            [40, PHP_FLOAT_MAX, 0.5]
        ];

        foreach ($ranges as $range) {
            $rangeExcess = min(
                max(0, ($excess - ($standard * $range[0] / 100))),
                $standardTenPercent
            );
            $bonus += floor($rangeExcess * $range[2]);
        }

        return floor($bonus);
    }

    /**
     * 보상/제재 결정 (기존 MyPage 컨트롤러의 메소드 재사용)
     */
    private function determineRewardOrPenalty($estimated, $standard)
    {
        $rate = ($estimated - $standard) / $standard * 100;

        if ($rate >= 20) return '연차 1일 부여';
        if ($rate >= 10) return '반차 1일 부여';
        if ($rate <= -20) return '주4일 근무 제외';
        if ($rate <= -10) return '재택근무 제외';
        return '-';
    }

    /**
     * 급여명세서 직접 생성 처리
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // 요청 데이터 검증
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'affiliation' => 'required|string|max:255',
                'statement_date' => 'required|date',
                'base_salary' => 'required|numeric|min:0',
                'meal_allowance' => 'nullable|numeric|min:0',
                'vehicle_allowance' => 'nullable|numeric|min:0',
                'child_allowance' => 'nullable|numeric|min:0',
                'performance_pay' => 'nullable|numeric|min:0',
                'adjustment_pay' => 'nullable|numeric|min:0',
                'vacation_pay' => 'nullable|numeric|min:0',
                'employment_insurance' => 'nullable|numeric',
                'student_loan_repayment' => 'nullable|numeric',
                'other_deductions' => 'nullable|numeric',
            ]);

            // 소득세 계산을 위한 데이터 구성
            $statement = [
                'base_salary' => $validated['base_salary'],
                'performance_pay' => $validated['performance_pay'] ?? 0,
                'adjustment_pay' => $validated['adjustment_pay'] ?? 0,
                'vacation_pay' => $validated['vacation_pay'] ?? 0
            ];

            // 소득세 계산 (일괄 생성과 동일한 로직 사용)
            $incomeTax = $this->calculateMonthlyIncomeTax($statement);
            $localIncomeTax = floor(($incomeTax * 0.1) / 10) * 10;

            // 과세대상 금액 계산
            $taxableAmount = $validated['base_salary'] +
                            ($validated['performance_pay'] ?? 0) +
                            ($validated['adjustment_pay'] ?? 0) +
                            ($validated['vacation_pay'] ?? 0);

            // 4대보험 계산
            $nationalPension = floor($taxableAmount * 0.045);
            $healthInsurance = floor($taxableAmount * 0.0343);
            $longTermCare = floor($healthInsurance * 0.1227);
            
            // 대표자(김충환)인 경우 고용보험 0으로 설정 - 수정된 부분
            if ($validated['name'] === '김충환') {
                $employmentInsurance = 0;
            } else {
                // 과세대상 금액의 0.9%를 정수 연산으로 정확히 계산
                // 0.009는 소수점 오차가 있을 수 있으므로 정수 연산으로 우회
                $employmentInsurance = (int)(($taxableAmount * 9) / 1000);
                // 정확히 10원 단위로 내림 처리
                $employmentInsurance = (int)($employmentInsurance / 10) * 10;
            }

            // 총지급액 계산
            $totalPayment = $validated['base_salary'] +
                           ($validated['meal_allowance'] ?? 0) +
                           ($validated['vehicle_allowance'] ?? 0) +
                           ($validated['child_allowance'] ?? 0) +
                           ($validated['performance_pay'] ?? 0) +
                           ($validated['adjustment_pay'] ?? 0) +
                           ($validated['vacation_pay'] ?? 0);

            // 총공제액 계산
            $totalDeduction = $incomeTax +
                             $localIncomeTax +
                             $nationalPension +
                             $healthInsurance +
                             $longTermCare +
                             $employmentInsurance +
                             ($validated['student_loan_repayment'] ?? 0) +
                             ($validated['other_deductions'] ?? 0) +
                             ($validated['year_end_tax'] ?? 0) +
                             ($validated['year_end_local_tax'] ?? 0) +
                             ($validated['health_insurance_adjustment'] ?? 0) +
                             ($validated['long_term_adjustment'] ?? 0) +
                             ($validated['interim_tax'] ?? 0) +
                             ($validated['interim_local_tax'] ?? 0) +
                             ($validated['agriculture_tax'] ?? 0);

            // 실수령액 계산
            $netPayment = $totalPayment - $totalDeduction;

            // 급여명세서 생성
            $statement = SalaryStatement::create([
                'name' => $validated['name'],
                'position' => $validated['position'],
                'affiliation' => $validated['affiliation'],
                'statement_date' => $validated['statement_date'],
                'base_salary' => $validated['base_salary'],
                'meal_allowance' => $validated['meal_allowance'] ?? 0,
                'vehicle_allowance' => $validated['vehicle_allowance'] ?? 0,
                'child_allowance' => $validated['child_allowance'] ?? 0,
                'performance_pay' => $validated['performance_pay'] ?? 0,
                'adjustment_pay' => $validated['adjustment_pay'] ?? 0,
                'vacation_pay' => $validated['vacation_pay'] ?? 0,
                'income_tax' => $incomeTax,
                'local_income_tax' => $localIncomeTax,
                'national_pension' => $nationalPension,
                'health_insurance' => $healthInsurance,
                'long_term_care' => $longTermCare,
                'employment_insurance' => $employmentInsurance,
                'total_payment' => $totalPayment,
                'total_deduction' => $totalDeduction,
                'net_payment' => $netPayment,
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '급여명세서가 생성되었습니다.',
                'data' => $statement
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('급여명세서 직접 생성 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '급여명세서 생성 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 건강보험 정보 업데이트
     */
    public function updateInsurance(Request $request)
    {
        try {
            $request->validate([
                'statement_date' => 'required|date_format:Y-m',
                'file' => 'required|file|max:1024'  // mimes:csv 제거
            ]);

            // 파일 확장자 직접 체크
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'csv') {
                throw new \Exception('CSV 파일만 업로드 가능합니다.');
            }

            $statementDate = $request->statement_date;
            
            // 파일 내용 읽기
            $content = file_get_contents($file->getPathname());
            
            // 인코딩 감지
            $encoding = mb_detect_encoding($content, ['UTF-8', 'EUC-KR', 'ISO-8859-1'], true);
            Log::info('Detected encoding: ' . $encoding);

            // EUC-KR인 경우 UTF-8로 변환
            if ($encoding === 'EUC-KR' || $encoding === false) {
                $content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);
            }

            // 임시 파일 생성
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $content);
            
            // CSV 파일 읽기
            $handle = fopen($tempFile, 'r');
            
            // 첫 번째 행을 읽어서 형식 확인
            $firstRow = fgetcsv($handle);
            rewind($handle); // 파일 포인터를 다시 처음으로
            
            // 첫 번째 행이 비어있는 경우 (null이거나 모든 컬럼이 비어있는 경우)
            if ($firstRow === null || count(array_filter($firstRow)) === 0) {
                // 처음 6줄 스킵
                for ($i = 0; $i < 6; $i++) {
                    fgetcsv($handle);
                }
            }
            
            // 헤더 행 스킵
            fgetcsv($handle);
            
            // 데이터 처리
            $updateCount = 0;
            
            // 트랜잭션 시작
            DB::beginTransaction();
            
            while (($data = fgetcsv($handle)) !== false) {
                // 데이터 행이 충분한 열을 가지고 있는지 확인
                if (count($data) < 27) {
                    Log::warning('CSV 행의 열 수가 부족합니다.', ['columns' => count($data), 'data' => $data]);
                    continue; // 이 행은 건너뜀
                }
                
                $statement = SalaryStatement::where(function($query) use ($data, $statementDate) {
                    $query->where(function($q) use ($data) {
                        $q->whereHas('user', function($q) use ($data) {
                            $q->where('name', $data[3]);
                        });
                    })->orWhere(function($q) use ($data) {
                        $q->whereNull('user_id')
                          ->where('name', $data[3]);
                    });
                })
                ->whereYear('statement_date', substr($statementDate, 0, 4))
                ->whereMonth('statement_date', substr($statementDate, 5, 2))
                ->first();
                
                if ($statement) {
                    $statement->update([
                        'health_insurance' => intval($data[13]),
                        'long_term_care' => intval($data[26])
                    ]);
                    
                    $statement->calculateNetPayment();
                    $statement->save();
                    
                    $updateCount++;
                }
            }
            
            fclose($handle);
            // 임시 파일 삭제
            unlink($tempFile);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'count' => $updateCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            Log::error('건강보험 정보 업데이트 실패: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 국민연금 정보 업데이트
     */
    public function updatePension(Request $request)
    {
        try {
            $request->validate([
                'statement_date' => 'required|date_format:Y-m',
                'file' => 'required|file|max:1024'  // mimes:csv 제거
            ]);

            // 파일 확장자 직접 체크
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'csv') {
                throw new \Exception('CSV 파일만 업로드 가능합니다.');
            }

            $statementDate = $request->statement_date;
            
            // 파일 내용 읽기
            $content = file_get_contents($file->getPathname());
            
            // 인코딩 감지
            $encoding = mb_detect_encoding($content, ['UTF-8', 'EUC-KR', 'ISO-8859-1'], true);
            Log::info('Detected encoding: ' . $encoding);

            // EUC-KR인 경우 UTF-8로 변환
            if ($encoding === 'EUC-KR' || $encoding === false) {
                $content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);
            }

            // 임시 파일 생성
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $content);
            
            // CSV 파일 읽기
            $handle = fopen($tempFile, 'r');
            
            // 첫 번째 행을 읽어서 형식 확인
            $firstRow = fgetcsv($handle);
            rewind($handle); // 파일 포인터를 다시 처음으로
            
            // 첫 번째 행이 비어있는 경우 (null이거나 모든 컬럼이 비어있는 경우)
            if ($firstRow === null || count(array_filter($firstRow)) === 0) {
                // 처음 6줄 스킵
                for ($i = 0; $i < 6; $i++) {
                    fgetcsv($handle);
                }
            }
            
            // 헤더 행을 찾기 위해 최대 10줄까지 읽어보기
            $headers = null;
            $insuranceAmountIndex = -1;
            $nameIndex = -1;
            
            for ($i = 0; $i < 10; $i++) {
                $row = fgetcsv($handle);
                if (!$row) break;
                
                // 각 열을 검사하여 '결정보험료'와 '성명' 또는 유사한 단어가 포함된 열 찾기
                foreach ($row as $index => $cell) {
                    $cell = trim($cell);
                    if (empty($cell)) continue;
                    
                    // 로그에 현재 검사 중인 셀 기록
                    Log::info('검사 중인 셀', ['row' => $i, 'index' => $index, 'content' => $cell]);
                    
                    // 결정보험료 열 찾기 (다양한 변형 고려)
                    if ($insuranceAmountIndex === -1 && (
                        strpos($cell, '결정보험료') !== false || 
                        strpos($cell, '보험료') !== false || 
                        strpos($cell, '금액') !== false
                    )) {
                        $insuranceAmountIndex = $index;
                        Log::info('결정보험료 열 발견', ['index' => $index, 'content' => $cell]);
                    }
                    
                    // 성명 열 찾기 (다양한 변형 고려)
                    if ($nameIndex === -1 && (
                        strpos($cell, '성명') !== false || 
                        strpos($cell, '이름') !== false || 
                        strpos($cell, '사업장') !== false ||
                        strpos($cell, '가입자명') !== false
                    )) {
                        $nameIndex = $index;
                        Log::info('성명 열 발견', ['index' => $index, 'content' => $cell]);
                    }
                }
                
                // 두 열을 모두 찾았으면 헤더로 설정하고 루프 종료
                if ($insuranceAmountIndex !== -1 && $nameIndex !== -1) {
                    $headers = $row;
                    break;
                }
            }
            
            // 헤더를 찾지 못했거나 필요한 열을 찾지 못한 경우
            if (!$headers || $insuranceAmountIndex === -1 || $nameIndex === -1) {
                // CSV 파일 내용 로깅 (처음 몇 줄만)
                rewind($handle);
                $sampleRows = [];
                for ($i = 0; $i < 10; $i++) {
                    $row = fgetcsv($handle);
                    if (!$row) break;
                    $sampleRows[] = $row;
                }
                
                Log::error('CSV 파일 분석 실패', [
                    'sample_rows' => $sampleRows
                ]);
                
                throw new \Exception('CSV 파일 형식이 올바르지 않습니다. 국민연금 CSV 파일인지 확인해주세요.');
            }
            
            Log::info('CSV 헤더 분석 성공', [
                '결정보험료 인덱스' => $insuranceAmountIndex,
                '성명 인덱스' => $nameIndex,
                '헤더' => $headers
            ]);
            
            // 데이터 처리를 위해 파일 포인터 재설정
            // 헤더 다음 행부터 읽기 시작
            
            // 데이터 처리
            $updateCount = 0;
            
            // 트랜잭션 시작
            DB::beginTransaction();
            
            while (($data = fgetcsv($handle)) !== false) {
                // 데이터 행이 충분한 열을 가지고 있는지 확인
                if (count($data) <= max($insuranceAmountIndex, $nameIndex)) {
                    Log::warning('CSV 행의 열 수가 부족합니다.', ['columns' => count($data), 'data' => $data]);
                    continue; // 이 행은 건너뜀
                }
                
                // 이름과 보험료 금액 가져오기
                $name = trim($data[$nameIndex]);
                $insuranceAmount = trim($data[$insuranceAmountIndex]);
                
                // 숫자가 아닌 문자 제거 (쉼표, 원 등)
                $insuranceAmount = preg_replace('/[^0-9]/', '', $insuranceAmount);
                
                if (empty($name) || empty($insuranceAmount)) {
                    Log::warning('이름 또는 보험료가 비어있습니다.', ['name' => $name, 'amount' => $insuranceAmount]);
                    continue; // 이름이나 보험료가 비어있으면 건너뜀
                }
                
                Log::info('처리 중인 데이터', ['name' => $name, 'amount' => $insuranceAmount]);
                
                $statement = SalaryStatement::where(function($query) use ($name, $statementDate) {
                    $query->where(function($q) use ($name) {
                        $q->whereHas('user', function($q) use ($name) {
                            $q->where('name', $name);
                        });
                    })->orWhere(function($q) use ($name) {
                        $q->whereNull('user_id')
                          ->where('name', $name);
                    });
                })
                ->whereYear('statement_date', substr($statementDate, 0, 4))
                ->whereMonth('statement_date', substr($statementDate, 5, 2))
                ->first();
                
                if ($statement) {
                    $pensionAmount = intval($insuranceAmount) / 2; // 국민연금 금액은 전체 금액의 절반(사용자 부담분)
                    $statement->update([
                        'national_pension' => $pensionAmount
                    ]);
                    
                    $statement->calculateNetPayment();
                    $statement->save();
                    
                    $updateCount++;
                    Log::info('데이터 업데이트 성공', ['name' => $name, 'pension_amount' => $pensionAmount]);
                } else {
                    Log::warning('해당 이름의 급여명세서를 찾을 수 없습니다.', ['name' => $name, 'statement_date' => $statementDate]);
                }
            }
            
            fclose($handle);
            // 임시 파일 삭제
            unlink($tempFile);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'count' => $updateCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            Log::error('국민연금 정보 업데이트 실패: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 고용보험 정보 업데이트
     */
    public function updateEmployment(Request $request)
    {
        try {
            $request->validate([
                'statement_date' => 'required|date_format:Y-m',
                'file' => 'required|file|max:1024'  // mimes:csv 제거
            ]);

            // 파일 확장자 직접 체크
            $file = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'csv') {
                throw new \Exception('CSV 파일만 업로드 가능합니다.');
            }

            $statementDate = $request->statement_date;
            
            // 파일 내용 읽기
            $content = file_get_contents($file->getPathname());
            
            // 인코딩 감지
            $encoding = mb_detect_encoding($content, ['UTF-8', 'EUC-KR', 'ISO-8859-1'], true);
            Log::info('Detected encoding: ' . $encoding);

            // EUC-KR인 경우 UTF-8로 변환
            if ($encoding === 'EUC-KR' || $encoding === false) {
                $content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);
            }

            // 임시 파일 생성
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $content);
            
            // CSV 파일 읽기
            $handle = fopen($tempFile, 'r');
            
            // 첫 번째 행을 읽어서 형식 확인
            $firstRow = fgetcsv($handle);
            rewind($handle); // 파일 포인터를 다시 처음으로
            
            // 첫 번째 행이 비어있는 경우 (null이거나 모든 컬럼이 비어있는 경우)
            if ($firstRow === null || count(array_filter($firstRow)) === 0) {
                // 처음 6줄 스킵
                for ($i = 0; $i < 6; $i++) {
                    fgetcsv($handle);
                }
            }
            
            // 헤더 행을 찾기 위해 최대 10줄까지 읽어보기
            $headers = null;
            $insuranceAmountIndex = -1;
            $nameIndex = -1;
            
            for ($i = 0; $i < 10; $i++) {
                $row = fgetcsv($handle);
                if (!$row) break;
                
                // 각 열을 검사하여 '결정보험료'와 '가입자명' 또는 유사한 단어가 포함된 열 찾기
                foreach ($row as $index => $cell) {
                    $cell = trim($cell);
                    if (empty($cell)) continue;
                    
                    // 로그에 현재 검사 중인 셀 기록
                    Log::info('검사 중인 셀', ['row' => $i, 'index' => $index, 'content' => $cell]);
                    
                    // 결정보험료 열 찾기 (다양한 변형 고려)
                    if ($insuranceAmountIndex === -1 && (
                        strpos($cell, '결정보험료') !== false || 
                        strpos($cell, '보험료') !== false || 
                        strpos($cell, '월평균보수') !== false ||
                        strpos($cell, '월보수총액') !== false
                    )) {
                        $insuranceAmountIndex = $index;
                        Log::info('보험료/보수 열 발견', ['index' => $index, 'content' => $cell]);
                    }
                    
                    // 가입자명 열 찾기
                    if ($nameIndex === -1 && (
                        strpos($cell, '성명') !== false || 
                        strpos($cell, '이름') !== false || 
                        strpos($cell, '가입자명') !== false
                    )) {
                        $nameIndex = $index;
                        Log::info('가입자명 열 발견', ['index' => $index, 'content' => $cell]);
                    }
                }
                
                // 두 열을 모두 찾았으면 헤더로 설정하고 루프 종료
                if ($insuranceAmountIndex !== -1 && $nameIndex !== -1) {
                    $headers = $row;
                    break;
                }
            }
            
            // 헤더를 찾지 못했거나 필요한 열을 찾지 못한 경우
            if (!$headers || $insuranceAmountIndex === -1 || $nameIndex === -1) {
                // CSV 파일 내용 로깅 (처음 몇 줄만)
                rewind($handle);
                $sampleRows = [];
                for ($i = 0; $i < 10; $i++) {
                    $row = fgetcsv($handle);
                    if (!$row) break;
                    $sampleRows[] = $row;
                }
                
                Log::error('CSV 파일 분석 실패', [
                    'sample_rows' => $sampleRows
                ]);
                
                throw new \Exception('CSV 파일 형식이 올바르지 않습니다. 고용보험 CSV 파일인지 확인해주세요.');
            }
            
            Log::info('CSV 헤더 분석 성공', [
                '보험료/보수 인덱스' => $insuranceAmountIndex,
                '가입자명 인덱스' => $nameIndex,
                '헤더' => $headers
            ]);
            
            // 데이터 처리
            $updateCount = 0;
            
            // 트랜잭션 시작
            DB::beginTransaction();
            
            while (($data = fgetcsv($handle)) !== false) {
                // 데이터 행이 충분한 열을 가지고 있는지 확인
                if (count($data) <= max($insuranceAmountIndex, $nameIndex)) {
                    Log::warning('CSV 행의 열 수가 부족합니다.', ['columns' => count($data), 'data' => $data]);
                    continue; // 이 행은 건너뜀
                }
                
                // 이름과 보험료/보수 금액 가져오기
                $name = trim($data[$nameIndex]);
                $amount = trim($data[$insuranceAmountIndex]);
                
                // 숫자가 아닌 문자 제거 (쉼표, 원 등)
                $amount = preg_replace('/[^0-9]/', '', $amount);
                
                if (empty($name) || empty($amount)) {
                    Log::warning('이름 또는 금액이 비어있습니다.', ['name' => $name, 'amount' => $amount]);
                    continue; // 이름이나 금액이 비어있으면 건너뜀
                }
                
                Log::info('처리 중인 데이터', ['name' => $name, 'amount' => $amount]);
                
                $statement = SalaryStatement::where(function($query) use ($name, $statementDate) {
                    $query->where(function($q) use ($name) {
                        $q->whereHas('user', function($q) use ($name) {
                            $q->where('name', $name);
                        });
                    })->orWhere(function($q) use ($name) {
                        $q->whereNull('user_id')
                          ->where('name', $name);
                    });
                })
                ->whereYear('statement_date', substr($statementDate, 0, 4))
                ->whereMonth('statement_date', substr($statementDate, 5, 2))
                ->first();
                
                if ($statement) {
                    // 헤더에 따라 다른 계산 방식 적용
                    $headerText = $headers[$insuranceAmountIndex] ?? '';
                    
                    if (strpos($headerText, '결정보험료') !== false || strpos($headerText, '보험료') !== false) {
                        // 결정보험료인 경우 그대로 사용
                        $employmentInsurance = intval($amount);
                    } else {
                        // 월평균보수나 월보수총액인 경우 0.9% 계산
                        $employmentInsurance = round(intval($amount) * 0.009);
                    }
                    
                    $statement->update([
                        'employment_insurance' => $employmentInsurance
                    ]);
                    
                    $statement->calculateNetPayment();
                    $statement->save();
                    
                    
                    $updateCount++;
                    Log::info('데이터 업데이트 성공', ['name' => $name, 'employment_insurance' => $employmentInsurance]);
                } else {
                    Log::warning('해당 이름의 급여명세서를 찾을 수 없습니다.', ['name' => $name, 'statement_date' => $statementDate]);
                }
            }
            
            fclose($handle);
            // 임시 파일 삭제
            unlink($tempFile);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'count' => $updateCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            Log::error('고용보험 정보 업데이트 실패: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 급여명세서 데이터 검색
     */
    public function search(Request $request)
    {
        try {
            $query = SalaryStatement::query()
                ->whereYear('statement_date', substr($request->statement_date, 0, 4))
                ->whereMonth('statement_date', substr($request->statement_date, 5, 2));

            // 지역 필터링
            if ($request->affiliation) {
                $query->where(function($q) use ($request) {
                    // salary_statements의 affiliation 검색
                    $q->where('affiliation', $request->affiliation)
                    // 또는 users와 members 테이블을 통한 affiliation 검색
                    ->orWhereHas('user.member', function($query) use ($request) {
                        $query->where('affiliation', $request->affiliation);
                    });
                });
            }

            $statements = $query->get()
                ->map(function($statement) {
                    return [
                        'id' => $statement->id,
                        'statement_date' => $statement->statement_date->format('Y-m'),
                        'name' => $statement->user ? $statement->user->name : $statement->name,
                        'position' => $statement->user ? $statement->user->member->position : $statement->position,
                        'task' => $statement->user ? $statement->user->member->task : null,
                        'affiliation' => $statement->user ? $statement->user->member->affiliation : $statement->affiliation,
                        'total_payment' => $statement->total_payment,
                        'total_deduction' => $statement->total_deduction,
                        'net_payment' => $statement->net_payment,
                        'approved_at' => $statement->approved_at ? $statement->approved_at->format('Y-m-d H:i') : null,
                        'created_at' => $statement->created_at->format('Y-m-d H:i')
                    ];
                });

            return response()->json([
                'success' => true,
                'statements' => $statements
            ]);

        } catch (\Exception $e) {
            Log::error('급여명세서 검색 실패', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => '데이터 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 선택된 급여명세서 데이터 엑셀 다운로드
     */
    public function downloadExcel(Request $request)
    {
        try {
            if (!$request->has('ids')) {
                throw new \Exception('선택된 데이터가 없습니다.');
            }

            $ids = explode(',', $request->ids);
            $statements = SalaryStatement::whereIn('id', $ids)->get();

            // bankcode.csv 파일 읽기
            $bankCodes = [];
            $handle = fopen(resource_path('views/admin/salary-statements/bankcode.csv'), 'r');
            while (($data = fgetcsv($handle)) !== false) {
                $bankCodes[$data[1]] = $data[0];
            }
            fclose($handle);

            // 새로운 Spreadsheet 객체 생성
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // 헤더 설정
            $headers = ['입금은행', '입금계좌', '고객관리성명', '입금액', '출금통장표시내용', '입금통장표시내용', '입금인코드', '비고', '업체사용key'];
            $sheet->fromArray([$headers], null, 'A1');

            // 데이터 입력
            $row = 2;
            foreach ($statements as $statement) {
                // 사용자와 연결된 member 정보 조회
                $bankCode = '';
                $accountNumber = '';
                $name = $statement->name;

                if ($statement->user) {
                    $member = $statement->user->member;
                    if ($member) {
                        $bankCode = $bankCodes[$member->bank] ?? '';
                        $accountNumber = $member->account_number;
                        $name = $statement->user->name;
                    }
                }

                $sheet->setCellValueExplicit('A' . $row, $bankCode, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('B' . $row, $accountNumber, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C' . $row, $name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('D' . $row, (string)$statement->net_payment, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('E' . $row, $name . ' 급여', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('H' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('I' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                // 모든 셀의 서식을 텍스트로 설정
                $sheet->getStyle('A'.$row.':I'.$row)->getNumberFormat()->setFormatCode('@');
                
                $row++;
            }

            // 파일명 생성
            $filename = '급여이체_' . now()->format('Ymd_His') . '.xls';

            // 엑셀 파일 생성
            $writer = new Xls($spreadsheet);
            
            // 헤더 설정
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // 출력 버퍼 초기화
            ob_end_clean();

            // 파일 출력
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('급여명세서 엑셀 다운로드 실패', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', '엑셀 파일 생성 중 오류가 발생했습니다.');
        }
    }

    /**
     * 급여명세서 PDF 생성
     */
    public function generatePdf(SalaryStatement $salaryStatement)
    {
        $salaryStatement->load(['user']);
        
        // PDF 설정 추가
        PDF::setOptions([
            'defaultFont' => 'NanumGothic',
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultPaperSize' => 'a4',
            'defaultEncoding' => 'UTF-8'
        ]);
        
        $pdf = PDF::loadView('admin.salary-statements.pdf', compact('salaryStatement'));
        
        $filename = sprintf(
            '급여명세서_%s_%s.pdf',
            $salaryStatement->statement_date->format('Y-m'),
            $salaryStatement->user ? $salaryStatement->user->name : $salaryStatement->name
        );
        
        return $pdf->download($filename);
    }

    /**
     * 급여대장 CSV 다운로드
     */
    public function downloadPayroll(Request $request)
    {
        try {
            if (!$request->has('ids')) {
                throw new \Exception('선택된 데이터가 없습니다.');
            }

            $ids = explode(',', $request->ids);
            
            // 디버깅용 로그 추가
            Log::info('다운로드 요청 파라미터', [
                'affiliation' => $request->affiliation,
                'ids' => $ids
            ]);

            $statements = SalaryStatement::with(['user' => function($query) {
                    $query->with('member');
                }])
                ->whereIn('id', $ids)
                ->get();

            if ($statements->isEmpty()) {
                throw new \Exception('다운로드할 데이터가 없습니다.');
            }

            // 필터에서 선택된 소속값 사용 (디버깅용 로그 추가)
            $locationText = $request->affiliation ?? '전체';
            Log::info('파일명 생성', [
                'locationText' => $locationText,
                'raw_affiliation' => $request->affiliation
            ]);
            
            // CSV 파일명 생성
            $filename = sprintf(
                '급여대장_%s_%s.csv',
                $locationText,
                $statements->first()->statement_date->format('Ym')
            );

            // CSV 헤더 정의
            $headers = [
                '순번', '이름', '직급', '업무', '소속', '기본급', '식대', '차량유지비', 
                '보육수당', '상여금', '성과급', '연차수당', '조정수당', '세전총급여',
                '소득세', '지방소득세', '국민연금', '건강보험', '장기요양', '고용보험',
                '취업 후 학자금 상환액', '기타공제액', '연말정산소득세', '연말정산지방소득세', '건강보험료정산',
                '장기요양보험정산', '중도정산소득세', '중도정산지방소득세', '농특세',
                '공제총액', '실지급액'
            ];

            // CSV 파일 생성
            $callback = function() use ($statements, $headers) {
                $file = fopen('php://output', 'w');
                
                // BOM 추가 (UTF-8 인코딩 명시)
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // 헤더 작성
                fputcsv($file, $headers);
                
                // 데이터 작성
                foreach ($statements as $index => $statement) {
                    $user = $statement->user;
                    $member = $user ? $user->member : null;
                    
                    $row = [
                        $index + 1, // 순번
                        $user ? $user->name : $statement->name,
                        $member ? $member->position : $statement->position,
                        $member ? $member->task : '-',
                        $member ? $member->affiliation : $statement->affiliation,
                        $statement->base_salary,
                        $statement->meal_allowance,
                        $statement->vehicle_allowance,
                        $statement->child_allowance,
                        $statement->bonus,
                        $statement->performance_pay,
                        $statement->vacation_pay,
                        $statement->adjustment_pay,
                        $statement->total_payment,
                        $statement->income_tax,
                        $statement->local_income_tax,
                        $statement->national_pension,
                        $statement->health_insurance,
                        $statement->long_term_care,
                        $statement->employment_insurance,
                        $statement->student_loan_repayment,
                        $statement->other_deductions,
                        $statement->year_end_tax,
                        $statement->year_end_local_tax,
                        $statement->health_insurance_adjustment,
                        $statement->long_term_adjustment,
                        $statement->interim_tax,
                        $statement->interim_local_tax,
                        $statement->agriculture_tax,
                        $statement->total_deduction,
                        $statement->net_payment
                    ];
                    
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache'
            ]);

        } catch (\Exception $e) {
            Log::error('급여대장 CSV 다운로드 실패', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', 'CSV 파일 생성 중 오류가 발생했습니다.');
        }
    }

    /**
     * 퇴직급여 엑셀 다운로드
     */
    public function downloadPension(Request $request)
    {
        try {
            if (!$request->has('ids')) {
                throw new \Exception('선택된 데이터가 없습니다.');
            }

            $ids = explode(',', $request->ids);
            
            // 디버깅용 로그 추가
            Log::info('퇴직급여 다운로드 요청 파라미터', [
                'affiliation' => $request->affiliation,
                'ids' => $ids
            ]);

            $statements = SalaryStatement::with(['user' => function($query) {
                    $query->with('member');
                }])
                ->whereIn('id', $ids)
                ->get();

            if ($statements->isEmpty()) {
                throw new \Exception('다운로드할 데이터가 없습니다.');
            }

            // 필터에서 선택된 소속값 사용
            $locationText = $request->affiliation ?? '전체';
            
            // 귀속년월 가져오기
            $statementDate = $statements->first()->statement_date;
            $yearMonth = $statementDate->format('Ym');
            
            // 납입시작일(해당 월의 1일)과 납입종료일(해당 월의 말일) 계산
            $startDate = $statementDate->format('Y-m-01');
            $endDate = $statementDate->format('Y-m-t');

            // 디버깅용 로그 추가
            Log::info('날짜 계산', [
                'statementDate' => $statementDate->format('Y-m-d'),
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);

            // 1. 먼저 중간 데이터를 생성
            $tempData = [];
            
            // private_number.xlsx 파일 읽기
            $privateNumberPath = resource_path('views/admin/salary-statements/private_number.xlsx');
            $privateNumbers = [];
            
            if (file_exists($privateNumberPath)) {
                $privateNumberReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $privateNumberSpreadsheet = $privateNumberReader->load($privateNumberPath);
                $privateNumberSheet = $privateNumberSpreadsheet->getActiveSheet();
                
                $highestRow = $privateNumberSheet->getHighestRow();
                
                for ($row = 1; $row <= $highestRow; $row++) {
                    $name = $privateNumberSheet->getCell('B' . $row)->getValue();
                    $number = $privateNumberSheet->getCell('C' . $row)->getValue();
                    if ($name && $number) {
                        $privateNumbers[$name] = $number;
                    }
                }
            }
            
            foreach ($statements as $index => $statement) {
                $name = $statement->user ? $statement->user->name : $statement->name;
                
                // 산출기초임금 계산 (기본급 + 식대 + 차량유지비 + 보육수당)
                $baseAmount = $statement->base_salary + 
                              $statement->meal_allowance + 
                              $statement->vehicle_allowance + 
                              $statement->child_allowance;
                
                // 정기부담금 계산 (산출기초임금의 1/12, 원단위 절사)
                $regularContribution = floor($baseAmount / 12);
                
                // 날짜를 DateTime 객체로 생성
                $startDateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($startDate));
                $endDateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($endDate));
                
                $tempData[] = [
                    'name' => $name,
                    'private_number' => $privateNumbers[$name] ?? '',
                    'base_amount' => $baseAmount,
                    'regular_contribution' => $regularContribution,
                    'start_date' => $startDateTime,
                    'end_date' => $endDateTime
                ];
            }
            
            // 2. pension.xls 파일을 읽어서 데이터 입력
            $pensionTemplatePath = resource_path('views/admin/salary-statements/pension.xls');
            
            if (!file_exists($pensionTemplatePath)) {
                throw new \Exception('pension.xls 템플릿 파일을 찾을 수 없습니다.');
            }
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
            $spreadsheet = $reader->load($pensionTemplatePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // 가입자 수 입력 (G3 셀)
            $sheet->setCellValue('G3', count($tempData));
            
            // 현재 날짜 입력 (K3 셀)
            $currentDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(now());
            $sheet->setCellValue('K3', $currentDate);
            $sheet->getStyle('K3')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            
            // 지역에 따른 C3 셀 값 설정
            $c3Value = '21800101000000'; // 기본값 (서울 또는 전체)
            if ($locationText === '대전') {
                $c3Value = '21800102000000';
            } elseif ($locationText === '부산') {
                $c3Value = '21800103000000';
            }
            $sheet->setCellValue('C3', $c3Value);
            
            // 법인명 입력 (C4 셀)
            $sheet->setCellValue('C4', '법무법인 로앤 ' . $locationText);
            
            // 데이터 입력 (7행부터)
            $row = 7;
            foreach ($tempData as $index => $data) {
                // A열: 순번
                $sheet->setCellValue('A' . $row, $index + 1);
                
                // B열: 가입자명
                $sheet->setCellValue('B' . $row, $data['name']);
                
                // C열: 주민등록번호
                $sheet->setCellValue('C' . $row, $data['private_number']);
                
                // D열: 산출기초임금
                $sheet->setCellValue('D' . $row, $data['base_amount']);
                
                // E열: 정기부담금
                $sheet->setCellValue('E' . $row, $data['regular_contribution']);
                
                // M열: 납입시작일
                $sheet->setCellValue('M' . $row, $data['start_date']);
                
                // N열: 납입종료일
                $sheet->setCellValue('N' . $row, $data['end_date']);
                
                $row++;
            }
            
            // 날짜 셀 서식 지정 (M열과 N열)
            $dateStyle = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD;
            $lastRow = $row - 1;
            $sheet->getStyle('M7:M' . $lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            $sheet->getStyle('N7:N' . $lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            
            // 파일명 생성
            $filename = sprintf(
                '퇴직급여부담금_%s_%s.xls',
                $locationText,
                $yearMonth
            );
            
            // 엑셀 파일 생성
            $writer = new Xls($spreadsheet);
            
            // 헤더 설정
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // 출력 버퍼 초기화
            ob_end_clean();
            
            // 파일 출력
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('퇴직급여 엑셀 다운로드 실패', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', '엑셀 파일 생성 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * Process checklist for salary management
     */
    public function getProcessChecklist()
    {
        try {
            $filePath = resource_path('views/admin/salary-statements/salary_process.txt');
            
            if (!file_exists($filePath)) {
                throw new \Exception('체크리스트 파일을 찾을 수 없습니다.');
            }
            
            $checklistItems = array_filter(
                array_map('trim', file($filePath)),
                function($line) {
                    return !empty($line);
                }
            );
            
            return response()->json([
                'success' => true,
                'items' => $checklistItems
            ]);
        } catch (\Exception $e) {
            Log::error('급여 처리 체크리스트 조회 실패: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '체크리스트를 불러오는데 실패했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 고용보험 재계산 처리
     */
    public function recalculateEmployment(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // 선택된 급여명세서 ID들
            $statementIds = $request->input('ids', []);
            
            if (empty($statementIds)) {
                return response()->json([
                    'success' => false,
                    'message' => '선택된 급여명세서가 없습니다.'
                ]);
            }
            
            // 선택된 급여명세서 조회
            $statements = SalaryStatement::whereIn('id', $statementIds)->get();
            
            $updateCount = 0;
            $details = [];
            
            foreach ($statements as $statement) {
                // 과세대상 금액 계산 (비과세 항목 제외)
                $taxableAmount = $statement->base_salary +
                               ($statement->performance_pay ?? 0) +
                               ($statement->adjustment_pay ?? 0) +
                               ($statement->vacation_pay ?? 0);
                
                // 사용자 정보 조회
                $user = $statement->user;
                $userName = $user ? $user->name : $statement->name;
                
                // 이전 고용보험 값 저장
                $previousInsurance = $statement->employment_insurance;
                
                // 대표자(김충환)인 경우 고용보험 0으로 설정
                if ($userName === '김충환') {
                    $employmentInsurance = 0;
                } else {
                    // 과세대상 금액의 0.9%를 정수 연산으로 정확히 계산
                    // 0.009는 소수점 오차가 있을 수 있으므로 정수 연산으로 우회
                    $employmentInsurance = (int)(($taxableAmount * 9) / 1000);
                    // 정확히 10원 단위로 내림 처리
                    $employmentInsurance = (int)($employmentInsurance / 10) * 10;
                    
                    // 로그 추가 - 디버깅용
                    \Log::debug("고용보험 계산: 과세대상금액={$taxableAmount}, 결과={$employmentInsurance}");
                }
                
                // 고용보험 업데이트
                $statement->employment_insurance = $employmentInsurance;
                
                // 총공제액 및 실지급액 재계산
                $statement->calculateNetPayment();
                $statement->save();
                
                $updateCount++;
                
                // 상세 내역 추가
                $details[] = [
                    'name' => $userName,
                    'previous' => $previousInsurance,
                    'current' => $employmentInsurance,
                    'difference' => $employmentInsurance - $previousInsurance
                ];
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "{$updateCount}개의 고용보험이 재계산되었습니다.",
                'count' => $updateCount,
                'details' => $details
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('고용보험 재계산 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '고용보험 재계산 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 성과금을 급여명세서에 적용
     */
    public function applyPerformanceBonus(Request $request)
    {
        try {
            // 요청 데이터 검증
            $validated = $request->validate([
                'statement_date' => 'required|date_format:Y-m',
                'bonuses' => 'required|array',
                'bonuses.*.name' => 'required|string',
                'bonuses.*.bonus' => 'required|numeric'
            ]);

            $statementDate = $validated['statement_date'];
            $bonuses = $validated['bonuses'];
            
            // 결과 저장을 위한 배열
            $results = [
                'success' => true,
                'total_count' => 0,
                'total_amount' => 0,
                'details' => [],
                'errors' => []
            ];
            
            // 트랜잭션 시작
            DB::beginTransaction();
            
            foreach ($bonuses as $bonus) {
                $name = $bonus['name'];
                $amount = $bonus['bonus'];
                
                try {
                    // 사용자 찾기
                    $user = User::where('name', $name)->first();
                    
                    if (!$user) {
                        $results['errors'][] = "{$name}님의 사용자 정보를 찾을 수 없습니다.";
                        continue;
                    }
                    
                    // 해당 월의 급여명세서 찾기
                    $statements = SalaryStatement::where('user_id', $user->id)
                        ->where(DB::raw("DATE_FORMAT(statement_date, '%Y-%m')"), $statementDate)
                        ->get();
                    
                    // 급여명세서가 없는 경우
                    if ($statements->isEmpty()) {
                        $results['errors'][] = "{$name}님의 {$statementDate} 귀속년월 급여명세서가 없습니다.";
                        continue;
                    }
                    
                    // 급여명세서가 여러 개인 경우
                    if ($statements->count() > 1) {
                        $results['errors'][] = "{$name}님의 {$statementDate} 귀속년월 급여명세서가 중복되어 있습니다.";
                        continue;
                    }
                    
                    // 급여명세서 업데이트
                    $statement = $statements->first();
                    $statement->performance_pay = $amount;
                    
                    // 총지급액 및 실지급액 재계산
                    $statement->calculateTotalPayment();
                    $statement->calculateNetPayment();
                    $statement->save();
                    
                    // 결과 추가
                    $results['total_count']++;
                    $results['total_amount'] += $amount;
                    $results['details'][] = [
                        'name' => $name,
                        'amount' => $amount
                    ];
                } catch (\Exception $e) {
                    // 각 직원별 처리 중 오류 발생시 로깅하고 다음으로 진행
                    Log::error("성과금 적용 중 오류 ({$name}): " . $e->getMessage());
                    $results['errors'][] = "{$name}님의 성과금 적용 중 오류가 발생했습니다: " . $e->getMessage();
                }
            }
            
            // 오류가 있는지 확인
            if (empty($results['errors'])) {
                DB::commit();
            } else {
                DB::rollBack();
                $results['success'] = false;
            }
            
            return response()->json($results);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('성과금 적용 중 오류: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '성과금 적용 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 소득세와 지방소득세 재계산 처리
     */
    public function recalculateIncomeTax(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // 선택된 급여명세서 ID들
            $statementIds = $request->input('ids', []);
            
            if (empty($statementIds)) {
                return response()->json([
                    'success' => false,
                    'message' => '선택된 급여명세서가 없습니다.'
                ]);
            }
            
            // 선택된 급여명세서 조회
            $statements = SalaryStatement::whereIn('id', $statementIds)->get();
            
            $updateCount = 0;
            $details = [];
            
            foreach ($statements as $statement) {
                // 과세대상 금액 계산 (비과세 항목 제외)
                $stmtData = [
                    'base_salary' => $statement->base_salary,
                    'performance_pay' => $statement->performance_pay,
                    'adjustment_pay' => $statement->adjustment_pay,
                    'vacation_pay' => $statement->vacation_pay
                ];
                
                // 사용자 정보 조회
                $user = $statement->user;
                $userName = $user ? $user->name : $statement->name;
                
                // 이전 소득세 및 지방소득세 값 저장
                $previousIncomeTax = $statement->income_tax;
                $previousLocalIncomeTax = $statement->local_income_tax;
                
                // 소득세 재계산
                $incomeTax = $this->calculateMonthlyIncomeTax($stmtData);
                
                // 지방소득세 계산 (소득세의 10%, 원단위 절사)
                $localIncomeTax = floor(($incomeTax * 0.1) / 10) * 10;
                
                // 소득세 및 지방소득세 업데이트
                $statement->income_tax = $incomeTax;
                $statement->local_income_tax = $localIncomeTax;
                
                // 총공제액 및 실지급액 재계산
                $statement->calculateNetPayment();
                $statement->save();
                
                $updateCount++;
                
                // 상세 내역 추가
                $details[] = [
                    'name' => $userName,
                    'previous_income_tax' => $previousIncomeTax,
                    'current_income_tax' => $incomeTax,
                    'income_tax_difference' => $incomeTax - $previousIncomeTax,
                    'previous_local_income_tax' => $previousLocalIncomeTax,
                    'current_local_income_tax' => $localIncomeTax,
                    'local_income_tax_difference' => $localIncomeTax - $previousLocalIncomeTax
                ];
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "{$updateCount}개의 소득세 및 지방소득세가 재계산되었습니다.",
                'count' => $updateCount,
                'details' => $details
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('소득세 재계산 실패: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => '소득세 재계산 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 사대보험 정보를 급여명세서에 동기화
     */
    public function syncSocialInsurance(Request $request)
    {
        try {
            // 요청 데이터 검증
            $validated = $request->validate([
                'statement_ids' => 'required|array',
                'insurance_types' => 'required|array',
                'statement_date' => 'required|date_format:Y-m'
            ]);

            $statementIds = $validated['statement_ids'];
            $insuranceTypes = $validated['insurance_types'];
            $statementDate = $validated['statement_date'];

            // 선택된 급여명세서 가져오기
            $statements = SalaryStatement::with('user')
                ->whereIn('id', $statementIds)
                ->get();

            $updatedCount = 0;

            // 각 급여명세서에 대해
            foreach ($statements as $statement) {
                $socialInsurance = null;
                
                // 사용자 ID가 있는 경우
                if ($statement->user) {
                    // 사용자의 이름과 귀속년월로 사대보험 데이터 조회
                    $socialInsurance = DB::table('social_insurance')
                        ->where('name', $statement->user->name)
                        ->whereYear('statement_date', substr($statementDate, 0, 4))
                        ->whereMonth('statement_date', substr($statementDate, 5, 2))
                        ->first();
                } else {
                    // 사용자 ID가 없는 경우, 급여명세서의 이름으로 사대보험 데이터 조회
                    $socialInsurance = DB::table('social_insurance')
                        ->where('name', $statement->name)
                        ->whereYear('statement_date', substr($statementDate, 0, 4))
                        ->whereMonth('statement_date', substr($statementDate, 5, 2))
                        ->first();
                }

                if (!$socialInsurance) {
                    continue; // 해당 사용자의 사대보험 데이터가 없는 경우 스킵
                }

                // 업데이트할 필드 준비
                $updateData = [];

                // 선택된 보험 유형에 따라 데이터 복사
                foreach ($insuranceTypes as $type) {
                    if (property_exists($socialInsurance, $type) && !is_null($socialInsurance->$type)) {
                        $updateData[$type] = $socialInsurance->$type;
                    }
                }

                // 데이터가 있는 경우만 업데이트
                if (!empty($updateData)) {
                    // 총 공제액 및 실지급액 재계산
                    $statement->update($updateData);
                    
                    // 총 공제액 계산
                    $totalDeduction = 
                        ($statement->income_tax ?? 0) +
                        ($statement->local_income_tax ?? 0) +
                        ($statement->national_pension ?? 0) +
                        ($statement->health_insurance ?? 0) +
                        ($statement->long_term_care ?? 0) +
                        ($statement->employment_insurance ?? 0) +
                        ($statement->other_deductions ?? 0) +
                        ($statement->year_end_tax ?? 0) +
                        ($statement->year_end_local_tax ?? 0) +
                        ($statement->health_insurance_adjustment ?? 0) +
                        ($statement->long_term_adjustment ?? 0) +
                        ($statement->interim_tax ?? 0) +
                        ($statement->interim_local_tax ?? 0);

                    // 실지급액 계산
                    $netPayment = ($statement->total_payment ?? 0) - $totalDeduction;

                    // 공제액과 실지급액 업데이트
                    $statement->update([
                        'total_deduction' => $totalDeduction,
                        'net_payment' => $netPayment
                    ]);

                    $updatedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'updated_count' => $updatedCount,
                'message' => "선택한 급여명세서에 대해 사대보험 정보가 성공적으로 동기화되었습니다."
            ]);
        } catch (\Exception $e) {
            Log::error('사대보험 동기화 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '사대보험 동기화 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
