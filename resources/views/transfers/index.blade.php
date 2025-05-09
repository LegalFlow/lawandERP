@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-tools">
                        <a href="{{ route('transfers.index', ['show_all' => 1]) }}" 
                           class="btn btn-outline-secondary me-2" 
                           title="전체보기" 
                           id="showAllBtn">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('transfers.download-excel') }}" 
                           class="btn btn-success me-2" 
                           title="Excel Download">
                            <i class="bi bi-file-earmark-excel"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-primary me-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#accountInfoModal" 
                                title="자주 쓰는 계좌">
                            <i class="bi bi-credit-card"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#createTransferModal" 
                                title="신규등록">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-outline-primary ms-2" 
                                id="toggleFilters"
                                title="필터">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                    <div class="m-0">
                        <span class="text-primary" style="font-size: 0.9rem;">납부해야 할 금액: {{ number_format($totalPendingAmount) }} 원</span>
                    </div>
                </div>
                
                <!-- 필터 섹션 추가 -->
                <div id="filterSection" class="card-body border-bottom p-3" style="display: none;">
                    <form id="filterForm" action="{{ route('transfers.index') }}" method="GET">
                        @if(request()->has('show_all'))
                            <input type="hidden" name="show_all" value="1">
                        @endif
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small">등록일자</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                                    <span class="input-group-text">~</span>
                                    <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">납부유형</label>
                                <select class="form-select form-select-sm" name="payment_type">
                                    <option value="">전체</option>
                                    @foreach(App\Models\Transfer::PAYMENT_TYPES as $type)
                                        <option value="{{ $type }}" {{ request('payment_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">납부대상</label>
                                <select class="form-select form-select-sm" name="payment_target">
                                    <option value="">전체</option>
                                    @foreach(App\Models\Transfer::PAYMENT_TARGETS as $target)
                                        <option value="{{ $target }}" {{ request('payment_target') == $target ? 'selected' : '' }}>{{ $target }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">담당자</label>
                                <select class="form-select form-select-sm" name="manager">
                                    <option value="">전체</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->name }}" {{ request('manager') == $member->name ? 'selected' : '' }}>{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">승인상태</label>
                                <select class="form-select form-select-sm" name="approval_status">
                                    <option value="">전체</option>
                                    @foreach(App\Models\Transfer::APPROVAL_STATUS as $status)
                                        <option value="{{ $status }}" {{ request('approval_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">납부상태</label>
                                <select class="form-select form-select-sm" name="payment_status">
                                    <option value="">전체</option>
                                    @foreach(App\Models\Transfer::PAYMENT_STATUS as $status)
                                        <option value="{{ $status }}" {{ request('payment_status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">검색</label>
                                <div class="input-group input-group-sm">
                                    <select class="form-select form-select-sm" name="search_field" style="max-width: 110px;">
                                        <option value="client_name" {{ request('search_field') == 'client_name' ? 'selected' : '' }}>고객명</option>
                                        <option value="case_number" {{ request('search_field') == 'case_number' ? 'selected' : '' }}>사건번호</option>
                                        <option value="court_name" {{ request('search_field') == 'court_name' ? 'selected' : '' }}>관할법원</option>
                                        <option value="virtual_account" {{ request('search_field') == 'virtual_account' ? 'selected' : '' }}>계좌번호</option>
                                    </select>
                                    <input type="text" class="form-control form-control-sm" name="search_term" value="{{ request('search_term') }}" placeholder="검색어 입력">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">금액범위</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm" name="amount_from" value="{{ request('amount_from') }}" placeholder="최소">
                                    <span class="input-group-text">~</span>
                                    <input type="number" class="form-control form-control-sm" name="amount_to" value="{{ request('amount_to') }}" placeholder="최대">
                                </div>
                            </div>
                            <div class="col-md-3 ms-auto d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-sm me-2">
                                    <i class="bi bi-search"></i> 검색
                                </button>
                                <a href="{{ route('transfers.index', request()->has('show_all') ? ['show_all' => 1] : []) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-counterclockwise"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>등록일자</th>
                                <th>납부유형</th>
                                <th>납부대상</th>
                                <th>고객명</th>
                                <th>관할법원</th>
                                <th>사건번호</th>
                                <th>계약자</th>
                                <th>담당자</th>
                                <th>은행</th>
                                <th>계좌번호</th>
                                <th>납부금액</th>
                                <th>승인상태</th>
                                <th>납부상태</th>
                                <th style="width: 100px;">오류코드</th>
                                <th>파일</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                                <tr class="cursor-pointer" onclick="location.href='{{ route('transfers.show', $transfer->id) }}'">
                                    <td>{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $transfer->payment_type }}</td>
                                    <td>{{ $transfer->payment_target }}</td>
                                    <td>{{ $transfer->client_name }}</td>
                                    <td>{{ $transfer->court_name }}</td>
                                    <td>{{ $transfer->case_number }}</td>
                                    <td>{{ $transfer->consultant }}</td>
                                    <td>{{ $transfer->manager }}</td>
                                    <td>{{ $transfer->bank }}</td>
                                    <td>{{ $transfer->virtual_account }}</td>
                                    <td>{{ number_format($transfer->payment_amount) }}</td>
                                    <td>
                                        @if($transfer->approval_status === '승인대기')
                                            <span class="badge bg-warning approval-badge cursor-pointer" 
                                                  data-transfer-id="{{ $transfer->id }}"
                                                  onclick="event.stopPropagation();">승인대기</span>
                                        @else
                                            <span class="badge bg-success">승인완료</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transfer->payment_status === '납부대기')
                                            <span class="badge bg-warning payment-badge cursor-pointer"
                                                  data-transfer-id="{{ $transfer->id }}"
                                                  onclick="event.stopPropagation();">납부대기</span>
                                        @else
                                            <span class="badge bg-success">납부완료</span>
                                        @endif
                                    </td>
                                    <td onclick="event.stopPropagation();">
                                        <input type="text" 
                                               class="form-control form-control-sm error-code-input" 
                                               value="{{ $transfer->error_code }}" 
                                               data-transfer-id="{{ $transfer->id }}"
                                               maxlength="6" 
                                               style="width: 100px;">
                                    </td>
                                    <td onclick="event.stopPropagation();">
                                        @if($transfer->files->where('del_flag', false)->count() > 0)
                                            <a href="{{ route('transfers.download-files', $transfer->id) }}" class="text-primary me-1">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center">등록된 이체요청이 없습니다.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    {{ $transfers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 파일 업로드 활성화 함수 추가
    function enableFileUpload() {
        $('.custom-file-upload').show();
        $('#transferForm button[type="submit"]').show();
    }

    // 필터 토글 기능 추가
    $('#toggleFilters').click(function() {
        $('#filterSection').slideToggle(300);
        $(this).toggleClass('active');
        
        if ($(this).hasClass('active')) {
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        } else {
            $(this).removeClass('btn-primary').addClass('btn-outline-primary');
        }
    });
    
    // URL에 필터 파라미터가 있으면 필터 섹션 자동으로 표시
    if (window.location.search && window.location.search !== '?show_all=1') {
        $('#filterSection').show();
        $('#toggleFilters').addClass('active').removeClass('btn-outline-primary').addClass('btn-primary');
    }

    // 신규등록 폼 제출
    $('#createTransferModal form').on('submit', function(e) {
        e.preventDefault();
        
        // 새로운 FormData 객체 생성
        let formData = new FormData();
        
        // 기본 필드들 추가
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('payment_type', $('select[name="payment_type"]').val());
        formData.append('payment_target', $('select[name="payment_target"]').val());
        formData.append('client_name', $('input[name="client_name"]').val());
        formData.append('court_name', $('input[name="court_name"]').val());
        formData.append('case_number', $('input[name="case_number"]').val());
        formData.append('consultant', $('select[name="consultant"]').val());
        formData.append('manager', $('select[name="manager"]').val());
        formData.append('bank', $('select[name="bank"]').val());
        formData.append('virtual_account', $('input[name="virtual_account"]').val());
        
        // payment_amount 처리
        let paymentAmount = $('input[name="payment_amount"]').val().replace(/,/g, '');
        formData.append('payment_amount', paymentAmount);
        
        formData.append('memo', $('textarea[name="memo"]').val());
        
        // 파일 처리
        let fileInput = $('input[name="files[]"]')[0];
        if (fileInput && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('files[]', fileInput.files[i]);
            }
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Success:', response);
                if (response.success) {
                    alert(response.message);
                    window.location.href = response.redirect;
                }
            },
            error: function(xhr, status, error) {
                console.log('Error:', xhr.responseText);
                let errorMessage = '저장 중 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            }
        });
    });

    // 파일 선택 시 이벤트 처리
    $('#createFileInput').on('change', function(e) {
        const files = e.target.files;
        const fileCount = files.length;
        
        if (fileCount > 10) {
            alert('최대 10개까지만 업로드 가능합니다.');
            this.value = '';
            return;
        }
    });

    // 천단위 구분 기능
    $('input[name="payment_amount"]').on('input', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        $(this).val(Number(value).toLocaleString());
    });

    let currentTransferId = null;

    // 납부대기 뱃지 클릭 이벤트 추가
    $('.payment-badge').click(function(e) {
        e.stopPropagation();
        const transferId = $(this).data('transfer-id');
        
        if (confirm('납부완료 처리하시겠습니까?')) {
            $.ajax({
                url: `/transfers/${transferId}/payment-status`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert('처리 중 오류가 발생했습니다.');
                }
            });
        }
    });

    // 승인대기 뱃지 클릭 이벤트 수정
    $('.approval-badge').click(function(e) {
        e.stopPropagation();
        currentTransferId = $(this).data('transfer-id');
        
        // 모달 표시
        $('#approvalModal').modal('show');
        
        // 초기 데이터 로드
        loadDepositHistory(currentTransferId, 1);
    });

    // 기간 필터 변경 이벤트
    $('#periodFilter').change(function() {
        const period = $(this).val();
        loadDepositHistory(currentTransferId, period);
    });

    // 승인 확인 버튼 클릭
    $('#confirmApproval').click(function() {
        if (confirm('승인하시겠습니까?')) {
            $.ajax({
                url: `/transfers/${currentTransferId}/approve`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#approvalModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    alert('승인 처리 중 오류가 발생했습니다.');
                }
            });
        }
    });

    // 데이터 로드 함수
    function loadDepositHistory(transferId, period) {
        $.ajax({
            url: `/transfers/${transferId}/deposit-history`,
            data: { period: period },
            success: function(response) {
                updateSearchResults(response.data);
                updateStatusIndicator(response.status, response.totalAmount, response.requestAmount);
            }
        });
    }

    // 검색 결과 업데이트 함수
    function updateSearchResults(data) {
        const tbody = $('#searchResults');
        tbody.empty();
        
        data.forEach(item => {
            tbody.append(`
                <tr>
                    <td>${item.deposit_date}</td>
                    <td>${item.bank_account}</td>
                    <td class="text-end">${Number(item.amount).toLocaleString()}</td>
                    <td>${item.client_name}</td>
                    <td>${item.account || ''}</td>
                    <td>${item.manager || ''}</td>
                    <td>${item.memo || ''}</td>
                </tr>
            `);
        });
    }

    // 상태 표시등 업데이트 함수
    function updateStatusIndicator(status, totalAmount, requestAmount) {
        const light = $('#statusLight');
        const totalText = $('#totalAmountText');
        const requestText = $('#requestAmountText');
        
        light.css('background-color', status === 'green' ? '#28a745' : '#dc3545');
        totalText.text(`송인부 계정 입금액 합계: ${Number(totalAmount).toLocaleString()}원`);
        requestText.text(`납부해야 할 금액: ${Number(requestAmount).toLocaleString()}원`);
    }

    // 모달이 닫힐 때 폼 초기화
    $('#createTransferModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
    });

    // 오류코드 입력 처리
    let errorCodeTimer;
    $('.error-code-input').on('input', function() {
        clearTimeout(errorCodeTimer);
        const $input = $(this);
        const transferId = $input.data('transfer-id');
        const errorCode = $input.val();

        errorCodeTimer = setTimeout(function() {
            $.ajax({
                url: `/transfers/${transferId}/update-error-code`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    error_code: errorCode
                },
                success: function(response) {
                    if (response.success) {
                        $input.removeClass('is-invalid').addClass('is-valid');
                        setTimeout(() => $input.removeClass('is-valid'), 1000);
                    }
                },
                error: function() {
                    $input.addClass('is-invalid');
                }
            });
        }, 500);
    });

    // 전체보기 버튼 상태 관리
    const urlParams = new URLSearchParams(window.location.search);
    const showAll = urlParams.has('show_all');
    
    if (showAll) {
        $('#showAllBtn')
            .removeClass('btn-outline-secondary')
            .addClass('btn-secondary')
            .attr('href', '{{ route("transfers.index") }}')
            .attr('title', '대기항목만 보기');
    }
});
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
/* 모달 너비 조정 */
#createTransferModal .modal-dialog {
    max-width: 25%;
}

/* 모바일 대응 */
@media (max-width: 768px) {
    #createTransferModal .modal-dialog {
        max-width: 95%;
        margin: 1.75rem auto;
    }
    
    #filterSection .row > div {
        margin-bottom: 0.5rem;
    }
}

/* 테이블 스타일 */
.table {
    font-size: 0.85rem;  /* 기본 폰트 사이즈보다 작게 설정 */
}

/* 클릭 가능한 행 스타일 */
.table tbody tr {
    cursor: pointer;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

.cursor-pointer {
    cursor: pointer;
}

#approvalModal .modal-dialog {
    max-width: 50%;
}

#approvalModal .table th {
    white-space: nowrap;
}

#accountInfoModal .modal-dialog {
    max-width: 40%;
}

#accountInfoModal .table {
    font-size: 0.9rem;
}

.error-code-input {
    font-size: 0.85rem;
    padding: 2px 5px;
    height: auto;
}

.error-code-input:focus {
    box-shadow: none;
    border-color: #80bdff;
}

/* 필터 섹션 스타일 */
#filterSection {
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

#filterSection .form-label {
    font-weight: 500;
    margin-bottom: 0.2rem;
}

#filterSection .input-group-text {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

#toggleFilters.active {
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* 바지게판 버튼 스타일 */
.badge.cursor-pointer:hover {
    opacity: 0.8;
}

/* 검색 필드 스타일 */
#filterForm select, #filterForm input {
    border-radius: 0.25rem;
}
</style>
@endpush


<!-- 신규등록 모달 -->
<div class="modal fade" id="createTransferModal" tabindex="-1" aria-labelledby="createTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTransferModalLabel">이체요청 신규등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('transfers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label>납부유형 <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_type" required>
                                    <option value="계좌이체">계좌이체</option>
                                    <option value="서면납부">서면납부</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>납부대상 <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_target" required>
                                    <option value="신건접수">신건접수</option>
                                    <option value="민사예납">민사예납</option>
                                    <option value="송달료환급">송달료환급</option>
                                    <option value="환불">환불</option>
                                    <option value="과오납">과오납</option>
                                    <option value="즉시항고">즉시항고</option>
                                    <option value="추완항고">추완항고</option>
                                    <option value="금지명령">금지명령</option>
                                    <option value="중지명령">중지명령</option>
                                    <option value="소송구조">소송구조</option>
                                    <option value="집행정지">집행정지</option>
                                    <option value="압류중지">압류중지</option>
                                    <option value="기타">기타</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>고객명 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="client_name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>관할법원</label>
                                <input type="text" class="form-control" name="court_name">
                            </div>
                            <div class="form-group mb-3">
                                <label>사건번호</label>
                                <input type="text" class="form-control" name="case_number">
                            </div>
                            <div class="form-group mb-3">
                                <label>계약자</label>
                                <select class="form-control" name="consultant">
                                    <option value="">선택하세요</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->name }}">{{ $member->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>담당자 <span class="text-danger">*</span></label>
                                <select class="form-control" name="manager" required>
                                    <option value="">선택하세요</option>
                                    @foreach($members as $member)
                                        <option value="{{ $member->name }}" 
                                            {{ Auth::user()->name === $member->name ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>은행</label>
                                <select class="form-control" name="bank">
                                    <option value="신한">신한</option>
                                    <option value="경남">경남</option>
                                    <option value="광주">광주</option>
                                    <option value="국민">국민</option>
                                    <option value="기업">기업</option>
                                    <option value="농협">농협</option>
                                    <option value="도이치">도이치</option>
                                    <option value="부산">부산</option>
                                    <option value="산업">산업</option>
                                    <option value="상호저축">상호저축</option>
                                    <option value="새마을">새마을</option>
                                    <option value="수협">수협</option>
                                    <option value="신협">신협</option>
                                    <option value="씨티">씨티</option>
                                    <option value="아이엠뱅크(대구)">아이엠뱅크(대구)</option>
                                    <option value="외환">외환</option>
                                    <option value="우리">우리</option>
                                    <option value="우체국">우체국</option>
                                    <option value="전북">전북</option>
                                    <option value="제주">제주</option>
                                    <option value="지역농축협">지역농축협</option>
                                    <option value="토스뱅크">토스뱅크</option>
                                    <option value="하나">하나</option>
                                    <option value="케이뱅크">케이뱅크</option>
                                    <option value="카카오뱅크">카카오뱅크</option>
                                    <option value="공상">공상</option>
                                    <option value="BNP파리바">BNP파리바</option>
                                    <option value="JP모간">JP모간</option>
                                    <option value="BOA">BOA</option>
                                    <option value="HSBC">HSBC</option>
                                    <option value="SC제일은행">SC제일은행</option>
                                    <option value="산림조합중앙회">산림조합중앙회</option>
                                    <option value="중국건설">중국건설</option>
                                    <option value="구조흥">구조흥</option>
                                    <option value="구신한">구신한</option>
                                    <option value="지방세입">지방세입</option>
                                    <option value="국고금">국고금</option>
                                    <option value="유안타증권">유안타증권</option>
                                    <option value="KB증권">KB증권</option>
                                    <option value="미래에셋증권(230)">미래에셋증권(230)</option>
                                    <option value="미래에셋증권(238)">미래에셋증권(238)</option>
                                    <option value="삼성증권">삼성증권</option>
                                    <option value="IBK투자증권">IBK투자증권</option>
                                    <option value="한국투자증권">한국투자증권</option>
                                    <option value="NH투자증권">NH투자증권</option>
                                    <option value="아이엠증권">아이엠증권</option>
                                    <option value="현대차증권">현대차증권</option>
                                    <option value="에스케이증권">에스케이증권</option>
                                    <option value="한화증권">한화증권</option>
                                    <option value="하나증권">하나증권</option>
                                    <option value="신한투자증권">신한투자증권</option>
                                    <option value="메리츠종합금융증권">메리츠종합금융증권</option>
                                    <option value="유진투자증권">유진투자증권</option>
                                    <option value="신영증권">신영증권</option>
                                    <option value="교보증권">교보증권</option>
                                    <option value="대신증권">대신증권</option>
                                    <option value="동부증권">동부증권</option>
                                    <option value="부국증권">부국증권</option>
                                    <option value="LS증권">LS증권</option>
                                    <option value="솔로몬투자증권">솔로몬투자증권</option>
                                    <option value="케이프투자증권">케이프투자증권</option>
                                    <option value="키움증권">키움증권</option>
                                    <option value="BNK투자증권">BNK투자증권</option>
                                    <option value="우리투자증권">우리투자증권</option>
                                    <option value="다올투자증권">다올투자증권</option>
                                    <option value="카카오페이증권">카카오페이증권</option>
                                    <option value="상상인증권">상상인증권</option>
                                    <option value="토스증권">토스증권</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>계좌번호</label>
                                <input type="text" class="form-control" name="virtual_account" 
                                       pattern="[0-9]+" title="숫자만 입력해주세요">
                            </div>
                            <div class="form-group mb-3">
                                <label>납부금액 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="payment_amount" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>파일 첨부</label>
                                <input type="file" class="form-control" name="files[]" multiple accept="application/pdf">
                                <small class="text-muted">PDF 파일만 가능합니다. (최대 10개)</small>
                            </div>
                            <div class="form-group mb-3">
                                <label>메모</label>
                                <textarea class="form-control" name="memo" rows="3"></textarea>
                            </div>
                            <div class="small text-muted mb-3">
                                <span class="text-danger">*</span> 표시는 필수 입력 항목입니다.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">등록</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 승인 확인 모달 추가 -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">승인 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <select class="form-select" style="width: 150px" id="periodFilter">
                        <option value="1">최근 1개월</option>
                        <option value="3">최근 3개월</option>
                        <option value="6">최근 6개월</option>
                        <option value="12">최근 1년</option>
                        <option value="all">전체 기간</option>
                    </select>
                    <div class="status-indicator d-flex align-items-center">
                        <div id="statusLight" class="rounded-circle me-2" style="width: 20px; height: 20px;"></div>
                        <span id="totalAmountText" class="me-3"></span>
                        <span id="requestAmountText"></span>
                    </div>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover">
                        <thead style="position: sticky; top: 0; background: white;">
                            <tr>
                                <th>입금일자</th>
                                <th>입금계좌</th>
                                <th>입금액</th>
                                <th>고객명</th>
                                <th>계정</th>
                                <th>담당자</th>
                                <th>메모</th>
                            </tr>
                        </thead>
                        <tbody id="searchResults"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmApproval">승인</button>
            </div>
        </div>
    </div>
</div>

<!-- 자주 쓰는 계좌 정보 모달 -->
<div class="modal fade" id="accountInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">자주 쓰는 계좌 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>순번</th>
                                <th>은행</th>
                                <th>계좌번호</th>
                                <th>예금주</th>
                                <th>계좌설명</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_map('str_getcsv', file(resource_path('views/transfers/account_info.csv'))) as $index => $row)
                                @if($index > 0) {{-- 헤더 행 제외 --}}
                                    <tr>
                                        <td>{{ $row[0] }}</td>
                                        <td>{{ $row[1] }}</td>
                                        <td>{{ $row[2] }}</td>
                                        <td>{{ $row[3] }}</td>
                                        <td>{{ $row[4] }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
