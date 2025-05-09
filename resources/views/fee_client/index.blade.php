@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/fee-client.css') }}" rel="stylesheet">
<style>
    /* 파스텔톤 배경색 스타일 - 우선순위 강화 */
    tr.client-row.bg-soft-success {
        background-color: rgba(25, 135, 84, 0.15) !important;
    }

    tr.client-row.bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.15) !important;
    }

    /* 호버 효과 유지 */
    tr.client-row.bg-soft-success:hover {
        background-color: rgba(25, 135, 84, 0.25) !important;
    }

    tr.client-row.bg-soft-danger:hover {
        background-color: rgba(220, 53, 69, 0.25) !important;
    }

    /* 뱃지 스타일 - 리액트 샘플 참조 */
    .badge-status {
        padding: 0.35em 0.65em;
        border-radius: 0.9rem;
        font-size: 0.75em;
        font-weight: 500;
    }
    .badge-completed {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    .badge-pending {
        background-color: #fff3cd;
        color: #664d03;
    }
    .badge-overdue {
        background-color: #f8d7da;
        color: #842029;
    }
    .badge-document-default {
        background-color: #e9ecef;
        color: #495057;
    }
    .badge-document-requested {
        background-color: #cfe2ff;
        color: #084298;
    }
    .badge-document-completed {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    
    /* 진행 표시기 개선 - 리액트 샘플 참조 */
    .doc-progress {
        height: 8px;
        width: 100%;
        max-width: 96px;
        background-color: #e9ecef;
        border-radius: 9999px;
        position: relative;
        display: inline-block;
        margin-right: 8px;
        overflow: hidden;
    }
    .doc-progress-bar {
        height: 100%;
        border-radius: 9999px;
        position: absolute;
        left: 0;
    }
    .doc-progress-text {
        font-size: 12px;
        margin-top: 2px;
        display: inline-block;
        vertical-align: middle;
    }
    
    /* 테이블 스타일 개선 */
    .table-hover tbody tr {
        cursor: pointer;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
    }
    .table thead th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    /* 롤러블 행 스타일 개선 */
    .detail-row {
        background-color: #f8f9fa;
    }
    .client-detail-container {
        padding: 16px 24px;
        border-bottom: 1px solid #dee2e6;
    }
    tr.client-row:hover {
        background-color: rgba(0, 0, 0, 0.075);
    }
    
    /* 상세 정보 영역에서는 기본 커서 사용 */
    .client-detail-container {
        cursor: default;
    }
    .client-detail-content {
        cursor: default;
    }
    /* 상세 정보 영역 내 버튼과 클릭 가능한 요소들에는 pointer 커서 유지 */
    .client-detail-container .btn,
    .client-detail-container input[type="checkbox"],
    .client-detail-container input[type="radio"],
    .client-detail-container select,
    .client-detail-container label {
        cursor: pointer;
    }
    
    /* 상세 정보 내 테이블과 셀에 기본 커서 적용 */
    .client-detail-container table,
    .client-detail-container th,
    .client-detail-container td {
        cursor: default !important;
    }
    
    /* 상세 정보 내 메모 버튼에만 포인터 커서 */
    .client-detail-container .memo-btn {
        cursor: pointer !important;
    }
    
    /* 버튼 스타일 개선 */
    .btn-primary {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    .btn-primary:hover {
        background-color: #2563eb;
        border-color: #2563eb;
    }
    .payment-status-btn.btn-warning {
        background-color: #fff3cd;
        color: #664d03;
        border-color: #ffeeba;
    }
    .payment-status-btn.btn-danger {
        background-color: #f8d7da;
        color: #842029;
        border-color: #f5c2c7;
    }
    
    /* 클라이언트 상세 정보 개선 */
    .client-detail-content h5 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #374151;
    }
    .client-detail-content .fw-bold {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    /* 체크박스 그룹 개선 */
    .form-check-inline {
        margin-right: 1rem;
    }
    .form-check-input:checked {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    
    /* 테이블 내 폰트 크기 조정 */
    .table {
        font-size: 0.875rem;
    }
    
    /* 카드 헤더 스타일 개선 */
    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.125);
        padding: 1rem 1.25rem;
    }
    .card-header h5 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
    }
    
    /* 페이지네이션 스타일 개선 */
    .pagination .page-link {
        color: #3b82f6;
        border-radius: 0.25rem;
        margin: 0 2px;
    }
    .pagination .page-item.active .page-link {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    
    /* 필터 영역 스타일 개선 */
    .filter-area, .search-area {
        padding: 1rem;
        background-color: #f9fafb;
        border-radius: 0.5rem;
    }
    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
    }
    
    /* 모달 스타일 개선 */
    .modal-header {
        background-color: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    .modal-header .modal-title {
        font-weight: 600;
        color: #111827;
    }
    .modal-footer {
        background-color: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    /* 수임료 캘린더 링크 스타일 */
    .calendar-link-alert {
        background-color: #e8f4fd;
        border-color: #b8daff;
    }
    .calendar-link-alert a {
        font-weight: 500;
        color: #0d6efd;
        text-decoration: underline;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 수임료 캘린더 이동 안내 알림 추가 -->
            <div class="alert calendar-link-alert mb-3" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                고객별 수임료 페이지에서는 납부 상태를 확인만 할 수 있습니다. 
                납부 상태 변경은 <a href="{{ route('fee-calendar.index') }}">수임료 캘린더</a> 페이지에서 진행해 주세요.
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">고객별 수임료 납부현황</h5>
                    <div class="d-flex">
                        <div class="btn-group me-2" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFilter">
                                <i class="bi bi-funnel"></i> 필터
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSearch">
                                <i class="bi bi-search"></i> 검색
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 필터 영역 -->
                    <div class="filter-area mb-3 d-none" id="filterArea">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">납부상태</label>
                                <select class="form-select form-select-sm" id="filterPaymentStatus">
                                    <option value="all">전체</option>
                                    <option value="completed">완납</option>
                                    <option value="pending">미납</option>
                                    <option value="overdue">연체</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">계약상태</label>
                                <select class="form-select form-select-sm" id="filterContractStatus">
                                    <option value="all">전체</option>
                                    <option value="normal">정상</option>
                                    <option value="terminated">계약해지</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">사건분야</label>
                                <select class="form-select form-select-sm" id="filterCaseType">
                                    <option value="all">전체</option>
                                    <option value="1">개인회생</option>
                                    <option value="2">개인파산</option>
                                    <option value="3">기타사건</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">상담자</label>
                                <select class="form-select form-select-sm" id="filterConsultant">
                                    <option value="all">전체</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">담당자</label>
                                <select class="form-select form-select-sm" id="filterManager">
                                    <option value="all">전체</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">계약일</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" class="form-control" id="filterStartDate">
                                    <span class="input-group-text">~</span>
                                    <input type="date" class="form-control" id="filterEndDate">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 검색 영역 -->
                    <div class="search-area mb-3 d-none" id="searchArea">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select class="form-select form-select-sm" id="searchType">
                                    <option value="name">고객명</option>
                                    <option value="case_idx">사건번호</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <input type="text" class="form-control form-control-sm" id="searchKeyword" placeholder="검색어를 입력하세요">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-primary w-100" id="btnDoSearch">검색</button>
                            </div>
                        </div>
                    </div>

                    <!-- 고객 목록 테이블 -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="clientsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>계약일</th>
                                    <th>고객명</th>
                                    <th>사건분야</th>
                                    <th>진행현황</th>
                                    <th>상담자</th>
                                    <th>담당자</th>
                                    <th>수임료</th>
                                    <th>신분증</th>
                                    <th>인감</th>
                                    <th>1차서류</th>
                                    <th>2차서류</th>
                                    <th>부채증명서</th>
                                    <th>납부상태</th>
                                    <th>계약상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="14" class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">로딩 중...</span>
                                        </div>
                                        <p class="mt-2">고객 목록을 불러오는 중...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- 페이지네이션 -->
                    <div class="d-flex justify-content-between align-items-center mt-3" id="paginationArea">
                        <div class="text-muted small">
                            총 <span id="totalClients">0</span>명의 고객 | 
                            완납: <span id="completedClients">0</span>명 | 
                            미납: <span id="unpaidClients">0</span>명 | 
                            연체: <span id="overdueClients">0</span>명
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSRF 토큰 -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- 메모 모달 -->
<div class="modal fade" id="memoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">메모 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="memoForm">
                    <input type="hidden" id="memoId">
                    <div class="mb-3">
                        <label class="form-label">메모</label>
                        <textarea class="form-control" id="memo" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveMemo">저장</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="{{ asset('js/fee-client.js') }}"></script>
<script>
    // 페이지 로드 시 스크립트 실행 - loadClients 함수는 fee-client.js 내부에 있으므로 직접 호출하지 않음
    document.addEventListener('DOMContentLoaded', function() {
        // 메모 저장 버튼 이벤트 리스너 등록
        const saveMemoBtn = document.getElementById('saveMemo');
        if (saveMemoBtn) {
            console.log('메모 저장 버튼 이벤트 리스너 등록');
            saveMemoBtn.addEventListener('click', function() {
                console.log('메모 저장 버튼 클릭됨');
                const id = document.getElementById('memoId').value;
                const memo = document.getElementById('memo').value;
                
                console.log('저장할 메모 정보:', { id, memo });
                
                // CSRF 토큰 가져오기
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // API 요청 데이터 구성
                const data = {
                    memo: memo
                };
                
                // 저장 버튼 비활성화 및 로딩 표시
                saveMemoBtn.disabled = true;
                saveMemoBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 저장 중...';
                
                // 메모 업데이트 API 호출
                fetch(`/fee-client/payments/${id}/update-memo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    console.log('서버 응답:', response);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('응답 데이터:', data);
                    if (data.success) {
                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('memoModal')).hide();
                        
                        // 이벤트 발생시켜 고객 상세 정보 새로고침
                        document.dispatchEvent(new CustomEvent('memoUpdated'));
                    } else {
                        alert('메모 저장 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error updating memo:', error);
                    alert('메모 저장 중 오류가 발생했습니다.');
                })
                .finally(() => {
                    // 저장 버튼 상태 복원
                    saveMemoBtn.disabled = false;
                    saveMemoBtn.innerHTML = '저장';
                });
            });
        } else {
            console.warn('saveMemo 버튼을 찾을 수 없습니다');
        }
        
        // 필터 초기화 버튼
        if (document.getElementById('btnResetFilter')) {
            document.getElementById('btnResetFilter').addEventListener('click', function() {
                if (document.getElementById('filterPaymentStatus')) document.getElementById('filterPaymentStatus').value = 'all';
                if (document.getElementById('filterContractStatus')) document.getElementById('filterContractStatus').value = 'all';
                if (document.getElementById('filterCaseType')) document.getElementById('filterCaseType').value = 'all';
                if (document.getElementById('filterConsultant')) document.getElementById('filterConsultant').value = 'all';
                if (document.getElementById('filterManager')) document.getElementById('filterManager').value = 'all';
                if (document.getElementById('filterStartDate')) document.getElementById('filterStartDate').value = '';
                if (document.getElementById('filterEndDate')) document.getElementById('filterEndDate').value = '';
                
                // 이벤트 발생
                document.dispatchEvent(new CustomEvent('applyFeeClientFilters'));
            });
        }
        
        // 검색 초기화 버튼
        if (document.getElementById('btnResetSearch')) {
            document.getElementById('btnResetSearch').addEventListener('click', function() {
                if (document.getElementById('searchType')) document.getElementById('searchType').value = 'name';
                if (document.getElementById('searchKeyword')) document.getElementById('searchKeyword').value = '';
                
                // 이벤트 발생
                document.dispatchEvent(new CustomEvent('resetFeeClientFilters'));
            });
        }
    });
</script>
@endpush 