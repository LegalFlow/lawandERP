@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if($hasStandard)
    <!-- 통계 영역 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ $statistics['date'] }} 기준 분기성과</h3>
                    <div>
                        <form id="quarterSelectForm" class="d-flex align-items-center" method="GET">
                            <select id="quarterSelector" class="form-select form-select-sm" style="width: auto;">
                                @foreach($availableQuarters as $quarter)
                                    <option value="{{ $quarter['year'] }}-{{ $quarter['quarter'] }}" 
                                        {{ ($selectedYear == $quarter['year'] && $selectedQuarter == $quarter['quarter']) || 
                                           (!$selectedYear && !$selectedQuarter && $quarter['year'] == now()->year && $quarter['quarter'] == ceil(now()->month/3)) 
                                        ? 'selected' : '' }}>
                                        {{ $quarter['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 첫 번째 행 -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">경과일수</span>
                                    <span class="info-box-number">
                                        {{ $statistics['elapsedDays'] }}일/{{ $statistics['totalDays'] }}일
                                        ({{ number_format($statistics['elapsedRate'], 2) }}%)
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">월 기준 매출액</span>
                                    <span class="info-box-number">{{ number_format($statistics['monthlyStandard']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 기준 매출액</span>
                                    <span class="info-box-number">{{ number_format($statistics['quarterlyStandard']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">현재 기준 매출액</span>
                                    <span class="info-box-number">{{ number_format($statistics['currentStandard']) }}원</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 두 번째 행 -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="info-box {{ $statistics['currentRange'] === 'plus20' ? 'status-blink' : '' }}"
                                 id="plus20Box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 기준 20%</span>
                                    <span class="info-box-number">{{ number_format($statistics['standardPlus20']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box {{ $statistics['currentRange'] === 'plus10' ? 'status-blink' : '' }}"
                                 id="plus10Box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 기준 10%</span>
                                    <span class="info-box-number">{{ number_format($statistics['standardPlus10']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box {{ $statistics['currentRange'] === 'minus10' ? 'status-blink' : '' }}"
                                 id="minus10Box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 기준 -10%</span>
                                    <span class="info-box-number">{{ number_format($statistics['standardMinus10']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box {{ $statistics['currentRange'] === 'minus20' ? 'status-blink' : '' }}"
                                 id="minus20Box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 기준 -20%</span>
                                    <span class="info-box-number">{{ number_format($statistics['standardMinus20']) }}원</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 세 번째 행 -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">현재 매출액</span>
                                    <span class="info-box-number">{{ number_format($statistics['currentAmount']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 예상 매출액</span>
                                    <span class="info-box-number">{{ number_format($statistics['estimatedAmount']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 예상 성과금</span>
                                    <span class="info-box-number">{{ number_format($statistics['expectedBonus']) }}원</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">분기 예상 보상 및 제재</span>
                                    <span class="info-box-number">{{ $statistics['reward'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 연말정산 파일 다운로드 영역 추가 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">연말정산 자료</h3>
                        <div class="d-flex align-items-center">
                            <span class="mr-2">근로소득 원천징수명세서</span>
                            <form action="{{ route('mypage.salary-statements.download-tax-file') }}" method="GET" class="form-inline m-0 d-flex align-items-center">
                                <select name="year" id="tax-year" class="form-control form-control-sm mx-2" style="width: auto;">
                                    @for($y = date('Y'); $y >= 2024; $y--)
                                        <option value="{{ $y }}" {{ $y == date('Y', strtotime('-1 year')) ? 'selected' : '' }}>{{ $y }}년</option>
                                    @endfor
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download mr-1"></i> 다운로드
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 디버깅 정보 표시 (개발 중에만 사용) -->
    {{--
    @if(session('debug_info') || session('found_file'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-header">
                    <h3 class="card-title">디버깅 정보</h3>
                </div>
                <div class="card-body">
                    @if(session('debug_info'))
                        <p><strong>사용자 정보:</strong> {{ session('debug_info') }}</p>
                    @endif
                    @if(session('found_file'))
                        <p><strong>찾은 파일:</strong> {{ session('found_file') }}</p>
                    @endif
                    @if(session('debug_files'))
                        <p><strong>디렉토리 파일 목록:</strong></p>
                        <ul>
                            @foreach(session('debug_files') as $file)
                                <li>{{ $file }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    --}}

    <!-- 기존 급여명세서 목록 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">내 급여명세서</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>해당월</th>
                                <th class="text-right">세전총급여</th>
                                <th class="text-right">공제총액</th>
                                <th class="text-right">실지급액</th>
                                <th class="text-center">승인상태</th>
                                <th>승인일시</th>
                                <th>작성일자</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statements as $statement)
                            <tr class="clickable-row" data-href="{{ route('mypage.salary-statements.show', $statement->id) }}" style="cursor: pointer;">
                                <td>{{ $statement->statement_date->format('Y-m') }}</td>
                                <td class="text-right">{{ number_format($statement->total_payment) }}원</td>
                                <td class="text-right">{{ number_format($statement->total_deduction) }}원</td>
                                <td class="text-right">{{ number_format($statement->net_payment) }}원</td>
                                <td class="text-center">
                                    @if($statement->approved_at)
                                        <span class="badge bg-success">승인완료</span>
                                    @else
                                        <span class="badge bg-warning">승인대기</span>
                                    @endif
                                </td>
                                <td>{{ $statement->approved_at ? $statement->approved_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>{{ $statement->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">등록된 급여명세서가 없습니다.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($statements->hasPages())
                    <div class="card-footer clearfix">
                        {{ $statements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* 통계 영역 스타일 */
.info-box {
    min-height: 80px;
    background: #fff;
    width: 100%;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.info-box-content {
    display: flex;
    flex-direction: column;
    padding: 5px 10px;
}

.info-box-text {
    display: block;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #6c757d;
}

.info-box-number {
    display: block;
    font-weight: 700;
    font-size: 1.25rem;
    color: #000;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-blink {
    animation: blink 1s infinite;
    background-color: #fff3cd;
    border: 2px solid #ffc107;
}

/* 급여명세서 목록 테이블 스타일 */
.clickable-row:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.text-right {
    text-align: right;
}

.badge {
    font-size: 0.875rem;
    padding: 0.25em 0.6em;
}

.bg-success {
    background-color: #28a745 !important;
    color: white;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: black;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 행 클릭 시 상세 페이지로 이동
    $('.clickable-row').on('click', function(e) {
        window.location = $(this).data('href');
    });
    
    // 분기 선택 드롭다운 변경 이벤트
    $('#quarterSelector').on('change', function() {
        const value = $(this).val();
        if (value) {
            const [year, quarter] = value.split('-');
            window.location.href = `{{ route('mypage.salary-statements.index') }}?year=${year}&quarter=${quarter}`;
        }
    });
});
</script>
@endpush