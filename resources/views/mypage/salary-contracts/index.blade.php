@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <!-- 헤더 영역 -->
    <div class="row mb-4">
        <div class="col">
            <h3>연봉계약서</h3>
            <p class="text-muted">연봉계약서 확인 및 승인 관리</p>
        </div>
    </div>

    @if($hasPendingContract)
    <!-- 승인 필요 알림 -->
    <div class="alert alert-info" role="alert">
        <div class="d-flex">
            <div class="flex-shrink-0">
                <i class="fas fa-bell me-2"></i>
            </div>
            <div>
                <h4 class="alert-heading">새로운 연봉계약서 승인 필요</h4>
                <p class="mb-0">새로운 연봉계약서가 도착했습니다. 내용을 확인하고 승인해주세요.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- 계약서 목록 -->
    <div class="card">
        <div class="list-group list-group-flush">
            @forelse($contracts as $contract)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-contract text-primary"></i>
                                <h5 class="mb-0">{{ $contract->contract_start_date->format('Y') }}년 연봉계약서</h5>
                                @if($contract->approved_at)
                                    <span class="badge bg-success">승인 완료</span>
                                @else
                                    <span class="badge bg-warning">승인 대기</span>
                                @endif
                            </div>
                            <div class="text-muted mt-1">
                                계약기간: {{ $contract->contract_start_date->format('Y.m.d') }} ~ 
                                         {{ $contract->contract_end_date->format('Y.m.d') }}
                            </div>
                            @if($contract->approved_at)
                            <div class="text-muted mt-1">
                                <i class="fas fa-check-circle me-1"></i>
                                승인일시: {{ $contract->approved_at->format('Y.m.d H:i') }}
                            </div>
                            @endif
                        </div>
                        <div>
                            @if(!$contract->approved_at)
                                <a href="{{ route('mypage.salary-contracts.show', $contract->id) }}" 
                                   class="btn btn-primary">
                                    확인 및 승인
                                </a>
                            @else
                                <a href="{{ route('mypage.salary-contracts.show', $contract->id) }}" 
                                   class="btn btn-secondary">
                                    상세보기
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item text-center py-4">
                    <p class="text-muted mb-0">등록된 연봉계약서가 없습니다.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.list-group-item {
    transition: background-color 0.2s;
}
.list-group-item:hover {
    background-color: #f8f9fa;
}
</style>
@endpush