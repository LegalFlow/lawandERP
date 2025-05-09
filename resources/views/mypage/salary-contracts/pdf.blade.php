<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>연봉계약서</title>
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
        .contract-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .contract-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .contract-table th, .contract-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        .contract-table th {
            background-color: #f5f5f5;
            text-align: center;
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
        <h1>연봉계약서</h1>
        <p>{{ $salaryContract->contract_start_date->format('Y') }}년도</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">이름</span>
            <span>{{ $salaryContract->user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">직급</span>
            <span>{{ $salaryContract->position }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">업무</span>
            <span>{{ $salaryContract->user->member->task }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">소속</span>
            <span>{{ $salaryContract->user->member->affiliation }}</span>
        </div>
    </div>

    <div class="contract-section">
        <table class="contract-table">
            <tr>
                <th>계약 항목</th>
                <th>내용</th>
            </tr>
            <tr>
                <td>기본급</td>
                <td>{{ number_format($salaryContract->base_salary) }}원</td>
            </tr>
            <tr>
                <td>계약 시작일</td>
                <td>{{ $salaryContract->contract_start_date->format('Y년 m월 d일') }}</td>
            </tr>
            <tr>
                <td>계약 종료일</td>
                <td>{{ $salaryContract->contract_end_date->format('Y년 m월 d일') }}</td>
            </tr>
            <tr>
                <td>작성일자</td>
                <td>{{ $salaryContract->created_date->format('Y년 m월 d일') }}</td>
            </tr>
            <tr>
                <td>작성자</td>
                <td>{{ $salaryContract->creator->name }}</td>
            </tr>
        </table>
    </div>

    @if($salaryContract->memo)
    <div class="info-section">
        <div class="info-label">메모</div>
        <div>{!! nl2br(e($salaryContract->memo)) !!}</div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-text">법무법인 로앤 대표변호사</div>
    </div>
</body>
</html> 