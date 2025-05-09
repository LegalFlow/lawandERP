@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <!-- 헤더 영역 -->
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1">연봉계약서 상세</h3>
                    <p class="text-muted">{{ $salaryContract->user->name }}님의 연봉계약서</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" onclick="startEdit()">수정</button>
                    <button type="button" class="btn btn-danger ms-2" onclick="deleteContract()">삭제</button>
                    <a href="{{ route('admin.salary-contracts.pdf', $salaryContract) }}" class="btn btn-outline-danger ms-2">
                        <i class="fas fa-file-pdf"></i> PDF 다운로드
                    </a>
                    <a href="{{ route('admin.salary-contracts.index') }}" class="btn btn-secondary ms-2">목록</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 경고 메시지 (수정 모드일 때만 표시) -->
    <div id="warningAlert" class="alert alert-warning d-none" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        계약서를 수정하면 기존 승인이 취소되며, 직원의 재승인이 필요합니다.
    </div>

    <!-- 기본 정보 카드 -->
    <form id="contractForm" action="{{ route('admin.salary-contracts.update', $salaryContract->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">기본 정보</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">직원명</label>
                        <input type="text" class="form-control" value="{{ $salaryContract->user->name }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">소속팀</label>
                        <input type="text" class="form-control" value="{{ $salaryContract->user->member->task }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">직급</label>
                        <input type="text" class="form-control" value="{{ $salaryContract->position }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">기본급</label>
                        <input type="number" name="base_salary" class="form-control edit-field" 
                               value="{{ $salaryContract->base_salary }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">계약 시작일</label>
                        <input type="date" name="contract_start_date" class="form-control edit-field"
                               value="{{ $salaryContract->contract_start_date->format('Y-m-d') }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">계약 종료일</label>
                        <input type="date" name="contract_end_date" class="form-control edit-field"
                               value="{{ $salaryContract->contract_end_date->format('Y-m-d') }}" disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- 메모 카드 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">메모</h5>
            </div>
            <div class="card-body">
                <textarea name="memo" class="form-control edit-field" rows="5" 
                          disabled>{{ $salaryContract->memo }}</textarea>
            </div>
        </div>

        <!-- 승인 정보 카드 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">승인 정보</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">승인 상태</label>
                        <div>
                            @if($salaryContract->approved_at)
                                <span class="badge bg-success">승인완료</span>
                            @else
                                <span class="badge bg-warning">승인대기</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">승인 일시</label>
                        <input type="text" class="form-control" 
                               value="{{ $salaryContract->approved_at ? $salaryContract->approved_at->format('Y-m-d H:i:s') : '-' }}" 
                               disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">작성일자</label>
                        <input type="text" class="form-control" 
                               value="{{ $salaryContract->created_date->format('Y-m-d') }}" 
                               disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">작성자</label>
                        <input type="text" class="form-control" 
                               value="{{ $salaryContract->creator->name }}" 
                               disabled>
                    </div>
                </div>
            </div>
        </div>

        <!-- 수정 버튼 (수정 모드일 때만 표시) -->
        <div id="editButtons" class="mt-4 text-end d-none">
            <button type="button" class="btn btn-secondary" onclick="cancelEdit()">취소</button>
            <button type="submit" class="btn btn-primary ms-2">저장</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function startEdit() {
    // 수정 모드 활성화
    document.querySelectorAll('.edit-field').forEach(field => {
        field.disabled = false;
    });
    document.getElementById('warningAlert').classList.remove('d-none');
    document.getElementById('editButtons').classList.remove('d-none');
}

function cancelEdit() {
    // 수정 모드 비활성화
    document.querySelectorAll('.edit-field').forEach(field => {
        field.disabled = true;
    });
    document.getElementById('warningAlert').classList.add('d-none');
    document.getElementById('editButtons').classList.add('d-none');
    document.getElementById('contractForm').reset();
}

function deleteContract() {
    if (confirm('정말 삭제하시겠습니까?')) {
        axios.delete('{{ route("admin.salary-contracts.destroy", $salaryContract->id) }}')
            .then(response => {
                window.location.href = '{{ route("admin.salary-contracts.index") }}';
            })
            .catch(error => {
                alert('삭제 중 오류가 발생했습니다.');
            });
    }
}
</script>
@endpush