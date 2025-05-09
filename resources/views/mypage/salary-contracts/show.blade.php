@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
   <!-- 헤더 영역 -->
   <div class="row mb-4">
       <div class="col">
           <div class="d-flex align-items-center">
               <a href="{{ route('mypage.salary-contracts.index') }}" class="btn btn-link p-0 me-3">
                   <i class="fas fa-arrow-left"></i>
               </a>
               <div>
                   <h3 class="mb-1">{{ $salaryContract->contract_start_date->format('Y') }}년 연봉계약서</h3>
                   <p class="text-muted mb-0">계약기간: {{ $salaryContract->contract_start_date->format('Y.m.d') }} ~ {{ $salaryContract->contract_end_date->format('Y.m.d') }}</p>
               </div>
           </div>
       </div>
   </div>

   <!-- 기본 정보 카드 -->
   <div class="card mb-4">
       <div class="card-header">
           <h5 class="card-title mb-0">기본 정보</h5>
       </div>
       <div class="card-body">
           <div class="row g-3">
               <div class="col-md-6">
                   <label class="form-label">소속팀</label>
                   <input type="text" class="form-control" value="{{ $salaryContract->user->member->task }}" disabled>
               </div>
               <div class="col-md-6">
                   <label class="form-label">직급</label>
                   <input type="text" class="form-control" value="{{ $salaryContract->position }}" disabled>
               </div>
               <div class="col-md-12">
                   <label class="form-label">기본급</label>
                   <input type="text" class="form-control" value="{{ number_format($salaryContract->base_salary) }}원" disabled>
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
           <div class="bg-light p-3 rounded">
               {!! nl2br(e($salaryContract->memo)) !!}
           </div>
       </div>
   </div>

   <!-- 승인 정보 및 승인 버튼 -->
   <div class="card">
       <div class="card-header">
           <h5 class="card-title mb-0">승인 정보</h5>
       </div>
       <div class="card-body">
           @if(!$salaryContract->approved_at)
               <div class="alert alert-info" role="alert">
                   <div class="d-flex">
                       <div class="flex-shrink-0">
                           <i class="fas fa-info-circle me-2"></i>
                       </div>
                       <div>
                           <h4 class="alert-heading">승인 대기 중</h4>
                           <p class="mb-0">연봉계약서 내용을 확인하시고 승인해주세요. 승인 후에는 취소할 수 없습니다.</p>
                       </div>
                   </div>
               </div>
               <form action="{{ route('mypage.salary-contracts.approve', $salaryContract->id) }}" method="POST">
                   @csrf
                   <div class="text-end">
                       <a href="{{ route('mypage.salary-contracts.index') }}" class="btn btn-secondary">취소</a>
                       <button type="submit" class="btn btn-primary ms-2">승인하기</button>
                   </div>
               </form>
           @else
               <div class="row g-3">
                   <div class="col-md-6">
                       <label class="form-label">승인 상태</label>
                       <div>
                           <span class="badge bg-success">승인 완료</span>
                       </div>
                   </div>
                   <div class="col-md-6">
                       <label class="form-label">승인 일시</label>
                       <input type="text" class="form-control" 
                              value="{{ $salaryContract->approved_at->format('Y-m-d H:i:s') }}" disabled>
                   </div>
               </div>
               <div class="text-end mt-3">
                   <a href="{{ route('mypage.salary-contracts.pdf', $salaryContract) }}" class="btn btn-outline-danger me-2">
                       <i class="fas fa-file-pdf"></i> PDF 다운로드
                   </a>
                   <a href="{{ route('mypage.salary-contracts.index') }}" class="btn btn-secondary">목록으로</a>
               </div>
           @endif
       </div>
   </div>
</div>
@endsection

@push('styles')
<style>
.bg-light {
   background-color: #f8f9fa;
}
</style>
@endpush