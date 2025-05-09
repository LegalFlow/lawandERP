@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2>보정서류관리</h2>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('correction-manager.index', ['view_mode' => 'unclassified']) }}" 
                   class="btn {{ request()->query('view_mode', 'unclassified') === 'unclassified' ? 'btn-primary' : 'btn-outline-primary' }}">
                    미분류
                    <span class="badge bg-light text-dark ms-1" id="unclassified-count">0</span>
                </a>
                <a href="{{ route('correction-manager.index', ['view_mode' => 'unsubmitted']) }}" 
                   class="btn {{ request()->query('view_mode') === 'unsubmitted' ? 'btn-primary' : 'btn-outline-primary' }}">
                    미제출
                    <span class="badge bg-light text-dark ms-1" id="unsubmitted-count">0</span>
                </a>
                <a href="{{ route('correction-manager.index', ['view_mode' => 'completed']) }}" 
                   class="btn {{ request()->query('view_mode') === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">
                    처리완료
                    <span class="badge bg-light text-dark ms-1" id="completed-count">0</span>
                </a>
                <a href="{{ route('correction-manager.index', ['view_mode' => 'no_manager']) }}" 
                   class="btn {{ request()->query('view_mode') === 'no_manager' ? 'btn-primary' : 'btn-outline-primary' }}">
                    담당자없음
                    <span class="badge bg-light text-dark ms-1" id="no-manager-count">0</span>
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-6">
            <button class="btn btn-primary" id="new-document-btn">
                새 문서 등록
            </button>
        </div>
        <div class="col-6 text-end">
            <button id="search-toggle-btn" class="btn btn-outline-secondary me-2">
                <i class="bi bi-search me-1"></i> 검색
            </button>
            <button id="filter-toggle-btn" class="btn btn-outline-secondary me-2">
                <i class="bi bi-funnel me-1"></i> 필터
            </button>
            <button id="chart-toggle-btn" class="btn btn-outline-secondary">
                <i class="bi bi-bar-chart me-1"></i> 차트
            </button>
        </div>
    </div>

    <!-- 검색 영역 -->
    <div id="search-area" class="row mb-3" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <select id="search-type" class="form-select">
                                <option value="name" selected>고객명</option>
                                <option value="case_number">사건번호</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" id="search-keyword" class="form-control" placeholder="검색어를 입력하세요">
                        </div>
                        <div class="col-md-2">
                            <button id="search-btn" class="btn btn-primary w-100">검색</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 영역 -->
    <div id="filter-area" class="row mb-3" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <!-- 날짜 필터 -->
                        <div class="col-md-4">
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label">날짜 필터</label>
                                    <select id="date-type" class="form-select">
                                        <option value="shipment_date">발송일자</option>
                                        <option value="receipt_date">수신일자</option>
                                        <option value="deadline">제출기한</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="date" id="date-from" class="form-control" placeholder="시작일">
                                </div>
                                <div class="col-md-6">
                                    <input type="date" id="date-to" class="form-control" placeholder="종료일">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 상담자 필터 -->
                        <div class="col-md-4">
                            <label class="form-label">상담자</label>
                            <select id="consultant-filter" class="form-select">
                                <option value="all" {{ request()->query('view_mode') === 'no_manager' ? 'selected' : '' }}>전체</option>
                                @foreach($consultants as $consultant)
                                    <option value="{{ $consultant }}" {{ request()->query('view_mode') !== 'no_manager' && $defaultConsultant === $consultant ? 'selected' : '' }}>
                                        {{ $consultant }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- 담당자 필터 -->
                        <div class="col-md-4">
                            <label class="form-label">담당자</label>
                            <select id="manager-filter" class="form-select">
                                <option value="all" {{ request()->query('view_mode') === 'no_manager' ? 'selected' : '' }}>전체</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager }}" {{ request()->query('view_mode') !== 'no_manager' && $defaultManager === $manager ? 'selected' : '' }}>
                                        {{ $manager }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- 필터 적용 버튼 -->
                        <div class="col-md-12 text-end">
                            <button id="apply-filter-btn" class="btn btn-primary">필터 적용</button>
                            <button id="reset-filter-btn" class="btn btn-outline-secondary ms-2">초기화</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 차트 영역 -->
    <div id="chart-area" class="row mb-3" style="display: none;">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">담당자별 미제출 문서 현황</h5>
                    <div style="height: 400px;">
                        <canvas id="documentChart"></canvas>
                    </div>
                    <div class="mt-4 bg-light p-3 rounded">
                        <p class="mb-0 text-muted small">
                            * 좌측 막대(파란색)는 '보정' 미제출 문서 수, 우측 막대는 '기타', '명령', '예외' 미제출 문서 수의 합계입니다.<br>
                            * '보정' 문서는 수신된 문서(진한 파란색)와 미수신된 문서(연한 파란색)로 구분됩니다.<br>
                            * 담당자는 '보정' 미제출 문서 수 기준으로 정렬되어 있습니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 문서 카드 영역 -->
    <div id="cards-container" class="row" data-viewmode="{{ $viewMode }}">
        <!-- 카드가 여기에 동적으로 추가됩니다 -->
        <div class="col-12 text-center py-5" id="loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <p class="mt-2">데이터를 불러오는 중입니다...</p>
        </div>
        <div class="col-12 text-center py-5" id="no-data" style="display: none;">
            <p class="fs-5 text-muted">데이터가 없습니다.</p>
        </div>
    </div>

    <!-- 편집 모달 -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">문서 정보 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-form">
                        <input type="hidden" id="edit-id">
                        
                        <!-- 고객 & 문서 정보 (읽기 전용) -->
                        <div class="mb-3 p-3 bg-light rounded">
                            <p class="fw-bold mb-1" id="edit-customer-name"></p>
                            <p class="text-muted mb-1" id="edit-court-case"></p>
                            <p class="text-muted mb-0" id="edit-document-name"></p>
                        </div>
                        
                        <!-- 분류 -->
                        <div class="mb-3">
                            <label for="edit-document-type" class="form-label">분류</label>
                            <select id="edit-document-type" class="form-select">
                                <option value="선택없음">선택없음</option>
                                <option value="명령">명령</option>
                                <option value="기타">기타</option>
                                <option value="보정">보정</option>
                                <option value="예외">예외</option>
                            </select>
                        </div>
                        
                        <!-- 상담자 -->
                        <div class="mb-3">
                            <label for="edit-consultant" class="form-label">상담자</label>
                            <select id="edit-consultant" class="form-select">
                                <option value="">선택</option>
                                @foreach($consultants as $consultant)
                                    <option value="{{ $consultant }}">{{ $consultant }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- 담당자 -->
                        <div class="mb-3">
                            <label for="edit-case-manager" class="form-label">담당자</label>
                            <select id="edit-case-manager" class="form-select">
                                <option value="">선택</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager }}">{{ $manager }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- 제출기한 -->
                        <div class="mb-3">
                            <label for="edit-deadline" class="form-label">제출기한</label>
                            <input type="date" id="edit-deadline" class="form-control">
                        </div>
                        
                        <!-- 제출여부 -->
                        <div class="mb-3">
                            <label for="edit-submission-status" class="form-label">제출여부</label>
                            <select id="edit-submission-status" class="form-select">
                                <option value="미제출">미제출</option>
                                <option value="제출완료">제출완료</option>
                                <option value="안내완료">안내완료</option>
                                <option value="처리완료">처리완료</option>
                                <option value="연기신청">연기신청</option>
                                <option value="제출불요">제출불요</option>
                                <option value="계약해지">계약해지</option>
                                <option value="연락두절">연락두절</option>
                            </select>
                        </div>
                        
                        <!-- 제출일자 -->
                        <div class="mb-3">
                            <label for="edit-summit-date" class="form-label">제출일자</label>
                            <input type="date" id="edit-summit-date" class="form-control">
                        </div>
                        
                        <!-- 메모 -->
                        <div class="mb-3">
                            <label for="edit-command" class="form-label">메모</label>
                            <textarea id="edit-command" class="form-control" rows="3" placeholder="메모를 입력하세요..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="save-edit-btn">저장</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 메모 모달 -->
    <div class="modal fade" id="memoModal" tabindex="-1" aria-labelledby="memoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memoModalLabel">메모</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light rounded mb-3">
                        <pre id="memo-content" class="mb-0" style="white-space: pre-wrap;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 사건번호 입력 모달 -->
    <div class="modal fade" id="caseNumberModal" tabindex="-1" aria-labelledby="caseNumberModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="caseNumberModalLabel">사건번호 입력</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="case-number-input" class="form-label">사건번호</label>
                        <input type="text" class="form-control" id="case-number-input" placeholder="사건번호를 입력하세요">
                        <div class="form-text mt-2">사건번호를 입력하면 해당 사건의 정보가 자동으로 불러와집니다.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="search-case-btn">확인</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 문서 등록 모달 -->
    <div class="modal fade" id="newDocumentModal" tabindex="-1" aria-labelledby="newDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newDocumentModalLabel">새 문서 등록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="new-document-form">
                        <div class="row g-3">
                            <!-- 발송일자 -->
                            <div class="col-md-6">
                                <label for="new-shipment-date" class="form-label">발송일자 <span class="text-danger">*</span></label>
                                <input type="date" id="new-shipment-date" class="form-control" required>
                            </div>
                            
                            <!-- 수신일자 -->
                            <div class="col-md-6">
                                <label for="new-receipt-date" class="form-label">수신일자 <span class="text-danger">*</span></label>
                                <input type="date" id="new-receipt-date" class="form-control" required>
                            </div>
                            
                            <!-- 법원 -->
                            <div class="col-md-6">
                                <label for="new-court-name" class="form-label">법원 <span class="text-danger">*</span></label>
                                <input type="text" id="new-court-name" class="form-control" required readonly>
                            </div>
                            
                            <!-- 사건번호 -->
                            <div class="col-md-6">
                                <label for="new-case-number" class="form-label">사건번호 <span class="text-danger">*</span></label>
                                <input type="text" id="new-case-number" class="form-control" required readonly>
                            </div>
                            
                            <!-- 고객명 -->
                            <div class="col-md-6">
                                <label for="new-client-name" class="form-label">고객명 <span class="text-danger">*</span></label>
                                <input type="text" id="new-client-name" class="form-control" required readonly>
                            </div>
                            
                            <!-- 송달문서 -->
                            <div class="col-md-6">
                                <label for="new-document-name" class="form-label">송달문서 <span class="text-danger">*</span></label>
                                <input type="text" id="new-document-name" class="form-control" required>
                            </div>
                            
                            <!-- 분류 -->
                            <div class="col-md-6">
                                <label for="new-document-type" class="form-label">분류 <span class="text-danger">*</span></label>
                                <select id="new-document-type" class="form-select" required>
                                    <option value="선택없음" selected>선택없음</option>
                                    <option value="명령">명령</option>
                                    <option value="기타">기타</option>
                                    <option value="보정">보정</option>
                                    <option value="예외">예외</option>
                                </select>
                            </div>
                            
                            <!-- 상담자 -->
                            <div class="col-md-6">
                                <label for="new-consultant" class="form-label">상담자</label>
                                <select id="new-consultant" class="form-select">
                                    <option value="">선택 안함</option>
                                    @foreach ($consultants as $consultant)
                                        <option value="{{ $consultant }}">{{ $consultant }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- 담당자 -->
                            <div class="col-md-6">
                                <label for="new-case-manager" class="form-label">담당자</label>
                                <select id="new-case-manager" class="form-select">
                                    <option value="">선택 안함</option>
                                    @foreach ($managers as $manager)
                                        <option value="{{ $manager }}">{{ $manager }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- 제출기한 -->
                            <div class="col-md-6">
                                <label for="new-deadline" class="form-label">제출기한</label>
                                <input type="date" id="new-deadline" class="form-control">
                            </div>
                            
                            <!-- 제출여부 -->
                            <div class="col-md-6">
                                <label for="new-submission-status" class="form-label">제출여부 <span class="text-danger">*</span></label>
                                <select id="new-submission-status" class="form-select" required>
                                    <option value="미제출" selected>미제출</option>
                                    <option value="제출완료">제출완료</option>
                                    <option value="안내완료">안내완료</option>
                                    <option value="처리완료">처리완료</option>
                                    <option value="연기신청">연기신청</option>
                                    <option value="제출불요">제출불요</option>
                                    <option value="계약해지">계약해지</option>
                                    <option value="연락두절">연락두절</option>
                                </select>
                            </div>
                            
                            <!-- 제출일자 -->
                            <div class="col-md-6">
                                <label for="new-summit-date" class="form-label">제출일자</label>
                                <input type="date" id="new-summit-date" class="form-control">
                            </div>
                            
                            <!-- 메모 -->
                            <div class="col-md-12">
                                <label for="new-command" class="form-label">메모</label>
                                <textarea id="new-command" class="form-control" rows="3" placeholder="메모를 입력하세요..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="save-document-btn">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* 카드 스타일 */
    .correction-card {
        height: 100%;
        transition: all 0.2s ease;
        position: relative;
        border-radius: 0.75rem;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    
    .correction-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    /* 분류별 카드 색상 - 더 부드러운 파스텔톤으로 변경 */
    .card-type-명령 {
        background-color: rgba(255, 193, 7, 0.15) !important; /* 노랑 계열 파스텔톤 */
    }
    
    .card-type-보정 {
        background-color: rgba(13, 110, 253, 0.15) !important; /* 파랑 계열 파스텔톤 */
    }
    
    .card-type-기타 {
        background-color: rgba(25, 135, 84, 0.15) !important; /* 초록 계열 파스텔톤 */
    }
    
    .card-type-예외 {
        background-color: rgba(220, 53, 69, 0.15) !important; /* 빨강 계열 파스텔톤 */
    }
    
    .card-type-선택없음, 
    .card-type-none {
        background-color: #ffffff !important; /* 하얀색 */
    }
    
    /* 분류 배지 스타일 */
    .type-badge {
        position: absolute;
        top: 0;
        right: 0;
        border-bottom-left-radius: 0.5rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 1;
    }
    
    .type-badge-명령 {
        background-color: rgba(255, 193, 7, 0.7);
        color: #664d03;
    }
    
    .type-badge-보정 {
        background-color: rgba(13, 110, 253, 0.7);
        color: white;
    }
    
    .type-badge-기타 {
        background-color: rgba(25, 135, 84, 0.7);
        color: white;
    }
    
    .type-badge-예외 {
        background-color: rgba(220, 53, 69, 0.7);
        color: white;
    }
    
    .type-badge-선택없음,
    .type-badge-none {
        background-color: rgba(156, 163, 175, 0.7);
        color: white;
    }
    
    /* 제출상태 배지 스타일 */
    .status-badge {
        font-size: 0.7rem;
        border-radius: 1rem;
        padding: 0.15rem 0.5rem;
        display: inline-block;
    }
    
    .status-미제출 {
        background-color: #f1f3f5;
        color: #495057;
    }
    
    .status-제출완료 {
        background-color: #e3faec;
        color: #146c43;
    }
    
    .status-안내완료 {
        background-color: #e3eefa;
        color: #0a58ca;
    }
    
    .status-처리완료 {
        background-color: #ede2fe;
        color: #6f42c1;
    }
    
    .status-연기신청 {
        background-color: #fff3cd;
        color: #997404;
    }
    
    .status-제출불요 {
        background-color: #f1f3f5;
        color: #495057;
    }
    
    .status-계약해지 {
        background-color: #fee2e2;
        color: #b02a37;
    }
    
    .status-연락두절 {
        background-color: #fee2e2;
        color: #b02a37;
    }
    
    /* 기한 배지 스타일 */
    .deadline-badge {
        font-size: 0.7rem;
        border-radius: 1rem;
        padding: 0.15rem 0.5rem;
        display: inline-block;
    }
    
    .deadline-overdue {
        background-color: #fee2e2;
        color: #b02a37;
    }
    
    .deadline-close {
        background-color: #fff3cd;
        color: #997404;
    }
    
    .deadline-normal {
        background-color: #e3faec;
        color: #146c43;
    }
    
    /* 기타 스타일 */
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .card-date {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .card-person {
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        color: #555;
    }
    
    .card-person i {
        margin-right: 0.25rem;
        font-size: 0.7rem;
    }

    /* 수신일자가 없는 카드 스타일 */
    .card-no-receipt {
        opacity: 0.3;
    }

    /* 새로운 스타일 추가 */
    .card-body {
        padding: 1.25rem;
    }

    .document-title {
        font-size: 0.85rem;
        font-weight: 500;
        line-height: 1.4;
    }

    .edit-btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
    }

    /* 검색 및 필터 버튼 스타일 */
    #search-toggle-btn, #filter-toggle-btn {
        border-radius: 0.5rem;
        padding: 0.4rem 0.8rem;
    }

    /* 탭 스타일 개선 */
    .btn-group .btn {
        border-radius: 0.5rem;
        margin: 0 0.15rem;
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    /* 새 문서 등록 버튼 스타일 */
    #new-document-btn {
        border-radius: 0.5rem;
        padding: 0.4rem 1rem;
        font-weight: 500;
    }
    
    /* 아이콘 버튼 스타일 */
    .btn.rounded-circle {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn.rounded-circle i {
        font-size: 0.8rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 상태 관리
        let currentData = [];
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        
        // 현재 뷰 모드 가져오기
        const viewMode = document.getElementById('cards-container').dataset.viewmode || 'unclassified';
        
        // 필터 옵션 설정 - 담당자없음 탭에서는 무조건 필터를 전체로 설정
        let filterOptions = {
            search_type: 'name',
            search_keyword: '',
            date_type: 'shipment_date',
            date_from: '',
            date_to: '',
            consultant: viewMode === 'no_manager' ? 'all' : '{{ $defaultConsultant }}',
            case_manager: viewMode === 'no_manager' ? 'all' : '{{ $defaultManager }}'
        };
        
        // 초기 URL 설정 - 첫 로드 시 뷰모드 파라미터가 없으면 미분류로 리다이렉트
        if (!window.location.href.includes('view_mode=')) {
            const url = new URL(window.location.href);
            url.searchParams.set('view_mode', 'unclassified');
            window.history.replaceState({}, '', url);
        }
        
        // 새 문서 등록 버튼 이벤트
        document.getElementById('new-document-btn').addEventListener('click', function() {
            const caseNumberModal = new bootstrap.Modal(document.getElementById('caseNumberModal'));
            caseNumberModal.show();
        });
        
        // 사건번호 검색 버튼 이벤트
        document.getElementById('search-case-btn').addEventListener('click', function() {
            const caseNumber = document.getElementById('case-number-input').value.trim();
            
            if (!caseNumber) {
                alert('사건번호를 입력해주세요.');
                return;
            }
            
            // 사건번호로 사건 정보 검색
            fetch(`{{ url('correction-manager/search-case') }}?case_number=${encodeURIComponent(caseNumber)}`)
                .then(response => response.json())
                .then(data => {
                    // 사건번호 모달 닫기
                    const caseNumberModal = bootstrap.Modal.getInstance(document.getElementById('caseNumberModal'));
                    caseNumberModal.hide();
                    
                    // 검색 결과에 따라 처리
                    if (data.status === 'success') {
                        if (data.data.length === 1) {
                            // 정확히 하나의 결과가 있는 경우
                            const caseData = data.data[0];
                            openNewDocumentModal(caseData, false);
                        } else if (data.data.length > 1) {
                            // 둘 이상의 결과가 있는 경우
                            alert('동일한 사건번호로 여러 사건이 검색되었습니다. 모든 필드를 직접 입력해주세요.');
                            openNewDocumentModal(null, true);
                        } else {
                            // 결과가 없는 경우
                            alert('해당 사건번호의 사건이 존재하지 않습니다. 모든 필드를 직접 입력해주세요.');
                            openNewDocumentModal(null, true);
                        }
                    } else {
                        alert('사건 검색 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error searching case:', error);
                    alert('사건 검색 중 오류가 발생했습니다.');
                });
        });
        
        // 문서 저장 버튼 이벤트
        document.getElementById('save-document-btn').addEventListener('click', function() {
            // 필수 필드 검증
            const shipmentDate = document.getElementById('new-shipment-date').value;
            const receiptDate = document.getElementById('new-receipt-date').value;
            const courtName = document.getElementById('new-court-name').value;
            const caseNumber = document.getElementById('new-case-number').value;
            const clientName = document.getElementById('new-client-name').value;
            const documentName = document.getElementById('new-document-name').value;
            const documentType = document.getElementById('new-document-type').value;
            const submissionStatus = document.getElementById('new-submission-status').value;
            
            if (!shipmentDate || !receiptDate || !courtName || !caseNumber || !clientName || !documentName || !documentType || !submissionStatus) {
                alert('필수 항목을 모두 입력해주세요.');
                return;
            }
            
            // 문서 데이터 수집
            const documentData = {
                shipment_date: shipmentDate,
                receipt_date: receiptDate,
                court_name: courtName,
                case_number: caseNumber,
                name: clientName,
                document_name: documentName,
                document_type: documentType,
                consultant: document.getElementById('new-consultant').value,
                case_manager: document.getElementById('new-case-manager').value,
                deadline: document.getElementById('new-deadline').value,
                submission_status: submissionStatus,
                summit_date: document.getElementById('new-summit-date').value,
                command: document.getElementById('new-command').value
            };
            
            // API 요청
            fetch('{{ route('correction-manager.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(documentData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // 모달 닫기
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newDocumentModal'));
                    modal.hide();
                    
                    // 입력값 초기화
                    resetNewDocumentForm();
                    
                    // 데이터 새로고침
                    resetAndFetchData();
                    
                    // 알림 표시
                    alert('문서가 등록되었습니다.');
                } else {
                    alert('문서 등록 중 오류가 발생했습니다: ' + (result.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error saving document:', error);
                alert('문서 등록 중 오류가 발생했습니다.');
            });
        });
        
        // 새 문서 등록 모달 열기 함수
        function openNewDocumentModal(caseData, editable) {
            // 오늘 날짜를 기본값으로 설정
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('new-shipment-date').value = today;
            document.getElementById('new-receipt-date').value = today;
            
            // 사건 데이터가 있는 경우 폼에 채우기
            if (caseData) {
                document.getElementById('new-court-name').value = caseData.court_name || '';
                document.getElementById('new-case-number').value = caseData.case_number || '';
                document.getElementById('new-client-name').value = caseData.client_name || '';
                
                // 상담자 값 설정
                const consultantSelect = document.getElementById('new-consultant');
                if (caseData.consultant) {
                    if (Array.from(consultantSelect.options).some(option => option.value === caseData.consultant)) {
                        consultantSelect.value = caseData.consultant;
                    } else {
                        // 목록에 없는 상담자인 경우 새 옵션 추가
                        const newOption = new Option(caseData.consultant, caseData.consultant);
                        consultantSelect.add(newOption);
                        consultantSelect.value = caseData.consultant;
                    }
                } else {
                    consultantSelect.value = '';
                }
                
                // 담당자 값 설정
                const managerSelect = document.getElementById('new-case-manager');
                if (caseData.case_manager) {
                    if (Array.from(managerSelect.options).some(option => option.value === caseData.case_manager)) {
                        managerSelect.value = caseData.case_manager;
                    } else {
                        // 목록에 없는 담당자인 경우 새 옵션 추가
                        const newOption = new Option(caseData.case_manager, caseData.case_manager);
                        managerSelect.add(newOption);
                        managerSelect.value = caseData.case_manager;
                    }
                } else {
                    managerSelect.value = '';
                }
            } else {
                // 데이터가 없는 경우 필드 초기화
                document.getElementById('new-court-name').value = '';
                document.getElementById('new-case-number').value = '';
                document.getElementById('new-client-name').value = '';
                document.getElementById('new-consultant').value = '';
                document.getElementById('new-case-manager').value = '';
            }
            
            // 수정 가능 여부에 따라 필드 상태 설정
            document.getElementById('new-court-name').readOnly = !editable;
            document.getElementById('new-case-number').readOnly = !editable;
            document.getElementById('new-client-name').readOnly = !editable;
            
            // 상담자와 담당자는 항상 수정 가능하도록 설정
            document.getElementById('new-consultant').disabled = false;
            document.getElementById('new-case-manager').disabled = false;
            
            // 다른 필드 초기화
            document.getElementById('new-document-name').value = '';
            document.getElementById('new-document-type').value = '선택없음';
            document.getElementById('new-deadline').value = '';
            document.getElementById('new-submission-status').value = '미제출';
            document.getElementById('new-summit-date').value = '';
            document.getElementById('new-command').value = '';
            
            // 발송일자 변경 시 수신일자 자동 업데이트
            document.getElementById('new-shipment-date').addEventListener('change', function() {
                document.getElementById('new-receipt-date').value = this.value;
            });
            
            // 제출여부 변경 시 자동으로 제출일자 설정
            document.getElementById('new-submission-status').addEventListener('change', function() {
                const status = this.value;
                if (status === '제출완료' || status === '안내완료' || status === '처리완료') {
                    if (!document.getElementById('new-summit-date').value) {
                        document.getElementById('new-summit-date').value = new Date().toISOString().split('T')[0];
                    }
                }
            });
            
            // 모달 열기
            const newDocumentModal = new bootstrap.Modal(document.getElementById('newDocumentModal'));
            newDocumentModal.show();
        }
        
        // 새 문서 폼 초기화 함수
        function resetNewDocumentForm() {
            document.getElementById('new-shipment-date').value = '';
            document.getElementById('new-receipt-date').value = '';
            document.getElementById('new-court-name').value = '';
            document.getElementById('new-case-number').value = '';
            document.getElementById('new-client-name').value = '';
            document.getElementById('new-document-name').value = '';
            document.getElementById('new-document-type').value = '선택없음';
            document.getElementById('new-consultant').value = '';
            document.getElementById('new-case-manager').value = '';
            document.getElementById('new-deadline').value = '';
            document.getElementById('new-submission-status').value = '미제출';
            document.getElementById('new-summit-date').value = '';
            document.getElementById('new-command').value = '';
        }
        
        // 검색 및 필터 토글 버튼
        document.getElementById('search-toggle-btn').addEventListener('click', function() {
            const searchArea = document.getElementById('search-area');
            searchArea.style.display = searchArea.style.display === 'none' ? 'flex' : 'none';
        });
        
        document.getElementById('filter-toggle-btn').addEventListener('click', function() {
            const filterArea = document.getElementById('filter-area');
            filterArea.style.display = filterArea.style.display === 'none' ? 'flex' : 'none';
        });
        
        // 검색 버튼 이벤트
        document.getElementById('search-btn').addEventListener('click', function() {
            filterOptions.search_type = document.getElementById('search-type').value;
            filterOptions.search_keyword = document.getElementById('search-keyword').value;
            resetAndFetchData();
            getFilteredCounts();
        });
        
        // 필터 적용 버튼 이벤트
        document.getElementById('apply-filter-btn').addEventListener('click', function() {
            filterOptions.date_type = document.getElementById('date-type').value;
            filterOptions.date_from = document.getElementById('date-from').value;
            filterOptions.date_to = document.getElementById('date-to').value;
            filterOptions.consultant = document.getElementById('consultant-filter').value;
            filterOptions.case_manager = document.getElementById('manager-filter').value;
            resetAndFetchData();
            getFilteredCounts();
        });
        
        // 필터 초기화 버튼 이벤트
        document.getElementById('reset-filter-btn').addEventListener('click', function() {
            document.getElementById('date-from').value = '';
            document.getElementById('date-to').value = '';
            document.getElementById('consultant-filter').value = 'all';
            document.getElementById('manager-filter').value = 'all';
            
            filterOptions = {
                search_type: 'name',
                search_keyword: '',
                date_type: 'shipment_date',
                date_from: '',
                date_to: '',
                consultant: 'all',
                case_manager: 'all'
            };
            
            resetAndFetchData();
            getFilteredCounts();
        });
        
        // 저장 버튼 이벤트
        document.getElementById('save-edit-btn').addEventListener('click', function() {
            const id = document.getElementById('edit-id').value;
            const data = {
                document_type: document.getElementById('edit-document-type').value,
                consultant: document.getElementById('edit-consultant').value,
                case_manager: document.getElementById('edit-case-manager').value,
                deadline: document.getElementById('edit-deadline').value,
                submission_status: document.getElementById('edit-submission-status').value,
                summit_date: document.getElementById('edit-summit-date').value,
                command: document.getElementById('edit-command').value
            };
            
            updateDocument(id, data);
        });
        
        // 날짜 포맷 함수
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return `${date.getFullYear()}.${String(date.getMonth() + 1).padStart(2, '0')}.${String(date.getDate()).padStart(2, '0')}`;
        }
        
        // 마감일 상태 계산 함수
        function getDeadlineStatus(deadlineDate) {
            if (!deadlineDate) return null;
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const deadline = new Date(deadlineDate);
            deadline.setHours(0, 0, 0, 0);
            
            const diffTime = deadline.getTime() - today.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays < 0) {
                return { text: '기한초과', class: 'deadline-overdue' };
            } else if (diffDays === 0) {
                return { text: 'D-day', class: 'deadline-close' };
            } else if (diffDays <= 3) {
                return { text: `D-${diffDays}`, class: 'deadline-close' };
            } else if (diffDays <= 7) {
                return { text: `D-${diffDays}`, class: 'deadline-normal' };
            } else {
                return { text: `D-${diffDays}`, class: 'deadline-normal' };
            }
        }
        
        // 카드 생성 함수
        function createCard(item) {
            const deadlineStatus = getDeadlineStatus(item.deadline);
            const documentType = item.document_type || '선택없음';
            
            const card = document.createElement('div');
            card.className = 'col-md-4 mb-4';
            card.innerHTML = `
                <div class="card correction-card card-type-${documentType} ${!item.receipt_date ? 'card-no-receipt' : ''}" data-id="${item.id}">
                    <div class="card-body">
                        <!-- 분류 배지 -->
                        <div class="type-badge type-badge-${documentType}">${documentType}</div>
                        
                        <!-- 카드 헤더 -->
                        <div class="d-flex justify-content-between align-items-start mb-2 mt-1">
                            <div class="d-flex align-items-center">
                                <span class="fw-bold">${item.name || '이름 없음'}</span>
                                ${item.command ? `
                                <button type="button" class="btn btn-link p-0 ms-1 text-muted memo-btn" data-memo="${item.command}">
                                    <i class="bi bi-chat-square-text"></i>
                                </button>` : ''}
                            </div>
                            
                            ${deadlineStatus ? `
                            <span class="deadline-badge ${deadlineStatus.class}">
                                ${deadlineStatus.text}
                            </span>` : ''}
                        </div>
                        
                        <!-- 법원 및 사건번호 -->
                        <div class="text-muted small mb-2 text-truncate">
                            ${item.court_name || '-'} | ${item.case_number || '-'}
                        </div>
                        
                        <!-- 문서명 -->
                        <div class="d-flex align-items-start mb-2">
                            <i class="bi bi-file-text me-1 text-muted"></i>
                            <span class="document-title text-truncate-2">${item.document_name || '문서명 없음'}</span>
                        </div>
                        
                        <!-- 날짜 정보 -->
                        <div class="row g-2 mb-3">
                            <div class="col-4 card-date">
                                발신: ${formatDate(item.shipment_date)}
                            </div>
                            <div class="col-4 card-date">
                                수신: ${item.receipt_date ? formatDate(item.receipt_date) : '미수신'}
                            </div>
                            <div class="col-4 card-date">
                                기한: ${formatDate(item.deadline)}
                            </div>
                        </div>
                        
                        <!-- 담당자 정보 -->
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6 card-person">
                                <i class="bi bi-person"></i>
                                <span class="text-truncate">상담: ${item.consultant || '미지정'}</span>
                            </div>
                            <div class="col-6 card-person">
                                <i class="bi bi-briefcase"></i>
                                <span class="text-truncate">담당: ${item.case_manager || '미지정'}</span>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="status-badge status-${item.submission_status || '미제출'}">
                                    ${item.submission_status || '미제출'}
                                </span>
                            </div>
                            <div class="col-6 mt-2 card-person">
                                ${item.summit_date ? `
                                <i class="bi bi-check-circle"></i>
                                <span>제출: ${formatDate(item.summit_date)}</span>
                                ` : `<span class="text-muted">미제출</span>`}
                            </div>
                            
                            <div class="col-12 mt-3 d-flex justify-content-between align-items-center">
                                ${item.pdf_path ? `
                                <a href="{{ url('correction-manager/download') }}/${btoa(encodeURIComponent(item.pdf_path))}" 
                                   class="btn btn-sm btn-outline-primary rounded-circle" 
                                   title="파일 다운로드"
                                   target="_blank">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>` : `
                                <span></span>
                                `}
                                
                                <div>
                                    ${item.order === null ? `
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle delete-btn me-1" data-id="${item.id}" title="삭제">
                                        <i class="bi bi-trash"></i>
                                    </button>` : ''}
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle edit-btn" title="정보 수정">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            return card;
        }
        
        // 데이터 초기화 및 새로 가져오기
        function resetAndFetchData() {
            // 상태 초기화
            currentData = [];
            currentPage = 1;
            hasMore = true;
            
            // 기존 카드 모두 제거
            const container = document.getElementById('cards-container');
            const cards = container.querySelectorAll('.col-md-4');
            cards.forEach(card => card.remove());
            
            // 로딩 더보기 요소도 제거
            const existingLoadingMore = document.getElementById('loading-more');
            if (existingLoadingMore && existingLoadingMore.parentNode === container) {
                container.removeChild(existingLoadingMore);
            }
            
            // 데이터 가져오기
            fetchData();
        }
        
        // 데이터 가져오기 함수
        function fetchData() {
            if (isLoading || !hasMore) return;
            
            const container = document.getElementById('cards-container');
            const loading = document.getElementById('loading');
            const noData = document.getElementById('no-data');
            const loadingMore = document.getElementById('loading-more') || createLoadingMoreElement();
            
            // 로딩 표시
            isLoading = true;
            
            if (currentPage === 1) {
                // 첫 페이지 로딩일 경우 메인 로딩 인디케이터 표시
                loading.style.display = 'block';
                noData.style.display = 'none';
                // 스크롤 페이징 로딩 숨김
                if (loadingMore.parentNode === container) {
                    container.removeChild(loadingMore);
                }
            } else {
                // 추가 페이지 로딩일 경우 스크롤 페이징 로딩 표시
                loading.style.display = 'none';
                // loadingMore가 이미 container에 있는지 확인 후 추가
                if (loadingMore.parentNode !== container) {
                    container.appendChild(loadingMore);
                }
                loadingMore.style.display = 'block';
            }
            
            // API 요청
            const queryParams = new URLSearchParams({
                view_mode: viewMode,
                page: currentPage,
                per_page: 15,
                ...filterOptions
            });
            
            fetch(`{{ route('correction-manager.data') }}?${queryParams}`)
                .then(response => response.json())
                .then(data => {
                    // 데이터 추가
                    currentData = [...currentData, ...data.data];
                    
                    // 로딩 숨기기
                    isLoading = false;
                    loading.style.display = 'none';
                    
                    // loadingMore 요소 제거
                    if (loadingMore.parentNode === container) {
                        loadingMore.style.display = 'none';
                    }
                    
                    if (currentData.length === 0) {
                        noData.style.display = 'block';
                    } else {
                        noData.style.display = 'none';
                        
                        // 카드 생성 및 추가
                        data.data.forEach(item => {
                            const card = createCard(item);
                            container.appendChild(card);
                        });
                        
                        // 다음 페이지 정보 업데이트
                        hasMore = data.meta.has_more;
                        currentPage++;
                        
                        // loadingMore 다시 추가
                        if (hasMore && loadingMore.parentNode !== container) {
                            container.appendChild(loadingMore);
                        }
                        
                        // 이벤트 리스너 추가
                        addEventListeners();
                        
                        // 카운터 업데이트 (필터 적용된 값 사용)
                        if (currentPage === 2) { // 첫 페이지 로드 후에만 카운트 업데이트
                            getFilteredCounts();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    isLoading = false;
                    loading.style.display = 'none';
                    loadingMore.style.display = 'none';
                    alert('데이터를 가져오는 중 오류가 발생했습니다.');
                });
        }
        
        // 필터가 적용된 카운트 가져오기
        function getFilteredCounts() {
            // 현재 필터 옵션을 복사
            const countFilterOptions = {...filterOptions};
            // 페이지 매개변수 제거 (전체 카운트를 위해)
            delete countFilterOptions.page;
            delete countFilterOptions.per_page;
            
            // 미분류 카운트 가져오기
            const unclassifiedParams = new URLSearchParams({
                view_mode: 'unclassified',
                count_only: true,
                ...countFilterOptions
            });
            
            // 미제출 카운트 가져오기
            const unsubmittedParams = new URLSearchParams({
                view_mode: 'unsubmitted',
                count_only: true,
                ...countFilterOptions
            });
            
            // 처리완료 카운트 가져오기
            const completedParams = new URLSearchParams({
                view_mode: 'completed',
                count_only: true,
                ...countFilterOptions
            });
            
            // 담당자없음 카운트 가져오기
            const noManagerParams = new URLSearchParams({
                view_mode: 'no_manager',
                count_only: true,
                ...countFilterOptions
            });
            
            // 병렬로 모든 카운트 요청
            Promise.all([
                fetch(`{{ route('correction-manager.data') }}?${unclassifiedParams}`).then(r => r.json()),
                fetch(`{{ route('correction-manager.data') }}?${unsubmittedParams}`).then(r => r.json()),
                fetch(`{{ route('correction-manager.data') }}?${completedParams}`).then(r => r.json()),
                fetch(`{{ route('correction-manager.data') }}?${noManagerParams}`).then(r => r.json())
            ]).then(([unclassified, unsubmitted, completed, noManager]) => {
                // 카운트 업데이트
                document.getElementById('unclassified-count').textContent = unclassified.meta.total || 0;
                document.getElementById('unsubmitted-count').textContent = unsubmitted.meta.total || 0;
                document.getElementById('completed-count').textContent = completed.meta.total || 0;
                document.getElementById('no-manager-count').textContent = noManager.meta.total || 0;
            }).catch(error => {
                console.error('Error fetching counts:', error);
            });
        }
        
        // 추가 로딩 인디케이터 생성
        function createLoadingMoreElement() {
            // 이미 존재하는 경우 재사용
            const existingEl = document.getElementById('loading-more');
            if (existingEl) return existingEl;
            
            const loadingMore = document.createElement('div');
            loadingMore.id = 'loading-more';
            loadingMore.className = 'col-12 text-center py-3';
            loadingMore.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">로딩 중...</span>
                </div>
                <span class="ms-2">더 불러오는 중...</span>
            `;
            loadingMore.style.display = 'none';
            return loadingMore;
        }
        
        // 이벤트 리스너 추가 함수
        function addEventListeners() {
            // 편집 버튼 이벤트
            document.querySelectorAll('.edit-btn').forEach(button => {
                if (!button.hasEventListener) {
                    button.addEventListener('click', function() {
                        const card = this.closest('.correction-card');
                        const id = card.dataset.id;
                        const item = currentData.find(d => d.id == id);
                        
                        if (item) {
                            openEditModal(item);
                        }
                    });
                    button.hasEventListener = true;
                }
            });
            
            // 메모 버튼 이벤트
            document.querySelectorAll('.memo-btn').forEach(button => {
                if (!button.hasEventListener) {
                    button.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const memo = this.dataset.memo;
                        openMemoModal(memo);
                    });
                    button.hasEventListener = true;
                }
            });
            
            // 삭제 버튼 이벤트
            document.querySelectorAll('.delete-btn').forEach(button => {
                if (!button.hasEventListener) {
                    button.addEventListener('click', function() {
                        const id = this.dataset.id;
                        if (confirm('정말로 이 문서를 삭제하시겠습니까?')) {
                            deleteDocument(id);
                        }
                    });
                    button.hasEventListener = true;
                }
            });
        }
        
        // 스크롤 이벤트 처리
        function handleScroll() {
            if (isLoading || !hasMore) return;
            
            const scrollY = window.scrollY || window.pageYOffset;
            const viewportHeight = window.innerHeight;
            const documentHeight = document.documentElement.scrollHeight;
            
            // 스크롤이 문서 하단에 가까워지면 추가 데이터 로드
            if (scrollY + viewportHeight >= documentHeight - 200) {
                fetchData();
            }
        }
        
        // 스크롤 이벤트 리스너 등록
        window.addEventListener('scroll', handleScroll);
        
        // 편집 모달 열기 함수
        function openEditModal(item) {
            document.getElementById('edit-id').value = item.id;
            document.getElementById('edit-customer-name').textContent = item.name || '이름 없음';
            document.getElementById('edit-court-case').textContent = `${item.court_name || '-'} | ${item.case_number || '-'}`;
            document.getElementById('edit-document-name').textContent = item.document_name || '문서명 없음';
            
            document.getElementById('edit-document-type').value = item.document_type || '선택없음';
            
            // 상담자 값 설정
            const consultantSelect = document.getElementById('edit-consultant');
            if (item.consultant) {
                if (Array.from(consultantSelect.options).some(option => option.value === item.consultant)) {
                    consultantSelect.value = item.consultant;
                } else {
                    // 목록에 없는 상담자인 경우 새 옵션 추가
                    const newOption = new Option(item.consultant, item.consultant);
                    consultantSelect.add(newOption);
                    consultantSelect.value = item.consultant;
                }
            } else {
                consultantSelect.value = '';
            }
            
            // 담당자 값 설정
            const managerSelect = document.getElementById('edit-case-manager');
            if (item.case_manager) {
                if (Array.from(managerSelect.options).some(option => option.value === item.case_manager)) {
                    managerSelect.value = item.case_manager;
                } else {
                    // 목록에 없는 담당자인 경우 새 옵션 추가
                    const newOption = new Option(item.case_manager, item.case_manager);
                    managerSelect.add(newOption);
                    managerSelect.value = item.case_manager;
                }
            } else {
                managerSelect.value = '';
            }
            
            document.getElementById('edit-deadline').value = item.deadline || '';
            document.getElementById('edit-submission-status').value = item.submission_status || '미제출';
            document.getElementById('edit-summit-date').value = item.summit_date || '';
            document.getElementById('edit-command').value = item.command || '';
            
            // 제출여부 변경 시 자동으로 제출일자 설정
            document.getElementById('edit-submission-status').addEventListener('change', function() {
                const status = this.value;
                if (status === '제출완료' || status === '안내완료' || status === '처리완료') {
                    if (!document.getElementById('edit-summit-date').value) {
                        document.getElementById('edit-summit-date').value = new Date().toISOString().split('T')[0];
                    }
                }
            });
            
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            modal.show();
        }
        
        // 메모 모달 열기 함수
        function openMemoModal(memo) {
            document.getElementById('memo-content').textContent = memo || '메모가 없습니다.';
            
            const modal = new bootstrap.Modal(document.getElementById('memoModal'));
            modal.show();
        }
        
        // 문서 업데이트 함수
        function updateDocument(id, data) {
            fetch(`{{ url('correction-manager') }}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // 모달 닫기
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                    modal.hide();
                    
                    // 데이터 다시 가져오기
                    resetAndFetchData();
                    
                    // 알림 표시
                    alert('수정되었습니다.');
                } else {
                    alert('수정 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error updating document:', error);
                alert('수정 중 오류가 발생했습니다.');
            });
        }
        
        // 문서 삭제 함수
        function deleteDocument(id) {
            fetch(`{{ url('correction-manager') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // 데이터 새로고침
                    resetAndFetchData();
                    
                    // 알림 표시
                    alert('문서가 삭제되었습니다.');
                } else {
                    alert('삭제 중 오류가 발생했습니다: ' + (result.message || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                console.error('Error deleting document:', error);
                alert('삭제 중 오류가 발생했습니다.');
            });
        }
        
        // 차트 관련 변수
        let documentChart = null;

        // 차트 토글 버튼
        document.getElementById('chart-toggle-btn').addEventListener('click', function() {
            const chartArea = document.getElementById('chart-area');
            if (chartArea.style.display === 'none') {
                chartArea.style.display = 'flex';
                // 차트가 없으면 로드
                if (!documentChart) {
                    loadChartData();
                }
            } else {
                chartArea.style.display = 'none';
            }
        });

        // 차트 데이터 로드
        function loadChartData() {
            fetch('{{ route('correction-manager.chart-data') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderChart(data.data);
                    } else {
                        console.error('차트 데이터를 불러오는데 실패했습니다.', data.message);
                    }
                })
                .catch(error => {
                    console.error('차트 데이터를 요청하는 중 오류가 발생했습니다.', error);
                });
        }

        // 차트 렌더링
        function renderChart(data) {
            const ctx = document.getElementById('documentChart').getContext('2d');
            
            // 기존 차트가 있으면 제거
            if (documentChart) {
                documentChart.destroy();
            }
            
            // 데이터 준비
            const labels = data.managers.map(manager => manager.name);
            const correctionReceivedData = data.managers.map(manager => manager.correction_received);
            const correctionNotReceivedData = data.managers.map(manager => manager.correction_not_received);
            const orderData = data.managers.map(manager => manager.order);
            const etcData = data.managers.map(manager => manager.etc);
            const exceptionData = data.managers.map(manager => manager.exception);
            
            // 차트 생성
            documentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '보정 (수신)',
                            data: correctionReceivedData,
                            backgroundColor: '#95C5FF', // 파란색
                            borderColor: '#7AB5FF',
                            borderWidth: 1,
                            borderRadius: {
                                topLeft: 4,
                                topRight: 4,
                                bottomLeft: 0,
                                bottomRight: 0
                            },
                            stack: 'correction',
                            barPercentage: 0.7
                        },
                        {
                            label: '보정 (미수신)',
                            data: correctionNotReceivedData,
                            backgroundColor: 'rgba(149, 197, 255, 0.3)', // 파란색 투명도 적용
                            borderColor: 'rgba(122, 181, 255, 0.3)',
                            borderWidth: 1,
                            borderRadius: {
                                topLeft: 0,
                                topRight: 0,
                                bottomLeft: 4,
                                bottomRight: 4
                            },
                            stack: 'correction',
                            barPercentage: 0.7
                        },
                        {
                            label: '명령',
                            data: orderData,
                            backgroundColor: '#FFC46B', // 주황색
                            borderColor: '#FFB94B',
                            borderWidth: 1,
                            borderRadius: {
                                topLeft: 4,
                                topRight: 4,
                                bottomLeft: 0,
                                bottomRight: 0
                            },
                            stack: 'others',
                            barPercentage: 0.7
                        },
                        {
                            label: '기타',
                            data: etcData,
                            backgroundColor: '#AAE9AA', // 초록색
                            borderColor: '#98D998',
                            borderWidth: 1,
                            borderRadius: 0,
                            stack: 'others',
                            barPercentage: 0.7
                        },
                        {
                            label: '예외',
                            data: exceptionData,
                            backgroundColor: '#FF9F9F', // 빨간색
                            borderColor: '#FF8A8A',
                            borderWidth: 1,
                            borderRadius: {
                                topLeft: 0,
                                topRight: 0,
                                bottomLeft: 4,
                                bottomRight: 4
                            },
                            stack: 'others',
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
                                text: '미제출 문서 수'
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

        // 초기 데이터 로드
        fetchData();
    });
</script>
@endpush