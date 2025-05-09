<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>급여명세서</title>
    <style>
        @font-face {
            font-family: 'NanumGothic';
            src: url({{ storage_path('fonts/NanumGothic-Regular.ttf') }}) format("truetype");
            font-weight: normal;
        }
        body {
            font-family: 'NanumGothic', sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-section {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
        }
        .info-row {
            margin-bottom: 5px;
        }
        .info-label {
            display: inline-block;
            width: 100px;
            font-weight: bold;
        }
        .salary-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .salary-table th, .salary-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: right;
        }
        .salary-table th {
            background-color: #f5f5f5;
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .signature-section {
            margin-top: 50px;
            text-align: right;
            padding-right: 50px;
        }
        
        .signature-text {
            font-size: 18px;
            font-weight: bold;
            font-family: 'NanumGothic', sans-serif;
        }
        
        title {
            font-family: 'NanumGothic', sans-serif;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>급여명세서</h1>
        <p>{{ $salaryStatement->statement_date->format('Y년 m월') }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">이름</span>
            <span>{{ $salaryStatement->user ? $salaryStatement->user->name : $salaryStatement->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">직급</span>
            <span>{{ $salaryStatement->user ? $salaryStatement->user->member->position : $salaryStatement->position }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">업무</span>
            <span>{{ $salaryStatement->user ? $salaryStatement->user->member->task : '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">소속</span>
            <span>{{ $salaryStatement->user ? $salaryStatement->user->member->affiliation : $salaryStatement->affiliation }}</span>
        </div>
    </div>

    <div class="salary-section">
        <table class="salary-table">
            <tr>
                <th colspan="4">급여 항목</th>
            </tr>
            <tr>
                <th>기본급</th>
                <th>식대</th>
                <th>차량유지비</th>
                <th>보육수당</th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->base_salary) }}</td>
                <td>{{ number_format($salaryStatement->meal_allowance) }}</td>
                <td>{{ number_format($salaryStatement->vehicle_allowance) }}</td>
                <td>{{ number_format($salaryStatement->child_allowance) }}</td>
            </tr>
            <tr>
                <th>상여금</th>
                <th>성과급</th>
                <th>연차수당</th>
                <th>조정수당</th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->bonus) }}</td>
                <td>{{ number_format($salaryStatement->performance_pay) }}</td>
                <td>{{ number_format($salaryStatement->vacation_pay) }}</td>
                <td>{{ number_format($salaryStatement->adjustment_pay) }}</td>
            </tr>
            <tr class="total-row">
                <th colspan="3">세전총급여</th>
                <td>{{ number_format($salaryStatement->total_payment) }}</td>
            </tr>
        </table>

        <table class="salary-table">
            <tr>
                <th colspan="4">공제 항목</th>
            </tr>
            <tr>
                <th>소득세</th>
                <th>지방소득세</th>
                <th>국민연금</th>
                <th>건강보험</th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->income_tax) }}</td>
                <td>{{ number_format($salaryStatement->local_income_tax) }}</td>
                <td>{{ number_format($salaryStatement->national_pension) }}</td>
                <td>{{ number_format($salaryStatement->health_insurance) }}</td>
            </tr>
            <tr>
                <th>장기요양</th>
                <th>고용보험</th>
                <th>기타공제액</th>
                <th>연말정산소득세</th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->long_term_care) }}</td>
                <td>{{ number_format($salaryStatement->employment_insurance) }}</td>
                <td>{{ number_format($salaryStatement->other_deductions) }}</td>
                <td>{{ number_format($salaryStatement->year_end_tax) }}</td>
            </tr>
            <tr>
                <th>취업 후 학자금 상환액</th>
                <th>연말정산지방소득세</th>
                <th>건강보험료정산</th>
                <th>장기요양보험정산</th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->student_loan_repayment) }}</td>
                <td>{{ number_format($salaryStatement->year_end_local_tax) }}</td>
                <td>{{ number_format($salaryStatement->health_insurance_adjustment) }}</td>
                <td>{{ number_format($salaryStatement->long_term_adjustment) }}</td>
            </tr>
            <tr>
                <th>중도정산소득세</th>
                <th>중도정산지방소득세</th>
                <th>농특세</th>
                <th></th>
            </tr>
            <tr>
                <td>{{ number_format($salaryStatement->interim_tax) }}</td>
                <td>{{ number_format($salaryStatement->interim_local_tax) }}</td>
                <td>{{ number_format($salaryStatement->agriculture_tax) }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <th colspan="3">공제총액</th>
                <td>{{ number_format($salaryStatement->total_deduction) }}</td>
            </tr>
        </table>

        <table class="salary-table">
            <tr class="total-row">
                <th colspan="3">실지급액</th>
                <td>{{ number_format($salaryStatement->net_payment) }}</td>
            </tr>
        </table>
    </div>

    @if($salaryStatement->memo)
    <div class="info-section">
        <div class="info-label">메모</div>
        <div>{!! nl2br(e($salaryStatement->memo)) !!}</div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-text">법무법인 로앤 대표변호사</div>
    </div>
</body>
</html>