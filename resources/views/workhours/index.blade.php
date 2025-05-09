@extends('layouts.app')

@push('styles')
<style>
/* 기본 달력 스타일 재정의 */
.fc {
    background-color: #fff;
    border: 1px solid #ddd;
}

/* 날짜 셀 스타일 */
.fc-daygrid-day {
    height: 120px !important;
}

/* 날짜 숫자 스타일 */
.fc .fc-daygrid-day-number {
    color: #333;
    font-weight: 500;
    font-size: 14px;
    padding: 8px;
    position: relative;
    z-index: 4;
}

/* 주말 날짜 색상 */
.fc-day-sun .fc-daygrid-day-number {
    color: #ff0000;
}

.fc-day-sat .fc-daygrid-day-number {
    color: #0000ff;
}

/* 오늘 날짜 강조 */
.fc-day-today {
    background-color: rgba(255, 220, 40, 0.15) !important;
}

/* 이벤트 스타일 */
.fc-event {
    border: none;
    padding: 2px 4px;
    margin: 1px 0;
    font-size: 12px;
}

/* 헤더 스타일 */
.fc-toolbar-title {
    font-size: 1.2em !important;
    font-weight: bold;
}

.fc-header-toolbar {
    margin-bottom: 1em !important;
    padding: 0.5em;
}

/* 버튼 스타일 */
.fc-button-primary {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.fc-button-primary:hover {
    background-color: #0b5ed7 !important;
    border-color: #0a58ca !important;
}

/* 모바일 환경을 위한 미디어 쿼리 추가 필요 */
@media (max-width: 768px) {
    .fc-toolbar {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 14px;
    }
}

/* FullCalendar 이벤트 z-index 수정 */
.fc-event,
.fc-event-title,
.fc-event-main {
    z-index: 1060 !important;  /* 사이드바(1050)와 네비바(1051)보다 높게 설정 */
    position: relative;
}

/* 이벤트 컨테이너도 함께 수정 */
.fc-daygrid-event-harness {
    z-index: 1060 !important;
    position: relative;
}

/* 스타일 섹션에 추가 */
.schedule-icon {
    transition: transform 0.2s;
    font-size: 0.9rem;
}

.schedule-icon:hover {
    transform: scale(1.2);
}

.member-status-badge {
    font-size: 0.75rem;
    padding: 0.25em 0.6em;
}

/* styles 섹션에 추가 */
.modal-title {
    font-size: 1rem;
}

.list-group-item {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}
</style>

<!-- Font Awesome CDN 추가 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Flatpickr CSS 추가 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Flatpickr 한국어 테마 -->
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/material_blue.css">
@endpush

@push('scripts')
<!-- SweetAlert2 추가 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- FullCalendar 번들 -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<!-- Flatpickr 스크립트 추가 -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/ko.js"></script>

<script>
let calendar; // 전역 변수로 선언

// 상수 정의 추가
const WORK_STATUSES = {
    WORK: '근무',
    REMOTE: '재택',
    OFF: '휴무',
    VACATION: '연차',
    MORNING_HALF: '오전반차',
    AFTERNOON_HALF: '오후반차'
};

// 근무시간 옵션 상수
const WORK_TIME_OPTIONS = {
    EIGHT_TO_FIVE: '8-17',
    NINE_TO_SIX: '9-18',
    TEN_TO_SEVEN: '10-19',
    NINE_TO_FOUR: '9-16'
};

// 시간 형식 변환 함수
function formatTimeString(timeString) {
    if (!timeString) return null;
    
    try {
        // ISO 형식의 시간을 HH:mm 형식으로 변환
        if (timeString.includes('T')) {
            const date = new Date(timeString);
            return date.toTimeString().slice(0, 5);
        }
        return timeString;
    } catch (error) {
        console.error('시간 형식 변환 오류:', error);
        return null;
    }
}

// 캘린더 초기화 함수 추가
function initializeCalendar(calendarEl) {
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        },
        locale: 'ko',
        height: 'auto',
        selectable: true,
        events: function(info, successCallback, failureCallback) {
            const filterType = document.getElementById('filter_type').value;
            const taskType = document.getElementById('task_type').value;
            const myScheduleOnly = document.getElementById('myScheduleOnly').checked;
            
            fetch(`/workhours/stats?start_date=${info.startStr}&end_date=${info.endStr}&filter_type=${filterType}&task_type=${taskType}&my_schedule=${myScheduleOnly}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Received schedule data:', data);
                    const events = [];
                    
                    // 객체 형태의 데이터 처리로 변경
                    Object.entries(data).forEach(([date, schedules]) => {
                        const eventContent = schedules.map(schedule => {
                            return schedule.displayText;
                        }).join('\n');

                        events.push({
                            title: '',
                            start: date,
                            allDay: true,
                            extendedProps: schedules,
                            backgroundColor: 'transparent',
                            borderColor: 'transparent',
                            textColor: '#000',
                            display: 'block'
                        });
                    });
                    
                    successCallback(events);
                })
                .catch(error => {
                    console.error('Error fetching events:', error);
                    failureCallback(error);
                });
        },
        eventContent: function(arg) {
            const schedules = Object.values(arg.event.extendedProps);
            let html = '';
            
            schedules.forEach(schedule => {
                html += `
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-users me-2 text-primary schedule-icon" 
                           role="button"
                           data-members='${JSON.stringify(schedule.members)}'
                           data-time='${schedule.time}'
                           style="cursor: pointer;">
                        </i>
                        <span>${schedule.displayText}</span>
                    </div>
                `;
            });
            
            return { html: html };
        }
    });
    
    calendar.render();
}

// 모든 JavaScript 코드를 DOMContentLoaded 이벤트 내에 실행
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        initializeCalendar(calendarEl);
    }
    
    // 필요한 DOM 요소들 미리 참조
    const elements = {
        weekSelect: document.getElementById('weekSelect'),
        memberSelect: document.getElementById('memberSelect'),
        weeklyScheduleForm: document.getElementById('weeklyScheduleForm'),
        calendar: document.getElementById('calendar'),
        workTimeSelects: document.querySelectorAll('.work-time-select'),
        workStatusSelects: document.querySelectorAll('.work-status-select'),
        resetButton: document.getElementById('resetButton')
    };

    // 요소 존재 여부 확인 함수
    function validateElements(...elementKeys) {
        return elementKeys.every(key => elements[key] !== null && elements[key] !== undefined);
    }

    // 캘린더 선택 초기화
    if (validateElements('weekSelect')) {
        initializeWeekSelector();
        elements.weekSelect.addEventListener('change', function() {
            if (validateElements('memberSelect')) {
                loadWeeklySchedule(this.value, elements.memberSelect.value);
            }
        });
    }

    // 담당자 선택 이벤트
    if (validateElements('memberSelect', 'weekSelect')) {
        elements.memberSelect.addEventListener('change', function() {
            loadWeeklySchedule(elements.weekSelect.value, this.value);
        });
    }

    // 폼 제출 이벤트
    if (validateElements('weeklyScheduleForm')) {
        elements.weeklyScheduleForm.addEventListener('submit', saveWeeklySchedule);
    }

    // 근무시간 선택 이벤트 - 한 번만 등록
    elements.workTimeSelects.forEach(select => {
        select.addEventListener('change', function() {
            handleWorkTimeChange(this);
        });
    });

    // 근무상태 선택 이벤트 - 한 번만 등록
    elements.workStatusSelects.forEach(select => {
        select.addEventListener('change', function() {
            handleWorkStatusChange(this);
        });
    });

    // 초기 버튼 이벤트
    if (elements.resetButton) {
        elements.resetButton.addEventListener('click', resetForm);
    }

    // 초기 데이터 로드
    if (validateElements('weekSelect', 'memberSelect')) {
        loadWeeklySchedule(
            elements.weekSelect.value, 
            elements.memberSelect.value
        );
    }

    // DOMContentLoaded 이벤트 리스너 내부에 추가
    document.addEventListener('click', function(e) {
        if (e.target.matches('.schedule-icon')) {
            const membersData = JSON.parse(e.target.dataset.members);
            const timeSlot = e.target.dataset.time;
            showMemberListModal(membersData, timeSlot);
        }
    });

    document.getElementById('filter_type').addEventListener('change', function() {
        calendar.refetchEvents();
    });

    document.getElementById('task_type').addEventListener('change', function() {
        calendar.refetchEvents();
    });

    const autoGenerateButton = document.getElementById('autoGenerateButton');
    if (autoGenerateButton) {
        autoGenerateButton.addEventListener('click', function() {
            showHolidaySelectionModal();
        });
    }

    // 체크박스 이벤트 리스너 추가
    document.getElementById('myScheduleOnly').addEventListener('change', function() {
        calendar.refetchEvents();
    });

    const filterType = document.getElementById('filter_type');
    const taskType = document.getElementById('task_type');
    const myScheduleOnly = document.getElementById('myScheduleOnly');

    // 체크박스 이벤트 리스너 수정
    myScheduleOnly.addEventListener('change', function() {
        // 체크박스가 체크되었을 때
        if (this.checked) {
            // 필터들을 '전체'로 초기화
            filterType.value = '전체';
            taskType.value = '전체';
            
            // 필터들을 비활성화
            filterType.disabled = true;
            taskType.disabled = true;
        } else {
            // 체크가 해제되면 필터들을 활성화
            filterType.disabled = false;
            taskType.disabled = false;
        }
        
        // 캘린더 새로고침
        calendar.refetchEvents();
    });

    // 페이지 로드 시 초기 상태 설정 (체크박스가 기본적으로 체크되어 있으므로)
    if (myScheduleOnly.checked) {
        filterType.value = '전체';
        taskType.value = '전체';
        filterType.disabled = true;
        taskType.disabled = true;
    }

    // 시간 입력 필드에 대한 이벤트 리스너 추가
    document.querySelectorAll('.start-time, .end-time').forEach(input => {
        // input 이벤트는 값이 변경될 때마다 발생
        input.addEventListener('input', function() {
            if (!this.disabled) { // 직접입력 모드일 때만 실행
                updateTotalWorkHours();
            }
        });
    });
});

// 근무시간 계산 함수
function calculateDailyWorkHours(workTimeSelect, startTime, endTime, status) {
    // 공휴일은 0 반환
    if (workTimeSelect.value === 'holiday') return 0;
    
    // 연차는 8시간
    if (status === 'vacation' || workTimeSelect.value === 'vacation') return 480;
    
    // 반차는 4시간 - 한국어 상태값과 영문 상태값 모두 처리
    if (status === 'morning-half' || status === 'afternoon-half' || 
        status === '오전반차' || status === '오후반차') {
        return 240;
    }
    
    // 휴무는 0시간
    if (status === 'off' || workTimeSelect.value === 'off') return 0;
    
    // 일반근무나 재택의 경우
    if (startTime && endTime) {
        const start = new Date(`2000-01-01 ${startTime}`);
        const end = new Date(`2000-01-01 ${endTime}`);
        let diffMinutes = (end - start) / (1000 * 60);
        
        // 식사시간 공제
        if (diffMinutes >= 720) { // 12시간 이상
            diffMinutes -= 120; // 2시간 공제
        } else if (diffMinutes >= 360) { // 6시간 이상
            diffMinutes -= 60; // 1시간 공제
        }
        
        return diffMinutes;
    }
    
    return 0;
}

// 주간 총 근무시간 계산 및 표시 함수
function updateTotalWorkHours() {
    let totalMinutes = 0;
    
    for (let i = 0; i < 7; i++) {
        const workTimeSelect = document.querySelector(`.work-time-select[data-day="${i}"]`);
        const statusSelect = document.querySelector(`.work-status-select[data-day="${i}"]`);
        const startTimeInput = document.querySelector(`.start-time[data-day="${i}"]`);
        const endTimeInput = document.querySelector(`.end-time[data-day="${i}"]`);
        
        if (!workTimeSelect) continue;
        
        // 디버깅 - 콘솔에 각 날짜의 상태와 시간 출력
        console.log(`Day ${i}:`, {
            workTime: workTimeSelect.value,
            status: statusSelect ? statusSelect.value : 'none',
            startTime: startTimeInput ? startTimeInput.value : 'none',
            endTime: endTimeInput ? endTimeInput.value : 'none'
        });
        
        let statusValue = '';
        if (statusSelect) {
            statusValue = statusSelect.value;
        }
        
        // 오후반차/오전반차 처리 수정
        if (statusValue === 'afternoon-half' || statusValue === 'morning-half' || 
            statusValue === '오후반차' || statusValue === '오전반차') {
            
            // 시작시간과 종료시간이 있는 경우 실제 근무시간 계산 후 반차 시간(4시간) 추가
            if (startTimeInput.value && endTimeInput.value) {
                const start = new Date(`2000-01-01 ${startTimeInput.value}`);
                const end = new Date(`2000-01-01 ${endTimeInput.value}`);
                let workMinutes = (end - start) / (1000 * 60);
                
                // 식사시간 공제
                if (workMinutes >= 360) { // 6시간 이상
                    workMinutes -= 60; // 1시간 공제
                }
                
                // 근무시간 + 반차 시간(4시간)
                totalMinutes += workMinutes + 240;
                console.log(`Day ${i}: Added ${workMinutes} minutes work + 240 minutes half day = ${workMinutes + 240}`);
            } else {
                // 시간이 없는 경우 반차만 계산
                totalMinutes += 240;
                console.log(`Day ${i}: Added 240 minutes for half day only`);
            }
            continue;
        }
        
        const dailyMinutes = calculateDailyWorkHours(
            workTimeSelect,
            startTimeInput?.value || null,
            endTimeInput?.value || null,
            statusValue
        );
        
        console.log(`Day ${i}: Calculated ${dailyMinutes} minutes`);
        totalMinutes += dailyMinutes;
    }
    
    // 시간과 분으로 변환
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    
    console.log(`Total minutes: ${totalMinutes}, Hours: ${hours}, Minutes: ${minutes}`);
    
    // 화면에 표시
    const weeklyTotalWorkHours = document.getElementById('weeklyTotalWorkHours');
    if (weeklyTotalWorkHours) {
        weeklyTotalWorkHours.textContent = `주간 총 근무시간: ${hours}시간 ${minutes}분`;
    }
}

// 전일 근무시간 옵션인지 확인하는 헬퍼 함수 추가
function isFullDayWorkTime(workTimeValue) {
    return ['8-17', '9-18', '10-19'].includes(workTimeValue);
}

// 근무시간 선택 이벤트 처리
function handleWorkTimeChange(select) {
    const dayIndex = select.getAttribute('data-day');
    const startTimeInput = document.querySelector(`.start-time[data-day="${dayIndex}"]`);
    const endTimeInput = document.querySelector(`.end-time[data-day="${dayIndex}"]`);
    const statusSelect = document.querySelector(`.work-status-select[data-day="${dayIndex}"]`);

    // 시간 입력 필드 초기화 및 비활성화
    startTimeInput.value = '';
    endTimeInput.value = '';
    startTimeInput.disabled = true;
    endTimeInput.disabled = true;

    // 선택된 값에 따른 처리
    switch(select.value) {
        case '8-17':
            startTimeInput.value = '08:00';
            endTimeInput.value = '17:00';
            break;
        case '9-18':
            startTimeInput.value = '09:00';
            endTimeInput.value = '18:00';
            break;
        case '10-19':
            startTimeInput.value = '10:00';
            endTimeInput.value = '19:00';
            break;
        case '9-16':
            startTimeInput.value = '09:00';
            endTimeInput.value = '16:00';
            break;
        case 'custom':
            startTimeInput.disabled = false;
            endTimeInput.disabled = false;
            break;
        case 'holiday':  // 공휴일 추가
        case 'off':
        case 'vacation':
            startTimeInput.value = '';
            endTimeInput.value = '';
            break;
    }

    // 기존 로직 유지
    if (select.value === 'off' || select.value === 'vacation' || select.value === 'holiday' || select.value === '') {
        statusSelect.disabled = true;
        statusSelect.value = '';
    } else {
        statusSelect.disabled = false;
    }

    // 기존 로직 유지
    if (statusSelect.value === 'morning-half' || statusSelect.value === 'afternoon-half') {
        handleWorkStatusChange(statusSelect);
    }

    // 함수 마지막에 근무시간 업데이트 추가
    updateTotalWorkHours();
}

// 시간 형식을 HH:mm 형식으로 변환하는 헬퍼 함수 추가
function formatTimeValue(hour) {
    return `${hour.toString().padStart(2, '0')}:00`;
}

// handleWorkStatusChange 함수 수정
function handleWorkStatusChange(select) {
    const dayIndex = select.getAttribute('data-day');
    const workTimeSelect = document.querySelector(`.work-time-select[data-day="${dayIndex}"]`);
    const startTimeInput = document.querySelector(`.start-time[data-day="${dayIndex}"]`);
    const endTimeInput = document.querySelector(`.end-time[data-day="${dayIndex}"]`);

    const currentWorkTime = workTimeSelect.value;

    switch(select.value) {
        case 'morning-half':
            if (currentWorkTime && currentWorkTime !== 'custom' && currentWorkTime !== 'off' && currentWorkTime !== 'vacation') {
                const [start, end] = currentWorkTime.split('-');
                // 시작 시간에 5시간(9-16은 4시간) 추가하여 값만 변경
                if (currentWorkTime === '9-16') {
                    startTimeInput.value = formatTimeValue(parseInt(start) + 4);
                } else {
                    startTimeInput.value = formatTimeValue(parseInt(start) + 5);
                }
                endTimeInput.value = formatTimeValue(parseInt(end));
                // 입력 필드의 활성화 상태는 변경하지 않음
            }
            break;
        case 'afternoon-half':
            if (currentWorkTime && currentWorkTime !== 'custom' && currentWorkTime !== 'off' && currentWorkTime !== 'vacation') {
                const [start, end] = currentWorkTime.split('-');
                startTimeInput.value = formatTimeValue(parseInt(start));
                // 종료 시간에서 5시간(9-16은 4시간) 차감하여 값만 변경
                if (currentWorkTime === '9-16') {
                    endTimeInput.value = formatTimeValue(parseInt(end) - 4);
                } else {
                    endTimeInput.value = formatTimeValue(parseInt(end) - 5);
                }
                // 입력 필드의 활성화 상태는 변경하지 않음
            }
            break;
        default:
            // 다른 옵션 선택시 기존 근무시간 옵션의 시간으로 복원
            if (currentWorkTime && currentWorkTime !== 'custom' && currentWorkTime !== 'off' && currentWorkTime !== 'vacation') {
                const [start, end] = currentWorkTime.split('-');
                startTimeInput.value = formatTimeValue(parseInt(start));
                endTimeInput.value = formatTimeValue(parseInt(end));
            }
            break;
    }

    // 함수 마지막에 근무시간 업데이트 추가
    updateTotalWorkHours();
}

// 저장 시 데이터 변환
function getScheduleData(dayIndex) {
    const workTimeSelect = document.querySelector(`.work-time-select[data-day="${dayIndex}"]`);
    const workStatusSelect = document.querySelector(`.work-status-select[data-day="${dayIndex}"]`);
    const startTimeInput = document.querySelector(`.start-time[data-day="${dayIndex}"]`);
    const endTimeInput = document.querySelector(`.end-time[data-day="${dayIndex}"]`);

    let status = '근무'; // 기본값
    let startTime = startTimeInput.value;
    let endTime = endTimeInput.value;

    // 근무시간 옵션에 따른 처리
    if (workTimeSelect.value === 'off') {
        status = '휴무';
        startTime = null;
        endTime = null;
    } else if (workTimeSelect.value === 'vacation') {
        status = '연차';
        startTime = null;
        endTime = null;
    }

    // 근무상태 옵션에 따른 처리
    if (workStatusSelect.value === 'remote') {
        status = '재택';
    }

    return {
        start_time: startTime,
        end_time: endTime,
        status: status
    };
}

// 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 근무시간 선택 이벤트
    document.querySelectorAll('.work-time-select').forEach(select => {
        select.addEventListener('change', function() {
            handleWorkTimeChange(this);
        });
    });

    // 근무상태 선택 이벤트
    document.querySelectorAll('.work-status-select').forEach(select => {
        select.addEventListener('change', function() {
            handleWorkStatusChange(this);
        });
    });
});

// showMessage 함수도 null 체크 추가
function showMessage(message, isError = false) {
    const container = document.getElementById('message-container');
    if (!container) {
        console.error('메시지 컨테이너를 찾을 수 없습니다.');
        return;
    }

    // 기존 알림 제거
    container.innerHTML = '';

    // 새 알림 생성
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${isError ? 'danger' : 'success'} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    
    // 메시지 내용 설정
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // 컨테이너에 알림 추가
    container.appendChild(alertDiv);

    // 3초 후 자동으로 제거
    setTimeout(() => {
        if (alertDiv && alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

function showLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.classList.remove('d-none');
    }
}

function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.classList.add('d-none');
    }
}

// 다음 주 요일 구하기
function getNextMonday(date) {
    const day = date.getDay();
    const diff = (day === 0 ? 1 : 8 - day); // 요일이면 1일, 아니면 다음 월요일까의 날짜
    return new Date(date.setDate(date.getDate() + diff));
}

// 날짜 포맷팅 (YYYY-MM-DD)
function formatDate(date) {
    return date.toLocaleDateString('ko-KR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// 날짜 셀 업데이트 함수 추가
function updateDateCells(startDate) {
    const dateCells = document.getElementById('dateCells');
    const dayHeaders = document.getElementById('dayHeaders');
    if (!dateCells || !dayHeaders) return;
    
    // 기존 내용 초기화
    dateCells.innerHTML = '';
    dayHeaders.innerHTML = '';
    
    const currentDate = new Date(startDate);
    const days = ['일', '월', '화', '수', '목', '금', '토'];
    
    // 7일 동안 반복
    for(let i = 0; i < 7; i++) {
        // 요일 헤더 생성
        const th = document.createElement('th');
        const dayIndex = currentDate.getDay();
        th.textContent = days[dayIndex];
        
        // 주말 스타일 적용
        if (dayIndex === 0) { // 일요일
            th.style.color = 'red';
        } else if (dayIndex === 6) { // 토요일
            th.style.color = 'blue';
        }
        dayHeaders.appendChild(th);
        
        // 날짜 셀 생성
        const td = document.createElement('td');
        const dateStr = formatDateString(currentDate);
        td.textContent = dateStr;
        td.dataset.date = dateStr;
        
        // 주말 스타일 적용
        if (dayIndex === 0) {
            td.style.color = 'red';
        } else if (dayIndex === 6) {
            td.style.color = 'blue';
        }
        dateCells.appendChild(td);
        
        // 다음 날짜로 이동
        currentDate.setDate(currentDate.getDate() + 1);
    }
}

// 근무시간 선택 이벤트 처리 개선
function initializeWorkTimeSelects() {
    document.querySelectorAll('.work-time-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const day = this.dataset.day;
            const statusSelect = document.querySelector(`.work-status-select[data-day="${day}"]`);
            const timeInputs = document.querySelectorAll(`[data-day="${day}"].start-time, [data-day="${day}"].end-time`);
            
            // 근무시간 선택에 따른 UI 업데이트
            switch(this.value) {
                case 'custom':
                    timeInputs.forEach(input => {
                        input.disabled = false;
                        input.required = true;
                    });
                    statusSelect.disabled = false;
                    break;
                    
                case 'off':
                case 'vacation':
                    timeInputs.forEach(input => {
                        input.disabled = true;
                        input.required = false;
                        input.value = '';
                    });
                    statusSelect.disabled = true;
                    statusSelect.value = '';
                    break;
                    
                default:
                    if (this.value.includes('-')) {
                        const [start, end] = this.value.split('-');
                        timeInputs[0].value = `${start}:00`;
                        timeInputs[1].value = `${end}:00`;
                    }
                    statusSelect.disabled = false;
                    timeInputs.forEach(input => input.disabled = true);
            }
        });
    });
}

// getDayIndex 함수 추가
function getDayIndex(dateString) {
    const weekStart = new Date(document.getElementById('weekSelect').value);
    const targetDate = new Date(dateString);
    
    // 시간대 차이로 인한 오차를 없애기 위해 날짜만 비교
    weekStart.setHours(0, 0, 0, 0);
    targetDate.setHours(0, 0, 0, 0);
    
    const diffTime = targetDate.getTime() - weekStart.getTime();
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays >= 0 && diffDays < 7 ? diffDays : -1;
}

// 시간 형식 변환 함수 추가
function formatTimeFromISO(isoString) {
    if (!isoString) return '';
    try {
        const date = new Date(isoString);
        return date.toTimeString().slice(0, 5); // "HH:mm" 형식으로 반환
    } catch (error) {
        console.error('시간 형식 변환 오류:', error);
        return '';
    }
}

// loadWeeklySchedule 함수 수정
async function loadWeeklySchedule(weekStart, member) {
    if (!weekStart || !member) return;

    try {
        const response = await fetch(`/workhours/weekly?week=${weekStart}&member=${encodeURIComponent(member)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Received schedule data:', data);

        // 모든 입력 필드 초기화
        document.querySelectorAll('.work-time-select').forEach(select => select.value = '');
        document.querySelectorAll('.work-status-select').forEach(select => {
            select.value = '';
            select.disabled = true;  // 기본적으로 비활성화
        });
        document.querySelectorAll('.start-time').forEach(input => {
            input.value = '';
            input.disabled = true;
        });
        document.querySelectorAll('.end-time').forEach(input => {
            input.value = '';
            input.disabled = true;
        });

        // 데이터가 있는 경우 값 설정 (기존 로직 유지)
        if (Array.isArray(data)) {
            data.forEach(schedule => {
                const dayIndex = getDayIndex(schedule.work_date);
                if (dayIndex === -1) return;

                const workTimeSelect = document.querySelector(`.work-time-select[data-day="${dayIndex}"]`);
                const workStatusSelect = document.querySelector(`.work-status-select[data-day="${dayIndex}"]`);
                const startTimeInput = document.querySelector(`.start-time[data-day="${dayIndex}"]`);
                const endTimeInput = document.querySelector(`.end-time[data-day="${dayIndex}"]`);

                if (!workTimeSelect || !startTimeInput || !endTimeInput) return;

                // 기존 데이터 설정 유지
                workTimeSelect.value = schedule.work_time || '';
                
                if (workStatusSelect) {
                    workStatusSelect.value = schedule.status || '';
                    workStatusSelect.disabled = !schedule.work_time || 
                                              schedule.work_time === 'vacation' || 
                                              schedule.work_time === 'off';
                }
                
                startTimeInput.value = formatTimeFromISO(schedule.start_time);
                endTimeInput.value = formatTimeFromISO(schedule.end_time);
                
                const isCustom = schedule.work_time === 'custom';
                startTimeInput.disabled = !isCustom;
                endTimeInput.disabled = !isCustom;
            });
        }

        // 데이터가 없는 주말에만 공휴일 설정
        const startDate = new Date(weekStart);
        for(let i = 0; i < 7; i++) {
            const currentDate = new Date(startDate);
            currentDate.setDate(currentDate.getDate() + i);
            
            const workTimeSelect = document.querySelector(`.work-time-select[data-day="${i}"]`);
            // 주말이고 아직 값이 설정되지 않은 경우에만 공휴일로 설정
            if ((currentDate.getDay() === 0 || currentDate.getDay() === 6) && 
                workTimeSelect && 
                !workTimeSelect.value) {
                workTimeSelect.value = 'holiday';
                handleWorkTimeChange(workTimeSelect);
            }
        }

        // 데이터 설정 후 근무시간 계산
        updateTotalWorkHours();

    } catch (error) {
        console.error('일정 로드 실패:', error);
        showMessage('일정 불러오는데 실패했습니다.', true);
    }
}

// 근무시간 옵션을 결정하는 새로운 함수 추가
function determineWorkTimeOption(startTime, endTime, existingWorkTime) {
    if (!startTime || !endTime) {
        return existingWorkTime || '';
    }

    // 시간 문자열에서 시간만 추출 (예: "09:00" -> "9")
    const start = parseInt(startTime.split(':')[0]);
    const end = parseInt(endTime.split(':')[0]);

    // 표준 근무시간 옵션과 비교
    const standardTimes = {
        '8-17': [8, 17],
        '9-18': [9, 18],
        '10-19': [10, 19],
        '9-16': [9, 16]
    };

    for (const [option, [standardStart, standardEnd]] of Object.entries(standardTimes)) {
        if (start === standardStart && end === standardEnd) {
            return option;
        }
    }

    // 일치하는 표준 옵션이 없으면 custom 반환
    return 'custom';
}

// 주간 일정 저장 API 호출
async function saveWeeklySchedule(event) {
    event.preventDefault();
    
    try {
        showLoading();
        const weekStart = document.getElementById('weekSelect').value;
        const member = document.getElementById('memberSelect').value;
        const schedules = [];

        for (let i = 0; i < 7; i++) {
            const currentDate = new Date(weekStart);
            currentDate.setDate(currentDate.getDate() + i);
            
            const workTimeSelect = document.querySelector(`.work-time-select[data-day="${i}"]`);
            const workStatusSelect = document.querySelector(`.work-status-select[data-day="${i}"]`);
            const startTimeInput = document.querySelector(`.start-time[data-day="${i}"]`);
            const endTimeInput = document.querySelector(`.end-time[data-day="${i}"]`);

            if (workTimeSelect.value) {
                const schedule = {
                    work_date: currentDate.toISOString().split('T')[0],
                    member: member,
                    work_time: workTimeSelect.value,
                    status: workStatusSelect.value || '',
                    start_time: startTimeInput.value,  // 항상 현재 입력된 시간값을 사용
                    end_time: endTimeInput.value       // 항상 현재 입력된 시간값을 사용
                };

                // 휴무나 연차인 경우에만 시간을 null로 설정
                if (workTimeSelect.value === 'off' || workTimeSelect.value === 'vacation') {
                    schedule.start_time = null;
                    schedule.end_time = null;
                }

                schedules.push(schedule);
            }
        }

        if (schedules.length === 0) {
            throw new Error('일정을 모두 삭제하려면 초기화 버튼을 클릭하세요.');
        }

        const response = await fetch('/workhours/store-weekly', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ schedules })
        });

        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || '저장에 실패했습니다.');
        }

        showMessage('일정이 저장되었습니다.');
        calendar.refetchEvents();
        
    } catch (error) {
        console.error('저장 실패:', error);
        showMessage(error.message || '저장에 실패했습니다.', true);
    } finally {
        hideLoading();
    }
}

// 날짜 포맷팅 함수 추가
function formatDateString(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// 주 선택 롭다운 초기화
function initializeWeekSelector() {
    const weekSelect = document.getElementById('weekSelect');
    if (!weekSelect) return;

    flatpickr(weekSelect, {
        locale: 'ko',
        dateFormat: 'Y-m-d',
        onChange: function(selectedDates, dateStr) {
            const weekStart = getWeekStart(selectedDates[0]);
            updateDateCells(weekStart);
            
            const member = document.getElementById('memberSelect').value;
            loadWeeklySchedule(formatDateString(weekStart), member);
            
            if (calendar) {
                calendar.gotoDate(dateStr);
            }
        }
    });

    // 초기값 설정 (오늘 날짜)
    const today = new Date();
    const weekStart = getWeekStart(today);
    weekSelect.value = formatDateString(weekStart);
    updateDateCells(weekStart);
}

// 선택된 날짜의 해당 주 시작일(일요일)을 반환하는 함수
function getWeekStart(date) {
    const result = new Date(date);
    const day = result.getDay();
    result.setDate(result.getDate() - day);
    return result;
}

// DOMContentLoaded 이벤트 리스너
document.addEventListener('DOMContentLoaded', function() {
    initializeWeekSelector();
    
    const weekSelect = document.getElementById('weekSelect');
    if (weekSelect) {
        weekSelect.addEventListener('change', function(e) {
            const selectedDate = new Date(e.target.value);
            updateDateCells(selectedDate);
            
            const member = document.getElementById('memberSelect').value;
            loadWeeklySchedule(e.target.value, member);
            
            // 캘린더 뷰 업데이트
            if (calendar) {
                calendar.gotoDate(e.target.value);
            }
        });
    }
});

// resetForm 함수 추가
async function resetForm() {
    // 사용자 확인
    const confirmed = await Swal.fire({
        title: '초기화 확인',
        text: '해당 주의 근무 스케쥴을 초기화합니다. 실행하시겠습니까?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '확인',
        cancelButtonText: '취소'
    });

    if (!confirmed.isConfirmed) {
        return;
    }

    try {
        showLoading();
        
        const weekStart = document.getElementById('weekSelect').value;
        const member = document.getElementById('memberSelect').value;
        
        // DB에서 데이터 삭제
        const response = await fetch('/workhours/reset-weekly', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                week_start: weekStart,
                member: member
            })
        });

        if (!response.ok) {
            throw new Error('초기화에 실패했습니다.');
        }

        // 폼 초기화
        document.querySelectorAll('.work-time-select').forEach(select => {
            select.value = '';
        });
        document.querySelectorAll('.work-status-select').forEach(select => {
            select.value = '';
        });
        document.querySelectorAll('.start-time, .end-time').forEach(input => {
            input.value = '';
            input.disabled = true;
        });

        // 캘린더 새로고침
        calendar.refetchEvents();
        
        showMessage('일정이 초기화되었습니다.');
        
    } catch (error) {
        console.error('초기화 실:', error);
        showMessage(error.message || '초기화에 실패했습니다.', true);
    } finally {
        hideLoading();
    }
}

function showMemberListModal(members, timeSlot) {
    const modalTitle = document.querySelector('#memberListModal .modal-title');
    const memberList = document.getElementById('memberList');
    
    modalTitle.textContent = `근무자 목록 (${timeSlot})`;
    memberList.innerHTML = members.map(member => {
        let statusBadge = '';
        
        // 상태값에 따른 뱃지 스타일 설정
        switch(member.status) {
            case 'remote':
                statusBadge = '<span class="badge bg-info member-status-badge">재택</span>';
                break;
            case 'morning-half':
                statusBadge = '<span class="badge bg-warning member-status-badge">오전반차</span>';
                break;
            case 'afternoon-half':
                statusBadge = '<span class="badge bg-warning member-status-badge">오후반차</span>';
                break;
        }
        
        return `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${member.name}
                ${statusBadge}
            </li>
        `;
    }).join('');
    
    const modal = new bootstrap.Modal(document.getElementById('memberListModal'));
    modal.show();
}

// 날짜 배열 생성 함수 추가
function getWeekDates(weekStart) {
    const startDate = new Date(weekStart);
    const dates = [];
    
    // 월요일부터 시작하도록 수정
    const mondayStart = new Date(startDate);
    if (mondayStart.getDay() === 0) {  // 일요일인 경우
        mondayStart.setDate(mondayStart.getDate() + 1);  // 다음날(월요일)로
    } else {
        // 이번 주 월요일로 설정
        mondayStart.setDate(mondayStart.getDate() + (1 - mondayStart.getDay()));
    }
    
    // 월~금 날짜 생성 (5일)
    for(let i = 0; i < 5; i++) {
        const currentDate = new Date(mondayStart);
        currentDate.setDate(mondayStart.getDate() + i);
        dates.push(formatDateString(currentDate));
    }
    
    return dates;
}

// 공휴일 체크박스 HTML 생성 함수 추가
function generateHolidayCheckboxes(dates) {
    return `
        <div class="mt-3">
            <p>공휴일로 지정할 날짜를 선택하세요:</p>
            ${dates.map(date => `
                <div class="form-check">
                    <input type="checkbox" class="form-check-input holiday-checkbox" 
                           id="holiday-${date}" value="${date}">
                    <label class="form-check-label" for="holiday-${date}">${date}</label>
                </div>
            `).join('')}
        </div>
    `;
}

// showHolidaySelectionModal 함수 수정
function showHolidaySelectionModal() {
    const weekStart = document.getElementById('weekSelect').value;
    const dates = getWeekDates(weekStart);

    // 먼저 eligible members 조회
    fetch('/workhours/eligible-members')
        .then(response => response.json())
        .then(members => {
            // SweetAlert2를 사용하여 2단계 모달 표시
            Swal.fire({
                title: '스케줄 자동생성',
                width: '800px',
                html: `
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <h6>구성원 선택</h6>
                            <div>
                                <span id="selected-count" class="badge bg-primary me-2">0/${members.length}명 선택됨</span>
                                <input type="checkbox" id="select-all" class="form-check-input">
                                <label for="select-all">전체 선택</label>
                            </div>
                        </div>
                        <div class="member-list" style="max-height: 200px; overflow-y: auto;">
                            ${members.map((member, index) => `
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input member-checkbox" 
                                           id="member-${member.id}" value="${member.id}">
                                    <label class="form-check-label" for="member-${member.id}">
                                        <span class="me-2">${index + 1}.</span>
                                        ${member.name} (${member.affiliation} / ${member.task} / ${member.status || '상태없음'})
                                    </label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <hr>
                    <div>
                        <h6>공휴일 선택</h6>
                        ${generateHolidayCheckboxes(dates)}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '자동생성 시작',
                cancelButtonText: '취소',
                didOpen: () => {
                    // 전체 선택 이벤트 핸들러
                    document.getElementById('select-all').addEventListener('change', function() {
                        document.querySelectorAll('.member-checkbox')
                            .forEach(cb => cb.checked = this.checked);
                        updateSelectedCount();
                    });
                    
                    // 개별 체크박스 이벤트 핸들러
                    document.querySelectorAll('.member-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', updateSelectedCount);
                    });
                    
                    // 선택된 인원 수 업데이트 함수
                    function updateSelectedCount() {
                        const totalMembers = members.length;
                        const selectedMembers = document.querySelectorAll('.member-checkbox:checked').length;
                        document.getElementById('selected-count').textContent = `${selectedMembers}/${totalMembers}명 선택됨`;
                    }
                },
                preConfirm: () => {
                    const selectedMembers = Array.from(document.querySelectorAll('.member-checkbox:checked'))
                        .map(cb => cb.value);
                    const holidays = [];
                    dates.forEach(date => {
                        if (document.getElementById(`holiday-${date}`).checked) {
                            holidays.push(date);
                        }
                    });
                        
                    if (selectedMembers.length === 0) {
                        Swal.showValidationMessage('최소 1명 이상의 구성원을 선택해주세요.');
                        return false;
                    }
                    
                    return { selectedMembers, holidays };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    generateSchedule(weekStart, result.value.holidays, result.value.selectedMembers);
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: '오류',
                text: '구성원 목록을 불러오는데 실패했습니다.'
            });
        });
}

// generateSchedule 함수 수정
async function generateSchedule(weekStart, holidays, selectedMembers) {
    try {
        showLoading();

        const response = await fetch('/workhours/auto-generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                weekStart: weekStart,
                holidays: holidays,
                selectedMembers: selectedMembers
            })
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || '스케줄 생성에 실패했습니다.');
        }

        await Swal.fire({
            icon: 'success',
            title: '완료',
            text: result.message
        });

        window.location.reload();

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: '오류',
            text: error.message || '스케줄 생성 중 오류가 발생했습니다.'
        });
    } finally {
        hideLoading();
    }
}

</script>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 필터 영역 -->
    <div class="row mb-3">
        <div class="col-md-4">
            <label for="filter_type" class="form-label">지역</label>
            <select id="filter_type" class="form-select">
                <option value="전체">전체</option>
                <option value="서울">서울</option>
                <option value="대전">대전</option>
                <option value="부산">부산</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="task_type" class="form-label">업무</label>
            <select id="task_type" class="form-select">
                <option value="전체">전체</option>
                <option value="법률컨설팅팀">법률컨설팅팀</option>
                <option value="사건관리팀">사건관리팀</option>
                <option value="지원팀">지원팀</option>
                <option value="개발팀">개발팀</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="myScheduleOnly" checked>
                <label class="form-check-label" for="myScheduleOnly">
                    나의 근무일정
                </label>
            </div>
        </div>
    </div>

    <!-- 캘린더 영역 -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- 근무 일정 입력 폼 -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center mb-3">
                <div class="col-md-4 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">근무 일정 입력</h5>
                    <span id="weeklyTotalWorkHours" class="text-primary">주간 총 근무시간: 0시간 0분</span>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="memberSelect">
                        @foreach($members as $member)
                            <option value="{{ $member->name }}" 
                                {{ $member->name === $defaultMember ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="weekSelect" placeholder="날짜 선택">
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- 메시지를 표시할 전용 컨테이너 추가 -->
            <div id="message-container"></div>
            
            <form id="weeklyScheduleForm">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr id="dayHeaders">
                                <!-- JavaScript로 동적 생성됨 -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 근무 일자 -->
                            <tr id="dateCells">
                                <!-- JavaScript로 동적 생성됨 -->
                            </tr>
                            <!-- 근무시간 옵션 -->
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                <td>
                                    <select class="form-select work-time-select" data-day="{{ $i }}">
                                        <option value="">선택없음</option>
                                        <option value="8-17">8시-17시</option>
                                        <option value="9-18">9시-18시</option>
                                        <option value="10-19">10시-19시</option>
                                        <option value="9-16">9시-16시</option>
                                        <option value="custom">직접입력</option>
                                        <option value="off">휴무</option>
                                        <option value="vacation">연차</option>
                                        <option value="holiday">공휴일</option>
                                    </select>
                                </td>
                                @endfor
                            </tr>
                            <!-- 근무상태 옵션 -->
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                <td>
                                    <select class="form-select work-status-select" data-day="{{ $i }}" disabled>
                                        <option value="">선택없음</option>
                                        <option value="remote">재택</option>
                                        <option value="morning-half">오전반차</option>
                                        <option value="afternoon-half">오후반차</option>
                                    </select>
                                </td>
                                @endfor
                            </tr>
                            <!-- 시작 시간 -->
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                <td>
                                    <input type="time" 
                                           class="form-control start-time" 
                                           data-day="{{ $i }}" 
                                           step="1800"
                                           disabled>
                                </td>
                                @endfor
                            </tr>
                            <!-- 종료 시간 -->
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                <td>
                                    <input type="time" 
                                           class="form-control end-time" 
                                           data-day="{{ $i }}" 
                                           step="1800"
                                           disabled>
                                </td>
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">저장</button>
                    <button type="button" class="btn btn-secondary ms-2" id="resetButton">초기화</button>
                    @if(Auth::user()->is_admin)
                        <button type="button" class="btn btn-success ms-2" id="autoGenerateButton">
                            근무스케쥴 자동생성
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 로딩 스피너 컴포넌트 추가 -->
    <div id="loading-spinner" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">로딩중...</span>
        </div>
    </div>

    <!-- 멤버 목록 모달 -->
    <div class="modal fade" id="memberListModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">근무자 목록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush" id="memberList"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
