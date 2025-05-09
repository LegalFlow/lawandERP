<?php

namespace App\Http\Controllers;

use App\Models\TaskList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskListController extends Controller
{
    /**
     * 업무 리스트 메인 페이지를 표시합니다.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // 필터링 옵션
        $status = $request->input('status');
        $category = $request->input('category');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $importance = $request->input('importance');
        $priority = $request->input('priority');
        $showAll = $request->input('show_all', false);
        
        // 쿼리 빌더 시작
        $query = TaskList::where('user_id', Auth::id());
        
        // 상태 필터링 (명시적으로 요청된 경우에만 적용)
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        
        // 카테고리 필터링
        if ($category) {
            $query->where('category_type', $category);
        }
        
        // 날짜 범위 필터링
        if ($startDate) {
            $query->where('plan_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('plan_date', '<=', $endDate);
        }
        
        // 중요도 필터링
        if ($importance) {
            $query->where('importance', $importance);
        }
        
        // 우선순위 필터링
        if ($priority) {
            $query->where('priority', $priority);
        }
        
        // 기본 정렬: 계획일자 오름차순
        $sortBy = $request->input('sort_by', 'plan_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $taskLists = $query->get();
        
        // 카테고리 데이터
        $categories = [
            '개발' => ['새 기능 개발', '버그 수정', '리팩토링', '코딩', 'API 개발 및 연동', '시스템 설계', 'DB 설계', 'UI/UX 설계', '단위 테스트', '통합 테스트', 'QA 테스트 지원', '코드 문서화', '기술 문서 작성', 'API 문서 작성', '팀 미팅', '고객 응대', '이해관계자 미팅', '코드 리뷰', '기술 학습', '리서치', 'POC 작업', '배포', '인프라 관리', 'CI/CD 인프라', '모니터링', '성능 최적화', '보안 패치'],
            '회생' => ['신건상담', '신건계약', '방문상담', '서류발급', '신청서작성', '신청서제출', '고객 응대', '법원 응대', '보정서작성', '보정서제출', '기타보정작성', '기타보정제출', '외근', '기타'],
            '지원' => ['급여', '퇴직급여', '입퇴사', '세무', '인사', '면담', '서류작업', '금융', '사대보험', '재무', '기타']
        ];
        
        // 상태 옵션
        $statusOptions = ['진행예정', '진행중', '완료', '보류', '기각'];
        
        // 중요도 옵션
        $importanceOptions = ['매우중요', '중요', '보통', '낮음'];
        
        // 우선순위 옵션
        $priorityOptions = ['최우선순위', '우선순위', '보통', '낮음'];
        
        return view('task_lists.index', [
            'taskLists' => $taskLists,
            'categories' => $categories,
            'statusOptions' => $statusOptions,
            'importanceOptions' => $importanceOptions,
            'priorityOptions' => $priorityOptions,
            'filters' => [
                'status' => $status,
                'category' => $category,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'importance' => $importance,
                'priority' => $priority,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }
    
    /**
     * 새 업무 리스트를 저장합니다.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_date' => 'required|date',
            'deadline' => 'nullable|date',
            'category_type' => 'nullable|string|max:50',
            'category_detail' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|string|max:20',
            'completion_date' => 'nullable|date',
            'importance' => 'nullable|string|max:20',
            'priority' => 'nullable|string|max:20',
            'memo' => 'nullable|string',
        ]);
        
        // 기본값 설정
        if (!$request->has('importance') || empty($request->importance)) {
            $request->merge(['importance' => '보통']);
        }
        
        if (!$request->has('priority') || empty($request->priority)) {
            $request->merge(['priority' => '보통']);
        }
        
        $taskList = new TaskList($request->all());
        $taskList->user_id = Auth::id();
        $taskList->save();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $taskList->id,
                'message' => '업무가 성공적으로 추가되었습니다.'
            ]);
        }
        
        return redirect()->route('task-lists.index')->with('success', '업무가 성공적으로 추가되었습니다.');
    }
    
    /**
     * 업무 리스트를 업데이트합니다.
     */
    public function update(Request $request, $id)
    {
        try {
            $taskList = TaskList::findOrFail($id);
            
            // 권한 확인
            if ($taskList->user_id !== Auth::id()) {
                if ($request->ajax()) {
                    return response()->json(['error' => '권한이 없습니다.'], 403);
                }
                return redirect()->route('task-lists.index')->with('error', '권한이 없습니다.');
            }
            
            $request->validate([
                'plan_date' => 'nullable|date',
                'deadline' => 'nullable|date',
                'category_type' => 'nullable|string|max:50',
                'category_detail' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'status' => 'nullable|string|max:20',
                'completion_date' => 'nullable|date',
                'importance' => 'nullable|string|max:20',
                'priority' => 'nullable|string|max:20',
                'memo' => 'nullable|string',
            ]);
            
            // 카테고리가 변경되었고 하위 카테고리가 비어있는 경우 기본값 설정
            if ($request->has('category_type') && $request->category_type !== $taskList->category_type) {
                // 카테고리에 맞는 하위 카테고리 목록 가져오기
                $categories = [
                    '개발' => ['새 기능 개발', '버그 수정', '리팩토링', '코딩', 'API 개발 및 연동', '시스템 설계', 'DB 설계', 'UI/UX 설계', '단위 테스트', '통합 테스트', 'QA 테스트 지원', '코드 문서화', '기술 문서 작성', 'API 문서 작성', '팀 미팅', '고객 응대', '이해관계자 미팅', '코드 리뷰', '기술 학습', '리서치', 'POC 작업', '배포', '인프라 관리', 'CI/CD 인프라', '모니터링', '성능 최적화', '보안 패치'],
                    '회생' => ['신건상담', '신건계약', '방문상담', '서류발급', '신청서작성', '신청서제출', '고객 응대', '법원 응대', '보정서작성', '보정서제출', '기타보정작성', '기타보정제출', '외근', '기타'],
                    '지원' => ['급여', '퇴직급여', '입퇴사', '세무', '인사', '면담', '서류작업', '금융', '사대보험', '재무', '기타']
                ];
                
                // 선택된 카테고리에 맞는 하위 카테고리가 있는지 확인
                if (isset($categories[$request->category_type]) && !empty($categories[$request->category_type])) {
                    // 하위 카테고리가 비어있거나 이전 카테고리의 하위 카테고리인 경우 새 카테고리의 첫 번째 하위 카테고리로 설정
                    if (empty($request->category_detail) || !in_array($request->category_detail, $categories[$request->category_type])) {
                        $request->merge(['category_detail' => $categories[$request->category_type][0]]);
                    }
                }
            }
            
            // 상태가 '완료'로 변경되었고 완료일자가 없는 경우 현재 날짜로 설정
            if ($request->status === '완료' && !$request->completion_date) {
                $request->merge(['completion_date' => Carbon::today()]);
            }
            
            // 상태가 '완료'가 아니면 완료일자 제거
            if ($request->has('status') && $request->status !== '완료') {
                $request->merge(['completion_date' => null]);
            }
            
            $taskList->update($request->all());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '업무가 성공적으로 수정되었습니다.'
                ]);
            }
            
            return redirect()->route('task-lists.index')->with('success', '업무가 성공적으로 수정되었습니다.');
        } catch (\Exception $e) {
            \Log::error('업무 리스트 업데이트 오류: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            if ($request->ajax()) {
                return response()->json([
                    'error' => '업무 수정 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('task-lists.index')->with('error', '업무 수정 중 오류가 발생했습니다.');
        }
    }
    
    /**
     * 업무 리스트를 삭제합니다.
     */
    public function destroy($id)
    {
        $taskList = TaskList::findOrFail($id);
        
        // 권한 확인
        if ($taskList->user_id !== Auth::id()) {
            if (request()->ajax()) {
                return response()->json(['error' => '권한이 없습니다.'], 403);
            }
            return redirect()->route('task-lists.index')->with('error', '권한이 없습니다.');
        }
        
        $taskList->delete();
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '업무가 성공적으로 삭제되었습니다.'
            ]);
        }
        
        return redirect()->route('task-lists.index')->with('success', '업무가 성공적으로 삭제되었습니다.');
    }
    
    /**
     * 업무 상태를 변경합니다.
     */
    public function updateStatus(Request $request, $id)
    {
        $taskList = TaskList::findOrFail($id);
        
        // 권한 확인
        if ($taskList->user_id !== Auth::id()) {
            return response()->json(['error' => '권한이 없습니다.'], 403);
        }
        
        $request->validate([
            'status' => 'required|string|max:20',
        ]);
        
        $oldStatus = $taskList->status;
        $newStatus = $request->status;
        
        $taskList->status = $newStatus;
        
        // 상태가 '완료'로 변경된 경우 완료일자 설정
        if ($newStatus === '완료' && $oldStatus !== '완료') {
            $taskList->completion_date = Carbon::today();
        }
        
        // 상태가 '완료'에서 다른 상태로 변경된 경우 완료일자 제거
        if ($oldStatus === '완료' && $newStatus !== '완료') {
            $taskList->completion_date = null;
        }
        
        $taskList->save();
        
        return response()->json([
            'success' => true,
            'message' => '상태가 변경되었습니다.',
            'status' => $taskList->status,
            'completion_date' => $taskList->completion_date ? $taskList->completion_date->format('Y-m-d') : null,
        ]);
    }
} 