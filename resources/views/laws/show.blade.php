@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4"></h1>
        <div>
            <a href="{{ route('laws.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>목록으로
            </a>
            @if($isAdmin)
                <a href="{{ route('laws.edit', $law) }}" class="btn btn-primary ms-2">
                    <i class="bi bi-pencil-square me-1"></i>수정
                </a>
                <button type="button" class="btn btn-danger ms-2" onclick="showDeleteModal()">
                    <i class="bi bi-trash me-1"></i>삭제
                </button>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <!-- 기본 정보 테이블 -->
            <table class="table table-bordered">
                <tr>
                    <th class="table-secondary" style="width: 150px">제목</th>
                    <td colspan="3">{{ $law->title }}</td>
                </tr>
                <tr>
                    <th class="table-secondary">등록일</th>
                    <td>{{ $law->registration_date->format('Y-m-d') }}</td>
                    <th class="table-secondary" style="width: 150px">시행일</th>
                    <td>{{ $law->enforcement_date->format('Y-m-d') }}</td>
                </tr>
                <tr>
                    <th class="table-secondary">시행여부</th>
                    <td>{{ $law->status }}</td>
                    <th class="table-secondary">폐기일</th>
                    <td>{{ $law->abolition_date ? $law->abolition_date->format('Y-m-d') : '-' }}</td>
                </tr>
            </table>

            <!-- 내용 -->
            <div class="mt-4">
                <h5 class="card-title mb-3">내용</h5>
                <div class="p-3 bg-light rounded">
                    {!! nl2br(e($law->content)) !!}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
@if($isAdmin)
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>이 내규를 정말 삭제하시겠습니까?</p>
                <p class="text-danger">이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form action="{{ route('laws.destroy', $law) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal() {
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endif

<style>
.table th {
    background-color: #f8f9fa;
    width: 150px;
}
.table td {
    vertical-align: middle;
}
</style>
@endsection