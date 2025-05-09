@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">신청함 관리</h5>
                </div>
                <div class="card-body">
                    @if(count($requests) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>신청일</th>
                                        <th>신청서 번호</th>
                                        <th>작성자</th>
                                        <th>신청종류</th>
                                        <th>시작일</th>
                                        <th>종료일</th>
                                        <th>특정일</th>
                                        <th>상태</th>
                                        <th>처리일</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr class="clickable-row" data-href="{{ route('admin.requests.show', $request->id) }}" style="cursor: pointer;">
                                            <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                            <td>{{ $request->request_number }}</td>
                                            <td>{{ $request->user->name }}</td>
                                            <td>{{ $request->request_type }}</td>
                                            <td>{{ $request->start_date ? $request->start_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $request->end_date ? $request->end_date->format('Y-m-d') : '-' }}</td>
                                            <td>{{ $request->specific_date ? $request->specific_date->format('Y-m-d') : '-' }}</td>
                                            <td>
                                                @if($request->status == '승인대기')
                                                    <span class="badge bg-warning">승인대기</span>
                                                @elseif($request->status == '승인완료')
                                                    <span class="badge bg-success">승인완료</span>
                                                @else
                                                    <span class="badge bg-danger">반려</span>
                                                @endif
                                            </td>
                                            <td>{{ $request->processed_at ? $request->processed_at->format('Y-m-d') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $requests->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">처리할 신청 내역이 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 클릭 가능한 행에 이벤트 리스너 추가
        const clickableRows = document.querySelectorAll('.clickable-row');
        clickableRows.forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.getAttribute('data-href');
            });
        });
    });
</script>
@endpush
@endsection 