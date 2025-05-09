@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    

    <!-- 통계 정보 -->
    <div class="stats-container mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">입금 현황</h5>
                <div class="d-flex justify-content-around">
                    <div class="text-center">
                        <div class="h4 mb-0">{{ number_format($statistics['total_count']) }}</div>
                        <small class="text-muted">총 건수</small>
                    </div>
                    <div class="text-center">
                        <div class="h4 mb-0">{{ number_format($statistics['service_sales']) }}원</div>
                        <small class="text-muted">서비스매출</small>
                    </div>
                    <div class="text-center">
                        <div class="h4 mb-0">{{ number_format($statistics['songinbu']) }}원</div>
                        <small class="text-muted">송인부</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 섹션 -->
    <div class="filters-container mb-4">
        <form action="{{ route('transactions3.index') }}" method="GET" class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">기간</label>
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
                <label class="form-label">금액</label>
                <input type="number" 
                       name="amount" 
                       class="form-control" 
                       value="{{ request('amount') }}" 
                       placeholder="금액 입력">
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">고객명</label>
                <input type="text" 
                       name="description" 
                       class="form-control" 
                       value="{{ request('description') }}" 
                       placeholder="고객명 검색">
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">계정</label>
                <select name="account" class="form-select">
                    <option value="">전체</option>
                    <option value="none" {{ request('account') == 'none' ? 'selected' : '' }}>선택없음</option>
                    <option value="서비스매출" {{ request('account') == '서비스매출' ? 'selected' : '' }}>서비스매출</option>
                    <option value="송인부" {{ request('account') == '송인부' ? 'selected' : '' }}>송인부</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">담당자</label>
                <select name="manager" class="form-select">
                    <option value="">전체</option>
                    <option value="none" {{ request('manager') == 'none' ? 'selected' : '' }}>선택없음</option>
                    @foreach($members as $member)
                        <option value="{{ $member->name }}" 
                                {{ request('manager') == $member->name ? 'selected' : '' }}>
                            {{ $member->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label class="form-label">메모</label>
                <input type="text" 
                       name="memo" 
                       class="form-control" 
                       value="{{ request('memo') }}" 
                       placeholder="메모 검색">
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-check">
                    <input type="hidden" name="exclude_refunds" value="0">
                    <input type="checkbox" 
                           class="form-check-input" 
                           name="exclude_refunds" 
                           id="exclude_refunds" 
                           value="1" 
                           {{ request('exclude_refunds', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="exclude_refunds">송달환급금 등 불포함</label>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">검색</button>
                    <a href="{{ route('transactions3.index') }}" class="btn btn-secondary">초기화</a>
                    <a href="{{ route('transactions3.export') }}?{{ http_build_query(request()->all()) }}" 
                       class="btn btn-success"
                       onclick="return confirm('현금영수증 미발행 서비스매출 데이터를 다운로드하시겠습니까?');">
                        엑셀 다운로드
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>결제일자</th>
                    <th>결제시간</th>
                    <th>결제금액</th>
                    <th>고객명</th>
                    <th>계정</th>
                    <th>담당자</th>
                    <th>메모</th>
                    <th>현금영수증</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($transaction->date)->format('Y-m-d') }}</td>
                    <td>{{ $transaction->time }}</td>
                    <td class="text-end">{{ number_format($transaction->amount) }}원</td>
                    <td>{{ $transaction->description }}</td>
                    <td>
                        <form action="{{ route('transactions3.update', $transaction->id) }}" method="POST" class="account-form">
                            @csrf
                            @method('PUT')
                            <select name="account" 
                                    class="form-select form-select-sm {{ $transaction->account == null ? 'text-danger fw-bold' : '' }}" 
                                    onchange="this.form.submit()">
                                <option value="" {{ $transaction->account == null ? 'selected' : '' }}>선택없음</option>
                                <option value="서비스매출" {{ $transaction->account == '서비스매출' ? 'selected' : '' }}>서비스매출</option>
                                <option value="송인부" {{ $transaction->account == '송인부' ? 'selected' : '' }}>송인부</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('transactions3.update', $transaction->id) }}" method="POST" class="manager-form">
                            @csrf
                            @method('PUT')
                            <select name="manager" 
                                    class="form-select form-select-sm {{ empty($transaction->manager) ? 'text-danger fw-bold' : '' }}" 
                                    onchange="this.form.submit()">
                                <option value="">선택없음</option>
                                @foreach($members as $member)
                                    <option value="{{ $member->name }}" {{ $transaction->manager == $member->name ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="{{ route('transactions3.update', $transaction->id) }}" method="POST" class="memo-form">
                            @csrf
                            @method('PUT')
                            <input type="text" name="memo" class="form-control form-control-sm" 
                                   value="{{ $transaction->memo }}" 
                                   onchange="this.form.submit()">
                        </form>
                    </td>
                    <td class="text-center">
                        <span class="badge cash-receipt-badge cursor-pointer {{ $transaction->cash_receipt ? 'bg-success' : 'bg-warning' }}" 
                              data-id="{{ $transaction->id }}" 
                              data-status="{{ $transaction->cash_receipt ? 'true' : 'false' }}">
                            {{ $transaction->cash_receipt ? '발행완료' : '미발행' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $transactions->withQueryString()->links() }}
    </div>
</div>

<style>
.form-select-sm, .form-control-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}
.text-end {
    text-align: right;
}
.table th, .table td {
    vertical-align: middle;
    font-size: 0.85rem;
}
.stats-container .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.stats-container .h4 {
    color: #2c3e50;
    font-weight: 600;
}

.filters-container {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.5rem;
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

/* 현금영수증 배지 스타일 */
.cash-receipt-badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
    cursor: pointer;
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 계정 선택 변경 시 자동 저장
    document.querySelectorAll('.account-form select').forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // 담당자 선택 변경 시 자동 저장
    document.querySelectorAll('.manager-form select').forEach(select => {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // 메모 입력 필드에서 포커스를 잃었을 때 자동 저장
    document.querySelectorAll('.memo-form input').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value !== this.defaultValue) {
                this.closest('form').submit();
            }
        });
    });

    // 현금영수증 배지 클릭 이벤트 처리
    document.querySelectorAll('.cash-receipt-badge').forEach(badge => {
        badge.addEventListener('click', function(event) {
            event.stopPropagation();
            const id = this.dataset.id;
            const currentStatus = this.dataset.status === 'true';
            const newStatus = !currentStatus;
            const statusText = newStatus ? '발행완료' : '미발행';
            
            if (confirm(`현금영수증 상태를 '${statusText}'로 변경하시겠습니까?`)) {
                // AJAX 요청으로 상태 업데이트
                fetch(`/transactions3/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ cash_receipt: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 배지 상태 업데이트
                        this.textContent = data.cash_receipt ? '발행완료' : '미발행';
                        this.classList.toggle('bg-warning', !data.cash_receipt);
                        this.classList.toggle('bg-success', data.cash_receipt);
                        this.dataset.status = data.cash_receipt ? 'true' : 'false';
                        
                        // 알림 표시
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('현금영수증 상태 변경 중 오류가 발생했습니다.');
                });
            }
        });
    });
});
</script>
@endsection
