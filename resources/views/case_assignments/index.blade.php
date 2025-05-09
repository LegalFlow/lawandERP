@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endpush

<div class="bg-gray-50 min-h-screen">
    <div class="container-fluid px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">신건배당 및 보정관리</h1>
            <div class="d-flex gap-2">
                <button id="searchBtn" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="fas fa-search"></i>
                    <span>검색</span>
                </button>
                <button id="filterBtn" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="fas fa-filter"></i>
                    <span>필터</span>
                </button>
                <button id="chartBtn" class="btn btn-outline-secondary d-flex align-items-center gap-1">
                    <i class="fas fa-chart-bar"></i>
                    <span>차트</span>
                </button>
            </div>
        </div>

        <!-- 검색 필터 영역 -->
        <div id="searchFilterArea" class="bg-white shadow rounded-lg mb-4 p-4" style="{{ request()->hasAny(['search_type', 'search_value']) ? 'display: block;' : 'display: none;' }}">
            <form action="{{ route('case-assignments.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <div class="me-2">
                    <select id="searchType" name="search_type" class="form-select">
                        <option value="client_name" {{ request('search_type') == 'client_name' ? 'selected' : '' }}>고객명</option>
                        <option value="case_number" {{ request('search_type') == 'case_number' ? 'selected' : '' }}>사건번호</option>
                    </select>
                </div>
                <div class="flex-fill me-2">
                    <input type="text" name="search_value" value="{{ request('search_value') }}" class="form-control" placeholder="검색어를 입력하세요">
                </div>
                <button type="submit" class="btn btn-primary">
                    검색
                </button>
            </form>
        </div>

        <!-- 상세 필터 영역 -->
        <div id="detailFilterArea" class="bg-white shadow rounded-lg mb-4 p-4" style="{{ request()->hasAny(['start_date', 'end_date', 'consultant', 'case_manager', 'date_type', 'submission_status', 'contract_status']) && !request()->hasAny(['search_type', 'search_value']) ? 'display: block;' : 'display: none;' }}">
            <form action="{{ route('case-assignments.index') }}" method="GET">
                <div class="row g-3 mb-3">
                    <!-- 기간 선택 -->
                    <div class="col-md-4">
                        <label class="form-label">조회 기간</label>
                        <div class="d-flex align-items-center">
                            <select name="date_type" class="form-select me-2">
                                <option value="assignment_date" {{ request('date_type') == 'assignment_date' ? 'selected' : '' }}>배당일</option>
                                <option value="summit_date" {{ request('date_type') == 'summit_date' ? 'selected' : '' }}>신청서 제출일</option>
                            </select>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control me-1">
                            <span class="mx-1">~</span>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                        </div>
                    </div>
                    
                    <!-- 상담자 -->
                    <div class="col-md-4">
                        <label class="form-label">상담자</label>
                        <select name="consultant" class="form-select">
                            <option value="" {{ $consultant === '' ? 'selected' : '' }}>전체</option>
                            @foreach($allMembers as $member)
                                <option value="{{ $member->name }}" {{ $consultant === $member->name ? 'selected' : '' }}>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- 담당자 -->
                    <div class="col-md-4">
                        <label class="form-label">담당자</label>
                        <select name="case_manager" class="form-select">
                            <option value="" {{ $caseManager === '' ? 'selected' : '' }}>전체</option>
                            @foreach($allMembers as $member)
                                <option value="{{ $member->name }}" {{ $caseManager === $member->name ? 'selected' : '' }}>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- 보정제출상태 -->
                    <div class="col-md-4">
                        <label class="form-label">보정제출상태</label>
                        <select name="submission_status" class="form-select">
                            <option value="">전체</option>
                            <option value="미제출" {{ request('submission_status') == '미제출' ? 'selected' : '' }}>미제출</option>
                            <option value="연기신청" {{ request('submission_status') == '연기신청' ? 'selected' : '' }}>연기신청</option>
                            <option value="계약해지" {{ request('submission_status') == '계약해지' ? 'selected' : '' }}>계약해지</option>
                        </select>
                    </div>
                    
                    <!-- 계약상태 -->
                    <div class="col-md-4">
                        <label class="form-label">계약상태</label>
                        <select name="contract_status" class="form-select">
                            <option value="">전체</option>
                            <option value="정상" {{ request('contract_status') == '정상' ? 'selected' : '' }}>정상</option>
                            <option value="계약해지" {{ request('contract_status') == '계약해지' ? 'selected' : '' }}>계약해지</option>
                        </select>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        필터 적용
                    </button>
                </div>
            </form>
        </div>

        <!-- 차트 영역 추가 -->
        <div id="chartArea" class="bg-white shadow rounded-lg mb-4 p-4" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800"></h2>
                <div class="d-flex align-items-center">
                    <label for="chartTimeFilter" class="me-2 text-gray-700"></label>
                    <select
                        id="chartTimeFilter"
                        class="form-select me-2"
                    >
                        <option value="currentMonth">당월</option>
                        <option value="last2Months" selected>최근 2개월</option>
                        <option value="last3Months">최근 3개월</option>
                        <option value="currentQuarter">이번 분기</option>
                        <option value="lastQuarter">지난 분기</option>
                        <option value="currentYear">올해</option>
                        <option value="lastYear">지난해</option>
                    </select>
                    <span id="dateRangeText" class="ms-2 text-sm text-gray-600"></span>
                </div>
            </div>
            
            <!-- 사건 유형 필터 체크박스 추가 -->
            <div class="mb-4">
                <div class="d-flex align-items-center">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input case-type-filter" type="checkbox" id="caseTypeFilter1" value="1" checked>
                        <label class="form-check-label" for="caseTypeFilter1">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">개인회생</span>
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input case-type-filter" type="checkbox" id="caseTypeFilter2" value="2" checked>
                        <label class="form-check-label" for="caseTypeFilter2">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">개인파산</span>
                        </label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input case-type-filter" type="checkbox" id="caseTypeFilter3" value="3">
                        <label class="form-check-label" for="caseTypeFilter3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">기타</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div style="height: 400px;">
                <canvas id="caseAssignmentChart"></canvas>
            </div>
            
            <div class="mt-4 bg-blue-50 p-3 rounded-md">
                <p class="text-sm text-gray-600">
                    * 배당 사건은 담당자에게 할당된 총 사건 수, 제출 사건은 법원에 제출 완료된 사건 수입니다.
                    <br />
                    * 데이터는 선택한 기간을 기준으로 집계되었습니다.
                </p>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg case-assignments-container">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">배당일</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">신청서 제출일</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">고객명</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">사건분야</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">진행현황</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">관할법원</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">사건번호</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상담자</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">담당자</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">보정 제출상태</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">계약상태</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">메모</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($assignments as $assignment)
                        <tr class="case-row hover:bg-gray-50 cursor-pointer" data-id="{{ $assignment->id }}">
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <input type="date" 
                                       class="form-control form-control-sm editable-field border-0 bg-transparent"
                                       data-field="assignment_date"
                                       data-id="{{ $assignment->id }}"
                                       value="{{ $assignment->assignment_date->format('Y-m-d') }}">
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <input type="date" 
                                       class="form-control form-control-sm editable-field border-0 bg-transparent"
                                       data-field="summit_date"
                                       data-id="{{ $assignment->id }}"
                                       value="{{ $assignment->summit_date ? $assignment->summit_date->format('Y-m-d') : '' }}">
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $assignment->client_name }}</td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                @if($assignment->case_type == 1)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">개인회생</span>
                                @elseif($assignment->case_type == 2)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">개인파산</span>
                                @elseif($assignment->case_type == 3)
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">기타</span>
                                @else
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">미지정</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ \App\Helpers\CaseStateHelper::getStateLabel($assignment->case_type, $assignment->case_state) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">{{ $assignment->court_name }}</td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">{{ $assignment->case_number }}</td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">{{ $assignment->consultant }}</td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">{{ $assignment->case_manager }}</td>
                            <td class="px-3 py-4 whitespace-nowrap submission-status">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap contract-status">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button type="button" 
                                        class="btn btn-sm note-icon"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#noteModal{{ $assignment->id }}">
                                    @if(!empty($assignment->notes))
                                    <div class="bg-yellow-400 p-1 rounded-full w-6 h-6 flex items-center justify-center">
                                        <i class="fas fa-exclamation-circle text-white" style="font-size: 14px;"></i>
                                    </div>
                                    @else
                                    <div class="bg-gray-200 p-1 rounded-full w-6 h-6 flex items-center justify-center">
                                        <i class="fas fa-exclamation-circle text-gray-600" style="font-size: 14px;"></i>
                                    </div>
                                    @endif
                                </button>
                                
                                <div class="modal fade" id="noteModal{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content rounded-lg shadow-lg">
                                            <div class="modal-header border-0">
                                                <h5 class="modal-title text-gray-900">메모</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <textarea class="form-control note-modal-input border rounded-lg" 
                                                          data-id="{{ $assignment->id }}" 
                                                          rows="6">{{ $assignment->notes }}</textarea>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light rounded-md" data-bs-dismiss="modal">취소</button>
                                                <button type="button" class="btn btn-primary rounded-md save-note" data-id="{{ $assignment->id }}">저장</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-chevron-down expand-icon"></i>
                                <i class="fas fa-chevron-up collapse-icon" style="display: none;"></i>
                            </td>
                        </tr>
                        <tr class="detail-row" data-id="{{ $assignment->id }}" style="display: none;">
                            <td colspan="13" class="px-6 py-6 bg-gray-50">
                                <div class="loading text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-gray-600">상세 정보를 불러오는 중...</p>
                                </div>
                                <div class="detail-content space-y-6" style="display: none;">
                                    <!-- 고객 정보 섹션 -->
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">고객정보</h3>
                                        <div class="bg-white p-4 rounded-md border border-gray-200 client-info">
                                            <!-- JavaScript로 채워짐 -->
                                        </div>
                                    </div>
                                    
                                    <!-- 보정내역 섹션 -->
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">보정내역</h3>
                                        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto correction-list">
                                            <!-- JavaScript로 채워짐 -->
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        이전
                    </a>
                    <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        다음
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            총 <span class="font-medium">{{ $assignments->total() }}</span> 건 중 
                            <span class="font-medium">{{ $assignments->firstItem() }}</span> - 
                            <span class="font-medium">{{ $assignments->lastItem() }}</span> 표시
                        </p>
                    </div>
                    <div>
                        {{ $assignments->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* 기본 스타일 */
.bg-gray-50 {
    background-color: #f9fafb;
}

.bg-gray-100 {
    background-color: #f3f4f6;
}

.bg-white {
    background-color: #ffffff;
}

/* 검색 및 필터 영역 스타일 */
#searchFilterArea, #detailFilterArea {
    transition: all 0.2s ease-in-out;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

/* 기존 스타일 */
.text-gray-500 {
    color: #6b7280;
}

.text-gray-600 {
    color: #4b5563;
}

.text-gray-700 {
    color: #374151;
}

.text-gray-800 {
    color: #1f2937;
}

.text-gray-900 {
    color: #111827;
}

.bg-blue-100 {
    background-color: #dbeafe;
}

.text-blue-800 {
    color: #1e40af;
}

.bg-green-100 {
    background-color: #d1fae5;
}

.text-green-800 {
    color: #065f46;
}

.bg-purple-100 {
    background-color: #ede9fe;
}

.text-purple-800 {
    color: #5b21b6;
}

.bg-yellow-100 {
    background-color: #fef3c7;
}

.bg-yellow-400 {
    background-color: #fbbf24;
}

.text-yellow-800 {
    color: #92400e;
}

.bg-red-100 {
    background-color: #fee2e2;
}

.text-red-800 {
    color: #991b1b;
}

.border-gray-200 {
    border-color: #e5e7eb;
}

.border-gray-300 {
    border-color: #d1d5db;
}

.shadow {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.rounded-md {
    border-radius: 0.375rem;
}

.rounded-lg {
    border-radius: 0.5rem;
}

.rounded-full {
    border-radius: 9999px;
}

.font-medium {
    font-weight: 500;
}

.font-semibold {
    font-weight: 600;
}

.text-xs {
    font-size: 0.75rem;
    line-height: 1rem;
}

.text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.text-lg {
    font-size: 1.125rem;
    line-height: 1.75rem;
}

.text-2xl {
    font-size: 1.5rem;
    line-height: 2rem;
}

.tracking-wider {
    letter-spacing: 0.05em;
}

.uppercase {
    text-transform: uppercase;
}

.whitespace-nowrap {
    white-space: nowrap;
}

.space-x-2 > * + * {
    margin-left: 0.5rem;
}

.space-y-6 > * + * {
    margin-top: 1.5rem;
}

.gap-2 {
    gap: 0.5rem;
}

.px-2 {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.px-3 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

.px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
}

.px-6 {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}

.py-1 {
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}

.py-2 {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}

.py-3 {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}

.py-4 {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.py-6 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}

.mb-3 {
    margin-bottom: 0.75rem;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

.mt-2 {
    margin-top: 0.5rem;
}

.ml-3 {
    margin-left: 0.75rem;
}

.flex {
    display: flex;
}

.inline-flex {
    display: inline-flex;
}

.w-6 {
    width: 1.5rem;
}

.h-6 {
    height: 1.5rem;
}

.min-h-screen {
    min-height: 100vh;
}

.min-w-full {
    min-width: 100%;
}

.flex-1 {
    flex: 1 1 0%;
}

.items-center {
    align-items: center;
}

.justify-center {
    justify-content: center;
}

.justify-between {
    justify-content: space-between;
}

.overflow-x-auto {
    overflow-x: auto;
}

.divide-y > * + * {
    border-top-width: 1px;
}

.divide-gray-200 > * + * {
    border-color: #e5e7eb;
}

.border {
    border-width: 1px;
}

.border-0 {
    border-width: 0;
}

.border-t {
    border-top-width: 1px;
}

.hover\:bg-gray-50:hover {
    background-color: #f9fafb;
}

.cursor-pointer {
    cursor: pointer;
}

.relative {
    position: relative;
}

.case-row:hover {
    background-color: #f9fafb;
}

.case-row.active-row {
    background-color: #f3f4f6;
}

.note-icon {
    background: transparent;
    border: none;
    padding: 0;
}

/* 페이지네이션 스타일 커스텀 */
.pagination {
    display: inline-flex;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.page-item .page-link {
    position: relative;
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    margin-left: -1px;
    color: #4b5563;
    background-color: #fff;
    border: 1px solid #e5e7eb;
}

.page-item:first-child .page-link {
    margin-left: 0;
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
}

.page-item:last-child .page-link {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
}

.page-item.active .page-link {
    z-index: 3;
    color: #fff;
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.page-item.disabled .page-link {
    color: #9ca3af;
    pointer-events: none;
    background-color: #fff;
    border-color: #e5e7eb;
}

/* TailwindCSS 개별 유틸리티 클래스 */
@media (min-width: 640px) {
    .sm\:hidden {
        display: none;
    }
    
    .sm\:flex {
        display: flex;
    }
    
    .sm\:flex-1 {
        flex: 1 1 0%;
    }
    
    .sm\:items-center {
        align-items: center;
    }
    
    .sm\:justify-between {
        justify-content: space-between;
    }
}

@media (max-width: 640px) {
    .hidden {
        display: none;
    }
}

/* 필드 스타일 */
.form-control.border-0.bg-transparent {
    background-color: transparent !important;
    border: none !important;
    padding: 0;
    box-shadow: none !important;
}

.form-control.border-0.bg-transparent:focus {
    background-color: white !important;
    border: 1px solid #e5e7eb !important;
    padding: 0.25rem 0.5rem;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
}

/* 상세 패널 메모 버튼 스타일 */
.memo-btn {
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

.memo-btn:hover {
    background-color: #f3f4f6;
}

.memo-btn.has-notes i {
    color: #fbbf24;
}

.memo-btn.no-notes i {
    color: #9ca3af;
}

/* 파일 다운로드 버튼 */
.file-download {
    color: #3b82f6;
    transition: all 0.2s;
}

.file-download:hover {
    color: #2563eb;
}
</style>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    // 검색 버튼 클릭 이벤트 처리
    $('#searchBtn').on('click', function() {
        if ($('#detailFilterArea').is(':visible')) {
            $('#detailFilterArea').slideUp(200);
        }
        if ($('#chartArea').is(':visible')) {
            $('#chartArea').slideUp(200);
        }
        $('#searchFilterArea').slideToggle(300);
    });
    
    // 필터 버튼 클릭 이벤트 처리
    $('#filterBtn').on('click', function() {
        if ($('#searchFilterArea').is(':visible')) {
            $('#searchFilterArea').slideUp(200);
        }
        if ($('#chartArea').is(':visible')) {
            $('#chartArea').slideUp(200);
        }
        $('#detailFilterArea').slideToggle(300);
    });
    
    // 차트 버튼 클릭 이벤트 처리 추가
    $('#chartBtn').on('click', function() {
        if ($('#searchFilterArea').is(':visible')) {
            $('#searchFilterArea').slideUp(200);
        }
        if ($('#detailFilterArea').is(':visible')) {
            $('#detailFilterArea').slideUp(200);
        }
        $('#chartArea').slideToggle(300, function() {
            if ($(this).is(':visible')) {
                loadChartData(); // 차트 데이터 로드
            }
        });
    });
    
    // 차트 기간 필터 변경 이벤트
    $('#chartTimeFilter').on('change', function() {
        loadChartData(); // 필터 변경 시 차트 데이터 다시 로드
    });
    
    // 사건 유형 체크박스 변경 이벤트
    $('.case-type-filter').on('change', function() {
        loadChartData(); // 필터 변경 시 차트 데이터 다시 로드
    });
    
    // 페이지 로드시 진행현황 배지 스타일 적용
    applyProgressBadgeStyles();
    
    // 초기 로드 시 상태 정보 로드
    loadStatusForVisibleRows();
    
    // 페이지네이션 후에도 배지 스타일 적용
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        
        var url = $(this).attr('href');
        // URL의 프로토콜을 현재 페이지의 프로토콜로 맞추기
        if (url.startsWith('http:') && window.location.protocol === 'https:') {
            url = url.replace('http:', 'https:');
        } else if (url.startsWith('https:') && window.location.protocol === 'http:') {
            url = url.replace('https:', 'http:');
        }
        window.history.pushState("", "", url);
        
        loadPage(url);
    });
    
    function loadPage(url) {
        $.get(url).done(function(data) {
            $('.case-assignments-container').html($(data).find('.case-assignments-container').html());
            applyProgressBadgeStyles();
            loadStatusForVisibleRows();
        });
    }
    
    // 진행현황 배지 스타일 적용 함수
    function applyProgressBadgeStyles() {
        // 모든 진행현황 배지에 단계별 스타일 적용
        $('.case-row').each(function() {
            const $row = $(this);
            const caseType = getCaseTypeFromBadge($row);
            const stateText = $row.find('td:nth-child(5) span').text().trim();
            const stateValue = getStateValueFromText(caseType, stateText);
            
            if (stateValue) {
                const badgeClass = getStateBadgeClass(caseType, stateValue);
                // 모든 기존 배경색 및 텍스트 색상 클래스 제거
                $row.find('td:nth-child(5) span').removeClass('bg-green-100 bg-yellow-100 bg-red-100 bg-gray-100 text-green-800 text-yellow-800 text-red-800 text-gray-800')
                                               .addClass(badgeClass);
                
                console.log(`행 ${$row.data('id')}에 배지 클래스 ${badgeClass} 적용됨`);
            }
        });
    }
    
    // 배지 클래스명 가져오기
    function getStateBadgeClass(caseType, stateValue) {
        // 기본 클래스 (회색)
        let badgeClass = 'bg-gray-100 text-gray-800';
        
        // 개인회생 (1)
        if (caseType == 1) {
            // 상담~신청서 작성 단계 (초기 단계) - 초록색 계열
            if ([5, 10, 11, 15, 20, 21, 22, 25].includes(Number(stateValue))) {
                badgeClass = 'bg-green-100 text-green-800';
            }
            // 신청서 제출~보정기간 (중간 단계) - 노란색 계열
            else if ([30, 35, 40].includes(Number(stateValue))) {
                badgeClass = 'bg-yellow-100 text-yellow-800';
            }
            // 개시결정 이후 (후기 단계) - 붉은색 계열
            else if ([45, 50, 55].includes(Number(stateValue))) {
                badgeClass = 'bg-red-100 text-red-800';
            }
        }
        // 개인파산 (2)
        else if (caseType == 2) {
            // 상담~신청서 작성 단계 (초기 단계) - 초록색 계열
            if ([5, 10, 11, 15, 20, 21, 22, 25].includes(Number(stateValue))) {
                badgeClass = 'bg-green-100 text-green-800';
            }
            // 신청서 제출~보정기간 (중간 단계) - 노란색 계열
            else if ([30, 40].includes(Number(stateValue))) {
                badgeClass = 'bg-yellow-100 text-yellow-800';
            }
            // 파산선고 이후 (후기 단계) - 붉은색 계열
            else if ([100, 105, 110, 115, 120, 125].includes(Number(stateValue))) {
                badgeClass = 'bg-red-100 text-red-800';
            }
        }
        // 기타 사건 (3)
        else if (caseType == 3) {
            // 초기단계 - 초록색 계열
            if ([5, 10, 15, 20].includes(Number(stateValue))) {
                badgeClass = 'bg-green-100 text-green-800';
            }
            // 중기단계 - 노란색 계열
            else if ([30].includes(Number(stateValue))) {
                badgeClass = 'bg-yellow-100 text-yellow-800';
            }
            // 말기단계 - 붉은색 계열
            else if ([50].includes(Number(stateValue))) {
                badgeClass = 'bg-red-100 text-red-800';
            }
        }
        
        console.log(`사건유형: ${caseType}, 상태값: ${stateValue}, 적용된 배지 클래스: ${badgeClass}`);
        return badgeClass;
    }
    
    // 사건 분야(유형) 가져오기
    function getCaseTypeFromBadge($row) {
        const caseTypeText = $row.find('td:nth-child(4) span').text().trim();
        if (caseTypeText === '개인회생') return 1;
        if (caseTypeText === '개인파산') return 2;
        if (caseTypeText === '기타') return 3;
        return 0; // 미지정
    }
    
    // 상태 텍스트에서 상태값 가져오기
    function getStateValueFromText(caseType, stateText) {
        const revivalStates = {
            '상담대기': 5,
            '상담완료': 10,
            '재상담필요': 11,
            '계약': 15,
            '서류준비': 20,
            '부채증명서 발급중': 21,
            '부채증명서 발급완료': 22,
            '신청서 작성 진행중': 25,
            '신청서 제출': 30,
            '금지명령': 35,
            '보정기간': 40,
            '개시결정': 45,
            '채권자 집회기일': 50,
            '인가결정': 55
        };
        
        const bankruptcyStates = {
            '상담대기': 5,
            '상담완료': 10,
            '재상담필요': 11,
            '계약': 15,
            '서류준비': 20,
            '부채증명서 발급중': 21,
            '부채증명서 발급완료': 22,
            '신청서 작성 진행중': 25,
            '신청서 제출': 30,
            '보정기간': 40,
            '파산선고': 100,
            '의견청취기일': 105,
            '재산환가 및 배당': 110,
            '파산폐지': 115,
            '면책결정': 120,
            '면책불허가': 125
        };
        
        const otherCaseStates = {
            '상담대기': 5,
            '상담완료': 10,
            '계약': 15,
            '서류준비': 20,
            '진행중': 30,
            '종결': 50
        };
        
        if (caseType == 1) {
            return revivalStates[stateText];
        } else if (caseType == 2) {
            return bankruptcyStates[stateText];
        } else if (caseType == 3) {
            return otherCaseStates[stateText];
        }
        
        return null;
    }

    // 메모 아이콘 클릭 이벤트 - 이벤트 전파 중지
    $(document).on('click', '.note-icon', function(e) {
        e.stopPropagation();
    });
    
    // 메모 모달 관련 이벤트
    $(document).on('hide.bs.modal', '[id^="noteModal"]', function(e) {
        e.stopPropagation();
    });
    
    $(document).on('click', '.modal-dialog', function(e) {
        e.stopPropagation();
    });
    
    // 행 클릭 이벤트 (상세 패널 토글)
    $(document).on('click', '.case-row', function(e) {
        // 클릭된 요소가 수정 가능한 필드나 버튼인 경우, 이벤트 전파 중지
        if ($(e.target).closest('.editable-field, .note-icon, [data-bs-toggle="modal"], .memo-btn').length) {
            return;
        }
        
        const id = $(this).data('id');
        const detailRow = $(`.detail-row[data-id="${id}"]`);
        
        // 다른 열린 상세 패널 닫기
        $('.detail-row').not(detailRow).hide();
        $('.case-row').not(this).removeClass('active-row');
        $('.case-row').not(this).find('.expand-icon').show();
        $('.case-row').not(this).find('.collapse-icon').hide();
        
        // 현재 행 토글
        $(this).toggleClass('active-row');
        
        // 아이콘 토글
        if (detailRow.is(':visible')) {
            $(this).find('.expand-icon').show();
            $(this).find('.collapse-icon').hide();
        } else {
            $(this).find('.expand-icon').hide();
            $(this).find('.collapse-icon').show();
        }
        
        // 상세 패널 토글
        if (detailRow.is(':visible')) {
            detailRow.hide();
        } else {
            detailRow.show();
            loadDetailContent(id);
        }
    });
    
    // 상세 내용 로드 함수
    function loadDetailContent(id) {
        const detailRow = $(`.detail-row[data-id="${id}"]`);
        const loading = detailRow.find('.loading');
        const content = detailRow.find('.detail-content');
        
        // 이미 로드된 경우 다시 로드하지 않음
        if (content.data('loaded')) {
            return;
        }
        
        loading.show();
        content.hide();
        
        $.ajax({
            url: `/case-assignments/${id}/detail`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // 고객 정보 구성
                    const clientInfoHtml = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="row w-100 mx-0">
                                <div class="col-md-3 mb-2">
                                    <p class="small fw-medium text-muted">고객명</p>
                                    <p class="mt-1 small text-dark">${data.assignment.client_name}</p>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <p class="small fw-medium text-muted">전화번호</p>
                                    <p class="mt-1 small text-dark">${data.phoneNumber || '-'}</p>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <p class="small fw-medium text-muted">관할법원</p>
                                    <p class="mt-1 small text-dark">${data.assignment.court_name || '-'}</p>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <p class="small fw-medium text-muted">사건번호</p>
                                    <p class="mt-1 small text-dark">${data.assignment.case_number || '-'}</p>
                                </div>
                            </div>
                            <button type="button" 
                                class="btn btn-sm btn-danger ms-3 delete-assignment"
                                style="width: auto; height: auto; min-width: 60px;"
                                data-assignment-id="${data.assignment.id}">
                                삭제
                            </button>
                        </div>
                    `;
                    detailRow.find('.client-info').html(clientInfoHtml);
                    
                    // 보정내역 구성
                    let correctionsHtml = `
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">발송일자</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">수신일자</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">송달문서</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">분류</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제출기한</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제출여부</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제출일자</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">파일</th>
                                    <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">메모</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                    `;
                    
                    if (data.corrections && data.corrections.length > 0) {
                        data.corrections.forEach(correction => {
                            let submissionStatusBadge = '';
                            const status = correction.submission_status || '미제출';
                            
                            // 제출상태에 따른 배지 스타일
                            let badgeClass = 'bg-gray-100 text-gray-800';
                            
                            if (status === '제출완료') {
                                badgeClass = 'bg-green-100 text-green-800';
                            } else if (status === '미제출') {
                                badgeClass = 'bg-yellow-100 text-yellow-800';
                            } else if (status === '연기신청') {
                                badgeClass = 'bg-blue-100 text-blue-800';
                            } else if (status === '계약해지') {
                                badgeClass = 'bg-red-100 text-red-800';
                            }
                            
                            submissionStatusBadge = `<span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">${status}</span>`;
                            
                            correctionsHtml += `
                                <tr>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.shipment_date || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.receipt_date || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.document_name || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.document_type || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.deadline || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap">${submissionStatusBadge}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">${correction.summit_date || '-'}</td>
                                    <td class="px-3 py-4 whitespace-nowrap text-center">
                                        ${correction.pdf_path ?
                                            `<a href="/correction-div/download/${btoa(encodeURIComponent(correction.pdf_path))}" class="file-download" target="_blank">
                                                <i class="fas fa-file-download"></i>
                                            </a>` :
                                            `<i class="fas fa-file-download"></i>`
                                        }
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-center">
                                        <button class="p-1 rounded ${correction.command ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'} memo-btn"
                                              data-id="${correction.id}" 
                                              data-memo="${correction.command || ''}"
                                              data-bs-toggle="modal" 
                                              data-bs-target="#correctionMemoModal">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        correctionsHtml += `
                            <tr>
                                <td colspan="9" class="px-3 py-4 text-center text-sm text-gray-500">보정내역이 없습니다.</td>
                            </tr>
                        `;
                    }
                    
                    correctionsHtml += `</tbody></table>`;
                    detailRow.find('.correction-list').html(correctionsHtml);
                    
                    // 상세 컨텐츠 표시
                    loading.hide();
                    content.show();
                    content.data('loaded', true);
                    
                    // 삭제 버튼 이벤트 바인딩
                    detailRow.find('.delete-assignment').click(function(e) {
                        e.stopPropagation();
                        deleteAssignment($(this).data('assignment-id'));
                    });
                    
                    // 메모 버튼 이벤트 바인딩
                    detailRow.find('.memo-btn').click(function(e) {
                        e.stopPropagation();
                        const memoId = $(this).data('id');
                        const memoText = $(this).data('memo');
                        
                        // 메모 모달 내용 설정
                        $('#correctionMemoModal').data('memo-id', memoId);
                        $('#correctionMemoText').val(memoText);
                    });
                } else {
                    detailRow.find('.loading').html(`
                        <div class="bg-red-50 p-4 rounded-md text-red-800">
                            ${response.message}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                detailRow.find('.loading').html(`
                    <div class="bg-red-50 p-4 rounded-md text-red-800">
                        상세 정보를 불러오는 중 오류가 발생했습니다.
                    </div>
                `);
                console.error(xhr);
            }
        });
    }
    
    // 상태 정보 로드 함수
    function loadStatusForVisibleRows() {
        const visibleIds = $('.case-row:visible').map(function() {
            return $(this).data('id');
        }).get();
        
        if (visibleIds.length === 0) return;
        
        $.ajax({
            url: '/case-assignments/bulk-status',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                ids: visibleIds
            },
            success: function(response) {
                if (response.success) {
                    Object.entries(response.data).forEach(([id, status]) => {
                        const $row = $(`.case-row[data-id="${id}"]`);
                        // 제출상태를 배지로 표시하고, 보정내역이 없으면 비워두기
                        if (status.submissionStatus) {
                            let badgeClass = '';
                            switch (status.submissionStatus) {
                                case '계약해지':
                                    badgeClass = 'bg-red-100 text-red-800';
                                    break;
                                case '미제출':
                                    badgeClass = 'bg-yellow-100 text-yellow-800';
                                    break;
                                case '연기신청':
                                    badgeClass = 'bg-blue-100 text-blue-800';
                                    break;
                                case '제출완료':
                                    badgeClass = 'bg-green-100 text-green-800';
                                    break;
                                default:
                                    badgeClass = 'bg-gray-100 text-gray-800';
                            }
                            
                            if (badgeClass) {
                                $row.find('.submission-status').html(
                                    `<span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">${status.submissionStatus}</span>`
                                );
                            } else {
                                $row.find('.submission-status').text('');
                            }
                        } else {
                            $row.find('.submission-status').text('');
                        }
                        
                        // 계약상태를 배지로 표시
                        if (status.contractStatus === '정상') {
                            $row.find('.contract-status').html(
                                `<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">정상</span>`
                            );
                        } else if (status.contractStatus === '계약해지') {
                            $row.find('.contract-status').html(
                                `<span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">계약해지</span>`
                            );
                        } else {
                            $row.find('.contract-status').text(status.contractStatus || '');
                        }
                        
                        console.log(`행 ${id}의 제출상태: ${status.submissionStatus}, 계약상태: ${status.contractStatus}`);
                    });
                }
            },
            error: function(xhr) {
                console.error('상태 정보 로드 중 오류 발생:', xhr);
            }
        });
    }
    
    // 삭제 기능
    function deleteAssignment(id) {
        if (confirm('정말 삭제하시겠습니까?')) {
            $.ajax({
                url: `/case-assignments/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('삭제되었습니다.');
                        location.reload();
                    } else {
                        alert('삭제 실패: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('오류가 발생했습니다.');
                    console.error(xhr);
                }
            });
        }
    }
    
    // 날짜 필드 변경 이벤트 처리
    $(document).on('change', '.editable-field', function() {
        const id = $(this).data('id');
        const field = $(this).data('field');
        const value = $(this).val();
        
        // 필드 타입에 따라 메시지 설정
        let fieldName = '';
        if (field === 'assignment_date') fieldName = '배당일';
        else if (field === 'summit_date') fieldName = '신청서 제출일';
        else fieldName = field;
        
        $.ajax({
            url: `/case-assignments/${id}/update-field`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                field: field,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    console.log(`${field} 필드가 ${value}로 업데이트되었습니다.`);
                    
                    // 토스트 알림 표시
                    $('#saveToast .toast-body').text(`${fieldName} 정보가 저장되었습니다.`);
                    var toast = new bootstrap.Toast(document.getElementById('saveToast'));
                    toast.show();
                } else {
                    alert(`업데이트 실패: ${response.message}`);
                }
            },
            error: function(xhr) {
                alert('업데이트 중 오류가 발생했습니다.');
                console.error(xhr);
            }
        });
    });

    // 메모 저장
    $(document).on('click', '.save-note', function() {
        const id = $(this).data('id');
        const notes = $(`#noteModal${id} .note-modal-input`).val();
        
        $.ajax({
            url: `/case-assignments/${id}/update-field`,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                field: 'notes',
                value: notes
            },
            success: function(response) {
                if (response.success) {
                    // 메모 아이콘 색상 업데이트
                    const $noteIcon = $(`.note-icon[data-bs-target="#noteModal${id}"]`);
                    const $iconDiv = $noteIcon.find('div');
                    
                    if (notes && notes.trim() !== '') {
                        // 메모가 있으면 노란색 배경으로 변경
                        $iconDiv.removeClass('bg-gray-200');
                        $iconDiv.addClass('bg-yellow-400');
                        
                        // 아이콘 색상 변경
                        $iconDiv.find('i').removeClass('text-gray-600');
                        $iconDiv.find('i').addClass('text-white');
                    } else {
                        // 메모가 없으면 회색 배경으로 변경
                        $iconDiv.removeClass('bg-yellow-400');
                        $iconDiv.addClass('bg-gray-200');
                        
                        // 아이콘 색상 변경
                        $iconDiv.find('i').removeClass('text-white');
                        $iconDiv.find('i').addClass('text-gray-600');
                    }
                    
                    // 모달 닫기
                    $(`#noteModal${id}`).modal('hide');
                } else {
                    alert('메모 저장 실패: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('메모 저장 중 오류가 발생했습니다.');
                console.error(xhr);
            }
        });
    });

    // 차트 관련 코드
    let caseAssignmentChart = null; // 차트 인스턴스 저장용
    
    // 날짜 범위 계산 함수
    function getDateRange(filterType) {
        const today = new Date();
        const currentYear = today.getFullYear();
        const currentMonth = today.getMonth();
        const currentDate = today.getDate();
        
        let startDate = new Date();
        let endDate = new Date(today);
        
        switch(filterType) {
            case 'currentMonth': // 당월
                startDate = new Date(currentYear, currentMonth, 1);
                break;
            case 'last2Months': // 최근 2개월
                startDate = new Date(currentYear, currentMonth - 1, 1);
                break;
            case 'last3Months': // 최근 3개월
                startDate = new Date(currentYear, currentMonth - 2, 1);
                break;
            case 'currentQuarter': // 이번 분기
                const currentQuarter = Math.floor(currentMonth / 3);
                startDate = new Date(currentYear, currentQuarter * 3, 1);
                break;
            case 'lastQuarter': // 지난 분기
                const lastQuarter = Math.floor(currentMonth / 3) - 1;
                const lastQuarterYear = lastQuarter < 0 ? currentYear - 1 : currentYear;
                const lastQuarterStartMonth = lastQuarter < 0 ? 9 : lastQuarter * 3;
                startDate = new Date(lastQuarterYear, lastQuarterStartMonth, 1);
                endDate = new Date(lastQuarterYear, lastQuarterStartMonth + 3, 0);
                break;
            case 'currentYear': // 올해
                startDate = new Date(currentYear, 0, 1);
                break;
            case 'lastYear': // 지난해
                startDate = new Date(currentYear - 1, 0, 1);
                endDate = new Date(currentYear - 1, 11, 31);
                break;
            default:
                startDate = new Date(currentYear, currentMonth - 1, 1);
        }
        
        const formatDate = (date) => {
            return `${date.getFullYear()}년 ${date.getMonth() + 1}월 ${date.getDate()}일`;
        };
        
        return {
            start: formatDate(startDate),
            end: formatDate(endDate),
            startDate: formatDate(startDate).replace(/년|월|일/g, ''),
            endDate: formatDate(endDate).replace(/년|월|일/g, '')
        };
    }
    
    // 차트 데이터 로딩 함수
    function loadChartData() {
        const filterType = $('#chartTimeFilter').val();
        const dateRange = getDateRange(filterType);
        
        // 선택된 사건 유형 필터 가져오기
        const selectedCaseTypes = $('.case-type-filter:checked').map(function() {
            return $(this).val();
        }).get();
        
        // 기간 표시 업데이트
        $('#dateRangeText').text(`${dateRange.start} ~ ${dateRange.end}`);
        
        // API 요청을 통해 데이터 가져오기
        $.ajax({
            url: '/case-assignments/chart-data',
            type: 'GET',
            data: {
                filterType: filterType,
                startDate: dateRange.startDate,
                endDate: dateRange.endDate,
                caseTypes: selectedCaseTypes // 선택된 사건 유형 전달
            },
            success: function(response) {
                if (response.success) {
                    renderChart(response.data);
                } else {
                    console.error('차트 데이터를 불러오는데 실패했습니다.', response.message);
                }
            },
            error: function(xhr) {
                console.error('차트 데이터를 요청하는 중 오류가 발생했습니다.', xhr);
            }
        });
    }
    
    // 차트 렌더링 함수
    function renderChart(data) {
        const ctx = document.getElementById('caseAssignmentChart').getContext('2d');
        
        // 기존 차트가 있으면 제거
        if (caseAssignmentChart) {
            caseAssignmentChart.destroy();
        }
        
        // 데이터 준비
        const labels = data.managers.map(manager => manager.name);
        const assignedData = data.managers.map(manager => manager.assigned);
        const submittedData = data.managers.map(manager => manager.submitted);
        
        // 차트 생성
        caseAssignmentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '배당 사건',
                        data: assignedData,
                        backgroundColor: '#9DB2FF',
                        borderColor: '#8DA2EF',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7
                    },
                    {
                        label: '제출 사건',
                        data: submittedData,
                        backgroundColor: '#ADE8B4',
                        borderColor: '#9DD8A4',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}건`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '사건 수'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '건';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<!-- 보정서 메모 모달 -->
<div class="modal fade" id="correctionMemoModal" tabindex="-1" aria-labelledby="correctionMemoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-lg shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title text-lg font-medium text-gray-900" id="correctionMemoModalLabel">보정서 메모</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" id="correctionMemoText" rows="10"></textarea>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200" data-bs-dismiss="modal">취소</button>
                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700" id="saveCorrectionMemoBtn">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 저장 완료 토스트 알림 -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                저장되었습니다.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endpush 
