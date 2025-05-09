@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">통지 관리</h1>

    <!-- 필터 섹션 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="row g-3">
                <!-- 검색어 필터 -->
                <div class="col-md-4">
                    <label class="form-label">검색어</label>
                    <input type="text" class="form-control" name="search_text" 
                           value="{{ request('search_text') }}" placeholder="제목 또는 내용 검색">
                </div>

                <!-- 기간 필터 -->
                <div class="col-md-6">
                    <label class="form-label">기간</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="start_date" 
                               value="{{ request('start_date', date('Y-m-01')) }}">
                        <span class="input-group-text">~</span>
                        <input type="date" class="form-control" name="end_date" 
                               value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <!-- 상태 필터 -->
                <div class="col-md-2">
                    <label class="form-label">답변상태</label>
                    <select class="form-select" name="status">
                        <option value="">전체</option>
                        @foreach(\App\Models\Notification::getAvailableStatuses() as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 버튼 그룹 -->
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">검색</button>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">초기화</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 신규등록 버튼 -->
    <div class="mb-3">
        <button onclick="openNotificationModal()" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>신규등록
        </button>
    </div>

    <!-- 리스트 테이블 -->
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 120px">등록일</th>
                        <th style="width: 120px">유형</th>
                        <th>제목</th>
                        <th style="width: 120px">피통지자</th>
                        <th style="width: 120px">경유자</th>
                        <th style="width: 100px">답변기한</th>
                        <th style="width: 100px">답변여부</th>
                        <th style="width: 120px">답변일자</th>
                        <th style="width: 120px">작성자</th>
                        <th style="width: 100px">관리</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notifications as $notification)
                        <tr>
                            <td>{{ $notification->created_at->format('Y-m-d') }}</td>
                            <td>{{ $notification->type }}</td>
                            <td>
                                <a href="{{ route('admin.notifications.show', $notification) }}" 
                                   class="text-decoration-none">
                                    {{ $notification->title }}
                                </a>
                            </td>
                            <td>{{ $notification->notifiedUser->name }}</td>
                            <td>{{ $notification->viaUser->name ?? '-' }}</td>
                            <td>
                                {{ $notification->response_deadline ? $notification->response_deadline.'일' : '-' }}
                                @if(
                                    $notification->status !== \App\Models\Notification::STATUS_COMPLETED && 
                                    $notification->response_deadline && 
                                    $notification->getRemainingDaysAttribute() < 0
                                )
                                    <span class="text-danger ms-1">(기한초과)</span>
                                @endif
                            </td>
                            <td>
                                @if($notification->status === \App\Models\Notification::STATUS_COMPLETED)
                                    <span class="badge bg-success">답변완료</span>
                                @elseif($notification->status === \App\Models\Notification::STATUS_WAITING)
                                    <span class="badge bg-warning">답변대기</span>
                                @else
                                    <span class="badge bg-secondary">답변불요</span>
                                @endif
                            </td>
                            <td>{{ $notification->response ? $notification->response->responded_at->format('Y-m-d') : '-' }}</td>
                            <td>{{ $notification->creator->name }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown">
                                        관리
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <button class="dropdown-item" 
                                                    onclick="openNotificationModal({{ $notification->id }})">
                                                수정
                                            </button>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.notifications.destroy', $notification) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"
                                                        onclick="return confirmDelete('{{ $notification->status }}')">
                                                    삭제
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <div class="d-flex justify-content-center mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</div>

<!-- 모달 컴포넌트는 그대로 유지 -->
@include('admin.notifications._modal')

<style>
    .table th, .table td {
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .form-control, .form-select, .btn {
        font-size: 0.85rem;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
</style>

<script>
function confirmDelete(status) {
    if (status === '{{ \App\Models\Notification::STATUS_COMPLETED }}') {
        alert('답변이 완료된 통지는 삭제할 수 없습니다.');
        return false;
    }
    return confirm('정말 삭제하시겠습니까?');
}
</script>
@endsection