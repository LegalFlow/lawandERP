@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">급여명세서 수정</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.salary-statements.index') }}" class="btn btn-default">목록으로</a>
                    </div>
                </div>
                <form action="{{ route('admin.salary-statements.update', $salaryStatement) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <!-- 개인정보 영역 - 수정불가 영역 -->
                        <div class="card bg-light mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">개인정보</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>귀속년월</label>
                                            <input type="month" class="form-control" name="statement_date" value="{{ $salaryStatement->statement_date->format('Y-m') }}" required>
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

                        <!-- 급여 항목 영역 -->
                        <div class="row">
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
                                                <input type="text" name="base_salary" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->base_salary) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">식대</label>
                                                <input type="text" name="meal_allowance" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->meal_allowance) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">차량유지비</label>
                                                <input type="text" name="vehicle_allowance" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->vehicle_allowance) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">보육수당</label>
                                                <input type="text" name="child_allowance" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->child_allowance) }}">
                                            </div>
                                        </div>
                                        
                                        <!-- 두 번째 줄 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">상여금</label>
                                                <input type="text" name="bonus" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->bonus) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">성과급</label>
                                                <input type="text" name="performance_pay" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->performance_pay) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">연차수당</label>
                                                <input type="text" name="vacation_pay" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->vacation_pay) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">조정수당</label>
                                                <input type="text" name="adjustment_pay" 
                                                       class="form-control calc-total amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->adjustment_pay) }}">
                                            </div>
                                        </div>

                                        <!-- 세전총급여 줄 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">세전총급여</label>
                                                <input type="text" id="total_payment" class="form-control bg-light number-input" readonly>
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
                                                    <input type="text" id="net_payment" class="form-control bg-light number-input" 
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
                                                <input type="text" name="income_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->income_tax) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">지방소득세</label>
                                                <input type="text" name="local_income_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->local_income_tax) }}">
                                            </div>
                                        </div>

                                        <!-- 두 번째 줄: 국민연금, 건강보험, 장기요양, 고용보험 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">국민연금</label>
                                                <input type="text" name="national_pension" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->national_pension) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">건강보험</label>
                                                <input type="text" name="health_insurance" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->health_insurance) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">장기요양</label>
                                                <input type="text" name="long_term_care" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->long_term_care) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">고용보험</label>
                                                <input type="text" name="employment_insurance" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->employment_insurance) }}">
                                            </div>
                                        </div>

                                        <!-- 세 번째 줄: 기타공제액, 연말정산소득세, 연말정산지방소득세 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">취업 후 학자금 상환액</label>
                                                <input type="text" name="student_loan_repayment" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->student_loan_repayment) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">기타공제액</label>
                                                <input type="text" name="other_deductions" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->other_deductions) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">연말정산소득세</label>
                                                <input type="text" name="year_end_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->year_end_tax) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">연말정산지방소득세</label>
                                                <input type="text" name="year_end_local_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->year_end_local_tax) }}">
                                            </div>
                                        </div>

                                        <!-- 네 번째 줄: 건강보험료정산, 장기요양보험정산 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">건강보험료정산</label>
                                                <input type="text" name="health_insurance_adjustment" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->health_insurance_adjustment) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">장기요양보험정산</label>
                                                <input type="text" name="long_term_adjustment" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->long_term_adjustment) }}">
                                            </div>
                                        </div>

                                        <!-- 다섯 번째 줄: 중도정산소득세, 중도정산지방소득세, 농특세 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">중도정산소득세</label>
                                                <input type="text" name="interim_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->interim_tax) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">중도정산지방소득세</label>
                                                <input type="text" name="interim_local_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->interim_local_tax) }}">
                                            </div>
                                            <div class="salary-item">
                                                <label class="salary-label">농특세</label>
                                                <input type="text" name="agriculture_tax" class="form-control calc-deduction amount-input number-input" 
                                                       value="{{ number_format($salaryStatement->agriculture_tax) }}">
                                            </div>
                                        </div>

                                        <!-- 여섯 번째 줄: 공제총액 -->
                                        <div class="salary-row">
                                            <div class="salary-item">
                                                <label class="salary-label">공제총액</label>
                                                <input type="text" id="total_deduction" class="form-control bg-light number-input" 
                                                       value="{{ number_format($salaryStatement->total_deduction) }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 메모 영역 -->
                        <div class="form-group mt-4">
                            <label>메모</label>
                            <textarea name="memo" class="form-control" rows="3">{{ $salaryStatement->memo }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">저장</button>
                        <a href="{{ route('admin.salary-statements.index') }}" class="btn btn-default">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 천단위 구분기호 포맷팅 함수
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // 쉼표 제거 함수
    function removeCommas(str) {
        return str.replace(/,/g, '');
    }

    // 금액 입력 필드에 자동 쉼표 추가 (음수 허용)
    $('.amount-input').on('input', function() {
        let value = $(this).val().replace(/[^\d\-]/g, ''); // 숫자와 마이너스 기호만 허용
        
        // 마이너스 기호는 맨 앞에만 허용
        if (value.startsWith('-')) {
            value = '-' + value.substring(1).replace(/\-/g, '');
        } else {
            value = value.replace(/\-/g, '');
        }
        
        // 숫자 부분에만 쉼표 적용
        if (value.startsWith('-')) {
            const numPart = value.substring(1);
            $(this).val('-' + numberWithCommas(numPart));
        } else {
            $(this).val(numberWithCommas(value));
        }
    });

    function calculateTotals() {
        // 세전총급여 계산
        let totalPayment = 0;
        $('.calc-total').each(function() {
            totalPayment += Number(removeCommas($(this).val())) || 0;
        });
        $('#total_payment').val(numberWithCommas(totalPayment));

        // 공제총액 계산
        let totalDeduction = 0;
        $('.calc-deduction').each(function() {
            totalDeduction += Number(removeCommas($(this).val())) || 0;
        });
        $('#total_deduction').val(numberWithCommas(totalDeduction));

        // 실지급액 계산
        let netPayment = totalPayment - totalDeduction;
        $('#net_payment').val(numberWithCommas(netPayment));
    }

    // 입력값 변경 시 자동 계산
    $('.calc-total, .calc-deduction').on('input', calculateTotals);

    // 폼 제출 전 쉼표 제거
    $('form').on('submit', function() {
        $('.amount-input').each(function() {
            $(this).val(removeCommas($(this).val()));
        });
    });

    // 초기 계산
    calculateTotals();
});
</script>
@endpush

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
