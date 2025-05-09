@extends('layouts.app')

@section('content')
<style>
    /* 전체 폰트 사이즈 축소 */
    .card-body {
        font-size: 0.875rem; /* 기본 폰트 사이즈에서 2pt 정도 축소 */
    }
    
    /* 입력 필드와 버튼 크기 조정 */
    .form-control, .btn {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* 상위 태스크 카드 배경색 */
    .root-task .card {
        background-color: #f8f9fa; /* 연한 회색 배경 */
        border: 1px solid #e9ecef; /* 약간 더 진한 테두리 */
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* 미세한 그림자 효과 */
    }
    
    /* 하위 태스크 구분을 위한 스타일 */
    .subtasks-container {
        border-left: 1px solid #dee2e6;
        margin-top: 0.5rem;
        padding-left: 1rem !important; /* 들여쓰기 조정 */
    }
    
    /* 넘버링 텍스트 크기 조정 */
    .task-number {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    /* 카드 헤더 폰트 크기 조정 */
    .card-header h5 {
        font-size: 1.1rem;
    }
    
    /* 태스크 간 간격 조정 */
    .root-task {
        margin-bottom: 1rem !important;
    }
    
    .subtask {
        margin-bottom: 0.5rem !important;
    }
    
    /* 토스트 알림 크기 조정 */
    .toast {
        font-size: 0.875rem;
    }
    
    /* 입력 필드 높이 조정 */
    .form-control {
        height: calc(1.5em + 0.5rem + 2px);
    }
    
    /* 셀렉트 박스 너비 조정 */
    select.category-type, select[data-field="category_type"] {
        width: 120px;
    }
    
    select.category-detail, select[data-field="category_detail"] {
        width: 150px;
    }
    
    /* 시간 입력 필드 너비 조정 */
    input.time-input, input[data-field="start_time"], input[data-field="end_time"] {
        width: 80px;
        text-align: center;
    }
    
    /* 배지 스타일링 */
    .badge-expected-hours {
        background-color: #E0F7FA; /* 파스텔 민트 */
        color: #00838F;
        font-weight: 500;
    }
    
    .badge-total-hours {
        background-color: #E8F5E9; /* 파스텔 그린 */
        color: #2E7D32;
        font-weight: 500;
    }
    
    /* 배지 컨테이너 */
    .badges-container {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    /* 소요시간이 입력된 태스크 카드 배경색 */
    .bg-completed-task {
        background-color: #e8f5e9 !important; /* 파스텔톤 초록색 배경 */
        border: 1px solid #c8e6c9 !important; /* 약간 더 진한 초록색 테두리 */
    }
    
    /* 버튼 스타일 개선 */
    .btn-outline-action {
        background-color: transparent;
        border: 1px solid;
        padding: 0.2rem 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-outline-add {
        color: #28a745;
        border-color: #28a745;
    }
    
    .btn-outline-add:hover {
        background-color: rgba(40, 167, 69, 0.1);
    }
    
    .btn-outline-delete {
        color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-outline-delete:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .btn-outline-primary {
        color: #007bff;
        border-color: #007bff;
        background-color: transparent;
    }
    
    .btn-outline-primary:hover {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    /* 날짜 표시 스타일 개선 */
    .date-display {
        display: flex;
        align-items: center;
        font-weight: 500;
    }
    
    .date-number {
        font-size: 1rem;
        color: #333;
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 4px 10px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    .date-weekday {
        font-size: 0.85rem;
        color: #6c757d;
        margin-left: 8px;
        background-color: #e9ecef;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 400;
    }
    
    /* 근무형태 배지 스타일 */
    .work-status-badge {
        font-size: 0.8rem;
        margin-left: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 500;
    }
    
    .work-status-work {
        background-color: #E3F2FD;
        color: #1565C0;
    }
    
    .work-status-remote {
        background-color: #E8F5E9;
        color: #2E7D32;
    }
    
    .work-status-vacation {
        background-color: #FFF3E0;
        color: #E65100;
    }
    
    .work-status-off {
        background-color: #FFEBEE;
        color: #C62828;
    }
    
    .work-status-half {
        background-color: #F3E5F5;
        color: #6A1B9A;
    }
    
    .work-status-holiday {
        background-color: #E0F7FA;
        color: #00838F;
    }
    
    .work-time {
        font-size: 0.8rem;
        color: #555;
        margin-left: 8px;
        font-weight: 400;
    }
    
    /* 신규상담 테이블 스타일 */
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* 문서명 텍스트 처리 */
    .document-name-cell {
        max-width: 150px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        position: relative;
    }
    
    .document-name-cell:hover::after {
        content: attr(data-full-text);
        position: absolute;
        left: 0;
        top: 100%;
        z-index: 1000;
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 5px 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        white-space: normal;
        max-width: 300px;
        font-size: 0.85rem;
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">업무일지</h5>
                    <div class="d-flex align-items-center">
                        <select id="user-selector" class="form-control me-2" style="width: 150px;">
                            @foreach($allUsers as $u)
                                <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <button id="prev-date-btn" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <input type="date" id="date-picker" class="form-control" value="{{ $selectedDate }}">
                        <button id="next-date-btn" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="date-display">
                            <span class="date-number">{{ \Carbon\Carbon::parse($selectedDate)->format('Y-m-d') }}</span>
                            <span class="date-weekday">{{ \Carbon\Carbon::parse($selectedDate)->locale('ko')->isoFormat('dddd') }}</span>
                            @if($workHour)
                                @php
                                    $statusClass = match($workHour->status) {
                                        '근무' => 'work-status-work',
                                        '재택' => 'work-status-remote',
                                        '연차' => 'work-status-vacation',
                                        '휴무' => 'work-status-off',
                                        '오전반차', '오후반차' => 'work-status-half',
                                        '공휴일' => 'work-status-holiday',
                                        default => 'work-status-work'
                                    };
                                    
                                    $startTime = $workHour->start_time ? (is_object($workHour->start_time) ? $workHour->start_time->format('H시 i분') : \Carbon\Carbon::parse($workHour->start_time)->format('H시 i분')) : '';
                                    $endTime = $workHour->end_time ? (is_object($workHour->end_time) ? $workHour->end_time->format('H시 i분') : \Carbon\Carbon::parse($workHour->end_time)->format('H시 i분')) : '';
                                @endphp
                                <span class="work-status-badge {{ $statusClass }}">{{ $workHour->status }}</span>
                                @if($startTime && $endTime)
                                    <span class="work-time">{{ $startTime }} ~ {{ $endTime }}</span>
                                @endif
                            @endif
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="badges-container">
                                <span class="badge badge-expected-hours">
                                    근로예정시간: {{ $expectedWorkHours }}시간 {{ $expectedWorkMinutes }}분
                                </span>
                                <span class="badge badge-total-hours">
                                    총 업무시간: <span id="total-duration-hours">{{ floor(($workLog->total_duration_minutes ?? 0) / 60) }}</span>시간 <span id="total-duration-minutes">{{ ($workLog->total_duration_minutes ?? 0) % 60 }}</span>분
                                </span>
                            </div>
                            @if(Auth::id() == $selectedUserId)
                            <button id="add-root-task" class="btn btn-outline-primary ms-3">
                                <i class="bi bi-plus-lg"></i> 신규태스크
                            </button>
                            <button id="import-tasks-btn" class="btn btn-outline-primary ms-2">
                                <i class="bi bi-download"></i> 업무리스트
                            </button>
                            @endif
                        </div>
                    </div>

                    <div id="tasks-container">
                        @foreach($rootTasks as $task)
                            @include('work_logs.partials.task', ['task' => $task, 'isRoot' => true])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 신규상담 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 신건상담</h5>
                </div>
                <div class="card-body">
                    @if(count($consultations) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>시간</th>
                                        <th>사건유형</th>
                                        <th>고객명</th>
                                        <th>전화번호</th>
                                        <th>지역</th>
                                        <th>진행현황</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consultations as $index => $consultation)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($consultation->create_dt)->format('H시 i분') }}</td>
                                            <td>
                                                @php
                                                    $caseTypeValue = $consultation->case_type ?? 1;
                                                    $caseTypeLabel = $caseTypeValue == 1 ? '개인회생' : ($caseTypeValue == 2 ? '개인파산' : '기타');
                                                @endphp
                                                {{ $caseTypeLabel }}
                                            </td>
                                            <td>{{ $consultation->name }}</td>
                                            <td>{{ $consultation->phone }}</td>
                                            <td>{{ $consultation->living_place }}</td>
                                            <td>
                                                @php
                                                    $caseType = $consultation->div_case ?? 1; // 기본값 1 (회생)
                                                    $stateValue = $consultation->case_state ?? 0;
                                                    $stateLabel = \App\Helpers\CaseStateHelper::getStateLabel($caseType, $stateValue);
                                                @endphp
                                                {{ $stateLabel }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 신건건상담이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 신규계약 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 신건계약</h5>
                </div>
                <div class="card-body">
                    @if(count($contracts) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>상담일자</th>
                                        <th>사건유형</th>
                                        <th>고객명</th>
                                        <th>계약일자</th>
                                        <th>수임료</th>
                                        <th>송인부</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contracts as $index => $contract)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($contract->create_dt)->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $caseTypeValue = $contract->case_type ?? 1;
                                                    $caseTypeLabel = $caseTypeValue == 1 ? '개인회생' : ($caseTypeValue == 2 ? '개인파산' : '기타');
                                                @endphp
                                                {{ $caseTypeLabel }}
                                            </td>
                                            <td>{{ $contract->name }}</td>
                                            <td>{{ $contract->contract_date }}</td>
                                            <td>{{ number_format($contract->lawyer_fee ?? 0) }}원</td>
                                            <td>
                                                @php
                                                    $totalCost = ($contract->total_const_delivery ?? 0) + 
                                                                ($contract->stamp_fee ?? 0) + 
                                                                ($contract->total_debt_cert_cost ?? 0);
                                                @endphp
                                                {{ number_format($totalCost) }}원
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 신건건계약이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 배당 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 신건배당</h5>
                </div>
                <div class="card-body">
                    @if(count($assignments) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>배당일자</th>
                                        <th>사건유형</th>
                                        <th>고객명</th>
                                        <th>계약자</th>
                                        <th>담당자</th>
                                        <th>관할법원</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignments as $index => $assignment)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($assignment->assignment_date)->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $caseTypeValue = $assignment->case_type ?? 1;
                                                    $caseTypeLabel = $caseTypeValue == 1 ? '개인회생' : ($caseTypeValue == 2 ? '개인파산' : '기타');
                                                @endphp
                                                {{ $caseTypeLabel }}
                                            </td>
                                            <td>{{ $assignment->client_name }}</td>
                                            <td>{{ $assignment->consultant }}</td>
                                            <td>{{ $assignment->case_manager }}</td>
                                            <td>{{ $assignment->court_name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 신건배당이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 신건제출 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 신건제출</h5>
                </div>
                <div class="card-body">
                    @if(count($submissions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>제출일자</th>
                                        <th>사건유형</th>
                                        <th>고객명</th>
                                        <th>계약자</th>
                                        <th>관할</th>
                                        <th>사건번호</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submissions as $index => $submission)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ \Carbon\Carbon::parse($submission->summit_date)->format('Y-m-d') }}</td>
                                            <td>
                                                @php
                                                    $caseTypeValue = $submission->case_type ?? 1;
                                                    $caseTypeLabel = $caseTypeValue == 1 ? '개인회생' : ($caseTypeValue == 2 ? '개인파산' : '기타');
                                                @endphp
                                                {{ $caseTypeLabel }}
                                            </td>
                                            <td>{{ $submission->client_name }}</td>
                                            <td>{{ $submission->consultant }}</td>
                                            <td>{{ $submission->court_name }}</td>
                                            <td>{{ $submission->case_number }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 신건제출이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 보정서제출 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 보정서제출</h5>
                </div>
                <div class="card-body">
                    @if(count($corrections) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>제출일자</th>
                                        <th>수신일자</th>
                                        <th>고객명</th>
                                        <th>문서명</th>
                                        <th>계약자</th>
                                        <th>관할</th>
                                        <th>사건번호</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($corrections as $index => $correction)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $correction->summit_date ? \Carbon\Carbon::parse($correction->summit_date)->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $correction->receipt_date ? \Carbon\Carbon::parse($correction->receipt_date)->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $correction->name }}</td>
                                            <td>
                                                <div class="document-name-cell" data-full-text="{{ $correction->document_name }}">
                                                    {{ $correction->document_name }}
                                                </div>
                                            </td>
                                            <td>{{ $correction->consultant }}</td>
                                            <td>{{ $correction->court_name }}</td>
                                            <td>{{ $correction->case_number }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 보정서제출이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 기타보정제출 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 기타보정제출</h5>
                </div>
                <div class="card-body">
                    @if(count($otherCorrections) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>제출일자</th>
                                        <th>수신일자</th>
                                        <th>고객명</th>
                                        <th>문서명</th>
                                        <th>제출여부</th>
                                        <th>계약자</th>
                                        <th>관할</th>
                                        <th>사건번호</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($otherCorrections as $index => $correction)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $correction->summit_date ? \Carbon\Carbon::parse($correction->summit_date)->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $correction->receipt_date ? \Carbon\Carbon::parse($correction->receipt_date)->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $correction->name }}</td>
                                            <td>
                                                <div class="document-name-cell" data-full-text="{{ $correction->document_name }}">
                                                    {{ $correction->document_name }}
                                                </div>
                                            </td>
                                            <td>{{ $correction->submission_status }}</td>
                                            <td>{{ $correction->consultant }}</td>
                                            <td>{{ $correction->court_name }}</td>
                                            <td>{{ $correction->case_number }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 기타보정제출이 없습니다.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 입금내역 데이터 테이블 -->
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">오늘의 입금내역</h5>
                </div>
                <div class="card-body">
                    @if(count($payments) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>순번</th>
                                        <th>결제시간</th>
                                        <th>계좌정보</th>
                                        <th>고객명</th>
                                        <th>계정</th>
                                        <th>금액</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $index => $payment)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $payment['time'] }}</td>
                                            <td>{{ $payment['account_info'] }}</td>
                                            <td>{{ $payment['customer_name'] }}</td>
                                            <td>{{ $payment['account_type'] }}</td>
                                            <td>{{ number_format($payment['amount']) }}원</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            오늘 등록된 입금내역이 없습니다.
                        </div>
                    @endif
                </div>
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

<!-- 업무 리스트 가져오기 모달 -->
<div class="modal fade" id="importTasksModal" tabindex="-1" aria-labelledby="importTasksModalLabel" aria-hidden="true">
    <!-- 모달 내용은 AJAX로 로드됩니다 -->
</div>

<!-- 카테고리 데이터 -->
<script>
    const categories = @json($categories ?? []);
    const defaultCategory = @json($defaultCategory ?? '회생');
    const defaultStartTime = @json($defaultStartTime ?? null);
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
            delay: 3000,
            autohide: true
        });
        
        const savingToast = new bootstrap.Toast(document.getElementById('savingToast'), {
            autohide: false
        });
        
        // 토스트 객체를 전역 변수로 노출
        window.saveToast = saveToast;
        window.errorToast = errorToast;
        window.savingToast = savingToast;
        
        // 시간 입력 처리 함수
        function formatTimeInput(input) {
            let value = input.val().replace(/\D/g, ''); // 숫자만 남김
            
            if (value.length === 3) {
                // 3자리 숫자인 경우 (예: 930 -> 09:30)
                value = '0' + value;
            }
            
            if (value.length === 4) {
                // 4자리 숫자인 경우 (예: 1430 -> 14:30)
                const hours = value.substring(0, 2);
                const minutes = value.substring(2, 4);
                
                // 시간과 분이 유효한지 확인
                if (parseInt(hours) < 24 && parseInt(minutes) < 60) {
                    // 포맷팅된 시간 설정
                    input.val(hours + ':' + minutes);
                    return true;
                } else {
                    // 유효하지 않은 시간
                    input.val('');
                    return false;
                }
            }
            
            return false;
        }
        
        // 시간 입력 필드 이벤트 처리
        $(document).on('input', '.time-input', function() {
            // 숫자만 입력 가능하도록
            $(this).val($(this).val().replace(/\D/g, ''));
        });
        
        $(document).on('blur', '.time-input', function() {
            // 포커스를 잃을 때 시간 포맷팅
            formatTimeInput($(this));
        });
        
        $(document).on('keyup', '.time-input', function(e) {
            // 4자리 숫자 입력 완료 시 자동 포맷팅 및 저장
            if ($(this).val().length === 4) {
                if (formatTimeInput($(this))) {
                    // 포맷팅 성공 시 자동 저장
                    const taskItem = $(this).closest('.task-item');
                    saveTask(taskItem);
                }
            }
        });
        
        // 시작시간과 종료시간으로 소요시간 계산 함수
        function calculateDuration(startTime, endTime) {
            if (!startTime || !endTime) return 0;
            
            try {
                // 시간 문자열 파싱 (HH:MM 형식)
                const [startHours, startMinutes] = startTime.split(':').map(Number);
                const [endHours, endMinutes] = endTime.split(':').map(Number);
                
                // 분으로 변환
                let startTotalMinutes = (startHours * 60) + startMinutes;
                let endTotalMinutes = (endHours * 60) + endMinutes;
                
                // 종료 시간이 시작 시간보다 작으면 다음 날로 간주
                if (endTotalMinutes < startTotalMinutes) {
                    endTotalMinutes += 24 * 60; // 24시간(1440분) 추가
                }
                
                // 소요 시간 계산 (분)
                return endTotalMinutes - startTotalMinutes;
            } catch (e) {
                console.error('시간 계산 오류:', e);
                return 0;
            }
        }
        
        // 날짜 선택 시 페이지 이동
        $('#date-picker').change(function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', $(this).val());
            window.location.href = currentUrl.toString();
        });
        
        // 사용자 선택 시 페이지 이동 (관리자용)
        $('#user-selector').change(function() {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('user_id', $(this).val());
            window.location.href = currentUrl.toString();
        });
        
        // 이전 날짜 버튼 클릭 이벤트
        $('#prev-date-btn').click(function() {
            const currentDate = new Date($('#date-picker').val());
            currentDate.setDate(currentDate.getDate() - 1);
            const prevDate = currentDate.toISOString().split('T')[0];
            
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', prevDate);
            window.location.href = currentUrl.toString();
        });
        
        // 다음 날짜 버튼 클릭 이벤트
        $('#next-date-btn').click(function() {
            const currentDate = new Date($('#date-picker').val());
            currentDate.setDate(currentDate.getDate() + 1);
            const nextDate = currentDate.toISOString().split('T')[0];
            
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', nextDate);
            window.location.href = currentUrl.toString();
        });

        // 최상위 태스크 추가
        $('#add-root-task').click(function() {
            addNewTask(null);
            updateTaskNumbering(); // 넘버링 업데이트
        });

        // 태스크 추가 함수
        function addNewTask(parentId) {
            const workLogId = {{ $workLog->id }};
            let taskHtml = '';
            let tempId = Date.now();
            
            if (parentId === null) {
                // 시작시각 기본값 설정
                let startTimeValue = '';
                if (defaultStartTime) {
                    // 기본값이 있으면 설정
                    startTimeValue = defaultStartTime;
                }
                
                // 최상위 태스크 템플릿
                taskHtml = `
                    <div class="task-item root-task mb-3" data-temp-id="${tempId}">
                        <div class="card" id="card-${tempId}">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="task-number me-2"></span>
                                    <div class="form-group mb-0 me-2">
                                        <select class="form-control category-type auto-save" data-temp-id="${tempId}">
                                            <option value="">카테고리 선택</option>
                                            ${Object.keys(categories).map(cat => `<option value="${cat}" ${cat === defaultCategory ? 'selected' : ''}>${cat}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div class="form-group mb-0 me-2">
                                        <select class="form-control category-detail auto-save" data-temp-id="${tempId}">
                                            <option value="">세부 카테고리 선택</option>
                                            ${categories[defaultCategory] ? categories[defaultCategory].map(detail => `<option value="${detail}">${detail}</option>`).join('') : ''}
                                        </select>
                                    </div>
                                    <div class="form-group mb-0 me-2">
                                        <input type="text" class="form-control time-input task-start-time auto-save" data-temp-id="${tempId}" placeholder="시작시각" maxlength="4" value="${startTimeValue}">
                                    </div>
                                    <div class="form-group mb-0 me-2">
                                        <input type="text" class="form-control time-input task-end-time auto-save" data-temp-id="${tempId}" placeholder="종료시각" maxlength="4">
                                    </div>
                                    <div class="form-group mb-0 flex-grow-1 me-2">
                                        <input type="text" class="form-control task-description auto-save" data-temp-id="${tempId}" placeholder="태스크 설명">
                                    </div>
                                    <button class="btn btn-outline-action btn-outline-add add-subtask me-2">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button class="btn btn-outline-action btn-outline-delete delete-existing-task">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                </div>
                                <div class="subtasks-container ps-4"></div>
                            </div>
                        </div>
                    </div>
                `;
                $('#tasks-container').append(taskHtml);
                
                // 기본 카테고리에 따라 세부 카테고리 활성화
                const newTaskItem = $(`[data-temp-id="${tempId}"]`);
                const detailDropdown = newTaskItem.find('.category-detail');
                if (defaultCategory && categories[defaultCategory]) {
                    detailDropdown.prop('disabled', false);
                }
            } else {
                // 하위 태스크 템플릿
                taskHtml = `
                    <div class="task-item subtask mb-2" data-temp-id="${tempId}" data-parent-id="${parentId}">
                        <div class="d-flex align-items-center">
                            <span class="ms-2">ㄴ</span>
                            <div class="form-group mb-0 flex-grow-1 ms-2 me-2">
                                <input type="text" class="form-control task-description auto-save" data-temp-id="${tempId}" placeholder="하위 태스크 설명">
                            </div>
                            <button class="btn btn-outline-action btn-outline-delete delete-existing-task">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                        </div>
                    </div>
                `;
                $(`.task-item[data-id="${parentId}"] .subtasks-container`).append(taskHtml);
                
                // 하위태스크 추가 후 이벤트 발생
                const parentTaskItem = $(`.task-item[data-id="${parentId}"]`);
                $(document).trigger('subtask:added', [parentTaskItem]);
            }
        }

        // 이벤트 위임을 사용하여 동적으로 추가된 요소에 이벤트 핸들러 연결
        
        // 카테고리 선택 이벤트 (신규 태스크)
        $(document).on('change', '.category-type', function() {
            const categoryType = $(this).val();
            const detailDropdown = $(this).closest('.task-item').find('.category-detail');
            
            detailDropdown.empty().append('<option value="">세부 카테고리 선택</option>');
            
            if (categories[categoryType]) {
                categories[categoryType].forEach(detail => {
                    detailDropdown.append(`<option value="${detail}">${detail}</option>`);
                });
                detailDropdown.prop('disabled', false);
            } else {
                detailDropdown.prop('disabled', true);
            }
            
            // 카테고리 변경 시 자동 저장
            saveTask($(this).closest('.task-item'));
        });
        
        // 카테고리 선택 이벤트 (기존 태스크)
        $(document).on('change', 'select[data-field="category_type"]', function() {
            const categoryType = $(this).val();
            const detailDropdown = $(this).closest('.task-item').find('select[data-field="category_detail"]');
            
            detailDropdown.empty().append('<option value="">세부 카테고리 선택</option>');
            
            if (categories[categoryType]) {
                categories[categoryType].forEach(detail => {
                    detailDropdown.append(`<option value="${detail}">${detail}</option>`);
                });
                detailDropdown.prop('disabled', false);
            } else {
                detailDropdown.prop('disabled', true);
            }
        });

        // 자동 저장 이벤트
        $(document).on('focus', '.auto-save', function() {
            // 포커스 시 현재 값 저장
            $(this).data('original-value', $(this).val());
        });
        
        $(document).on('blur', '.auto-save', function() {
            const taskItem = $(this).closest('.task-item');
            const originalValue = $(this).data('original-value');
            const currentValue = $(this).val();
            
            // 값이 변경된 경우에만 저장
            if (originalValue !== currentValue) {
                console.log('값 변경됨:', originalValue, '->', currentValue);
                
                // 소요시간 필드인 경우 배경색 업데이트
                if ($(this).hasClass('task-duration')) {
                    const durationValue = parseInt(currentValue || 0);
                    const tempId = $(this).data('temp-id');
                    const card = $(`#card-${tempId}`);
                    
                    if (durationValue > 0) {
                        card.addClass('bg-completed-task');
                    } else {
                        card.removeClass('bg-completed-task');
                    }
                }
                
                // 필드 변경 시 해당 태스크만 저장 (상위/하위 태스크 구분)
                const isRootTask = taskItem.hasClass('root-task');
                const isSubtask = taskItem.hasClass('subtask');
                
                console.log('태스크 유형:', isRootTask ? '상위 태스크' : '하위 태스크');
                
                // 하위 태스크가 추가/편집될 때 상위 태스크가 실수로 업데이트되지 않도록 방지
                saveTask(taskItem);
            } else {
                console.log('값 변경 없음, 저장 요청 생략');
            }
        });

        // 태스크 삭제 이벤트
        $(document).on('click', '.delete-existing-task', function() {
            const taskItem = $(this).closest('.task-item');
            const taskId = taskItem.data('id');
            const tempId = taskItem.data('temp-id');
            
            console.log('삭제 시도: 태스크 ID =', taskId, '임시 ID =', tempId, '태스크 타입 =', taskItem.hasClass('root-task') ? '상위 태스크' : '하위 태스크');
            
            // 아직 서버에 저장되지 않은 태스크는 UI에서만 제거
            if (!taskId && tempId) {
                console.log('아직 서버에 저장되지 않은 태스크 삭제');
                taskItem.fadeOut(300, function() {
                    $(this).remove();
                    if (taskItem.hasClass('root-task')) {
                        updateTaskNumbering(); // 상위 태스크 삭제 시 넘버링 업데이트
                    }
                });
                
                // 삭제 알림 표시
                $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> 삭제되었습니다.');
                saveToast.show();
                return;
            }
            
            if (!taskId) {
                console.error('태스크 ID가 없습니다.');
                $('#errorToast .toast-body').html('<i class="bi bi-exclamation-triangle me-2"></i> 태스크 ID를 찾을 수 없습니다.');
                errorToast.show();
                return;
            }
            
            // 상위 태스크인 경우에만 확인 메시지 표시
            if (taskItem.hasClass('root-task')) {
                if (confirm('정말로 이 태스크를 삭제하시겠습니까? 하위 태스크도 모두 삭제됩니다.')) {
                    deleteTask(taskId, taskItem);
                }
            } else {
                // 하위 태스크는 바로 삭제
                deleteTask(taskId, taskItem);
            }
        });

        // 하위 태스크 추가 이벤트
        $(document).on('click', '.add-subtask, .add-existing-subtask', function() {
            const taskItem = $(this).closest('.task-item');
            const taskId = taskItem.data('id');
            const tempId = taskItem.data('temp-id');
            
            console.log('하위 태스크 추가 시도:', '태스크 ID =', taskId, '임시 ID =', tempId);
            
            if (taskId) {
                // 이미 저장된 태스크에 하위 태스크 추가
                console.log('기존 상위 태스크에 하위 태스크 추가 (ID: ' + taskId + ')');
                addNewTask(taskId);
            } else {
                // 임시 ID가 있는 경우 먼저 저장
                console.log('상위 태스크 먼저 저장 후 하위 태스크 추가');
                
                // 현재 상위 태스크 설명 로깅
                const currentDescription = taskItem.find('> .card > .card-body > .d-flex > .form-group > .task-description').val();
                console.log('저장할 상위 태스크 설명:', currentDescription);
                
                // 저장 중 알림 표시
                savingToast.show();
                
                saveTask(taskItem, function(savedTaskId) {
                    console.log('상위 태스크 저장 완료, ID:', savedTaskId);
                    
                    // 저장 후 상위 태스크 설명 확인
                    const savedDescription = taskItem.find('> .card > .card-body > .d-flex > .form-group > .task-description').val();
                    console.log('저장 후 상위 태스크 설명:', savedDescription);
                    
                    // 저장 후 ID를 설정하고 하위 태스크 추가
                    taskItem.attr('data-id', savedTaskId);
                    taskItem.removeAttr('data-temp-id');
                    addNewTask(savedTaskId);
                });
            }
        });

        // 태스크 삭제 함수
        function deleteTask(taskId, taskItem) {
            // 삭제 중 알림 표시
            savingToast.hide();
            $('#savingToast .toast-body').html('<i class="bi bi-trash me-2"></i> 삭제 중...');
            savingToast.show();
            
            console.log('deleteTask 함수 호출: 태스크 ID =', taskId);
            
            $.ajax({
                url: `/work-logs/tasks/${taskId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    savingToast.hide();
                    console.log('삭제 성공:', response);
                    
                    if (response.success) {
                        // 삭제 성공 알림
                        $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> 삭제되었습니다.');
                        saveToast.show();
                        
                        // UI에서 태스크 제거
                        if (taskItem) {
                            const isRootTask = taskItem.hasClass('root-task');
                            taskItem.fadeOut(300, function() {
                                $(this).remove();
                                if (isRootTask) {
                                    updateTaskNumbering(); // 상위 태스크 삭제 시 넘버링 업데이트
                                    updateTotalDurationDisplay(); // 총 업무시간 업데이트
                                }
                            });
                        } else {
                            // 약간의 지연 후 페이지 새로고침
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    savingToast.hide();
                    console.error('삭제 실패:', xhr, status, error);
                    $('#errorToast .toast-body').html('<i class="bi bi-exclamation-triangle me-2"></i> 삭제 중 오류가 발생했습니다.');
                    errorToast.show();
                }
            });
        }
        
        // 기존 태스크 수정 - 자동 저장 이벤트 연결
        $(document).on('focus', '.editable-field', function() {
            // 포커스 시 현재 값 저장
            $(this).data('original-value', $(this).val() || $(this).text());
        });
        
        $(document).on('blur', '.editable-field', function() {
            const taskItem = $(this).closest('.task-item');
            const taskId = taskItem.data('id');
            const fieldName = $(this).data('field');
            const originalValue = $(this).data('original-value');
            const currentValue = $(this).val() || $(this).text();
            
            // 값이 변경된 경우에만 저장
            if (originalValue !== currentValue) {
                console.log('값 변경됨:', fieldName, originalValue, '->', currentValue);
                
                // 저장 중 알림 표시
                savingToast.show();
                
                // 시간 필드인 경우 소요시간 자동 계산
                let data = {
                    [fieldName]: currentValue
                };
                
                // 시작시각 또는 종료시각이 변경된 경우 소요시간 계산
                if (fieldName === 'start_time' || fieldName === 'end_time') {
                    const startTimeField = taskItem.find('[data-field="start_time"]');
                    const endTimeField = taskItem.find('[data-field="end_time"]');
                    
                    const startTime = startTimeField.val();
                    const endTime = endTimeField.val();
                    
                    if (startTime && endTime) {
                        data.duration_minutes = calculateDuration(startTime, endTime);
                    }
                }
                
                $.ajax({
                    url: `/work-logs/tasks/${taskId}`,
                    method: 'PUT',
                    data: data,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        savingToast.hide();
                        $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> 변경사항이 저장되었습니다.');
                        saveToast.show();
                        
                        // 시간 정보가 변경된 경우 페이지 새로고침
                        if (fieldName === 'start_time' || fieldName === 'end_time') {
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    },
                    error: function(xhr, status, error) {
                        savingToast.hide();
                        console.error('업데이트 실패:', xhr, status, error);
                        $('#errorToast .toast-body').html('<i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다.');
                        errorToast.show();
                    }
                });
            } else {
                console.log('값 변경 없음, 저장 요청 생략');
            }
        });

        // 태스크 저장 함수
        function saveTask(taskItem, callback) {
            const isRoot = taskItem.hasClass('root-task');
            const isNew = !taskItem.data('id');
            const tempId = taskItem.data('temp-id');
            
            // 필수 필드 검증 - 첫 번째(직계) 자식 요소로 제한
            let description;
            if (isRoot) {
                // 상위 태스크는 최상위 레벨에서 설명 필드 찾기
                description = taskItem.find('> .card > .card-body > .d-flex > .form-group > .task-description').val();
                if (!description) {
                    // 기존 태스크의 경우 다른 선택자 시도
                    description = taskItem.find('> .card > .card-body > .d-flex > .form-group > [data-field="description"]').val();
                }
            } else {
                // 하위 태스크는 직계 자식으로 제한
                description = taskItem.find('> .d-flex > .form-group > .task-description').val();
            }
            
            if (!description) {
                console.log('태스크 설명을 찾을 수 없음:', isRoot ? '상위태스크' : '하위태스크');
                return; // 설명이 없으면 저장하지 않음
            }
            
            // 저장 중 알림 표시
            savingToast.show();
            
            let data = {
                work_log_id: {{ $workLog->id }},
                description: description,
                parent_id: isRoot ? null : taskItem.data('parent-id')
            };
            
            if (isRoot) {
                // 상위 태스크는 카테고리와 시간 정보 추가
                let categoryType = taskItem.find('> .card > .card-body > .d-flex > .form-group > .category-type').val();
                if (!categoryType) {
                    categoryType = taskItem.find('> .card > .card-body > .d-flex > .form-group > [data-field="category_type"]').val();
                }
                
                let categoryDetail = taskItem.find('> .card > .card-body > .d-flex > .form-group > .category-detail').val();
                if (!categoryDetail) {
                    categoryDetail = taskItem.find('> .card > .card-body > .d-flex > .form-group > [data-field="category_detail"]').val();
                }
                
                // 시작시각과 종료시각 처리
                let startTime = taskItem.find('> .card > .card-body > .d-flex > .form-group > .task-start-time').val();
                if (!startTime) {
                    startTime = taskItem.find('> .card > .card-body > .d-flex > .form-group > [data-field="start_time"]').val();
                }
                
                let endTime = taskItem.find('> .card > .card-body > .d-flex > .form-group > .task-end-time').val();
                if (!endTime) {
                    endTime = taskItem.find('> .card > .card-body > .d-flex > .form-group > [data-field="end_time"]').val();
                }
                
                data.category_type = categoryType;
                data.category_detail = categoryDetail;
                data.start_time = startTime;
                data.end_time = endTime;
                
                // 시작시각과 종료시각이 모두 있으면 소요시간 자동 계산
                if (startTime && endTime) {
                    data.duration_minutes = calculateDuration(startTime, endTime);
                } else {
                    data.duration_minutes = 0;
                }
            }
            
            let url = "{{ route('work-logs.add-task') }}";
            let method = 'POST';
            
            // 이미 저장된 태스크인 경우 업데이트
            if (!isNew) {
                url = `/work-logs/tasks/${taskItem.data('id')}`;
                method = 'PUT';
            }
            
            $.ajax({
                url: url,
                method: method,
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    savingToast.hide();
                    $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> 변경사항이 저장되었습니다.');
                    saveToast.show();
                    
                    if (response.success) {
                        // 새 태스크인 경우 ID 설정
                        if (isNew && response.task && response.task.id) {
                            console.log('새 태스크 저장 성공, ID 설정:', response.task.id);
                            taskItem.attr('data-id', response.task.id);
                            taskItem.removeAttr('data-temp-id');
                            
                            // 하위태스크인 경우 이벤트 발생
                            if (!isRoot) {
                                const parentTaskItem = $(`.task-item[data-id="${data.parent_id}"]`);
                                $(document).trigger('subtask:added', [parentTaskItem]);
                            }
                            
                            // 콜백 함수가 있으면 실행
                            if (typeof callback === 'function') {
                                callback(response.task.id);
                            }
                        }
                        
                        // 총 업무시간 업데이트
                        updateTotalDurationDisplay();
                        
                        // 시간 정보가 있으면 페이지 새로고침하여 정렬 적용
                        if (isRoot && (data.start_time || data.end_time)) {
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    savingToast.hide();
                    console.error('저장 실패:', xhr, status, error);
                    $('#errorToast .toast-body').html('<i class="bi bi-exclamation-triangle me-2"></i> 저장 중 오류가 발생했습니다.');
                    errorToast.show();
                }
            });
        }

        // 태스크 넘버링 업데이트 함수
        function updateTaskNumbering() {
            console.log('태스크 넘버링 업데이트');
            $('.task-item.root-task').each(function(index) {
                $(this).find('.task-number').first().text((index + 1) + '.');
            });
        }
        
        // 페이지 로드 시 넘버링 업데이트
        updateTaskNumbering();
        
        // 페이지 로드 시 기본 카테고리 설정
        function initializeDefaultCategories() {
            // 새로 생성된 태스크는 이미 템플릿에서 처리됨
            
            // 기존 태스크에 대한 처리
            $('select[data-field="category_type"]').each(function() {
                // 이미 선택된 값이 있으면 건너뜀
                if ($(this).val()) {
                    return;
                }
                
                // 기본 카테고리 설정
                $(this).val(defaultCategory).trigger('change');
            });
        }
        
        // 페이지 로드 시 초기화 함수 호출
        initializeDefaultCategories();

        // 소요시간이 입력된 태스크에 배경색 적용
        function initializeCompletedTaskStyles() {
            $('.root-task').each(function() {
                const startTimeInput = $(this).find('[data-field="start_time"]').first();
                const endTimeInput = $(this).find('[data-field="end_time"]').first();
                const startTime = startTimeInput.val();
                const endTime = endTimeInput.val();
                
                const card = $(this).find('.card').first();
                
                if (startTime && endTime) {
                    card.addClass('bg-completed-task');
                }
            });
        }
        
        // 페이지 로드 시 소요시간 스타일 초기화
        initializeCompletedTaskStyles();

        // 총 업무시간 계산 및 표시 함수
        function updateTotalDurationDisplay() {
            // 모든 상위 태스크의 소요 시간 합계 계산
            let totalDuration = 0;
            $('.root-task').each(function() {
                // 시작시각과 종료시각 필드 찾기
                const startTimeField = $(this).find('.time-input[data-field="start_time"], .task-start-time').first();
                const endTimeField = $(this).find('.time-input[data-field="end_time"], .task-end-time').first();
                
                const startTime = startTimeField.val();
                const endTime = endTimeField.val();
                
                // 시작시각과 종료시각이 모두 있으면 소요시간 계산
                if (startTime && endTime) {
                    const duration = calculateDuration(startTime, endTime);
                    totalDuration += duration;
                } else {
                    // 기존 방식으로 소요시간 필드 값 사용
                    const durationInput = $(this).find('[data-field="duration_minutes"]').first();
                    const duration = parseInt(durationInput.val() || 0);
                    if (!isNaN(duration)) {
                        totalDuration += duration;
                    }
                }
            });
            
            // 총 업무시간 표시 업데이트 (시간과 분으로 변환)
            const hours = Math.floor(totalDuration / 60);
            const minutes = totalDuration % 60;
            $('#total-duration-hours').text(hours);
            $('#total-duration-minutes').text(minutes);
        }
        
        // 소요 시간 입력 필드 변경 시 총 업무시간 업데이트
        $(document).on('change', '.task-duration, [data-field="duration_minutes"], .time-input, [data-field="start_time"], [data-field="end_time"]', function() {
            updateTotalDurationDisplay();
            
            // 소요시간에 따라 카드 배경색 업데이트
            const taskItem = $(this).closest('.task-item');
            if (taskItem.hasClass('root-task')) {
                const startTimeField = taskItem.find('[data-field="start_time"], .task-start-time').first();
                const endTimeField = taskItem.find('[data-field="end_time"], .task-end-time').first();
                
                const startTime = startTimeField.val();
                const endTime = endTimeField.val();
                
                const card = taskItem.find('.card').first();
                
                if (startTime && endTime) {
                    card.addClass('bg-completed-task');
                } else {
                    card.removeClass('bg-completed-task');
                }
            }
        });
        
        // 페이지 로드 시 총 업무시간 계산
        updateTotalDurationDisplay();

        // 업무 가져오기 버튼 클릭 이벤트
        $('#import-tasks-btn').click(function() {
            // 모달 내용 로드
            $.ajax({
                url: "{{ route('work-logs.import-tasks-modal') }}",
                method: 'GET',
                data: {
                    work_log_id: {{ $workLog->id }}
                },
                success: function(response) {
                    $('#importTasksModal').html(response);
                    $('#importTasksModal').modal('show');
                },
                error: function() {
                    alert('업무 리스트를 불러오는 중 오류가 발생했습니다.');
                }
            });
        });

        // 하위태스크 펼치기/접기 기능
        $(document).on('click', '.toggle-subtasks', function() {
            const button = $(this);
            const taskItem = button.closest('.task-item');
            const subtasksContainer = taskItem.find('.subtasks-container').first();
            const isExpanded = button.attr('data-expanded') === 'true';
            
            if (isExpanded) {
                // 접기
                subtasksContainer.slideUp(200);
                button.attr('data-expanded', 'false');
                button.find('i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else {
                // 펼치기
                subtasksContainer.slideDown(200);
                button.attr('data-expanded', 'true');
                button.find('i').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            }
        });
        
        // 하위태스크 추가 버튼 클릭 시 자동으로 펼치기
        $(document).on('click', '.add-existing-subtask', function() {
            const taskItem = $(this).closest('.task-item');
            const toggleButton = taskItem.find('.toggle-subtasks').first();
            
            // 토글 버튼이 있고 접혀있는 상태라면 펼치기
            if (toggleButton.length > 0 && toggleButton.attr('data-expanded') === 'false') {
                toggleButton.click();
            }
        });
        
        // 하위태스크 추가 후 토글 버튼 상태 업데이트
        $(document).on('subtask:added', function(e, taskItem) {
            const rootTaskItem = $(taskItem).closest('.root-task');
            const toggleButton = rootTaskItem.find('.toggle-subtasks').first();
            const subtasksContainer = rootTaskItem.find('.subtasks-container').first();
            
            // 토글 버튼이 없는 경우 (첫 하위태스크 추가 시) 버튼 추가
            if (toggleButton.length === 0) {
                const placeholder = rootTaskItem.find('.task-number').prev('span');
                if (placeholder.length > 0) {
                    placeholder.replaceWith(
                        '<button class="btn btn-sm toggle-subtasks me-1" data-expanded="true">' +
                        '<i class="bi bi-chevron-up"></i>' +
                        '</button>'
                    );
                }
            } else {
                // 이미 버튼이 있는 경우 펼침 상태로 변경
                toggleButton.attr('data-expanded', 'true');
                toggleButton.find('i').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            }
            
            // 하위태스크 컨테이너 표시
            subtasksContainer.show();
        });
    });
</script>
@endpush