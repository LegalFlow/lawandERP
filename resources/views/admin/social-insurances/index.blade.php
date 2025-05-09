@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- 플래시 메시지 표시 -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>사대보험 업로드</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="bi bi-file-earmark-excel"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- 검색 필터 -->
                    <form action="{{ route('admin.social-insurances.index') }}" method="GET" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="statement_date" class="form-label">고지년월</label>
                                <input type="month" name="statement_date" id="statement_date" class="form-control" 
                                    value="{{ request('statement_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="name" class="form-label">가입자명</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                    value="{{ request('name') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="resident_id" class="form-label">주민번호</label>
                                <input type="text" name="resident_id" id="resident_id" class="form-control" 
                                    value="{{ request('resident_id') }}">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-secondary w-100">검색</button>
                            </div>
                        </div>
                    </form>

                    <!-- 결과 테이블 -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>고지년월</th>
                                    <th>주민번호</th>
                                    <th>가입자명</th>
                                    <th>건강보험 고지보험료</th>
                                    <th>국민연금 결정보험료</th>
                                    <th>장기요양 고지보험료</th>
                                    <th>고용보험 고지보험료</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($socialInsurances as $insurance)
                                <tr>
                                    <td>{{ $insurance->statement_date->format('Y-m') }}</td>
                                    <td>{{ substr($insurance->resident_id, 0, 6) . '-' . substr($insurance->resident_id, 6) }}</td>
                                    <td>{{ $insurance->name }}</td>
                                    <td class="text-end">{{ number_format($insurance->health_insurance) }}</td>
                                    <td class="text-end">{{ number_format($insurance->national_pension) }}</td>
                                    <td class="text-end">{{ number_format($insurance->long_term_care) }}</td>
                                    <td class="text-end">{{ number_format($insurance->employment_insurance) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">데이터가 없습니다.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이지네이션 -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $socialInsurances->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 엑셀 업로드 모달 -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">사대보험 엑셀 업로드</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.social-insurances.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="statement_date_upload" class="form-label">고지년월</label>
                        <input type="month" class="form-control" id="statement_date_upload" name="statement_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="files" class="form-label">엑셀 파일 (최대 10개)</label>
                        <input type="file" class="form-control" id="files" name="files[]" accept=".xlsx,.xls,.csv" required multiple>
                        <div class="form-text">
                            여러 파일을 한 번에 선택할 수 있습니다. (건강보험, 국민연금, 고용보험 등)
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6 class="alert-heading">파일 형식 안내</h6>
                        <p class="mb-0">파일명에 따라 다음과 같이 처리됩니다:</p>
                        <ul class="mb-0">
                            <li><strong>Gungang</strong>: 건강보험과 장기요양보험료 업로드</li>
                            <li><strong>Yeonkum</strong>: 국민연금 업로드</li>
                            <li><strong>Goyong</strong>: 고용보험 업로드</li>
                        </ul>
                    </div>
                    
                    <div class="form-text text-muted mt-3">
                        <i class="fas fa-info-circle"></i> 선택한 파일은 순차적으로 처리됩니다. 각 파일은 이름에 따라 적절한 처리기로 전달됩니다.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">업로드</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 