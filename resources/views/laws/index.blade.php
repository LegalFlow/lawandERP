@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4"></h1>

    <!-- 필터 섹션 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('laws.index') }}" class="row g-3">
                <!-- 텍스트 검색 -->
                <div class="col-md-4">
                    <label class="form-label">검색어</label>
                    <input type="text" class="form-control" name="search_text" 
                           value="{{ request('search_text') }}" placeholder="제목 또는 내용 검색">
                </div>

                <!-- 기간 필터 -->
                <div class="col-md-6">
                    <label class="form-label">기간</label>
                    <div class="input-group">
                        <select class="form-select" name="date_type" style="max-width: 150px;">
                            @foreach($dateTypes as $value => $label)
                                <option value="{{ $value }}" {{ request('date_type') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date" class="form-control" name="start_date" 
                               value="{{ request('start_date', date('Y-m-01')) }}">
                        <span class="input-group-text">~</span>
                        <input type="date" class="form-control" name="end_date" 
                               value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <!-- 시행여부 필터 -->
                <div class="col-md-2">
                    <label class="form-label">시행여부</label>
                    <select class="form-select" name="status">
                        <option value="">전체</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 버튼 그룹 -->
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">검색</button>
                    <a href="{{ route('laws.index') }}" class="btn btn-secondary">초기화</a>
                </div>
            </form>
        </div>
    </div>

    <!-- 관리자용 신규등록 버튼 -->
    @if($isAdmin)
    <div class="mb-3">
        <a href="{{ route('laws.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>신규등록
        </a>
    </div>
    @endif

    <!-- 리스트 테이블 -->
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 120px">등록일</th>
                        <th>제목</th>
                        <th style="width: 120px">시행일</th>
                        <th style="width: 100px">시행여부</th>
                        <th style="width: 120px">폐기일</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($laws as $law)
                        <tr>
                            <td>{{ $law->registration_date->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('laws.show', $law) }}" class="text-decoration-none">
                                    {{ $law->title }}
                                    @if($law->registration_date->diffInDays(now()) < 3)
                                        <i class="bi bi-stars text-danger ms-1" style="font-size: 0.8rem;" title="최근 3일 이내 등록"></i>
                                    @endif
                                </a>
                            </td>
                            <td>{{ $law->enforcement_date->format('Y-m-d') }}</td>
                            <td>
                                @if($law->status === '시행중')
                                    <span class="badge bg-success">시행중</span>
                                @else
                                    <span class="badge bg-danger">폐기</span>
                                @endif
                            </td>
                            <td>{{ $law->abolition_date ? $law->abolition_date->format('Y-m-d') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- 페이지네이션 -->
            <div class="d-flex justify-content-center mt-4">
                {{ $laws->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .table th, .table td {
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .form-control, .form-select, .btn {
        font-size: 0.85rem;
    }

    .badge {
        font-size: 0.85rem;
        padding: 0.35em 0.65em;
    }
    .table td {
        vertical-align: middle;
    }
</style>
@endsection