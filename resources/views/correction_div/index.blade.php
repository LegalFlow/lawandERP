@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<div class="container-fluid" style="max-width: calc(100vw - var(--sidebar-width)); margin-right: 0; position: relative; z-index: 1;">
    <div class="row">
        <div class="col-md-12">
            <div class="card" style="position: relative; z-index: 1;">
                <div class="card-body">
                    <form id="searchForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="unconfirmed_only" name="unconfirmed_only" value="1" {{ request('unconfirmed_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="unconfirmed_only">미확인만 보기</label>
                                </div>
                            </div>

                            

                            <div class="col-md-10">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <select class="form-select form-select-sm" name="date_type">
                                            <option value="shipment_date" {{ request('date_type', 'shipment_date') == 'shipment_date' ? 'selected' : '' }}>발송일자</option>
                                            <option value="receipt_date" {{ request('date_type') == 'receipt_date' ? 'selected' : '' }}>수신일자</option>
                                            <option value="deadline" {{ request('date_type') == 'deadline' ? 'selected' : '' }}>제출기한</option>
                                            <option value="summit_date" {{ request('date_type') == 'summit_date' ? 'selected' : '' }}>제출일자</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select form-select-sm" id="quick_date">
                                            <option value="">빠른 선택</option>
                                            <option value="1">최근 1개월</option>
                                            <option value="3">최근 3개월</option>
                                            <option value="6">최근 6개월</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                            <span class="input-group-text">~</span>
                                            <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm" name="search_text" 
                                    placeholder="법원/사건번호/고객명/송달문서 검색" value="{{ request('search_text') }}">
                            </div>

                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="consultant">
                                    <option value="">상담자 선택</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->name }}" {{ request('consultant') == $member->name ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="case_manager">
                                    <option value="">담당자 선택</option>
                                    <option value="none" {{ request('case_manager') == 'none' ? 'selected' : '' }}>담당자 없음</option>
                                    <option value="absent_today" {{ request('case_manager') == 'absent_today' ? 'selected' : '' }}>금일 담당자 부재</option>
                                    <option value="resigned" {{ request('case_manager') == 'resigned' ? 'selected' : '' }}>퇴사 및 업무변경 담당자</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->name }}" {{ request('case_manager') == $member->name ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="submission_status">
                                    <option value="">전체</option>
                                    @foreach($submissionStatuses as $status)
                                        <option value="{{ $status }}" {{ request('submission_status') == $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select class="form-select form-select-sm" name="document_type">
                                    <option value="">분류 선택</option>
                                    @foreach($documentTypes as $type)
                                        <option value="{{ $type }}" {{ request('document_type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="submit" class="btn btn-primary btn-sm">검색</button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="resetBtn">초기화</button>
                                    <a href="{{ route('correction-div.export') }}?{{ http_build_query(request()->all()) }}" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </a>
                                </div>
                                <button type="button" class="btn btn-outline-dark btn-sm" id="unsubmittedStatsBtn">
                                    미제출현황
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover small-text">
                            <thead class="table-dark">
                                <tr>
                                    <th>발송일자</th>
                                    <th>수신일자</th>
                                    <th>법원</th>
                                    <th>사건번호</th>
                                    <th>고객명</th>
                                    <th>송달문서</th>
                                    <th>분류</th>
                                    <th>상담자</th>
                                    <th>담당자</th>
                                    <th>제출기한</th>
                                    <th>제출여부</th>
                                    <th>제출일자</th>
                                    <th>파일</th>
                                    <th>메모</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($correctionDivs as $correction)
                                    <tr data-id="{{ $correction->id }}">
                                        <td>{{ $correction->shipment_date ?? '-' }}</td>
                                        <td>{{ $correction->receipt_date ?? '-' }}</td>
                                        <td>{{ $correction->court_name ?? '-' }}</td>
                                        <td>{{ $correction->case_number }}</td>
                                        <td>
                                            <span class="name-truncate truncate-hover" data-full-text="{!! htmlspecialchars($correction->name ?? '-') !!}">
                                                {{ $correction->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="document-truncate truncate-hover" data-full-text="{!! htmlspecialchars($correction->document_name) !!}">
                                                {{ $correction->document_name }}
                                            </span>
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm document-type-input {{ ($correction->document_type ?? '선택없음') === '선택없음' ? 'text-danger fw-bold' : '' }}">
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type }}" 
                                                        {{ ($correction->document_type ?? '선택없음') === $type ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm consultant-input">
                                                <option value="">선택</option>
                                                @foreach($members as $member)
                                                    <option value="{{ $member->name }}" {{ ($correction->consultant ?? '') === $member->name ? 'selected' : '' }}>
                                                        {{ $member->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm case-manager-input">
                                                <option value="">선택</option>
                                                @foreach($members as $member)
                                                    <option value="{{ $member->name }}" {{ ($correction->case_manager ?? '') === $member->name ? 'selected' : '' }}>
                                                        {{ $member->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm deadline-input"
                                                value="{{ $correction->deadline ?? '' }}"
                                                data-original="{{ $correction->deadline ?? '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm submission-status-input {{ ($correction->submission_status ?? '미제출') === '미제출' ? 'text-danger fw-bold' : '' }}">
                                                @foreach($submissionStatuses as $status)
                                                    <option value="{{ $status }}" 
                                                        {{ ($correction->submission_status ?? '미제출') === $status ? 'selected' : '' }}>
                                                        {{ $status }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm summit-date-input"
                                                value="{{ $correction->summit_date ?? '' }}"
                                                data-original="{{ $correction->summit_date ?? '' }}">
                                        </td>
                                        <td class="text-center">
                                            @if($correction->pdf_path)
                                                <button type="button" 
                                                        class="btn btn-sm btn-link download-btn" 
                                                        data-href="{{ route('correction-div.download', ['path' => base64_encode($correction->pdf_path)]) }}">
                                                    <i class="fas fa-file-download"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary memo-btn"
                                                    data-id="{{ $correction->id }}"
                                                    data-memo="{{ $correction->command }}">
                                                <i class="fas fa-sticky-note {{ $correction->command ? 'text-warning' : '' }}"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">데이터가 없습니다.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-3">
                        {{ $correctionDivs->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.small-text {
    font-size: 0.875rem !important;
}
.small-text th,
.small-text td {
    padding: 0.5rem !important;
}

/* 테이블 셀 설정 */
.table td {
    position: relative;
}

/* 테이블 관련 컨테이너들의 overflow 설정 수정 */
.table-responsive {
    max-width: 100%;
    overflow-x: auto;
    position: relative;
    z-index: 1;
}

.card-body {
    overflow: visible;
}

.card {
    overflow: hidden;
    background: white;
    position: relative;
    z-index: 1;
}

/* 테이블 셀 최대 너비 한도 */
.table td, .table th {
    max-width: 200px; /* 적절한 값으로 조정 */
    white-space: nowrap;
    position: relative;
    z-index: 1;
}

/* 페이지 컨텐츠 전체를 사이드바보다 아래 레이어에 위치 */
#page-content-wrapper {
    position: relative;
    z-index: 1;
}
</style>
@endsection



@push('scripts')
<script>
$(document).ready(function() {
    // 페이지 최초 로드 시에만 자동 검색 실행 (어떤 검색 파라미터도 없을 때만)
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.toString()) {
        // 기본 날짜 설정
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 3);
        
        $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
        $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
        
        // 제출여부 기본값 '미제출'로 설정
        $('select[name="submission_status"]').val('미제출');
        
        // 담당자 선택을 현재 로그인한 사용자로 설정
        $('select[name="case_manager"]').val('{{ auth()->user()->name }}');
        
        // 폼 제출
        $('#searchForm').submit();
    }

    function updateField(element, field) {
        const tr = element.closest('tr');
        const td = element.closest('td');
        const id = tr.data('id');
        const value = element.val();
        const original = element.data('original');

        if (value === original) return;

        // 저장 중 상태 표시 추가
        td.addClass('saving');
        if (!td.find('.status-icon.saving-icon').length) {
            td.append('<i class="fas fa-spinner fa-spin status-icon saving-icon"></i>');
        }

        if (field === 'summit_date' && value && !original) {
            const submissionStatusSelect = tr.find('.submission-status-input');
            submissionStatusSelect.val('제출완료');
            submissionStatusSelect.data('original', '제출완료');
        }

        $.ajax({
            url: `/correction-div/${id}`,
            method: 'PUT',
            data: {
                [field]: value,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                element.data('original', value);
                
                // 분류와 제출여부 필드의 스타일 업데이트
                if (field === 'document_type') {
                    element.toggleClass('text-danger fw-bold', value === '선택없음');
                }
                if (field === 'submission_status') {
                    element.toggleClass('text-danger fw-bold', value === '미제출');
                }
                
                // 기존 성공 표시 코드
                td.removeClass('saving').addClass('save-success');
                td.find('.saving-icon').remove();
                td.append('<i class="fas fa-check status-icon success-icon text-success"></i>');
                
                setTimeout(() => {
                    td.removeClass('save-success');
                    td.find('.success-icon').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 2000);
            },
            error: function() {
                element.val(original);
                if (field === 'summit_date') {
                    const submissionStatusSelect = tr.find('.submission-status-input');
                    submissionStatusSelect.val(submissionStatusSelect.data('original'));
                }
                
                // 에러 상태 표시 추가
                td.removeClass('saving').addClass('save-error');
                td.find('.saving-icon').remove();
                td.append('<i class="fas fa-exclamation-circle status-icon error-icon text-danger"></i>');
                
                // 3초 후 에러 상태 제거
                setTimeout(() => {
                    td.removeClass('save-error');
                    td.find('.error-icon').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    }

    $('.deadline-input, .summit-date-input').change(function() {
        updateField($(this), $(this).hasClass('deadline-input') ? 'deadline' : 'summit_date');
    });

    $('.submission-status-input').change(function() {
        updateField($(this), 'submission_status');
    });

    // 빠른 날짜 선택
    $('#quick_date').change(function() {
        const months = $(this).val();
        if (!months) return;
        
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - parseInt(months));
        
        $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
        $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
    });

    // 페이지 로드 기본 날짜 설정 (1개월)
    if (!$('input[name="start_date"]').val() && !$('input[name="end_date"]').val()) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 1);
        
        $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
        $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
    }

    // 초기화 버튼 - 수정된 버튼
    $('#resetBtn').on('click', function(e) {
        e.preventDefault(); // 기본 동작 지
        
        // 체크박스 초기화
        $('input[type="checkbox"]').prop('checked', false);
        
        // select 박스들 초기화
        $('select').val('');
        $('select[name="date_type"]').val('shipment_date');
        
        // 검색어 초기화
        $('input[name="search_text"]').val('');
        
        // 날짜 설정
        const endDate = new Date();
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 1);
        
        $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
        $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
        
        // 폼 제출
        $('#searchForm').submit();
    });

    // 페이지네이션 링크에 현재 검색 파라미터 추가
    $('.pagination a').each(function() {
        const url = new URL($(this).prop('href'), window.location.origin);
        const currentParams = new URLSearchParams(window.location.search);
        
        // 체크박스 상태 확인
        if ($('#unconfirmed_only').is(':checked')) {
            url.searchParams.set('unconfirmed_only', '1');
        }
        
        // select 박스들의 값
        $('select[name]').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (value) {
                url.searchParams.set(name, value);
            }
        });
        
        // 날짜 필터
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();
        if (startDate) url.searchParams.set('start_date', startDate);
        if (endDate) url.searchParams.set('end_date', endDate);
        
        // 검색어
        const searchText = $('input[name="search_text"]').val();
        if (searchText) url.searchParams.set('search_text', searchText);
        
        $(this).prop('href', url.toString());
    });

    $('.download-btn').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('href');
        
        // Ajax 대신 직접 다운로드 링크 생성
        var link = document.createElement('a');
        link.href = url;
        link.target = '_blank';  // 새 창에서 열기 (선택사항)
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // document_type 변경 이벤트 추가
    $('.document-type-input').change(function() {
        updateField($(this), 'document_type');
    });

    // 메모 관련 기능
    let currentMemoId = null;
    const memoModal = new bootstrap.Modal(document.getElementById('memoModal'), {
        backdrop: true,
        keyboard: true
    });

    $('.memo-btn').on('click', function() {
        console.log('메모 버튼 클릭됨');
        currentMemoId = $(this).data('id');
        const memo = $(this).data('memo');
        console.log('현재 메모 ID:', currentMemoId);
        console.log('현재 메모 내용:', memo);
        
        $('#memoText').val(memo || '');
        console.log('모달 표시 시도');
        try {
            memoModal.show();
            console.log('모달 표시 성공');
        } catch (error) {
            console.error('모달 표시 중 에러:', error);
        }
    });

    $('#saveMemoBtn').on('click', function() {
        console.log('저장 버튼 클릭됨');
        const memo = $('#memoText').val();
        console.log('저장할 메모 내용:', memo);
        
        $.ajax({
            url: `/correction-div/${currentMemoId}/memo`,
            method: 'POST',
            data: {
                memo: memo,
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                console.log('Ajax 요청 시작');
            },
            success: function(response) {
                console.log('Ajax 응답:', response);
                if (response.success) {
                    const btn = $(`.memo-btn[data-id="${currentMemoId}"]`);
                    btn.data('memo', memo);
                    btn.find('i').toggleClass('text-warning', !!memo);
                    memoModal.hide();
                } else {
                    alert('메모 저장에 실패했습니다.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax 에러:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('메모 저장 중 오류가 발생했습니다.');
            }
        });
    });

    // 디버깅을 위한 수정된 콘솔 로그
    $('.document-type-input').each(function() {
        console.log('분류 정보:', {
            현재값: $(this).val(),
            원본값: $(this).data('original'),
            클래스: $(this).attr('class')
        });
    });
    
    $('.submission-status-input').each(function() {
        console.log('제출여부 정보:', {
            현재값: $(this).val(),
            원본값: $(this).data('original'),
            클래스: $(this).attr('class')
        });
    });

    // 상담자와 담당자 변경 이벤트 추가
    $('.consultant-input, .case-manager-input').change(function() {
        const field = $(this).hasClass('consultant-input') ? 'consultant' : 'case_manager';
        updateField($(this), field);
    });

    // 미제출현황 모달 관련 스크립트
    const unsubmittedStatsModal = new bootstrap.Modal(document.getElementById('unsubmittedStatsModal'));
    
    function loadUnsubmittedStats(months) {
        const caseTeamOnly = $('#caseTeamOnlyCheck').is(':checked');
        
        $.ajax({
            url: '{{ route("correction-div.unsubmitted-stats") }}',
            method: 'GET',
            data: { 
                months: months,
                case_team_only: caseTeamOnly
            },
            success: function(response) {
                if (response.success) {
                    const tbody = $('#statsTableBody');
                    tbody.empty();
                    
                    response.data.forEach(function(row) {
                        tbody.append(`
                            <tr>
                                <td class="text-center">${row.rank}</td>
                                <td class="text-center">${row.name}</td>
                                <td class="text-center">${row.order_count || ''}</td>
                                <td class="text-center">${row.etc_count || ''}</td>
                                <td class="text-center">${row.correction_count || ''}</td>
                                <td class="text-center">${row.exception_count || ''}</td>
                                <td class="text-center">${row.none_count || ''}</td>
                                <td class="text-center">${row.total_count}</td>
                            </tr>
                        `);
                    });
                } else {
                    alert('데이터를 불러오는데 실패했습니다.');
                }
            },
            error: function() {
                alert('서버 오류가 발생했습니다.');
            }
        });
    }

    $('#unsubmittedStatsBtn').on('click', function() {
        const months = $('#statsMonthSelect').val();
        loadUnsubmittedStats(months);
        unsubmittedStatsModal.show();
    });

    $('#statsMonthSelect').on('change', function() {
        loadUnsubmittedStats($(this).val());
    });

    $('#caseTeamOnlyCheck').on('change', function() {
        loadUnsubmittedStats($('#statsMonthSelect').val());
    });
});
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* 테이블 폰트 크기 조절*/
.container .table th, 
.container .table td {
    vertical-align: middle;
    font-size: 0.85rem !important;
}

/* 메모 관련 스타일 추가 */
.memo-btn {
    padding: 0.25rem 0.5rem;
}

.memo-btn i {
    font-size: 1rem;
}

#memoText {
    resize: vertical;
    min-height: 100px;
}

/* 모달 관련 z-index 스타일 */
.modal {
    z-index: 1060 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

.modal-dialog {
    z-index: 1070 !important;
}

#memoText {
    resize: vertical;
    min-height: 100px;
    z-index: 1080 !important;
}

/* 저장 상태 표시를 위한 스타일 */
.saving {
    position: relative;
    background-color: #fff7e6 !important;
}

.save-success {
    position: relative;
    background-color: #e6ffe6 !important;
    transition: background-color 0.5s;
}

.save-error {
    position: relative;
    background-color: #ffe6e6 !important;
}

/* 저장 아이콘 애니메이션 */
.status-icon {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
}

.saving .status-icon.saving-icon,
.save-success .status-icon.success-icon,
.save-error .status-icon.error-icon {
    opacity: 1;
}

/* 드롭다운 옵션 스타일 */
.document-type-input option[value="선택없음"] {
    color: #dc3545 !important;
    font-weight: bold !important;
}

.submission-status-input option[value="미제출"] {
    color: #dc3545 !important;
    font-weight: bold !important;
}

/* 다른 옵션들은 기본 스타일 유지 */
.document-type-input option:not([value="선택없음"]),
.submission-status-input option:not([value="미제출"]) {
    color: initial !important;
    font-weight: normal !important;
}

/* 말줄임 및 호버 관련 스타일 */
.truncate-hover {
    position: relative;
    cursor: pointer;
}

.truncate-hover:hover::after {
    content: attr(data-full-text);
    position: absolute;
    left: 100%;
    top: 0;
    background: #fff;
    color: #333;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1500;  /* z-index 값을 1500으로 통일 */
    white-space: normal;
    word-break: break-all;
    min-width: 200px;
    max-width: 300px;
}

/* 말줄임 스타일 */
.name-truncate {
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
}

.document-truncate {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
}

.modal-dialog {
    margin: 1.75rem auto;
}

.table td, .table th {
    padding: 0.5rem;
    vertical-align: middle;
}

#unsubmittedStatsModal .modal-content {
    overflow-y: auto;
}

#unsubmittedStatsModal .table-responsive {
    max-height: calc(80vh - 150px);
    overflow-y: auto;
}

.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f8f9fa;  /* 더 옅은 회색으로 설정 */
}

.table-hover tbody tr:hover {
    background-color: #e9ecef;  /* hover 시 배경색 */
}
</style>
@endpush

<!-- 메모 모달 -->
<div class="modal fade" id="memoModal" tabindex="-1" aria-labelledby="memoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memoModalLabel">메모</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="memoText" rows="20"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveMemoBtn">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 모달 추가 (body 태그 닫기 직전에 추가) -->
<div class="modal fade" id="unsubmittedStatsModal" tabindex="-1" aria-labelledby="unsubmittedStatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="max-width: 50vw;">
        <div class="modal-content" style="height: 80vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="unsubmittedStatsModalLabel">미제출 현황</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <select class="form-select form-select-sm" id="statsMonthSelect" style="width: auto;">
                            <option value="1">최근 1개월</option>
                            <option value="2">최근 2개월</option>
                            <option value="3" selected>최근 3개월</option>
                            <option value="4">최근 4개월</option>
                            <option value="5">최근 5개월</option>
                            <option value="6">최근 6개월</option>
                        </select>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="caseTeamOnlyCheck" checked>
                            <label class="form-check-label" for="caseTeamOnlyCheck">
                                사건관리팀만 보기
                            </label>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 60px;">순위</th>
                                <th class="text-center" style="width: 120px;">이름</th>
                                <th class="text-center" style="width: 14%;">명령</th>
                                <th class="text-center" style="width: 14%;">기타</th>
                                <th class="text-center" style="width: 14%;">보정</th>
                                <th class="text-center" style="width: 14%;">예외</th>
                                <th class="text-center" style="width: 14%;">선택없음</th>
                                <th class="text-center" style="width: 14%;">총 미제출 수</th>
                            </tr>
                        </thead>
                        <tbody id="statsTableBody">
                            <!-- JavaScript로 데이터가 채워짐 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
