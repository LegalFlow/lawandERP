@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">승인 대기 중</div>

                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <h4 class="alert-heading">회원가입이 완료되었습니다!</h4>
                        <p>관리자의 승인을 기다리고 있습니다. 승인이 완료되면 서비스를 이용하실 수 있습니다.</p>
                        <hr>
                        <p class="mb-0">승인이 완료되면 등록하신 이메일로 알림이 발송됩니다.</p>
                    </div>
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link">로그아웃</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 