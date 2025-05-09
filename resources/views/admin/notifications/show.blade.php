{{-- resources/views/admin/notifications/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4"></h1>

    <div class="card mb-4">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $notification->title }}</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary" 
                        onclick="openNotificationModal({{ $notification->id }})">
                    수정
                </button>
                <form action="{{ route('admin.notifications.destroy', $notification) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger ms-2"
                            onclick="return confirm('정말 삭제하시겠습니까?')">
                        삭제
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body">
            {{-- 통지 정보 --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 120px">등록일</th>
                            <td>{{ $notification->created_at->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>통지유형</th>
                            <td>{{ $notification->type }}</td>
                        </tr>
                        <tr>
                            <th>피통지자</th>
                            <td>{{ $notification->notifiedUser->name }}</td>
                        </tr>
                        <tr>
                            <th>경유자</th>
                            <td>{{ $notification->viaUser->name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 120px">작성자</th>
                            <td>{{ $notification->creator->name }}</td>
                        </tr>
                        <tr>
                            <th>답변기한</th>
                            <td>
                                {{ $notification->response_deadline ? $notification->response_deadline.'일' : '-' }}
                                @if(
                                    $notification->status !== \App\Models\Notification::STATUS_COMPLETED && 
                                    $notification->response_deadline && 
                                    $notification->getRemainingDaysAttribute() < 0
                                )
                                    <span class="text-danger ms-2">(기한 초과)</span>
                                @elseif($notification->response_deadline && !$notification->response)
                                    <span class="text-muted ms-2">({{ $notification->getRemainingDaysAttribute() }}일 남음)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>답변여부</th>
                            <td>
                                @if($notification->status === \App\Models\Notification::STATUS_COMPLETED)
                                    <span class="badge bg-success">답변완료</span>
                                @elseif($notification->status === \App\Models\Notification::STATUS_WAITING)
                                    <span class="badge bg-warning">답변대기</span>
                                @else
                                    <span class="badge bg-secondary">답변불요</span>
                                @endif
                            </td>
                        </tr>
                        @if($notification->response)
                        <tr>
                            <th>답변일자</th>
                            <td>{{ $notification->response->responded_at->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- 통지 내용 --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">통지 내용</h6>
                </div>
                <div class="card-body bg-light">
                    <div class="p-3">
                        {!! nl2br(e($notification->content)) !!}
                    </div>
                </div>
            </div>

            {{-- 답변 내용 --}}
            @if($notification->response)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">답변 내용</h6>
                </div>
                <div class="card-body bg-light">
                    <div class="p-3">
                        {!! nl2br(e($notification->response->content)) !!}
                    </div>
                    <div class="mt-2 text-muted small">
                        답변자: {{ $notification->response->responder->name }} | 
                        답변일시: {{ $notification->response->responded_at->format('Y-m-d H:i') }}
                    </div>
                </div>
            </div>
            @endif

            {{-- Footer --}}
            <div class="mt-4">
                <a href="{{ route('admin.notifications.index') }}" 
                   class="btn btn-secondary">
                    목록으로
                </a>
            </div>
        </div>
    </div>
</div>

{{-- 모달 컴포넌트 포함 --}}
@include('admin.notifications._modal')

<style>
    .table th {
        background-color: #f8f9fa;
    }
    .table th, .table td {
        padding: 0.5rem;
        font-size: 0.85rem;
    }
    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
</style>
@endsection