@extends('layouts.app')

@section('content')
<div class="container">
    <h1>구성원 수정</h1>
    <form action="{{ route('members.update', $member->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">이름</label>
            <input type="text" name="name" class="form-control" value="{{ $member->name }}" required>
        </div>
        <div class="form-group">
            <label for="position">직급</label>
            <select name="position" class="form-control">
                <option value="사원" {{ $member->position == '사원' ? 'selected' : '' }}>사원</option>
                <option value="주임" {{ $member->position == '주임' ? 'selected' : '' }}>주임</option>
                <option value="대리" {{ $member->position == '대리' ? 'selected' : '' }}>대리</option>
                <option value="과장" {{ $member->position == '과장' ? 'selected' : '' }}>과장</option>
                <option value="팀장" {{ $member->position == '팀장' ? 'selected' : '' }}>팀장</option>
                <option value="실장" {{ $member->position == '실장' ? 'selected' : '' }}>실장</option>
                <option value="변호사" {{ $member->position == '변호사' ? 'selected' : '' }}>변호사</option>
                <option value="파트너" {{ $member->position == '파트너' ? 'selected' : '' }}>파트너</option>
                <option value="개발자" {{ $member->position == '개발자' ? 'selected' : '' }}>개발자</option>
                <option value="대표" {{ $member->position == '대표' ? 'selected' : '' }}>대표</option>
            </select>
        </div>
        <div class="form-group">
            <label for="task">업무</label>
            <select name="task" class="form-control">
                <option value="법률컨설팅팀" {{ $member->task == '법률컨설팅팀' ? 'selected' : '' }}>법률컨설팅팀</option>
                <option value="사건관리팀" {{ $member->task == '사건관리팀' ? 'selected' : '' }}>사건관리팀</option>
                <option value="개발팀" {{ $member->task == '개발팀' ? 'selected' : '' }}>개발팀</option>
                <option value="지원팀" {{ $member->task == '지원팀' ? 'selected' : '' }}>지원팀</option>
            </select>
        </div>
        <div class="form-group">
            <label for="affiliation">소속</label>
            <select name="affiliation" class="form-control">
                <option value="서울" {{ $member->affiliation == '서울' ? 'selected' : '' }}>서울</option>
                <option value="대전" {{ $member->affiliation == '대전' ? 'selected' : '' }}>대전</option>
                <option value="부산" {{ $member->affiliation == '부산' ? 'selected' : '' }}>부산</option>
            </select>
        </div>
        <div class="form-group">
            <label for="status">근무 상태</label>
            <select name="status" class="form-control">
                <option value="재직중" {{ $member->status == '재직중' ? 'selected' : '' }}>재직중</option>
                <option value="휴직중" {{ $member->status == '휴직중' ? 'selected' : '' }}>휴직중</option>
            </select>
        </div>
        <div class="form-group">
            <label for="authority">권한</label>
            <select name="authority" class="form-control">
                <option value="일반" {{ $member->authority == '일반' ? 'selected' : '' }}>일반</option>
                <option value="관리자" {{ $member->authority == '관리자' ? 'selected' : '' }}>관리자</option>
            </select>
        </div>
        <div class="form-group">
            <label for="years">년차</label>
            <input type="number" name="years" class="form-control" value="{{ $member->years }}">
        </div>
        <div class="form-group">
            <label for="working_days_per_week">주당 근무일수</label>
            <input type="number" name="working_days_per_week" class="form-control" value="{{ $member->working_days_per_week }}">
        </div>
        <div class="form-group">
            <label for="remote_days_per_week">주당 재택근무일수</label>
            <input type="number" name="remote_days_per_week" class="form-control" value="{{ $member->remote_days_per_week }}">
        </div>
        <div class="form-group">
            <label for="bank">은행</label>
            <input type="text" name="bank" class="form-control" value="{{ $member->bank }}">
        </div>
        <div class="form-group">
            <label for="account_number">계좌번호</label>
            <input type="text" name="account_number" class="form-control" value="{{ $member->account_number }}">
        </div>
        <div class="form-group">
            <label for="notes">기타 사항</label>
            <textarea name="notes" class="form-control">{{ $member->notes }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-3">수정</button>
    </form>
</div>
@endsection
