@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/file-download.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">PDF 파일 다운로드</h5>
                    <div class="d-flex align-items-center">
                        <div class="filter-area d-flex me-3">
                            <div class="me-2">
                                <select id="handlerFilter" class="form-select form-select-sm">
                                    <option value="">-- 담당자 전체 --</option>
                                </select>
                            </div>
                            <div class="me-2 d-flex">
                                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="파일명 입력">
                                <button id="searchBtn" class="btn btn-sm btn-primary ms-1">검색</button>
                            </div>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewMode-month">월간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary active" id="viewMode-week">주간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewMode-day">일간</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="viewMode-all">전체</button>
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
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-1" style="width:10px;height:10px;"></span>
                                <small>파일 있음</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 캘린더 영역 -->
                    <div class="card mb-4">
                        <div class="card-body p-3" id="calendarContainer">
                            <div id="loading" class="text-center p-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">로딩 중...</span>
                                </div>
                                <p class="mt-2">캘린더 로딩 중...</p>
                            </div>
                            
                            <!-- 월간 달력 -->
                            <div id="monthCalendar" class="calendar-container">
                                <!-- 월간 달력은 자바스크립트로 동적 생성 -->
                            </div>
                            
                            <!-- 주간 달력 -->
                            <div id="weekCalendar" class="calendar-container" style="display: none;">
                                <!-- 주간 달력은 자바스크립트로 동적 생성 -->
                            </div>
                            
                            <!-- 일간 달력 -->
                            <div id="dayCalendar" class="calendar-container" style="display: none;">
                                <!-- 일간 달력은 자바스크립트로 동적 생성 -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- 통계 요약 영역 -->
                    <div class="row mb-4" id="statisticsContainer">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-muted mb-1">총 파일 수</h6>
                                            <h4 class="mb-0" id="totalCount">0개</h4>
                                        </div>
                                        <div>
                                            <i class="bi bi-file-earmark-text fs-1 text-primary opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 상세 정보 영역 -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3" id="viewTitle">이번 달 문서</h5>
                            
                            <!-- 파일 카드 그리드 -->
                            <div class="row" id="fileCardContainer">
                                <!-- 파일 카드가 여기에 동적으로 추가됩니다 -->
                            </div>
                            
                            <!-- 로딩 표시 -->
                            <div class="text-center py-4 my-3" id="loadingIndicator" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">로딩 중...</span>
                                </div>
                                <p class="mt-2">파일을 불러오는 중...</p>
                            </div>
                            
                            <!-- 데이터 없음 표시 -->
                            <div class="text-center py-5 my-3 bg-light rounded" id="noDataMessage" style="display: none;">
                                <p class="text-muted mb-0">해당 기간에 수신된 문서가 없습니다</p>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 상태 변수
        let currentViewMode = 'week'; // 'month', 'week', 'day', 'all'
        let currentDate = new Date();
        let currentPage = 1;
        let totalFiles = 0;
        let hasMoreFiles = false;
        let isLoading = false;
        let searchTerm = '';
        let handlerFilter = '';
        let handlers = new Set(); // 중복 없는 담당자 목록
        let calendarData = {}; // 날짜별 파일 개수를 저장하는 객체
        let selectedDate = null; // 선택된 날짜
        
        // DOM 요소
        const fileCardContainer = document.getElementById('fileCardContainer');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const noDataMessage = document.getElementById('noDataMessage');
        const loading = document.getElementById('loading');
        const currentDateRange = document.getElementById('currentDateRange');
        const viewTitle = document.getElementById('viewTitle');
        const totalCount = document.getElementById('totalCount');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const handlerFilterSelect = document.getElementById('handlerFilter');
        const weekCalendar = document.getElementById('weekCalendar');
        const monthCalendar = document.getElementById('monthCalendar');
        
        // 일간 달력 컨테이너 추가
        const dayCalendarContainer = document.createElement('div');
        dayCalendarContainer.id = 'dayCalendar';
        dayCalendarContainer.className = 'calendar-container';
        dayCalendarContainer.style.display = 'none';
        document.getElementById('calendarContainer').appendChild(dayCalendarContainer);
        
        // 담당자 목록 로드
        function loadHandlers() {
            fetch('{{ route('file-download.handlers') }}')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // 기존 옵션 제거 (첫 번째 옵션 '전체'만 유지)
                        while (handlerFilterSelect.options.length > 0) {
                            handlerFilterSelect.remove(0);
                        }
                        
                        // '전체' 옵션 추가
                        const allOption = new Option('-- 담당자 전체 --', '');
                        handlerFilterSelect.add(allOption);
                        
                        // 담당자 옵션 추가
                        data.handlers.forEach(handler => {
                            const option = new Option(handler, handler);
                            handlerFilterSelect.add(option);
                        });
                        
                        // 기본값 설정
                        if (data.defaultHandler) {
                            handlerFilterSelect.value = data.defaultHandler;
                            handlerFilter = data.defaultHandler;
                        }
                        
                        // 캘린더 데이터와 파일 목록 로드
                        loadCalendarData();
                        resetAndFetchFiles();
                    }
                })
                .catch(error => {
                    console.error('Error loading handlers:', error);
                });
        }
        
        // 날짜 포맷 함수
        function formatDate(date, format = 'YYYY-MM-DD') {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            
            if (format === 'YYYY-MM-DD') {
                return `${year}-${month}-${day}`;
            } else if (format === 'YYYY년 MM월 DD일') {
                return `${year}년 ${month}월 ${day}일`;
            } else if (format === 'MM월 DD일') {
                return `${month}월 ${day}일`;
            } else if (format === 'YYYY년 MM월') {
                return `${year}년 ${month}월`;
            }
        }
        
        // 보기 모드 변경 시 UI 업데이트
        function updateViewModeUI() {
            // 버튼 활성화 상태 변경
            document.querySelectorAll('[id^="viewMode-"]').forEach(btn => {
                const mode = btn.id.split('-')[1];
                if (mode === currentViewMode) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // 달력 표시/숨김 처리
            const calendarRow = document.querySelector('.card.mb-4');
            if (currentViewMode === 'all') {
                calendarRow.style.display = 'none';
            } else {
                calendarRow.style.display = '';
                
                // 월간/주간/일간 달력 표시 전환
                if (currentViewMode === 'month') {
                    weekCalendar.style.display = 'none';
                    monthCalendar.style.display = 'block';
                    dayCalendarContainer.style.display = 'none';
                    renderMonthCalendar();
                } else if (currentViewMode === 'week') {
                    weekCalendar.style.display = 'block';
                    monthCalendar.style.display = 'none';
                    dayCalendarContainer.style.display = 'none';
                    renderWeekCalendar();
                } else if (currentViewMode === 'day') {
                    weekCalendar.style.display = 'none';
                    monthCalendar.style.display = 'none';
                    dayCalendarContainer.style.display = 'block';
                    renderDayCalendar();
                }
            }
            
            // 제목 업데이트
            if (currentViewMode === 'month') {
                viewTitle.textContent = '이번 달 문서';
            } else if (currentViewMode === 'week') {
                viewTitle.textContent = '이번 주 문서';
            } else if (currentViewMode === 'day') {
                viewTitle.textContent = '오늘 문서';
            } else {
                viewTitle.textContent = '전체 문서';
            }
            
            // 기간 표시 업데이트
            updatePeriodDisplay();
        }
        
        // 기간 표시 업데이트
        function updatePeriodDisplay() {
            if (currentViewMode === 'month') {
                currentDateRange.textContent = formatDate(currentDate, 'YYYY년 MM월');
            } else if (currentViewMode === 'week') {
                const startOfWeek = new Date(currentDate);
                startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
                
                const endOfWeek = new Date(startOfWeek);
                endOfWeek.setDate(startOfWeek.getDate() + 6);
                
                if (startOfWeek.getMonth() === endOfWeek.getMonth()) {
                    const displayText = `${startOfWeek.getFullYear()}년 ${startOfWeek.getMonth() + 1}월 ${startOfWeek.getDate()}일 - ${endOfWeek.getDate()}일`;
                    currentDateRange.textContent = displayText;
                } else {
                    const displayText = `${startOfWeek.getFullYear()}년 ${startOfWeek.getMonth() + 1}월 ${startOfWeek.getDate()}일 - ${endOfWeek.getMonth() + 1}월 ${endOfWeek.getDate()}일`;
                    currentDateRange.textContent = displayText;
                }
            } else if (currentViewMode === 'day') {
                const displayText = formatDate(currentDate, 'YYYY년 MM월 DD일');
                currentDateRange.textContent = displayText;
            } else {
                currentDateRange.textContent = '';
            }
        }
        
        // 월간 달력 렌더링
        function renderMonthCalendar() {
            loading.style.display = 'block';
            
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // 해당 월의 첫 날짜와 마지막 날짜
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            // 월간 달력 영역 생성
            let calendarHTML = '<div class="calendar-days-container">';
            const dayNames = ['일', '월', '화', '수', '목', '금', '토'];
            
            // 요일 헤더 추가
            calendarHTML += '<div class="calendar-days-header">';
            dayNames.forEach(day => {
                calendarHTML += `<div class="calendar-day-name">${day}</div>`;
            });
            calendarHTML += '</div>';
            
            calendarHTML += '<div class="calendar-days">';
            
            // 월 시작 전 빈 칸 수
            const firstDayOfWeek = firstDay.getDay();
            
            // 이전 달 날짜 추가
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = 0; i < firstDayOfWeek; i++) {
                const dayNumber = prevMonthLastDay - firstDayOfWeek + i + 1;
                const dayDate = new Date(year, month - 1, dayNumber);
                const dateStr = formatDate(dayDate);
                const fileCount = calendarData[dateStr] || 0;
                
                calendarHTML += `
                    <div class="calendar-day disabled" data-date="${dateStr}">
                        <div class="calendar-day-content">
                            <span class="day-number">${dayNumber}</span>
                            ${fileCount > 0 ? `<span class="badge bg-secondary">${fileCount}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            // 현재 달 날짜 추가
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const dayDate = new Date(year, month, i);
                const dateStr = formatDate(dayDate);
                const isToday = isSameDay(dayDate, new Date());
                const isSelected = selectedDate ? isSameDay(dayDate, new Date(selectedDate)) : false;
                const fileCount = calendarData[dateStr] || 0;
                
                calendarHTML += `
                    <div class="calendar-day ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''}" data-date="${dateStr}">
                        <div class="calendar-day-content">
                            <span class="day-number">${i}</span>
                            ${fileCount > 0 ? `<span class="badge bg-primary">${fileCount}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            // 다음 달 날짜 추가 (6주 채우기)
            const totalDaysDisplayed = firstDayOfWeek + lastDay.getDate();
            const remainingCells = 42 - totalDaysDisplayed; // 7x6 그리드 기준
            
            for (let i = 1; i <= remainingCells; i++) {
                const dayDate = new Date(year, month + 1, i);
                const dateStr = formatDate(dayDate);
                const fileCount = calendarData[dateStr] || 0;
                
                calendarHTML += `
                    <div class="calendar-day disabled" data-date="${dateStr}">
                        <div class="calendar-day-content">
                            <span class="day-number">${i}</span>
                            ${fileCount > 0 ? `<span class="badge bg-secondary">${fileCount}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            calendarHTML += '</div></div>';
            monthCalendar.innerHTML = calendarHTML;
            loading.style.display = 'none';
            
            // 이벤트 리스너 추가
            monthCalendar.querySelectorAll('.calendar-day:not(.disabled)').forEach(day => {
                day.addEventListener('click', function() {
                    // 이전 선택 항목 제거
                    monthCalendar.querySelectorAll('.calendar-day.selected').forEach(el => {
                        el.classList.remove('selected');
                    });
                    
                    // 새로운 선택 항목 표시
                    this.classList.add('selected');
                    
                    // 날짜 변경 및 데이터 새로고침
                    selectedDate = this.dataset.date;
                    currentDate = new Date(selectedDate);
                    currentPage = 1;
                    
                    // 일간 모드로 전환
                    currentViewMode = 'day';
                    updateViewModeUI();
                    
                    // 파일 목록 새로고침
                    resetAndFetchFiles();
                });
            });
        }
        
        // 주간 달력 렌더링
        function renderWeekCalendar() {
            loading.style.display = 'block';
            
            const startOfWeek = new Date(currentDate);
            startOfWeek.setDate(currentDate.getDate() - currentDate.getDay()); // 일요일부터 시작
            
            // 주간 달력 영역 생성
            let calendarHTML = '<div class="calendar-days-container">';
            const dayNames = ['일', '월', '화', '수', '목', '금', '토'];
            
            // 요일 헤더 추가
            calendarHTML += '<div class="calendar-days-header">';
            dayNames.forEach(day => {
                calendarHTML += `<div class="calendar-day-name">${day}</div>`;
            });
            calendarHTML += '</div>';
            
            calendarHTML += '<div class="calendar-days">';
            
            // 주간 날짜 추가
            for (let i = 0; i < 7; i++) {
                const day = new Date(startOfWeek);
                day.setDate(startOfWeek.getDate() + i);
                const dateStr = formatDate(day);
                
                const isToday = isSameDay(day, new Date());
                const isSelected = selectedDate ? isSameDay(day, new Date(selectedDate)) : false;
                const fileCount = calendarData[dateStr] || 0;
                
                calendarHTML += `
                    <div class="calendar-day ${isToday ? 'today' : ''} ${isSelected ? 'selected' : ''}" data-date="${dateStr}">
                        <div class="calendar-day-content">
                            <span class="day-number">${day.getDate()}</span>
                            ${fileCount > 0 ? `<span class="badge bg-primary">${fileCount}</span>` : ''}
                        </div>
                    </div>
                `;
            }
            
            calendarHTML += '</div></div>';
            weekCalendar.innerHTML = calendarHTML;
            loading.style.display = 'none';
            
            // 이벤트 리스너 추가
            weekCalendar.querySelectorAll('.calendar-day').forEach(day => {
                day.addEventListener('click', function() {
                    // 이전 선택 항목 제거
                    weekCalendar.querySelectorAll('.calendar-day.selected').forEach(el => {
                        el.classList.remove('selected');
                    });
                    
                    // 새로운 선택 항목 표시
                    this.classList.add('selected');
                    
                    // 날짜 변경 및 데이터 새로고침
                    selectedDate = this.dataset.date;
                    currentDate = new Date(selectedDate);
                    currentPage = 1;
                    
                    // 일간 모드로 전환
                    currentViewMode = 'day';
                    updateViewModeUI();
                    
                    // 파일 목록 새로고침
                    resetAndFetchFiles();
                });
            });
        }
        
        // 일간 달력 렌더링
        function renderDayCalendar() {
            loading.style.display = 'block';
            
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const day = currentDate.getDate();
            
            // 요일 구하기
            const dayOfWeek = new Date(year, month, day).getDay();
            const dayNames = ['일', '월', '화', '수', '목', '금', '토'];
            const dayName = dayNames[dayOfWeek];
            
            // 일간 달력 영역 생성 - 간소화된 버전
            let calendarHTML = '<div class="text-center p-3">';
            calendarHTML += `<h3 class="text-primary m-0">${year}년 ${month + 1}월 ${day}일 (${dayName})</h3>`;
            calendarHTML += '</div>';
            
            dayCalendarContainer.innerHTML = calendarHTML;
            loading.style.display = 'none';
        }
        
        // 두 날짜가 같은 날인지 확인
        function isSameDay(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        }
        
        // 캘린더 데이터 로드
        function loadCalendarData() {
            loading.style.display = 'block';
            
            // 현재 월 또는 주에 해당하는 날짜 범위 계산
            let startDate, endDate;
            
            if (currentViewMode === 'month') {
                // 월의 첫날과 마지막 날
                startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
                endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                
                // 달력에 표시될 이전 달과 다음 달의 날짜도 포함
                const firstDayOfWeek = startDate.getDay();
                const lastDayOfWeek = endDate.getDay();
                
                startDate.setDate(startDate.getDate() - firstDayOfWeek);
                endDate.setDate(endDate.getDate() + (6 - lastDayOfWeek));
            } else if (currentViewMode === 'week' || currentViewMode === 'day') {
                // 주의 시작일(일요일)과 종료일(토요일)
                startDate = new Date(currentDate);
                startDate.setDate(currentDate.getDate() - currentDate.getDay());
                
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 6);
            } else {
                // 전체 모드일 경우 캘린더 데이터 로드하지 않음
                loading.style.display = 'none';
                return;
            }
            
            // 날짜 형식 변환
            const startDateStr = formatDate(startDate);
            const endDateStr = formatDate(endDate);
            
            // API 요청 파라미터
            const params = new URLSearchParams({
                start_date: startDateStr,
                end_date: endDateStr,
                search: searchTerm,
                handler: handlerFilter
            });
            
            fetch(`{{ route('file-download.calendar-data') }}?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        calendarData = data.data;
                        
                        // 달력 다시 렌더링
                        if (currentViewMode === 'month') {
                            renderMonthCalendar();
                        } else if (currentViewMode === 'week') {
                            renderWeekCalendar();
                        } else if (currentViewMode === 'day') {
                            renderDayCalendar();
                        }
                    } else {
                        console.error('Error fetching calendar data:', data.message);
                    }
                    
                    loading.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error fetching calendar data:', error);
                    loading.style.display = 'none';
                });
        }
        
        // 파일 목록 초기화 후 새로 가져오기
        function resetAndFetchFiles() {
            fileCardContainer.innerHTML = '';
            currentPage = 1;
            hasMoreFiles = true;
            fetchFiles();
        }
        
        // 파일 목록 가져오기
        function fetchFiles() {
            if (isLoading || !hasMoreFiles) return;
            
            isLoading = true;
            loadingIndicator.style.display = 'block';
            noDataMessage.style.display = 'none';
            
            // API 요청 파라미터
            const params = new URLSearchParams({
                view_mode: currentViewMode,
                date: formatDate(currentDate),
                page: currentPage,
                per_page: 30, // 한 번에 30개씩 로드
                search: searchTerm,
                handler: handlerFilter
            });
            
            fetch(`{{ route('file-download.list') }}?${params.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    isLoading = false;
                    loadingIndicator.style.display = 'none';
                    
                    if (data.success) {
                        totalFiles = data.meta.total;
                        hasMoreFiles = data.meta.has_more;
                        
                        // 첫 페이지이면서 데이터가 없는 경우
                        if (currentPage === 1 && data.files.length === 0) {
                            noDataMessage.style.display = 'block';
                        }
                        
                        // 파일 카드 렌더링
                        data.files.forEach(file => {
                            // 담당자 목록에 추가
                            if (file.handler) {
                                handlers.add(file.handler);
                            }
                            
                            // 보정권고 파일 여부 확인
                            const isCorrectionFile = file.filename.includes('보정권고') || 
                                (file.filename.includes('보정명령등본') && !file.filename.includes('주소보정명령등본'));
                            
                            // 파일 카드 생성
                            const cardHtml = `
                                <div class="col-md-4 card-column">
                                    <div class="file-card bg-white ${isCorrectionFile ? 'correction-file' : ''}">
                                        <div class="file-card-header p-3 d-flex align-items-center">
                                            <div>
                                                <div class="text-muted small">수신일: ${file.receiptDate}</div>
                                                <div class="fw-medium">${file.handler || '담당자 없음'}</div>
                                            </div>
                                            <a href="{{ url('file-download') }}/${file.path}" 
                                               class="btn btn-sm btn-outline-primary rounded-circle ms-auto"
                                               target="_blank" title="다운로드">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                        <div class="p-3">
                                            <p class="text-muted small mb-1">사건번호: ${file.caseNumber}</p>
                                            <p class="file-name mb-0" title="${file.filename}">${file.filename}</p>
                                        </div>
                                        <div class="bg-light p-2 small d-flex justify-content-between">
                                            <span>발신일: ${file.sentDate}</span>
                                            <span>${file.size}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            fileCardContainer.insertAdjacentHTML('beforeend', cardHtml);
                        });
                        
                        // 총 파일 수 업데이트
                        totalCount.textContent = `${totalFiles}개`;
                        
                        // 담당자 필터 업데이트
                        updateHandlerFilter();
                        
                        // 다음 페이지 준비
                        currentPage++;
                    } else {
                        console.error('Error fetching files:', data.message);
                        if (currentPage === 1) {
                            noDataMessage.style.display = 'block';
                            noDataMessage.querySelector('p').textContent = '파일 목록을 불러오는데 실패했습니다.';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching files:', error);
                    isLoading = false;
                    loadingIndicator.style.display = 'none';
                    
                    if (currentPage === 1) {
                        noDataMessage.style.display = 'block';
                        noDataMessage.querySelector('p').textContent = '파일 목록을 불러오는데 실패했습니다.';
                    }
                });
        }
        
        // 담당자 필터 옵션 업데이트
        function updateHandlerFilter() {
            // 기존에 이미 로드된 담당자 목록에 새로운 담당자가 있는 경우에만 추가
            const currentHandlers = Array.from(handlerFilterSelect.options).map(option => option.value);
            
            // 담당자 목록 정렬
            const sortedHandlers = Array.from(handlers).sort();
            
            // 새로운 담당자 추가
            sortedHandlers.forEach(handler => {
                if (handler && !currentHandlers.includes(handler)) {
                    const option = new Option(handler, handler);
                    handlerFilterSelect.add(option);
                }
            });
            
            // 담당자 옵션 알파벳 순으로 정렬 (첫 번째 '전체' 옵션 제외)
            const options = Array.from(handlerFilterSelect.options).slice(1);
            options.sort((a, b) => a.text.localeCompare(b.text));
            
            // 기존 옵션 제거 (첫 번째 옵션 제외)
            while (handlerFilterSelect.options.length > 1) {
                handlerFilterSelect.remove(1);
            }
            
            // 정렬된 옵션 추가
            options.forEach(option => {
                handlerFilterSelect.add(option);
            });
        }
        
        // 네비게이션 변경 함수 (이전/다음/오늘)
        function navigateDate(direction) {
            const date = new Date(currentDate);
            
            if (direction === 'prev') {
                if (currentViewMode === 'month') {
                    date.setMonth(date.getMonth() - 1);
                } else if (currentViewMode === 'week') {
                    date.setDate(date.getDate() - 7);
                } else if (currentViewMode === 'day') {
                    date.setDate(date.getDate() - 1);
                }
            } else if (direction === 'next') {
                if (currentViewMode === 'month') {
                    date.setMonth(date.getMonth() + 1);
                } else if (currentViewMode === 'week') {
                    date.setDate(date.getDate() + 7);
                } else if (currentViewMode === 'day') {
                    date.setDate(date.getDate() + 1);
                }
            } else if (direction === 'today') {
                date.setTime(new Date().getTime());
            }
            
            currentDate = date;
            selectedDate = null; // 날짜 선택 초기화
            currentPage = 1;
            
            // 캘린더 데이터 로드
            loadCalendarData();
            
            updateViewModeUI();
            resetAndFetchFiles();
        }
        
        // 스크롤 이벤트 리스너 (무한 스크롤)
        window.addEventListener('scroll', function() {
            if (isLoading || !hasMoreFiles) return;
            
            const scrollHeight = document.documentElement.scrollHeight;
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            const clientHeight = document.documentElement.clientHeight;
            
            // 스크롤이 하단에 가까워지면 추가 데이터 로드
            if (scrollTop + clientHeight + 300 >= scrollHeight) {
                fetchFiles();
            }
        });
        
        // 뷰 모드 변경 이벤트 리스너
        document.querySelectorAll('[id^="viewMode-"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const mode = this.id.split('-')[1];
                
                if (currentViewMode !== mode) {
                    currentViewMode = mode;
                    selectedDate = null; // 날짜 선택 초기화
                    currentPage = 1;
                    
                    // 캘린더 데이터 로드
                    loadCalendarData();
                    
                    updateViewModeUI();
                    resetAndFetchFiles();
                }
            });
        });
        
        // 이전/다음/오늘 버튼 이벤트 리스너
        document.getElementById('prevBtn').addEventListener('click', () => navigateDate('prev'));
        document.getElementById('nextBtn').addEventListener('click', () => navigateDate('next'));
        document.getElementById('todayBtn').addEventListener('click', () => navigateDate('today'));
        
        // 검색 버튼 이벤트 리스너
        searchBtn.addEventListener('click', function() {
            searchTerm = searchInput.value.trim();
            currentPage = 1;
            
            // 캘린더 데이터 로드
            loadCalendarData();
            
            resetAndFetchFiles();
        });
        
        // 검색어 입력 시 엔터키 이벤트 리스너
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTerm = this.value.trim();
                currentPage = 1;
                
                // 캘린더 데이터 로드
                loadCalendarData();
                
                resetAndFetchFiles();
            }
        });
        
        // 담당자 필터 변경 이벤트 리스너
        handlerFilterSelect.addEventListener('change', function() {
            handlerFilter = this.value;
            currentPage = 1;
            
            // 캘린더 데이터 로드
            loadCalendarData();
            
            resetAndFetchFiles();
        });
        
        // 초기화
        loadHandlers();
        updateViewModeUI();
        fetchFiles();
    });
</script>
@endpush 