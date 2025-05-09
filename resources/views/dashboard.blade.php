@extends('layouts.app')

@section('content')
<div class="container-fluid">
    
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    
    <!-- Retool 대시보드 iframe 추가 -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body p-0">
                    <iframe 
                        src="https://legalflow.retool.com/embedded/public/c01124d3-abee-4f72-b417-0390512aa211" 
                        width="100%" 
                        height="800px"
                        frameborder="0"
                        style="border: none;"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 알림 자동 닫기
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(function() {
            alert.remove();
        }, 5000);
    }
});
</script>
@endpush
@endsection
