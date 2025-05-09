@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <!-- 헤더 영역 -->
    <div class="row mb-2">
        <div class="col-auto d-none d-sm-block">
            <h3>연봉계약서 관리</h3>
        </div>
        <div class="col-auto ms-auto">
            <button type="button" class="btn btn-primary me-2" onclick="openIndividualModal()">
                연봉계약서 개별 생성
            </button>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                연봉계약서 일괄 생성
            </button>
        </div>
    </div>

    <!-- 필터 카드 -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.salary-contracts.index') }}" method="GET" class="row g-3">
                <div class="col-md">
                    <label class="form-label">계약 기간</label>
                    <select class="form-select" name="period">
                        <option value="">전체 기간</option>
                        @for($i = now()->year; $i >= now()->year - 5; $i--)
                            <option value="{{ $i }}" {{ request('period') == $i ? 'selected' : '' }}>
                                {{ $i }}년
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label">소속팀</label>
                    <select class="form-select" name="task">
                        <option value="">전체</option>
                        <option value="법률컨설팅팀" {{ request('task') == '법률컨설팅팀' ? 'selected' : '' }}>법률컨설팅팀</option>
                        <option value="사건관리팀" {{ request('task') == '사건관리팀' ? 'selected' : '' }}>사건관리팀</option>
                        <option value="개발팀" {{ request('task') == '개발팀' ? 'selected' : '' }}>개발팀</option>
                        <option value="지원팀" {{ request('task') == '지원팀' ? 'selected' : '' }}>지원팀</option>
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label">직급</label>
                    <select class="form-select" name="position">
                        <option value="">전체</option>
                        <option value="실장" {{ request('position') == '실장' ? 'selected' : '' }}>실장</option>
                        <option value="팀장" {{ request('position') == '팀장' ? 'selected' : '' }}>팀장</option>
                        <option value="과장" {{ request('position') == '과장' ? 'selected' : '' }}>과장</option>
                        <option value="대리" {{ request('position') == '대리' ? 'selected' : '' }}>대리</option>
                        <option value="주임" {{ request('position') == '주임' ? 'selected' : '' }}>주임</option>
                        <option value="사원" {{ request('position') == '사원' ? 'selected' : '' }}>사원</option>
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label">승인상태</label>
                    <select class="form-select" name="approval_status">
                        <option value="">전체</option>
                        <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>승인대기</option>
                        <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>승인완료</option>
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label">검색</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               value="{{ request('search') }}" placeholder="이름 검색">
                    </div>
                </div>
                <div class="col-md align-self-end">
                    <button type="submit" class="btn btn-primary w-100">검색</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 목록 테이블 -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap">직원명</th>
                        <th class="text-nowrap">소속팀</th>
                        <th class="text-nowrap">직급</th>
                        <th class="text-nowrap text-end">기본급</th>
                        <th class="text-nowrap">계약기간</th>
                        <th class="text-nowrap text-center">승인상태</th>
                        <th class="text-nowrap">승인일시</th>
                        <th class="text-nowrap">작성일자</th>
                        <th class="text-nowrap text-center">관리</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                    <tr>
                        <td>{{ $contract->user->name }}</td>
                        <td>{{ $contract->user->member->task }}</td>
                        <td>{{ $contract->position }}</td>
                        <td class="text-end">{{ number_format($contract->base_salary) }}원</td>
                        <td>{{ $contract->contract_start_date->format('Y.m.d') }} ~ 
                            {{ $contract->contract_end_date->format('Y.m.d') }}</td>
                        <td class="text-center">
                            @if($contract->approved_at)
                                <span class="badge bg-success">승인완료</span>
                            @else
                                <span class="badge bg-warning">승인대기</span>
                            @endif
                        </td>
                        <td>{{ $contract->approved_at ? $contract->approved_at->format('Y.m.d H:i') : '-' }}</td>
                        <td>{{ $contract->created_date->format('Y.m.d') }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.salary-contracts.show', $contract->id) }}" 
                               class="btn btn-sm btn-primary">상세</a>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteContract({{ $contract->id }})">삭제</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">등록된 연봉계약서가 없습니다.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-center">
            {{ $contracts->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- 일괄 생성 모달 -->
<div class="modal fade" id="createContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연봉계약서 일괄 생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="contractForm" action="{{ route('admin.salary-contracts.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- 계약 기간 설정 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">연봉계약 시작일</label>
                            <input type="date" name="contract_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">연봉계약 종료일</label>
                            <input type="date" name="contract_end_date" class="form-control" required>
                        </div>
                    </div>

                    <!-- 팀별 급여 설정 -->
                    <div class="row">
                        @foreach(['법률컨설팅팀', '사건관리팀', '개발팀', '지원팀'] as $team)
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ $team }}</h5>
                                </div>
                                <div class="card-body">
                                    @foreach(['실장', '팀장', '과장', '대리', '주임', '사원'] as $position)
                                    <div class="mb-3">
                                        <label class="form-label">{{ $position }}</label>
                                        <input type="number" 
                                               name="salaries[{{ $team }}][{{ $position }}]" 
                                               class="form-control" 
                                               placeholder="기본급 입력">
                                    </div>
                                    @endforeach
                                    <div class="mb-3">
                                        <label class="form-label">메모</label>
                                        <textarea name="memos[{{ $team }}]" 
                                                  class="form-control" 
                                                  rows="3" 
                                                  placeholder="팀 메모 입력"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">일괄 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 개별 생성 모달 추가 -->
<div class="modal fade" id="individualContractModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연봉계약서 개별 생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="individualContractForm" action="{{ route('admin.salary-contracts.store-individual') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- 계약 기간 설정 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">연봉계약 시작일</label>
                            <input type="date" name="contract_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">연봉계약 종료일</label>
                            <input type="date" name="contract_end_date" class="form-control" required>
                        </div>
                    </div>

                    <!-- 직원 목록 테이블 -->
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="checkAll" class="form-check-input">
                                    </th>
                                    <th>이름</th>
                                    <th>직급</th>
                                    <th>업무</th>
                                    <th>지역</th>
                                    <th>재직상태</th>
                                    <th>기본급</th>
                                    <th>메모</th>
                                </tr>
                            </thead>
                            <tbody id="membersList">
                                <!-- Ajax로 데이터 로드 -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">개별 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Axios 라이브러리 추가 -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
// CSRF 토큰 설정
const token = document.querySelector('meta[name="csrf-token"]').content;
axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

function openCreateModal() {
    const modal = new bootstrap.Modal(document.getElementById('createContractModal'));
    modal.show();
}

function openIndividualModal() {
    loadMembers();
    const modal = new bootstrap.Modal(document.getElementById('individualContractModal'));
    modal.show();
}

function loadMembers() {
    axios.get('/admin/members/active')
        .then(response => {
            const tbody = document.getElementById('membersList');
            tbody.innerHTML = response.data.map(member => `
                <tr>
                    <td>
                        <input type="checkbox" name="selected_members[]" value="${member.id}" class="form-check-input member-checkbox">
                    </td>
                    <td>${member.name}</td>
                    <td>${member.position}</td>
                    <td>${member.task}</td>
                    <td>${member.affiliation}</td>
                    <td>${member.status}</td>
                    <td>
                        <input type="number" name="base_salary[${member.id}]" class="form-control" placeholder="기본급 입력">
                    </td>
                    <td>
                        <input type="text" name="memo[${member.id}]" class="form-control" placeholder="메모 입력">
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading members:', error);
            alert('멤버 목록을 불러오는 중 오류가 발생했습니다.');
        });
}

function deleteContract(id) {
    if (confirm('정말 삭제하시겠습니까?')) {
        axios.delete(`/admin/salary-contracts/${id}`)
            .then(response => {
                if (response.data.success) {
                    location.reload();
                } else {
                    alert('삭제 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('삭제 중 오류가 발생했습니다.');
            });
    }
}

// 전체 선택/해제 기능
document.getElementById('checkAll').addEventListener('change', function() {
    const checkboxes = document.getElementsByClassName('member-checkbox');
    Array.from(checkboxes).forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// 에러 메시지가 있는 경우 모달 자동 표시
@if($errors->any())
    document.addEventListener('DOMContentLoaded', function() {
        openCreateModal();
    });
@endif
</script>
@endpush

@push('styles')
<style>
.table th {
    white-space: nowrap;
}
</style>
@endpush