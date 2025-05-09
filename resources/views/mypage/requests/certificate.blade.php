<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>재직증명서</title>
    <style>
        @font-face {
            font-family: 'NanumGothic';
            src: url({{ storage_path('fonts/NanumGothic-Regular.ttf') }}) format("truetype");
            font-weight: normal;
        }
        @font-face {
            font-family: 'NanumGothic';
            src: url({{ storage_path('fonts/NanumGothic-Bold.ttf') }}) format("truetype");
            font-weight: bold;
        }
        body {
            font-family: 'NanumGothic', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .request-number {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 10px;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            margin-top: 50px;
        }
        .section-title {
            font-weight: bold;
            font-size: 16px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table th {
            width: 25%;
            text-align: left;
            padding: 8px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
        .info-table td {
            width: 75%;
            padding: 8px;
            border: 1px solid #ddd;
        }
        .statement {
            margin-top: 40px;
            margin-bottom: 40px;
            text-align: center;
            font-size: 14px;
        }
        .date {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
        }
        .signature {
            margin-top: 50px;
            text-align: center;
            position: relative;
        }
        .signature-text {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 8px;
        }
        .stamp-container {
            position: absolute;
            top: -20px;
            right: 3%;
            width: 120px;
            height: 120px;
        }
        .stamp-image {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div class="request-number">신청서 번호: {{ $request->request_number }}</div>
    
    <div class="title">재 직 증 명 서</div>
    
    <div class="section-title">인사정보</div>
    <table class="info-table">
        <tr>
            <th>이름</th>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <th>주민등록번호</th>
            <td>{{ $residentId }}</td>
        </tr>
        <tr>
            <th>휴대전화번호</th>
            <td>{{ $phoneNumber }}</td>
        </tr>
        <tr>
            <th>이메일</th>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <th>기본주소</th>
            <td>{{ $user->address_main }}</td>
        </tr>
        <tr>
            <th>상세주소</th>
            <td>{{ $user->address_detail }}</td>
        </tr>
        <tr>
            <th>소속</th>
            <td>{{ $affiliation }}</td>
        </tr>
        <tr>
            <th>업무</th>
            <td>{{ $member->task }}</td>
        </tr>
        <tr>
            <th>직급</th>
            <td>{{ $member->position }}</td>
        </tr>
        <tr>
            <th>입사일</th>
            <td>{{ $user->join_date ? $user->join_date->format('Y년 m월 d일') : '-' }}</td>
        </tr>
    </table>
    
    <div class="section-title">회사정보</div>
    <table class="info-table">
        <tr>
            <th>회사명</th>
            <td>{{ $companyInfo['회사명'] }}</td>
        </tr>
        <tr>
            <th>사업자번호</th>
            <td>{{ $companyInfo['사업자번호'] }}</td>
        </tr>
        <tr>
            <th>주사무소 주소</th>
            <td>서울특별시 강남구 논현로87길 25, HB타워 3층</td>
        </tr>
        <tr>
            <th>주소</th>
            <td>{{ $companyInfo['주소'] }}</td>
        </tr>
    </table>
    
    <div class="statement">
        위 근로자는 자사에 재직중임을 증명합니다.
    </div>
    
    <div class="date">
        {{ $request->processed_at ? $request->processed_at->format('Y년 m월 d일') : now()->format('Y년 m월 d일') }}
    </div>
    
    <div class="signature">
        <div class="signature-text">법 무 법 인 로 앤 대 표 변 호 사 인</div>
        @if(isset($useStamp) && $useStamp)
        <div class="stamp-container">
            <img class="stamp-image" src="data:image/png;base64,{{ base64_encode(file_get_contents(resource_path('views/mypage/requests/lawand_stamp.png'))) }}" alt="직인">
        </div>
        @endif
    </div>
</body>
</html> 