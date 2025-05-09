@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">급여명세서 상세</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.salary-statements.pdf', $salaryStatement) }}" 
                           class="btn btn-outline-danger">
                            <i class="fas fa-file-pdf"></i> PDF 다운로드
                        </a>
                        <a href="{{ route('admin.salary-statements.edit', $salaryStatement) }}" 
                           class="btn btn-outline-primary ml-2">
                            <i class="fas fa-edit"></i> 수정하기
                        </a>
                        <a href="{{ route('admin.salary-statements.index') }}" 
                           class="btn btn-outline-secondary ml-2">
                            <i class="fas fa-list"></i> 목록으로
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- 승인 상태 표시 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert {{ $salaryStatement->approved_at ? 'alert-success' : 'alert-warning' }}">
                                @if($salaryStatement->approved_at)
                                    <strong>승인완료</strong> | 
                                    승인자: {{ $salaryStatement->approver ? $salaryStatement->approver->name : '알 수 없음' }} | 
                                    승인일시: {{ $salaryStatement->approved_at->format('Y-m-d H:i') }}
                                @else
                                    <strong>미승인</strong>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 개인정보 영역 -->
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">개인정보</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>귀속년월</label>
                                        <input type="text" class="form-control bg-light" value="{{ $salaryStatement->statement_date->format('Y-m') }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>이름</label>
                                        <input type="text" class="form-control bg-light" value="{{ $salaryStatement->user ? $salaryStatement->user->name : $salaryStatement->name }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>직급</label>
                                        <input type="text" class="form-control bg-light" value="{{ $salaryStatement->user ? $salaryStatement->user->member->position : $salaryStatement->position }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>업무</label>
                                        <input type="text" class="form-control bg-light" value="{{ $salaryStatement->user ? $salaryStatement->user->member->task : '-' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>소속</label>
                                        <input type="text" class="form-control bg-light" value="{{ $salaryStatement->user ? $salaryStatement->user->member->affiliation : $salaryStatement->affiliation }}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 급여/공제 항목 영역 -->
                    <div class="row">
                        <!-- 급여 항목 + 최종급여 영역 -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">급여 항목</h5>
                                </div>
                                <div class="card-body">
                                    <!-- 첫 번째 줄 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">기본급</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->base_salary) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">식대</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->meal_allowance) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">차량유지비</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->vehicle_allowance) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">보육수당</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->child_allowance) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 두 번째 줄 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">상여금</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->bonus) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">성과급</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->performance_pay) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">연차수당</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->vacation_pay) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">조정수당</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->adjustment_pay) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 세전총급여 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">세전총급여</label>
                                            <input type="text" class="form-control bg-light number-input" 
                                                   value="{{ number_format($salaryStatement->total_payment) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 최종급여 카드 -->
                            <div class="final-salary-wrapper">
                                <div class="card bg-light final-salary-card">
                                    <div class="card-header">
                                        <h5 class="mb-0">최종 급여</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">실지급액</label>
                                                <input type="text" class="form-control bg-light number-input" 
                                                       value="{{ number_format($salaryStatement->net_payment) }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 공제 항목 영역 -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">공제 항목</h5>
                                </div>
                                <div class="card-body">
                                    <!-- 첫 번째 줄: 소득세, 지방소득세 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->income_tax) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">지방소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->local_income_tax) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 두 번째 줄: 국민연금, 건강보험, 장기요양, 고용보험 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">국민연금</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->national_pension) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">건강보험</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->health_insurance) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">장기요양</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->long_term_care) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">고용보험</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->employment_insurance) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 세 번째 줄: 기타공제액, 연말정산소득세, 연말정산지방소득세 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">취업 후 학자금 상환액</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->student_loan_repayment) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">기타공제액</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->other_deductions) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">연말정산소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->year_end_tax) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">연말정산지방소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->year_end_local_tax) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 네 번째 줄: 건강보험료정산, 장기요양보험정산 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">건강보험료정산</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->health_insurance_adjustment) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">장기요양보험정산</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->long_term_adjustment) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 다섯 번째 줄: 중도정산소득세, 중도정산지방소득세, 농특세 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">중도정산소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->interim_tax) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">중도정산지방소득세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->interim_local_tax) }}" readonly>
                                        </div>
                                        <div class="salary-item">
                                            <label class="salary-label">농특세</label>
                                            <input type="text" class="form-control number-input" 
                                                   value="{{ number_format($salaryStatement->agriculture_tax) }}" readonly>
                                        </div>
                                    </div>

                                    <!-- 여섯 번째 줄: 공제총액 -->
                                    <div class="salary-row">
                                        <div class="salary-item">
                                            <label class="salary-label">공제총액</label>
                                            <input type="text" class="form-control bg-light number-input" 
                                                   value="{{ number_format($salaryStatement->total_deduction) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 메모 영역 -->
                    @if($salaryStatement->memo)
                    <div class="form-group mt-4">
                        <label>메모</label>
                        <div class="p-3 bg-light">
                            {!! nl2br(e($salaryStatement->memo)) !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.number-input {
    width: 120px !important;
    text-align: right;
}

.salary-label {
    text-align: left;
    white-space: nowrap;
    display: block;
    margin-bottom: 0.3rem;
}

.salary-row {
    display: flex;
    margin-bottom: 1rem;
    gap: 1.5rem;
}

.salary-item {
    display: flex;
    flex-direction: column;
}

.final-salary-card {
    height: 214px !important;
    margin-bottom: 0 !important;
}

.final-salary-wrapper {
    margin-top: auto;
}
</style>
@endpush
