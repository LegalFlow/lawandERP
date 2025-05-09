@extends('layouts.app')

@section('content')
<style>
    /* 연차 현황 섹션 스타일 */
    .annual-stat {
        display: flex;
        flex-direction: column;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
        min-width: 120px;
    }
    
    .annual-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .annual-value {
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .annual-progress-container {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background-color: #f8f9fa;
    }
    
    .progress {
        border-radius: 0.5rem;
        overflow: hidden;
        background-color: transparent;
        border: 1px solid #dee2e6;
        position: relative;
    }
    
    .progress-bar-marker {
        position: absolute;
        top: 0;
        width: 2px;
        height: 100%;
        background-color: #dc3545;
        z-index: 10;
    }
    
    .annual-period-badge {
        font-size: 0.95rem;
        padding: 0.4rem 0.8rem;
    }
    
    @media (max-width: 768px) {
        .annual-stat {
            min-width: 100px;
            padding: 0.5rem;
        }
        
        .annual-value {
            font-size: 1.1rem;
        }
        
        .annual-period-badge {
            font-size: 0.85rem;
        }
    }
</style>

<div class="container-fluid">
    <h1 class="mb-4"></h1>



    <!-- 연차 통계 다음에, 월별 통계 섹션 추가 -->
    @if(request('member') && isset($monthlyStats))
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-circle me-2"></i>
                    {{ request('member') }} 연차 현황
                </h5>
                <span class="badge bg-light text-dark annual-period-badge">
                    {{ $selectedMemberAnnualPeriod['start_date'] }} ~ {{ $selectedMemberAnnualPeriod['end_date'] }}
                </span>
            </div>

            <!-- 연차 현황 섹션 - 모던한 디자인 -->
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="d-flex flex-wrap gap-4 mb-3">
                        <div class="annual-stat">
                            <span class="annual-label"><i class="fas fa-calendar-plus text-primary me-1"></i> 총 연차</span>
                            <span class="annual-value">{{ $totalAnnualLeave }}일</span>
                        </div>
                        <div class="annual-stat">
                            <span class="annual-label"><i class="fas fa-sliders-h text-success me-1"></i> 조정 연차</span>
                            <span class="annual-value">{{ $adjustedAnnualLeave }}일</span>
                        </div>
                        <div class="annual-stat">
                            <span class="annual-label"><i class="fas fa-calendar-check text-warning me-1"></i> 사용 연차</span>
                            <span class="annual-value">{{ $usedAnnualLeave }}일</span>
                        </div>
                        <div class="annual-stat">
                            <span class="annual-label"><i class="fas fa-calendar-day text-info me-1"></i> 잔여 연차</span>
                            <span class="annual-value" {{ $remainingAnnualLeave <= 5 ? 'style=color:#dc3545;font-weight:bold' : '' }}>
                                {{ $remainingAnnualLeave }}일
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-12">
                    @php
                        $totalWidth = $totalAnnualLeave + max(0, $adjustedAnnualLeave);
                        $usedPercent = $totalWidth > 0 ? ($usedAnnualLeave / $totalWidth) * 100 : 0;
                        $remainingPercent = 100 - $usedPercent;
                        
                        // 잔여 연차에 따른 색상 설정
                        $progressColor = 'bg-success';
                        if ($remainingAnnualLeave <= 5 && $remainingAnnualLeave > 2) {
                            $progressColor = 'bg-warning';
                        } elseif ($remainingAnnualLeave <= 2) {
                            $progressColor = 'bg-danger';
                        }
                        
                        // 연차적용기간 진행률 계산
                        $periodStartDate = \Carbon\Carbon::parse($selectedMemberAnnualPeriod['start_date']);
                        $periodEndDate = \Carbon\Carbon::parse($selectedMemberAnnualPeriod['end_date']);
                        $today = \Carbon\Carbon::now();
                        
                        // 오늘이 연차적용기간 내에 있는지 확인
                        $isWithinPeriod = $today->between($periodStartDate, $periodEndDate);
                        
                        // 연차적용기간의 총 일수
                        $totalDays = $periodStartDate->diffInDays($periodEndDate) + 1;
                        
                        // 연차적용기간 시작일부터 오늘까지의 일수 (오늘이 연차적용기간을 벗어난 경우 처리)
                        $daysElapsed = 0;
                        if ($today->lt($periodStartDate)) {
                            $daysElapsed = 0;
                        } elseif ($today->gt($periodEndDate)) {
                            $daysElapsed = $totalDays;
                        } else {
                            $daysElapsed = $periodStartDate->diffInDays($today) + 1;
                        }
                        
                        // 연차적용기간 진행률
                        $periodProgressPercent = $totalDays > 0 ? ($daysElapsed / $totalDays) * 100 : 0;
                    @endphp
                    
                    <div class="annual-progress-container mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">연차적용기간 진행률</small>
                            <small class="text-muted">{{ (int)$daysElapsed }}/{{ (int)$totalDays }}일 ({{ number_format($periodProgressPercent, 1) }}%)</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-secondary" role="progressbar" 
                                style="width: {{ $periodProgressPercent }}%" 
                                aria-valuenow="{{ $periodProgressPercent }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                            @if($isWithinPeriod)
                            <div class="progress-bar-marker" style="left: {{ $periodProgressPercent }}%;" title="오늘"></div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="annual-progress-container">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">연차 사용 현황</small>
                            <small class="text-muted">{{ $usedAnnualLeave }}/{{ $totalWidth }}일 ({{ number_format($usedPercent, 1) }}%)</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                style="width: {{ $usedPercent }}%" 
                                aria-valuenow="{{ $usedPercent }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">
                                @php
                                    $diff = abs($periodProgressPercent - $usedPercent);
                                @endphp
                                @if($diff <= 10)
                                    <i class="fas fa-check-circle text-success me-1"></i> 연차 소진 속도가 적절합니다
                                @elseif($periodProgressPercent > $usedPercent)
                                    <i class="fas fa-info-circle text-info me-1"></i> 연차 소진 속도가 느립니다
                                @else
                                    <i class="fas fa-exclamation-circle text-warning me-1"></i> 연차 소진 속도가 빠릅니다
                                @endif
                            </small>
                            <small class="text-muted">
                                차이: {{ number_format($diff, 1) }}%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 검색 폼 -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-info mb-3" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <small>담당자 필터와 지역/팀 필터는 함께 사용할 수 없습니다. 지역이나 팀을 선택하면 담당자는 '전체'로 설정되고, 담당자를 선택하면 지역과 팀은 '전체'로 설정됩니다.</small>
                <br>
                <small>기간 필터의 기본값은 오늘을 포함한 최근 3개월입니다.</small>
            </div>
            <form method="GET" action="{{ route('work-management.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label for="start_date" class="form-label">시작일</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">종료일</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label for="member" class="form-label">담당자</label>
                    <select name="member" id="member" class="form-select">
                        <option value="">전체</option>
                        @foreach($members as $memberName)
                            <option value="{{ $memberName }}" {{ request('member') == $memberName ? 'selected' : '' }}>
                                {{ $memberName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="location" class="form-label">지역</label>
                    <select name="location" id="location" class="form-select">
                        <option value="">전체</option>
                        <option value="서울" {{ request('location') == '서울' ? 'selected' : '' }}>서울</option>
                        <option value="대전" {{ request('location') == '대전' ? 'selected' : '' }}>대전</option>
                        <option value="부산" {{ request('location') == '부산' ? 'selected' : '' }}>부산</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="team" class="form-label">팀</label>
                    <select name="team" id="team" class="form-select">
                        <option value="">전체</option>
                        <option value="법률컨설팅팀" {{ request('team') == '법률컨설팅팀' ? 'selected' : '' }}>법률컨설팅팀</option>
                        <option value="사건관리팀" {{ request('team') == '사건관리팀' ? 'selected' : '' }}>사건관리팀</option>
                        <option value="개발팀" {{ request('team') == '개발팀' ? 'selected' : '' }}>개발팀</option>
                        <option value="지원팀" {{ request('team') == '지원팀' ? 'selected' : '' }}>지원팀</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">근무상태</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">전체</option>
                        <option value="근무" {{ request('status') == '근무' ? 'selected' : '' }}>근무</option>
                        <option value="재택" {{ request('status') == '재택' ? 'selected' : '' }}>재택</option>
                        <option value="휴무" {{ request('status') == '휴무' ? 'selected' : '' }}>휴무</option>
                        <option value="연차" {{ request('status') == '연차' ? 'selected' : '' }}>연차</option>
                        <option value="오전반차" {{ request('status') == '오전반차' ? 'selected' : '' }}>오전반차</option>
                        <option value="오후반차" {{ request('status') == '오후반차' ? 'selected' : '' }}>오후반차</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex justify-content-between align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary">검색</button>
                        <a href="{{ route('work-management.index') }}" class="btn btn-secondary ms-2">
                            <i class="fas fa-sync-alt me-1"></i>필터 초기화
                        </a>
                    </div>
                    <div>
                        <button type="button" id="showAllAnnualLeaves" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i>전체 연차목록
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- 현재 적용된 필터 표시 -->
            @if(request()->anyFilled(['start_date', 'end_date', 'member', 'location', 'team', 'status']))
            <div class="mt-3">
                <h6 class="text-muted mb-2">적용된 필터:</h6>
                <div class="d-flex flex-wrap gap-2">
                    @if(request('start_date') && request('end_date'))
                        <span class="badge bg-light text-dark">
                            기간: {{ request('start_date') }} ~ {{ request('end_date') }}
                        </span>
                    @elseif(request('start_date'))
                        <span class="badge bg-light text-dark">
                            시작일: {{ request('start_date') }}
                        </span>
                    @elseif(request('end_date'))
                        <span class="badge bg-light text-dark">
                            종료일: {{ request('end_date') }}
                        </span>
                    @endif
                    
                    @if(request('member'))
                        <span class="badge bg-light text-dark">
                            담당자: {{ request('member') }}
                        </span>
                    @endif
                    
                    @if(request('location'))
                        <span class="badge bg-light text-dark">
                            지역: {{ request('location') }}
                        </span>
                    @endif
                    
                    @if(request('team'))
                        <span class="badge bg-light text-dark">
                            팀: {{ request('team') }}
                        </span>
                    @endif
                    
                    @if(request('status'))
                        <span class="badge bg-light text-dark">
                            근무상태: {{ request('status') }}
                        </span>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- 필터 상호작용을 위한 JavaScript 추가 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locationSelect = document.getElementById('location');
            const teamSelect = document.getElementById('team');
            const memberSelect = document.getElementById('member');
            
            // 지역 또는 팀 필터 변경 시 담당자 필터 처리
            function handleFilterChange() {
                if (this.value !== '') {
                    // 지역이나 팀이 선택되면 담당자를 '전체'로 설정
                    memberSelect.value = '';
                }
            }
            
            // 이벤트 리스너 등록
            locationSelect.addEventListener('change', handleFilterChange);
            teamSelect.addEventListener('change', handleFilterChange);
            
            // 담당자 필터 변경 시 지역/팀 필터 처리
            memberSelect.addEventListener('change', function() {
                if (this.value !== '') {
                    // 담당자가 선택되면 지역과 팀을 '전체'로 설정
                    locationSelect.value = '';
                    teamSelect.value = '';
                }
            });
        });
    </script>

    <!-- 근무 기록 테이블 -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>날짜</th>
                            <th>담당자</th>
                            <th>업무</th>
                            <th>소속</th>
                            <th>근무시간</th>
                            <th>시작시간</th>
                            <th>종료시간</th>
                            <th>출근시간</th>
                            <th>퇴근시간</th>
                            <th>근무시간</th>
                            <th>상태</th>
                            <th>근태</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workHours as $workHour)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($workHour->work_date)->format('Y-m-d') }}</td>
                            <td>{{ $workHour->member }}</td>
                            <td>{{ $workHour->task }}</td>
                            <td>{{ $workHour->affiliation }}</td>
                            <td>{{ $workHour->work_time }}</td>
                            <td>{{ date('H:i:s', strtotime($workHour->start_time)) }}</td>
                            <td>{{ date('H:i:s', strtotime($workHour->end_time)) }}</td>
                            <td>{{ $workHour->WSTime ? date('H:i:s', strtotime($workHour->WSTime)) : '' }}</td>
                            <td>{{ $workHour->WCTime ? date('H:i:s', strtotime($workHour->WCTime)) : '' }}</td>
                            <td>{{ $workHour->working_hours }}</td>
                            <td>{{ $workHour->status }}</td>
                            <td>
                                @switch($workHour->attendance)
                                    @case('정상')
                                        <span class="badge bg-success">정상</span>
                                        @break
                                    @case('누락')
                                        <span class="badge bg-warning">누락</span>
                                        @break
                                    @case('지각')
                                        <span class="badge bg-danger">지각</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $workHour->attendance }}</span>
                                @endswitch
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 페이지네이션 -->
    <div class="mt-3">
        {{ $workHours->appends(request()->query())->links() }}
    </div>

    <!-- 모달 추가 (페이지 하단) -->
    <div class="modal fade" id="annualLeavesModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">전체 연차목록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="max-height: 80vh;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>담당자</th>
                                    <th>업무</th>
                                    <th>소속</th>
                                    <th>연차적용기간</th>
                                    <th>총연차</th>
                                    <th>조정연차</th>
                                    <th>사용연차</th>
                                    <th>잔여연차</th>
                                    <th>상태</th>
                                    <th>분기지각</th>
                                </tr>
                            </thead>
                            <tbody id="annualLeavesTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.modal-xl {
    max-width: 50vw;
}

.status-normal {
    background-color: #198754;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.status-warning {
    background-color: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-renewal {
    background-color: #ffc107;
    color: black;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    animation: blink 1s infinite;
}

/* 전체 연차목록 버튼 스타일 */
#showAllAnnualLeaves {
    transition: all 0.3s ease;
    border-radius: 20px;
    padding: 8px 16px;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

#showAllAnnualLeaves:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

#showAllAnnualLeaves i {
    transition: transform 0.3s ease;
}

#showAllAnnualLeaves:hover i {
    transform: rotate(15deg);
}

.late-none {
    background-color: #198754;  /* 초록색 */
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.late-warning {
    background-color: #ffc107;  /* 노란색 */
    color: black;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    animation: blink-slow 1s infinite;  /* 1초 간격 깜빡임 */
}

.late-danger {
    background-color: #fd7e14;  /* 주황색 */
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    animation: blink-fast 0.5s infinite;  /* 0.5초 간격 깜빡임 */
}

.late-critical {
    background-color: #dc3545;  /* 빨간색 */
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

@keyframes blink-slow {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@keyframes blink-fast {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('member').addEventListener('change', function() {
    if (this.value) {
        document.getElementById('location').value = '';
        document.getElementById('team').value = '';
        document.getElementById('location').disabled = true;
        document.getElementById('team').disabled = true;
    } else {
        document.getElementById('location').disabled = false;
        document.getElementById('team').disabled = false;
    }
});

document.getElementById('showAllAnnualLeaves').addEventListener('click', function() {
    fetch('/work-management/annual-leaves')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('annualLeavesTableBody');
            tbody.innerHTML = '';
            
            data.forEach(item => {
                const tr = document.createElement('tr');
                
                // 분기지각 상태에 따른 스타일 결정
                let lateStatus = '';
                let lateText = '';
                
                if (item.quarterly_late === 0) {
                    lateStatus = 'late-none';
                    lateText = '없음';
                } else if (item.quarterly_late === 1) {
                    lateStatus = 'late-warning';
                    lateText = '1회';
                } else if (item.quarterly_late === 2) {
                    lateStatus = 'late-danger';
                    lateText = '2회';
                } else {
                    lateStatus = 'late-critical';
                    lateText = `${item.quarterly_late}회`;
                }

                tr.innerHTML = `
                    <td>${item.member}</td>
                    <td>${item.task}</td>
                    <td>${item.affiliation}</td>
                    <td>${item.period}</td>
                    <td>${item.total_leave}일</td>
                    <td>${item.adjusted_leave}일</td>
                    <td>${item.used_leave}일</td>
                    <td>${item.remaining_leave}일</td>
                    <td><span class="status-${item.status}">${
                        item.status === 'normal' ? '정상' :
                        item.status === 'warning' ? '주의' :
                        '갱신'
                    }</span></td>
                    <td><span class="${lateStatus}">${lateText}</span></td>
                `;
                tbody.appendChild(tr);
            });
            
            const modal = new bootstrap.Modal(document.getElementById('annualLeavesModal'));
            modal.show();
        });
});
</script>
@endpush