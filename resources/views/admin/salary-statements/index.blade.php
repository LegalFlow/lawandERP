@extends('layouts.app')

@section('content')
<div class="container-fluid">
    
    <!-- 기존 카드 영역 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">급여명세서 관리</h3>
                    <div class="d-flex gap-2">
                        <!-- 좌측 버튼 그룹 -->
                        <div class="me-auto">
                            <button type="button" class="btn btn-primary btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                                일괄생성
                            </button>
                            <button type="button" class="btn btn-primary btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#createModal">
                                직접생성
                            </button>
                        </div>
                        
                        <!-- 우측 버튼 그룹 -->
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-dark btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#socialInsuranceModal">
                                사대보험
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#performanceModal">
                                성과금
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#excelDownloadModal">
                                Excel Download
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm fs-7" data-bs-toggle="modal" data-bs-target="#processModal">
                                Process
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>귀속년월</th>
                                <th>이름</th>
                                <th>직급</th>
                                <th>업무</th>
                                <th>지역</th>
                                <th class="text-right">세전총급여</th>
                                <th class="text-right">공제총액</th>
                                <th class="text-right">실지급액</th>
                                <th class="text-center">승인상태</th>
                                <th>승인일시</th>
                                <th>작성일자</th>
                                <th class="text-center" style="width: 150px;">액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statements as $statement)
                            <tr class="clickable-row" data-href="{{ route('admin.salary-statements.show', $statement->id) }}" style="cursor: pointer;">
                                <td>{{ $statement->statement_date->format('Y-m') }}</td>
                                <td>{{ $statement->user ? $statement->user->name : $statement->name }}</td>
                                <td>{{ $statement->user ? $statement->user->member->position : $statement->position }}</td>
                                <td>{{ $statement->user ? $statement->user->member->task : '-' }}</td>
                                <td>{{ $statement->user ? $statement->user->member->affiliation : $statement->affiliation }}</td>
                                <td class="text-right">{{ number_format($statement->total_payment) }}</td>
                                <td class="text-right">{{ number_format($statement->total_deduction) }}</td>
                                <td class="text-right">{{ number_format($statement->net_payment) }}</td>
                                <td class="text-center">
                                    @if($statement->approved_at)
                                        <span class="badge bg-success">승인완료</span>
                                    @else
                                        <span class="badge bg-warning">승인대기</span>
                                    @endif
                                </td>
                                <td>{{ $statement->approved_at ? $statement->approved_at->format('Y-m-d H:i') : '-' }}</td>
                                <td>{{ $statement->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-center action-column">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.salary-statements.edit', $statement->id) }}" 
                                           class="btn btn-sm btn-outline-secondary">수정</a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-statement" 
                                                data-id="{{ $statement->id }}">삭제</button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">
                    {{ $statements->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 일괄생성 모달 -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkCreateModalLabel">급여명세서 일괄생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>귀속년월</label>
                    <input type="month" class="form-control" id="statementDate">
                </div>
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="bulkSelectAll">
                                </th>
                                <th style="width: 50px;">순번</th>
                                <th style="width: 120px;">이름</th>
                                <th style="width: 100px;">직급</th>
                                <th style="width: 120px;">지역</th>
                                <th style="width: 100px;">기본급</th>
                                <th style="width: 100px;">식대</th>
                                <th style="width: 100px;">차량유지비</th>
                                <th style="width: 100px;">보육수당</th>
                                <th style="width: 100px;">성과급</th>
                                <th style="width: 100px;">조정수당</th>
                                <th style="width: 100px;">연차수당</th>
                                <th style="width: 100px;">세전총액</th>
                            </tr>
                        </thead>
                        <tbody id="membersList">
                            <!-- JavaScript로 동적 추가 -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="bulkCreateBtn">생성</button>
            </div>
        </div>
    </div>
</div>

<!-- 성과금 조회 모달 -->
<div class="modal fade" id="performanceModal" tabindex="-1" aria-labelledby="performanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="performanceModalLabel">분기 성과금 현황</h5>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" id="addPerformanceBtn">성과금추가</button>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">조회 분기</label>
                        <select class="form-select" id="quarterSelector">
                            <!-- JavaScript로 동적 추가 -->
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <h6 class="elapsed-info"></h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr class="table-primary">
                                <th width="30px" class="text-center">
                                    <input type="checkbox" id="selectAllPerformance">
                                </th>
                                <th>합계</th>
                                <td class="text-right total-monthly-standard">0</td>
                                <td class="text-right total-quarterly-standard">0</td>
                                <td class="text-right total-current-standard">0</td>
                                <td class="text-right total-current-amount">0</td>
                                <td class="text-right total-estimated-amount">0</td>
                                <td class="text-right total-expected-bonus">0</td>
                                <td></td>
                            </tr>
                            <tr>
                                <th width="30px" class="text-center"></th>
                                <th>이름</th>
                                <th class="text-right">월 기준 매출액</th>
                                <th class="text-right">분기 기준 매출액</th>
                                <th class="text-right">현재 기준 매출액</th>
                                <th class="text-right">현재 매출액</th>
                                <th class="text-right">분기 예상 매출액</th>
                                <th class="text-right">분기 예상 성과금</th>
                                <th class="text-center">분기 예상 보상 및 제재</th>
                            </tr>
                        </thead>
                        <tbody id="performanceTableBody">
                            <!-- JavaScript로 동적 추가 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 성과금 추가 모달 -->
<div class="modal fade" id="addPerformanceBonusModal" tabindex="-1" aria-labelledby="addPerformanceBonusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPerformanceBonusModalLabel">성과금 급여명세서 반영</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label for="bonusStatementDate" class="form-label">귀속년월</label>
                    <input type="month" class="form-control" id="bonusStatementDate">
                </div>
                <div id="performanceBonusResult" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="applyPerformanceBonusBtn">확인</button>
            </div>
        </div>
    </div>
</div>

<!-- 직접생성 모달 -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">급여명세서 직접생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createSalaryStatementForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">귀속년월</label>
                            <input type="month" class="form-control" name="statement_date" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">이름</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">직급</label>
                            <input type="text" class="form-control" name="position" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">소속</label>
                            <input type="text" class="form-control" name="affiliation" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">기본급</label>
                            <input type="text" class="form-control amount-input" name="base_salary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">식대</label>
                            <input type="text" class="form-control amount-input" name="meal_allowance">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">차량유지비</label>
                            <input type="text" class="form-control amount-input" name="vehicle_allowance">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">보육수당</label>
                            <input type="text" class="form-control amount-input" name="child_allowance">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">성과급</label>
                            <input type="text" class="form-control amount-input" name="performance_pay">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">조정수당</label>
                            <input type="text" class="form-control amount-input" name="adjustment_pay">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">연차수당</label>
                            <input type="text" class="form-control amount-input" name="vacation_pay">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="createBtn">생성</button>
            </div>
        </div>
    </div>
</div>

<!-- 건강보험 입력 모달 -->
<div class="modal fade" id="healthInsuranceModal" tabindex="-1" aria-labelledby="healthInsuranceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="max-height: 300px;">
            <div class="modal-header p-2">
                <h5 class="modal-title" id="healthInsuranceModalLabel">건강보험 입력</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="healthInsuranceForm" enctype="multipart/form-data" class="d-flex flex-column" style="height: 100%;">
                <div class="modal-body p-2 flex-grow-0">
                    <div class="mb-2">
                        <label for="insurance_statement_date" class="form-label small">귀속년월</label>
                        <input type="month" class="form-control form-control-sm" id="insurance_statement_date" name="statement_date" required>
                    </div>
                    <div class="mb-0">
                        <label for="insurance_file" class="form-label small">CSV 파일</label>
                        <input type="file" class="form-control form-control-sm" id="insurance_file" name="file" accept=".csv" required>
                        <div class="form-text text-muted">CSV 파일만 업로드 가능합니다.</div>
                    </div>
                </div>
                <div class="modal-footer mt-auto p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="insurance-submit-btn">입력</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 국민연금 입력 모달 -->
<div class="modal fade" id="nationalPensionModal" tabindex="-1" aria-labelledby="nationalPensionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="max-height: 300px;">
            <div class="modal-header p-2">
                <h5 class="modal-title" id="nationalPensionModalLabel">국민연금 입력</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="nationalPensionForm" enctype="multipart/form-data" class="d-flex flex-column" style="height: 100%;">
                <div class="modal-body p-2 flex-grow-0">
                    <div class="mb-2">
                        <label for="pension_statement_date" class="form-label small">귀속년월</label>
                        <input type="month" class="form-control form-control-sm" id="pension_statement_date" name="statement_date" required>
                    </div>
                    <div class="mb-0">
                        <label for="pension_file" class="form-label small">CSV 파일</label>
                        <input type="file" class="form-control form-control-sm" id="pension_file" name="file" accept=".csv" required>
                        <div class="form-text text-muted">CSV 파일만 업로드 가능합니다.</div>
                    </div>
                </div>
                <div class="modal-footer mt-auto p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="pension-submit-btn">입력</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 고용보험 입력 모달 -->
<div class="modal fade" id="employmentInsuranceModal" tabindex="-1" aria-labelledby="employmentInsuranceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="max-height: 300px;">
            <div class="modal-header p-2">
                <h5 class="modal-title" id="employmentInsuranceModalLabel">고용보험 입력</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="employmentInsuranceForm" enctype="multipart/form-data" class="d-flex flex-column" style="height: 100%;">
                <div class="modal-body p-2 flex-grow-0">
                    <div class="mb-2">
                        <label for="employment_statement_date" class="form-label small">귀속년월</label>
                        <input type="month" class="form-control form-control-sm" id="employment_statement_date" name="statement_date" required>
                    </div>
                    <div class="mb-0">
                        <label for="employment_file" class="form-label small">CSV 파일</label>
                        <input type="file" class="form-control form-control-sm" id="employment_file" name="file" accept=".csv" required>
                        <div class="form-text text-muted">CSV 파일만 업로드 가능합니다.</div>
                    </div>
                </div>
                <div class="modal-footer mt-auto p-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="employment-submit-btn">입력</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Excel Download 모달 -->
<div class="modal fade" id="excelDownloadModal" tabindex="-1" aria-labelledby="excelDownloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excelDownloadModalLabel">급여명세서 Excel Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- 합계 표시 영역 수정 -->
            <div class="row mx-2 mt-3">
                <div class="col">
                    <div class="card border-0 shadow-none">
                        <div class="card-body">
                            <h5 class="card-title text-muted">선택된 직원 수</h5>
                            <h3 class="mb-0 text-dark" id="selected-employee-count">0명</h3>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-none">
                        <div class="card-body">
                            <h5 class="card-title text-muted">선택 세전총급여</h5>
                            <h3 class="mb-0 text-primary" id="selected-total-payment">0원</h3>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-none">
                        <div class="card-body">
                            <h5 class="card-title text-muted">선택 공제금액</h5>
                            <h3 class="mb-0 text-danger" id="selected-total-deduction">0원</h3>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card border-0 shadow-none">
                        <div class="card-body">
                            <h5 class="card-title text-muted">선택 실지급액</h5>
                            <h3 class="mb-0 text-success" id="selected-net-payment">0원</h3>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 기존 모달 내용 -->
            <div class="modal-body">
                <!-- 검색 조건 -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">귀속년월</label>
                        <input type="month" class="form-control" id="searchStatementDate">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">지역</label>
                        <select class="form-select" id="searchAffiliation">
                            <option value="">전체</option>
                            <option value="서울">서울</option>
                            <option value="대전">대전</option>
                            <option value="부산">부산</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" id="searchBtn">조회</button>
                    </div>
                </div>

                <!-- 데이터 테이블 -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="excelSelectAll">
                                </th>
                                <th>귀속년월</th>
                                <th>이름</th>
                                <th>직급</th>
                                <th>업무</th>
                                <th>지역</th>
                                <th class="text-right">세전총급여</th>
                                <th class="text-right">공제총액</th>
                                <th class="text-right">실지급액</th>
                                <th class="text-center">승인상태</th>
                                <th>승인일시</th>
                                <th>작성일자</th>
                            </tr>
                        </thead>
                        <tbody id="statementList">
                            <!-- JavaScript로 동적 추가될 부분 -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="recalculateEmploymentBtn">고용보험 재계산</button>
                <button type="button" class="btn btn-danger" id="recalculateIncomeTaxBtn">소득세 재계산</button>
                <button type="button" class="btn btn-success" id="payrollBtn">급여대장</button>
                <button type="button" class="btn btn-primary" id="downloadBtn">신한이체</button>
                <button type="button" class="btn btn-warning" id="pensionBtn">퇴직급여</button>
            </div>
        </div>
    </div>
</div>

<!-- Process 모달 -->
<div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="width: 50%; max-width: 800px;">
        <div class="modal-content" style="height: 80vh;">
            <div class="modal-header">
                <h5 class="modal-title" id="processModalLabel">급여 처리 체크리스트</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input" type="checkbox" id="selectAllProcess">
                        <label class="form-check-label fw-bold" for="selectAllProcess">
                            전체 선택/해제
                        </label>
                    </div>
                </div>
                <div id="processChecklistContainer">
                    <!-- 체크리스트 항목이 여기에 동적으로 추가됩니다 -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 사대보험 모달 -->
<div class="modal fade" id="socialInsuranceModal" tabindex="-1" aria-labelledby="socialInsuranceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="socialInsuranceModalLabel">사대보험 연동</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">귀속년월</label>
                        <input type="month" class="form-control" id="socialInsuranceDate">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="healthInsuranceCheck" value="health_insurance" checked>
                            <label class="form-check-label" for="healthInsuranceCheck">건강보험</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="nationalPensionCheck" value="national_pension" checked>
                            <label class="form-check-label" for="nationalPensionCheck">국민연금</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="longTermCareCheck" value="long_term_care" checked>
                            <label class="form-check-label" for="longTermCareCheck">장기요양</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="employmentInsuranceCheck" value="employment_insurance">
                            <label class="form-check-label" for="employmentInsuranceCheck">고용보험</label>
                        </div>
                    </div>
                </div>
                
                <!-- 데이터 테이블 -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="socialInsuranceSelectAll">
                                </th>
                                <th>귀속년월</th>
                                <th>이름</th>
                                <th>직급</th>
                                <th>업무</th>
                                <th>지역</th>
                                <th class="text-right">세전총급여</th>
                                <th class="text-right">공제총액</th>
                                <th class="text-right">실지급액</th>
                                <th class="text-center">승인상태</th>
                            </tr>
                        </thead>
                        <tbody id="socialInsuranceStatementList">
                            <!-- JavaScript로 동적 추가될 부분 -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="syncSocialInsuranceBtn">확인</button>
            </div>
        </div>
    </div>
</div>

<!-- 모달 스타일 추가 -->
<style>
.modal-xl {
    max-width: 80% !important;
    width: 80% !important;
}

.modal-dialog {
    height: 80vh !important;
}

.modal-content {
    height: 100% !important;
}

.modal-body {
    height: calc(100% - 120px) !important; /* 헤더와 푸터 높이 고려 */
    overflow-y: auto;
}

/* 금액 관련 스타일 */
.amount-input {
    text-align: right !important;
    padding-right: 10px !important;
    width: 100% !important;
    min-width: 100px !important;
}

.amount-cell {
    text-align: right !important;
    padding-right: 15px !important;
}

/* 테이블 레이아웃 고정 */
.table {
    table-layout: fixed !important;
    width: 100% !important;
}

/* 셀 내용 줄바꿈 방지 */
.table td, .table th {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

#performanceModal .modal-xl {
    max-width: 80% !important;
    width: 80% !important;
}

#performanceModal .modal-dialog {
    height: 80vh !important;
}

#performanceModal .modal-content {
    height: 100% !important;
}

#performanceModal .modal-body {
    height: calc(100% - 60px) !important;
    overflow-y: auto;
}

.text-right {
    text-align: right !important;
}

/* 글씨 크기 2포인트 축소를 위한 클래스 */
.fs-7 {
    font-size: 0.875rem !important; /* 14px */
}

/* 버튼 패딩 조정 */
.btn-sm {
    padding: 0.25rem 0.5rem;
}

/* Process 모달 스타일 */
#processModal .modal-dialog {
    width: 50% !important;
    max-width: 800px !important;
}

#processModal .modal-content {
    height: 80vh !important;
}

#processModal .modal-body {
    overflow-y: auto;
}

#processModal .form-check {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

#processModal .form-check-input {
    margin-top: 0;
    margin-right: 8px;
    flex-shrink: 0;
}

#processModal .form-check-label {
    display: block;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
    width: 100%;
    line-height: 1.5;
}
</style>

@endsection

@push('scripts')
<script>
// CSRF 토큰 설정
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// 숫자에 천단위 쉼표를 추가하는 함수
function numberWithCommas(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function calculateRowTotal(row) {
    try {
        let total = 0;
        
        // 각 급여 항목의 값을 더함 (null 체크 추가)
        const getValue = (selector) => {
            const element = $(row).find(selector);
            if (element.length === 0 || !element.val()) return 0;
            return parseInt(element.val().replace(/,/g, '')) || 0;
        };
        
        total += getValue('.base-salary');
        total += getValue('.meal-allowance');
        total += getValue('.vehicle-allowance');
        total += getValue('.child-allowance');
        total += getValue('.performance-pay');
        total += getValue('.adjustment-pay');
        total += getValue('.vacation-pay');
        
        // 총액 표시 요소가 있는 경우에만 업데이트
        const totalElement = $(row).find('.total-payment');
        if (totalElement.length > 0) {
            totalElement.text(numberWithCommas(total));
        }
        
        return total;
    } catch (error) {
        console.error('Error in calculateRowTotal:', error);
        return 0;
    }
}

$(document).ready(function() {
    console.log('Script loaded'); // 스크립트 로드 확인용
    
    // 모달 열기 테스트
    $('.btn-primary[data-bs-toggle="modal"]').click(function() {
        console.log('Modal button clicked'); // 버튼 클릭 확인용
    });

    // 일괄생성 모달의 전체선택/해제 체크박스 이벤트
    $('#bulkSelectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.member-select').prop('checked', isChecked);
    });
    
    // 개별 멤버 체크박스 이벤트
    $(document).on('change', '.member-select', function() {
        const totalCheckboxes = $('.member-select').length;
        const checkedCheckboxes = $('.member-select:checked').length;
        $('#bulkSelectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // 모달이 열릴 때 구성원 목록 로드
    $('#bulkCreateModal').on('show.bs.modal', function() {
        loadMembers();
    });

    // 구성원 목록 로드 함수
    function loadMembers() {
        console.log('Loading members...'); // 디버깅용
        $.get("{{ route('admin.salary-statements.create') }}", function(response) {
            console.log('Response:', response); // 디버깅용
            let html = '';
            if (response.members && response.members.length > 0) {
                response.members.forEach(function(member, index) {
                    const contract = member.salary_contract || {};
                    html += `
                        <tr>
                            <td>
                                <input type="checkbox" class="member-select" value="${member.user_id || ''}">
                            </td>
                            <td class="text-center">${index + 1}</td>
                            <td>${member.name || ''}</td>
                            <td>${member.position || ''}</td>
                            <td>${member.affiliation || ''}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm base-salary amount-input" 
                                       value="${numberWithCommas(contract.monthly_salary || 0)}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm meal-allowance amount-input" 
                                       value="${numberWithCommas(contract.meal_allowance || 0)}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm vehicle-allowance amount-input" 
                                       value="${numberWithCommas(contract.vehicle_allowance || 0)}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm child-allowance amount-input" 
                                       value="${numberWithCommas(contract.child_allowance || 0)}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm performance-pay amount-input" value="0">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm adjustment-pay amount-input" value="0">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm vacation-pay amount-input" value="0">
                            </td>
                            <td class="amount-cell total-payment">0</td>
                        </tr>
                    `;
                });
                $('#membersList').html(html);

                // 금액 입력 시 자동 계산 및 쉼표 처리
                $('.form-control-sm').on('input', function() {
                    // 숫자와 쉼표만 허용
                    let value = $(this).val().replace(/[^\d,]/g, '');
                    // 쉼표 제거 후 숫자만 남김
                    let number = parseInt(value.replace(/,/g, '')) || 0;
                    // 쉼표 포함된 형태로 다시 표시
                    $(this).val(numberWithCommas(number));
                    calculateRowTotal($(this).closest('tr'));
                });
                
                // 초기 총액 계산
                $('tr').each(function() {
                    calculateRowTotal($(this));
                });
            } else {
                $('#membersList').html('<tr><td colspan="12" class="text-center">재직 중인 구성원이 없습니다.</td></tr>');
            }
        }).fail(function(error) {
            console.error('Error:', error);
            $('#membersList').html('<tr><td colspan="12" class="text-center">구성원 목록을 불러오는데 실패했습니다.</td></tr>');
        });
    }

    // 일괄생성 버튼 클릭 시 데이터 처리 수정
    $('#bulkCreateBtn').click(function() {
        let statements = [];
        $('.member-select:checked').each(function() {
            let row = $(this).closest('tr');
            statements.push({
                user_id: $(this).val(),
                base_salary: parseInt(row.find('.base-salary').val().replace(/,/g, '')) || 0,
                meal_allowance: parseInt(row.find('.meal-allowance').val().replace(/,/g, '')) || 0,
                vehicle_allowance: parseInt(row.find('.vehicle-allowance').val().replace(/,/g, '')) || 0,
                child_allowance: parseInt(row.find('.child-allowance').val().replace(/,/g, '')) || 0,
                performance_pay: parseInt(row.find('.performance-pay').val().replace(/,/g, '')) || 0,
                adjustment_pay: parseInt(row.find('.adjustment-pay').val().replace(/,/g, '')) || 0,
                vacation_pay: parseInt(row.find('.vacation-pay').val().replace(/,/g, '')) || 0
            });
        });

        if (statements.length === 0) {
            alert('선택된 구성원이 없습니다.');
            return;
        }

        let statementDate = $('#statementDate').val();
        if (!statementDate) {
            alert('해당 년월을 선택해주세요.');
            return;
        }

        $.ajax({
            url: "{{ route('admin.salary-statements.bulk-create') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                statement_date: statementDate,
                statements: statements
            },
            success: function(response) {
                alert(response.message);
                location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || '급여명세서 생성 중 오류가 발생했습니다.');
            }
        });
    });

    // 행 클릭 시 상세페이지로 이동
    $('.clickable-row').on('click', function(e) {
        // 액션 컬럼(수정/삭제 버튼)을 클릭한 경우 이동하지 않음
        if (!$(e.target).closest('.action-column').length) {
            window.location = $(this).data('href');
        }
    });

    // 성과금 조회 모달이 열릴 때 데이터 로드
    $('#performanceModal').on('show.bs.modal', function() {
        loadPerformanceData();
    });

    function loadPerformanceData(year, quarter) {
        // API 호출 URL 구성
        let url = "/admin/salary-statements/performance-status";
        let params = {};
        
        // 연도와 분기가 지정된 경우 파라미터 추가
        if (year !== undefined && quarter !== undefined) {
            params = { year: year, quarter: quarter };
        }
        
        $.get(url, params, function(response) {
            if (response.success) {
                // 분기 선택기 업데이트
                updateQuarterSelector(response.available_quarters, response.elapsed_info.year, response.elapsed_info.quarter);
                
                // 경과 정보 표시
                const elapsedInfo = response.elapsed_info;
                $('.elapsed-info').html(
                    `${elapsedInfo.quarter_name} 성과 (${elapsedInfo.date} 기준)<br>` +
                    `${elapsedInfo.elapsed_days}일/${elapsedInfo.total_days}일 (${elapsedInfo.elapsed_rate}%)`
                );

                // 합계 데이터 표시
                $('.total-monthly-standard').text(numberWithCommas(response.totals.monthly_standard) + '원');
                $('.total-quarterly-standard').text(numberWithCommas(response.totals.quarterly_standard) + '원');
                $('.total-current-standard').text(numberWithCommas(response.totals.current_standard) + '원');
                $('.total-current-amount').text(numberWithCommas(response.totals.current_amount) + '원');
                $('.total-estimated-amount').text(numberWithCommas(response.totals.estimated_amount) + '원');
                $('.total-expected-bonus').text(numberWithCommas(response.totals.expected_bonus) + '원');

                // 성과 데이터 표시
                const tbody = $('#performanceTableBody');
                tbody.empty();

                response.performance_data.forEach(function(item) {
                    tbody.append(`
                        <tr data-name="${item.name}" data-bonus="${item.expected_bonus}">
                            <td class="text-center">
                                <input type="checkbox" class="performance-item-check" value="${item.name}">
                            </td>
                            <td>${item.name}</td>
                            <td class="text-right">${numberWithCommas(item.monthly_standard)}원</td>
                            <td class="text-right">${numberWithCommas(item.quarterly_standard)}원</td>
                            <td class="text-right">${numberWithCommas(item.current_standard)}원</td>
                            <td class="text-right">${numberWithCommas(item.current_amount)}원</td>
                            <td class="text-right">${numberWithCommas(item.estimated_amount)}원</td>
                            <td class="text-right">${numberWithCommas(item.expected_bonus)}원</td>
                            <td class="text-center">${item.reward}</td>
                        </tr>
                    `);
                });
                
                // 전체 선택 체크박스 초기화
                $('#selectAllPerformance').prop('checked', false);
            } else {
                alert('성과금 현황을 불러오는데 실패했습니다.');
            }
        }).fail(function() {
            alert('서버 오류가 발생했습니다.');
        });
    }
    
    // 분기 선택기 업데이트 함수
    function updateQuarterSelector(quarters, selectedYear, selectedQuarter) {
        const selector = $('#quarterSelector');
        selector.empty();
        
        quarters.forEach(function(quarter) {
            const isSelected = (quarter.year == selectedYear && quarter.quarter == selectedQuarter);
            selector.append(`
                <option value="${quarter.year}-${quarter.quarter}" ${isSelected ? 'selected' : ''}>
                    ${quarter.name}
                </option>
            `);
        });
        
        // 분기 선택 이벤트 처리
        selector.off('change').on('change', function() {
            const value = $(this).val();
            if (value) {
                const [year, quarter] = value.split('-');
                loadPerformanceData(year, quarter);
            }
        });
    }
    
    // 성과금 전체 선택 체크박스 이벤트
    $('#selectAllPerformance').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.performance-item-check').prop('checked', isChecked);
    });
    
    // 개별 체크박스 변경 시 전체 선택 상태 업데이트
    $(document).on('change', '.performance-item-check', function() {
        const totalItems = $('.performance-item-check').length;
        const checkedItems = $('.performance-item-check:checked').length;
        
        $('#selectAllPerformance').prop('checked', totalItems === checkedItems && totalItems > 0);
    });
    
    // 성과금 추가 버튼 클릭 이벤트
    $('#addPerformanceBtn').on('click', function() {
        // 체크된 항목이 있는지 확인
        const checkedItems = $('.performance-item-check:checked');
        
        if (checkedItems.length === 0) {
            alert('성과금을 적용할 직원을 선택해주세요.');
            return;
        }
        
        // 현재 날짜로 귀속년월 기본값 설정
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        $('#bonusStatementDate').val(`${year}-${month}`);
        
        // 성과금 추가 모달 열기
        $('#addPerformanceBonusModal').modal('show');
    });
    
    // 성과금 적용 버튼 클릭 이벤트 재설정 함수
    function setupApplyPerformanceBonusBtn() {
        $('#applyPerformanceBonusBtn').off('click').on('click', function() {
            const statementDate = $('#bonusStatementDate').val();
            
            if (!statementDate) {
                alert('귀속년월을 선택해주세요.');
                return;
            }
            
            // 선택된 직원들의 성과금 데이터 수집
            const bonuses = [];
            $('.performance-item-check:checked').each(function() {
                const row = $(this).closest('tr');
                const name = row.data('name');
                const bonus = row.data('bonus');
                
                bonuses.push({
                    name: name,
                    bonus: bonus
                });
            });
            
            // 버튼 비활성화 및 로딩 표시
            const btn = $(this);
            const originalText = btn.text();
            btn.prop('disabled', true).text('처리 중...');
            
            // 결과 영역 초기화
            $('#performanceBonusResult').empty();
            
            // API 호출
            $.ajax({
                url: "/admin/salary-statements/apply-performance-bonus",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    statement_date: statementDate,
                    bonuses: bonuses
                },
                success: function(response) {
                    btn.prop('disabled', false).text(originalText);
                    
                    if (response.success) {
                        // 성공 메시지 생성
                        let resultHtml = `<div class="alert alert-success">
                            총 ${response.total_count}명에게 ${numberWithCommas(response.total_amount)}원의 성과금을 입력하였습니다.
                        </div>`;
                        
                        // 상세 내역 추가
                        resultHtml += '<ul class="list-group mt-2">';
                        response.details.forEach(function(detail, index) {
                            resultHtml += `<li class="list-group-item">
                                ${index + 1}. ${detail.name}님에게 ${numberWithCommas(detail.amount)}원의 성과금을 입력하였습니다.
                            </li>`;
                        });
                        resultHtml += '</ul>';
                        
                        $('#performanceBonusResult').html(resultHtml);
                        
                        // 확인 버튼을 닫기 버튼으로 변경하고 기능 수정
                        $('#applyPerformanceBonusBtn')
                            .text('닫기')
                            .removeClass('btn-primary')
                            .addClass('btn-success')
                            .off('click')
                            .on('click', function() {
                                // 두 모달 모두 닫기
                                $('#addPerformanceBonusModal').modal('hide');
                                $('#performanceModal').modal('hide');
                                
                                // 페이지 새로고침
                                setTimeout(function() {
                                    location.reload();
                                }, 300);
                            });
                            
                        // 취소 버튼 숨기기
                        $('#addPerformanceBonusModal .btn-secondary').hide();
                    } else {
                        // 오류 메시지 생성
                        let resultHtml = '<div class="alert alert-danger">성과금 적용 중 오류가 발생했습니다.</div>';
                        
                        if (response.errors && response.errors.length > 0) {
                            resultHtml += '<ul class="list-group mt-2">';
                            response.errors.forEach(function(error) {
                                resultHtml += `<li class="list-group-item list-group-item-danger">${error}</li>`;
                            });
                            resultHtml += '</ul>';
                        }
                        
                        $('#performanceBonusResult').html(resultHtml);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text(originalText);
                    
                    let errorMessage = '서버 오류가 발생했습니다.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('#performanceBonusResult').html(`
                        <div class="alert alert-danger">${errorMessage}</div>
                    `);
                }
            });
        });
    }
    
    // 문서 로드 시 성과금 적용 버튼 이벤트 설정
    setupApplyPerformanceBonusBtn();
    
    // 성과금 추가 모달이 열릴 때 버튼 상태 초기화
    $('#addPerformanceBonusModal').on('show.bs.modal', function() {
        // 버튼 상태 초기화
        $('#applyPerformanceBonusBtn')
            .text('확인')
            .removeClass('btn-success')
            .addClass('btn-primary');
        
        // 취소 버튼 다시 표시
        $(this).find('.btn-secondary').show();
        
        // 결과 영역 초기화
        $('#performanceBonusResult').empty();
        
        // 버튼 이벤트 재설정
        setupApplyPerformanceBonusBtn();
    });
    
    // 성과금 추가 모달이 닫힐 때 결과 영역 초기화
    $('#addPerformanceBonusModal').on('hidden.bs.modal', function() {
        // 결과 영역 초기화
        $('#performanceBonusResult').empty();
    });

    // 삭제 버튼 클릭 이벤트 핸들러 추가
    $('.delete-statement').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const statementId = $(this).data('id');
        
        if (confirm('정말 이 급여명세서를 삭제하시겠습니까?')) {
            $.ajax({
                url: `/admin/salary-statements/${statementId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message || '급여명세서 삭제 중 오류가 발생했습니다.');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || '급여명세서 삭제 중 오류가 발생했습니다.';
                    alert(errorMessage);
                    console.error('Error:', xhr);
                }
            });
        }
    });

    // 직접생성 모달 관련 스크립트 추가
    // 금액 입력 필드에 자동 쉼표 추가
    $('.amount-input').on('input', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        $(this).val(numberWithCommas(value));
    });

    // 생성 버튼 클릭 이벤트
    $('#createBtn').click(function() {
        let formData = {};
        
        // 폼 데이터 수집 및 쉼표 제거
        $('#createSalaryStatementForm').serializeArray().forEach(function(item) {
            if (item.name.includes('salary') || item.name.includes('allowance') || item.name.includes('pay')) {
                formData[item.name] = parseInt(item.value.replace(/,/g, '')) || 0;
            } else {
                formData[item.name] = item.value;
            }
        });

        $.ajax({
            url: "{{ route('admin.salary-statements.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || '급여명세서 생성 중 오류가 발생했습니다.');
            }
        });
    });

    // 건강보험 입력 폼 제출
    $('#healthInsuranceForm').on('submit', function(e) {
        e.preventDefault();
        
        // 파일 확장자 검증
        const fileInput = document.getElementById('insurance_file');
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'csv') {
                alert('CSV 파일만 업로드 가능합니다.');
                return;
            }
        }
        
        // 로딩 상태 표시
        const submitBtn = $('#insurance-submit-btn');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('처리 중...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('admin.salary-statements.update-insurance') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(`${response.count}개의 데이터가 업데이트되었습니다.`);
                    $('#healthInsuranceModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || '처리 중 오류가 발생했습니다.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                let errorMsg = '처리 중 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // 국민연금 입력 폼 제출
    $('#nationalPensionForm').on('submit', function(e) {
        e.preventDefault();
        
        // 파일 확장자 검증
        const fileInput = document.getElementById('pension_file');
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'csv') {
                alert('CSV 파일만 업로드 가능합니다.');
                return;
            }
        }
        
        // 로딩 상태 표시
        const submitBtn = $('#pension-submit-btn');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('처리 중...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('admin.salary-statements.update-pension') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(`${response.count}개의 데이터가 업데이트되었습니다.`);
                    $('#nationalPensionModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || '처리 중 오류가 발생했습니다.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                let errorMsg = '처리 중 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // 고용보험 입력 폼 제출
    $('#employmentInsuranceForm').on('submit', function(e) {
        e.preventDefault();
        
        // 파일 확장자 검증
        const fileInput = document.getElementById('employment_file');
        if (fileInput.files.length > 0) {
            const fileName = fileInput.files[0].name;
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            if (fileExt !== 'csv') {
                alert('CSV 파일만 업로드 가능합니다.');
                return;
            }
        }
        
        // 로딩 상태 표시
        const submitBtn = $('#employment-submit-btn');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('처리 중...');
        
        const formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('admin.salary-statements.update-employment') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(`${response.count}개의 데이터가 업데이트되었습니다.`);
                    $('#employmentInsuranceModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || '처리 중 오류가 발생했습니다.');
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                let errorMsg = '처리 중 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert(errorMsg);
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Excel Download 모달 관련 스크립트
    $('#excelDownloadModal').on('show.bs.modal', function() {
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1);
        const year = lastMonth.getFullYear();
        const month = String(lastMonth.getMonth() + 1).padStart(2, '0');
        
        $('#searchStatementDate').val(`${year}-${month}`);
        $('#searchBtn').click();

        // 합계 초기화
        updateSelectedTotals();
    });

    // Excel Download 모달의 전체선택/해제 체크박스 이벤트
    $('#excelSelectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.statement-select').prop('checked', isChecked);
        updateSelectedTotals();
    });

    // 개별 명세서 체크박스 이벤트
    $(document).on('change', '.statement-select', function() {
        const totalCheckboxes = $('.statement-select').length;
        const checkedCheckboxes = $('.statement-select:checked').length;
        $('#excelSelectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        updateSelectedTotals();
    });

    // 조회 버튼 클릭
    $('#searchBtn').click(function() {
        const statementDate = $('#searchStatementDate').val();
        const affiliation = $('#searchAffiliation').val();

        $.ajax({
            url: "{{ route('admin.salary-statements.search') }}",
            method: 'GET',
            data: {
                statement_date: statementDate,
                affiliation: affiliation
            },
            success: function(response) {
                updateStatementList(response.statements);
            },
            error: function(xhr) {
                alert('데이터 조회 중 오류가 발생했습니다.');
            }
        });
    });

    // 다운로드 버튼 클릭
    $('#downloadBtn').click(function() {
        const selectedIds = [];
        $('.statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('다운로드 할 데이터가 없습니다.');
            return;
        }

        // 다운로드 요청
        window.location.href = "{{ route('admin.salary-statements.download-excel') }}?ids=" + selectedIds.join(',');
    });

    // 급여대장 버튼 클릭
    $('#payrollBtn').click(function() {
        const selectedIds = [];
        $('.statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('다운로드 할 데이터가 없습니다.');
            return;
        }

        // 현재 선택된 소속 필터값 가져오기 (올바른 ID 사용)
        const affiliation = $('#searchAffiliation').val();
        console.log('선택된 소속:', affiliation);

        // URL 생성
        const url = `{{ route('admin.salary-statements.download-payroll') }}?ids=${selectedIds.join(',')}&affiliation=${affiliation || '전체'}`;
        console.log('요청 URL:', url);

        window.location.href = url;
    });

    // 퇴직급여 버튼 클릭
    $('#pensionBtn').click(function() {
        const selectedIds = [];
        $('.statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('다운로드 할 데이터가 없습니다.');
            return;
        }

        // 현재 선택된 소속 필터값 가져오기
        const affiliation = $('#searchAffiliation').val();
        console.log('선택된 소속:', affiliation);

        // URL 생성
        const url = `{{ route('admin.salary-statements.download-pension') }}?ids=${selectedIds.join(',')}&affiliation=${affiliation || '전체'}`;
        console.log('요청 URL:', url);

        window.location.href = url;
    });

    // 성과금 추가 버튼 클릭 이벤트
    $('#addPerformanceBtn').on('click', function() {
        // 체크된 항목이 있는지 확인
        const checkedItems = $('.performance-item-check:checked');
        
        if (checkedItems.length === 0) {
            alert('성과금을 적용할 직원을 선택해주세요.');
            return;
        }
        
        // 현재 날짜로 귀속년월 기본값 설정
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        $('#bonusStatementDate').val(`${year}-${month}`);
        
        // 성과금 추가 모달 열기
        $('#addPerformanceBonusModal').modal('show');
    });

    // 고용보험 재계산 버튼 클릭 이벤트
    $('#recalculateEmploymentBtn').click(function() {
        const selectedIds = [];
        $('.statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('재계산할 급여명세서를 선택해주세요.');
            return;
        }

        if (!confirm('선택한 ' + selectedIds.length + '개의 급여명세서에 대해 고용보험을 재계산하시겠습니까?')) {
            return;
        }

        // 로딩 상태 표시
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('처리 중...');

        $.ajax({
            url: "{{ route('admin.salary-statements.recalculate-employment') }}",
            method: 'POST',
            data: {
                ids: selectedIds
            },
            success: function(response) {
                btn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    let message = response.message + '\n\n';
                    let detailsCount = 0;
                    
                    // 변경된 내역이 있는 경우만 상세 내역에 추가
                    response.details.forEach(function(detail) {
                        if (detail.difference !== 0) {
                            message += detail.name + ': ' + numberWithCommas(detail.previous) + '원 → ' + 
                                       numberWithCommas(detail.current) + '원 (차액: ' + 
                                       numberWithCommas(detail.difference) + '원)\n';
                            detailsCount++;
                        }
                    });
                    
                    if (detailsCount === 0) {
                        message += '변경사항이 없습니다.';
                    }
                    
                    alert(message);
                    // 화면 새로고침
                    location.reload();
                } else {
                    alert(response.message || '고용보험 재계산 중 오류가 발생했습니다.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text(originalText);
                alert(xhr.responseJSON?.message || '고용보험 재계산 중 오류가 발생했습니다.');
            }
        });
    });

    // 소득세 재계산 버튼 클릭 이벤트
    $('#recalculateIncomeTaxBtn').click(function() {
        const selectedIds = [];
        $('.statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('재계산할 급여명세서를 선택해주세요.');
            return;
        }

        if (!confirm('선택한 ' + selectedIds.length + '개의 급여명세서에 대해 소득세를 재계산하시겠습니까?')) {
            return;
        }

        // 로딩 상태 표시
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('처리 중...');

        $.ajax({
            url: "{{ route('admin.salary-statements.recalculate-income-tax') }}",
            method: 'POST',
            data: {
                ids: selectedIds
            },
            success: function(response) {
                btn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    let message = response.message + '\n\n';
                    let detailsCount = 0;
                    
                    // 변경된 내역이 있는 경우만 상세 내역에 추가
                    response.details.forEach(function(detail) {
                        if (detail.income_tax_difference !== 0 || detail.local_income_tax_difference !== 0) {
                            message += detail.name + ':\n';
                            if (detail.income_tax_difference !== 0) {
                                message += '- 소득세: ' + numberWithCommas(detail.previous_income_tax) + '원 → ' + 
                                        numberWithCommas(detail.current_income_tax) + '원 (차액: ' + 
                                        numberWithCommas(detail.income_tax_difference) + '원)\n';
                            }
                            if (detail.local_income_tax_difference !== 0) {
                                message += '- 지방소득세: ' + numberWithCommas(detail.previous_local_income_tax) + '원 → ' + 
                                        numberWithCommas(detail.current_local_income_tax) + '원 (차액: ' + 
                                        numberWithCommas(detail.local_income_tax_difference) + '원)\n';
                            }
                            detailsCount++;
                        }
                    });
                    
                    if (detailsCount === 0) {
                        message += '변경사항이 없습니다.';
                    }
                    
                    alert(message);
                    // 화면 새로고침
                    location.reload();
                } else {
                    alert(response.message || '소득세 재계산 중 오류가 발생했습니다.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text(originalText);
                alert(xhr.responseJSON?.message || '소득세 재계산 중 오류가 발생했습니다.');
            }
        });
    });
});

// 데이터 테이블 업데이트 함수
function updateStatementList(statements) {
    const tbody = $('#statementList');
    tbody.empty();

    if (statements.length === 0) {
        tbody.append('<tr><td colspan="12" class="text-center">조회된 데이터가 없습니다.</td></tr>');
        return;
    }

    statements.forEach(function(statement) {
        tbody.append(`
            <tr data-total-payment="${statement.total_payment}" 
                data-total-deduction="${statement.total_deduction}" 
                data-net-payment="${statement.net_payment}">
                <td><input type="checkbox" class="statement-select" value="${statement.id}"></td>
                <td>${statement.statement_date}</td>
                <td>${statement.name}</td>
                <td>${statement.position}</td>
                <td>${statement.task || '-'}</td>
                <td>${statement.affiliation}</td>
                <td class="text-right">${numberWithCommas(statement.total_payment)}</td>
                <td class="text-right">${numberWithCommas(statement.total_deduction)}</td>
                <td class="text-right">${numberWithCommas(statement.net_payment)}</td>
                <td class="text-center">
                    ${statement.approved_at ? '<span class="badge bg-success">승인완료</span>' : '<span class="badge bg-warning">승인대기</span>'}
                </td>
                <td>${statement.approved_at || '-'}</td>
                <td>${statement.created_at}</td>
            </tr>
        `);
    });
}

// 선택된 항목들의 합계 계산 및 표시 함수
function updateSelectedTotals() {
    let totalPayment = 0;
    let totalDeduction = 0;
    let netPayment = 0;
    const selectedCount = $('.statement-select:checked').length;

    $('.statement-select:checked').each(function() {
        const row = $(this).closest('tr');
        totalPayment += parseInt(row.data('total-payment')) || 0;
        totalDeduction += parseInt(row.data('total-deduction')) || 0;
        netPayment += parseInt(row.data('net-payment')) || 0;
    });

    // 합계 표시 업데이트
    $('#selected-employee-count').text(selectedCount + '명');
    $('#selected-total-payment').text(numberWithCommas(totalPayment) + '원');
    $('#selected-total-deduction').text(numberWithCommas(totalDeduction) + '원');
    $('#selected-net-payment').text(numberWithCommas(netPayment) + '원');
}

document.addEventListener('DOMContentLoaded', function() {
    // 모든 귀속년월 입력 필드에 대해 기본값 설정 (당월로 변경)
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const defaultDate = `${year}-${month}`;

    // 각 모달의 귀속년월 입력 필드에 기본값 설정
    ['statement-date', 'socialInsuranceDate'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.value = defaultDate;
        }
    });

    // 필터 폼 제출
    const filterForm = document.getElementById('salary-filter-form');
    if (filterForm) {  // 요소가 존재하는지 확인
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            updateSalaryList();
        });
    }

    // 초기화 버튼
    const resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {  // 요소가 존재하는지 확인
        resetBtn.addEventListener('click', function() {
            if (filterForm) {
                filterForm.reset();
                if (element) {
                    element.value = defaultDate;
                }
                updateSalaryList();
            }
        });
    }

    // 데이터 업데이트 함수
    function updateSalaryList() {
        const filterForm = document.getElementById('salary-filter-form');
        if (!filterForm) return;  // 폼이 없으면 함수 종료

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);

        fetch(`{{ route('admin.salary-statements.filter') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 통계 업데이트
                    const totalPayment = document.getElementById('total-payment');
                    const totalDeduction = document.getElementById('total-deduction');
                    const netPayment = document.getElementById('net-payment');

                    if (totalPayment) totalPayment.textContent = numberWithCommas(data.statistics.total_payment) + '원';
                    if (totalDeduction) totalDeduction.textContent = numberWithCommas(data.statistics.total_deduction) + '원';
                    if (netPayment) netPayment.textContent = numberWithCommas(data.statistics.net_payment) + '원';

                    // TODO: 리스트 업데이트 로직 구현
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('데이터 조회 중 오류가 발생했습니다.');
            });
    }

    // 초기 데이터 로드
    updateSalaryList();
});

// Process 모달 관련 스크립트
$(document).ready(function() {
    // Process 모달이 열릴 때 체크리스트 로드
    $('#processModal').on('show.bs.modal', function() {
        loadProcessChecklist();
    });

    // 체크리스트 로드 함수
    function loadProcessChecklist() {
        // 컨트롤러를 통해 체크리스트 내용 가져오기
        $.ajax({
            url: "{{ route('admin.salary-statements.process-checklist') }}",
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    $('#processChecklistContainer').html(`<div class="alert alert-danger">${response.message || '체크리스트를 불러오는데 실패했습니다.'}</div>`);
                    return;
                }
                
                const items = response.items;
                let html = '';
                
                // 각 항목마다 체크박스 생성
                items.forEach((item, index) => {
                    const itemId = `process-item-${index}`;
                    const isChecked = localStorage.getItem(itemId) === 'true';
                    
                    html += `
                        <div class="form-check mb-2">
                            <input class="form-check-input process-item" type="checkbox" id="${itemId}" ${isChecked ? 'checked' : ''}>
                            <label class="form-check-label w-100" for="${itemId}" style="${isChecked ? 'background-color: #d1e7dd;' : ''}">
                                ${item}
                            </label>
                        </div>
                    `;
                });
                
                $('#processChecklistContainer').html(html);
                
                // 전체 선택 체크박스 상태 업데이트
                updateSelectAllCheckbox();
                
                // 체크박스 변경 이벤트 처리
                $('.process-item').on('change', function() {
                    const itemId = $(this).attr('id');
                    const isChecked = $(this).prop('checked');
                    
                    // 로컬 스토리지에 상태 저장
                    localStorage.setItem(itemId, isChecked);
                    
                    // 배경색 변경
                    if (isChecked) {
                        $(this).next('label').css('background-color', '#d1e7dd');
                    } else {
                        $(this).next('label').css('background-color', '');
                    }
                    
                    // 전체 선택 체크박스 상태 업데이트
                    updateSelectAllCheckbox();
                });
            },
            error: function(xhr, status, error) {
                console.error('Error loading process checklist:', error);
                $('#processChecklistContainer').html('<div class="alert alert-danger">체크리스트를 불러오는데 실패했습니다.</div>');
            }
        });
    }
    
    // 전체 선택/해제 체크박스 이벤트
    $('#selectAllProcess').on('change', function() {
        const isChecked = $(this).prop('checked');
        
        // 모든 체크박스 상태 변경
        $('.process-item').each(function() {
            $(this).prop('checked', isChecked);
            const itemId = $(this).attr('id');
            
            // 로컬 스토리지에 상태 저장
            localStorage.setItem(itemId, isChecked);
            
            // 배경색 변경
            if (isChecked) {
                $(this).next('label').css('background-color', '#d1e7dd');
            } else {
                $(this).next('label').css('background-color', '');
            }
        });
    });
    
    // 전체 선택 체크박스 상태 업데이트 함수
    function updateSelectAllCheckbox() {
        const totalItems = $('.process-item').length;
        const checkedItems = $('.process-item:checked').length;
        
        $('#selectAllProcess').prop('checked', totalItems > 0 && totalItems === checkedItems);
    }
});

// 사대보험 모달 관련 스크립트
$(document).ready(function() {
    // 사대보험 모달이 열릴 때 이벤트
    $('#socialInsuranceModal').on('show.bs.modal', function() {
        loadSocialInsuranceStatements();
    });

    // 귀속년월 변경 시 데이터 재로드
    $('#socialInsuranceDate').on('change', function() {
        loadSocialInsuranceStatements();
    });

    // 급여명세서 조회 함수
    function loadSocialInsuranceStatements() {
        const statementDate = $('#socialInsuranceDate').val();
        if(!statementDate) return;

        $.ajax({
            url: "{{ route('admin.salary-statements.search') }}",
            method: 'GET',
            data: {
                statement_date: statementDate
            },
            success: function(response) {
                updateSocialInsuranceStatementList(response.statements);
                // 전체 선택 체크박스 초기화
                $('#socialInsuranceSelectAll').prop('checked', false);
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                alert('급여명세서 조회 중 오류가 발생했습니다.');
            }
        });
    }

    // 데이터 테이블 업데이트 함수
    function updateSocialInsuranceStatementList(statements) {
        const tbody = $('#socialInsuranceStatementList');
        tbody.empty();

        if (statements.length === 0) {
            tbody.append('<tr><td colspan="10" class="text-center">조회된 데이터가 없습니다.</td></tr>');
            return;
        }

        statements.forEach(function(statement) {
            tbody.append(`
                <tr>
                    <td><input type="checkbox" class="social-insurance-statement-select" value="${statement.id}"></td>
                    <td>${statement.statement_date}</td>
                    <td>${statement.name}</td>
                    <td>${statement.position || '-'}</td>
                    <td>${statement.task || '-'}</td>
                    <td>${statement.affiliation || '-'}</td>
                    <td class="text-right">${numberWithCommas(statement.total_payment)}</td>
                    <td class="text-right">${numberWithCommas(statement.total_deduction)}</td>
                    <td class="text-right">${numberWithCommas(statement.net_payment)}</td>
                    <td class="text-center">
                        ${statement.approved_at ? '<span class="badge bg-success">승인완료</span>' : '<span class="badge bg-warning">승인대기</span>'}
                    </td>
                </tr>
            `);
        });

        // 체크박스 이벤트 다시 연결
        $('.social-insurance-statement-select').on('change', function() {
            updateSocialInsuranceSelectAll();
        });
    }

    // 전체 선택/해제 체크박스 이벤트
    $('#socialInsuranceSelectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.social-insurance-statement-select').prop('checked', isChecked);
    });

    // 개별 체크박스 변경 시 전체 선택 체크박스 상태 업데이트
    function updateSocialInsuranceSelectAll() {
        const totalItems = $('.social-insurance-statement-select').length;
        const checkedItems = $('.social-insurance-statement-select:checked').length;
        
        $('#socialInsuranceSelectAll').prop('checked', totalItems > 0 && totalItems === checkedItems);
    }

    // 사대보험 동기화 버튼 이벤트
    $('#syncSocialInsuranceBtn').on('click', function() {
        const selectedIds = [];
        $('.social-insurance-statement-select:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('동기화할 급여명세서를 선택해주세요.');
            return;
        }

        // 선택된 보험 유형 가져오기
        const insuranceTypes = [];
        $('.form-check-input:checked').each(function() {
            insuranceTypes.push($(this).val());
        });

        if (insuranceTypes.length === 0) {
            alert('적용할 보험 유형을 선택해주세요.');
            return;
        }

        const statementDate = $('#socialInsuranceDate').val();
        if (!statementDate) {
            alert('귀속년월을 선택해주세요.');
            return;
        }

        // 로딩 상태 표시
        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('처리 중...');

        // API 호출
        $.ajax({
            url: "{{ route('admin.salary-statements.sync-social-insurance') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                statement_ids: selectedIds,
                insurance_types: insuranceTypes,
                statement_date: statementDate
            },
            success: function(response) {
                btn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    alert(`선택한 ${selectedIds.length}개의 급여명세서에 대해 ${response.updated_count}개의 사대보험 정보가 성공적으로 동기화되었습니다.`);
                    $('#socialInsuranceModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || '사대보험 동기화 중 오류가 발생했습니다.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text(originalText);
                alert(xhr.responseJSON?.message || '사대보험 동기화 중 오류가 발생했습니다.');
                console.error('Error:', xhr);
            }
        });
    });
});

</script>
@endpush
