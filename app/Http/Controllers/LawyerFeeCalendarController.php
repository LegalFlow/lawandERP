<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LawyerFeeDetail;
use App\Models\LawyerFee;
use App\Models\Target;
use App\Helpers\CaseStateHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LawyerFeeCalendarController extends Controller
{
    /**
     * 수임료 캘린더 메인 페이지
     *
     * @return \Illuminate\Contracts\View\View
     * @route GET lawyer-fee-calendar/index
     */
    public function index()
    {
        return view('fee_calendar.index');
    }

    /**
     * 월별 수임료 데이터 조회
     */
    public function getMonthlyData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
        
        // 필터 파라미터 추출
        $filters = [
            'consultant' => $request->input('consultant'),
            'manager' => $request->input('manager'),
            'client_name' => $request->input('client_name')
        ];
        
        return $this->getFeeDataByDateRange($startDate, $endDate, array_filter($filters));
    }

    /**
     * 주별 수임료 데이터 조회
     */
    public function getWeeklyData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if (!$startDate || !$endDate) {
            $today = Carbon::today();
            $startDate = $today->copy()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $endDate = $today->copy()->endOfWeek(Carbon::SATURDAY)->format('Y-m-d');
        }
        
        \Log::info('Weekly Data Request', [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        // 필터 파라미터 추출
        $filters = [
            'consultant' => $request->input('consultant'),
            'manager' => $request->input('manager'),
            'client_name' => $request->input('client_name')
        ];
        
        $result = $this->getFeeDataByDateRange($startDate, $endDate, array_filter($filters));
        
        \Log::info('Weekly Data Response', [
            'result' => $result
        ]);
        
        return $result;
    }

    /**
     * 일별 수임료 데이터 조회
     */
    public function getDailyData(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        
        // 필터 파라미터 추출
        $filters = [
            'consultant' => $request->input('consultant'),
            'manager' => $request->input('manager'),
            'client_name' => $request->input('client_name')
        ];
        
        return $this->getFeeDataByDateRange($date, $date, array_filter($filters));
    }

    /**
     * 날짜 범위에 따른 수임료 데이터 조회 및 처리
     */
    private function getFeeDataByDateRange($startDate, $endDate, $filters = [])
    {
        \Log::info('Getting fee data for date range', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'filters' => $filters
        ]);
        
        try {
            // 쿼리 빌더 생성 - target_table과 inner join을 사용하여 우리 회사 사건만 표시
            $query = LawyerFeeDetail::select('TblLawyerFeeDetail.*', 'target_table.name as client_name', 'target_table.case_type')
                ->join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) >= ?", [$startDate])
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) <= ?", [$endDate])
                // contract_date가 null이 아닌 데이터만 표시
                ->whereNotNull('target_table.contract_date')
                // del_flag가 0인 데이터만 표시
                ->where('target_table.del_flag', 0)
                // lawyer_fee 값이 0을 초과하는 데이터만 표시
                ->where('target_table.lawyer_fee', '>', 0)
                // case_state 값이 5, 10, 11인 데이터는 제외
                ->whereNotIn('target_table.case_state', [5, 10, 11]);
            
            // 필터 추가
            if (!empty($filters)) {
                // 상담자 필터
                if (!empty($filters['consultant'])) {
                    $query->where('target_table.Member', $filters['consultant']);
                }
                
                // 담당자 필터 - case_assignments 테이블과 조인하여 필터링
                if (!empty($filters['manager'])) {
                    $query->leftJoin('case_assignments', 'TblLawyerFeeDetail.case_idx', '=', 'case_assignments.case_idx')
                          ->where('case_assignments.case_manager', $filters['manager']);
                }
                
                // 고객명 필터
                if (!empty($filters['client_name'])) {
                    $query->where('target_table.name', 'like', '%' . $filters['client_name'] . '%');
                }
            }
            
            // 쿼리 로깅
            \Log::info('Query', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // 쿼리 실행
            $feeDetails = $query->get();
                
            \Log::info('Found fee details', [
                'count' => $feeDetails->count()
            ]);
            
            $result = [];
            $dailyCount = [];
            
            // 수정: 수임료와 송달료 등 부대비용 구분하는 통계 구조
            $statistics = [
                'total' => [
                    'count' => 0, 
                    'amount' => 0,
                    'lawyer_fee' => ['count' => 0, 'amount' => 0],  // 수임료 (-1, 1, 2, 3)
                    'other_fee' => ['count' => 0, 'amount' => 0]    // 송달료 등 부대비용 (0)
                ],
                'completed' => [
                    'count' => 0, 
                    'amount' => 0,
                    'lawyer_fee' => ['count' => 0, 'amount' => 0],
                    'other_fee' => ['count' => 0, 'amount' => 0]
                ],
                'pending' => [
                    'count' => 0, 
                    'amount' => 0,
                    'lawyer_fee' => ['count' => 0, 'amount' => 0],
                    'other_fee' => ['count' => 0, 'amount' => 0]
                ],
                'overdue' => [
                    'count' => 0, 
                    'amount' => 0,
                    'lawyer_fee' => ['count' => 0, 'amount' => 0],
                    'other_fee' => ['count' => 0, 'amount' => 0]
                ]
            ];
            
            foreach ($feeDetails as $detail) {
                try {
                    $detailData = is_array($detail->detail) ? $detail->detail : (is_string($detail->detail) ? json_decode($detail->detail, true) : []);
                    if (!is_array($detailData)) {
                        \Log::warning('Invalid detail data', [
                            'detail_id' => $detail->idx,
                            'detail' => $detail->detail
                        ]);
                        continue;
                    }
                    
                    $scheduled_date = $detailData['scheduled_date'] ?? null;
                    if (!$scheduled_date) {
                        \Log::warning('Missing scheduled_date', [
                            'detail_id' => $detail->idx
                        ]);
                        continue;
                    }
                    
                    // 일별 카운트 증가
                    if (!isset($dailyCount[$scheduled_date])) {
                        $dailyCount[$scheduled_date] = 0;
                    }
                    $dailyCount[$scheduled_date]++;
                    
                    // fee_type에 따른 구분
                    $feeType = isset($detailData['fee_type']) ? (int)$detailData['fee_type'] : -1;
                    $isFeeType = $feeType === -1 || $feeType === 1 || $feeType === 2 || $feeType === 3;
                    $feeCategory = $isFeeType ? 'lawyer_fee' : 'other_fee';
                    
                    // 금액
                    $amount = $detailData['money'] ?? 0;
                    
                    // 통계 데이터 업데이트 - 전체
                    $statistics['total']['count']++;
                    $statistics['total']['amount'] += $amount;
                    $statistics['total'][$feeCategory]['count']++;
                    $statistics['total'][$feeCategory]['amount'] += $amount;
                    
                    // 상태에 따른 통계 처리
                    $state = $detailData['state'] ?? 0;
                    if ($state === 1 || $state === '1' || $state === 'completed') {
                        // 완납
                        $statistics['completed']['count']++;
                        $statistics['completed']['amount'] += $amount;
                        $statistics['completed'][$feeCategory]['count']++;
                        $statistics['completed'][$feeCategory]['amount'] += $amount;
                    } else {
                        // 미납
                        $statistics['pending']['count']++;
                        $statistics['pending']['amount'] += $amount;
                        $statistics['pending'][$feeCategory]['count']++;
                        $statistics['pending'][$feeCategory]['amount'] += $amount;
                        
                        // 연체 확인
                        if (strtotime($scheduled_date) < strtotime('today')) {
                            $statistics['overdue']['count']++;
                            $statistics['overdue']['amount'] += $amount;
                            $statistics['overdue'][$feeCategory]['count']++;
                            $statistics['overdue'][$feeCategory]['amount'] += $amount;
                        }
                    }
                    
                    // 결과 배열에 추가
                    if (!isset($result[$scheduled_date])) {
                        $result[$scheduled_date] = [];
                    }
                    
                    $result[$scheduled_date][] = [
                        'id' => $detail->idx,
                        'case_idx' => $detail->case_idx,
                        'scheduled_date' => $scheduled_date,
                        'amount' => $amount,
                        'state' => $state,
                        'settlement_date' => $detailData['settlement_date'] ?? null,
                        'memo' => $detailData['memo'] ?? null,
                        'fee_type' => $feeType
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing fee detail', [
                        'detail_id' => $detail->idx,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }
            
            return [
                'daily_count' => $dailyCount,
                'fee_details' => $result,
                'statistics' => $statistics
            ];
        } catch (\Exception $e) {
            \Log::error('Error in getFeeDataByDateRange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => '데이터를 불러오는 중 오류가 발생했습니다.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 날짜별 상세 정보 조회
     */
    public function getDailyDetails(Request $request)
    {
        $date = $request->input('date');
        
        if (!$date) {
            return response()->json(['error' => '날짜가 지정되지 않았습니다.'], 400);
        }
        
        try {
            \Log::info('Getting daily details for date', ['date' => $date]);
            
            // target_table과 inner join을 사용하여 우리 회사 사건만 표시
            $query = LawyerFeeDetail::select(
                    'TblLawyerFeeDetail.*', 
                    'target_table.name as client_name',
                    'target_table.phone as client_phone',
                    'target_table.case_type',
                    'target_table.Member as consultant', 
                    'target_table.case_state'
                )
                ->join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) = ?", [$date])
                // contract_date가 null이 아닌 데이터만 표시
                ->whereNotNull('target_table.contract_date')
                // del_flag가 0인 데이터만 표시
                ->where('target_table.del_flag', 0)
                // lawyer_fee 값이 0을 초과하는 데이터만 표시
                ->where('target_table.lawyer_fee', '>', 0)
                // case_state 값이 5, 10, 11인 데이터는 제외
                ->whereNotIn('target_table.case_state', [5, 10, 11]);
            
            // 필터 적용
            // 상담자 필터
            if ($request->has('consultant') && !empty($request->consultant)) {
                $query->where('target_table.Member', $request->consultant);
            }
            
            // 담당자 필터
            if ($request->has('manager') && !empty($request->manager)) {
                $query->leftJoin('case_assignments', 'TblLawyerFeeDetail.case_idx', '=', 'case_assignments.case_idx')
                     ->where('case_assignments.case_manager', $request->manager);
            }
            
            // 고객명 필터
            if ($request->has('client_name') && !empty($request->client_name)) {
                $query->where('target_table.name', 'like', '%' . $request->client_name . '%');
            }
            
            \Log::info('Query SQL', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            $feeDetails = $query->get();
            \Log::info('Found fee details', ['count' => $feeDetails->count()]);
            
            $result = [];
            foreach ($feeDetails as $detail) {
                $detailData = is_array($detail->detail) ? $detail->detail : (is_string($detail->detail) ? json_decode($detail->detail, true) : []);
                if (!is_array($detailData)) {
                    \Log::warning('Invalid detail data', ['detail_id' => $detail->idx]);
                    continue;
                }
                
                // 기본 데이터 구성
                $baseData = [
                    'id' => $detail->idx,
                    'case_idx' => $detail->case_idx,
                    'client_name' => $detail->client_name ? ($detail->client_name === '미지정' ? '' : $detail->client_name) : '',
                    'client_phone' => $detail->client_phone ?: '-',
                    'case_type' => '미지정',
                    'fee_type' => '미지정',
                    'amount' => $detailData['money'] ?? 0,
                    'consultant' => $detail->consultant ?: '',
                    'case_manager' => '',
                    'state' => ($detailData['state'] == 1 || $detailData['state'] == '1') ? '완납' : '미납',
                    'settlement_date' => $detailData['settlement_date'] ?? null,
                    'scheduled_date' => $detailData['scheduled_date'] ?? null,
                    'case_state' => isset($detail->case_state) ? CaseStateHelper::getStateLabel($detail->case_type, $detail->case_state) : '미지정',
                    'case_state_value' => $detail->case_state ?? 0,
                    'case_state_class' => $this->getCaseStateClass($detail->case_type, $detail->case_state),
                    'contract_status' => '정상'
                ];
                
                // 사건분야 처리
                if (isset($detail->case_type)) {
                    switch ($detail->case_type) {
                        case 1:
                            $baseData['case_type'] = '개인회생';
                            break;
                        case 2:
                            $baseData['case_type'] = '개인파산';
                            break;
                        case 3:
                            $baseData['case_type'] = '기타사건';
                            break;
                    }
                }
                
                // 사건 담당자 정보 조회
                try {
                    $assignment = DB::table('case_assignments')
                        ->where('case_idx', $detail->case_idx)
                        ->first();
                    
                    if ($assignment && $assignment->case_manager) {
                        $baseData['case_manager'] = $assignment->case_manager;
                    }
                } catch (\Exception $e) {
                    \Log::error('Error retrieving case assignment info', [
                        'case_idx' => $detail->case_idx,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // 납부회차 처리
                $feeTypeText = '';
                $feeType = $detailData['fee_type'] ?? -1;
                switch ($feeType) {
                    case -1:
                        $feeTypeText = '미지정';
                        break;
                    case 0:
                        $feeTypeText = '송달료 등 부대비용';
                        break;
                    case 1:
                        $feeTypeText = '착수금';
                        break;
                    case 2:
                        $feeTypeText = '분할납부';
                        break;
                    case 3:
                        $feeTypeText = '성공보수';
                        break;
                    default:
                        $feeTypeText = '미지정';
                }
                
                $baseData['fee_type'] = $feeTypeText;
                
                // 분할납부 차수 계산 (fee_type이 2인 경우) - 에러 발생 가능성 있는 부분
                if ($feeType == 2) {
                    try {
                        // 같은 사건의 분할납부 항목들 조회
                        $installments = LawyerFeeDetail::select('idx', 'detail')
                            ->where('case_idx', $detail->case_idx)
                            ->get();
                        
                        // 날짜별로 정렬할 배열 준비
                        $datesArray = [];
                        foreach ($installments as $inst) {
                            try {
                                $instData = is_array($inst->detail) ? $inst->detail : (is_string($inst->detail) ? json_decode($inst->detail, true) : []);
                                if (isset($instData['fee_type']) && $instData['fee_type'] == 2 && isset($instData['scheduled_date'])) {
                                    $datesArray[$inst->idx] = $instData['scheduled_date'];
                                }
                            } catch (\Exception $e) {
                                \Log::error('Error processing installment', [
                                    'detail_id' => $inst->idx,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        
                        if (!empty($datesArray)) {
                            asort($datesArray); // 날짜 기준 오름차순 정렬
                            
                            // 현재 항목의 순위 확인
                            $currentOrder = 0;
                            $idx = 1;
                            foreach (array_keys($datesArray) as $id) {
                                if ($id == $detail->idx) {
                                    $currentOrder = $idx;
                                    break;
                                }
                                $idx++;
                            }
                            
                            $totalInstallments = count($datesArray);
                            if ($currentOrder > 0 && $totalInstallments > 0) {
                                $feeTypeText = $currentOrder . "차 분할납부 / " . $totalInstallments . "차 분할납부";
                                $baseData['fee_type'] = $feeTypeText;
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error calculating installment order', [
                            'detail_id' => $detail->idx,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                
                // 완납된 항목의 경우 결제 정보 조회 (payment_method만 추가)
                if ($baseData['state'] === '완납') {
                    $subDetail = DB::table('Sub_LawyerFeeDetail')
                        ->where('idx', $detail->idx)
                        ->first();
                    
                    if ($subDetail) {
                        // 결제 방식 표시
                        $paymentTypes = [
                            'transactions' => '서울계좌입금',
                            'transactions2' => '대전계좌입금',
                            'transactions3' => '부산계좌입금',
                            'payments' => 'CMS입금',
                            'income_entries' => '매출직접입력'
                        ];
                        
                        $baseData['payment_method'] = isset($paymentTypes[$subDetail->payment_type]) 
                            ? $paymentTypes[$subDetail->payment_type] 
                            : $subDetail->payment_type;
                    }
                }
                
                $result[] = $baseData;
            }
            
            // 반환 형식을 fee_details 키를 가진 객체로 변경
            return response()->json(['fee_details' => $result]);
        } catch (\Exception $e) {
            \Log::error('Error getting daily details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => '상세 정보를 불러오는 중 오류가 발생했습니다.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * RDS 데이터베이스의 TblLawyerFeeDetail 테이블 동기화
     */
    private function syncToRDS($id, $detail)
    {
        // 개발 환경에서는 RDS 동기화를 건너뛰고 항상 성공 반환
        if (env('APP_ENV') !== 'production') {
            \Log::info('개발 환경에서는 RDS 동기화를 수행하지 않습니다.', ['id' => $id]);
            return true;
        }
        
        try {
            \Log::info('RDS 동기화 시작', ['id' => $id]);
            
            // detail이 array인 경우 JSON으로 변환 (RDS의 detail 필드는 JSON 타입)
            $detailJson = is_array($detail->detail) ? json_encode($detail->detail) : $detail->detail;
            
            try {
                // RDS 연결로 쿼리 실행 - UPSERT 방식 사용
                DB::connection('rds')->statement(
                    "INSERT INTO TblLawyerFeeDetail (idx, case_idx, detail, alarm_dt) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     case_idx = VALUES(case_idx),
                     detail = VALUES(detail),
                     alarm_dt = VALUES(alarm_dt)",
                    [$id, $detail->case_idx, $detailJson, $detail->alarm_dt ?? '']
                );
                
                \Log::info('RDS 동기화 성공', ['id' => $id]);
                return true;
            } catch (\Exception $e) {
                // RDS 연결 오류 기록
                \Log::error('RDS DB 연결 오류', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                
                // 오류 발생 시 예외 발생
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('RDS 동기화 실패', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 납부 완료 처리
     */
    public function markAsCompleted($id)
    {
        try {
            // 트랜잭션 시작
            DB::beginTransaction();
            
            $detail = LawyerFeeDetail::findOrFail($id);
            
            // 항상 객체로 변환하여 작업
            if (is_string($detail->detail)) {
                $detailData = json_decode($detail->detail, true);
                // JSON 파싱 오류 체크
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // 이중 인코딩된 경우 처리
                    $detailData = json_decode(stripslashes($detail->detail), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON format in detail data');
                    }
                }
            } else if (is_array($detail->detail)) {
                $detailData = $detail->detail;
            } else {
                $detailData = [];
            }
            
            // 상태 변경 및 납부완료일 설정
            $detailData['state'] = 1;
            $detailData['settlement_date'] = date('Y-m-d');
            $detailData['settlement_money'] = $detailData['money'] ?? 0;
            
            // 데이터 업데이트 - 항상 배열 형태로 저장
            $detail->detail = $detailData;
            $detail->save();
            
            // RDS 동기화
            $syncResult = $this->syncToRDS($id, $detail);
            
            if (!$syncResult) {
                // RDS 동기화 실패 시 롤백
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'RDS 동기화 실패']);
            }
            
            // 모든 작업 성공 시 커밋
            DB::commit();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            // 오류 발생 시 롤백
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 납부완료에서 미납으로 변경
     */
    public function markAsPending($id)
    {
        try {
            // 트랜잭션 시작
            DB::beginTransaction();
            
            $detail = LawyerFeeDetail::findOrFail($id);
            
            // 항상 객체로 변환하여 작업
            if (is_string($detail->detail)) {
                $detailData = json_decode($detail->detail, true);
                // JSON 파싱 오류 체크
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // 이중 인코딩된 경우 처리
                    $detailData = json_decode(stripslashes($detail->detail), true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON format in detail data');
                    }
                }
            } else if (is_array($detail->detail)) {
                $detailData = $detail->detail;
            } else {
                $detailData = [];
            }
            
            // 연결된 입금내역 정보 찾기 (미납처리 전에 찾아야 함)
            $subDetail = DB::table('Sub_LawyerFeeDetail')
                ->where('idx', $id)
                ->first();
            
            if ($subDetail) {
                try {
                    // 연결된 입금내역의 계정과 담당자 정보 초기화
                    $tableName = $subDetail->payment_type;
                    $paymentId = $subDetail->payment_id;
                    
                    switch ($tableName) {
                        case 'transactions':
                            DB::table('transactions')
                                ->where('id', $paymentId)
                                ->update([
                                    'account' => null,
                                    'manager' => null
                                ]);
                            break;
                            
                        case 'transactions2':
                            DB::table('transactions2')
                                ->where('id', $paymentId)
                                ->update([
                                    'account' => null,
                                    'manager' => null
                                ]);
                            break;
                            
                        case 'transactions3':
                            DB::table('transactions3')
                                ->where('id', $paymentId)
                                ->update([
                                    'account' => null,
                                    'manager' => null
                                ]);
                            break;
                            
                        case 'payments':
                            DB::table('payments')
                                ->where('id', $paymentId)
                                ->update([
                                    'account' => null,
                                    'manager' => null
                                ]);
                            break;
                            
                        case 'income_entries':
                            DB::table('income_entries')
                                ->where('id', $paymentId)
                                ->update([
                                    'account_type' => null,
                                    'representative_id' => null
                                ]);
                            break;
                    }
                    
                    // Sub_LawyerFeeDetail에서 관련 매칭 정보 삭제
                    DB::table('Sub_LawyerFeeDetail')
                        ->where('idx', $id)
                        ->delete();
                    
                    // 상태를 미납으로 변경하고 납부완료일 제거
                    $detailData['state'] = 0;
                    
                    // 납부완료일 정보가 있으면 제거
                    if (isset($detailData['settlement_date'])) {
                        unset($detailData['settlement_date']);
                    }
                    
                    // 납부금액 정보가 있으면 제거
                    if (isset($detailData['settlement_money'])) {
                        unset($detailData['settlement_money']);
                    }
                    
                    // 데이터 업데이트 - 항상 배열 형태로 저장
                    $detail->detail = $detailData;
                    $detail->save();
                    
                    // RDS 동기화
                    $syncResult = $this->syncToRDS($id, $detail);
                    
                    if (!$syncResult) {
                        // RDS 동기화 실패 시 롤백
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => 'RDS 동기화 실패']);
                    }
                    
                    // 변경사항 커밋
                    DB::commit();
                    
                    return response()->json(['success' => true]);
                } catch (\Exception $e) {
                    // 오류 발생 시 롤백
                    DB::rollBack();
                    throw $e;
                }
            } else {
                // 연결된 입금내역이 없는 경우, 단순히 상태만 변경
                
                // 상태를 미납으로 변경하고 납부완료일 제거
                $detailData['state'] = 0;
                
                // 납부완료일 정보가 있으면 제거
                if (isset($detailData['settlement_date'])) {
                    unset($detailData['settlement_date']);
                }
                
                // 납부금액 정보가 있으면 제거
                if (isset($detailData['settlement_money'])) {
                    unset($detailData['settlement_money']);
                }
                
                // 데이터 업데이트 - 항상 배열 형태로 저장
                $detail->detail = $detailData;
                $detail->save();
                
                // RDS 동기화
                $syncResult = $this->syncToRDS($id, $detail);
                
                if (!$syncResult) {
                    // RDS 동기화 실패 시 롤백
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'RDS 동기화 실패']);
                }
                
                // 변경사항 커밋
                DB::commit();
                
                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            // 오류 발생 시 롤백
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 선택한 입금내역으로 납부 완료 처리
     */
    public function processPaymentMatch(Request $request)
    {
        try {
            \Log::info('Payment match processing', [
                'request' => $request->all()
            ]);
            
            $feeDetailId = $request->input('fee_detail_id');
            $paymentId = $request->input('payment_id');
            $tableName = $request->input('table_name');
            $paymentType = $request->input('payment_type');
            
            if (!$feeDetailId || !$paymentId || !$tableName) {
                return response()->json(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.'], 400);
            }
            
            // 1. 수임료 상세 정보 조회
            $feeDetail = LawyerFeeDetail::join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                ->select('TblLawyerFeeDetail.*', 'target_table.Member as case_manager')
                ->where('TblLawyerFeeDetail.idx', $feeDetailId)
                ->first();
            
            if (!$feeDetail) {
                return response()->json(['success' => false, 'message' => '수임료 정보를 찾을 수 없습니다.'], 404);
            }
            
            // 2. 트랜잭션 정보 조회
            $transaction = $this->getTransactionById($tableName, $paymentId);
            if (!$transaction) {
                return response()->json(['success' => false, 'message' => '입금내역 정보를 찾을 수 없습니다.'], 404);
            }
            
            // 3. 수임료 상세 데이터 파싱 및 업데이트
            $detailData = is_array($feeDetail->detail) ? $feeDetail->detail : json_decode($feeDetail->detail, true);
            if (!is_array($detailData)) {
                $detailData = json_decode(stripslashes($feeDetail->detail), true);
                if (!is_array($detailData)) {
                    return response()->json(['success' => false, 'message' => '유효하지 않은 상세 데이터 형식입니다.'], 400);
                }
            }
            
            // 이미 완납 처리된 항목인지 확인
            if (isset($detailData['state']) && $detailData['state'] == 1) {
                return response()->json(['success' => false, 'message' => '이미 납부 완료된 항목입니다.'], 400);
            }
            
            // fee_type이 0인 경우 송인부, 1,2,3인 경우 서비스매출로 처리
            $feeType = $detailData['fee_type'] ?? 0;
            $accountType = ($feeType == 0) ? '송인부' : '서비스매출';
            
            DB::beginTransaction();
            
            try {
                // 4. 트랜잭션 정보 업데이트 (account, manager)
                $this->updateTransactionInfo($transaction, $accountType, $feeDetail->case_manager);
                
                // 5. Sub_LawyerFeeDetail에 매칭 정보 저장
                $this->createSubFeeDetail($feeDetailId, $transaction);
                
                // 6. TblLawyerFeeDetail 상태 업데이트
                $detailData['state'] = 1;
                $detailData['settlement_date'] = $transaction['date'];
                $detailData['settlement_money'] = $detailData['money'];
                $feeDetail->detail = $detailData;
                $feeDetail->save();
                
                // 7. RDS 동기화
                $syncResult = $this->syncToRDS($feeDetailId, $feeDetail);
                
                if (!$syncResult) {
                    throw new \Exception("ID {$feeDetailId}: RDS 동기화 실패");
                }
                
                DB::commit();
                
                // 결제 방식 표시용 데이터
                $paymentTypes = [
                    'transactions' => '서울계좌입금',
                    'transactions2' => '대전계좌입금',
                    'transactions3' => '부산계좌입금',
                    'payments' => 'CMS입금',
                    'income_entries' => '매출직접입력'
                ];
                
                $paymentMethod = isset($paymentTypes[$tableName]) ? $paymentTypes[$tableName] : $tableName;
                
                return response()->json([
                    'success' => true, 
                    'message' => '납부 완료 처리되었습니다.',
                    'payment_method' => $paymentMethod
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            \Log::error('Error in processPaymentMatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 날짜별 일괄 납부 완료 처리 및 자동 매칭
     */
    public function batchMarkAsCompleted(Request $request)
    {
        try {
            \Log::info('일괄 납부처리 시작', [
                'date' => $request->date, 
                'is_overdue' => $request->is_overdue ?? false,
                'filters' => [
                    'consultant' => $request->consultant,
                    'manager' => $request->manager,
                    'client_name' => $request->client_name
                ]
            ]);
            
            // 해당 날짜의 미납 수임료 항목 조회
            $query = LawyerFeeDetail::select('TblLawyerFeeDetail.*', 'target_table.name', 'target_table.Member')
                ->join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.state')) = '0'")
                ->whereNotNull('target_table.contract_date')
                ->where('target_table.del_flag', 0)
                ->where('target_table.lawyer_fee', '>', 0)
                ->whereNotIn('target_table.case_state', [5, 10, 11]);
            
            // 연체 모드인 경우 오늘 이전 날짜의 데이터 모두 조회
            if ($request->is_overdue) {
                $today = date('Y-m-d');
                $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) < ?", [$today])
                      ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) >= ?", ['2024-01-01'])
                      ->whereRaw("(CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.fee_type')) AS UNSIGNED) = 1 OR CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.fee_type')) AS UNSIGNED) = 2)")
                      ->leftJoin('Sub_LawyerFee', 'TblLawyerFeeDetail.case_idx', '=', 'Sub_LawyerFee.case_idx')
                      ->where(function($q) {
                          $q->where('Sub_LawyerFee.contract_termination', '!=', 1)
                            ->orWhereNull('Sub_LawyerFee.contract_termination');
                      });
            } else {
                // 일반 모드인 경우 특정 날짜의 데이터만 조회
                $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) = ?", [$request->date]);
            }
            
            // 필터 조건 적용
            if ($request->filled('consultant')) {
                $query->where('target_table.Member', $request->consultant);
            }
            
            if ($request->filled('manager')) {
                $query->where('target_table.Member', $request->manager);
            }
            
            if ($request->filled('client_name')) {
                $query->where('target_table.name', 'like', '%' . $request->client_name . '%');
            }
            
            \Log::info('SQL 쿼리', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
            
            $feeDetails = $query->get();
            
            if ($feeDetails->isEmpty()) {
                return response()->json([
                    'success' => false, 
                    'message' => '납부 처리할 미납 수임료가 없습니다.'
                ]);
            }

            \Log::info('처리할 항목 수', ['count' => $feeDetails->count()]);
            
            $processedItems = [];
            $errors = [];
            
            // 각 항목별로 처리
            foreach ($feeDetails as $detail) {
                try {
                    \Log::info('항목 처리 시작', [
                        'id' => $detail->idx,
                        'case_idx' => $detail->case_idx,
                        'client_name' => $detail->name,
                        'member' => $detail->Member
                    ]);
                    
                    // detail JSON 데이터 파싱
                    $detailData = null;
                    
                    if (is_string($detail->detail)) {
                        try {
                            $detailData = json_decode($detail->detail, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $detailData = json_decode(stripslashes($detail->detail), true);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    throw new \Exception("ID {$detail->idx}: Invalid JSON format: " . json_last_error_msg());
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::error('JSON 파싱 오류', [
                                'id' => $detail->idx,
                                'detail' => $detail->detail,
                                'error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    } else if (is_array($detail->detail)) {
                        $detailData = $detail->detail;
                    } else {
                        throw new \Exception("ID {$detail->idx}: Invalid detail data type: " . gettype($detail->detail));
                    }
                    
                    \Log::info('파싱된 상세 데이터', [
                        'id' => $detail->idx,
                        'detailData' => $detailData
                    ]);
                    
                    // 필요한 데이터 추출
                    $money = $detailData['money'] ?? 0;
                    $scheduledDate = $detailData['scheduled_date'] ?? null;
                    $feeType = $detailData['fee_type'] ?? null;
                    
                    \Log::info('추출된 필드 값', [
                        'id' => $detail->idx,
                        'money' => $money,
                        'scheduledDate' => $scheduledDate,
                        'feeType' => $feeType
                    ]);
                    
                    if (!$money || !$scheduledDate) {
                        throw new \Exception("ID {$detail->idx}: Missing required data");
                    }
                    
                    // 고객명 첫 3글자 추출 (한글 주의)
                    $clientName = $detail->name ?? '';
                    $searchKey = mb_substr($clientName, 0, 3, 'UTF-8');
                    
                    if (empty($searchKey)) {
                        throw new \Exception("ID {$detail->idx}: Invalid client name");
                    }
                    
                    \Log::info('고객명 검색키', [
                        'id' => $detail->idx,
                        'clientName' => $clientName,
                        'searchKey' => $searchKey
                    ]);
                    
                    // 5개 테이블에서 매칭되는 데이터 검색
                    $matchedTransaction = $this->findMatchingTransaction($searchKey, $scheduledDate, $money);
                    
                    if ($matchedTransaction) {
                        \Log::info('매칭된 거래 정보', [
                            'id' => $detail->idx,
                            'transaction' => $matchedTransaction
                        ]);
                        
                        DB::beginTransaction();
                        
                        try {
                            // 1. TblLawyerFeeDetail 상태 업데이트
                            $detailData['state'] = 1;
                            $detailData['settlement_date'] = $matchedTransaction['date'];
                            $detailData['settlement_money'] = $money;
                            $detail->detail = $detailData;
                            $detail->save();
                            
                            \Log::info('상태 업데이트 완료', [
                                'id' => $detail->idx,
                                'state' => 1,
                                'settlement_date' => $matchedTransaction['date']
                            ]);
                            
                            // 2. 매칭된 트랜잭션의 account 및 manager 업데이트
                            // fee_type이 0인 경우 송인부, 1,2,3인 경우 서비스매출로 처리
                            $accountType = ($feeType == 0) ? '송인부' : '서비스매출';
                            $this->updateTransactionInfo($matchedTransaction, $accountType, $detail->Member);
                            
                            \Log::info('거래 정보 업데이트 완료', [
                                'id' => $detail->idx,
                                'table' => $matchedTransaction['table_name'],
                                'accountType' => $accountType,
                                'manager' => $detail->Member
                            ]);
                            
                            // 3. Sub_LawyerFeeDetail에 매칭 정보 저장
                            $this->createSubFeeDetail($detail->idx, $matchedTransaction);
                            
                            \Log::info('Sub 테이블 생성 완료', [
                                'id' => $detail->idx,
                                'payment_type' => $matchedTransaction['type'],
                                'payment_id' => $matchedTransaction['id']
                            ]);
                            
                            // 4. RDS 동기화
                            $syncResult = $this->syncToRDS($detail->idx, $detail);
                            
                            if (!$syncResult) {
                                throw new \Exception("ID {$detail->idx}: RDS 동기화 실패");
                            }
                            
                            DB::commit();
                            
                            $processedItems[] = [
                                'date' => $matchedTransaction['date'],
                                'type' => $matchedTransaction['type'],
                                'client_name' => $matchedTransaction['client_name'],
                                'amount' => $money,
                                'manager' => $detail->Member,
                                'account_type' => $accountType
                            ];
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }
                    } else {
                        // 매칭되는 트랜잭션이 없는 경우 로깅
                        \Log::warning("ID {$detail->idx}: 매칭되는 입금내역을 찾을 수 없음", [
                            'client' => $clientName, 
                            'amount' => $money,
                            'date' => $scheduledDate
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('항목 처리 중 오류', [
                        'id' => $detail->idx,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errors[] = $e->getMessage();
                }
            }
            
            \Log::info('일괄 처리 완료', [
                'processed' => count($processedItems),
                'errors' => count($errors)
            ]);
            
            return response()->json([
                'success' => true,
                'processed_count' => count($processedItems),
                'processed_items' => $processedItems,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            \Log::error('일괄 납부처리 중 오류 발생', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (isset($processedItems) && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 매칭되는 트랜잭션 검색
     */
    private function findMatchingTransaction($searchKey, $scheduledDate, $money)
    {
        // 검색 날짜 기준 (scheduledDate를 기준으로 ±7일)
        $startDate = date('Y-m-d', strtotime($scheduledDate . ' -7 days'));
        $endDate = date('Y-m-d', strtotime($scheduledDate . ' +7 days'));
        
        \Log::info('매칭 시작', [
            'searchKey' => $searchKey, 
            'money' => $money, 
            'dateRange' => [$startDate, $endDate]
        ]);
        
        // 1. transactions 테이블 검색
        $transactionsQuery = DB::table('transactions')
            ->select([
                'id',
                DB::raw("'transactions' as table_name"),
                DB::raw("'서울계좌입금' as type"),
                'date',
                'amount',
                'description as client_name',
                'account',
                'manager'
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        
        \Log::info('transactions 쿼리', [
            'sql' => $transactionsQuery->toSql(),
            'bindings' => $transactionsQuery->getBindings()
        ]);
        
        $transaction = $transactionsQuery->first();
        
        if ($transaction) {
            \Log::info('transactions에서 매칭됨', [
                'id' => $transaction->id,
                'date' => $transaction->date,
                'amount' => $transaction->amount,
                'description' => $transaction->client_name,
                'account' => $transaction->account,
                'manager' => $transaction->manager
            ]);
            return (array)$transaction;
        }
        
        // 2. transactions2 테이블 검색
        $transactions2Query = DB::table('transactions2')
            ->select([
                'id',
                DB::raw("'transactions2' as table_name"),
                DB::raw("'대전계좌입금' as type"),
                'date',
                'amount',
                'description as client_name',
                'account',
                'manager'
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        
        \Log::info('transactions2 쿼리', [
            'sql' => $transactions2Query->toSql(),
            'bindings' => $transactions2Query->getBindings()
        ]);
        
        $transaction2 = $transactions2Query->first();
        
        if ($transaction2) {
            \Log::info('transactions2에서 매칭됨', [
                'id' => $transaction2->id,
                'date' => $transaction2->date,
                'amount' => $transaction2->amount,
                'description' => $transaction2->client_name,
                'account' => $transaction2->account,
                'manager' => $transaction2->manager
            ]);
            return (array)$transaction2;
        }
        
        // 3. transactions3 테이블 검색
        $transactions3Query = DB::table('transactions3')
            ->select([
                'id',
                DB::raw("'transactions3' as table_name"),
                DB::raw("'부산계좌입금' as type"),
                'date',
                'amount',
                'description as client_name',
                'account',
                'manager'
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        
        \Log::info('transactions3 쿼리', [
            'sql' => $transactions3Query->toSql(),
            'bindings' => $transactions3Query->getBindings()
        ]);
        
        $transaction3 = $transactions3Query->first();
        
        if ($transaction3) {
            \Log::info('transactions3에서 매칭됨', [
                'id' => $transaction3->id,
                'date' => $transaction3->date,
                'amount' => $transaction3->amount,
                'description' => $transaction3->client_name,
                'account' => $transaction3->account,
                'manager' => $transaction3->manager
            ]);
            return (array)$transaction3;
        }
        
        // 4. payments 테이블 검색
        $paymentsQuery = DB::table('payments')
            ->select([
                'id',
                DB::raw("'payments' as table_name"),
                DB::raw("'CMS입금' as type"),
                'payment_date as date',
                'payment_amount as amount',
                'name as client_name',
                'note as memo',
                'account',
                'manager'
            ])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('payment_amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('name', 'like', "%{$searchKey}%")
                  ->orWhere('note', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        
        \Log::info('payments 쿼리', [
            'sql' => $paymentsQuery->toSql(),
            'bindings' => $paymentsQuery->getBindings()
        ]);
        
        $payment = $paymentsQuery->first();
        
        if ($payment) {
            \Log::info('payments에서 매칭됨', [
                'id' => $payment->id,
                'date' => $payment->date,
                'amount' => $payment->amount,
                'name' => $payment->client_name,
                'account' => $payment->account,
                'manager' => $payment->manager
            ]);
            return (array)$payment;
        }
        
        // 5. income_entries 테이블 검색
        $incomeEntriesQuery = DB::table('income_entries')
            ->select([
                'id',
                DB::raw("'income_entries' as table_name"),
                DB::raw("'매출직접입력' as type"),
                'deposit_date as date',
                'amount',
                'depositor_name as client_name',
                'memo',
                'account_type as account',
                'representative_id as manager_id'
            ])
            ->whereBetween('deposit_date', [$startDate, $endDate])
            ->where('amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('depositor_name', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        
        \Log::info('income_entries 쿼리', [
            'sql' => $incomeEntriesQuery->toSql(),
            'bindings' => $incomeEntriesQuery->getBindings()
        ]);
        
        $incomeEntry = $incomeEntriesQuery->first();
            
        if ($incomeEntry) {
            // representative_id를 이름으로 변환
            if ($incomeEntry->manager_id) {
                $member = DB::table('members')->where('id', $incomeEntry->manager_id)->first();
                $incomeEntry->manager = $member ? $member->name : null;
            }
            \Log::info('income_entries에서 매칭됨', [
                'id' => $incomeEntry->id,
                'date' => $incomeEntry->date,
                'amount' => $incomeEntry->amount,
                'depositor' => $incomeEntry->client_name,
                'account' => $incomeEntry->account,
                'manager_id' => $incomeEntry->manager_id,
                'manager' => $incomeEntry->manager ?? null
            ]);
            return (array)$incomeEntry;
        }
        
        // 매칭을 다시 시도해보자. 이번에는 좀 더 유연하게
        // 문제가 날짜/memo/description 중 어디서 생기는지 디버깅
        \Log::warning('첫 번째 매칭 실패. 직접 거래 데이터 확인', [
            'searchKey' => $searchKey,
            'money' => $money
        ]);
        
        // 금액만으로 검색해보기
        $directTransactions = DB::table('transactions')
            ->where('amount', $money)
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            })
            ->get();
        
        if ($directTransactions->count() > 0) {
            \Log::info('날짜 범위 외 거래 존재 (transactions)', [
                'count' => $directTransactions->count(),
                'data' => $directTransactions
            ]);
        }
        
        \Log::info('매칭되는 입금내역 없음', [
            'searchKey' => $searchKey,
            'money' => $money,
            'dateRange' => [$startDate, $endDate]
        ]);
        return null;
    }
    
    /**
     * 매칭된 트랜잭션 테이블 업데이트
     */
    private function updateTransactionInfo($transaction, $accountType, $managerName)
    {
        $tableName = $transaction['table_name'];
        $id = $transaction['id'];
        
        if ($tableName === 'transactions') {
            DB::table('transactions')
                ->where('id', $id)
                ->update([
                    'account' => $accountType,
                    'manager' => $managerName
                ]);
        } else if ($tableName === 'transactions2') {
            DB::table('transactions2')
                ->where('id', $id)
                ->update([
                    'account' => $accountType,
                    'manager' => $managerName
                ]);
        } else if ($tableName === 'transactions3') {
            DB::table('transactions3')
                ->where('id', $id)
                ->update([
                    'account' => $accountType,
                    'manager' => $managerName
                ]);
        } else if ($tableName === 'payments') {
            DB::table('payments')
                ->where('id', $id)
                ->update([
                    'account' => $accountType,
                    'manager' => $managerName
                ]);
        } else if ($tableName === 'income_entries') {
            // income_entries는 manager가 아닌 representative_id를 사용
            $member = DB::table('members')->where('name', $managerName)->first();
            if ($member) {
                DB::table('income_entries')
                    ->where('id', $id)
                    ->update([
                        'account_type' => $accountType,
                        'representative_id' => $member->id
                    ]);
            }
        }
    }
    
    /**
     * Sub_LawyerFeeDetail 테이블에 매칭 정보 저장
     */
    private function createSubFeeDetail($feeDetailId, $transaction)
    {
        // payment_type을 테이블 ENUM 타입에 맞게 변환
        // ENUM('transactions','transactions2','transactions3','payments','income_entries')
        $paymentType = $transaction['table_name']; // 테이블 이름 그대로 사용
        
        DB::table('Sub_LawyerFeeDetail')->insert([
            'idx' => $feeDetailId,
            'payment_type' => $paymentType,
            'payment_id' => $transaction['id']
        ]);
    }

    /**
     * 사건 상태에 따른 CSS 클래스 반환
     * 
     * @param int $caseType 사건 유형 (1: 개인회생, 2: 개인파산, 3: 기타사건)
     * @param int $stateValue 사건 상태 값
     * @return string CSS 클래스 이름
     */
    private function getCaseStateClass($caseType, $stateValue)
    {
        // 기본 클래스
        $defaultClass = 'badge-state-default';
        
        if (empty($stateValue)) {
            return $defaultClass;
        }
        
        // 개인회생 (1)
        if ($caseType == 1) {
            // 상담~신청서 작성 단계 (초기 단계)
            if (in_array($stateValue, [5, 10, 11, 15, 20, 21, 22, 25])) {
                return 'badge-state-initial';
            }
            // 신청서 제출~보정기간 (중간 단계)
            else if (in_array($stateValue, [30, 35, 40])) {
                return 'badge-state-middle';
            }
            // 개시결정 이후 (후기 단계)
            else if (in_array($stateValue, [45, 50, 55])) {
                return 'badge-state-final';
            }
        } 
        // 개인파산 (2)
        else if ($caseType == 2) {
            // 상담~신청서 작성 단계 (초기 단계)
            if (in_array($stateValue, [5, 10, 11, 15, 20, 21, 22, 25])) {
                return 'badge-state-initial';
            }
            // 신청서 제출~보정기간 (중간 단계)
            else if (in_array($stateValue, [30, 40])) {
                return 'badge-state-middle';
            }
            // 파산선고 이후 (후기 단계)
            else if (in_array($stateValue, [100, 105, 110, 115, 120, 125])) {
                return 'badge-state-final';
            }
        }
        // 기타 사건 (3)
        else if ($caseType == 3) {
            // 상담~서류준비 (초기 단계)
            if (in_array($stateValue, [5, 10, 15, 20])) {
                return 'badge-state-initial';
            }
            // 진행중 (중간 단계)
            else if (in_array($stateValue, [30])) {
                return 'badge-state-middle';
            }
            // 종결 (후기 단계)
            else if (in_array($stateValue, [50])) {
                return 'badge-state-final';
            }
        }
        
        // 위 조건에 해당하지 않는 경우
        return $defaultClass;
    }

    /**
     * 매칭되는 입금내역 검색
     */
    public function searchMatchingPayments($id, Request $request)
    {
        try {
            // 수임료 상세 정보 조회
            $feeDetail = LawyerFeeDetail::join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                ->select('TblLawyerFeeDetail.*', 'target_table.name as client_name', 'target_table.Member')
                ->where('TblLawyerFeeDetail.idx', $id)
                ->first();
            
            if (!$feeDetail) {
                return response()->json(['success' => false, 'message' => '수임료 정보를 찾을 수 없습니다.'], 404);
            }
            
            // 수임료 상세 데이터 파싱
            $detailData = is_array($feeDetail->detail) ? $feeDetail->detail : (is_string($feeDetail->detail) ? json_decode($feeDetail->detail, true) : []);
            if (!is_array($detailData)) {
                return response()->json(['success' => false, 'message' => '유효하지 않은 상세 데이터 형식입니다.'], 400);
            }
            
            // 필요한 데이터 추출
            $money = $detailData['money'] ?? 0;
            $scheduledDate = $detailData['scheduled_date'] ?? date('Y-m-d');
            $feeType = $detailData['fee_type'] ?? -1;
            
            // 고객명 첫 3글자 추출
            $clientName = $feeDetail->client_name ?? '';
            $searchKey = mb_substr($clientName, 0, 3, 'UTF-8');
            
            if (empty($searchKey)) {
                return response()->json(['success' => false, 'message' => '고객명이 유효하지 않습니다.'], 400);
            }
            
            \Log::info('검색 키와 조건', [
                'client_name' => $clientName,
                'searchKey' => $searchKey,
                'money' => $money
            ]);
            
            // 필터 값 가져오기
            $periodFilter = $request->input('period_filter', 7); // 기본값은 7일
            $amountFilter = $request->input('amount_filter', 'exact'); // 기본값은 정확히 일치
            $nameFilter = $request->input('name_filter', 'match'); // 기본값은 이름 일치
            
            // 기간 필터 적용
            if ($periodFilter > 0) {
                $startDate = date('Y-m-d', strtotime($scheduledDate . " -{$periodFilter} days"));
                $endDate = date('Y-m-d', strtotime($scheduledDate . " +{$periodFilter} days"));
            } else {
                // 제한 없음일 경우 넓은 범위 설정
                $startDate = date('Y-m-d', strtotime('-1 year'));
                $endDate = date('Y-m-d', strtotime('+1 year'));
            }
            
            // 금액 필터 적용
            $amountCondition = function($query) use ($amountFilter, $money) {
                if ($amountFilter === 'exact') {
                    $query->where('amount', $money);
                } else if ($amountFilter === 'plus10') {
                    // 10% 범위 내 금액
                    $minAmount = $money * 0.9;
                    $maxAmount = $money * 1.1;
                    $query->whereBetween('amount', [$minAmount, $maxAmount]);
                }
                // 'unlimited'일 경우 금액 조건 없음
            };
            
            // 이름 필터 적용 (일괄 납부처리와 동일한 로직 사용)
            $nameCondition = function($query) use ($nameFilter, $searchKey) {
                if ($nameFilter === 'match') {
                    // 일괄 납부처리와 동일한 방식으로 description, memo 필드에서 검색
                    $query->where(function($q) use ($searchKey) {
                        $q->where('description', 'like', "%{$searchKey}%")
                          ->orWhere('memo', 'like', "%{$searchKey}%");
                    });
                }
                // 'unmatch'일 경우 이름 조건 없음
            };
            
            // payments 테이블에서는 name, note, memo 필드에서 검색해야 함
            $paymentsNameCondition = function($query) use ($nameFilter, $searchKey) {
                if ($nameFilter === 'match') {
                    $query->where(function($q) use ($searchKey) {
                        $q->where('name', 'like', "%{$searchKey}%")
                          ->orWhere('note', 'like', "%{$searchKey}%")
                          ->orWhere('memo', 'like', "%{$searchKey}%");
                    });
                }
            };
            
            // income_entries 테이블에서는 depositor_name, memo 필드에서 검색해야 함
            $incomeEntriesNameCondition = function($query) use ($nameFilter, $searchKey) {
                if ($nameFilter === 'match') {
                    $query->where(function($q) use ($searchKey) {
                        $q->where('depositor_name', 'like', "%{$searchKey}%")
                          ->orWhere('memo', 'like', "%{$searchKey}%");
                    });
                }
            };
            
            // 결과 배열 초기화
            $allResults = [];
            
            // 1. transactions 테이블 검색
            $transactionsQuery = DB::table('transactions')
                ->select([
                    'id',
                    DB::raw("'transactions' as table_name"),
                    DB::raw("'서울계좌입금' as type"),
                    'date',
                    'amount',
                    'description as client_name',
                    'memo',
                    'account',
                    'manager'
                ])
                ->whereBetween('date', [$startDate, $endDate]);
            
            // 금액 필터 적용
            $amountCondition($transactionsQuery);
            
            // 이름 필터 적용
            $nameCondition($transactionsQuery);
            
            $transactions = $transactionsQuery->get();
            foreach ($transactions as $item) {
                $allResults[] = (array)$item;
            }
            
            // 2. transactions2 테이블 검색
            $transactions2Query = DB::table('transactions2')
                ->select([
                    'id',
                    DB::raw("'transactions2' as table_name"),
                    DB::raw("'대전계좌입금' as type"),
                    'date',
                    'amount',
                    'description as client_name',
                    'memo',
                    'account',
                    'manager'
                ])
                ->whereBetween('date', [$startDate, $endDate]);
            
            $amountCondition($transactions2Query);
            $nameCondition($transactions2Query);
            
            $transactions2 = $transactions2Query->get();
            foreach ($transactions2 as $item) {
                $allResults[] = (array)$item;
            }
            
            // 3. transactions3 테이블 검색
            $transactions3Query = DB::table('transactions3')
                ->select([
                    'id',
                    DB::raw("'transactions3' as table_name"),
                    DB::raw("'부산계좌입금' as type"),
                    'date',
                    'amount',
                    'description as client_name',
                    'memo',
                    'account',
                    'manager'
                ])
                ->whereBetween('date', [$startDate, $endDate]);
            
            $amountCondition($transactions3Query);
            $nameCondition($transactions3Query);
            
            $transactions3 = $transactions3Query->get();
            foreach ($transactions3 as $item) {
                $allResults[] = (array)$item;
            }
            
            // 4. payments 테이블 검색
            $paymentsQuery = DB::table('payments')
                ->select([
                    'id',
                    DB::raw("'payments' as table_name"),
                    DB::raw("'CMS입금' as type"),
                    'payment_date as date',
                    'payment_amount as amount',
                    'name as client_name',
                    'note as memo',
                    'account',
                    'manager'
                ])
                ->whereBetween('payment_date', [$startDate, $endDate]);
            
            if ($amountFilter === 'exact') {
                $paymentsQuery->where('payment_amount', $money);
            } else if ($amountFilter === 'plus10') {
                $minAmount = $money * 0.9;
                $maxAmount = $money * 1.1;
                $paymentsQuery->whereBetween('payment_amount', [$minAmount, $maxAmount]);
            }
            
            // payments 테이블용 이름 조건
            $paymentsNameCondition($paymentsQuery);
            
            $payments = $paymentsQuery->get();
            foreach ($payments as $item) {
                $allResults[] = (array)$item;
            }
            
            // 5. income_entries 테이블 검색
            $incomeEntriesQuery = DB::table('income_entries')
                ->select([
                    'id',
                    DB::raw("'income_entries' as table_name"),
                    DB::raw("'매출직접입력' as type"),
                    'deposit_date as date',
                    'amount',
                    'depositor_name as client_name',
                    'memo',
                    'account_type as account',
                    'representative_id as manager_id'
                ])
                ->whereBetween('deposit_date', [$startDate, $endDate]);
            
            $amountCondition($incomeEntriesQuery);
            
            // income_entries 테이블용 이름 조건
            $incomeEntriesNameCondition($incomeEntriesQuery);
            
            $incomeEntries = $incomeEntriesQuery->get();
            foreach ($incomeEntries as $item) {
                // representative_id를 이름으로 변환
                if ($item->manager_id) {
                    $member = DB::table('members')->where('id', $item->manager_id)->first();
                    $item->manager = $member ? $member->name : null;
                }
                
                $allResults[] = (array)$item;
            }
            
            // 일자 기준 내림차순 정렬
            usort($allResults, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            // 클라이언트에 응답
            return response()->json([
                'success' => true,
                'payments' => $allResults,
                'fee_detail' => [
                    'id' => $feeDetail->idx,
                    'case_idx' => $feeDetail->case_idx,
                    'client_name' => $clientName,
                    'amount' => $money,
                    'scheduled_date' => $scheduledDate,
                    'fee_type' => $feeType
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in searchMatchingPayments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '입금 내역 검색 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 테이블과 ID를 기반으로 트랜잭션 정보 조회
     */
    private function getTransactionById($tableName, $id)
    {
        try {
            if ($tableName === 'transactions') {
                $transaction = DB::table('transactions')
                    ->where('id', $id)
                    ->select([
                        'id',
                        DB::raw("'transactions' as table_name"),
                        DB::raw("'서울계좌입금' as type"),
                        'date',
                        'amount',
                        'description as client_name',
                        'account',
                        'manager'
                    ])
                    ->first();
            } else if ($tableName === 'transactions2') {
                $transaction = DB::table('transactions2')
                    ->where('id', $id)
                    ->select([
                        'id',
                        DB::raw("'transactions2' as table_name"),
                        DB::raw("'대전계좌입금' as type"),
                        'date',
                        'amount',
                        'description as client_name',
                        'account',
                        'manager'
                    ])
                    ->first();
            } else if ($tableName === 'transactions3') {
                $transaction = DB::table('transactions3')
                    ->where('id', $id)
                    ->select([
                        'id',
                        DB::raw("'transactions3' as table_name"),
                        DB::raw("'부산계좌입금' as type"),
                        'date',
                        'amount',
                        'description as client_name',
                        'account',
                        'manager'
                    ])
                    ->first();
            } else if ($tableName === 'payments') {
                $transaction = DB::table('payments')
                    ->where('id', $id)
                    ->select([
                        'id',
                        DB::raw("'payments' as table_name"),
                        DB::raw("'CMS입금' as type"),
                        'payment_date as date',
                        'payment_amount as amount',
                        'name as client_name',
                        'account',
                        'manager'
                    ])
                    ->first();
            } else if ($tableName === 'income_entries') {
                $transaction = DB::table('income_entries')
                    ->where('id', $id)
                    ->select([
                        'id',
                        DB::raw("'income_entries' as table_name"),
                        DB::raw("'매출직접입력' as type"),
                        'deposit_date as date',
                        'amount',
                        'depositor_name as client_name',
                        'account_type as account',
                        'representative_id as manager_id'
                    ])
                    ->first();
                    
                // representative_id를 이름으로 변환
                if ($transaction && $transaction->manager_id) {
                    $member = DB::table('members')->where('id', $transaction->manager_id)->first();
                    $transaction->manager = $member ? $member->name : null;
                }
            } else {
                return null;
            }
            
            return $transaction ? (array)$transaction : null;
        } catch (\Exception $e) {
            \Log::error('Error in getTransactionById', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'table' => $tableName,
                'id' => $id
            ]);
            
            return null;
        }
    }

    /**
     * 연체된 수임료 데이터 조회
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverdueData(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);
        $today = date('Y-m-d');
        
        \Log::info('Getting overdue data', [
            'page' => $page,
            'per_page' => $perPage
        ]);
        
        try {
            // 기본 쿼리 - 연체된 데이터만 가져오기
            $baseQuery = LawyerFeeDetail::select('TblLawyerFeeDetail.*', 'target_table.name as client_name', 'target_table.case_type')
                ->join('target_table', 'TblLawyerFeeDetail.case_idx', '=', 'target_table.idx_TblCase')
                // scheduled_date가 오늘보다 이전인 데이터 (연체)
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) < ?", [$today])
                // scheduled_date가 2024-01-01 이후인 데이터만 포함 (오래된 데이터 제외)
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) >= ?", ['2024-01-01'])
                // state가 0(미납)인 데이터만 조회
                ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.state')) AS CHAR) = '0'")
                // fee_type이 1(착수금) 또는 2(분할납부)인 데이터만 표시
                ->whereRaw("(CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.fee_type')) AS UNSIGNED) = 1 OR CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.fee_type')) AS UNSIGNED) = 2)")
                // contract_date가 null이 아닌 데이터만 표시
                ->whereNotNull('target_table.contract_date')
                // del_flag가 0인 데이터만 표시
                ->where('target_table.del_flag', 0)
                // lawyer_fee 값이 0을 초과하는 데이터만 표시
                ->where('target_table.lawyer_fee', '>', 0)
                // case_state 값이 5, 10, 11인 데이터는 제외
                ->whereNotIn('target_table.case_state', [5, 10, 11])
                // Sub_LawyerFee 테이블과 LEFT JOIN으로 계약 해지 여부 확인
                ->leftJoin('Sub_LawyerFee', 'TblLawyerFeeDetail.case_idx', '=', 'Sub_LawyerFee.case_idx')
                // contract_termination이 1인 데이터는 제외 (NULL인 경우는 포함)
                ->where(function($q) {
                    $q->where('Sub_LawyerFee.contract_termination', '!=', 1)
                      ->orWhereNull('Sub_LawyerFee.contract_termination');
                });
            
            // 필터 적용
            if ($request->filled('consultant')) {
                $baseQuery->where('target_table.Member', $request->consultant);
            }
            
            if ($request->filled('manager')) {
                $baseQuery->leftJoin('case_assignments', 'TblLawyerFeeDetail.case_idx', '=', 'case_assignments.case_idx')
                        ->where('case_assignments.case_manager', $request->manager);
            }
            
            if ($request->filled('client_name')) {
                $baseQuery->where('target_table.name', 'like', '%' . $request->client_name . '%');
            }
            
            // 전체 수 쿼리
            $totalQuery = clone $baseQuery;
            $totalCount = $totalQuery->count();
            
            // 전체 금액 계산 쿼리
            $totalQuery = clone $baseQuery;
            $totalAmount = 0;
            $totalLawyerFeeAmount = 0;
            $totalOtherFeeAmount = 0;
            $totalLawyerFeeCount = 0;
            $totalOtherFeeCount = 0;
            
            $totalQuery->chunk(500, function ($details) use (&$totalAmount, &$totalLawyerFeeAmount, &$totalOtherFeeAmount, &$totalLawyerFeeCount, &$totalOtherFeeCount) {
                foreach ($details as $detail) {
                    try {
                        $detailData = is_array($detail->detail) ? $detail->detail : (is_string($detail->detail) ? json_decode($detail->detail, true) : []);
                        if (!is_array($detailData)) {
                            continue;
                        }
                        
                        $amount = $detailData['money'] ?? 0;
                        $totalAmount += $amount;
                        
                        // fee_type에 따른 구분
                        $feeType = isset($detailData['fee_type']) ? (int)$detailData['fee_type'] : -1;
                        $isFeeType = $feeType === -1 || $feeType === 1 || $feeType === 2 || $feeType === 3;
                        
                        if ($isFeeType) {
                            $totalLawyerFeeAmount += $amount;
                            $totalLawyerFeeCount++;
                        } else {
                            $totalOtherFeeAmount += $amount;
                            $totalOtherFeeCount++;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error processing fee detail amount', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });
            
            $statistics = [
                'total' => [
                    'count' => $totalCount, 
                    'amount' => $totalAmount,
                    'lawyer_fee' => ['count' => $totalLawyerFeeCount, 'amount' => $totalLawyerFeeAmount],
                    'other_fee' => ['count' => $totalOtherFeeCount, 'amount' => $totalOtherFeeAmount]
                ],
                'completed' => [
                    'count' => 0, 
                    'amount' => 0,
                    'lawyer_fee' => ['count' => 0, 'amount' => 0],
                    'other_fee' => ['count' => 0, 'amount' => 0]
                ],
                'pending' => [
                    'count' => $totalCount, 
                    'amount' => $totalAmount,
                    'lawyer_fee' => ['count' => $totalLawyerFeeCount, 'amount' => $totalLawyerFeeAmount],
                    'other_fee' => ['count' => $totalOtherFeeCount, 'amount' => $totalOtherFeeAmount]
                ],
                'overdue' => [
                    'count' => $totalCount, 
                    'amount' => $totalAmount,
                    'lawyer_fee' => ['count' => $totalLawyerFeeCount, 'amount' => $totalLawyerFeeAmount],
                    'other_fee' => ['count' => $totalOtherFeeCount, 'amount' => $totalOtherFeeAmount]
                ]
            ];
            
            \Log::info('연체 통계 결과', [
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
                'lawyer_fee_count' => $totalLawyerFeeCount,
                'lawyer_fee_amount' => $totalLawyerFeeAmount,
                'other_fee_count' => $totalOtherFeeCount,
                'other_fee_amount' => $totalOtherFeeAmount
            ]);
            
            // 페이지네이션 적용한 데이터 쿼리
            $paginatedQuery = clone $baseQuery;
            // 납부예정일 최근순 정렬
            $paginatedQuery->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(TblLawyerFeeDetail.detail, '$.scheduled_date')) AS DATE) DESC");
            
            $feeDetails = $paginatedQuery->skip(($page - 1) * $perPage)
                             ->take($perPage)
                             ->get();
                
            $result = [];
            
            foreach ($feeDetails as $detail) {
                try {
                    $detailData = is_array($detail->detail) ? $detail->detail : (is_string($detail->detail) ? json_decode($detail->detail, true) : []);
                    
                    if (!is_array($detailData)) {
                        continue;
                    }
                    
                    $scheduled_date = $detailData['scheduled_date'] ?? null;
                    if (!$scheduled_date) {
                        continue;
                    }
                    
                    // 결과 배열에 추가
                    if (!isset($result[$scheduled_date])) {
                        $result[$scheduled_date] = [];
                    }
                    
                    $feeType = isset($detailData['fee_type']) ? (int)$detailData['fee_type'] : -1;
                    
                    $result[$scheduled_date][] = [
                        'id' => $detail->idx,
                        'case_idx' => $detail->case_idx,
                        'scheduled_date' => $scheduled_date,
                        'amount' => $detailData['money'] ?? 0,
                        'state' => $detailData['state'] ?? 0,
                        'settlement_date' => $detailData['settlement_date'] ?? null,
                        'memo' => $detailData['memo'] ?? null,
                        'fee_type' => $feeType
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error processing fee detail', [
                        'detail_id' => $detail->idx,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }
            
            // 페이지네이션 정보 추가
            $hasMorePages = ($page * $perPage) < $totalCount;
            $nextPage = $hasMorePages ? $page + 1 : null;
            
            return [
                'fee_details' => $result,
                'statistics' => $statistics,
                'is_overdue_view' => true,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'has_more_pages' => $hasMorePages,
                    'next_page' => $nextPage
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error in getOverdueData', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => '연체 데이터를 불러오는 중 오류가 발생했습니다.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 멤버 목록 조회 (필터용)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembers()
    {
        try {
            $members = DB::table('members')
                ->select('id', 'name')
                ->where('status', '!=', '퇴사')
                ->orWhereNull('status')
                ->orderBy('name', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'members' => $members
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching members', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '멤버 목록을 가져오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 현재 로그인한 사용자의 정보를 바탕으로 필터 기본값 설정
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentUserData()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '로그인 정보를 찾을 수 없습니다.'
                ], 401);
            }
            
            $userName = $user->name;
            
            // users.name 값으로 members.name을 조인하여 members.task 값 확인
            $memberData = DB::table('members')
                ->where('name', $userName)
                ->select('name', 'task')
                ->first();
            
            $defaultData = [
                'success' => true,
                'defaultConsultant' => '',
                'defaultManager' => '',
                'userTask' => $memberData ? $memberData->task : ''
            ];
            
            // 사용자의 task 값에 따라 기본 필터값 설정
            if ($memberData) {
                if ($memberData->task === '법률컨설팅팀') {
                    $defaultData['defaultConsultant'] = $userName;
                } else if ($memberData->task === '사건관리팀') {
                    $defaultData['defaultManager'] = $userName;
                }
            }
            
            return response()->json($defaultData);
        } catch (\Exception $e) {
            \Log::error('Error fetching user data for filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '사용자 정보를 가져오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }
} 