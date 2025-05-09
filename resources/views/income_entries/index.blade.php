@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    

    <!-- 필터 섹션 추가 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('income_entries.index') }}" class="row g-3">
                <!-- 기간 필터 -->
                <div class="col-md-3">
                    <label class="form-label">기간</label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="start_date" value="{{ request('start_date', date('Y-m-01')) }}">
                        <span class="input-group-text">~</span>
                        <input type="date" class="form-control" name="end_date" value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <!-- 금액 필터 -->
                <div class="col-md-3">
                    <label class="form-label">금액 범위</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="min_amount" placeholder="최소금액" value="{{ request('min_amount') }}">
                        <span class="input-group-text">~</span>
                        <input type="number" class="form-control" name="max_amount" placeholder="최대금액" value="{{ request('max_amount') }}">
                    </div>
                </div>

                <!-- 고객명 필터 -->
                <div class="col-md-2">
                    <label class="form-label">고객명</label>
                    <input type="text" class="form-control" name="depositor_name" value="{{ request('depositor_name') }}" placeholder="고객명 검색">
                </div>

                <!-- 계정 필터 -->
                <div class="col-md-2">
                    <label class="form-label">계정</label>
                    <select class="form-control" name="account_type">
                        <option value="">전체</option>
                        <option value="서비스매출" {{ request('account_type') == '서비스매출' ? 'selected' : '' }}>서비스매출</option>
                        <option value="송인부" {{ request('account_type') == '송인부' ? 'selected' : '' }}>송인부</option>
                    </select>
                </div>

                <!-- 담당자 필터 -->
                <div class="col-md-2">
                    <label class="form-label">담당자</label>
                    <select class="form-control" name="representative_id">
                        <option value="">전체</option>
                        @foreach($representatives as $representative)
                            <option value="{{ $representative->id }}" {{ request('representative_id') == $representative->id ? 'selected' : '' }}>
                                {{ $representative->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 버모 필터 추가 (담당자 필터 다음에 추가) -->
                <div class="col-md-2">
                    <label class="form-label">메모</label>
                    <input type="text" class="form-control" name="memo" value="{{ request('memo') }}" placeholder="메모 검색">
                </div>

                <!-- 버튼들을 맨 아래로 이동하고 좌측 정렬 -->
                <div class="col-12 mt-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">검색</button>
                        <a href="{{ route('income_entries.index') }}" class="btn btn-secondary">초기화</a>
                        <a href="{{ route('income_entries.export') }}?{{ http_build_query(request()->all()) }}" 
                           class="btn btn-success"
                           onclick="return confirm('현재 필터링된 모든 데이터를 다운로드하시겠습니까?');">
                            CSV 다운로드
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle me-1"></i>신규등록
        </button>
        <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#songInBuModal">
            <i class="bi bi-arrow-left-right me-1"></i>송인부처리
        </button>
    </div>

    <!-- 테이블 내용 -->
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th style="width: 100px">입금일자</th>
                <th style="width: 120px">입금자명</th>
                <th style="width: 100px">입금액</th>
                <th style="width: 100px">담당자</th>
                <th style="width: 100px">계정</th>
                <th>메모</th>
                <th style="width: 80px">관리</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomeEntries as $entry)
                <tr>
                    <td class="text-nowrap">{{ $entry->deposit_date }}</td>
                    <td class="text-nowrap">{{ $entry->depositor_name }}</td>
                    <td class="text-nowrap text-end">{{ number_format($entry->amount) }}원</td>
                    <td class="text-nowrap">{{ $entry->representative->name ?? '' }}</td>
                    <td class="text-nowrap">{{ $entry->account_type }}</td>
                    <td>{{ $entry->memo }}</td>
                    <td class="text-nowrap text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm" 
                                    onclick="editEntry({{ $entry->id }})"
                                    title="수정">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-danger btn-sm" 
                                    onclick="showDeleteModal({{ $entry->id }}, '{{ $entry->deposit_date }}', '{{ $entry->depositor_name }}', {{ $entry->amount }}, '{{ $entry->representative->name }}', '{{ $entry->account_type }}', '{{ $entry->memo }}')"
                                    title="삭제">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 테이블 다음에 추가 -->
    <div class="d-flex justify-content-center mt-4">
        {{ $incomeEntries->appends(request()->query())->links() }}
    </div>

    <!-- 신규등록 모달 -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">매출 신규등록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" action="{{ route('income_entries.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="deposit_date" class="form-label">입금일자</label>
                            <input type="date" class="form-control" id="deposit_date" name="deposit_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="depositor_name" class="form-label">입금자명</label>
                            <input type="text" class="form-control" id="depositor_name" name="depositor_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">입금액</label>
                            <input type="text" class="form-control amount-input" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="representative_id" class="form-label">담당자</label>
                            <select class="form-control" id="representative_id" name="representative_id" required>
                                <option value="">선택안함</option>
                                @foreach($representatives as $representative)
                                    <option value="{{ $representative->id }}">{{ $representative->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="account_type" class="form-label">계정</label>
                            <select class="form-control" id="account_type" name="account_type" required>
                                <option value="">선택안함</option>
                                <option value="서비스매출">서비스매출</option>
                                <option value="송인부">송인부</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="memo" class="form-label">메모</label>
                            <textarea class="form-control" id="memo" name="memo"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('createForm').submit()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 수정 모달 -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">매출 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="current_page" value="{{ $incomeEntries->currentPage() }}">
                        <div class="mb-3">
                            <label for="edit_deposit_date" class="form-label">입금일자</label>
                            <input type="date" class="form-control" id="edit_deposit_date" name="deposit_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_depositor_name" class="form-label">입금자명</label>
                            <input type="text" class="form-control" id="edit_depositor_name" name="depositor_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amount" class="form-label">입금액</label>
                            <input type="text" class="form-control amount-input" id="edit_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_representative_id" class="form-label">담당자</label>
                            <select class="form-control" id="edit_representative_id" name="representative_id" required>
                                <option value="">선택안함</option>
                                @foreach($representatives as $representative)
                                    <option value="{{ $representative->id }}">{{ $representative->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_account_type" class="form-label">계정</label>
                            <select class="form-control" id="edit_account_type" name="account_type" required>
                                <option value="">선택안함</option>
                                <option value="서비스매출">서비스매출</option>
                                <option value="송인부">송인부</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_memo" class="form-label">메모</label>
                            <textarea class="form-control" id="edit_memo" name="memo"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').submit()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 송인부처리 모달 (수정 모달 다음에 추가) -->
    <div class="modal fade" id="songInBuModal" tabindex="-1" aria-labelledby="songInBuModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="songInBuModalLabel">송인부처리</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="songInBuForm" action="{{ route('income_entries.store_songinbu') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="songinbu_deposit_date" class="form-label">입금일자</label>
                            <input type="date" class="form-control" id="songinbu_deposit_date" name="deposit_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="songinbu_depositor_name" class="form-label">입금자명</label>
                            <input type="text" class="form-control" id="songinbu_depositor_name" name="depositor_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="songinbu_amount" class="form-label">입금액</label>
                            <input type="text" class="form-control amount-input" id="songinbu_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="songinbu_representative_id" class="form-label">담당자</label>
                            <select class="form-control" id="songinbu_representative_id" name="representative_id" required>
                                <option value="">선택안함</option>
                                @foreach($representatives as $representative)
                                    <option value="{{ $representative->id }}">{{ $representative->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="songinbu_memo" class="form-label">메모</label>
                            <textarea class="form-control" id="songinbu_memo" name="memo"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('songInBuForm').submit()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 삭제 확인 모달 추가 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">삭제 확인</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>다음 데이터를 정말 삭제하시겠습니까?</p>
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%">입금일자</th>
                            <td id="delete_deposit_date"></td>
                        </tr>
                        <tr>
                            <th>입금자명</th>
                            <td id="delete_depositor_name"></td>
                        </tr>
                        <tr>
                            <th>입금액</th>
                            <td id="delete_amount"></td>
                        </tr>
                        <tr>
                            <th>담당자</th>
                            <td id="delete_representative"></td>
                        </tr>
                        <tr>
                            <th>계정</th>
                            <td id="delete_account_type"></td>
                        </tr>
                        <tr>
                            <th>메모</th>
                            <td id="delete_memo"></td>
                        </tr>
                    </table>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit()">삭제</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 기 스타일 -->
    <style>
        .btn-group-sm > .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .btn-group-sm i {
            font-size: 1rem;
        }

        .table td {
            vertical-align: middle;
        }

        .btn-group > .btn {
            margin-right: 2px;
        }

        /* 페이지네이션 스타일 추가 */
        .pagination {
            margin-bottom: 20px;
        }
        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .page-link {
            color: #0d6efd;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .input-group-text {
            background-color: #f8f9fa;
        }

        .form-label {
            font-weight: 500;
        }

        .table th, .table td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .form-control, .form-select, .btn {
            font-size: 0.85rem;
        }
        .modal-body {
            font-size: 0.85rem;
        }

        /* 메모 필드를 제외한 모든 셀의 텍스트가 줄바꿈되지 않도록 설정 */
        .text-nowrap {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* 메모 필드는 자동 줄바꿈 허용 */
        .table td:nth-child(6) {
            white-space: normal;
            word-break: break-word;
        }
    </style>

    <!-- JavaScript 추가 -->
    <script>
        // 천단위 구분기호 포맷팅 함수
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // 쉼표 제거 함수
        function removeCommas(str) {
            return str.replace(/,/g, '');
        }

        // 모든 금액 입력 필드에 자동 쉼표 추가
        document.addEventListener('DOMContentLoaded', function() {
            // 금액 입력 필드에 이벤트 리스너 추가
            document.querySelectorAll('.amount-input').forEach(function(input) {
                input.addEventListener('input', function(e) {
                    // 숫자, 쉼표, 마이너스 기호만 허용
                    let value = this.value.replace(/[^\d\-]/g, '');
                    
                    // 마이너스 기호는 맨 앞에만 허용
                    if (value.startsWith('-')) {
                        value = '-' + value.substring(1).replace(/\-/g, '');
                    } else {
                        value = value.replace(/\-/g, '');
                    }
                    
                    // 숫자 부분에만 쉼표 적용
                    if (value.startsWith('-')) {
                        const numPart = value.substring(1);
                        if (numPart) {
                            this.value = '-' + numberWithCommas(numPart);
                        } else {
                            this.value = '-';
                        }
                    } else {
                        this.value = numberWithCommas(value);
                    }
                });
            });

            // 폼 제출 전 쉼표 제거
            document.getElementById('createForm').addEventListener('submit', function(e) {
                document.querySelectorAll('.amount-input').forEach(function(input) {
                    input.value = removeCommas(input.value);
                });
            });

            document.getElementById('editForm').addEventListener('submit', function(e) {
                document.querySelectorAll('.amount-input').forEach(function(input) {
                    input.value = removeCommas(input.value);
                });
            });

            document.getElementById('songInBuForm').addEventListener('submit', function(e) {
                document.querySelectorAll('.amount-input').forEach(function(input) {
                    input.value = removeCommas(input.value);
                });
            });
        });

        function editEntry(id) {
            fetch(`/income_entries/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    // 폼 필드에 데이터 설정
                    document.getElementById('edit_deposit_date').value = data.deposit_date;
                    document.getElementById('edit_depositor_name').value = data.depositor_name;
                    
                    // 음수 값 처리
                    const amount = data.amount;
                    if (amount < 0) {
                        document.getElementById('edit_amount').value = '-' + numberWithCommas(Math.abs(amount));
                    } else {
                        document.getElementById('edit_amount').value = numberWithCommas(amount);
                    }
                    
                    document.getElementById('edit_representative_id').value = data.representative_id || '';
                    document.getElementById('edit_account_type').value = data.account_type || '';
                    document.getElementById('edit_memo').value = data.memo || '';

                    // 폼 action 설정
                    const form = document.getElementById('editForm');
                    form.action = `/income_entries/${id}`;

                    // 모달 표시
                    new bootstrap.Modal(document.getElementById('editModal')).show();
                })
                .catch(error => console.error('Error:', error));
        }

        function showDeleteModal(id, deposit_date, depositor_name, amount, representative, account_type, memo) {
            // 모달 내용 설정
            document.getElementById('delete_deposit_date').textContent = deposit_date;
            document.getElementById('delete_depositor_name').textContent = depositor_name;
            document.getElementById('delete_amount').textContent = new Intl.NumberFormat('ko-KR').format(amount) + '원';
            document.getElementById('delete_representative').textContent = representative;
            document.getElementById('delete_account_type').textContent = account_type;
            document.getElementById('delete_memo').textContent = memo || '-';

            // 삭제 폼 action 설정
            document.getElementById('deleteForm').action = `/income_entries/${id}`;

            // 모달 표시
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</div>
@endsection
