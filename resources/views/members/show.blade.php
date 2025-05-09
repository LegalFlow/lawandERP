@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $member->name }}님의 상세 정보</h1>
    <p><strong>직급:</strong> {{ $member->position }}</p>
    <p><strong>업무:</strong> {{ $member->task }}</p>
    <p><strong>소속:</strong> {{ $member->affiliation }}</p>
    <!-- 나머지 필드들도 동일하게 추가 -->
    <a href="{{ route('members.index') }}" class="btn btn-secondary mt-3">돌아가기</a>
</div>
@endsection
