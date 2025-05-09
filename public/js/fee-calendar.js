/**
 * 수임료 캘린더 JavaScript
 */
// 공통 변수를 window 객체에 할당
window.calendarData = {}; // API에서 가져온 데이터
window.filterConsultant = '';
window.filterManager = '';
window.filterClientName = '';

document.addEventListener('DOMContentLoaded', function() {
    // 전역 변수
    let currentView = 'weekly'; // 'monthly', 'weekly', 'daily', 'overdue'
    let currentDate = moment().startOf('day');
    let selectedDate = moment().startOf('day');
    
    // 연체 데이터 관련 변수
    let overdueCurrentPage = 1;
    let overdueIsLoading = false;
    let overdueHasMorePages = false;
    
    // 초기 데이터 로드
    loadUserData(); // 사용자 정보 먼저 로드하여 필터 기본값 설정
    
    // 이벤트 리스너 등록
    document.getElementById('viewMonthly').addEventListener('click', () => setView('monthly'));
    document.getElementById('viewWeekly').addEventListener('click', () => setView('weekly'));
    document.getElementById('viewDaily').addEventListener('click', () => setView('daily'));
    document.getElementById('viewOverdue').addEventListener('click', () => setView('overdue'));
    document.getElementById('prevBtn').addEventListener('click', goToPrevious);
    document.getElementById('nextBtn').addEventListener('click', goToNext);
    document.getElementById('todayBtn').addEventListener('click', goToToday);
    
    // 필터 이벤트 리스너 등록
    document.getElementById('consultantFilter').addEventListener('change', applyFilters);
    document.getElementById('managerFilter').addEventListener('change', applyFilters);
    document.getElementById('searchFilterBtn').addEventListener('click', applyFilters);
    
    // 엔터키 이벤트 처리 (고객명 필터에서 엔터키 누를 경우 검색 실행)
    document.getElementById('clientNameFilter').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
        }
    });
    
    // 스크롤 이벤트 리스너 등록
    window.addEventListener('scroll', handleScroll);
    
    document.getElementById('calendarContainer').addEventListener('click', function(e) {
        // 날짜 셀 클릭 이벤트 처리
        const dayCell = e.target.closest('.calendar-day');
        if (dayCell) {
            const date = dayCell.getAttribute('data-date');
            if (date) {
                selectedDate = moment(date);
                highlightSelectedDate();
                loadDailyDetailsGlobal(date);
            }
        }
    });
    
    // 날짜 클릭 및 상세 정보 로드를 위한 글로벌 함수 정의
    window.loadDailyDetailsGlobal = function(date) {
        loadDailyDetails(date);
    };
    
    /**
     * 로그인한 사용자 정보 로드 및 필터 기본값 설정
     */
    function loadUserData() {
        // API를 통해 사용자 정보 가져오기
        fetch('/fee-calendar/user-data', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('사용자 정보 로드 완료:', data);
            
            if (data.success) {
                // 필터 기본값 설정 - window 객체에 할당된 변수 사용
                window.filterConsultant = data.defaultConsultant;
                window.filterManager = data.defaultManager;
                
                // 멤버 목록 로드 (필터 드롭다운 채우기)
                loadMembers(data);
            } else {
                // 오류 시 기본 멤버 로드
                loadMembers();
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            // 오류 시 기본 멤버 로드
            loadMembers();
        });
    }
    
    /**
     * 멤버 목록 로드 (필터 드롭다운 채우기)
     */
    function loadMembers(userData = null) {
        fetch('/fee-calendar/members', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const consultantSelect = document.getElementById('consultantFilter');
                const managerSelect = document.getElementById('managerFilter');
                
                // 기존 옵션 초기화 (첫 번째 옵션은 유지)
                while (consultantSelect.options.length > 1) {
                    consultantSelect.remove(1);
                }
                while (managerSelect.options.length > 1) {
                    managerSelect.remove(1);
                }
                
                // 새 옵션 추가
                data.members.forEach(member => {
                    const consultantOption = document.createElement('option');
                    consultantOption.value = member.name;
                    consultantOption.textContent = member.name;
                    consultantSelect.appendChild(consultantOption);
                    
                    const managerOption = document.createElement('option');
                    managerOption.value = member.name;
                    managerOption.textContent = member.name;
                    managerSelect.appendChild(managerOption);
                });
                
                // 사용자 데이터에 따라 기본값 설정
                if (userData) {
                    if (userData.defaultConsultant) {
                        consultantSelect.value = userData.defaultConsultant;
                    }
                    if (userData.defaultManager) {
                        managerSelect.value = userData.defaultManager;
                    }
                }
                
                // 캘린더 데이터 로드
                loadCalendarData();
            }
        })
        .catch(error => {
            console.error('Error loading members:', error);
            // 오류 발생해도 캘린더 데이터는 로드
            loadCalendarData();
        });
    }
    
    /**
     * 필터 적용 함수
     */
    function applyFilters() {
        // 필터 값 업데이트 - window 객체에 할당된 변수 사용
        window.filterConsultant = document.getElementById('consultantFilter').value;
        window.filterManager = document.getElementById('managerFilter').value;
        window.filterClientName = document.getElementById('clientNameFilter').value;
        
        // 연체 탭의 경우 페이지 초기화
        if (currentView === 'overdue') {
            overdueCurrentPage = 1;
            overdueIsLoading = false;
            overdueHasMorePages = false;
        }
        
        // 데이터 다시 로드
        loadCalendarData();
    }
    
    /**
     * 스크롤 이벤트 처리
     */
    function handleScroll() {
        if (currentView !== 'overdue' || overdueIsLoading || !overdueHasMorePages) {
            return;
        }
        
        const scrollHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const clientHeight = window.innerHeight || document.documentElement.clientHeight;
        
        // 페이지 하단에 도달했는지 확인 (하단 200px 전에 로드 시작)
        if (scrollTop + clientHeight >= scrollHeight - 200) {
            loadMoreOverdueData();
        }
    }
    
    /**
     * 뷰 모드 설정
     */
    function setView(view) {
        currentView = view;
        
        // 연체 뷰로 변경 시 페이지 번호 초기화
        if (view === 'overdue') {
            overdueCurrentPage = 1;
            overdueIsLoading = false;
            overdueHasMorePages = false;
        }
        
        // 버튼 상태 업데이트
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        });
        
        document.getElementById(`view${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.remove('btn-outline-primary');
        document.getElementById(`view${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.add('btn-primary', 'active');
        
        // 네비게이션 버튼 상태 업데이트 (연체 탭에서는 사용 안함)
        const navButtons = document.querySelectorAll('#prevBtn, #nextBtn, #todayBtn');
        if (view === 'overdue') {
            navButtons.forEach(btn => btn.style.display = 'none');
        } else {
            navButtons.forEach(btn => btn.style.display = 'inline-block');
        }
        
        // 뷰 모드 변경 시 오늘 날짜로 기본 선택
        // 오늘 날짜가 현재 보이는 기간에 있지 않으면 현재 날짜를 오늘이 있는 기간으로 변경
        const today = moment().startOf('day');
        
        if (view === 'daily') {
            // 일간 뷰는 currentDate와 동기화
            selectedDate = moment(currentDate);
        } else if (view === 'weekly' || view === 'monthly') {
            // 주간/월간 뷰는 오늘 날짜로 선택
            selectedDate = today;
            
            // 현재 표시 중인 기간에 오늘이 없으면 currentDate를 오늘이 있는 기간으로 변경
            if (view === 'weekly') {
                const weekStart = moment(currentDate).startOf('week');
                const weekEnd = moment(currentDate).endOf('week');
                if (!today.isBetween(weekStart, weekEnd, 'day', '[]')) {
                    currentDate = today;
                }
            } else if (view === 'monthly') {
                const monthStart = moment(currentDate).startOf('month');
                const monthEnd = moment(currentDate).endOf('month');
                if (!today.isBetween(monthStart, monthEnd, 'day', '[]')) {
                    currentDate = today;
                }
            }
        }
        
        loadCalendarData();
    }
    
    /**
     * 이전 기간으로 이동
     */
    function goToPrevious() {
        if (currentView === 'overdue') {
            return; // 연체 뷰에서는 기간 이동 없음
        }
        
        if (currentView === 'monthly') {
            currentDate = moment(currentDate).subtract(1, 'month');
            // 월간 뷰에서는 해당 월의 1일로 selectedDate 설정
            selectedDate = moment(currentDate).startOf('month');
        } else if (currentView === 'weekly') {
            currentDate = moment(currentDate).subtract(1, 'week');
            // 주간 뷰에서는 해당 주의 첫째 날로 selectedDate 설정
            selectedDate = moment(currentDate).startOf('week');
        } else {
            currentDate = moment(currentDate).subtract(1, 'day');
            selectedDate = moment(currentDate);
        }
        
        loadCalendarData();
    }
    
    /**
     * 다음 기간으로 이동
     */
    function goToNext() {
        if (currentView === 'overdue') {
            return; // 연체 뷰에서는 기간 이동 없음
        }
        
        if (currentView === 'monthly') {
            currentDate = moment(currentDate).add(1, 'month');
            // 월간 뷰에서는 해당 월의 1일로 selectedDate 설정
            selectedDate = moment(currentDate).startOf('month');
        } else if (currentView === 'weekly') {
            currentDate = moment(currentDate).add(1, 'week');
            // 주간 뷰에서는 해당 주의 첫째 날로 selectedDate 설정
            selectedDate = moment(currentDate).startOf('week');
        } else {
            currentDate = moment(currentDate).add(1, 'day');
            selectedDate = moment(currentDate);
        }
        
        loadCalendarData();
    }
    
    /**
     * 오늘로 이동
     */
    function goToToday() {
        if (currentView === 'overdue') {
            return; // 연체 뷰에서는 기간 이동 없음
        }
        
        currentDate = moment().startOf('day');
        selectedDate = moment().startOf('day');
        
        loadCalendarData();
    }
    
    /**
     * 캘린더 데이터 로드
     */
    function loadCalendarData() {
        let url = '';
        let params = {};
        
        if (currentView === 'monthly') {
            url = '/fee-calendar/monthly';
            params = {
                year: currentDate.year(),
                month: currentDate.month() + 1
            };
        } else if (currentView === 'weekly') {
            url = '/fee-calendar/weekly';
            const startDate = moment(currentDate).startOf('week').format('YYYY-MM-DD');
            const endDate = moment(currentDate).endOf('week').format('YYYY-MM-DD');
            params = {
                start_date: startDate,
                end_date: endDate
            };
        } else if (currentView === 'daily') {
            url = '/fee-calendar/daily';
            params = {
                date: currentDate.format('YYYY-MM-DD')
            };
        } else if (currentView === 'overdue') {
            url = '/fee-calendar/overdue';
            params = {
                page: overdueCurrentPage,
                per_page: 30
            }; 
        }
        
        // 필터 추가 - window 객체에서 가져온 값 사용
        if (window.filterConsultant) {
            params.consultant = window.filterConsultant;
        }
        if (window.filterManager) {
            params.manager = window.filterManager;
        }
        if (window.filterClientName) {
            params.client_name = window.filterClientName;
        }
        
        // 로딩 표시
        if (currentView === 'overdue' && overdueCurrentPage > 1) {
            // 추가 페이지 로딩 시에는 로딩 인디케이터만 표시
            const loadingIndicator = document.getElementById('overdue-loading-indicator');
            if (!loadingIndicator) {
                const container = document.getElementById('detailsContainer');
                const loadingHtml = `
                    <div id="overdue-loading-indicator" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">로딩 중...</span>
                        </div>
                        <span class="ms-2">추가 데이터 로딩 중...</span>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', loadingHtml);
            }
        } else {
            document.getElementById('calendarContainer').innerHTML = `
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                    <p class="mt-2">캘린더 로딩 중...</p>
                </div>
            `;
        }
        
        // 연체 뷰에서는 로딩 상태 업데이트
        if (currentView === 'overdue') {
            overdueIsLoading = true;
        }
        
        // URL에 쿼리 파라미터 추가
        const queryString = Object.keys(params)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
            
        console.log('API 호출:', `${url}?${queryString}`); // 디버깅 로그
        
        fetch(`${url}?${queryString}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
            .then(response => {
                console.log('API 응답 상태:', response.status); // 디버깅 로그
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API 응답 데이터:', data); // 디버깅 로그
                if (!data) {
                    throw new Error('No data received from server');
                }
                
                // 연체 뷰에서 추가 페이지 로딩인 경우
                if (currentView === 'overdue' && overdueCurrentPage > 1) {
                    // 기존 데이터와 병합
                    mergeOverdueData(data);
                } else {
                    // 첫 페이지 또는 다른 뷰인 경우 데이터 교체
                    window.calendarData = data;
                }
                
                if (currentView === 'overdue') {
                    renderOverdueView();
                    
                    // 페이지네이션 정보 업데이트
                    if (data.pagination) {
                        overdueHasMorePages = data.pagination.has_more_pages;
                    }
                    
                    // 로딩 상태 업데이트
                    overdueIsLoading = false;
                    
                    // 로딩 인디케이터 제거
                    const loadingIndicator = document.getElementById('overdue-loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }
                } else {
                    renderCalendar();
                }
                
                renderStatistics();
                
                // 선택된 날짜의 상세 정보 로드
                if (currentView === 'daily') {
                    loadDailyDetails(currentDate.format('YYYY-MM-DD'));
                } else if (currentView === 'overdue') {
                    renderOverdueDetails();
                } else {
                    const dateStr = selectedDate.format('YYYY-MM-DD');
                    loadDailyDetails(dateStr);
                }
                
                // 날짜 범위 표시 업데이트
                updateDateRangeDisplay();
            })
            .catch(error => {
                console.error('Error loading calendar data:', error);
                
                // 로딩 상태 리셋
                if (currentView === 'overdue') {
                    overdueIsLoading = false;
                    
                    // 로딩 인디케이터 제거
                    const loadingIndicator = document.getElementById('overdue-loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }
                }
                
                // 첫 페이지 로딩에서 오류 발생 시에만 에러 메시지 표시
                if (currentView !== 'overdue' || overdueCurrentPage === 1) {
                    document.getElementById('calendarContainer').innerHTML = `
                        <div class="alert alert-danger">
                            데이터 로드 중 오류가 발생했습니다.<br>
                            ${error.message}
                        </div>
                    `;
                }
            });
    }
    
    /**
     * 연체 데이터 병합
     */
    function mergeOverdueData(newData) {
        // 기존 statistics 정보는 유지 (전체 통계는 이미 첫 페이지에서 가져옴)
        
        // fee_details 데이터 병합
        if (newData.fee_details) {
            Object.keys(newData.fee_details).forEach(date => {
                if (window.calendarData.fee_details[date]) {
                    // 해당 날짜의 데이터가 이미 있는 경우 항목 추가
                    window.calendarData.fee_details[date].push(...newData.fee_details[date]);
                } else {
                    // 해당 날짜의 데이터가 없는 경우 새로 생성
                    window.calendarData.fee_details[date] = newData.fee_details[date];
                }
            });
        }
        
        // 페이지네이션 정보 업데이트
        if (newData.pagination) {
            window.calendarData.pagination = newData.pagination;
        }
    }
    
    /**
     * 추가 연체 데이터 로드
     */
    function loadMoreOverdueData() {
        if (overdueIsLoading || !overdueHasMorePages) {
            return;
        }
        
        // 다음 페이지 로드
        overdueCurrentPage++;
        loadCalendarData();
    }
    
    /**
     * 캘린더 렌더링
     */
    function renderCalendar() {
        const container = document.getElementById('calendarContainer');
        
        if (currentView === 'monthly') {
            renderMonthlyCalendar(container);
        } else if (currentView === 'weekly') {
            renderWeeklyCalendar(container);
        } else {
            renderDailyView(container);
        }
        
        // 선택된 날짜 강조 표시
        highlightSelectedDate();
    }
    
    /**
     * 연체 뷰 렌더링 - 캘린더 대신 간단한 메시지 표시
     */
    function renderOverdueView() {
        const container = document.getElementById('calendarContainer');
        
        container.innerHTML = `
            <div class="text-center py-5">
                <p class="text-muted">연체 데이터 조회 모드입니다. 아래에서 연체된 수임료 목록을 확인하세요.</p>
            </div>
        `;
    }
    
    /**
     * 연체 데이터 상세 정보 렌더링
     */
    function renderOverdueDetails() {
        const container = document.getElementById('detailsContainer');
        
        // 연체 데이터 건수 확인
        let totalOverdueItems = 0;
        
        if (window.calendarData && window.calendarData.fee_details) {
            Object.values(window.calendarData.fee_details).forEach(items => {
                totalOverdueItems += items.length;
            });
        }
        
        if (totalOverdueItems === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <p class="text-muted">연체된 수임료가 없습니다.</p>
                </div>
            `;
            return;
        }
        
        // 첫 페이지일 경우에만 HTML 초기화, 아니면 추가
        if (overdueCurrentPage === 1) {
            // 연체 데이터가 있으면 표시
            let html = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">${moment().format('YYYY년 MM월 DD일')} 기준 수임료 연체 정보</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllAsCompleted('overdue')">
                        일괄 납부처리
                    </button>
                </div>
                <div class="row" id="overdue-cards-container">
            `;
            
            // 날짜별로 반복
            Object.keys(window.calendarData.fee_details).sort((a, b) => moment(b).diff(moment(a))).forEach(date => {
                const items = window.calendarData.fee_details[date];
                
                // 각 항목마다 카드 생성
                items.forEach(item => {
                    // 화폐 단위 포맷
                    const formattedAmount = formatCurrency(item.amount);
                    
                    // 상태에 따른 스타일 설정 - 항상 미납 상태
                    const stateBadgeClass = 'badge-status-pending';
                    const buttonClass = 'btn-outline-primary';
                    
                    // 고객 카드 HTML 생성
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">사건 ${item.case_idx}</h5>
                                        <span class="badge ${stateBadgeClass}">미납</span>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <div class="mb-2">정보 로드 중...</div>
                                            <div class="mb-2">
                                                ${item.fee_type === 1 ? '착수금' : 
                                                  item.fee_type === 2 ? '분할납부' : 
                                                  item.fee_type === 3 ? '성공보수' : '기타'}
                                            </div>
                                            <div class="mb-2 fw-bold">${formattedAmount}원</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-2">납부예정일: ${item.scheduled_date}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm ${buttonClass}" 
                                            onclick="markAsCompleted(${item.id}, '미납')">
                                            납부처리
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            });
            
            html += `</div>`;
            
            // 무한 스크롤 로딩 인디케이터 추가
            if (overdueHasMorePages) {
                html += `
                    <div id="overdue-loading-indicator" class="text-center py-3" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">로딩 중...</span>
                        </div>
                        <span class="ms-2">추가 데이터 로딩 중...</span>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        } else {
            // 기존 컨테이너에 추가
            const cardsContainer = document.getElementById('overdue-cards-container');
            if (!cardsContainer) return;
            
            let newCardsHtml = '';
            
            // 날짜별로 반복
            Object.keys(window.calendarData.fee_details).sort((a, b) => moment(b).diff(moment(a))).forEach(date => {
                // 이미 표시된 항목을 제외한 새 항목만 찾기
                const existingCardBtns = document.querySelectorAll(`button[onclick^="markAsCompleted("]`);
                const existingIds = Array.from(existingCardBtns).map(btn => {
                    const match = btn.getAttribute('onclick').match(/markAsCompleted\((\d+)/);
                    return match ? parseInt(match[1]) : null;
                }).filter(id => id !== null);
                
                const items = window.calendarData.fee_details[date].filter(item => !existingIds.includes(item.id));
                
                // 각 항목마다 카드 생성
                items.forEach(item => {
                    // 화폐 단위 포맷
                    const formattedAmount = formatCurrency(item.amount);
                    
                    // 상태에 따른 스타일 설정 - 항상 미납 상태
                    const stateBadgeClass = 'badge-status-pending';
                    const buttonClass = 'btn-outline-primary';
                    
                    // 고객 카드 HTML 생성
                    newCardsHtml += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="card-title mb-0">사건 ${item.case_idx}</h5>
                                        <span class="badge ${stateBadgeClass}">미납</span>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <div class="mb-2">정보 로드 중...</div>
                                            <div class="mb-2">
                                                ${item.fee_type === 1 ? '착수금' : 
                                                  item.fee_type === 2 ? '분할납부' : 
                                                  item.fee_type === 3 ? '성공보수' : '기타'}
                                            </div>
                                            <div class="mb-2 fw-bold">${formattedAmount}원</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-2">납부예정일: ${item.scheduled_date}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm ${buttonClass}" 
                                            onclick="markAsCompleted(${item.id}, '미납')">
                                            납부처리
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            });
            
            // 새 카드 추가
            cardsContainer.insertAdjacentHTML('beforeend', newCardsHtml);
        }
        
        // 실제 상세 정보 API 호출
        fetchActualOverdueDetails();
    }
    
    /**
     * 연체 데이터의 실제 상세 정보 가져오기
     */
    function fetchActualOverdueDetails() {
        // 연체 데이터의 모든 날짜 추출
        const dates = Object.keys(window.calendarData.fee_details);
        
        if (dates.length === 0) {
            return;
        }
        
        // 이미 상세 정보가 로드된 항목 ID 추출
        const existingDetailCardBtns = document.querySelectorAll(`.card-title`);
        const existingDetailCardIds = Array.from(existingDetailCardBtns)
            .filter(el => !el.textContent.includes('사건 ')) // 이미 상세 정보가 로드된 카드 (제목이 '사건 #' 형식이 아닌 경우)
            .map(el => {
                const card = el.closest('.card');
                if (!card) return null;
                const btn = card.querySelector('button[onclick^="markAsCompleted("]');
                if (!btn) return null;
                const match = btn.getAttribute('onclick').match(/markAsCompleted\((\d+)/);
                return match ? parseInt(match[1]) : null;
            })
            .filter(id => id !== null);
        
        // 필터 파라미터 구성
        const queryParams = [];
        if (window.filterConsultant) {
            queryParams.push(`consultant=${encodeURIComponent(window.filterConsultant)}`);
        }
        if (window.filterManager) {
            queryParams.push(`manager=${encodeURIComponent(window.filterManager)}`);
        }
        if (window.filterClientName) {
            queryParams.push(`client_name=${encodeURIComponent(window.filterClientName)}`);
        }
        
        const queryString = queryParams.length > 0 
            ? `&${queryParams.join('&')}` 
            : '';
        
        // 각 날짜별로 상세 정보 API 호출
        dates.forEach(date => {
            // 해당 날짜의 항목 중 아직 상세 정보가 로드되지 않은 항목 찾기
            const itemsToLoad = window.calendarData.fee_details[date].filter(item => 
                !existingDetailCardIds.includes(item.id)
            );
            
            if (itemsToLoad.length === 0) {
                return; // 해당 날짜에 로드할 항목이 없으면 건너뜀
            }
            
            fetch(`/fee-calendar/daily-details?date=${date}${queryString}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // 가져온 상세 정보로 연체 카드 업데이트
                updateOverdueCards(date, data);
            })
            .catch(error => {
                console.error(`Error loading details for ${date}:`, error);
            });
        });
    }
    
    /**
     * 연체 카드 업데이트
     */
    function updateOverdueCards(date, detailsData) {
        // 해당 날짜의 연체 항목 ID 가져오기
        const overdueItems = window.calendarData.fee_details[date];
        if (!overdueItems) return;
        
        // API 응답 구조 확인 및 처리
        const detailItems = detailsData.fee_details || detailsData;
        
        // detailItems가 배열이 아닌 경우 처리
        if (!Array.isArray(detailItems)) {
            console.error(`Invalid details data format for ${date}:`, detailItems);
            return;
        }
        
        // 각 항목 ID에 해당하는 상세 정보 매핑
        overdueItems.forEach(item => {
            const detailInfo = detailItems.find(detail => detail.id === item.id);
            if (!detailInfo) return;
            
            // 해당 ID를 가진 카드 찾기
            const cardButton = document.querySelector(`button[onclick="markAsCompleted(${item.id}, '미납')"]`);
            if (!cardButton) return;
            
            const card = cardButton.closest('.card');
            if (!card) return;
            
            // 이미 업데이트된 카드인지 확인 (이미 고객명이 설정된 경우 업데이트 완료된 것으로 간주)
            const titleElem = card.querySelector('.card-title');
            const isAlreadyUpdated = titleElem && !titleElem.textContent.includes('사건 ');
            
            // 카드 내용 업데이트
            if (titleElem && detailInfo.client_name) {
                titleElem.innerHTML = `${detailInfo.client_name} <small class="text-muted">${detailInfo.client_phone || '-'}</small>`;
            }
            
            const caseTypeElem = card.querySelector('.col-6:first-child .mb-2:first-child');
            if (caseTypeElem && detailInfo.case_type) {
                caseTypeElem.innerHTML = `${detailInfo.case_type} <span class="badge ${detailInfo.case_state_class || 'badge-state-default'} ms-1">${detailInfo.case_state || '미지정'}</span>`;
            }
            
            const feeTypeElem = card.querySelector('.col-6:first-child .mb-2:nth-child(2)');
            if (feeTypeElem && detailInfo.fee_type) {
                feeTypeElem.textContent = detailInfo.fee_type;
            }
            
            const rightCol = card.querySelector('.col-6:last-child');
            if (rightCol) {
                // 기존 내용 대신 새로운 내용으로 구성
                let newContent = '';
                
                // 납부예정일 정보 (항상 포함)
                if (item.scheduled_date) {
                    newContent += `<div class="mb-2">납부예정일: ${item.scheduled_date}</div>`;
                }
                
                // 담당자 정보 추가
                if (detailInfo.consultant && detailInfo.consultant !== '미지정') {
                    newContent += `<div class="mb-2">${detailInfo.consultant}</div>`;
                }
                
                if (detailInfo.case_manager && detailInfo.case_manager !== '미지정') {
                    newContent += `<div class="mb-2">${detailInfo.case_manager}</div>`;
                }
                
                // 내용 교체
                rightCol.innerHTML = newContent;
            }
        });
    }
    
    /**
     * 캘린더 렌더링
     */
    function renderMonthlyCalendar(container) {
        const firstDayOfMonth = moment(currentDate).startOf('month');
        const lastDayOfMonth = moment(currentDate).endOf('month');
        const firstDayOfCalendar = moment(firstDayOfMonth).startOf('week');
        const lastDayOfCalendar = moment(lastDayOfMonth).endOf('week');
        
        let html = `
            <div class="monthly-calendar">
                <div class="row mb-2 fw-bold text-center">
                    <div class="col">일</div>
                    <div class="col">월</div>
                    <div class="col">화</div>
                    <div class="col">수</div>
                    <div class="col">목</div>
                    <div class="col">금</div>
                    <div class="col">토</div>
                </div>
        `;
        
        let currentDay = moment(firstDayOfCalendar);
        
        while (currentDay <= lastDayOfCalendar) {
            html += '<div class="row mb-2">';
            
            for (let i = 0; i < 7; i++) {
                const dateStr = currentDay.format('YYYY-MM-DD');
                const isCurrentMonth = currentDay.month() === currentDate.month();
                const isToday = currentDay.isSame(moment(), 'day');
                const count = (window.calendarData.daily_count && window.calendarData.daily_count[dateStr]) || 0;
                
                html += `
                    <div class="col p-1">
                        <div class="calendar-day ${!isCurrentMonth ? 'text-muted' : ''} ${isToday ? 'today' : ''}"
                             data-date="${dateStr}">
                            <div class="d-flex justify-content-between align-items-center p-1">
                                <span class="day-number ${isToday ? 'fw-bold text-primary' : ''}">
                                    ${currentDay.date()}
                                </span>
                                ${(count > 0 && isCurrentMonth) ? `<span class="badge bg-primary rounded-pill">${count}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                currentDay.add(1, 'day');
            }
            
            html += '</div>';
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * 주간 캘린더 렌더링 - HTML 직접 생성
     */
    function renderWeeklyCalendar(container) {
        const weekStart = moment(currentDate).startOf('week');
        let html = `
            <div class="weekly-calendar">
                <div class="row mb-2 fw-bold text-center">
                    <div class="col">일</div>
                    <div class="col">월</div>
                    <div class="col">화</div>
                    <div class="col">수</div>
                    <div class="col">목</div>
                    <div class="col">금</div>
                    <div class="col">토</div>
                </div>
                <div class="row">
        `;
        
        for (let i = 0; i < 7; i++) {
            const day = moment(weekStart).add(i, 'days');
            const dateStr = day.format('YYYY-MM-DD');
            const isToday = day.isSame(moment(), 'day');
            const count = (window.calendarData.daily_count && window.calendarData.daily_count[dateStr]) || 0;
            
            html += `
                <div class="col">
                    <div class="calendar-day day-col ${isToday ? 'today' : ''}"
                         data-date="${dateStr}">
                        <div class="text-center p-2">
                            <div class="day-name mb-1">${day.format('ddd')}</div>
                            <div class="day-number ${isToday ? 'fw-bold text-primary' : ''}">
                                ${day.date()}
                            </div>
                            ${count > 0 ? `
                                <div class="mt-1">
                                    <span class="badge bg-primary rounded-pill">${count}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }
        
        html += `</div></div>`;
        container.innerHTML = html;
    }
    
    /**
     * 일간 뷰 렌더링 - HTML 직접 생성
     */
    function renderDailyView(container) {
        const dateStr = currentDate.format('YYYY-MM-DD');
        const count = (window.calendarData.daily_count && window.calendarData.daily_count[dateStr]) || 0;
        
        container.innerHTML = `
            <div class="daily-view">
                <div class="p-3 bg-light rounded">
                    <h5 class="mb-1">${currentDate.format('YYYY년 M월 D일 (ddd)')}</h5>
                    <p class="mb-0">납부 예정: ${count}건</p>
                </div>
            </div>
        `;
        
        // 일간 뷰에서는 selectedDate를 currentDate와 동기화
        selectedDate = moment(currentDate);
    }
    
    /**
     * 통계 렌더링 - HTML 직접 생성
     */
    function renderStatistics() {
        const stats = window.calendarData.statistics || {
            total: { 
                count: 0, 
                amount: 0,
                lawyer_fee: { count: 0, amount: 0 },
                other_fee: { count: 0, amount: 0 }
            },
            completed: { 
                count: 0, 
                amount: 0,
                lawyer_fee: { count: 0, amount: 0 },
                other_fee: { count: 0, amount: 0 }
            },
            pending: { 
                count: 0, 
                amount: 0,
                lawyer_fee: { count: 0, amount: 0 },
                other_fee: { count: 0, amount: 0 }
            },
            overdue: { 
                count: 0, 
                amount: 0,
                lawyer_fee: { count: 0, amount: 0 },
                other_fee: { count: 0, amount: 0 }
            }
        };
        
        document.getElementById('statisticsContainer').innerHTML = `
            <div class="col-md-3">
                <div class="card bg-primary-subtle border-primary-subtle h-100">
                    <div class="card-body">
                        <h6 class="text-muted">전체</h6>
                        <h4 class="fw-bold text-primary-emphasis">${stats.total.count}건</h4>
                        <div class="mb-1 text-primary-emphasis">₩${numberWithCommas(stats.total.amount)}</div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">수임료</div>
                                <div class="small text-primary-emphasis">${stats.total.lawyer_fee.count}건</div>
                                <div class="small text-primary-emphasis">₩${numberWithCommas(stats.total.lawyer_fee.amount)}</div>
                            </div>
                            <div>
                                <div class="small text-muted">부대비용</div>
                                <div class="small text-primary-emphasis">${stats.total.other_fee.count}건</div>
                                <div class="small text-primary-emphasis">₩${numberWithCommas(stats.total.other_fee.amount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success-subtle border-success-subtle h-100">
                    <div class="card-body">
                        <h6 class="text-muted">완납</h6>
                        <h4 class="fw-bold text-success-emphasis">${stats.completed.count}건</h4>
                        <div class="mb-1 text-success-emphasis">₩${numberWithCommas(stats.completed.amount)}</div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">수임료</div>
                                <div class="small text-success-emphasis">${stats.completed.lawyer_fee.count}건</div>
                                <div class="small text-success-emphasis">₩${numberWithCommas(stats.completed.lawyer_fee.amount)}</div>
                            </div>
                            <div>
                                <div class="small text-muted">부대비용</div>
                                <div class="small text-success-emphasis">${stats.completed.other_fee.count}건</div>
                                <div class="small text-success-emphasis">₩${numberWithCommas(stats.completed.other_fee.amount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning-subtle border-warning-subtle h-100">
                    <div class="card-body">
                        <h6 class="text-muted">미납</h6>
                        <h4 class="fw-bold text-warning-emphasis">${stats.pending.count}건</h4>
                        <div class="mb-1 text-warning-emphasis">₩${numberWithCommas(stats.pending.amount)}</div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">수임료</div>
                                <div class="small text-warning-emphasis">${stats.pending.lawyer_fee.count}건</div>
                                <div class="small text-warning-emphasis">₩${numberWithCommas(stats.pending.lawyer_fee.amount)}</div>
                            </div>
                            <div>
                                <div class="small text-muted">부대비용</div>
                                <div class="small text-warning-emphasis">${stats.pending.other_fee.count}건</div>
                                <div class="small text-warning-emphasis">₩${numberWithCommas(stats.pending.other_fee.amount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger-subtle border-danger-subtle h-100">
                    <div class="card-body">
                        <h6 class="text-muted">연체</h6>
                        <h4 class="fw-bold text-danger-emphasis">${stats.overdue.count}건</h4>
                        <div class="mb-1 text-danger-emphasis">₩${numberWithCommas(stats.overdue.amount)}</div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="small text-muted">수임료</div>
                                <div class="small text-danger-emphasis">${stats.overdue.lawyer_fee.count}건</div>
                                <div class="small text-danger-emphasis">₩${numberWithCommas(stats.overdue.lawyer_fee.amount)}</div>
                            </div>
                            <div>
                                <div class="small text-muted">부대비용</div>
                                <div class="small text-danger-emphasis">${stats.overdue.other_fee.count}건</div>
                                <div class="small text-danger-emphasis">₩${numberWithCommas(stats.overdue.other_fee.amount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * 선택된 날짜 강조 표시
     */
    function highlightSelectedDate() {
        // 모든 날짜 셀의 강조 표시 제거
        document.querySelectorAll('.calendar-day').forEach(cell => {
            cell.classList.remove('selected', 'bg-primary', 'text-white', 'border-primary', 'border-3');
        });
        
        // 선택된 날짜 셀 강조 표시 - 배경색 대신 테두리 사용
        const selectedDateStr = selectedDate.format('YYYY-MM-DD');
        const selectedCells = document.querySelectorAll(`.calendar-day[data-date="${selectedDateStr}"]`);
        selectedCells.forEach(cell => {
            cell.classList.add('selected', 'border-primary', 'border-3');
        });
    }
    
    /**
     * 현재 날짜 범위 표시 업데이트
     */
    function updateDateRangeDisplay() {
        const rangeDisplay = document.getElementById('currentDateRange');
        
        if (currentView === 'monthly') {
            rangeDisplay.textContent = `${currentDate.year()}년 ${currentDate.month() + 1}월`;
        } else if (currentView === 'weekly') {
            const weekStart = moment(currentDate).startOf('week');
            const weekEnd = moment(currentDate).endOf('week');
            
            if (weekStart.month() === weekEnd.month()) {
                rangeDisplay.textContent = `${weekStart.year()}년 ${weekStart.month() + 1}월 ${weekStart.date()}일 - ${weekEnd.date()}일`;
            } else {
                rangeDisplay.textContent = `${weekStart.year()}년 ${weekStart.month() + 1}월 ${weekStart.date()}일 - ${weekEnd.month() + 1}월 ${weekEnd.date()}일`;
            }
        } else if (currentView === 'daily') {
            rangeDisplay.textContent = `${currentDate.year()}년 ${currentDate.month() + 1}월 ${currentDate.date()}일`;
        } else if (currentView === 'overdue') {
            rangeDisplay.textContent = `연체 데이터`;
        }
    }
    
    /**
     * 천단위 구분 기호 추가
     */
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    /**
     * 화폐 형식으로 포맷
     */
    function formatCurrency(amount) {
        return amount ? numberWithCommas(amount) : '0';
    }
    
    function getStateClass(state) {
        if (state === 1 || state === '1' || state === 'completed') {
            return 'state-completed';
        }
        return 'state-pending';
    }

    function getStateText(state) {
        if (state === 1 || state === '1' || state === 'completed') {
            return '완납';
        }
        return '미납';
    }

    /**
     * 상세 정보 카드 렌더링
     */
    function renderDetailCards(items, date) {
        const dateTitle = moment(date).format('YYYY년 MM월 DD일');
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">${dateTitle} 수임료 상세 정보</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAllAsCompleted('${date}')">
                    일괄 납부처리
                </button>
            </div>
            <div class="row">
        `;
        
        items.forEach(item => {
            // 화폐 단위 포맷
            const formattedAmount = formatCurrency(item.amount);
            
            // 상태에 따른 스타일 설정
            const stateClass = item.state === '완납' ? 'text-success' : 'text-danger';
            const buttonDisabled = ''; // 비활성화 제거
            const stateBadgeClass = item.state === '완납' ? 'badge-status-completed' : 'badge-status-pending';
            const buttonText = item.state === '완납' ? '미납처리' : '납부처리';
            const buttonClass = item.state === '완납' ? 'btn-outline-warning' : 'btn-outline-primary';
            
            // 계약 상태 확인 - 기본값은 '정상'
            const isTerminated = item.contract_status === '계약해지';
            
            // 카드 배경색 스타일 - 계약해지 > 완납 순으로 우선순위
            let cardClass = '';
            if (isTerminated) {
                cardClass = 'bg-soft-danger border-danger';
            } else if (item.state === '완납') {
                cardClass = 'bg-soft-success border-success';
            }
            
            // 컨설턴트 및 담당자 정보
            const consultantInfo = item.consultant && item.consultant !== '미지정' ? `<div class="mb-2">${item.consultant}</div>` : '';
            const managerInfo = item.case_manager && item.case_manager !== '미지정' ? `<div class="mb-2">${item.case_manager}</div>` : '';
            
            // 완납일 및 입금유형 정보 (완납 상태일 때만 표시)
            const settlementInfo = item.state === '완납' ? `<div class="mb-2">완납일: ${item.settlement_date || '정보 없음'}</div>` : '';
            const paymentMethodInfo = item.state === '완납' && item.payment_method ? `<div class="mb-2 text-secondary">${item.payment_method}</div>` : '';
            
            // 납부예정일 정보 추가 (미납 상태일 때만 표시)
            const scheduledDateInfo = item.state !== '완납' && item.scheduled_date ? `<div class="mb-2">납부예정일: ${item.scheduled_date}</div>` : '';
            
            // 고객 카드 HTML 생성
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card h-100 shadow-sm ${cardClass}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">${item.client_name} <small class="text-muted">${item.client_phone || '-'}</small></h5>
                                <span class="badge ${stateBadgeClass}">${item.state}</span>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-6">
                                    <div class="mb-2">${item.case_type} <span class="badge ${item.case_state_class || 'badge-state-default'} ms-1">${item.case_state || '미지정'}</span></div>
                                    <div class="mb-2">${item.fee_type}</div>
                                    <div class="mb-2 fw-bold">${formattedAmount}원</div>
                                </div>
                                <div class="col-6">
                                    ${consultantInfo}
                                    ${managerInfo}
                                    ${scheduledDateInfo}
                                    ${settlementInfo}
                                    ${paymentMethodInfo}
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="button" class="btn btn-sm ${buttonClass}" 
                                    onclick="markAsCompleted(${item.id}, '${item.state}')" ${buttonDisabled}>
                                    ${buttonText}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
        `;
        
        document.getElementById('detailsContainer').innerHTML = html;
    }

    /**
     * 선택된 날짜의 상세 정보 로드 및 렌더링
     */
    function loadDailyDetails(date) {
        if (!date) {
            return;
        }
        
        // 로딩 표시
        const detailsContainer = document.getElementById('detailsContainer');
        detailsContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">로딩 중...</span>
                </div>
                <p class="mt-2">상세 정보 로딩 중...</p>
            </div>
        `;
        
        // 필터 파라미터 구성
        const queryParams = new URLSearchParams();
        queryParams.append('date', date);
        
        // 필터 값 추가 - window 객체에서 가져온 값 사용
        if (window.filterConsultant) {
            queryParams.append('consultant', window.filterConsultant);
        }
        if (window.filterManager) {
            queryParams.append('manager', window.filterManager);
        }
        if (window.filterClientName) {
            queryParams.append('client_name', window.filterClientName);
        }
        
        // API 호출
        fetch(`/fee-calendar/daily-details?${queryParams.toString()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // 선택된 날짜에 데이터가 없는 경우 처리
            if (!window.calendarData.fee_details || !window.calendarData.fee_details[date]) {
                detailsContainer.innerHTML = `
                    <div class="text-center py-5">
                        <p class="text-muted">선택하신 날짜(${moment(date).format('YYYY년 MM월 DD일')})에 예정된 수임료가 없습니다.</p>
                    </div>
                `;
                return;
            }
            
            // 기본 데이터 - 상세 API 호출 실패 시 기본 정보라도 표시
            const basicData = window.calendarData.fee_details[date].map(item => {
                return {
                    id: item.id,
                    case_idx: item.case_idx,
                    client_name: `사건 ${item.case_idx}`,
                    case_type: '정보 로드 중...',
                    fee_type: item.fee_type === 1 ? '착수금' : 
                              item.fee_type === 2 ? '분할납부' : 
                              item.fee_type === 3 ? '성공보수' : '기타',
                    amount: item.amount,
                    consultant: '',
                    case_manager: '',
                    state: item.state === 1 || item.state === '1' ? '완납' : '미납',
                    settlement_date: item.settlement_date,
                    scheduled_date: item.scheduled_date
                };
            });
            
            // 일단 기본 데이터로 화면 표시
            renderDetailCards(basicData, date);
            
            if (data.error) {
                console.error('Error fetching daily details:', data.error);
                return;
            }
            
            // 세부 데이터로 화면 업데이트
            renderDetailCards(data.fee_details, date);
        })
        .catch(error => {
            console.error('Error fetching daily details:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    상세 정보를 불러오는 중 오류가 발생했습니다.
                </div>
            `;
        });
    }
});

/**
 * 사건 상세 페이지로 이동
 */
function viewCaseDetails(caseIdx) {
    window.location.href = `/cases/${caseIdx}`;
}

/**
 * 납부 처리 기능
 */
function markAsCompleted(id, currentState) {
    // 상태에 따라 다른 API 호출
    const isCompleted = currentState === '완납';
    
    // 이미 완납 상태인 경우 - 미납으로 변경하는 로직 (기존 유지)
    if (isCompleted) {
        if (!confirm('선택한 수임료를 미납으로 변경하시겠습니까?\n연결된 입금내역 정보와 Sub 데이터도 함께 삭제됩니다.')) {
            return;
        }
        
        const apiUrl = `/fee-calendar/mark-as-pending/${id}`;
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 성공 메시지 표시
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    미납처리가 완료되었습니다.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.getElementById('detailsContainer').prepend(alertDiv);
                
                // 1.5초 후 페이지 새로고침
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                // 에러 메시지 표시
                showErrorAlert(data.message || '미납처리 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // 에러 메시지 표시
            showErrorAlert(error.message);
        });
        
        return;
    }
    
    // 미납 상태일 경우 모달 창으로 입금 데이터 검색 및 선택
    showPaymentMatchModal(id);
}

/**
 * 입금 매칭 모달 표시
 */
function showPaymentMatchModal(feeDetailId) {
    // 기존 모달 제거
    const existingModal = document.getElementById('paymentMatchModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // 모달 HTML 생성
    const modalHtml = `
        <div class="modal fade" id="paymentMatchModal" tabindex="-1" aria-labelledby="paymentMatchModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentMatchModalLabel">입금내역 매칭</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- 필터 영역 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="periodFilter" class="form-label">기간 필터</label>
                                <select id="periodFilter" class="form-select">
                                    <option value="7" selected>최근 7일</option>
                                    <option value="14">최근 14일</option>
                                    <option value="30">최근 1개월</option>
                                    <option value="90">최근 3개월</option>
                                    <option value="180">최근 6개월</option>
                                    <option value="365">최근 1년</option>
                                    <option value="0">제한없음</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="amountFilter" class="form-label">금액 필터</label>
                                <select id="amountFilter" class="form-select">
                                    <option value="exact" selected>금액 일치</option>
                                    <option value="plus10">10% 가산</option>
                                    <option value="unlimited">제한없음</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="nameFilter" class="form-label">고객명 필터</label>
                                <select id="nameFilter" class="form-select">
                                    <option value="match" selected>일치</option>
                                    <option value="unmatch">불일치</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- 결과 테이블 -->
                        <div id="paymentResults" class="table-responsive">
                            <div class="text-center p-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">로딩 중...</span>
                                </div>
                                <p class="mt-2">입금내역을 검색 중입니다...</p>
                            </div>
                        </div>
                        
                        <!-- 페이지네이션 컨테이너 -->
                        <div id="paymentPagination" class="d-flex justify-content-center mt-3">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="confirmMatchBtn" disabled>확인</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 모달 추가 및 표시
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('paymentMatchModal'));
    modal.show();
    
    // 모달이 완전히 표시된 후 기본 필터값으로 조회 실행
    document.getElementById('paymentMatchModal').addEventListener('shown.bs.modal', function() {
        searchMatchingPayments(feeDetailId, 1);
    });
    
    // 필터 변경 시 자동 조회
    document.getElementById('periodFilter').addEventListener('change', function() {
        searchMatchingPayments(feeDetailId, 1);
    });
    
    document.getElementById('amountFilter').addEventListener('change', function() {
        searchMatchingPayments(feeDetailId, 1);
    });
    
    document.getElementById('nameFilter').addEventListener('change', function() {
        searchMatchingPayments(feeDetailId, 1);
    });
    
    // 확인 버튼 이벤트 리스너
    document.getElementById('confirmMatchBtn').addEventListener('click', function() {
        const selectedPayment = document.querySelector('input[name="paymentRadio"]:checked');
        if (selectedPayment) {
            processPaymentMatch(feeDetailId, selectedPayment.value);
            modal.hide();
        }
    });
}

/**
 * 매칭되는 입금내역 검색
 */
function searchMatchingPayments(feeDetailId, page = 1) {
    const periodFilter = document.getElementById('periodFilter').value;
    const amountFilter = document.getElementById('amountFilter').value;
    const nameFilter = document.getElementById('nameFilter').value;
    const itemsPerPage = 15; // 페이지당 표시할 항목 수
    
    const resultsContainer = document.getElementById('paymentResults');
    const paginationContainer = document.getElementById('paymentPagination');
    
    // 로딩 표시
    resultsContainer.innerHTML = `
        <div class="text-center p-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <p class="mt-2">입금내역을 검색 중입니다...</p>
        </div>
    `;
    
    // 페이지네이션 초기화
    paginationContainer.innerHTML = '';
    
    // API 호출
    fetch(`/fee-calendar/search-matching-payments/${feeDetailId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            period_filter: periodFilter,
            amount_filter: amountFilter,
            name_filter: nameFilter
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // 확인 버튼 비활성화
        document.getElementById('confirmMatchBtn').disabled = true;
        
        if (!data.payments || data.payments.length === 0) {
            resultsContainer.innerHTML = `
                <div class="alert alert-warning">
                    조건에 맞는 입금내역이 없습니다. 필터 조건을 변경하여 다시 시도해보세요.
                </div>
            `;
            return;
        }
        
        // 페이지네이션 적용을 위한 데이터 준비
        const allPayments = data.payments;
        const totalItems = allPayments.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        // 현재 페이지 데이터만 필터링
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const currentPageData = allPayments.slice(startIndex, endIndex);
        
        // 테이블 생성
        let tableHtml = `
            <table class="table table-striped table-hover">
                <thead>
                    <tr class="text-center">
                        <th>선택</th>
                        <th>입금일</th>
                        <th>입금유형</th>
                        <th>입금자명</th>
                        <th>금액</th>
                        <th>계정</th>
                        <th>담당자</th>
                        <th>메모</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        currentPageData.forEach((payment, index) => {
            const globalIndex = startIndex + index;
            tableHtml += `
                <tr class="text-center">
                    <td>
                        <input class="form-check-input" type="radio" name="paymentRadio" 
                            id="paymentRadio${globalIndex}" value="${payment.id}|${payment.table_name}|${payment.type}"
                            onchange="enableConfirmButton()">
                    </td>
                    <td>${payment.date}</td>
                    <td>${payment.type}</td>
                    <td>${payment.client_name}</td>
                    <td>${Number(payment.amount).toLocaleString()}원</td>
                    <td>${payment.account || ''}</td>
                    <td>${payment.manager || ''}</td>
                    <td>${payment.memo || '-'}</td>
                </tr>
            `;
        });
        
        tableHtml += `
                </tbody>
            </table>
        `;
        
        resultsContainer.innerHTML = tableHtml;
        
        // 페이지네이션 렌더링
        if (totalPages > 1) {
            renderPagination(totalPages, page, totalItems, feeDetailId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsContainer.innerHTML = `
            <div class="alert alert-danger">
                입금내역 조회 중 오류가 발생했습니다: ${error.message}
            </div>
        `;
    });
}

/**
 * 페이지네이션 렌더링
 */
function renderPagination(totalPages, currentPage, totalItems, feeDetailId) {
    const paginationContainer = document.getElementById('paymentPagination');
    const maxPageButtons = 5; // 표시할 최대 페이지 버튼 수
    
    let paginationHtml = `
        <nav aria-label="입금내역 페이지네이션">
            <ul class="pagination">
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="searchMatchingPayments(${feeDetailId}, 1); return false;" aria-label="처음">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="searchMatchingPayments(${feeDetailId}, ${currentPage - 1}); return false;" aria-label="이전">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                </li>
    `;
    
    // 페이지 버튼 범위 계산
    let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxPageButtons - 1);
    
    // 시작 페이지 조정
    if (endPage - startPage + 1 < maxPageButtons) {
        startPage = Math.max(1, endPage - maxPageButtons + 1);
    }
    
    // 페이지 버튼 생성
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="searchMatchingPayments(${feeDetailId}, ${i}); return false;">${i}</a>
            </li>
        `;
    }
    
    paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="searchMatchingPayments(${feeDetailId}, ${currentPage + 1}); return false;" aria-label="다음">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </li>
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="searchMatchingPayments(${feeDetailId}, ${totalPages}); return false;" aria-label="마지막">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="text-center mt-2 text-muted small">
            총 ${totalItems}개 중 ${(currentPage - 1) * 15 + 1}~${Math.min(currentPage * 15, totalItems)}개 표시
        </div>
    `;
    
    paginationContainer.innerHTML = paginationHtml;
}

/**
 * 라디오 버튼 선택 시 확인 버튼 활성화
 */
function enableConfirmButton() {
    document.getElementById('confirmMatchBtn').disabled = false;
}

/**
 * 선택한 입금내역으로 납부처리
 */
function processPaymentMatch(feeDetailId, paymentInfo) {
    // paymentInfo = "id|table_name|type" 형식
    const [paymentId, tableName, paymentType] = paymentInfo.split('|');
    
    // 로딩 표시
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'alert alert-info';
    loadingDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <div>납부 처리 중입니다. 잠시만 기다려주세요...</div>
        </div>
    `;
    document.getElementById('detailsContainer').prepend(loadingDiv);
    
    // API 호출
    fetch(`/fee-calendar/process-payment-match`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            fee_detail_id: feeDetailId,
            payment_id: paymentId,
            table_name: tableName,
            payment_type: paymentType
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // 로딩 표시 제거
        loadingDiv.remove();
        
        if (data.success) {
            // 성공 메시지 표시
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                납부 완료 처리되었습니다.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.getElementById('detailsContainer').prepend(alertDiv);
            
            // 카드 상태 즉시 업데이트 (페이지 새로고침 없이)
            updateCardStatus(feeDetailId, data.payment_method);
            
            // 통계 데이터도 업데이트가 필요하므로 1.5초 후 페이지 새로고침
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showErrorAlert(data.message || '처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // 에러 메시지 표시
        showErrorAlert(error.message);
    });
}

/**
 * 카드 상태 즉시 업데이트
 */
function updateCardStatus(feeDetailId, paymentMethod) {
    // 해당 feeDetailId를 가진 카드 찾기
    const cardContainer = document.querySelector(`button[onclick="markAsCompleted(${feeDetailId}, '미납')"]`).closest('.card');
    if (!cardContainer) return;
    
    // 카드 배경색 변경
    cardContainer.classList.add('bg-soft-success', 'border-success');
    
    // 상태 배지 업데이트
    const stateBadge = cardContainer.querySelector('.badge');
    if (stateBadge) {
        stateBadge.className = 'badge badge-status-completed';
        stateBadge.textContent = '완납';
    }
    
    // 버튼 텍스트 및 스타일 변경
    const actionButton = cardContainer.querySelector('button');
    if (actionButton) {
        actionButton.textContent = '미납처리';
        actionButton.className = 'btn btn-sm btn-outline-warning';
        actionButton.setAttribute('onclick', `markAsCompleted(${feeDetailId}, '완납')`);
    }
    
    // 완납일 추가 및 납부예정일 제거
    const rightCol = cardContainer.querySelector('.col-6:last-child');
    if (rightCol) {
        // 기존 내용 유지
        let existingContent = rightCol.innerHTML;
        
        // 납부예정일 정보 삭제 (정규식을 사용하여 납부예정일 행을 찾아 제거)
        existingContent = existingContent.replace(/<div class="mb-2">납부예정일: .*?<\/div>/, '');
        
        // 완납일 및 입금방식 추가 - 실제 거래 날짜 정보는 백엔드 응답에서 오지 않으므로 UI 업데이트 시점에서는 표시하지 않음
        // 페이지 새로고침 시 API에서 가져온 실제 납부일자가 표시됨
        rightCol.innerHTML = existingContent + 
            (paymentMethod ? `<div class="mb-2 text-secondary">${paymentMethod}</div>` : '');
    }
}

/**
 * 에러 알림 표시
 */
function showErrorAlert(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        처리 중 오류가 발생했습니다: ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.getElementById('detailsContainer').prepend(alertDiv);
}

/**
 * 일괄 납부 처리 기능
 */
function markAllAsCompleted(date) {
    if (!confirm('선택한 날짜의 미납 수임료 중 입금내역과 매칭되는 항목만 자동으로 납부완료 처리하시겠습니까?')) {
        return;
    }
    
    // 로딩 표시
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'alert alert-info';
    loadingDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <div>일괄 납부 처리 중입니다. 잠시만 기다려주세요...</div>
        </div>
    `;
    document.getElementById('detailsContainer').prepend(loadingDiv);
    
    // 백엔드 API 호출하여 일괄 처리 - window 객체의 필터 변수 사용
    fetch('/fee-calendar/batch-mark-as-completed', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ 
            date: date,
            is_overdue: date === 'overdue' ? true : false,  // 연체 모드 파라미터 추가
            consultant: window.filterConsultant,  // window 객체의 필터 변수 사용
            manager: window.filterManager,        // window 객체의 필터 변수 사용
            client_name: window.filterClientName  // window 객체의 필터 변수 사용
        }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // 로딩 표시 제거
        loadingDiv.remove();
        
        if (!data.success) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${data.message || '처리 중 오류가 발생했습니다.'}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.getElementById('detailsContainer').prepend(alertDiv);
            return;
        }
        
        if (data.processed_count === 0) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = `
                매칭되는 입금내역이 없어 처리된 항목이 없습니다.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.getElementById('detailsContainer').prepend(alertDiv);
            return;
        }
        
        // 성공 알림 표시
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            일괄 납부처리가 성공적으로 완료되었습니다. 총 ${data.processed_count}건이 처리되었습니다.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.getElementById('detailsContainer').prepend(alertDiv);
        
        // 성공 모달 표시
        showProcessedItemsModal(data.processed_items);
    })
    .catch(error => {
        console.error('Error in batch processing:', error);
        
        // 로딩 표시 제거
        loadingDiv.remove();
        
        // 에러 메시지 표시
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            일괄 처리 중 오류가 발생했습니다: ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.getElementById('detailsContainer').prepend(alertDiv);
    });
}

/**
 * 처리된 항목 모달 표시
 */
function showProcessedItemsModal(items) {
    // 이미 있는 모달 제거
    const existingModal = document.getElementById('processedItemsModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // 모달 HTML
    const modalHtml = `
        <div class="modal fade" id="processedItemsModal" tabindex="-1" aria-labelledby="processedItemsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="processedItemsModalLabel">일괄 납부처리 결과</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>총 ${items.length}건의 수임료가 납부 완료 처리되었습니다.</p>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>입금일자</th>
                                        <th>입금유형</th>
                                        <th>고객명</th>
                                        <th>금액</th>
                                        <th>처리내용</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(item => `
                                        <tr>
                                            <td>${item.date}</td>
                                            <td>${item.type}</td>
                                            <td>${item.client_name}</td>
                                            <td class="text-end">${Number(item.amount).toLocaleString()}원</td>
                                            <td>${item.manager}의 ${item.account_type}로 처리</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="closeModalBtn">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 모달 추가 및 표시
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('processedItemsModal'));
    modal.show();
    
    // 닫기 버튼 클릭 시 페이지 새로고침
    document.getElementById('closeModalBtn').addEventListener('click', function() {
        modal.hide();
        window.location.reload();
    });
    
    // 모달 닫힘 이벤트에 페이지 새로고침 추가 (X 버튼 클릭 등)
    document.getElementById('processedItemsModal').addEventListener('hidden.bs.modal', function() {
        window.location.reload();
    });
}

// 전역에서 호출되는 코드를 위한 래퍼 함수
function loadDailyDetails(date) {
    // 클로저 내부의 loadDailyDetailsGlobal 함수를 호출
    window.loadDailyDetailsGlobal(date);
}