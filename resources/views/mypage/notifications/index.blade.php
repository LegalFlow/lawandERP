{{-- resources/views/mypage/notifications/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4"></h1>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">통지 목록</h5>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>등록일</th>
                            <th>유형</th>
                            <th>제목</th>
                            <th>작성자</th>
                            <th>피통지자</th>
                            <th>경유자</th>
                            <th>답변기한</th>
                            <th>답변여부</th>
                            <th>답변일자</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            <tr>
                                <td>{{ $notification->created_at->format('Y-m-d') }}</td>
                                <td>{{ $notification->type }}</td>
                                <td>
                                    <a href="{{ route('mypage.notifications.show', $notification) }}" 
                                       class="text-primary text-decoration-none">
                                        {{ $notification->title }}
                                    </a>
                                </td>
                                <td>
                                    @if($notification->creator->name === '김충환')
                                        관리자
                                    @else
                                        {{ $notification->creator->name }}
                                    @endif
                                </td>
                                <td>{{ $notification->notifiedUser->name }}</td>
                                <td>{{ $notification->viaUser ? $notification->viaUser->name : '-' }}</td>
                                <td>
                                    {{ $notification->response_deadline ? $notification->response_deadline.'일' : '-' }}
                                    @if($notification->response_deadline && $notification->getRemainingDaysAttribute() < 0 && $notification->status !== \App\Models\Notification::STATUS_COMPLETED)
                                        <span class="text-danger ms-2">(기한 초과)</span>
                                    @elseif($notification->response_deadline && !$notification->response)
                                        <span class="text-muted ms-2">({{ $notification->getRemainingDaysAttribute() }}일 남음)</span>
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
                                <td>
                                    {{ $notification->response ? $notification->response->responded_at->format('Y-m-d') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    통지 내역이 없습니다.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($notifications->isNotEmpty())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .table th {
        background-color: #f8f9fa;
        font-size: 0.9rem;
    }
    .table td {
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
</style>
@endsection