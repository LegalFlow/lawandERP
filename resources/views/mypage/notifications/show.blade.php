{{-- resources/views/mypage/notifications/show.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4"></h1>

    <div class="card mb-4">
        {{-- Header --}}
        <div class="card-header">
            <h5 class="mb-0">{{ $notification->title }}</h5>
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
                            <th>작성자</th>
                            <td>
                                @if($notification->creator->name === '김충환')
                                    관리자
                                @else
                                    {{ $notification->creator->name }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>피통지자</th>
                            <td>{{ $notification->notifiedUser->name }}</td>
                        </tr>
                        <tr>
                            <th>경유자</th>
                            <td>{{ $notification->viaUser ? $notification->viaUser->name : '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th style="width: 120px">답변기한</th>
                            <td>
                                {{ $notification->response_deadline ? $notification->response_deadline.'일' : '-' }}
                                @if($notification->response_deadline && $notification->getRemainingDaysAttribute() < 0 && $notification->status !== \App\Models\Notification::STATUS_COMPLETED)
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

            {{-- 답변 폼 --}}
            @if($notification->status === \App\Models\Notification::STATUS_WAITING && $notification->notified_user_id === auth()->id())
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">답변하기</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('mypage.notifications.response.store', $notification) }}" method="POST">
                        @csrf
                        
                        {{-- 답변 가이드 --}}
                        <div class="border border-secondary rounded p-3 bg-light mb-4">
                            <p class="fw-bold mb-2">답변 작성 안내:</p>
                            <p class="mb-1">- 요청사항에 대해 구체적으로 답변해주세요.</p>
                            <p class="mb-1">- 관련 자료가 있다면 언급해주세요.</p>
                            <p class="mb-1">- 향후 계획이나 개선방안을 포함해주세요.</p>
                            <p class="mb-1">- 답변은 피통지자만 하고, 경유자는 조회만 가능합니다.</p>
                            <p class="mb-0 text-danger">- 답변이 완료되면 수정할 수 없으므로 신중히 작성하여 제출해 주세요.</p>
                        </div>

                        <div class="mb-3">
                            <textarea name="content" 
                                    rows="10" 
                                    class="form-control"
                                    placeholder="답변을 작성해주세요..."
                                    required></textarea>
                            
                            @error('content')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-end">
                            <a href="{{ route('mypage.notifications.index') }}" 
                               class="btn btn-secondary">
                                취소
                            </a>
                            <button type="submit" class="btn btn-primary ms-2">답변하기</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

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
                <a href="{{ route('mypage.notifications.index') }}" 
                   class="btn btn-secondary">
                    목록으로
                </a>
            </div>
        </div>
    </div>
</div>

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