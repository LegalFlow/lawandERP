@extends('layouts.app')

@section('content')
<div class="container-fluid" style="max-width: calc(100vw - var(--sidebar-width)); margin-right: 0; position: relative; z-index: 1;">
    <div class="row">
        <div class="col-md-12">
            <div class="card" style="position: relative; z-index: 1;">
                <div class="card-body">
                    <!-- 신규등록 버튼 -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                            신규등록
                        </button>
                    </div>

                    <!-- 검색 폼 -->
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
                                    <option value="none" {{ request('case_manager') == 'none' ? 'selected' : '' }}>담당자없음</option>
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

                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm">검색</button>
                                <button type="button" class="btn btn-secondary btn-sm" id="resetBtn">초기화</button>
                            </div>
                        </div>
                    </form>

                    <!-- 테이블 -->
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
                                    <th>메모</th>
                                    <th>삭제</th>
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
                                            <select class="form-control form-control-sm document-type-input {{ ($correction->document_type ?? '선택없음') == '선택없음' ? 'text-danger fw-bold' : '' }}"
                                                    data-original="{{ $correction->document_type ?? '선택없음' }}">
                                                @foreach($documentTypes as $type)
                                                    <option value="{{ $type }}" 
                                                        {{ ($correction->document_type ?? '선택없음') == $type ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ $correction->consultant ?? '-' }}</td>
                                        <td>{{ $correction->case_manager ?? '-' }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm deadline-input"
                                                value="{{ $correction->deadline ?? '' }}"
                                                data-original="{{ $correction->deadline ?? '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm submission-status-input {{ ($correction->submission_status ?? '미제출') == '미제출' ? 'text-danger fw-bold' : '' }}"
                                                    data-original="{{ $correction->submission_status ?? '미제출' }}">
                                                @foreach($submissionStatuses as $status)
                                                    <option value="{{ $status }}" 
                                                        {{ ($correction->submission_status ?? '미제출') == $status ? 'selected' : '' }}>
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
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary memo-btn"
                                                    data-id="{{ $correction->id }}"
                                                    data-memo="{{ $correction->command }}">
                                                <i class="fas fa-sticky-note {{ $correction->command ? 'text-warning' : '' }}"></i>
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-btn"
                                                    data-id="{{ $correction->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="14" class="text-center">데이터가 없습니다.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이지네이션 -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $correctionDivs->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .table {
        font-size: 0.875rem; /* 14px */
    }
    
    .table input, 
    .table select {
        font-size: 0.875rem; /* 14px */
    }
    
    /* 필요한 경우 모달 내부의 텍스트 크기도 조정 */
    .modal-body {
        font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    $(function() {
        // CSRF 설정
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        let isSubmitting = false;
        let currentMemoId = null;

        // 신규등록 저장
        $(document).on('click', '#saveBtn', function() {
            if (isSubmitting) return;
            isSubmitting = true;
            
            const formData = new FormData($('#createForm')[0]);
            
            $.ajax({
                url: '{{ route("correction-div-manual.store") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // response가 문자열인 경우도 처리
                    if (response.success || (typeof response === 'string' && response.includes('success'))) {
                        // 포커스를 다른 요소로 이동
                        $('.container-fluid').focus();
                        
                        // Bootstrap 5 모달 닫기
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // 폼 초기화
                        $('#createForm')[0].reset();
                        
                        // 성공 메시지 표시
                        alert('저장되었습니다.');
                        
                        // 페이지 새로고침
                        window.location.reload();
                    } else {
                        console.log('Response:', response);  // 디버깅용
                        alert('저장은 완료되었으나 화면 갱신에 실패했습니다. 페이지를 새로고침합니다.');
                        window.location.reload();
                    }
                },
                error: function(xhr) {
                    console.log('Error response:', xhr);  // 디버깅용
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = '다음 항목을 확인해주세요:\n';
                        for (let field in errors) {
                            errorMessage += `${errors[field][0]}\n`;
                        }
                        alert(errorMessage);
                    } else {
                        // 실제로 저장은 됐을 수 있으므로
                        alert('저장 상태를 확인하기 위해 페이지를 새로고침합니다.');
                        window.location.reload();
                    }
                },
                complete: function() {
                    isSubmitting = false;
                }
            });
        });

        // 삭제 기능
        $(document).on('click', '.delete-btn', function() {
            if (!confirm('정말 삭제하시겠습니까?')) return;
            
            const id = $(this).data('id');
            
            $.ajax({
                url: `/correction-div-manual/${id}`,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert('삭제 중 오류가 발생했습니다.');
                }
            });
        });

        // 메모 관련
        $(document).on('click', '.memo-btn', function() {
            currentMemoId = $(this).data('id');
            $('#memoText').val($(this).data('memo'));
            $('#memoModal').modal('show');
        });

        $(document).on('click', '#saveMemoBtn', function() {
            if (!currentMemoId) return;
            
            $.ajax({
                url: `/correction-div-manual/${currentMemoId}/memo`,
                method: 'POST',
                data: {
                    memo: $('#memoText').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    alert('메모 저장 중 오류가 발생했습니다.');
                }
            });
        });

        // 모달 초기화
        $('#createModal').on('hidden.bs.modal', function () {
            $('#createForm')[0].reset();
        });

        $('#memoModal').on('hidden.bs.modal', function () {
            currentMemoId = null;
            $('#memoText').val('');
        });

        // 검색 폼 초기화
        $('#resetBtn').click(function() {
            $('#searchForm')[0].reset();
            $('#searchForm').submit();
        });

        // 빠른 날짜 선택
        $('#quick_date').change(function() {
            const months = $(this).val();
            if (months) {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setMonth(startDate.getMonth() - parseInt(months));
                
                $('input[name="end_date"]').val(endDate.toISOString().split('T')[0]);
                $('input[name="start_date"]').val(startDate.toISOString().split('T')[0]);
            }
        });

        // 테이블 셀 업데이트
        $(document).on('change', '.document-type-input, .deadline-input, .submission-status-input, .summit-date-input', function() {
            const $input = $(this);
            const id = $input.closest('tr').data('id');
            
            // 필드명 매핑
            const fieldClassMap = {
                'document-type-input': 'document_type',
                'deadline-input': 'deadline',
                'submission-status-input': 'submission_status',
                'summit-date-input': 'summit_date'
            };
            
            // 클래스명에서 실제 필드명 추출
            const inputClass = Array.from($input[0].classList).find(className => 
                className.endsWith('-input')
            );
            const field = fieldClassMap[inputClass];
            const value = $input.val();
            
            $.ajax({
                url: `/correction-div-manual/${id}/update`,  // URL 수정
                method: 'POST',
                data: {
                    field: field,
                    value: value,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $input.data('original', value);
                        // 성공 시 시각적 피드백 (선택사항)
                        $input.addClass('bg-success-light');
                        setTimeout(() => {
                            $input.removeClass('bg-success-light');
                        }, 500);
                    } else {
                        alert('저장에 실패했습니다.');
                        $input.val($input.data('original'));
                    }
                },
                error: function(xhr) {
                    console.error('Update failed:', xhr);
                    alert('저장 중 오류가 발생했습니다.');
                    $input.val($input.data('original'));
                }
            });
        });
    });
});
</script>
@endpush

<!-- 신규등록 모달 -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">보정서 신규등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">발송일자 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="shipment_date" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">수신일자</label>
                            <input type="date" class="form-control" name="receipt_date">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">법원</label>
                            <input type="text" class="form-control" name="court_name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">사건번호 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="case_number" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">고객명</label>
                            <input type="text" class="form-control" name="name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">송달문서 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="document_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">분류</label>
                            <select class="form-select" name="document_type">
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">상담자</label>
                            <select class="form-select" name="consultant">
                                <option value="">선택없음</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->name }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">담당자</label>
                            <select class="form-select" name="case_manager">
                                <option value="">선택없음</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->name }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">제출기한</label>
                            <input type="date" class="form-control" name="deadline">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">제출여부</label>
                            <select class="form-select" name="submission_status">
                                @foreach($submissionStatuses as $status)
                                    <option value="{{ $status }}" {{ $status == '미제출' ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">제출일자</label>
                            <input type="date" class="form-control" name="summit_date">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveBtn">저장</button>
            </div>
        </div>
    </div>
</div>

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