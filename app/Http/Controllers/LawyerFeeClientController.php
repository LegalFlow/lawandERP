<?php

namespace App\Http\Controllers;

use App\Models\LawyerFeeDetail;
use App\Models\LawyerFee;
use App\Models\Target;
use App\Models\CaseAssignment;
use App\Helpers\CaseStateHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LawyerFeeClientController extends Controller
{
    /**
     * 고객별 수임료 납부현황 메인 페이지
     */
    public function index()
    {
        return view('fee_client.index');
    }

    /**
     * 고객별 수임료 납부현황 목록 조회
     */
    public function getClientsList(Request $request)
    {
        try {
            // 기본 쿼리 - target_table과 join하여 계약이 있는 고객만 표시
            $query = Target::select(
                    'target_table.*',
                    DB::raw('(SELECT SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, "$.money")) AS DECIMAL(10,0))) 
                    FROM TblLawyerFeeDetail 
                    WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.state")) = "1") as paid_fee'),
                    DB::raw('(SELECT SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, "$.money")) AS DECIMAL(10,0))) 
                    FROM TblLawyerFeeDetail 
                    WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")) as total_fee')
                )
                ->whereNotNull('contract_date')
                // del_flag가 0인 데이터만 표시
                ->where('del_flag', 0)
                // lawyer_fee 값이 0을 초과하는 데이터만 표시
                ->where('lawyer_fee', '>', 0)
                // case_state 값이 5, 10, 11인 데이터는 제외
                ->whereNotIn('case_state', [5, 10, 11])
                // 2024년 1월 이후 계약일 데이터만 표시
                ->where('contract_date', '>=', '2024-01-01')
                // 총 수임료가 0을 초과하는 데이터만 표시
                ->whereRaw('(SELECT SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, "$.money")) AS DECIMAL(10,0))) 
                    FROM TblLawyerFeeDetail 
                    WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")) > 0');
            
            // 필터 적용
            // 납부 상태 필터
            if ($request->filled('payment_status')) {
                $paymentStatus = $request->input('payment_status');
                if ($paymentStatus === 'completed') {
                    // 이미 완납된 항목 필터
                    $query->whereRaw('(SELECT COUNT(*) FROM TblLawyerFeeDetail 
                        WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.state")) = "0") = 0');
                    $query->whereRaw('(SELECT COUNT(*) FROM TblLawyerFeeDetail 
                        WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")) > 0');
                } elseif ($paymentStatus === 'pending') {
                    // 미납 항목이 있으나 연체되지 않은 항목 필터
                    $query->whereRaw('(SELECT COUNT(*) FROM TblLawyerFeeDetail 
                        WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.state")) = "0"
                        AND (JSON_UNQUOTE(JSON_EXTRACT(detail, "$.scheduled_date")) >= CURDATE() OR JSON_UNQUOTE(JSON_EXTRACT(detail, "$.scheduled_date")) IS NULL)) > 0');
                } elseif ($paymentStatus === 'overdue') {
                    // 연체된 항목이 있는 항목 필터
                    $query->whereRaw('(SELECT COUNT(*) FROM TblLawyerFeeDetail 
                        WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.state")) = "0"
                        AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.scheduled_date")) < CURDATE()) > 0');
                }
            }
            
            // 계약 상태 필터
            if ($request->filled('contract_status')) {
                $contractStatus = $request->input('contract_status');
                if ($contractStatus === 'terminated') {
                    // 계약 해지된 고객만 표시
                    $query->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('Sub_LawyerFee')
                            ->whereRaw('Sub_LawyerFee.case_idx = target_table.idx_TblCase')
                            ->where('contract_termination', 1);
                    });
                } elseif ($contractStatus === 'normal') {
                    // 계약 정상인 고객만 표시
                    $query->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('Sub_LawyerFee')
                            ->whereRaw('Sub_LawyerFee.case_idx = target_table.idx_TblCase')
                            ->where('contract_termination', 1);
                    });
                }
            }
            
            // 사건 유형 필터
            if ($request->filled('case_type') && $request->input('case_type') !== 'all') {
                $query->where('case_type', $request->input('case_type'));
            }
            
            // 상담자 필터
            if ($request->filled('consultant') && $request->input('consultant') !== 'all') {
                $query->where('Member', $request->input('consultant'));
            }
            
            // 담당자 필터 (CaseAssignment 테이블 조인 필요)
            if ($request->filled('case_manager') && $request->input('case_manager') !== 'all') {
                $caseManager = $request->input('case_manager');
                $query->whereExists(function ($query) use ($caseManager) {
                    $query->select(DB::raw(1))
                        ->from('case_assignments')
                        ->whereRaw('case_assignments.case_idx = target_table.idx_TblCase')
                        ->where('case_manager', $caseManager);
                });
            }
            
            // 날짜 범위 필터
            if ($request->filled('start_date')) {
                $query->where('contract_date', '>=', $request->input('start_date'));
            }
            
            if ($request->filled('end_date')) {
                $query->where('contract_date', '<=', $request->input('end_date'));
            }
            
            // 검색 필터
            if ($request->filled('search_type') && $request->filled('search_keyword')) {
                $searchType = $request->input('search_type');
                $searchKeyword = $request->input('search_keyword');
                
                if ($searchType === 'name') {
                    $query->where('name', 'like', '%' . $searchKeyword . '%');
                } elseif ($searchType === 'case_idx') {
                    $query->where('idx_TblCase', $searchKeyword);
                }
            }
            
            // 계약일 최신순으로 정렬 후, 같은 계약일인 경우 고객명으로 정렬
            $query->orderBy('contract_date', 'desc')
                ->orderBy('name', 'asc');
            
            // 페이지네이션
            $perPage = $request->input('per_page', 10);
            $clients = $query->paginate($perPage);
            
            // 결과가 비어있는 경우에도 유효한 응답 반환
            if ($clients->isEmpty()) {
                return response()->json([
                    'clients' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => 1,
                        'last_page' => 1
                    ],
                    'summary' => [
                        'total_clients' => 0,
                        'unpaid_clients' => 0,
                        'overdue_clients' => 0
                    ]
                ]);
            }
            
            // 각 고객에 대한 추가 정보 조회 (담당자, 서류 상태 등)
            foreach ($clients as $client) {
                // 담당자 정보 조회
                $assignment = CaseAssignment::where('case_idx', $client->idx_TblCase)->first();
                $client->case_manager = $assignment ? $assignment->case_manager : '미지정';
                
                // 납부 상태 계산
                $this->calculatePaymentStatus($client);
                
                // 계약 상태 (기본값: 정상)
                $client->contract_status = '정상';
                
                // 서류 상태 조회
                $this->getDocumentStatus($client);
            }
            
            // 완납, 미납, 연체 고객 수 계산
            $completedCount = 0;
            $unpaidCount = 0;
            $overdueCount = 0;
            
            foreach ($clients as $client) {
                if ($client->payment_status === '완납') {
                    $completedCount++;
                } else if ($client->payment_status === '미납') {
                    $unpaidCount++;
                } else if ($client->payment_status === '연체') {
                    $overdueCount++;
                }
            }
            
            return response()->json([
                'clients' => $clients->items(),
                'pagination' => [
                    'total' => $clients->total(),
                    'per_page' => $clients->perPage(),
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage()
                ],
                'summary' => [
                    'total_clients' => $clients->total(),
                    'completed_clients' => $completedCount,
                    'unpaid_clients' => $unpaidCount,
                    'overdue_clients' => $overdueCount
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('고객별 수임료 목록 조회 오류', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => '고객 목록을 불러오는 중 오류가 발생했습니다.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 고객별 수임료 납부 상세 정보 조회
     */
    public function getClientDetail($case_idx)
    {
        try {
            // 고객 정보 조회
            $client = Target::select(
                    'target_table.*',
                    DB::raw('(SELECT SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, "$.money")) AS DECIMAL(10,0))) 
                    FROM TblLawyerFeeDetail 
                    WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.state")) = "1") as paid_fee'),
                    DB::raw('(SELECT SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(detail, "$.money")) AS DECIMAL(10,0))) 
                    FROM TblLawyerFeeDetail 
                    WHERE TblLawyerFeeDetail.case_idx = target_table.idx_TblCase 
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, "$.fee_type")) IN ("1", "2")) as total_fee')
                )
                ->whereNotNull('contract_date')
                ->where('idx_TblCase', $case_idx)
                ->first();
            
            if (!$client) {
                return response()->json(['error' => '고객 정보를 찾을 수 없습니다.'], 404);
            }
            
            // 수임료 상세 정보 조회
            $feeDetails = LawyerFeeDetail::where('case_idx', $case_idx)
                ->get()
                ->map(function ($detail) {
                    $detailData = is_array($detail->detail) ? $detail->detail : json_decode($detail->detail, true);
                    
                    // 기본 정보 구성
                    $result = [
                        'id' => $detail->idx,
                        'case_idx' => $detail->case_idx,
                        'fee_type' => $detailData['fee_type'] ?? -1,
                        'fee_type_text' => $this->getFeeTypeText($detail),
                        'scheduled_date' => $detailData['scheduled_date'] ?? null,
                        'money' => $detailData['money'] ?? 0,
                        'state' => $detailData['state'] ?? 0,
                        'state_text' => ($detailData['state'] == 1) ? '완납' : (
                            (isset($detailData['scheduled_date']) && strtotime($detailData['scheduled_date']) < strtotime('today')) 
                            ? '연체' : '미납'
                        ),
                        'settlement_date' => $detailData['settlement_date'] ?? null,
                        'memo' => $detailData['memo'] ?? ''
                    ];
                    
                    // 완납된 항목이면 Sub_LawyerFeeDetail 에서 payment_type 조회
                    if ($result['state'] == 1) {
                        $subDetail = DB::table('Sub_LawyerFeeDetail')
                            ->where('idx', $detail->idx)
                            ->first();
                        
                        if ($subDetail) {
                            // 결제 방식 매핑
                            $paymentTypes = [
                                'transactions' => '서울계좌입금',
                                'transactions2' => '대전계좌입금',
                                'transactions3' => '부산계좌입금',
                                'payments' => 'CMS입금',
                                'income_entries' => '매출직접입력'
                            ];
                            
                            $result['settlement_method'] = isset($paymentTypes[$subDetail->payment_type]) 
                                ? $paymentTypes[$subDetail->payment_type] 
                                : $subDetail->payment_type;
                        }
                    }
                    
                    return $result;
                });
            
            // 서류 정보 조회
            $applyDocs = DB::table('TblCaseApplyDocs')
                ->where('case_idx', $case_idx)
                ->first();
            
            $debtDocs = DB::table('TblCaseDebtDoc')
                ->where('case_idx', $case_idx)
                ->first();
            
            // 서류 상태 처리
            $docsInfo = $this->getDocumentStatus($client);
            
            // 분할납부 순서 계산 (fee_type이 2인 항목)
            $installments = $feeDetails->filter(function ($detail) {
                return $detail['fee_type'] == 2;
            })->sortBy('scheduled_date');
            
            $installmentCount = 1;
            foreach ($installments as $key => $detail) {
                $feeDetails->transform(function ($item) use ($detail, $installmentCount) {
                    if ($item['id'] == $detail['id']) {
                        $item['fee_type_text'] = $installmentCount . '차 분할납부';
                    }
                    return $item;
                });
                $installmentCount++;
            }
            
            // Sub_LawyerFee 테이블에서 서류 요청 상태 정보 조회
            $docRequestStatus = DB::table('Sub_LawyerFee')
                ->where('case_idx', $case_idx)
                ->first();
            
            return response()->json([
                'client' => $client,
                'fee_details' => $feeDetails->sortBy([
                    ['fee_type', 'asc'],
                    [function ($item) {
                        return $item['scheduled_date'] ?? '9999-12-31';
                    }, 'asc']
                ])->values()->all(),
                'docs_info' => $docsInfo,
                'doc_request_status' => $docRequestStatus ?: (object)[
                    'id_request' => 0,
                    'seal_request' => 0,
                    'first_doc_request' => 0,
                    'second_doc_request' => 0,
                    'debt_cert_request' => 0,
                    'contract_termination' => 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('고객 상세 정보 조회 오류', [
                'case_idx' => $case_idx,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => '고객 상세 정보를 불러오는 중 오류가 발생했습니다.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 납부 상태 업데이트
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        return response()->json([
            'success' => false, 
            'message' => '고객별 수임료 페이지에서는 납부 상태를 변경할 수 없습니다. 수임료 캘린더 페이지를 이용해주세요.'
        ], 403);
    }

    /**
     * 서류 요청 상태 업데이트
     */
    public function updateDocumentRequest(Request $request, $case_idx)
    {
        try {
            // 요청으로부터 체크박스 상태 가져오기
            $idRequest = (bool)$request->input('id_request', false);
            $sealRequest = (bool)$request->input('seal_request', false);
            $firstDocRequest = (bool)$request->input('first_doc_request', false);
            $secondDocRequest = (bool)$request->input('second_doc_request', false);
            $debtCertRequest = (bool)$request->input('debt_cert_request', false);
            $contractTermination = (bool)$request->input('contract_termination', false);
            
            // Sub_LawyerFee 테이블에서 해당 case_idx의 데이터 조회
            $lawyerFee = DB::table('Sub_LawyerFee')->where('case_idx', $case_idx)->first();
            
            if ($lawyerFee) {
                // 기존 데이터가 있는 경우 업데이트
                DB::table('Sub_LawyerFee')
                    ->where('case_idx', $case_idx)
                    ->update([
                        'id_request' => $idRequest ? 1 : 0,
                        'seal_request' => $sealRequest ? 1 : 0,
                        'first_doc_request' => $firstDocRequest ? 1 : 0,
                        'second_doc_request' => $secondDocRequest ? 1 : 0,
                        'debt_cert_request' => $debtCertRequest ? 1 : 0,
                        'contract_termination' => $contractTermination ? 1 : 0,
                        'update_dt' => now()
                    ]);
            } else {
                // 데이터가 없는 경우 새로 생성
                DB::table('Sub_LawyerFee')->insert([
                    'case_idx' => $case_idx,
                    'id_request' => $idRequest ? 1 : 0,
                    'seal_request' => $sealRequest ? 1 : 0,
                    'first_doc_request' => $firstDocRequest ? 1 : 0,
                    'second_doc_request' => $secondDocRequest ? 1 : 0,
                    'debt_cert_request' => $debtCertRequest ? 1 : 0,
                    'contract_termination' => $contractTermination ? 1 : 0,
                    'create_dt' => now(),
                    'update_dt' => now()
                ]);
            }
            
            // 성공 응답
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('서류 요청 상태 업데이트 오류', [
                'case_idx' => $case_idx,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '서류 요청 상태를 업데이트하는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 계약 상태 업데이트
     */
    public function updateContractStatus(Request $request, $case_idx)
    {
        // 추후 구현
        return response()->json(['success' => true]);
    }

    /**
     * 납부 메모 업데이트
     */
    public function updatePaymentMemo(Request $request, $id)
    {
        try {
            \Log::info('메모 업데이트 시작', [
                'id' => $id,
                'memo' => $request->input('memo'),
                'request_data' => $request->all()
            ]);
            
            // 트랜잭션 시작
            DB::beginTransaction();
            
            $detail = LawyerFeeDetail::findOrFail($id);
            
            // detail 필드의 현재 상태 로깅
            \Log::info('현재 detail 데이터', ['detail_type' => gettype($detail->detail), 'detail' => $detail->detail]);
            
            // detail 필드의 타입과 값에 따른 안전한 디코딩
            $detailData = null;
            
            if (is_array($detail->detail)) {
                // 이미 배열인 경우 그대로 사용
                $detailData = $detail->detail;
                \Log::info('detail이 이미 배열입니다');
            } else if (is_string($detail->detail)) {
                // 문자열인 경우 JSON인지 확인 후 디코딩
                $detailData = json_decode($detail->detail, true);
                
                // 디코딩 실패 시, 이중 인코딩 데이터인지 확인
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::warning('첫 번째 디코딩 실패, 이중 인코딩 데이터일 수 있음', ['error' => json_last_error_msg()]);
                    
                    // 이중 인코딩된 경우 한 번 더 디코딩 시도
                    $decodedOnce = json_decode($detail->detail, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_string($decodedOnce)) {
                        $detailData = json_decode($decodedOnce, true);
                        \Log::info('이중 인코딩 데이터 감지 및 처리', ['result' => json_last_error() === JSON_ERROR_NONE]);
                    }
                } else {
                    \Log::info('정상적인 JSON 문자열을 디코딩했습니다');
                }
            }
            
            // 디코딩 실패 시 새 객체로 시작
            if (!is_array($detailData)) {
                \Log::warning('디코딩 실패, 신규 객체 생성');
                $detailData = [
                    'fee_type' => isset($detail->detail['fee_type']) ? $detail->detail['fee_type'] : -1,
                    'money' => isset($detail->detail['money']) ? $detail->detail['money'] : 0,
                    'state' => isset($detail->detail['state']) ? $detail->detail['state'] : 0,
                    'scheduled_date' => isset($detail->detail['scheduled_date']) ? $detail->detail['scheduled_date'] : null,
                    'settlement_date' => isset($detail->detail['settlement_date']) ? $detail->detail['settlement_date'] : null
                ];
            }
            
            // 메모 업데이트
            $detailData['memo'] = $request->input('memo', '');
            
            // 정보 로깅
            \Log::info('메모 업데이트 후 detailData', ['detailData' => $detailData]);
            
            // 데이터 업데이트 (1회만 인코딩)
            $detail->detail = json_encode($detailData);
            $detail->save();
            
            \Log::info('로컬 DB 업데이트 완료', ['id' => $id, 'saved_detail' => $detail->detail]);
            
            // AWS RDS 동기화 시도
            $syncResult = $this->syncToRDS($id, $detail);
            
            // 동기화 결과에 상관없이 로컬 트랜잭션은 커밋
            DB::commit();
            
            \Log::info('메모 업데이트 완료', ['id' => $id, 'rds_sync' => $syncResult]);
            
            return response()->json(['success' => true, 'message' => '메모가 저장되었습니다.']);
        } catch (\Exception $e) {
            // 오류 발생 시 롤백
            DB::rollBack();
            \Log::error('메모 업데이트 실패', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
            
            // detail 필드 분석 및 처리
            $detailValue = $detail->detail;
            
            // detail 필드의 현재 상태 확인 및 적절한 JSON 형식으로 변환
            if (is_array($detailValue)) {
                // 배열인 경우 JSON 문자열로 변환
                $detailJson = json_encode($detailValue);
                \Log::info('배열에서 JSON으로 변환', ['type' => 'array_to_json']);
            } else if (is_string($detailValue)) {
                // 문자열이 이미 JSON 형식인지 확인
                json_decode($detailValue);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // JSON 형식이 아닌 경우 경고 로깅
                    \Log::warning('RDS 동기화: detail이 유효한 JSON이 아님', [
                        'detail_type' => gettype($detailValue),
                        'error' => json_last_error_msg()
                    ]);
                    // 실패 시 안전장치: 기본 객체로 변환
                    $detailJson = json_encode([
                        'fee_type' => -1,
                        'money' => 0,
                        'state' => 0,
                        'memo' => ''
                    ]);
                } else {
                    // 이미 유효한 JSON 문자열이면 그대로 사용
                    $detailJson = $detailValue;
                    \Log::info('이미 유효한 JSON 문자열', ['type' => 'valid_json_string']);
                }
            } else {
                // 다른 타입의 경우 안전장치: 기본 객체로 변환
                \Log::warning('RDS 동기화: detail 필드가 예상치 못한 타입', ['type' => gettype($detailValue)]);
                $detailJson = json_encode([
                    'fee_type' => -1,
                    'money' => 0,
                    'state' => 0,
                    'memo' => ''
                ]);
            }
            
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
                
                // 오류가 발생해도 로컬 DB 업데이트는 계속 진행
                return false;
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
     * 납부 유형 텍스트 반환
     */
    private function getFeeTypeText($detail)
    {
        $detailData = is_array($detail->detail) ? $detail->detail : json_decode($detail->detail, true);
        
        if (!isset($detailData['fee_type'])) {
            return '미지정';
        }

        switch ($detailData['fee_type']) {
            case -1:
                return '미지정';
            case 0:
                return '송달료 등 부대비용';
            case 1:
                return '착수금';
            case 2:
                return '분할납부'; // 기본값으로 설정, 컨트롤러에서 차수 계산
            case 3:
                return '성공보수';
            default:
                return '미지정';
        }
    }

    /**
     * 고객별 납부 상태 계산
     */
    private function calculatePaymentStatus($client)
    {
        // DB 레벨에서 해당 case_idx에 대한 수임료 데이터 확인
        $feeStatus = DB::select("
            SELECT 
                COUNT(*) as total_fees,
                SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(detail, '$.state')) = '1' THEN 1 ELSE 0 END) as paid_fees
            FROM TblLawyerFeeDetail
            WHERE case_idx = ?
            AND JSON_UNQUOTE(JSON_EXTRACT(detail, '$.fee_type')) IN ('1', '2')
        ", [$client->idx_TblCase]);
        
        // 수임료 항목이 있는지 확인
        if ($feeStatus[0]->total_fees > 0) {
            if ($feeStatus[0]->total_fees == $feeStatus[0]->paid_fees) {
                // 모든 항목이 완납인 경우
                $client->payment_status = '완납';
            } else {
                // 미납 항목이 있는 경우, 연체 여부 확인
                $overdueStatus = DB::select("
                    SELECT COUNT(*) as overdue_count
                    FROM TblLawyerFeeDetail
                    WHERE case_idx = ?
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, '$.fee_type')) IN ('1', '2')
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, '$.state')) = '0'
                    AND JSON_UNQUOTE(JSON_EXTRACT(detail, '$.scheduled_date')) < CURDATE()
                ", [$client->idx_TblCase]);
                
                if ($overdueStatus[0]->overdue_count > 0) {
                    // 연체된 항목이 있는 경우
                    $client->payment_status = '연체';
                } else {
                    // 미납이지만 연체는 아닌 경우
                    $client->payment_status = '미납';
                }
            }
        } else {
            // 수임료 항목이 없는 경우
            $client->payment_status = '미정';
        }
        
        return $client;
    }

    /**
     * 서류 상태 조회
     */
    private function getDocumentStatus($client)
    {
        // 서류 상태 조회
        $applyDocs = DB::table('TblCaseApplyDocs')
            ->where('case_idx', $client->idx_TblCase)
            ->first();
        
        $debtDocs = DB::table('TblCaseDebtDoc')
            ->where('case_idx', $client->idx_TblCase)
            ->first();
        
        // Sub_LawyerFee 테이블에서 서류 요청 상태 정보 조회
        $docRequestStatus = DB::table('Sub_LawyerFee')
            ->where('case_idx', $client->idx_TblCase)
            ->first();
        
        // 신분증 상태
        $idCardStatus = $this->checkDocumentStatus($applyDocs, '신분증 사본');
        $client->id_card_status = $idCardStatus;
        
        // 인감 상태
        $sealStatus = $this->checkDocumentStatus($applyDocs, '인감도장');
        $client->seal_status = $sealStatus;
        
        // 서류 요청 상태가 있고, 기존 상태가 '표시없음'인 경우에만 요청 상태 적용
        if ($docRequestStatus) {
            if ($idCardStatus === '표시없음' && $docRequestStatus->id_request) {
                $client->id_card_status = '요청';
            }
            
            if ($sealStatus === '표시없음' && $docRequestStatus->seal_request) {
                $client->seal_status = '요청';
            }
            
            // 계약해지 상태 적용
            if ($docRequestStatus->contract_termination) {
                $client->contract_status = '계약해지';
            }
        }
        
        // 1차 서류 상태
        $firstDocsStatus = $this->calculateFirstDocsStatus($applyDocs);
        $client->first_docs_completed = $firstDocsStatus['completed'] ?? 0;
        $client->first_docs_total = $firstDocsStatus['total'] ?? 0;
        
        // 1차 서류 요청 상태 적용 (프로그레스바가 없는 경우)
        if ($docRequestStatus && $firstDocsStatus['total'] == 0 && $docRequestStatus->first_doc_request) {
            $client->first_docs_request = true;
        }
        
        // 2차 서류 상태
        $secondDocsStatus = $this->calculateSecondDocsStatus($applyDocs);
        $client->second_docs_completed = $secondDocsStatus['completed'] ?? 0;
        $client->second_docs_total = $secondDocsStatus['total'] ?? 0;
        
        // 2차 서류 요청 상태 적용 (프로그레스바가 없는 경우)
        if ($docRequestStatus && $secondDocsStatus['total'] == 0 && $docRequestStatus->second_doc_request) {
            $client->second_docs_request = true;
        }
        
        // 부채증명서 상태
        $debtDocsStatus = $this->calculateDebtDocsStatus($debtDocs);
        $client->debt_docs_completed = $debtDocsStatus['completed'] ?? 0;
        $client->debt_docs_total = $debtDocsStatus['total'] ?? 0;
        
        // 부채증명서 요청 상태 적용 (프로그레스바가 없는 경우)
        if ($docRequestStatus && $debtDocsStatus['total'] == 0 && $docRequestStatus->debt_cert_request) {
            $client->debt_docs_request = true;
        }
        
        return $client;
    }

    /**
     * 문서 항목 상태 확인
     */
    private function checkDocumentStatus($docs, $docName)
    {
        if (!$docs || !isset($docs->details)) {
            return '표시없음';
        }
        
        $details = json_decode($docs->details, true);
        
        foreach ($details as $doc) {
            if (isset($doc['name']) && strpos($doc['name'], $docName) !== false) {
                if (isset($doc['active']) && $doc['active'] === true) {
                    return '완료';
                } else {
                    return '표시없음';
                }
            }
        }
        
        return '표시없음';
    }

    /**
     * 1차 서류 상태 계산
     */
    private function calculateFirstDocsStatus($docs)
    {
        if (!$docs || !isset($docs->details)) {
            return ['completed' => 0, 'total' => 0];
        }
        
        $details = json_decode($docs->details, true);
        $firstDocsTotal = 0;
        $firstDocsCompleted = 0;
        
        foreach ($details as $doc) {
            // 소득금액증명을 포함하는 항목 이전까지가 1차 서류
            if (isset($doc['name']) && strpos($doc['name'], '소득금액증명') !== false) {
                break;
            }
            
            $firstDocsTotal++;
            if (isset($doc['active']) && $doc['active'] === true) {
                $firstDocsCompleted++;
            }
        }
        
        return [
            'completed' => $firstDocsCompleted,
            'total' => $firstDocsTotal
        ];
    }

    /**
     * 2차 서류 상태 계산
     */
    private function calculateSecondDocsStatus($docs)
    {
        if (!$docs || !isset($docs->details)) {
            return ['completed' => 0, 'total' => 0];
        }
        
        $details = json_decode($docs->details, true);
        $secondDocsTotal = 0;
        $secondDocsCompleted = 0;
        $isSecondDocs = false;
        
        foreach ($details as $doc) {
            // 소득금액증명을 포함하는 항목부터 2차 서류 시작
            if (!$isSecondDocs) {
                if (isset($doc['name']) && strpos($doc['name'], '소득금액증명') !== false) {
                    $isSecondDocs = true;
                } else {
                    continue;
                }
            }
            
            $secondDocsTotal++;
            if (isset($doc['active']) && $doc['active'] === true) {
                $secondDocsCompleted++;
            }
        }
        
        return [
            'completed' => $secondDocsCompleted,
            'total' => $secondDocsTotal
        ];
    }

    /**
     * 부채증명서 상태 계산
     */
    private function calculateDebtDocsStatus($docs)
    {
        if (!$docs || !isset($docs->docs)) {
            return ['completed' => 0, 'total' => 0];
        }
        
        $details = json_decode($docs->docs, true);
        $debtDocsTotal = count($details);
        $debtDocsCompleted = 0;
        
        foreach ($details as $doc) {
            if (isset($doc['issue']) && $doc['issue'] === true) {
                $debtDocsCompleted++;
            }
        }
        
        return [
            'completed' => $debtDocsCompleted,
            'total' => $debtDocsTotal
        ];
    }

    /**
     * 멤버 목록 조회 (상담자/담당자 필터용)
     */
    public function getMembersList()
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
            Log::error('멤버 목록 조회 오류', [
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
            Log::error('사용자 정보 조회 오류', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '사용자 정보를 가져오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 이중 인코딩된 TblLawyerFeeDetail 레코드 수정 (관리자용)
     * 
     * 주의: 이 메소드는 직접 호출해서만 사용하세요.
     * 예: /fee-client/fix-double-encoded-details
     */
    public function fixDoubleEncodedDetails()
    {
        if (!auth()->user() || !auth()->user()->is_admin) {
            return response()->json(['error' => '관리자만 사용할 수 있는 기능입니다.'], 403);
        }

        try {
            // 트랜잭션 시작
            DB::beginTransaction();
            
            // 잠재적으로 이중 인코딩된 레코드 찾기
            $potentiallyDoubleEncoded = DB::table('TblLawyerFeeDetail')
                ->whereRaw("detail LIKE '{\"\\\\%' OR detail LIKE '\"%\"'")
                ->get();
            
            \Log::info('잠재적으로 이중 인코딩된 레코드 발견', [
                'count' => $potentiallyDoubleEncoded->count(),
            ]);
            
            $fixed = 0;
            $failed = 0;
            $details = [];
            
            foreach ($potentiallyDoubleEncoded as $record) {
                try {
                    // 현재 데이터 로깅
                    $details[] = [
                        'idx' => $record->idx,
                        'case_idx' => $record->case_idx,
                        'before' => $record->detail
                    ];
                    
                    // JSON 문자열로 저장된 경우의 처리
                    if (substr($record->detail, 0, 1) === '"' && substr($record->detail, -1) === '"') {
                        // 가장 바깥쪽 따옴표 제거
                        $content = substr($record->detail, 1, -1);
                        // 이스케이프된 따옴표와 슬래시 처리
                        $content = str_replace('\\"', '"', $content);
                        $content = str_replace('\\\\', '\\', $content);
                        
                        // 유효한 JSON인지 확인
                        $decoded = json_decode($content, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // 유효하면 업데이트
                            DB::table('TblLawyerFeeDetail')
                                ->where('idx', $record->idx)
                                ->update(['detail' => $content]);
                            
                            $fixed++;
                            $details[count($details) - 1]['after'] = $content;
                            $details[count($details) - 1]['status'] = 'fixed';
                        } else {
                            // 여전히 JSON이 아니면 첫 번째 디코딩 후 다시 인코딩
                            $decoded = json_decode($record->detail, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $newDetail = json_encode($decoded);
                                DB::table('TblLawyerFeeDetail')
                                    ->where('idx', $record->idx)
                                    ->update(['detail' => $newDetail]);
                                
                                $fixed++;
                                $details[count($details) - 1]['after'] = $newDetail;
                                $details[count($details) - 1]['status'] = 'fixed_with_decode';
                            } else {
                                $failed++;
                                $details[count($details) - 1]['status'] = 'failed_invalid_json';
                            }
                        }
                    } else {
                        // 이중 인코딩 확인 (JSON 내부에 이스케이프된 큰따옴표가 있는지)
                        if (strpos($record->detail, '\\"') !== false) {
                            $decoded = json_decode($record->detail, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
                                $decodedAgain = json_decode($decoded, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $newDetail = json_encode($decodedAgain);
                                    DB::table('TblLawyerFeeDetail')
                                        ->where('idx', $record->idx)
                                        ->update(['detail' => $newDetail]);
                                    
                                    $fixed++;
                                    $details[count($details) - 1]['after'] = $newDetail;
                                    $details[count($details) - 1]['status'] = 'fixed_double_encoded';
                                }
                            } else {
                                $failed++;
                                $details[count($details) - 1]['status'] = 'failed_not_double_encoded';
                            }
                        } else {
                            // 특별한 처리가 필요하지 않음
                            $details[count($details) - 1]['status'] = 'no_action_needed';
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $details[count($details) - 1]['error'] = $e->getMessage();
                    $details[count($details) - 1]['status'] = 'failed_exception';
                }
            }
            
            // 트랜잭션 커밋
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "총 {$potentiallyDoubleEncoded->count()}개 레코드 중 {$fixed}개 수정 완료, {$failed}개 실패",
                'fixed' => $fixed,
                'failed' => $failed,
                'total' => $potentiallyDoubleEncoded->count(),
                'details' => $details
            ]);
            
        } catch (\Exception $e) {
            // 트랜잭션 롤백
            DB::rollBack();
            
            \Log::error('이중 인코딩 수정 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '이중 인코딩 수정 중 오류 발생: ' . $e->getMessage()
            ], 500);
        }
    }
} 