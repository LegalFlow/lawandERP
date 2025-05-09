@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    /* 전체 폰트 사이즈 축소 */
    .card-body {
        font-size: 0.875rem;
    }
    
    /* 입력 필드와 버튼 크기 조정 */
    .form-control, .btn {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* 0.5 너비 컬럼 정의 */
    .col-md-0-5 {
        flex: 0 0 auto;
        width: 4%;
    }
    
    /* 1.5 너비 컬럼 정의 */
    .col-md-1-5 {
        flex: 0 0 auto;
        width: 11%;
    }
    
    /* 순번 컬럼 스타일 */
    .col-number {
        flex: 0 0 auto;
        width: 4%;
        text-align: center;
    }
    
    .task-number {
        display: inline-block;
        width: 100%;
        text-align: center;
        font-weight: 500;
        color: #6c757d;
    }
    
    /* 카테고리 컬럼 스타일 */
    .col-category {
        flex: 0 0 auto;
        width: 11%;
    }
    
    /* 업무내용 컬럼 스타일 */
    .col-description {
        flex: 0 0 auto;
        width: 25%;
    }
    
    /* 상태 컬럼 스타일 */
    .col-status {
        flex: 0 0 auto;
        width: 8%;
    }
    
    /* 날짜 컬럼 스타일 */
    .col-date {
        flex: 0 0 auto;
        width: 11%;
    }
    
    /* 버튼 컬럼 스타일 */
    .col-button {
        flex: 0 0 auto;
        width: 4%;
        text-align: center;
    }
    
    /* 테이블 스타일 */
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .table td, .table th {
        padding: 0.5rem;
        vertical-align: middle;
    }
    
    /* 헤더 텍스트 줄바꿈 방지 */
    .row.fw-bold > div {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* 상태별 배지 스타일 */
    .badge-진행예정 {
        background-color: #6c757d;
    }
    
    .badge-진행중 {
        background-color: #007bff;
    }
    
    .badge-완료 {
        background-color: #28a745;
    }
    
    .badge-보류 {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-기각 {
        background-color: #dc3545;
    }
    
    /* 중요도별 배지 스타일 */
    .badge-매우중요 {
        background-color: #dc3545;
    }
    
    .badge-중요 {
        background-color: #fd7e14;
    }
    
    .badge-보통 {
        background-color: #6c757d;
    }
    
    .badge-낮음 {
        background-color: #20c997;
    }
    
    /* 우선순위별 배지 스타일 */
    .badge-최우선순위 {
        background-color: #dc3545;
    }
    
    .badge-우선순위 {
        background-color: #fd7e14;
    }
    
    /* 메모 아이콘 스타일 */
    .memo-icon {
        cursor: pointer;
        color: #6c757d;
    }
    
    .memo-icon:hover {
        color: #007bff;
    }
    
    /* 메모 버튼과 삭제 버튼 스타일 */
    .memo-btn, .delete-task {
        padding: 0.25rem 0.5rem;
        border: none;
        background: transparent;
    }
    
    .memo-btn i {
        color: #6c757d;
    }
    
    .memo-btn i:hover {
        color: #007bff;
    }
    
    .delete-task i {
        color: #dc3545;
    }
    
    .delete-task i:hover {
        color: #bd2130;
    }
    
    /* 토스트 알림 스타일 */
    .toast {
        font-size: 0.875rem;
    }
    
    /* 입력 필드 높이 조정 */
    .form-control {
        height: calc(1.5em + 0.5rem + 2px);
    }
    
    /* 셀렉트 박스 너비 조정 */
    select.category-type, select[data-field="category_type"] {
        width: 100%;
        min-width: 90px;
        font-size: 0.8rem;
    }
    
    select.category-detail, select[data-field="category_detail"] {
        width: 100%;
        min-width: 130px;
        font-size: 0.8rem;
    }
    
    /* 날짜 입력 필드 너비 조정 */
    input.date-field {
        width: 100%;
        min-width: 120px;
        max-width: 140px;
        padding-right: 30px; /* 달력 아이콘을 위한 여유 공간 */
    }
    
    /* 상태 셀렉트 박스 너비 조정 */
    select[data-field="status"] {
        width: 100%;
        min-width: 90px;
        font-size: 0.8rem;
    }
    
    /* 업무 항목 스타일 */
    .task-item {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        position: relative;
    }
    
    /* 상태별 배경색 */
    .task-item[data-status="진행예정"] {
        background-color: #f5f5f5;  /* 회색 */
    }
    
    .task-item[data-status="진행중"] {
        background-color: #fff8e1;  /* 연한 노란색 */
    }
    
    .task-item[data-status="완료"] {
        background-color: #c8e6c9;  /* 초록색 */
    }
    
    .task-item[data-status="기각"] {
        background-color: #ffebee;  /* 연한 붉은색 */
    }
    
    .task-item[data-status="보류"] {
        background-color: #e3f2fd;  /* 연한 푸른색 */
    }
    
    .task-item:hover {
        filter: brightness(0.95);
    }
    
    /* 행 정렬 스타일 */
    .task-item .row {
        flex-wrap: nowrap;
    }
    
    /* 삭제 버튼 스타일 */
    .delete-task {
        color: #dc3545;
    }
    
    .delete-task:hover {
        color: #bd2130;
    }
</style>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h4>업무 리스트</h4>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-outline-secondary me-2" id="toggle-view-btn">
                <i class="fas fa-eye"></i>
            </button>
            <button type="button" class="btn btn-primary" id="add-task-btn">
                <i class="bi bi-plus-lg"></i> 신규태스크
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <!-- 업무 리스트 헤더 -->
            <div class="row fw-bold mb-2 d-none d-md-flex">
                <div class="col-number">#</div>
                <div class="col-date">계획일자</div>
                <div class="col-date">데드라인</div>
                <div class="col-category">카테고리</div>
                <div class="col-category">하위카테고리</div>
                <div class="col-description">업무내용</div>
                <div class="col-status">상태</div>
                <div class="col-date">완료일자</div>
                <div class="col-button">메모</div>
                <div class="col-button">삭제</div>
            </div>
            
            <!-- 업무 리스트 항목들 -->
            <div id="task-list-container">
                @foreach($taskLists as $task)
                <div class="task-item" data-id="{{ $task->id }}" data-status="{{ $task->status }}">
                    <div class="row align-items-center">
                        <div class="col-number">
                            <span class="task-number">{{ $loop->iteration }}</span>
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field editable-field" data-field="plan_date" value="{{ $task->plan_date->format('Y-m-d') }}">
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field editable-field" data-field="deadline" value="{{ $task->deadline ? $task->deadline->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-category">
                            <select class="form-control editable-field" data-field="category_type">
                                <option value="">선택</option>
                                @foreach(array_keys($categories) as $category)
                                    <option value="{{ $category }}" {{ $task->category_type == $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-category">
                            <select class="form-control editable-field" data-field="category_detail" {{ empty($task->category_type) ? 'disabled' : '' }}>
                                <option value="">선택</option>
                                @if(!empty($task->category_type) && isset($categories[$task->category_type]))
                                    @foreach($categories[$task->category_type] as $detail)
                                        <option value="{{ $detail }}" {{ $task->category_detail == $detail ? 'selected' : '' }}>{{ $detail }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-description">
                            <input type="text" class="form-control editable-field" data-field="description" value="{{ $task->description }}" placeholder="업무내용 (필수)" required>
                        </div>
                        <div class="col-status">
                            <select class="form-control editable-field" data-field="status">
                                @foreach($statusOptions as $option)
                                    <option value="{{ $option }}" {{ $task->status == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field editable-field" data-field="completion_date" value="{{ $task->completion_date ? $task->completion_date->format('Y-m-d') : '' }}" {{ $task->status !== '완료' ? 'disabled' : '' }}>
                        </div>
                        <div class="col-button">
                            <button type="button" class="btn btn-sm memo-btn" data-memo="{{ $task->memo }}" data-id="{{ $task->id }}">
                                <i class="fas fa-sticky-note {{ $task->memo ? 'text-warning' : '' }}"></i>
                            </button>
                        </div>
                        <div class="col-button">
                            <button type="button" class="btn btn-sm delete-task" data-id="{{ $task->id }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- 메모 모달 -->
<div class="modal fade" id="memoModal" tabindex="-1" aria-labelledby="memoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 50%; max-height: 50%;">
        <div class="modal-content" style="height: 50vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="memoModalLabel">메모</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="height: calc(50vh - 130px); overflow-y: auto;">
                <input type="hidden" id="memo-task-id">
                <textarea class="form-control" id="memo-content" style="height: 100%; width: 100%; resize: none;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="save-memo-btn">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 업무를 삭제하시겠습니까?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">삭제</button>
            </div>
        </div>
    </div>
</div>

<!-- 토스트 알림 컨테이너 -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i> 변경사항이 저장되었습니다.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
    <div id="savingToast" class="toast align-items-center text-white bg-info border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-arrow-repeat me-2"></i> 저장 중...
            </div>
        </div>
    </div>
</div>

<!-- 카테고리 데이터 -->
<script>
    const categories = @json($categories ?? []);
    const statusOptions = @json($statusOptions ?? []);
    const importanceOptions = @json($importanceOptions ?? []);
    const priorityOptions = @json($priorityOptions ?? []);
    
    // 로그인한 사용자의 팀 정보에 따른 기본 카테고리 설정
    let defaultCategory = '회생'; // 기본값
    
    @if(Auth::user()->member && Auth::user()->member->task)
        @php
            $userTask = Auth::user()->member->task;
            if (strpos($userTask, '개발팀') !== false) {
                $defaultCat = '개발';
            } elseif (strpos($userTask, '지원팀') !== false) {
                $defaultCat = '지원';
            } elseif (strpos($userTask, '법률컨설팅팀') !== false || strpos($userTask, '사건관리팀') !== false) {
                $defaultCat = '회생';
            } else {
                $defaultCat = '회생';
            }
        @endphp
        defaultCategory = '{{ $defaultCat }}';
    @endif
</script>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // 토스트 객체 생성
        const saveToast = new bootstrap.Toast(document.getElementById('saveToast'), {
            delay: 2000,
            autohide: true
        });
        
        const errorToast = new bootstrap.Toast(document.getElementById('errorToast'), {
            delay: 5000,
            autohide: true
        });
        
        const savingToast = new bootstrap.Toast(document.getElementById('savingToast'), {
            autohide: false
        });
        
        // 모달 객체 초기화
        const memoModal = new bootstrap.Modal(document.getElementById('memoModal'));
        const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        
        // 초기 상태 필터링 설정 (localStorage에서 상태 복원)
        let showAllStatus = localStorage.getItem('showAllStatus') === 'true';
        
        // 아이콘 초기 상태 설정
        updateViewIcon();
        
        // 초기 상태 필터링 적용
        filterTasks();
        
        // 상태 토글 버튼 클릭 이벤트
        $('#toggle-view-btn').click(function() {
            showAllStatus = !showAllStatus;
            // 상태를 localStorage에 저장
            localStorage.setItem('showAllStatus', showAllStatus);
            
            updateViewIcon();
            filterTasks();
        });
        
        // 아이콘 상태 업데이트 함수
        function updateViewIcon() {
            const icon = $('#toggle-view-btn').find('i');
            if (showAllStatus) {
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }
        
        // 태스크 필터링 함수 수정
        function filterTasks() {
            let visibleCount = 0;
            $('.task-item').each(function() {
                const status = $(this).attr('data-status');
                if (!showAllStatus) {
                    // 완료나 기각 상태인 경우 숨김
                    if (status === '완료' || status === '기각') {
                        $(this).hide();
                    } else {
                        $(this).show();
                        visibleCount++;
                        $(this).find('.task-number').text(visibleCount);
                    }
                } else {
                    // 모든 상태 표시
                    $(this).show();
                    visibleCount++;
                    $(this).find('.task-number').text(visibleCount);
                }
            });
        }
        
        // 상태 변경 시 배경색 업데이트
        $(document).on('change', 'select[data-field="status"]', function() {
            const status = $(this).val();
            const taskItem = $(this).closest('.task-item');
            taskItem.attr('data-status', status);
            
            // 상태 필터링 적용
            if (!showAllStatus && (status === '완료' || status === '기각')) {
                taskItem.hide();
            }
        });
        
        // 업무 추가 버튼 클릭 이벤트
        $('#add-task-btn').click(function() {
            addNewTask();
        });
        
        // 새 업무 추가 함수
        function addNewTask() {
            const tempId = Date.now();
            const today = new Date().toISOString().split('T')[0];
            
            // 기본 카테고리 및 하위 카테고리 설정
            let defaultCategoryDetail = '';
            if (categories[defaultCategory] && categories[defaultCategory].length > 0) {
                defaultCategoryDetail = categories[defaultCategory][0];
            }
            
            const taskHtml = `
                <div class="task-item" data-temp-id="${tempId}">
                    <div class="row align-items-center">
                        <div class="col-number">
                            <span class="task-number">-</span>
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field auto-save" data-field="plan_date" value="${today}">
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field auto-save" data-field="deadline">
                        </div>
                        <div class="col-category">
                            <select class="form-control auto-save" data-field="category_type">
                                <option value="">선택</option>
                                ${Object.keys(categories).map(cat => `<option value="${cat}" ${cat === defaultCategory ? 'selected' : ''}>${cat}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-category">
                            <select class="form-control auto-save" data-field="category_detail">
                                <option value="">선택</option>
                                ${categories[defaultCategory] ? categories[defaultCategory].map(detail => `<option value="${detail}" ${detail === defaultCategoryDetail ? 'selected' : ''}>${detail}</option>`).join('') : ''}
                            </select>
                        </div>
                        <div class="col-description">
                            <input type="text" class="form-control auto-save" data-field="description" placeholder="업무내용" required>
                        </div>
                        <div class="col-status">
                            <select class="form-control auto-save" data-field="status">
                                ${statusOptions.map(status => `<option value="${status}" ${status === '진행예정' ? 'selected' : ''}>${status}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-date">
                            <input type="date" class="form-control date-field auto-save" data-field="completion_date" disabled>
                        </div>
                        <div class="col-button">
                            <button type="button" class="btn btn-sm memo-btn" data-memo="" data-temp-id="${tempId}">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                        </div>
                        <div class="col-button">
                            <button type="button" class="btn btn-sm delete-task" data-temp-id="${tempId}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#task-list-container').prepend(taskHtml);
            
            // 새 태스크 생성 후 바로 저장
            const taskItem = $(`.task-item[data-temp-id="${tempId}"]`);
            saveTask(taskItem);
            
            // 새 태스크의 업무내용 필드에 포커스
            taskItem.find('[data-field="description"]').focus();
        }
        
        // 카테고리 변경 이벤트 (신규 태스크)
        $(document).on('change', 'select[data-field="category_type"]', function() {
            const categoryType = $(this).val();
            const detailDropdown = $(this).closest('.row').find('select[data-field="category_detail"]');
            
            detailDropdown.empty().append('<option value="">선택</option>');
            
            if (categories[categoryType]) {
                categories[categoryType].forEach(detail => {
                    detailDropdown.append(`<option value="${detail}">${detail}</option>`);
                });
                detailDropdown.prop('disabled', false);
            } else {
                detailDropdown.prop('disabled', true);
            }
            
            // 카테고리가 변경되면 하위 카테고리를 초기화하고 첫 번째 옵션 선택
            if (categories[categoryType] && categories[categoryType].length > 0) {
                detailDropdown.val(categories[categoryType][0]);
            } else {
                detailDropdown.val('');
            }
            
            // 변경된 하위 카테고리 값도 저장
            const taskItem = $(this).closest('.task-item');
            saveTask(taskItem);
        });
        
        // 하위 카테고리 변경 이벤트
        $(document).on('change', 'select[data-field="category_detail"]', function() {
            // 변경된 하위 카테고리 값 저장
            const taskItem = $(this).closest('.task-item');
            saveTask(taskItem);
        });
        
        // 상태 변경 이벤트 - 완료일자 필드 활성화/비활성화
        $(document).on('change', 'select[data-field="status"]', function() {
            const status = $(this).val();
            const completionDateField = $(this).closest('.row').find('input[data-field="completion_date"]');
            
            if (status === '완료') {
                completionDateField.prop('disabled', false);
                // 완료일자가 비어있으면 현재 날짜로 설정
                if (!completionDateField.val()) {
                    const today = new Date().toISOString().split('T')[0];
                    completionDateField.val(today);
                }
            } else {
                completionDateField.prop('disabled', true);
                completionDateField.val('');
            }
        });
        
        // 자동 저장 이벤트
        $(document).on('focus', '.auto-save, .editable-field', function() {
            // 포커스 시 현재 값 저장
            $(this).data('original-value', $(this).val());
        });
        
        $(document).on('blur', '.auto-save, .editable-field', function() {
            const taskItem = $(this).closest('.task-item');
            const originalValue = $(this).data('original-value');
            const currentValue = $(this).val();
            
            // 값이 변경된 경우에만 저장 시도
            if (originalValue !== currentValue) {
                console.log('값 변경됨:', originalValue, '->', currentValue);
                
                // 값이 변경되면 바로 저장 진행
                saveTask(taskItem);
            }
        });
        
        // 메모 버튼 클릭 이벤트
        $(document).on('click', '.memo-btn', function() {
            const taskId = $(this).data('id');
            const tempId = $(this).data('temp-id');
            const memo = $(this).data('memo') || '';
            
            $('#memo-task-id').val(taskId || '');
            $('#memo-content').val(memo);
            
            // 임시 ID가 있는 경우 저장
            if (tempId) {
                $('#memoModal').data('temp-id', tempId);
            } else {
                $('#memoModal').removeData('temp-id');
            }
            
            memoModal.show();
        });
        
        // 메모 저장 버튼 클릭 이벤트
        $('#save-memo-btn').click(function() {
            const taskId = $('#memo-task-id').val();
            const tempId = $('#memoModal').data('temp-id');
            const memo = $('#memo-content').val();
            
            if (taskId) {
                // 기존 태스크의 메모 업데이트
                savingToast.show();
                
                $.ajax({
                    url: `/task-lists/${taskId}`,
                    method: 'PUT',
                    data: {
                        memo: memo,
                        // 중요도와 우선순위 기본값 추가 (UI에서 제거된 필드)
                        importance: '보통',
                        priority: '보통',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        savingToast.hide();
                        saveToast.show();
                        
                        // 메모 버튼 데이터 업데이트
                        const memoBtn = $(`.memo-btn[data-id="${taskId}"]`);
                        memoBtn.data('memo', memo);
                        
                        // 메모 아이콘 색상 업데이트
                        const memoIcon = memoBtn.find('i.fas.fa-sticky-note');
                        if (memo) {
                            memoIcon.addClass('text-warning');
                        } else {
                            memoIcon.removeClass('text-warning');
                        }
                        
                        memoModal.hide();
                    },
                    error: function(xhr, status, error) {
                        savingToast.hide();
                        errorToast.show();
                        
                        // 오류 메시지를 토스트에 표시
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> ${xhr.responseJSON.error}`);
                        } else {
                            $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다. (${status}: ${error})`);
                        }
                    }
                });
            } else if (tempId) {
                // 임시 태스크의 메모 저장
                const memoBtn = $(`.memo-btn[data-temp-id="${tempId}"]`);
                memoBtn.data('memo', memo);
                
                // 메모 아이콘 색상 업데이트
                const memoIcon = memoBtn.find('i.fas.fa-sticky-note');
                if (memo) {
                    memoIcon.addClass('text-warning');
                } else {
                    memoIcon.removeClass('text-warning');
                }
                
                // 태스크 저장
                const taskItem = $(`.task-item[data-temp-id="${tempId}"]`);
                saveTask(taskItem);
                
                memoModal.hide();
            }
        });
        
        // 삭제 버튼 클릭 이벤트
        $(document).on('click', '.delete-task', function() {
            const taskId = $(this).data('id');
            const tempId = $(this).data('temp-id');
            
            if (tempId) {
                // 임시 태스크는 바로 삭제
                $(`.task-item[data-temp-id="${tempId}"]`).remove();
                return;
            }
            
            // 삭제 확인 모달 표시
            $('#confirm-delete-btn').data('id', taskId);
            deleteConfirmModal.show();
        });
        
        // 삭제 확인 버튼 클릭 이벤트
        $('#confirm-delete-btn').click(function() {
            const taskId = $(this).data('id');
            
            if (!taskId) {
                return;
            }
            
            savingToast.show();
            
            $.ajax({
                url: `/task-lists/${taskId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    savingToast.hide();
                    
                    // 성공 메시지 표시
                    $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> 삭제되었습니다.');
                    saveToast.show();
                    
                    // 삭제 모달 닫기
                    deleteConfirmModal.hide();
                    
                    // 1초 후 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    savingToast.hide();
                    errorToast.show();
                    
                    // 오류 메시지를 토스트에 표시
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> ${xhr.responseJSON.error}`);
                    } else {
                        $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다. (${status}: ${error})`);
                    }
                }
            });
        });
        
        // 태스크 저장 함수
        function saveTask(taskItem) {
            const taskId = taskItem.data('id');
            const tempId = taskItem.data('temp-id');
            const isNew = !taskId;
            
            // 필드 값 수집
            const data = {};
            taskItem.find('[data-field]').each(function() {
                const field = $(this).data('field');
                const value = $(this).val();
                if (value !== '') {
                    data[field] = value;
                }
            });
            
            // 중요도와 우선순위 기본값 추가
            data.importance = '보통';
            data.priority = '보통';
            
            // CSRF 토큰 추가
            data._token = $('meta[name="csrf-token"]').attr('content');
            
            // 저장 중 알림 표시
            savingToast.show();
            
            // API 엔드포인트 설정
            const url = isNew ? '/task-lists' : `/task-lists/${taskId}`;
            const method = isNew ? 'POST' : 'PUT';
            
            // AJAX 요청
            $.ajax({
                url: url,
                method: method,
                data: data,
                success: function(response) {
                    savingToast.hide();
                    saveToast.show();
                    
                    if (response.success) {
                        // 새 태스크인 경우
                        if (isNew && response.id) {
                            // 임시 ID를 실제 ID로 변경
                            taskItem.attr('data-id', response.id);
                            taskItem.removeAttr('data-temp-id');
                            
                            // 메모 버튼 업데이트
                            const memoBtn = taskItem.find('.memo-btn');
                            memoBtn.attr('data-id', response.id);
                            memoBtn.removeAttr('data-temp-id');
                            
                            // 삭제 버튼 업데이트
                            const deleteBtn = taskItem.find('.delete-task');
                            deleteBtn.attr('data-id', response.id);
                            deleteBtn.removeAttr('data-temp-id');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    savingToast.hide();
                    errorToast.show();
                    
                    // 오류 메시지를 토스트에 표시
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> ${xhr.responseJSON.message}`);
                    } else {
                        $('#errorToast .toast-body').html(`<i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다. (${status}: ${error})`);
                    }
                    
                    console.error('저장 오류:', xhr.responseJSON);
                }
            });
        }
    });
</script>
@endpush