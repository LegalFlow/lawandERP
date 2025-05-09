@extends('layouts.app')

@section('content')
<div class="container">
    <!-- 제목과 현재 시각을 포함하는 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>근무현황</h2>
        <div id="current-time" class="current-time-wrapper">
            <div class="date"></div>
            <div class="time"></div>
        </div>
    </div>

    <!-- 필터 섹션 -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" method="GET" class="row g-3 align-items-end">
                <!-- 날짜 선택 -->
                <div class="col-md-3">
                    <label for="date" class="form-label">날짜</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="{{ $selectedDate }}" onchange="this.form.submit()">
                </div>

                <!-- 소속 필터 -->
                <div class="col-md-3">
                    <label for="affiliation" class="form-label">소속</label>
                    <select class="form-select" id="affiliation" name="affiliation" onchange="this.form.submit()">
                        @foreach($affiliations as $affiliation)
                            <option value="{{ $affiliation }}" {{ $selectedAffiliation === $affiliation ? 'selected' : '' }}>
                                {{ $affiliation }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 업무 필터 -->
                <div class="col-md-3">
                    <label for="task" class="form-label">업무</label>
                    <select class="form-select" id="task" name="task" onchange="this.form.submit()">
                        @foreach($tasks as $task)
                            <option value="{{ $task }}" {{ $selectedTask === $task ? 'selected' : '' }}>
                                {{ $task }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 상태 필터 -->
                <div class="col-md-3">
                    <label for="status" class="form-label">상태</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- 데이터 테이블 -->
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>순번</th>
                        <th>이름</th>
                        <th>내선번호</th>
                        <th>업무</th>
                        <th>소속</th>
                        <th>상태</th>
                        <th>출근시각</th>
                        <th>퇴근시각</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workData as $index => $work)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="status-dot" 
                                      data-start-time="{{ $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i:s') : '' }}" 
                                      data-end-time="{{ $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i:s') : '' }}"
                                      data-status="{{ $work->status }}">●</span>
                                {{ $work->member }}
                            </td>
                            <td>{{ $phoneNumbers[$work->member] ?? '' }}</td>
                            <td>{{ $work->task }}</td>
                            <td>{{ $work->affiliation }}</td>
                            <td>{{ $work->status }}</td>
                            <td>{{ $work->start_time ? \Carbon\Carbon::parse($work->start_time)->format('H:i:s') : '' }}</td>
                            <td>{{ $work->end_time ? \Carbon\Carbon::parse($work->end_time)->format('H:i:s') : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">데이터가 없습니다.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CSS 스타일 추가 (head 섹션이나 별도 스타일시트에 추가) -->
<style>
    .status-dot {
        margin-right: 8px;
        font-size: 12px;
    }
    .status-active {
        color: #28a745;
        animation: blink 1s infinite;
    }
    .status-remote {
        color: #fd7e14;  /* 주황색 */
        animation: blink 1s infinite;
    }
    .status-inactive {
        color: #dc3545;
    }
    @keyframes blink {
        50% {
            opacity: 0;
        }
    }
    .current-time-wrapper {
        background: #f8f9fa;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
        min-width: 200px;
    }

    .current-time-wrapper .date {
        color: #495057;
        font-size: 0.9rem;
        margin-bottom: 2px;
    }

    .current-time-wrapper .time {
        color: #212529;
        font-size: 1.4rem;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }
</style>

<!-- JavaScript 추가 (body 끝 부분에 추가) -->
<script>
    function updateCurrentTime() {
        const now = new Date();
        
        // 날짜 포매팅
        const dateStr = now.toLocaleDateString('ko-KR', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            weekday: 'short' 
        });
        
        // 시간 포매팅
        const timeStr = now.toLocaleTimeString('ko-KR', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: false 
        });
        
        // 날짜와 시간을 별도의 요소에 업데이트
        document.querySelector('#current-time .date').textContent = dateStr;
        document.querySelector('#current-time .time').textContent = timeStr;
    }

    // 현재 시각 업데이트 시작
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);

    function timeToMinutes(timeStr) {
        if (!timeStr) return 0;
        const [hours, minutes, seconds] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function updateStatusDots() {
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        document.querySelectorAll('.status-dot').forEach(dot => {
            const startTime = dot.dataset.startTime;
            const endTime = dot.dataset.endTime;
            const status = dot.dataset.status;  // 상태 정보 추가
            
            if (!startTime || !endTime) {
                dot.classList.add('status-inactive');
                dot.classList.remove('status-active', 'status-remote');
                return;
            }

            const startMinutes = timeToMinutes(startTime);
            const endMinutes = timeToMinutes(endTime);
            const isWorkingTime = currentMinutes >= startMinutes && currentMinutes <= endMinutes;

            if (isWorkingTime) {
                if (status === '재택') {
                    dot.classList.add('status-remote');
                    dot.classList.remove('status-active', 'status-inactive');
                } else {
                    dot.classList.add('status-active');
                    dot.classList.remove('status-remote', 'status-inactive');
                }
            } else {
                dot.classList.add('status-inactive');
                dot.classList.remove('status-active', 'status-remote');
            }
        });
    }

    // 초기 실행
    updateStatusDots();

    // 1초마다 업데이트
    setInterval(updateStatusDots, 1000);
</script>
@endsection