@extends('layouts.app')

@section('content')
<div class="container">
    <h1>구성원 추가</h1>
    <form action="{{ route('members.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">이름</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="position">직급</label>
            <select name="position" class="form-control">
                <option value="사원">사원</option>
                <option value="주임">주임</option>
                <option value="대리">대리</option>
                <option value="과장">과장</option>
                <option value="팀장">팀장</option>
                <option value="실장">실장</option>
                <option value="변호사">변호사</option>
                <option value="파트너">파트너</option>
                <option value="개발자">개발자</option>
                <option value="대표">대표</option>
            </select>
        </div>
        <div class="form-group">
            <label for="task">업무</label>
            <select name="task" class="form-control">
                <option value="법률컨설팅팀">법률컨설팅팀</option>
                <option value="사건관리팀">사건관리팀</option>
                <option value="개발팀">개발팀</option>
                <option value="지원팀">지원팀</option>
            </select>
        </div>
        <div class="form-group">
            <label for="affiliation">소속</label>
            <select name="affiliation" class="form-control">
                <option value="서울">서울</option>
                <option value="대전">대전</option>
                <option value="부산">부산</option>
            </select>
        </div>
        <div class="form-group">
            <label for="status">근무 상태</label>
            <select name="status" class="form-control">
                <option value="재직중">재직중</option>
                <option value="휴직중">휴직중</option>
            </select>
        </div>
        <div class="form-group">
            <label for="authority">권한</label>
            <select name="authority" class="form-control">
                <option value="일반">일반</option>
                <option value="관리자">관리자</option>
            </select>
        </div>
        <div class="form-group">
            <label for="years">년차</label>
            <input type="number" class="form-control" id="years" name="years">
        </div>
        <div class="form-group">
            <label for="working_days_per_week">주당 근무일수</label>
            <input type="number" class="form-control" id="working_days_per_week" name="working_days_per_week">
        </div>
        <div class="form-group">
            <label for="remote_days_per_week">주당 재택근무일수</label>
            <input type="number" class="form-control" id="remote_days_per_week" name="remote_days_per_week">
        </div>
        <div class="form-group">
            <label for="bank">은행</label>
            <input type="text" class="form-control" id="bank" name="bank">
        </div>
        <div class="form-group">
            <label for="account_number">계좌번호</label>
            <input type="text" class="form-control" id="account_number" name="account_number">
        </div>
        <div class="form-group">
            <label for="notes">기타 사항</label>
            <textarea class="form-control" id="notes" name="notes"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">저장</button>
    </form>
</div>
@endsection
