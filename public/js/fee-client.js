// 고객별 수임료 납부 관리 JS
document.addEventListener('DOMContentLoaded', function() {
    // CSRF 토큰 설정
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // 전역 변수
    let currentPage = 1;
    let selectedCaseIdx = null;
    let currentFilters = {};
    
    // 페이지 로드 시 사용자 정보와 멤버 목록 불러오기, 그 후 고객 목록 불러오기
    loadUserData();
    
    // 미해결 오류 loadClients is not defined를 해결하기 위한 이벤트 리스너 추가
    document.addEventListener('initFeeClient', function() {
        loadClients();
    });
    
    // 다른 스크립트와의 연동을 위한 이벤트 리스너 추가
    document.addEventListener('applyFeeClientFilters', function() {
        applyFilters();
    });
    
    document.addEventListener('resetFeeClientFilters', function() {
        currentFilters = {};
        loadClients(1);
    });
    
    // 메모 업데이트 후 고객 상세 정보 새로고침
    document.addEventListener('memoUpdated', function() {
        console.log('memoUpdated 이벤트 발생 - 고객 상세 정보 새로고침');
        if (selectedCaseIdx) {
            console.log('새로고침할 고객 정보:', selectedCaseIdx);
            loadClientDetail(selectedCaseIdx);
        }
    });
    
    // 필터 및 검색 영역 토글
    document.getElementById('btnFilter').addEventListener('click', function() {
        const filterArea = document.getElementById('filterArea');
        const searchArea = document.getElementById('searchArea');
        
        if (filterArea.classList.contains('d-none')) {
            filterArea.classList.remove('d-none');
            searchArea.classList.add('d-none');
        } else {
            filterArea.classList.add('d-none');
        }
    });
    
    document.getElementById('btnSearch').addEventListener('click', function() {
        const filterArea = document.getElementById('filterArea');
        const searchArea = document.getElementById('searchArea');
        
        if (searchArea.classList.contains('d-none')) {
            searchArea.classList.remove('d-none');
            filterArea.classList.add('d-none');
        } else {
            searchArea.classList.add('d-none');
        }
    });
    
    // 필터 변경 이벤트
    const filterElements = [
        'filterPaymentStatus', 'filterContractStatus', 'filterCaseType', 
        'filterConsultant', 'filterManager', 'filterStartDate', 'filterEndDate'
    ];
    
    filterElements.forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            applyFilters();
        });
    });
    
    // 검색 버튼 클릭 이벤트
    document.getElementById('btnDoSearch').addEventListener('click', function() {
        const searchType = document.getElementById('searchType').value;
        const searchKeyword = document.getElementById('searchKeyword').value;
        
        if (searchKeyword.trim() === '') {
            alert('검색어를 입력하세요.');
            return;
        }
        
        currentFilters = {
            search_type: searchType,
            search_keyword: searchKeyword
        };
        
        loadClients(1);
    });
    
    // 현재 로그인한 사용자 정보를 가져와 기본 필터값 설정
    function loadUserData() {
        fetch('/fee-client/user-data')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('사용자 정보 로드 성공:', data);
                    
                    // 멤버 목록 로드 후 필터 기본값 설정
                    loadMembers(data.defaultConsultant, data.defaultManager);
                } else {
                    console.warn('사용자 정보 로드 실패:', data.message);
                    // 기본값 없이 멤버 목록 로드
                    loadMembers();
                }
            })
            .catch(error => {
                console.error('사용자 정보를 불러오는 중 오류가 발생했습니다:', error);
                // 오류 발생 시 기본값 없이 멤버 목록 로드
                loadMembers();
            });
    }
    
    // 멤버 목록 로드 (상담자 및 담당자 필터용)
    function loadMembers(defaultConsultant = '', defaultManager = '') {
        fetch('/fee-client/members')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.members) {
                    // 상담자 드롭다운 채우기
                    const consultantDropdown = document.getElementById('filterConsultant');
                    consultantDropdown.innerHTML = '<option value="all">전체</option>';
                    
                    // 담당자 드롭다운 채우기
                    const managerDropdown = document.getElementById('filterManager');
                    managerDropdown.innerHTML = '<option value="all">전체</option>';
                    
                    // 멤버 목록으로 드롭다운 채우기
                    data.members.forEach(member => {
                        // 상담자 옵션 추가
                        const consultantOption = document.createElement('option');
                        consultantOption.value = member.name;
                        consultantOption.textContent = member.name;
                        consultantDropdown.appendChild(consultantOption);
                        
                        // 담당자 옵션 추가
                        const managerOption = document.createElement('option');
                        managerOption.value = member.name;
                        managerOption.textContent = member.name;
                        managerDropdown.appendChild(managerOption);
                    });
                    
                    // 기본값 설정
                    if (defaultConsultant) {
                        consultantDropdown.value = defaultConsultant;
                        
                        // 기본값이 있으면 필터에 추가
                        currentFilters.consultant = defaultConsultant;
                    }
                    
                    if (defaultManager) {
                        managerDropdown.value = defaultManager;
                        
                        // 기본값이 있으면 필터에 추가
                        currentFilters.case_manager = defaultManager;
                    }
                    
                    // 고객 목록 로드
                    loadClients();
                } else {
                    // 오류 발생 시 필터 없이 고객 목록 로드
                    loadClients();
                }
            })
            .catch(error => {
                console.error('멤버 목록을 불러오는 중 오류가 발생했습니다:', error);
                // 오류 발생 시 필터 없이 고객 목록 로드
                loadClients();
            });
    }
    
    // 고객 목록 로드
    function loadClients(page = 1) {
        currentPage = page;
        
        // 로딩 표시
        document.querySelector('#clientsTable tbody').innerHTML = `
            <tr>
                <td colspan="14" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">로딩 중...</span>
                    </div>
                    <p class="mt-2">고객 목록을 불러오는 중...</p>
                </td>
            </tr>
        `;
        
        // API 요청 파라미터 구성
        const params = new URLSearchParams({
            page: page,
            per_page: 10,
            ...currentFilters
        });
        
        // 고객 목록 API 호출
        fetch(`/fee-client/clients?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // 데이터 유효성 검사
                if (!data || typeof data !== 'object') {
                    console.error('Invalid data format:', data);
                    throw new Error('Invalid data format received from server');
                }
                renderClientsList(data);
            })
            .catch(error => {
                console.error('Error fetching clients:', error);
                document.querySelector('#clientsTable tbody').innerHTML = `
                    <tr>
                        <td colspan="14" class="text-center py-5">
                            <div class="alert alert-danger mb-0">
                                데이터를 불러오는 중 오류가 발생했습니다: ${error.message}
                            </div>
                        </td>
                    </tr>
                `;
                
                // 통계 및 페이지네이션 초기화
                document.getElementById('totalClients').textContent = '0';
                document.getElementById('completedClients').textContent = '0';
                document.getElementById('unpaidClients').textContent = '0';
                document.getElementById('overdueClients').textContent = '0';
                document.getElementById('pagination').innerHTML = '';
            });
    }
    
    // 고객 목록 렌더링
    function renderClientsList(data) {
        const { clients, pagination, summary } = data;
        let html = '';
        
        // clients가 배열인지 확인하고, 배열이 아니면 빈 배열로 처리
        const clientsArray = Array.isArray(clients) ? clients : [];
        
        if (clientsArray.length === 0) {
            html = `
                <tr>
                    <td colspan="14" class="text-center py-5">
                        해당 조건의 고객이 없습니다.
                    </td>
                </tr>
            `;
        } else {
            clientsArray.forEach(client => {
                // 행 배경색 설정을 위한 클래스
                let rowClass = '';
                let rowStyle = '';
                
                // 계약해지는 최우선 적용
                if (client.contract_status === '계약해지') {
                    rowClass = 'bg-soft-danger';
                    rowStyle = 'background-color: rgba(220, 53, 69, 0.15) !important; --bs-table-accent-bg: rgba(220, 53, 69, 0.15) !important;';
                }
                // 그 다음으로 완납 상태 체크
                else if (client.payment_status === '완납') {
                    rowClass = 'bg-soft-success green-row';
                    rowStyle = 'background-color: rgba(25, 135, 84, 0.15) !important; --bs-table-accent-bg: rgba(25, 135, 84, 0.15) !important;';
                    console.log('완납 상태 클라이언트:', client.name, '납부상태:', client.payment_status);
                }
                
                html += `
                    <tr data-case-idx="${client.idx_TblCase}" class="client-row ${rowClass}" style="${rowStyle}">
                        <td>${formatDate(client.contract_date)}</td>
                        <td>${client.name || ''}</td>
                        <td>${getCaseTypeText(client.case_type)}</td>
                        <td>${getCaseStateText(client.case_state, client.case_type)}</td>
                        <td>${client.Member && client.Member !== '미지정' ? client.Member : ''}</td>
                        <td>${client.case_manager && client.case_manager !== '미지정' ? client.case_manager : ''}</td>
                        <td class="text-center">
                            ${getFeeProgressBar(client.paid_fee || 0, client.total_fee || 0)}
                        </td>
                        <td class="text-center">${getDocumentBadge(client.id_card_status)}</td>
                        <td class="text-center">${getDocumentBadge(client.seal_status)}</td>
                        <td class="text-center">${getProgressBar(client.first_docs_completed, client.first_docs_total, client.first_docs_request)}</td>
                        <td class="text-center">${getProgressBar(client.second_docs_completed, client.second_docs_total, client.second_docs_request)}</td>
                        <td class="text-center">${getProgressBar(client.debt_docs_completed, client.debt_docs_total, client.debt_docs_request)}</td>
                        <td class="text-center">${getStatusBadge(client.payment_status)}</td>
                        <td class="text-center">${getStatusBadge(client.contract_status)}</td>
                    </tr>
                    <tr class="detail-row d-none" id="detail-${client.idx_TblCase}">
                        <td colspan="14" class="p-0">
                            <div class="client-detail-container p-3 border-top">
                                <div class="loading-indicator text-center py-5 d-none">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">로딩 중...</span>
                                    </div>
                                    <p class="mt-2">고객 상세 정보를 불러오는 중...</p>
                                </div>
                                <div class="client-detail-content"></div>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        // 테이블 업데이트
        document.querySelector('#clientsTable tbody').innerHTML = html;
        
        // 완납 행에 스타일 직접 적용 (추가 보장)
        setTimeout(() => {
            document.querySelectorAll('#clientsTable tbody tr.client-row.bg-soft-success').forEach(row => {
                console.log('완납 행 스타일 직접 적용:', row);
                
                // 인라인 스타일 강제 적용
                row.setAttribute('style', 'background-color: rgba(25, 135, 84, 0.15) !important; --bs-table-accent-bg: rgba(25, 135, 84, 0.15) !important;');
                
                // 모든 셀에도 배경색 적용
                row.querySelectorAll('td').forEach(cell => {
                    cell.setAttribute('style', 'background-color: rgba(25, 135, 84, 0.15) !important;');
                });
                
                // .bg-success 클래스 추가 (Bootstrap의 기본 배경색 클래스)
                row.classList.add('table-success');
                
                // 트릭: 임시로 클래스 제거했다가 다시 추가하면 스타일이 다시 계산됨
                row.classList.remove('bg-soft-success');
                setTimeout(() => row.classList.add('bg-soft-success'), 10);
            });
        }, 100);
        
        // 통계 업데이트 - 데이터가 없는 경우 기본값 설정
        document.getElementById('totalClients').textContent = summary?.total_clients || 0;
        document.getElementById('completedClients').textContent = summary?.completed_clients || 0;
        document.getElementById('unpaidClients').textContent = summary?.unpaid_clients || 0;
        document.getElementById('overdueClients').textContent = summary?.overdue_clients || 0;
        
        // 페이지네이션 업데이트 - pagination이 있는 경우에만 처리
        if (pagination) {
            renderPagination(pagination);
        }
        
        // 행 클릭 이벤트 추가
        document.querySelectorAll('#clientsTable tbody tr.client-row').forEach(row => {
            row.addEventListener('click', function() {
                const caseIdx = this.getAttribute('data-case-idx');
                if (caseIdx) {
                    toggleClientDetail(caseIdx);
                }
            });
            
            // 호버 효과 추가
            row.addEventListener('mouseenter', function() {
                if (this.classList.contains('bg-soft-success')) {
                    this.style.backgroundColor = 'rgba(25, 135, 84, 0.25) !important';
                } else if (this.classList.contains('bg-soft-danger')) {
                    this.style.backgroundColor = 'rgba(220, 53, 69, 0.25) !important';
                }
            });
            
            row.addEventListener('mouseleave', function() {
                if (this.classList.contains('bg-soft-success')) {
                    this.style.backgroundColor = 'rgba(25, 135, 84, 0.15) !important';
                } else if (this.classList.contains('bg-soft-danger')) {
                    this.style.backgroundColor = 'rgba(220, 53, 69, 0.15) !important';
                }
            });
        });
    }
    
    // 고객 상세 정보 토글
    function toggleClientDetail(caseIdx) {
        selectedCaseIdx = caseIdx;
        const detailRow = document.getElementById(`detail-${caseIdx}`);
        
        // 모든 다른 열린 행 닫기
        document.querySelectorAll('.detail-row:not(.d-none)').forEach(row => {
            if (row.id !== `detail-${caseIdx}`) {
                row.classList.add('d-none');
            }
        });
        
        // 클릭한 행 토글
        if (detailRow.classList.contains('d-none')) {
            detailRow.classList.remove('d-none');
            loadClientDetail(caseIdx);
        } else {
            detailRow.classList.add('d-none');
        }
    }
    
    // 페이지네이션 렌더링
    function renderPagination(pagination) {
        const { current_page, last_page } = pagination;
        let html = '';
        
        // 이전 페이지 버튼을 처음 페이지로 이동하는 버튼으로 변경
        html += `
            <li class="page-item ${current_page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="1" aria-label="First">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // 페이지 번호
        let startPage = Math.max(1, current_page - 10);
        let endPage = Math.min(last_page, current_page + 10);
        
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <li class="page-item ${i === current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // 다음 페이지 버튼을 마지막 페이지로 이동하는 버튼으로 변경
        html += `
            <li class="page-item ${current_page >= last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${last_page}" aria-label="Last">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        // 페이지네이션 업데이트
        document.getElementById('pagination').innerHTML = html;
        
        // 페이지네이션 이벤트 추가
        document.querySelectorAll('#pagination .page-link').forEach(link => {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                const page = parseInt(this.getAttribute('data-page'), 10);
                if (!isNaN(page) && page > 0) {
                    loadClients(page);
                }
            });
        });
    }
    
    // 고객 상세 정보 로드
    function loadClientDetail(caseIdx) {
        const detailRow = document.getElementById(`detail-${caseIdx}`);
        const loadingIndicator = detailRow.querySelector('.loading-indicator');
        const contentContainer = detailRow.querySelector('.client-detail-content');
        
        // 로딩 표시
        loadingIndicator.classList.remove('d-none');
        contentContainer.innerHTML = '';
        
        // 고객 상세 정보 API 호출
        fetch(`/fee-client/clients/${caseIdx}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.classList.add('d-none');
                renderClientDetailContent(contentContainer, data);
            })
            .catch(error => {
                console.error('Error fetching client detail:', error);
                loadingIndicator.classList.add('d-none');
                contentContainer.innerHTML = `
                    <div class="alert alert-danger">
                        데이터를 불러오는 중 오류가 발생했습니다.
                    </div>
                `;
            });
    }
    
    // 고객 상세 정보 렌더링
    function renderClientDetailContent(container, data) {
        const { client, fee_details, docs_info, doc_request_status } = data;
        
        // 고객 상세 정보 HTML
        let html = `
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2">고객 정보</h5>
                </div>
                <div class="col-12">
                    <table class="table table-sm table-bordered">
                        <tr>
                            <th class="bg-light text-center" width="15%">고객명</th>
                            <th class="bg-light text-center" width="25%">전화번호</th>
                            <th class="bg-light text-center" width="20%">사건분야</th>
                            <th class="bg-light text-center" width="20%">상담자</th>
                            <th class="bg-light text-center" width="20%">담당자</th>
                        </tr>
                        <tr>
                            <td class="text-center">${client.name || ''}</td>
                            <td class="text-center">${client.phone || '-'}</td>
                            <td class="text-center">${getCaseTypeText(client.case_type)}</td>
                            <td class="text-center">${client.Member && client.Member !== '미지정' ? client.Member : ''}</td>
                            <td class="text-center">${client.case_manager && client.case_manager !== '미지정' ? client.case_manager : ''}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 서류 발급 영역 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2">서류 발급</h5>
                </div>
                <div class="col-md-12 mb-2">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="requestIdCard-${client.idx_TblCase}" 
                               ${doc_request_status.id_request ? 'checked' : ''}>
                        <label class="form-check-label" for="requestIdCard-${client.idx_TblCase}">신분증 요청</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="requestSeal-${client.idx_TblCase}"
                               ${doc_request_status.seal_request ? 'checked' : ''}>
                        <label class="form-check-label" for="requestSeal-${client.idx_TblCase}">인감 요청</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="requestFirstDoc-${client.idx_TblCase}"
                               ${doc_request_status.first_doc_request ? 'checked' : ''}>
                        <label class="form-check-label" for="requestFirstDoc-${client.idx_TblCase}">1차서류 발급요청</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="requestSecondDoc-${client.idx_TblCase}"
                               ${doc_request_status.second_doc_request ? 'checked' : ''}>
                        <label class="form-check-label" for="requestSecondDoc-${client.idx_TblCase}">2차서류 발급요청</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="requestDebtCert-${client.idx_TblCase}"
                               ${doc_request_status.debt_cert_request ? 'checked' : ''}>
                        <label class="form-check-label" for="requestDebtCert-${client.idx_TblCase}">부채증명서 발급요청</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input doc-request-checkbox" type="checkbox" id="terminateContract-${client.idx_TblCase}"
                               ${doc_request_status.contract_termination ? 'checked' : ''}>
                        <label class="form-check-label" for="terminateContract-${client.idx_TblCase}">계약해지</label>
                    </div>
                    <button class="btn btn-sm btn-primary ms-3" id="saveDocRequest-${client.idx_TblCase}">저장하기</button>
                </div>
            </div>
            
            <!-- 납부 내역 테이블 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <h5 class="border-bottom pb-2">납부 내역</h5>
                </div>
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>항목</th>
                                    <th>예정일</th>
                                    <th class="text-end">금액</th>
                                    <th class="text-center">상태</th>
                                    <th>납부일</th>
                                    <th>납부방법</th>
                                    <th class="text-center">메모</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        if (fee_details.length === 0) {
            html += `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        납부 내역이 없습니다.
                    </td>
                </tr>
            `;
        } else {
            // fee_type 순서대로 정렬: 미지정(-1), 송달료 등 부대비용(0), 착수금(1), 분할납부(2), 성공보수(3)
            const sortedDetails = [...fee_details].sort((a, b) => {
                // 먼저 fee_type으로 정렬
                if (a.fee_type !== b.fee_type) {
                    return a.fee_type - b.fee_type;
                }
                
                // 분할납부는 차수에 따라 정렬
                if (a.fee_type === 2 && b.fee_type === 2) {
                    // 차수를 추출 (예: "1차 분할납부"에서 1을 추출)
                    const aOrder = parseInt(a.fee_type_text.match(/^(\d+)차/)?.[1] || 0);
                    const bOrder = parseInt(b.fee_type_text.match(/^(\d+)차/)?.[1] || 0);
                    return aOrder - bOrder;
                }
                
                // 같은 유형이면 날짜순
                return new Date(a.scheduled_date || '9999-12-31') - new Date(b.scheduled_date || '9999-12-31');
            });
            
            sortedDetails.forEach(detail => {
                html += `
                    <tr>
                        <td>${detail.fee_type_text}</td>
                        <td>${detail.scheduled_date ? formatDate(detail.scheduled_date) : '-'}</td>
                        <td class="text-end">${formatNumber(detail.money)}</td>
                        <td class="text-center">${getPaymentStatusButton(detail)}</td>
                        <td>${detail.settlement_date ? formatDate(detail.settlement_date) : '-'}</td>
                        <td>${detail.settlement_method || '-'}</td>
                        <td class="text-center">
                            ${renderMemoButton(detail.id, detail.memo)}
                        </td>
                    </tr>
                `;
            });
        }
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // 메모 버튼 이벤트 추가 (납부 상태 버튼 이벤트는 제거)
        container.querySelectorAll('.memo-btn').forEach(button => {
            console.log('메모 버튼에 이벤트 리스너 추가:', button);
            
            button.addEventListener('click', function(e) {
                console.log('메모 버튼 클릭됨!', this);
                e.preventDefault();
                e.stopPropagation();
                
                const id = this.getAttribute('data-id');
                const memo = this.getAttribute('data-memo');
                
                console.log('메모 모달 열기 시도:', { id, memo });
                
                openMemoModal(id, memo);
            });
        });
        
        // 상세 컨테이너에 클릭 이벤트가 상위로 전파되지 않도록 이벤트 리스너 추가
        const detailContainer = container.closest('.client-detail-container');
        if (detailContainer) {
            detailContainer.addEventListener('click', function(e) {
                // 이벤트 전파 중지
                e.stopPropagation();
            });
            
            // 상세 정보 내 모든 테이블 행과 셀에 대해 클릭 이벤트 전파 중지
            const tables = detailContainer.querySelectorAll('table');
            tables.forEach(table => {
                table.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                
                // 테이블 내 모든 행과 셀에 이벤트 리스너 추가
                const rows = table.querySelectorAll('tr');
                rows.forEach(row => {
                    row.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                    
                    const cells = row.querySelectorAll('td, th');
                    cells.forEach(cell => {
                        cell.addEventListener('click', function(e) {
                            e.stopPropagation();
                        });
                    });
                });
            });
        }
        
        // 서류 발급 저장 버튼 이벤트 추가
        const saveDocRequestBtn = container.querySelector(`#saveDocRequest-${client.idx_TblCase}`);
        if (saveDocRequestBtn) {
            saveDocRequestBtn.addEventListener('click', function() {
                // 체크박스 상태 수집
                const idRequest = container.querySelector(`#requestIdCard-${client.idx_TblCase}`).checked;
                const sealRequest = container.querySelector(`#requestSeal-${client.idx_TblCase}`).checked;
                const firstDocRequest = container.querySelector(`#requestFirstDoc-${client.idx_TblCase}`).checked;
                const secondDocRequest = container.querySelector(`#requestSecondDoc-${client.idx_TblCase}`).checked;
                const debtCertRequest = container.querySelector(`#requestDebtCert-${client.idx_TblCase}`).checked;
                const contractTermination = container.querySelector(`#terminateContract-${client.idx_TblCase}`).checked;
                
                // API 요청 데이터 구성
                const data = {
                    id_request: idRequest,
                    seal_request: sealRequest,
                    first_doc_request: firstDocRequest,
                    second_doc_request: secondDocRequest,
                    debt_cert_request: debtCertRequest,
                    contract_termination: contractTermination
                };
                
                // 저장 버튼 비활성화 및 로딩 표시
                saveDocRequestBtn.disabled = true;
                saveDocRequestBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 저장 중...';
                
                // 서류 발급 요청 상태 업데이트 API 호출
                fetch(`/fee-client/documents/${client.idx_TblCase}/update-request`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        // 저장 성공 시 클라이언트 목록 새로고침
                        loadClients(currentPage);
                        
                        // 저장 버튼 상태 복원 및 성공 메시지 표시
                        saveDocRequestBtn.disabled = false;
                        saveDocRequestBtn.innerHTML = '저장하기';
                        saveDocRequestBtn.classList.remove('btn-primary');
                        saveDocRequestBtn.classList.add('btn-success');
                        setTimeout(() => {
                            saveDocRequestBtn.classList.remove('btn-success');
                            saveDocRequestBtn.classList.add('btn-primary');
                        }, 1500);
                    } else {
                        alert(result.message || '서류 발급 요청 상태 저장 중 오류가 발생했습니다.');
                        saveDocRequestBtn.disabled = false;
                        saveDocRequestBtn.innerHTML = '저장하기';
                    }
                })
                .catch(error => {
                    console.error('Error updating document request status:', error);
                    alert('서류 발급 요청 상태 저장 중 오류가 발생했습니다.');
                    saveDocRequestBtn.disabled = false;
                    saveDocRequestBtn.innerHTML = '저장하기';
                });
            });
        }
    }
    
    // 필터 적용
    function applyFilters() {
        // 필터 값 수집
        const paymentStatus = document.getElementById('filterPaymentStatus').value;
        const contractStatus = document.getElementById('filterContractStatus').value;
        const caseType = document.getElementById('filterCaseType').value;
        const consultant = document.getElementById('filterConsultant').value;
        const manager = document.getElementById('filterManager').value;
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value;
        
        // 필터 객체 구성
        currentFilters = {};
        
        if (paymentStatus !== 'all') currentFilters.payment_status = paymentStatus;
        if (contractStatus !== 'all') currentFilters.contract_status = contractStatus;
        if (caseType !== 'all') currentFilters.case_type = caseType;
        if (consultant !== 'all') currentFilters.consultant = consultant;
        if (manager !== 'all') currentFilters.case_manager = manager;
        if (startDate) currentFilters.start_date = startDate;
        if (endDate) currentFilters.end_date = endDate;
        
        // 고객 목록 리로드
        loadClients(1);
    }
    
    // 메모 모달 열기
    function openMemoModal(id, memo) {
        console.log('openMemoModal 실행됨:', { id, memo });
        
        // 모달 엘리먼트 확인
        const memoModalElement = document.getElementById('memoModal');
        console.log('메모 모달 엘리먼트:', memoModalElement);
        
        // 폼 필드 확인
        const memoIdField = document.getElementById('memoId');
        const memoTextField = document.getElementById('memo');
        
        if (!memoIdField || !memoTextField) {
            console.error('메모 폼 필드를 찾을 수 없습니다:', { 
                memoIdField: !!memoIdField, 
                memoTextField: !!memoTextField 
            });
            return;
        }
        
        // 폼 값 설정
        memoIdField.value = id;
        memoTextField.value = memo || '';
        
        console.log('메모 폼 값 설정 완료:', { 
            id: memoIdField.value, 
            memo: memoTextField.value 
        });
        
        // 모달 열기
        try {
            const modal = new bootstrap.Modal(memoModalElement);
            modal.show();
            console.log('모달 열기 성공');
        } catch (error) {
            console.error('모달 열기 실패:', error);
        }
    }
    
    // 메모 버튼 렌더링 함수 - 메모 내용이 있으면 노란색 아이콘으로 표시
    function renderMemoButton(id, memo) {
        const buttonClass = memo && memo.trim() !== '' ? 'btn-outline-warning' : 'btn-outline-secondary';
        
        return `
            <button type="button" class="btn btn-sm ${buttonClass} memo-btn" 
                    data-id="${id}" data-memo="${memo || ''}">
                <i class="bi bi-pencil-square"></i>
            </button>
        `;
    }
    
    // 유틸리티 함수들
    function formatDate(dateString) {
        if (!dateString) return '-';
        return moment(dateString).format('YYYY-MM-DD');
    }
    
    function formatNumber(number) {
        return Number(number).toLocaleString('ko-KR');
    }
    
    function getCaseTypeText(type) {
        switch (type) {
            case 1:
                return '개인회생';
            case 2:
                return '개인파산';
            case 3:
                return '기타사건';
            default:
                return '미지정';
        }
    }
    
    function getCaseStateText(stateValue, caseType) {
        // CaseStateHelper에서 정의한 상태 값에 따라 표시
        if (!stateValue) return '미지정';
        
        let stateBadgeClass = 'badge-state-default';
        let stateText = '미지정';
        
        // 개인회생 (1)
        if (caseType == 1) {
            const revivalStates = {
                5: '상담대기',
                10: '상담완료',
                11: '재상담필요',
                15: '계약',
                20: '서류준비',
                21: '부채증명서 발급중',
                22: '부채증명서 발급완료',
                25: '신청서 작성 진행중',
                30: '신청서 제출',
                35: '금지명령',
                40: '보정기간',
                45: '개시결정',
                50: '채권자 집회기일',
                55: '인가결정'
            };
            stateText = revivalStates[stateValue] || `상태값(${stateValue})`;
            
            // 단계별 배지 클래스 적용 (초기/중기/말기)
            if ([5, 10, 11, 15, 20, 21, 22, 25].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-initial'; // 초기단계
            } else if ([30, 35, 40].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-middle'; // 중기단계
            } else if ([45, 50, 55].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-final'; // 말기단계
            }
        }
        
        // 개인파산 (2)
        else if (caseType == 2) {
            const bankruptcyStates = {
                5: '상담대기',
                10: '상담완료',
                11: '재상담필요',
                15: '계약',
                20: '서류준비',
                21: '부채증명서 발급중',
                22: '부채증명서 발급완료',
                25: '신청서 작성 진행중',
                30: '신청서 제출',
                40: '보정기간',
                100: '파산선고',
                105: '의견청취기일',
                110: '재산환가 및 배당',
                115: '파산폐지',
                120: '면책결정',
                125: '면책불허가'
            };
            stateText = bankruptcyStates[stateValue] || `상태값(${stateValue})`;
            
            // 단계별 배지 클래스 적용 (초기/중기/말기)
            if ([5, 10, 11, 15, 20, 21, 22, 25].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-initial'; // 초기단계
            } else if ([30, 40].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-middle'; // 중기단계
            } else if ([100, 105, 110, 115, 120, 125].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-final'; // 말기단계
            }
        }
        
        // 기타 사건(3)도 동일한 배지 스타일 적용
        else if (caseType == 3) {
            // 기타 사건의 상태값에 따른 텍스트 정의
            const otherCaseStates = {
                5: '상담대기',
                10: '상담완료',
                15: '계약',
                20: '서류준비',
                30: '진행중',
                50: '종결'
            };
            stateText = otherCaseStates[stateValue] || `상태값(${stateValue})`;
            
            // 단계별 배지 클래스 적용 (초기/중기/말기)
            if ([5, 10, 15, 20].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-initial'; // 초기단계
            } else if ([30].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-middle'; // 중기단계
            } else if ([50].includes(Number(stateValue))) {
                stateBadgeClass = 'badge-state-final'; // 말기단계
            }
        }
        
        return `<span class="badge ${stateBadgeClass}">${stateText}</span>`;
    }
    
    function getDocumentBadge(status) {
        switch (status) {
            case '요청':
                return '<span class="badge badge-status badge-document-requested">요청</span>';
            case '완료':
                return '<span class="badge badge-status badge-document-completed">완료</span>';
            default:
                return '<span class="badge badge-status badge-document-default">-</span>';
        }
    }
    
    function getStatusBadge(status) {
        switch (status) {
            case '완납':
                return '<span class="badge badge-status badge-completed">완납</span>';
            case '미납':
                return '<span class="badge badge-status badge-pending">미납</span>';
            case '연체':
                return '<span class="badge badge-status badge-overdue">연체</span>';
            case '계약해지':
                return '<span class="badge badge-status badge-overdue">계약해지</span>';
            case '정상':
                return '<span class="badge badge-status badge-completed">정상</span>';
            default:
                return '<span class="badge badge-status badge-document-default">-</span>';
        }
    }
    
    function getFeeProgressBar(completed, total) {
        if (!total) return '-';
        
        const percentage = Math.round((completed / total) * 100);
        let bgColor = 'bg-danger';
        
        if (percentage >= 100) {
            bgColor = 'bg-success';
        } else if (percentage >= 60) {
            bgColor = 'bg-info';
        } else if (percentage >= 30) {
            bgColor = 'bg-warning';
        }
        
        // 컨테이너에 고정 높이를 지정하여 항상 동일한 레이아웃을 유지
        return `
            <div style="text-align: center; min-width: 100px; min-height: 45px;">
                <div class="doc-progress">
                    <div class="doc-progress-bar ${bgColor}" style="width: ${percentage}%"></div>
                </div>
                <br>
                <div class="doc-progress-text">${formatNumber(completed)} / ${formatNumber(total)}</div>
            </div>
        `;
    }
    
    function getProgressBar(completed, total, isRequested) {
        if (isRequested) {
            return '<span class="badge badge-status badge-document-requested">요청</span>';
        }
        
        if (!total) return '-';
        
        const percentage = Math.round((completed / total) * 100);
        let bgColor = 'bg-danger';
        
        if (percentage >= 100) {
            bgColor = 'bg-success';
        } else if (percentage >= 60) {
            bgColor = 'bg-info';
        } else if (percentage >= 30) {
            bgColor = 'bg-warning';
        }
        
        // 컨테이너에 고정 높이를 지정하여 항상 동일한 레이아웃을 유지
        return `
            <div style="text-align: center; min-width: 100px; min-height: 45px;">
                <div class="doc-progress">
                    <div class="doc-progress-bar ${bgColor}" style="width: ${percentage}%"></div>
                </div>
                <br>
                <div class="doc-progress-text">${completed}/${total}</div>
            </div>
        `;
    }
    
    function getPaymentStatusButton(detail) {
        let statusClass, statusText;
        
        if (detail.state == '1' || detail.state == 1) {
            statusClass = 'badge-completed';
            statusText = '완납';
        } else if (detail.scheduled_date && new Date(detail.scheduled_date) < new Date()) {
            statusClass = 'badge-overdue';
            statusText = '연체';
        } else {
            statusClass = 'badge-pending';
            statusText = '미납';
        }
        
        return `<span class="badge badge-status ${statusClass}">${statusText}</span>`;
    }
}); 