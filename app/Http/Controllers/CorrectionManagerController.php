<?php

namespace App\Http\Controllers;

use App\Models\CorrectionDiv;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CorrectionManagerController extends Controller
{
    public function index(Request $request)
    {
        // 기본 필터: 보기 모드
        $viewMode = $request->query('view_mode', 'unclassified'); // 기본값을 'unclassified'로 변경

        // 로그인한 사용자 정보
        $user = Auth::user();
        $member = null;
        
        // 사용자와 연결된 멤버 정보 검색
        if ($user) {
            $member = Member::where('name', $user->name)->first();
        }

        // 필터 기본값 설정
        $defaultConsultant = 'all';
        $defaultManager = 'all';
        
        // 담당자없음 탭이 아닐 경우에만 사용자 역할에 따라 필터 기본값 설정
        if ($viewMode !== 'no_manager' && $member) {
            if ($member->task === '법률컨설팅팀') {
                $defaultConsultant = $member->name;
            } elseif ($member->task === '사건관리팀') {
                $defaultManager = $member->name;
            }
        }

        // 재직 중인 직원 목록
        $activeMembers = Member::where('status', '재직')->get();
        $consultants = $activeMembers->pluck('name')->toArray();
        $managers = $activeMembers->pluck('name')->toArray();

        return view('correction_manager.index', [
            'viewMode' => $viewMode,
            'defaultConsultant' => $defaultConsultant,
            'defaultManager' => $defaultManager,
            'consultants' => $consultants,
            'managers' => $managers,
        ]);
    }

    public function getData(Request $request)
    {
        $query = CorrectionDiv::query();
        
        // 발신일자 기준 2025-01-01 이후의 문서만 표시 - 기본 적용 필터
        $query->where('shipment_date', '>=', '2025-01-01');
        
        // 뷰 모드에 따른 필터링
        $viewMode = $request->query('view_mode', 'all');
        
        switch ($viewMode) {
            case 'unclassified':
                $query->where(function($q) {
                    $q->whereNull('document_type')
                      ->orWhere('document_type', '')
                      ->orWhere('document_type', '선택없음');
                });
                break;
            case 'unsubmitted':
                $query->where(function($q) {
                    $q->whereNotNull('document_type')
                      ->where('document_type', '!=', '')
                      ->where('document_type', '!=', '선택없음')
                      ->where(function($sq) {
                          $sq->where('submission_status', '미제출')
                             ->orWhere('submission_status', '연기신청');
                      });
                });
                break;
            case 'completed':
                $query->where(function($q) {
                    $q->whereNotNull('document_type')
                      ->where('document_type', '!=', '')
                      ->where('document_type', '!=', '선택없음')
                      ->whereNotIn('submission_status', ['미제출', '연기신청']);
                });
                break;
            case 'no_manager':
                // 담당자가 없거나 재직 중이 아닌 경우
                $activeManagerNames = Member::where('status', '재직')
                                          ->where('task', '사건관리팀')
                                          ->pluck('name')
                                          ->toArray();
                
                $query->where(function($q) use ($activeManagerNames) {
                    $q->whereNull('case_manager')
                      ->orWhere('case_manager', '')
                      ->orWhereNotIn('case_manager', $activeManagerNames);
                })
                // 제출여부가 '미제출'인 경우만 표시
                ->where('submission_status', '미제출');
                break;
        }
        
        // 검색 조건 적용
        if ($request->has('search_type') && $request->has('search_keyword') && $request->search_keyword) {
            $searchType = $request->search_type;
            $searchKeyword = $request->search_keyword;
            
            if ($searchType === 'name') {
                $query->where('name', 'like', "%{$searchKeyword}%");
            } elseif ($searchType === 'case_number') {
                $query->where('case_number', 'like', "%{$searchKeyword}%");
            }
        }
        
        // 날짜 필터 적용
        if ($request->has('date_type') && $request->has('date_from') && $request->has('date_to')) {
            $dateType = $request->date_type;
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;
            
            if (!empty($dateFrom) && !empty($dateTo)) {
                $query->whereBetween($dateType, [$dateFrom, $dateTo]);
            }
        }
        
        // 상담자 필터 적용
        if ($request->has('consultant') && $request->consultant !== 'all') {
            $query->where('consultant', $request->consultant);
        }
        
        // 담당자 필터 적용
        if ($request->has('case_manager') && $request->case_manager !== 'all') {
            $query->where('case_manager', $request->case_manager);
        }
        
        // 정렬 (발송일 내림차순을 기본값으로)
        $query->orderBy('shipment_date', 'desc');
        
        // 총 레코드 수 계산
        $totalCount = $query->count();
        
        // count_only 파라미터가 있으면 카운트만 반환
        if ($request->has('count_only') && $request->count_only === 'true') {
            return response()->json([
                'meta' => [
                    'total' => $totalCount
                ]
            ]);
        }
        
        // 페이지네이션 처리
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);
        $offset = ($page - 1) * $perPage;
        
        $corrections = $query->skip($offset)->take($perPage)->get();
        
        // 각 카테고리별 카운트 미리 계산
        $unclassifiedCount = CorrectionDiv::where('shipment_date', '>=', '2025-01-01')
            ->where(function($q) {
                $q->whereNull('document_type')
                  ->orWhere('document_type', '')
                  ->orWhere('document_type', '선택없음');
            })->count();
        
        $unsubmittedCount = CorrectionDiv::where('shipment_date', '>=', '2025-01-01')
            ->where(function($q) {
                $q->whereNotNull('document_type')
                  ->where('document_type', '!=', '')
                  ->where('document_type', '!=', '선택없음')
                  ->where(function($sq) {
                      $sq->where('submission_status', '미제출')
                        ->orWhere('submission_status', '연기신청');
                  });
            })->count();
            
        $completedCount = CorrectionDiv::where('shipment_date', '>=', '2025-01-01')
            ->where(function($q) {
                $q->whereNotNull('document_type')
                  ->where('document_type', '!=', '')
                  ->where('document_type', '!=', '선택없음')
                  ->whereNotIn('submission_status', ['미제출', '연기신청']);
            })->count();
            
        $activeManagerNames = Member::where('status', '재직')
            ->where('task', '사건관리팀')
            ->pluck('name')
            ->toArray();
            
        $noManagerCount = CorrectionDiv::where('shipment_date', '>=', '2025-01-01')
            ->where(function($q) use ($activeManagerNames) {
                $q->whereNull('case_manager')
                  ->orWhere('case_manager', '')
                  ->orWhereNotIn('case_manager', $activeManagerNames);
            })
            ->where('submission_status', '미제출')
            ->count();
        
        return response()->json([
            'data' => $corrections,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalCount,
                'has_more' => ($offset + $perPage) < $totalCount,
                'counts' => [
                    'unclassified' => $unclassifiedCount,
                    'unsubmitted' => $unsubmittedCount,
                    'completed' => $completedCount,
                    'no_manager' => $noManagerCount
                ]
            ]
        ]);
    }

    /**
     * 새 문서 저장
     */
    public function store(Request $request)
    {
        // 유효성 검사
        $validated = $request->validate([
            'shipment_date' => 'required|date',
            'receipt_date' => 'required|date',
            'court_name' => 'required|string',
            'case_number' => 'required|string',
            'name' => 'required|string',
            'document_name' => 'nullable|string',
            'document_type' => 'required|string',
            'consultant' => 'nullable|string',
            'case_manager' => 'nullable|string',
            'deadline' => 'nullable|date',
            'submission_status' => 'required|string',
            'summit_date' => 'nullable|date',
            'command' => 'nullable|string',
        ]);
        
        // 제출상태가 제출완료, 안내완료, 처리완료인데 제출일자가 없는 경우 오늘 날짜로 설정
        if (in_array($request->submission_status, ['제출완료', '안내완료', '처리완료']) && !$request->filled('summit_date')) {
            $validated['summit_date'] = date('Y-m-d');
        }
        
        // 해당 사건의 case_idx를 찾기 (있으면 설정)
        $caseAssignment = DB::table('case_assignments')
            ->where('case_number', $request->case_number)
            ->first();
            
        if ($caseAssignment) {
            $validated['case_idx'] = $caseAssignment->case_idx;
        }
        
        // 보정서 생성
        $correction = CorrectionDiv::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => '문서가 등록되었습니다.',
            'data' => $correction
        ]);
    }
    
    /**
     * 사건번호로 사건 정보 검색
     */
    public function searchCase(Request $request)
    {
        $caseNumber = $request->query('case_number');
        
        if (empty($caseNumber)) {
            return response()->json([
                'status' => 'error',
                'message' => '사건번호가 입력되지 않았습니다.'
            ]);
        }
        
        // 현재 로그인한 사용자와 관련된 사건 검색
        $user = Auth::user();
        $member = null;
        
        if ($user) {
            $member = Member::where('name', $user->name)->first();
        }
        
        $query = DB::table('case_assignments')
            ->where('case_number', 'like', "%{$caseNumber}%");
        
        // 관리자가 아닌 경우 본인이 담당하는 사건만 검색
        if ($user && !$user->is_admin && $member) {
            $query->where('case_manager', $member->name);
        }
        
        $cases = $query->get([
            'case_idx',
            'case_number',
            'client_name',
            'court_name',
            'consultant',
            'case_manager'
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $cases
        ]);
    }

    public function update(Request $request, $id)
    {
        $correction = CorrectionDiv::findOrFail($id);
        
        // 유효성 검사 추가 가능
        $validated = $request->validate([
            'document_type' => 'nullable|string',
            'deadline' => 'nullable|date',
            'submission_status' => 'nullable|string',
            'consultant' => 'nullable|string',
            'case_manager' => 'nullable|string',
            'summit_date' => 'nullable|date',
            'command' => 'nullable|string',
        ]);
        
        // submission_status가 제출완료, 안내완료, 처리완료로 변경되었고 
        // summit_date가 제공되지 않은 경우 오늘 날짜로 설정
        if (
            in_array($request->submission_status, ['제출완료', '안내완료', '처리완료']) && 
            !$request->filled('summit_date') &&
            ($correction->submission_status !== $request->submission_status)
        ) {
            $validated['summit_date'] = date('Y-m-d');
        }
        
        $correction->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => '수정되었습니다.',
            'data' => $correction
        ]);
    }

    public function download($path)
    {
        try {
            \Log::info('Download requested with path parameter: ' . $path);
            
            // base64 디코딩 후 URL 디코딩 실행
            $decoded_path = urldecode(base64_decode($path));
            \Log::info('Decoded path: ' . $decoded_path);
            
            // URL 형식의 경로를 실제 서버 경로로 변환
            $real_path = str_replace('/download/', '/home/ec2-user/rbdocs/', $decoded_path);
            \Log::info('Real path: ' . $real_path);
            
            if (!file_exists($real_path)) {
                \Log::error('File not found at path: ' . $real_path);
                return response()->json(['error' => '파일을 찾을 수 없습니다.'], 404);
            }

            // 파일명 추출
            $filename = basename($real_path);
            
            \Log::info('File exists, attempting to download: ' . $filename);
            return response()->file($real_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
            ]);
        } catch (\Exception $e) {
            \Log::error('File download error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => '파일 다운로드 중 오류가 발생했습니다.'], 500);
        }
    }

    /**
     * 문서 삭제 (직접 등록한 데이터만 삭제 가능)
     */
    public function destroy($id)
    {
        $correction = CorrectionDiv::findOrFail($id);
        
        // order가 null인 경우에만 삭제 가능 (직접 등록한 데이터)
        if ($correction->order !== null) {
            return response()->json([
                'success' => false,
                'message' => '직접 등록한 데이터만 삭제 가능합니다.'
            ], 403);
        }
        
        $correction->delete();
        
        return response()->json([
            'success' => true,
            'message' => '문서가 삭제되었습니다.'
        ]);
    }

    /**
     * 담당자별 미제출 문서 수 차트 데이터를 제공합니다.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChartData(Request $request)
    {
        try {
            // 사건관리팀 담당자 목록 조회
            $caseManagers = Member::where('task', '사건관리팀')
                ->where('status', '재직')
                ->orderBy('name')
                ->get(['name']);
            
            // 각 담당자별 미제출 문서 통계 계산
            $managersData = [];
            
            foreach ($caseManagers as $manager) {
                // 담당자별 '보정' 미제출 문서 - 수신됨 (receipt_date가 not null)
                $correctionReceived = CorrectionDiv::where('case_manager', $manager->name)
                    ->where('document_type', '보정')
                    ->where('submission_status', '미제출')
                    ->whereNotNull('receipt_date')
                    ->count();
                
                // 담당자별 '보정' 미제출 문서 - 미수신 (receipt_date가 null)
                $correctionNotReceived = CorrectionDiv::where('case_manager', $manager->name)
                    ->where('document_type', '보정')
                    ->where('submission_status', '미제출')
                    ->whereNull('receipt_date')
                    ->count();
                
                // 담당자별 '명령' 미제출 문서 수 계산
                $orderCount = CorrectionDiv::where('case_manager', $manager->name)
                    ->where('document_type', '명령')
                    ->where('submission_status', '미제출')
                    ->count();
                
                // 담당자별 '기타' 미제출 문서 수 계산
                $etcCount = CorrectionDiv::where('case_manager', $manager->name)
                    ->where('document_type', '기타')
                    ->where('submission_status', '미제출')
                    ->count();
                
                // 담당자별 '예외' 미제출 문서 수 계산
                $exceptionCount = CorrectionDiv::where('case_manager', $manager->name)
                    ->where('document_type', '예외')
                    ->where('submission_status', '미제출')
                    ->count();
                
                // 명령, 기타, 예외 합계
                $otherCount = $orderCount + $etcCount + $exceptionCount;
                
                $managersData[] = [
                    'name' => $manager->name,
                    'correction_received' => $correctionReceived,
                    'correction_not_received' => $correctionNotReceived,
                    'correction' => $correctionReceived + $correctionNotReceived, // 총합도 함께 제공
                    'order' => $orderCount,
                    'etc' => $etcCount,
                    'exception' => $exceptionCount,
                    'others' => $otherCount
                ];
            }
            
            // 보정 문서 수 기준 내림차순 정렬
            usort($managersData, function($a, $b) {
                return $b['correction'] - $a['correction'];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'managers' => $managersData
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