@extends('layouts.app')

@section('content')
<div class="container">
 

    <!-- 통계 정보 - 새로운 디자인 -->
    <div class="stats-container mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">상담 현황</h5>
                    <button type="button" class="btn btn-outline-secondary btn-sm stats-toggle">
                        상세보기 <i class="fas fa-chevron-down ms-1"></i>
                    </button>
                </div>
                
                <!-- 기본 표시 (실 상담수) -->
                <div class="stats-basic">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-center">전체</th>
                                <th class="text-center">서울</th>
                                <th class="text-center">대전</th>
                                <th class="text-center">부산</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>실 상담수</th>
                                <td class="text-center">{{ number_format($totalRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($seoulRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($busanRealConsultCount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 상세 통계 (기본적으로 숨김) -->
                <div class="stats-detail" style="display: none;">
                    <table class="table table-borderless mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-center">전체</th>
                                <th class="text-center">서울</th>
                                <th class="text-center">대전</th>
                                <th class="text-center">부산</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>총합</th>
                                <td class="text-center">{{ number_format($totalCount) }}</td>
                                <td class="text-center">{{ number_format($seoulCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonCount) }}</td>
                                <td class="text-center">{{ number_format($busanCount) }}</td>
                            </tr>
                            <tr>
                                <th>재진행</th>
                                <td class="text-center">{{ number_format($totalReprocessCount) }}</td>
                                <td class="text-center">{{ number_format($seoulReprocessCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonReprocessCount) }}</td>
                                <td class="text-center">{{ number_format($busanReprocessCount) }}</td>
                            </tr>
                            <tr>
                                <th>기존</th>
                                <td class="text-center">{{ number_format($totalExistingCount) }}</td>
                                <td class="text-center">{{ number_format($seoulExistingCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonExistingCount) }}</td>
                                <td class="text-center">{{ number_format($busanExistingCount) }}</td>
                            </tr>
                            <tr>
                                <th>소개</th>
                                <td class="text-center">{{ number_format($totalIntroducedCount) }}</td>
                                <td class="text-center">{{ number_format($seoulIntroducedCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonIntroducedCount) }}</td>
                                <td class="text-center">{{ number_format($busanIntroducedCount) }}</td>
                            </tr>
                            <tr>
                                <th>나이스</th>
                                <td class="text-center">{{ number_format($totalNiceCount) }}</td>
                                <td class="text-center">{{ number_format($seoulNiceCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonNiceCount) }}</td>
                                <td class="text-center">{{ number_format($busanNiceCount) }}</td>
                            </tr>
                            <tr>
                                <th>SNS</th>
                                <td class="text-center">{{ number_format($totalSNSCount) }}</td>
                                <td class="text-center">{{ number_format($seoulSNSCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonSNSCount) }}</td>
                                <td class="text-center">{{ number_format($busanSNSCount) }}</td>
                            </tr>
                            <tr>
                                <th>실 상담수</th>
                                <td class="text-center">{{ number_format($totalRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($seoulRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonRealConsultCount) }}</td>
                                <td class="text-center">{{ number_format($busanRealConsultCount) }}</td>
                            </tr>
                            <tr>
                                <th>부재</th>
                                <td class="text-center">{{ number_format($totalAbsentCount) }}</td>
                                <td class="text-center">{{ number_format($seoulAbsentCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonAbsentCount) }}</td>
                                <td class="text-center">{{ number_format($busanAbsentCount) }}</td>
                            </tr>
                            <tr>
                                <th>무효</th>
                                <td class="text-center">{{ number_format($totalInvalidCount) }}</td>
                                <td class="text-center">{{ number_format($seoulInvalidCount) }}</td>
                                <td class="text-center">{{ number_format($daejeonInvalidCount) }}</td>
                                <td class="text-center">{{ number_format($busanInvalidCount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 섹션 - 레이아웃 및 반응형 개선 -->
    <div class="filters-container mb-4">
        <form action="{{ route('targets.index') }}" method="GET" class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">기준일자</label>
                <select name="date_type" class="form-select mb-2">
                    <option value="create_dt" {{ request('date_type', 'create_dt') == 'create_dt' ? 'selected' : '' }}>등록일자</option>
                    <option value="contract_date" {{ request('date_type') == 'contract_date' ? 'selected' : '' }}>계약일자</option>
                </select>
                <div class="input-group">
                    <input type="date" 
                           name="start_date" 
                           class="form-control" 
                           value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                    <span class="input-group-text">~</span>
                    <input type="date" 
                           name="end_date" 
                           class="form-control" 
                           value="{{ request('end_date', now()->format('Y-m-d')) }}">
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <label class="form-label">고객명</label>
                <input type="text" 
                       name="name" 
                       class="form-control" 
                       value="{{ request('name') }}" 
                       placeholder="고객명 검색">
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">지역</label>
                <select name="region" class="form-select">
                    <option value="">전체</option>
                    <option value="서울" {{ request('region') == '서울' ? 'selected' : '' }}>서울</option>
                    <option value="대전" {{ request('region') == '대전' ? 'selected' : '' }}>대전</option>
                    <option value="부산" {{ request('region') == '부산' ? 'selected' : '' }}>부산</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">담당자</label>
                <select name="member" class="form-select">
                    <option value="">전체</option>
                    <option value="무효" {{ request('member') == '무효' ? 'selected' : '' }}>무효</option>
                    @foreach($members as $member)
                        <option value="{{ $member->name }}" 
                                {{ request('member') == $member->name ? 'selected' : '' }}>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-check mt-4">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="contract_only" 
                           name="contract_only" 
                           value="true"
                           {{ request('contract_only') === 'true' ? 'checked' : '' }}>
                    <label class="form-check-label" for="contract_only">
                        계약사건만 보기
                    </label>
                </div>
                <div class="form-check mt-2">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="my_cases_only" 
                           name="my_cases_only" 
                           value="true"
                           {{ request('my_cases_only') === 'true' ? 'checked' : '' }}>
                    <label class="form-check-label" for="my_cases_only">
                        나의 사건만 보기
                    </label>
                </div>
            </div>

            <div class="col-lg-3 col-md-12 d-flex align-items-end">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">검색</button>
                    <a href="{{ route('targets.index') }}" class="btn btn-secondary">초기화</a>
                    <a href="{{ route('targets.export') }}?{{ http_build_query(request()->all()) }}" 
                       class="btn btn-success"
                       onclick="return confirm('현재 필터링된 모든 데이터를 다운로드하시겠습니까?');">
                        CSV 다운로드
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>등록일자</th>
                    <th>고객명</th>
                    <th>전화번호</th>
                    <th>지역</th>
                    <th>상담자</th>
                    <th>진행현황</th>
                    <th>계약일자</th>
                    <th>수임료</th>
                    <th>송인부</th>
                    <th>배당</th>
                </tr>
            </thead>
            <tbody>
                @foreach($targets as $index => $target)
                @php
                    $initialStates = [5, 10, 11]; // 상담대기(5), 상담완료(10), 재상담필요(11)
                    $isInitialState = in_array($target->case_state, $initialStates);
                    
                    // 전체 데이터 수에서 역순으로 번호 매기기
                    $totalCount = $targets->total(); // 전체 데이터 수
                    $currentPage = $targets->currentPage();
                    $perPage = $targets->perPage();
                    $currentIndex = ($currentPage - 1) * $perPage + $index;
                    $currentNo = $totalCount - $currentIndex;
                @endphp
                <tr>
                    <td>{{ $currentNo }}</td>
                    <td>{{ \Carbon\Carbon::parse($target->create_dt)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $target->name }}</td>
                    <td>{{ $target->phone }}</td>
                    <td>{{ $target->living_place }}</td>
                    <td>{{ $target->Member }}</td>
                    <td>{{ \App\Helpers\CaseStateHelper::getStateLabel($target->case_type, $target->case_state) }}</td>
                    <td>{{ !$isInitialState && $target->contract_date ? \Carbon\Carbon::parse($target->contract_date)->format('Y-m-d') : '' }}</td>
                    <td class="text-end">
                        {{ !$isInitialState && isset($target->lawyer_fee) && $target->lawyer_fee > 0 ? number_format($target->lawyer_fee).'원' : '' }}
                    </td>
                    <td class="text-end">
                        @php
                            $total = !$isInitialState ? ($target->total_const_delivery ?? 0) + 
                                     ($target->stamp_fee ?? 0) + 
                                     ($target->total_debt_cert_cost ?? 0) : 0;
                        @endphp
                        {{ $total > 0 ? number_format($total).'원' : '' }}
                    </td>
                    <td>
                        @if($target->div_case)
                            <span class="text-success">배당완료</span>
                        @elseif(!$isInitialState && $target->contract_date && isset($target->lawyer_fee) && $target->lawyer_fee > 0)
                            <button type="button" 
                                    class="btn btn-sm btn-primary assign-btn" 
                                    data-case-id="{{ $target->idx_TblCase }}">
                                배당
                            </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $targets->withQueryString()->links() }}
    </div>

    <!-- 배당 모달 추가 -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">담당자 선택</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="assignForm">
                        @csrf
                        <input type="hidden" id="caseId" name="caseId">
                        <div class="mb-3">
                            <label class="form-label">담당자 선택</label>
                            <select class="form-select" name="case_manager" required>
                                <option value="">선택하세요</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->name }}">{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">메모</label>
                            <textarea class="form-control" name="assignment_memo" id="assignmentMemo" rows="6" placeholder="사건을 배당받는 담당자에게 전달해야 할 사항을 작성하세요."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" id="confirmAssign">확인</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 페이지 자동 새로고침 설정 (1분 = 60000 밀리초)
setInterval(function() {
    location.reload();
}, 60000);

console.log('스크립트 시작');

$(document).ready(function() {
    console.log('Document ready 실행됨');
    
    // 배당 버튼 존재 여부 확인
    console.log('배당 버튼 개수:', $('.assign-btn').length);
    
    $('.assign-btn').on('click', function() {
        console.log('버튼 클릭됨');
        const caseId = $(this).data('case-id');
        console.log('Case ID:', caseId);
        
        // caseId를 모달의 hidden input에 설정
        $('#caseId').val(caseId);
        
        const modalElement = document.getElementById('assignModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    });

    // 확인 버튼 클릭 시
    $('#confirmAssign').on('click', function() {
        const caseId = $('#caseId').val();
        const manager = $('select[name="case_manager"]').val();
        const memo = $('#assignmentMemo').val();
        
        console.log('배당 처리 시작:', { caseId, manager, memo });

        if (!manager) {
            alert('담당자를 선택해주세요.');
            return;
        }

        if (!caseId) {
            console.error('Case ID가 없습니다.');
            alert('케이스 정보를 찾을 수 없습니다.');
            return;
        }

        // URL 생성 시 caseId 포함
        const requestUrl = `/case-assignments/assign/${caseId}`;
        console.log('요청 URL:', requestUrl);

        $.ajax({
            url: requestUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                case_manager: manager,
                notes: memo
            },
            beforeSend: function(xhr) {
                console.log('요청 정보:', {
                    url: this.url,
                    method: this.type,
                    caseId: caseId,
                    manager: manager,
                    memo: memo,
                    csrf: this.headers['X-CSRF-TOKEN']
                });
            },
            success: function(response) {
                console.log('배당 성공:', response);
                if (response.success) {
                    alert(response.message);
                    window.location.href = '{{ route("case-assignments.index") }}';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('배당 실패 상세 정보:', {
                    url: requestUrl,
                    caseId: caseId,
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    state: xhr.state(),
                    readyState: xhr.readyState
                });
                alert('배당 처리  오류가 발생했습니다.');
            }
        });
    });

    // 모달 초기화
    $('#assignModal').on('hidden.bs.modal', function () {
        $('#assignForm')[0].reset();
    });

    // 통계 토글 기능
    $('.stats-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('active');
        $('.stats-detail').slideToggle(300);
        
        // 버튼 텍스트 변경
        const isExpanded = $(this).hasClass('active');
        $(this).html(isExpanded ? '접기 <i class="fas fa-chevron-up ms-1"></i>' : '상세보기 <i class="fas fa-chevron-down ms-1"></i>');
    });
});
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* 테이블 폰트 크기 조정 */
.container .table th, 
.container .table td {
    vertical-align: middle;
    font-size: 0.85rem !important;  /* 폰트 크기 변경 */
}

/* 필터 영역 폰트 크기 조정 */
.container .filters-container {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
    font-size: 0.85rem !important;  /* 폰트 크기 변경 */
}

/* 필터 영역 내부 요소들 폰트 크기 조정 */
.container .filters-container label,
.container .filters-container input,
.container .filters-container select,
.container .filters-container button {
    font-size: 0.85rem !important;  /* 폰트 크기 변경 */
}

/* 통계 영역 폰트 크기 조정 */
.container .stats-container .text-muted {
    font-size: 0.85rem !important;  /* 폰트 크기 변경 */
}

/* 배당 모달 스타일 */
#assignModal .modal-dialog {
    height: 60vh;
}

#assignModal .modal-content {
    height: 100%;
}

#assignModal .modal-body {
    overflow-y: auto;
}

#assignmentMemo {
    min-height: 150px;
}

/* 기존 스타일 유지 */
.stats-container .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.stats-container .h4 {
    color: #2c3e50;
    font-weight: 600;
}

.text-end {
    text-align: right;
}

@media (max-width: 768px) {
    .d-flex.gap-2 {
        width: 100%;
    }
    .d-flex.gap-2 button,
    .d-flex.gap-2 a {
        flex: 1;
    }
}

/* 통계 테이블 스타일 */
.stats-container table {
    margin-bottom: 0;
}

.stats-container th {
    font-weight: 500;
    color: #666;
}

.stats-container td {
    font-weight: 600;
    color: #333;
}

.stats-toggle {
    min-width: 100px;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}

.stats-toggle i {
    transition: transform 0.2s;
}

.stats-toggle.active i {
    transform: rotate(180deg);
}

.stats-detail {
    margin-top: 1.5rem;
    border-top: 1px solid #dee2e6;
    padding-top: 1.5rem;
}

.stats-basic {
    margin-bottom: 0;
}

/* 통계 테이블 반응형 */
@media (max-width: 768px) {
    .stats-container table {
        font-size: 0.8rem;
    }
}
</style>
@endpush 