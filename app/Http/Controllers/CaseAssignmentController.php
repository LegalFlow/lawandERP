<?php

namespace App\Http\Controllers;

use App\Models\CaseAssignment;
use App\Models\Target;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CaseAssignmentController extends Controller
{
    public function index()
    {
        // 기본 쿼리 시작
        $query = CaseAssignment::query();
        
        // Target 테이블과 조인하여 최신 case_state 값 가져오기
        $query->leftJoin('target_table', 'case_assignments.case_idx', '=', 'target_table.idx_TblCase')
              ->select('case_assignments.*', 'target_table.case_state');
        
        // 로그인한 사용자의 task 정보 가져오기
        $userTask = null;
        $userName = auth()->user() ? auth()->user()->name : null;
        
        if ($userName) {
            $memberInfo = DB::table('members')
                ->where('name', $userName)
                ->first();
            
            if ($memberInfo) {
                $userTask = $memberInfo->task;
            }
        }
        
        // 사용자 role에 따른 기본 필터값 설정
        $defaultConsultant = '';
        $defaultCaseManager = '';
        
        if ($userTask === '법률컨설팅팀') {
            $defaultConsultant = $userName;
        } elseif ($userTask === '사건관리팀') {
            $defaultCaseManager = $userName;
        }
        
        // 필터가 적용된 경우 (어떤 파라미터든 있는 경우)와 처음 페이지 방문시 구분
        $isFilterApplied = request()->hasAny(['start_date', 'end_date', 'client_name', 'case_number', 'notes', 'search_type', 'search_value', 'submission_status', 'contract_status', 'date_type']) || 
            request()->has('consultant') || request()->has('case_manager');
        
        // 필터가 적용된 경우: 파라미터 값 그대로 사용 (빈 값도 그대로 '전체' 의미)
        // 처음 방문시: 기본값 적용
        $consultant = $isFilterApplied ? request('consultant', '') : $defaultConsultant;
        $caseManager = $isFilterApplied ? request('case_manager', '') : $defaultCaseManager;
        
        // 검색 필터가 있는 경우에만 필터 적용
        if ($isFilterApplied || !empty($defaultConsultant) || !empty($defaultCaseManager)) {
            
            // 간편 검색 처리 (검색 타입과 값이 있는 경우)
            if (request()->filled(['search_type', 'search_value'])) {
                $searchType = request('search_type');
                $searchValue = request('search_value');
                
                if ($searchType === 'client_name') {
                    $query->where('case_assignments.client_name', 'like', '%' . trim($searchValue) . '%');
                } elseif ($searchType === 'case_number') {
                    $searchNumber = preg_replace('/\s+/', '', $searchValue);
                    $query->where(DB::raw('REPLACE(case_assignments.case_number, " ", "")'), 'like', '%' . $searchNumber . '%');
                }
            }
            
            // 날짜 유형 필터
            $dateColumn = request('date_type', 'assignment_date');
            
            // 기간 필터
            if (request()->filled(['start_date', 'end_date'])) {
                $query->whereBetween('case_assignments.'.$dateColumn, [
                    request('start_date'),
                    request('end_date')
                ]);
            }

            // 고객명 검색 (상세 필터 사용 시)
            if (request()->filled('client_name')) {
                $query->where('case_assignments.client_name', 'like', '%' . trim(request('client_name')) . '%');
            }

            // 상담자 검색 (기본값 또는 명시적 지정값 적용)
            if (!empty($consultant)) {
                $query->where('case_assignments.consultant', $consultant);
            }

            // 담당자 검색 (기본값 또는 명시적 지정값 적용)
            if (!empty($caseManager)) {
                $query->where('case_assignments.case_manager', $caseManager);
            }

            // 사건번호 검색 (공백 무시) (상세 필터 사용 시)
            if (request()->filled('case_number')) {
                $searchNumber = preg_replace('/\s+/', '', request('case_number'));
                $query->where(DB::raw('REPLACE(case_assignments.case_number, " ", "")'), 'like', '%' . $searchNumber . '%');
            }

            // 비고 검색
            if (request()->filled('notes')) {
                $query->where('case_assignments.notes', 'like', '%' . trim(request('notes')) . '%');
            }
            
            // 보정제출상태 필터
            if (request()->filled('submission_status')) {
                $submissionStatus = request('submission_status');
                // 보정제출상태로 필터링하는 서브쿼리 구성
                $caseNumbers = DB::table('correction_div')
                    ->select('case_number')
                    ->where('submission_status', $submissionStatus)
                    ->distinct()
                    ->pluck('case_number')
                    ->toArray();
                
                if (!empty($caseNumbers)) {
                    $query->whereIn('case_assignments.case_number', $caseNumbers);
                } else {
                    // 일치하는 보정이 없을 경우 빈 결과 반환
                    $query->whereRaw('1 = 0');
                }
            }
            
            // 계약상태 필터
            if (request()->filled('contract_status')) {
                $contractStatus = request('contract_status');
                // 서브쿼리로 Sub_LawyerFee 테이블과 연결
                $subQuery = DB::table('Sub_LawyerFee')
                    ->select('case_idx');
                
                if ($contractStatus === '계약해지') {
                    $subQuery->where('contract_termination', true);
                } else if ($contractStatus === '정상') {
                    $subQuery->where(function($q) {
                        $q->where('contract_termination', false)
                          ->orWhereNull('contract_termination');
                    });
                }
                
                $caseIndices = $subQuery->pluck('case_idx')->toArray();
                
                if (!empty($caseIndices)) {
                    $query->whereIn('case_assignments.case_idx', $caseIndices);
                } else {
                    // 일치하는 계약이 없을 경우 빈 결과 반환
                    $query->whereRaw('1 = 0');
                }
            }

            // 정렬 로직 추가
            if (request('date_type') == 'summit_date') {
                $query->orderBy('case_assignments.summit_date', 'desc');
            } else {
                $query->orderBy('case_assignments.assignment_date', 'desc');
            }
        } else {
            // 검색 필터가 없는 경우 created_at으로 정렬
            $query->orderBy('case_assignments.created_at', 'desc');
        }

        // 기간별 통계를 위한 선택된 개월 수 가져오기 (기본값: 2)
        $selectedMonths = session('selected_months', 2);
        
        // 통계 데이터 준비
        $statistics = $this->getStatistics($query, $selectedMonths);

        // 기존 코드 유지
        $today = now()->format('Ymd');
        $lastAssignment = CaseAssignment::where('case_idx', 'like', "{$today}%")
                                       ->orderBy('case_idx', 'desc')
                                       ->first();
        
        $newNumber = $lastAssignment 
            ? str_pad(intval(substr($lastAssignment->case_idx, -3)) + 1, 3, '0', STR_PAD_LEFT)
            : '001';
        
        $newCaseIdx = $today . $newNumber;

        // 사건관리팀 멤버 조회 (통계용)
        $caseManagers = Member::where('task', '사건관리팀')
                             ->where('status', '재직')
                             ->orderBy('name')
                             ->get();

        // 전체 멤버 조회 (검색필터 및 신규등록용)
        $allMembers = Member::orderBy('name')->get();

        $assignments = $query->paginate(15);
        
        return view('case_assignments.index', compact(
            'assignments', 
            'caseManagers',
            'allMembers',
            'newCaseIdx',
            'statistics',
            'selectedMonths',
            'userTask',
            'userName',
            'defaultConsultant',
            'defaultCaseManager',
            'consultant',
            'caseManager'
        ));
    }

    private function getStatistics($query, $selectedMonths = 2)
    {
        // 기간별 통계 계산
        $periodQuery = (clone $query)
            ->whereBetween('case_assignments.assignment_date', [
                now()->subMonths($selectedMonths - 1)->startOfMonth()->format('Y-m-d'),
                now()->endOfMonth()->format('Y-m-d')
            ]);

        $periodTotal = $periodQuery->count();
        $periodCounts = (clone $periodQuery)
            ->whereNotNull('case_assignments.case_manager')
            ->select('case_assignments.case_manager', DB::raw('count(*) as count'))
            ->groupBy('case_assignments.case_manager')
            ->pluck('count', 'case_manager')
            ->toArray();

        // 이번달 통계 - 1일부터 말일까지
        $currentMonthQuery = (clone $query)
            ->whereBetween('case_assignments.assignment_date', [
                now()->startOfMonth()->format('Y-m-d'),    // 이번달 1일
                now()->endOfMonth()->format('Y-m-d')       // 이번달 말일
            ]);

        $currentMonthTotal = $currentMonthQuery->count();
        $currentMonthCounts = (clone $currentMonthQuery)
            ->whereNotNull('case_assignments.case_manager')
            ->select('case_assignments.case_manager', DB::raw('count(*) as count'))
            ->groupBy('case_assignments.case_manager')
            ->pluck('count', 'case_manager')
            ->toArray();

        // 지난달 통계
        $lastMonthQuery = (clone $query)
            ->whereBetween('case_assignments.assignment_date', [
                now()->subMonth()->startOfMonth()->format('Y-m-d'),
                now()->subMonth()->endOfMonth()->format('Y-m-d')
            ]);

        $lastMonthTotal = $lastMonthQuery->count();
        $lastMonthCounts = (clone $lastMonthQuery)
            ->whereNotNull('case_assignments.case_manager')
            ->select('case_assignments.case_manager', DB::raw('count(*) as count'))
            ->groupBy('case_assignments.case_manager')
            ->pluck('count', 'case_manager')
            ->toArray();

        return [
            'period' => [
                'totalCount' => $periodTotal,
                'managerCounts' => $periodCounts,
                'months' => $selectedMonths
            ],
            'currentMonth' => [
                'totalCount' => $currentMonthTotal,
                'managerCounts' => $currentMonthCounts
            ],
            'lastMonth' => [
                'totalCount' => $lastMonthTotal,
                'managerCounts' => $lastMonthCounts
            ]
        ];
    }

    public function assign(Request $request, $caseId)
    {
        try {
            DB::beginTransaction();

            $target = DB::table('target_table')
                       ->where('idx_TblCase', $caseId)
                       ->where('div_case', 0)
                       ->first();

            if (!$target) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }

            // 배당 생성
            CaseAssignment::create([
                'case_idx' => $target->idx_TblCase,
                'case_type' => $target->case_type,
                'assignment_date' => Carbon::now()->toDateString(),
                'client_name' => $target->name,
                'living_place' => $target->living_place,
                'consultant' => $target->Member,
                'case_state' => $target->case_state,
                'court_name' => $target->court_name,
                'case_number' => $target->case_number,
                'case_manager' => $request->case_manager,
                'notes' => $request->notes
            ]);

            // target 업데이트
            DB::table('target_table')
              ->where('idx_TblCase', $caseId)
              ->update(['div_case' => 1]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '사건이 성공적으로 배당되었습니다.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '사건 배당 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $assignment = CaseAssignment::findOrFail($id);
            
            // target의 div_case를 0으로 되돌림
            DB::table('target_table')
              ->where('idx_TblCase', $assignment->case_idx)
              ->update(['div_case' => 0]);

            // 배당 삭제
            $assignment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '배당이 삭제되었습니다.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '배당 삭제 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    public function create()
    {
        // 오늘 날짜 기준으로 새로운 case_idx 생성
        $today = now()->format('Ymd');
        
        // 오늘 생성된 마지막 번호 조회
        $lastAssignment = CaseAssignment::where('case_idx', 'like', "{$today}%")
                                       ->orderBy('case_idx', 'desc')
                                       ->first();
        
        // 새로운 일련번호 생성
        if ($lastAssignment) {
            $lastNumber = intval(substr($lastAssignment->case_idx, -3));
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        
        // 새로운 case_idx 생성 (예: 20241119001)
        $newCaseIdx = $today . $newNumber;
        
        return view('case_assignments.create', compact('newCaseIdx'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // case_idx 생성 (YYYYMMDD + 3자리 순번)
            $today = now()->format('Ymd');
            $lastAssignment = CaseAssignment::where('case_idx', 'like', "{$today}%")
                                          ->orderBy('case_idx', 'desc')
                                          ->first();
            
            if ($lastAssignment) {
                $lastNumber = intval(substr($lastAssignment->case_idx, -3));
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }
            
            $caseIdx = intval($today . $newNumber); // bigint로 저장하기 위해 정수로 변환
            
            // 데이터 생성
            CaseAssignment::create([
                'case_idx' => $caseIdx,
                'case_type' => $request->case_type,
                'assignment_date' => $request->assignment_date ?? now()->toDateString(),
                'client_name' => $request->client_name,
                'living_place' => $request->living_place,
                'consultant' => $request->consultant,
                'case_state' => $request->case_state,
                'court_name' => $request->court_name,
                'case_number' => $request->case_number,
                'case_manager' => $request->case_manager ?? '담당없음',
                'notes' => $request->notes,
                'summit_date' => $request->summit_date
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => '사건이 성공적으로 등록되었습니다.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '사건 등록 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateField(Request $request, $id)
    {
        try {
            $assignment = CaseAssignment::findOrFail($id);
            $assignment->update([
                $request->field => $request->value
            ]);

            return response()->json([
                'success' => true,
                'message' => '업데이트되었습니다.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '업데이트 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $assignment = CaseAssignment::findOrFail($id);
            $assignment->{$request->field} = $request->value;
            $assignment->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // Ajax 요청을 처리할 새로운 메소드
    public function updatePeriodStats(Request $request)
    {
        $months = $request->input('months', 2);
        
        // 세션에 선택된 개월 수 저장
        session(['selected_months' => $months]);
        
        // 기본 쿼리 생성
        $query = CaseAssignment::query()
            ->leftJoin('target_table', 'case_assignments.case_idx', '=', 'target_table.idx_TblCase')
            ->select('case_assignments.*', 'target_table.case_state');
        
        // 통계 데이터 계산
        $periodQuery = (clone $query)
            ->whereBetween('case_assignments.assignment_date', [
                now()->subMonths($months - 1)->startOfMonth()->format('Y-m-d'),
                now()->endOfMonth()->format('Y-m-d')
            ]);

        $periodTotal = $periodQuery->count();
        $periodCounts = (clone $periodQuery)
            ->whereNotNull('case_assignments.case_manager')
            ->select('case_assignments.case_manager', DB::raw('count(*) as count'))
            ->groupBy('case_assignments.case_manager')
            ->pluck('count', 'case_manager')
            ->toArray();

        // 사건관리팀 멤버 조회
        $caseManagers = Member::where('task', '사건관리팀')
                             ->where('status', '재직')
                             ->orderBy('name')
                             ->get();

        // 기간 텍스트 생성
        $periodText = now()->subMonths($months - 1)->format('Y년 m월') . ' ~ ' . now()->format('Y년 m월');

        return response()->json([
            'success' => true,
            'data' => [
                'totalCount' => $periodTotal,
                'managerCounts' => $periodCounts,
                'months' => $months,
                'periodText' => $periodText,
                'managers' => $caseManagers
            ]
        ]);
    }

    /**
     * 사건 상세정보를 가져옵니다.
     * 
     * @param int $id 사건배당 ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetail($id)
    {
        try {
            // 사건배당 정보 조회
            $assignment = CaseAssignment::findOrFail($id);
            
            // Target 테이블에서 전화번호 정보 조회
            $target = DB::table('target_table')
                       ->where('idx_TblCase', $assignment->case_idx)
                       ->first();
            $phoneNumber = $target ? $target->phone : null;
            
            // 계약상태 조회 (Sub_LawyerFee 테이블)
            $contractStatus = DB::table('Sub_LawyerFee')
                ->where('case_idx', $assignment->case_idx)
                ->value('contract_termination');
                
            $contractStatusText = $contractStatus ? '계약해지' : '정상';
            
            // 보정내역 조회 (correction_div 테이블)
            $corrections = DB::table('correction_div')
                ->where('case_number', $assignment->case_number)
                ->where('name', $assignment->client_name)
                ->orderByRaw('CASE WHEN receipt_date IS NULL THEN 0 ELSE 1 END')
                ->orderByRaw('receipt_date IS NULL DESC')
                ->orderBy('receipt_date', 'asc')
                ->orderBy('shipment_date', 'desc')
                ->get();
                
            // 제출상태 계산
            $submissionStatus = '제출완료';
            
            // 제출상태 로직 적용
            $hasUnsubmitted = $corrections->contains('submission_status', '미제출');
            $hasPending = $corrections->contains('submission_status', '연기신청');
            $hasTerminated = $corrections->contains('submission_status', '계약해지') || 
                            $corrections->contains('submission_status', '연락두절');
            
            if ($hasTerminated) {
                $submissionStatus = '계약해지';
            } else if ($hasUnsubmitted) {
                $submissionStatus = '미제출';
            } else if ($hasPending) {
                $submissionStatus = '연기신청';
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'assignment' => $assignment,
                    'phoneNumber' => $phoneNumber,
                    'contractStatus' => $contractStatusText,
                    'submissionStatus' => $submissionStatus,
                    'corrections' => $corrections
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '상세 정보를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 여러 행의 상태 정보를 한 번에 가져옵니다.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBulkStatus(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID 목록이 필요합니다.'
                ], 400);
            }
            
            // CaseAssignment 정보 일괄 조회
            $assignments = CaseAssignment::whereIn('id', $ids)->get();
            
            // case_idx 및 case_number 목록 추출
            $caseIdxList = $assignments->pluck('case_idx')->toArray();
            $caseNumberList = $assignments->pluck('case_number')->toArray();
            $clientNameList = $assignments->pluck('client_name')->toArray();
            
            // SubLawyerFee 테이블에서 계약상태 일괄 조회
            $contractStatuses = DB::table('Sub_LawyerFee')
                ->whereIn('case_idx', $caseIdxList)
                ->select('case_idx', 'contract_termination')
                ->get()
                ->keyBy('case_idx');
            
            // CorrectionDiv 테이블에서 보정내역 상태 일괄 조회 (필요한 정보만)
            $corrections = DB::table('correction_div')
                ->whereIn('case_number', $caseNumberList)
                ->whereIn('name', $clientNameList)
                ->select('case_number', 'name', 'submission_status')
                ->get();
            
            // 결과 준비
            $result = [];
            
            foreach ($assignments as $assignment) {
                // 계약상태 확인
                $contractStatus = $contractStatuses->get($assignment->case_idx);
                $contractStatusText = ($contractStatus && $contractStatus->contract_termination) ? '계약해지' : '정상';
                
                // 해당 사건의 보정내역 필터링
                $assignmentCorrections = $corrections->filter(function ($item) use ($assignment) {
                    return $item->case_number === $assignment->case_number && 
                           $item->name === $assignment->client_name;
                });
                
                // 제출상태 계산 - 보정내역이 없으면 빈 값으로 설정
                $submissionStatus = '';
                
                // 보정내역이 있는 경우에만 상태 설정
                if ($assignmentCorrections->count() > 0) {
                    $hasUnsubmitted = $assignmentCorrections->contains('submission_status', '미제출');
                    $hasPending = $assignmentCorrections->contains('submission_status', '연기신청');
                    $hasTerminated = $assignmentCorrections->contains('submission_status', '계약해지') || 
                                    $assignmentCorrections->contains('submission_status', '연락두절');
                    
                    if ($hasTerminated) {
                        $submissionStatus = '계약해지';
                    } else if ($hasUnsubmitted) {
                        $submissionStatus = '미제출';
                    } else if ($hasPending) {
                        $submissionStatus = '연기신청';
                    } else {
                        $submissionStatus = '제출완료';
                    }
                }
                
                $result[$assignment->id] = [
                    'contractStatus' => $contractStatusText,
                    'submissionStatus' => $submissionStatus
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '상태 정보를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Target 테이블의 case_state 값을 사용하여 진행현황을 일괄 업데이트합니다.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateCaseStates(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID 목록이 필요합니다.'
                ], 400);
            }
            
            // CaseAssignment 정보 일괄 조회
            $assignments = CaseAssignment::whereIn('id', $ids)->get();
            
            // case_idx 목록 추출
            $caseIdxList = $assignments->pluck('case_idx')->toArray();
            
            // Target 테이블의 case_state 값 조회
            $targetStates = DB::table('target_table')
                ->whereIn('idx_TblCase', $caseIdxList)
                ->select('idx_TblCase', 'case_state', 'case_type')
                ->get()
                ->keyBy('idx_TblCase');
            
            $updatedCount = 0;
            $unchangedCount = 0;
            $updatedAssignments = [];
            
            // 각 배당 항목에 대해 진행현황 업데이트
            foreach ($assignments as $assignment) {
                $targetInfo = $targetStates->get($assignment->case_idx);
                
                if ($targetInfo && $assignment->case_state !== $targetInfo->case_state) {
                    // 진행현황이 다를 경우에만 업데이트
                    $oldState = $assignment->case_state;
                    $newState = $targetInfo->case_state;
                    
                    $assignment->case_state = $newState;
                    $assignment->save();
                    
                    $updatedCount++;
                    $updatedAssignments[] = [
                        'id' => $assignment->id,
                        'oldState' => $oldState,
                        'newState' => $newState,
                        'caseType' => $assignment->case_type,
                        'stateLabel' => \App\Helpers\CaseStateHelper::getStateLabel($assignment->case_type, $newState)
                    ];
                } else {
                    $unchangedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'updatedCount' => $updatedCount,
                    'unchangedCount' => $unchangedCount,
                    'updatedAssignments' => $updatedAssignments
                ],
                'message' => "진행현황이 업데이트되었습니다. (변경: {$updatedCount}, 유지: {$unchangedCount})"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '진행현황 업데이트 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 담당자별 사건 배당 및 제출 현황 차트 데이터를 제공합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData(Request $request)
    {
        try {
            $filterType = $request->input('filterType', 'last2Months');
            
            // 사건 유형 필터 받기 (배열로)
            $caseTypes = $request->input('caseTypes', [1, 2]); // 기본값은 개인회생(1)과 개인파산(2)
            
            // 시작일과 종료일 기간 계산
            $startDate = now();
            $endDate = now();
            
            // 기간 필터에 따른 날짜 범위 설정
            switch ($filterType) {
                case 'currentMonth': // 당월
                    $startDate = now()->startOfMonth();
                    break;
                case 'last2Months': // 최근 2개월
                    $startDate = now()->subMonth()->startOfMonth();
                    break;
                case 'last3Months': // 최근 3개월
                    $startDate = now()->subMonths(2)->startOfMonth();
                    break;
                case 'currentQuarter': // 이번 분기
                    $currentQuarter = ceil(now()->month / 3);
                    $startDate = now()->setMonth(($currentQuarter - 1) * 3 + 1)->startOfMonth();
                    break;
                case 'lastQuarter': // 지난 분기
                    $currentQuarter = ceil(now()->month / 3);
                    if ($currentQuarter === 1) {
                        // 1분기인 경우 작년 4분기
                        $startDate = now()->subYear()->setMonth(10)->startOfMonth();
                        $endDate = now()->subYear()->setMonth(12)->endOfMonth();
                    } else {
                        // 이전 분기
                        $startDate = now()->setMonth(($currentQuarter - 2) * 3 + 1)->startOfMonth();
                        $endDate = now()->setMonth(($currentQuarter - 1) * 3)->endOfMonth();
                    }
                    break;
                case 'currentYear': // 올해
                    $startDate = now()->startOfYear();
                    break;
                case 'lastYear': // 지난해
                    $startDate = now()->subYear()->startOfYear();
                    $endDate = now()->subYear()->endOfYear();
                    break;
                default:
                    $startDate = now()->subMonth()->startOfMonth();
            }
            
            // 사건관리팀 담당자 목록 조회
            $caseManagers = Member::where('task', '사건관리팀')
                ->where('status', '재직')
                ->orderBy('name')
                ->get(['name']);
            
            // 각 담당자별 배당 사건 수와 제출 사건 수 계산
            $managers = [];
            
            foreach ($caseManagers as $manager) {
                // 담당자별 배당 사건 수 계산 - 사건 유형 필터 적용
                $assignedCount = CaseAssignment::where('case_manager', $manager->name)
                    ->whereIn('case_type', $caseTypes) // 선택된 사건 유형만 필터링
                    ->whereBetween('assignment_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->count();
                
                // 담당자별 제출 사건 수 계산 - 사건 유형 필터 적용
                $submittedCount = CaseAssignment::where('case_manager', $manager->name)
                    ->whereIn('case_type', $caseTypes) // 선택된 사건 유형만 필터링
                    ->whereNotNull('summit_date')
                    ->whereBetween('summit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->count();
                
                $managers[] = [
                    'name' => $manager->name,
                    'assigned' => $assignedCount,
                    'submitted' => $submittedCount
                ];
            }
            
            // 배당 사건 수 기준 내림차순 정렬
            usort($managers, function($a, $b) {
                return $b['assigned'] - $a['assigned'];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'filterType' => $filterType,
                    'caseTypes' => $caseTypes, // 선택된 사건 유형도 응답에 포함
                    'startDate' => $startDate->format('Y-m-d'),
                    'endDate' => $endDate->format('Y-m-d'),
                    'managers' => $managers
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '차트 데이터를 불러오는 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
} 