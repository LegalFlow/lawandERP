@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/fee-calendar.css') }}" rel="stylesheet">
<style>
    #searchFilterBtn {
        min-width: 60px;
        white-space: nowrap;
        padding-left: 8px;
        padding-right: 8px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">수임료 캘린더</h5>
                    <div class="d-flex align-items-center">
                        <div class="filter-area d-flex me-3">
                            <div class="me-2">
                                <select id="consultantFilter" class="form-select form-select-sm">
                                    <option value="">-- 상담자 전체 --</option>
                                </select>
                            </div>
                            <div class="me-2">
                                <select id="managerFilter" class="form-select form-select-sm">
                                    <option value="">-- 담당자 전체 --</option>
                                </select>
                            </div>
                            <div class="me-2 d-flex">
                                <input type="text" id="clientNameFilter" class="form-control form-control-sm" placeholder="고객명 입력">
                                <button id="searchFilterBtn" class="btn btn-sm btn-primary ms-1">검색</button>
                            </div>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewMonthly">월간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary active" id="viewWeekly">주간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewDaily">일간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewOverdue">연체</button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 날짜 네비게이션 영역 -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="prevBtn">
                                <i class="bi bi-chevron-left"></i> 이전
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary mx-2" id="todayBtn">오늘</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="nextBtn">
                                다음 <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        
                        <h6 class="mb-0" id="currentDateRange">{{ date('Y년 m월') }}</h6>
                        
                        <div class="d-flex align-items-center">
                            <div class="d-flex align-items-center me-3">
                                <span class="badge bg-danger me-1" style="width:10px;height:10px;"></span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-1" style="width:10px;height:10px;"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 캘린더 영역 -->
                    <div class="card mb-4">
                        <div class="card-body p-3" id="calendarContainer">
                            <div id="loading" class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">로딩 중...</span>
                                </div>
                                <p class="mt-2">캘린더 로딩 중...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 통계 요약 영역 -->
                    <div class="row mb-4" id="statisticsContainer">
                        <!-- 통계 카드가 여기에 동적으로 삽입됩니다 -->
                    </div>
                    
                    <!-- 상세 정보 영역 -->
                    <div class="card">
                        <div class="card-body" id="detailsContainer">
                            <!-- 선택된 날짜의 상세 정보가 여기에 동적으로 삽입됩니다 -->
                            <div class="text-center py-5">
                                <p class="text-muted">날짜를 선택하면 해당 날짜의 수임료 정보가 여기에 표시됩니다.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSRF 토큰 메타 태그 -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="{{ asset('js/fee-calendar.js') }}"></script>
<script>
// 날짜 클릭 처리를 위한 전역 함수 (fee-calendar.js의 내부 함수를 호출)
function loadDailyDetails(date) {
    // window.loadDailyDetailsGlobal 함수를 통해 fee-calendar.js의 내부 함수 호출
    if (typeof window.loadDailyDetailsGlobal === 'function') {
        window.loadDailyDetailsGlobal(date);
    } else {
        console.error('loadDailyDetailsGlobal 함수를 찾을 수 없습니다.');
    }
}
</script>
@endpush 