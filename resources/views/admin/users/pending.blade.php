@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">사용자 관리</h2>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- 승인 대기 사용자 -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">승인 대기 사용자</h3>
        </div>
        <div class="card-body">
            @if($pendingUsers->isEmpty())
                <p class="text-muted">승인 대기 중인 사용자가 없습니다.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['pending_sort' => 'name', 'pending_direction' => request('pending_direction') == 'asc' && request('pending_sort') == 'name' ? 'desc' : 'asc']) }}">
                                        이름
                                        @if(request('pending_sort') == 'name')
                                            <i class="fas fa-sort-{{ request('pending_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['pending_sort' => 'email', 'pending_direction' => request('pending_direction') == 'asc' && request('pending_sort') == 'email' ? 'desc' : 'asc']) }}">
                                        이메일
                                        @if(request('pending_sort') == 'email')
                                            <i class="fas fa-sort-{{ request('pending_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['pending_sort' => 'created_at', 'pending_direction' => request('pending_direction') == 'asc' && request('pending_sort') == 'created_at' ? 'desc' : 'asc']) }}">
                                        등록일
                                        @if(request('pending_sort') == 'created_at')
                                            <i class="fas fa-sort-{{ request('pending_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>상세정보</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#userModal{{ $user->id }}">
                                        상세정보
                                    </button>
                                </td>
                                <td>
                                    <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">승인</button>
                                    </form>
                                    <form action="{{ route('admin.users.reject', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">거부</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $pendingUsers->appends(request()->except('page'))->links() }}
            @endif
        </div>
    </div>

    <!-- 승인된 사용자 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">승인된 사용자</h3>
        </div>
        <div class="card-body">
            @if($approvedUsers->isEmpty())
                <p class="text-muted">승인된 사용자가 없습니다.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['approved_sort' => 'name', 'approved_direction' => request('approved_direction') == 'asc' && request('approved_sort') == 'name' ? 'desc' : 'asc']) }}">
                                        이름
                                        @if(request('approved_sort') == 'name')
                                            <i class="fas fa-sort-{{ request('approved_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['approved_sort' => 'email', 'approved_direction' => request('approved_direction') == 'asc' && request('approved_sort') == 'email' ? 'desc' : 'asc']) }}">
                                        이메일
                                        @if(request('approved_sort') == 'email')
                                            <i class="fas fa-sort-{{ request('approved_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['approved_sort' => 'created_at', 'approved_direction' => request('approved_direction') == 'asc' && request('approved_sort') == 'created_at' ? 'desc' : 'asc']) }}">
                                        등록일
                                        @if(request('approved_sort') == 'created_at')
                                            <i class="fas fa-sort-{{ request('approved_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['approved_sort' => 'approved_at', 'approved_direction' => request('approved_direction') == 'asc' && request('approved_sort') == 'approved_at' ? 'desc' : 'asc']) }}">
                                        승인일
                                        @if(request('approved_sort') == 'approved_at')
                                            <i class="fas fa-sort-{{ request('approved_direction') == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>승인자</th>
                                <th>상세정보</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($approvedUsers as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $user->approved_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $user->approvedBy->name ?? '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#userModal{{ $user->id }}">
                                        상세정보
                                    </button>
                                </td>
                                <td>
                                    <form action="{{ route('admin.users.revoke', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm" 
                                                onclick="return confirm('정말로 이 사용자의 승인을 취소하시겠습니까?')">
                                            승인 취소
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $approvedUsers->appends(request()->except('page'))->links() }}
            @endif
        </div>
    </div>
    
    <!-- 모달 섹션 - 테이블 바깥에 위치 -->
    @foreach($pendingUsers as $user)
    <!-- 사용자 상세정보 모달 -->
    <div class="modal fade" id="userModal{{ $user->id }}" tabindex="-1" aria-labelledby="userModalLabel{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel{{ $user->id }}">{{ $user->name }} 상세정보</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>기본 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>이름</th>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <th>이메일</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>주민등록번호</th>
                                    <td>{{ $user->resident_id_front }}-{{ $user->resident_id_back }}</td>
                                </tr>
                                <tr>
                                    <th>휴대전화번호</th>
                                    <td>{{ $user->phone_number }}</td>
                                </tr>
                                <tr>
                                    <th>입사일자</th>
                                    <td>{{ $user->join_date ? $user->join_date->format('Y-m-d') : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>계좌 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>은행명</th>
                                    <td>{{ $user->bank }}</td>
                                </tr>
                                <tr>
                                    <th>계좌번호</th>
                                    <td>{{ $user->account_number }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>주소 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>우편번호</th>
                                    <td>{{ $user->postal_code }}</td>
                                </tr>
                                <tr>
                                    <th>기본주소</th>
                                    <td>{{ $user->address_main }}</td>
                                </tr>
                                <tr>
                                    <th>상세주소</th>
                                    <td>{{ $user->address_detail }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6>첨부 파일</h6>
                            @if($user->documents->isEmpty())
                                <p class="text-muted">첨부된 파일이 없습니다.</p>
                            @else
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>파일명</th>
                                            <th>파일 유형</th>
                                            <th>파일 크기</th>
                                            <th>업로드 일시</th>
                                            <th>다운로드</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->documents as $document)
                                        <tr>
                                            <td>{{ $document->original_filename }}</td>
                                            <td>{{ strtoupper($document->file_type) }}</td>
                                            <td>{{ number_format($document->file_size / 1024, 2) }} KB</td>
                                            <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <a href="{{ route('admin.users.document.download', $document) }}" class="btn btn-primary btn-sm">
                                                    다운로드
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <form action="{{ route('admin.users.approve', $user) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">승인</button>
                    </form>
                    <form action="{{ route('admin.users.reject', $user) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">거부</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    
    @foreach($approvedUsers as $user)
    <!-- 사용자 상세정보 모달 -->
    <div class="modal fade" id="userModal{{ $user->id }}" tabindex="-1" aria-labelledby="userModalLabel{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel{{ $user->id }}">{{ $user->name }} 상세정보</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>기본 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>이름</th>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <th>이메일</th>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <th>주민등록번호</th>
                                    <td>{{ $user->resident_id_front }}-{{ $user->resident_id_back }}</td>
                                </tr>
                                <tr>
                                    <th>휴대전화번호</th>
                                    <td>{{ $user->phone_number }}</td>
                                </tr>
                                <tr>
                                    <th>입사일자</th>
                                    <td>{{ $user->join_date ? $user->join_date->format('Y-m-d') : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>계좌 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>은행명</th>
                                    <td>{{ $user->bank }}</td>
                                </tr>
                                <tr>
                                    <th>계좌번호</th>
                                    <td>{{ $user->account_number }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6>주소 정보</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>우편번호</th>
                                    <td>{{ $user->postal_code }}</td>
                                </tr>
                                <tr>
                                    <th>기본주소</th>
                                    <td>{{ $user->address_main }}</td>
                                </tr>
                                <tr>
                                    <th>상세주소</th>
                                    <td>{{ $user->address_detail }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6>첨부 파일</h6>
                            @if($user->documents->isEmpty())
                                <p class="text-muted">첨부된 파일이 없습니다.</p>
                            @else
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>파일명</th>
                                            <th>파일 유형</th>
                                            <th>파일 크기</th>
                                            <th>업로드 일시</th>
                                            <th>다운로드</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($user->documents as $document)
                                        <tr>
                                            <td>{{ $document->original_filename }}</td>
                                            <td>{{ strtoupper($document->file_type) }}</td>
                                            <td>{{ number_format($document->file_size / 1024, 2) }} KB</td>
                                            <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <a href="{{ route('admin.users.document.download', $document) }}" class="btn btn-primary btn-sm">
                                                    다운로드
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush 