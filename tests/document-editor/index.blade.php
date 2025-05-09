@extends('layouts.app')

@section('content')
<div class="container-fluid h-100">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">보정권고 요약</h4>
                <div class="d-flex">

                </div>
            </div>
        </div>
    </div>

    <div id="main-container" class="row" style="height: calc(100vh - 180px); min-height: 500px;">
        <!-- 파일 리스트 영역 (좌측) -->
        <div id="file-list-container" class="col-2 pe-0" style="min-width: 200px;">
            <div class="card h-100">
                <div class="card-header bg-light p-2">
                    <div class="input-group input-group-sm mb-1">
                        <select id="handler-filter" class="form-select form-select-sm" style="flex: 0 0 auto; width: 110px;">
                            <option value="">담당자 전체</option>
                        </select>
                        <input type="text" id="search-input" class="form-control form-control-sm" placeholder="파일명 검색">
                        <button class="btn btn-sm btn-primary" id="search-btn">검색</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="file-list" class="file-list">
                        <!-- 파일 목록이 여기에 동적으로 로드됩니다 -->
                        <div class="text-center p-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">로딩 중...</span>
                            </div>
                            <p class="mb-0 mt-2 small">파일 목록 로딩 중...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 문서 편집 영역 (중앙) -->
        <div id="editor-container" class="col-7 px-1">
            <div class="card h-100">
                <div class="card-header p-2">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-primary active" id="pdf-tab" data-tab="pdf-content" style="color: white; font-weight: bold;">PDF 뷰어</button>
                        <button type="button" class="btn btn-outline-primary" id="text-tab" data-tab="text-content" style="color: #0d6efd; font-weight: bold;">보정서 항목정리</button>
                        <button type="button" class="btn btn-outline-primary" id="summary-tab" data-tab="summary-content" style="color: #0d6efd; font-weight: bold;">보정서 요약표</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content h-100">
                        <!-- PDF 뷰어 탭 -->
                        <div class="tab-pane fade show active h-100" id="pdf-content" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom sticky-top">
                                <div class="d-flex align-items-center">
                                    <span id="current-file-name" class="me-2">PDF 문서</span>
                                    <span id="current-file-info" class="badge bg-secondary small"></span>
                                </div>
                                <div>
                                    <button id="prev-page" class="btn btn-sm btn-outline-secondary me-1">이전</button>
                                    <span id="page-info" class="mx-1">0 / 0</span>
                                    <button id="next-page" class="btn btn-sm btn-outline-secondary me-1">다음</button>
                                    <button id="zoom-in" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-zoom-in"></i>
                                    </button>
                                    <button id="zoom-out" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-zoom-out"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="pdf-container" style="height: calc(100% - 42px); overflow: auto;">
                                <div id="pdf-viewer">
                                    <div id="pdf-loading" class="d-none text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">PDF 로딩 중...</span>
                                        </div>
                                        <p class="mt-3">PDF 문서를 로딩 중입니다...</p>
                                    </div>
                                    <div id="pdf-placeholder" class="text-center py-5">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-secondary"></i>
                                        <p class="mt-3">좌측에서 문서를 선택하세요.</p>
                                    </div>
                                    <canvas id="pdf-canvas"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 보정서 항목정리리 탭 -->
                        <div class="tab-pane fade h-100" id="text-content" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom sticky-top">
                                <span>보정서 항목정리</span>
                                <div>
                                    
                                    <button id="process-correction" class="btn btn-sm btn-primary me-1">
                                        <i class="bi bi-list-check"></i> 보정서 항목정리
                                    </button>
                                    <button id="copy-text" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-clipboard"></i> 복사
                                    </button>
                                    <button id="clear-text" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle"></i> 지우기
                                    </button>
                                </div>
                            </div>
                            <div class="text-container">
                                <textarea id="text-editor" class="form-control h-100 border-0"></textarea>
                            </div>
                        </div>
                        
                        <!-- 보정서 요약표 탭 -->
                        <div class="tab-pane fade h-100" id="summary-content" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom sticky-top">
                                <span>보정서 요약표</span>
                                <div>
                                    <button id="generate-summary" class="btn btn-sm btn-primary me-1">
                                        <i class="bi bi-table"></i> 요약표 생성
                                    </button>
                                    <button id="copy-summary" class="btn btn-sm btn-outline-secondary me-1">
                                        <i class="bi bi-clipboard"></i> 복사
                                    </button>
                                    <button id="clear-summary" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-x-circle"></i> 지우기
                                    </button>
                                </div>
                            </div>
                            <div class="text-container">
                                <div id="summary-table-container" class="p-4 w-100 overflow-auto">
                                    <div class="alert alert-info text-center">
                                        <i class="bi bi-info-circle me-2"></i>
                                        요약표 생성 버튼을 클릭하여 보정서 요약표를 생성하세요.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 대화창 영역 (우측) -->
        <div id="chat-container" class="col-3 ps-0" style="min-width: 200px;">
            <div class="card h-100">
                <div class="card-header bg-light p-2 d-flex justify-content-between align-items-center">
                    <span>AI 법률 어시스턴트</span>
                    <div class="dropdown">
                        <button class="btn btn-sm dropdown-toggle" type="button" id="modelDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                                style="font-size: 0.75rem; padding: 2px 8px; background-color: #f7f9fc; color: #6c757d; border: 1px solid #dee2e6;">
                            GPT-4.1
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="modelDropdown" style="font-size: 0.75rem;">
                            <li><a class="dropdown-item model-item" href="#" data-model="claude-3-5-sonnet">Claude 3.5 Sonnet</a></li>
                            <li><a class="dropdown-item model-item" href="#" data-model="gpt-4o">GPT-4o</a></li>
                            <li><a class="dropdown-item model-item" href="#" data-model="gpt-4.1">GPT-4.1</a></li>
                            <li><a class="dropdown-item model-item" href="#" data-model="gpt-4.1-mini">GPT-4.1 Mini</a></li>
                            <li><a class="dropdown-item model-item" href="#" data-model="gpt-4o-mini">GPT-4o Mini</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <div id="chat-messages" class="chat-messages overflow-auto flex-grow-1 p-3">
                        <div class="text-center my-5">
                            <div class="pastel-circle-container d-flex justify-content-center align-items-center" style="height: 200px;">
                                <div class="pastel-circle" style="width: 120px; height: 120px;"></div>
                            </div>
                            <p class="text-muted mt-3">AI 법률 어시스턴트가 문서 작성을 도와드립니다.</p>
                        </div>
                    </div>
                    <div class="chat-input-container p-2 border-top">
                        <div class="chat-input-wrapper d-flex align-items-flex-end bg-light rounded p-2 position-relative">
                            <textarea id="chat-input" class="form-control border-0 bg-transparent" 
                                      rows="2" placeholder="메시지를 입력하세요..."
                                      style="resize: none; max-height: 100px;"></textarea>
                            <button id="send-button" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center ms-2"
                                    style="width: 32px; height: 32px; flex-shrink: 0;">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 로딩 모달 -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">처리 중...</span>
                </div>
                <p class="mb-0" id="loading-message">PDF를 텍스트로 변환 중입니다...</p>
            </div>
        </div>
    </div>
</div>

<!-- 상태 표시 모달 -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">처리 중...</span>
                </div>
                <p class="mb-0" id="status-message">처리 중...</p>
            </div>
        </div>
    </div>
</div>

<!-- 요약표 생성 확인 모달 -->
<div class="modal fade" id="summaryConfirmModal" tabindex="-1" aria-labelledby="summaryConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="summaryConfirmModalLabel">요약표 생성 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>보정서 요약표를 생성하시겠습니까?</p>
                <p class="small text-muted">텍스트 편집 탭의 내용을 분석하여 보정권고 항목을 표로 정리합니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirm-generate-summary">생성하기</button>
            </div>
        </div>
    </div>
</div>

<!-- 도움말 모달 -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">법률문서 편집기 도움말</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>주요 기능</h6>
                <ul>
                    <li>좌측에서 PDF 문서를 선택하여 내용을 확인할 수 있습니다.</li>
                    <li>중앙 영역의 탭을 이용해 PDF 뷰어, 텍스트 편집, 보정서 요약표 영역을 전환할 수 있습니다.</li>
                    <li>"보정서 항목정리" 버튼을 클릭하면 PDF를 텍스트로 변환하고, 자동으로 AI를 통해 항목별로 정리합니다.</li>
                    <li>"요약표 생성" 버튼을 클릭하면 텍스트 편집 탭의 내용을 분석하여 보정서 요약표를 생성합니다.</li>
                    <li>처리 과정은 다음과 같습니다:
                        <ol>
                            <li>PDF 파일을 텍스트로 변환</li> 
                            <li>텍스트 생성 완료 후 보정 항목 정리 시작</li>
                            <li>처리 결과를 텍스트 편집기에 표시</li>
                            <li>텍스트 편집 탭의 내용을 분석하여 보정서 요약표 생성</li>
                        </ol>
                    </li>
                    <li>처리 시간이 길어지는 경우 자동으로 대화목록에서 마지막 응답을 가져와 표시합니다.</li>
                    <li>우측의 AI 법률 어시스턴트를 통해 문서 작성에 도움을 받을 수 있습니다.</li>
                </ul>
                
                <h6>단축키</h6>
                <ul>
                    <li><kbd>Ctrl</kbd> + <kbd>S</kbd> : 텍스트 저장</li>
                    <li><kbd>Ctrl</kbd> + <kbd>Enter</kbd> : 채팅 메시지 전송</li>
                </ul>
                
                <h6>텍스트 변환 규칙</h6>
                <ul>
                    <li>'아래' 이후부터 '등본입니다' 이전까지의 내용만 추출됩니다.</li>
                    <li>머릿말(개인정보유출주의 등)과 표는 제외됩니다.</li>
                    <li>삭제선이 그어진 텍스트는 제외됩니다.</li>
                    <li>항목별로 행이 분리됩니다.</li>
                </ul>
                
                <h6>보정서 요약표 기능</h6>
                <ul>
                    <li>텍스트 편집 탭의 내용을 AI가 분석하여 보정권고 항목을 표 형식으로 정리합니다.</li>
                    <li>각 보정 항목의 보정 여부(O/X), 추가보정 예정일자, 미보정 사유 등을 자동으로 분류합니다.</li>
                    <li>요약표는 텍스트 형식으로 제공되며, 복사하여 다른 문서에 붙여넣기 할 수 있습니다.</li>
                    <li>보정서의 응답 현황을 한눈에 파악하는데 도움이 됩니다.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* 전체 레이아웃 */
    #main-container {
        height: calc(100vh - 180px);
        min-height: 500px;
        position: relative;
        width: 100%;
        margin: 0;
    }
    
    /* 파일 리스트 영역 */
    #file-list-container {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    #file-list-container .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    #file-list-container .card-body {
        flex: 1;
        padding: 0 !important;
        overflow: hidden; /* 중요: 여기서는 스크롤 없음 */
    }
    
    .file-list {
        height: 100%;
        overflow-y: scroll !important; /* 항상 스크롤 표시 */
        overflow-x: hidden;
        font-size: 8pt;
        scrollbar-width: thin; /* Firefox */
    }
    
    /* 스크롤바 스타일 (웹킷 브라우저) */
    .file-list::-webkit-scrollbar {
        width: 5px;
    }
    
    .file-list::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .file-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    
    .file-list::-webkit-scrollbar-thumb:hover {
        background: #999;
    }
    
    /* 파일 항목 스타일 */
    .file-item {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        line-height: 1.4;
    }
    
    .file-item .file-item-title {
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .file-item .smaller {
        font-size: 0.9em;
    }
    
    .file-item:hover {
        background-color: #f5f5f5;
    }
    
    .file-item.active {
        background-color: #e7f5ff;
        border-left: 2px solid #4361ee;
    }
    
    /* Card 컨테이너가 부모 높이를 채우도록 설정 */
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .card-body {
        flex: 1;
        overflow: hidden;
    }
    
    #editor-container .card-body {
        padding: 0 !important;
    }
    
    /* 탭 컨텐츠 스타일 */
    .tab-content {
        height: 100%;
        overflow: hidden;
    }
    
    .tab-pane {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    /* PDF 뷰어 영역 */
    #pdf-canvas {
        display: block;
        margin: 0 auto;
    }
    
    /* 텍스트 편집기 영역 */
    #text-editor {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px; /* 13px에서 14px로 1pt 증가 */
        line-height: 1.8; /* 1.5에서 1.8로 행간 증가 */
        resize: none;
        white-space: pre-wrap;
        height: 100%;
        padding: 20px 40px; /* 좌우 패딩 증가로 텍스트 너비 줄이기 */
        overflow-y: auto;
        max-width: 75%; /* 컨테이너의 3/4 크기로 제한 */
        margin: 0 auto; /* 중앙 정렬 */
    }
    
    /* 요약표 편집기 영역 */
    #summary-editor {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.4;
        resize: none;
        white-space: pre;
        height: 100%;
        padding: 20px 40px;
        overflow-y: auto;
        overflow-x: auto;
        max-width: 95%;
        margin: 0 auto;
    }
    
    /* 텍스트 컨테이너 스타일 */
    .text-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        height: calc(100% - 42px); /* 탭 헤더 높이 제외 */
        padding: 0;
        overflow: hidden;
    }
    
    /* PDF 뷰어와 텍스트 영역의 툴바 고정 */
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background: white;
    }
    
    /* PDF 뷰어와 텍스트 에디터 컨테이너 */
    #pdf-viewer {
        height: 100%;
        overflow-y: auto;
        padding-bottom: 20px;
    }
    
    /* 채팅 영역 */
    .chat-messages {
        height: 100%;
    }
    
    .message {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        max-width: 85%;
    }
    
    .message.ai {
        max-width: 90%;
    }
    
    .message.user {
        align-self: flex-end;
        flex-direction: row-reverse;
        margin-left: auto;
        max-width: 75%;
    }
    
    .message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .ai-message-avatar {
        background-color: #4361ee;
        color: white;
    }
    
    .user-message-avatar {
        background-color: #e9ecef;
        color: #333;
    }
    
    .message-content {
        background-color: #f7f9fc;
        padding: 10px 15px;
        border-radius: 18px;
        position: relative;
        font-size: 0.85rem;
    }
    
    .message.user .message-content {
        background-color: #4361ee;
        color: white;
        border-top-right-radius: 2px;
    }
    
    .message.ai .message-content {
        background-color: #f7f9fc;
        border-top-left-radius: 2px;
    }
    
    .message-time {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: 5px;
        text-align: right;
    }
    
    .message.user .message-time {
        text-align: left;
    }
    
    /* 탭 네비게이션 스타일 */
    .card-header {
        background-color: #ffffff;
        border-bottom: 1px solid #dee2e6;
    }
    
    .nav-tabs {
        border-bottom: none;
    }
    
    .nav-tabs .nav-link {
        padding: 0.5rem 1.2rem;
        font-size: 1rem;
        color: #495057;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-bottom: none;
        margin-right: 0.25rem;
        border-radius: 0.25rem 0.25rem 0 0;
    }
    
    .nav-tabs .nav-link:hover {
        color: #000;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
    
    .nav-tabs .nav-link.active {
        font-weight: 600;
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6;
        border-bottom: 2px solid #fff;
        margin-bottom: -1px;
    }
    
    /* 파스텔 색상 원형 애니메이션 */
    .pastel-circle {
        border-radius: 50%;
        background: linear-gradient(130deg, 
            rgba(255, 175, 189, 0.7), 
            rgba(255, 195, 160, 0.7), 
            rgba(194, 233, 251, 0.7), 
            rgba(161, 196, 253, 0.7), 
            rgba(212, 252, 121, 0.7),
            rgba(203, 186, 250, 0.7),
            rgba(178, 224, 212, 0.7),
            rgba(254, 219, 183, 0.7)
        );
        background-size: 800% 800%;
        animation: gradientAnimation 25s ease-in-out infinite alternate;
        position: relative;
    }
    
    .pastel-circle::after {
        content: '';
        position: absolute;
        top: -10px;
        left: -10px;
        right: -10px;
        bottom: -10px;
        background: inherit;
        border-radius: 50%;
        filter: blur(15px);
        opacity: 0.3;
        z-index: -1;
        animation: pulseAnimation 8s ease-in-out infinite alternate;
    }
    
    @keyframes gradientAnimation {
        0% {
            background-position: 0% 50%;
            opacity: 0.5;
        }
        25% {
            background-position: 50% 100%;
            opacity: 0.7;
        }
        50% {
            background-position: 100% 50%;
            opacity: 0.8;
        }
        75% {
            background-position: 50% 0%;
            opacity: 0.7;
        }
        100% {
            background-position: 0% 50%;
            opacity: 0.5;
        }
    }
    
    @keyframes pulseAnimation {
        0% {
            transform: scale(0.95);
            opacity: 0.3;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.5;
        }
        100% {
            transform: scale(0.95);
            opacity: 0.3;
        }
    }
    
    /* 마크다운 스타일 */
    .markdown-content h1, 
    .markdown-content h2, 
    .markdown-content h3, 
    .markdown-content h4 {
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 600;
        line-height: 1.25;
    }
    
    .markdown-content h1 {
        font-size: 1.5rem;
    }
    
    .markdown-content h2 {
        font-size: 1.3rem;
    }
    
    .markdown-content h3 {
        font-size: 1.1rem;
    }
    
    .markdown-content h4 {
        font-size: 1rem;
    }
    
    .markdown-content p {
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    
    .markdown-content ul, 
    .markdown-content ol {
        margin-bottom: 1rem;
        padding-left: 2rem;
    }
    
    .markdown-content li {
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<!-- PDF.js 라이브러리 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<!-- Marked.js 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // PDF.js 워커 설정
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        console.log("Document editor initialized");
        
        // 변수 초기화
        let currentPDFDoc = null;
        let currentPageNum = 1;
        let totalPageCount = 0;
        let currentZoomLevel = 1.0;
        let currentFilePath = null;
        let currentConversationId = null;
        let isWaitingForResponse = false;
        let typingInterval = null;
        let currentModel = 'gpt-4.1';
        let userHasScrolled = false;
        let currentFileListPage = 1; // 현재 파일 목록 페이지
        let isLoadingFileList = false; // 파일 목록 로딩 중 플래그
        let hasMoreFiles = true; // 더 로드할 파일이 있는지 플래그
        
        // DOM 요소
        const fileList = document.getElementById('file-list');
        const fileListContainer = document.getElementById('file-list'); // 스크롤 이벤트를 직접 file-list에 연결
        const pdfCanvas = document.getElementById('pdf-canvas');
        const pdfLoading = document.getElementById('pdf-loading');
        const pdfPlaceholder = document.getElementById('pdf-placeholder');
        const prevPageBtn = document.getElementById('prev-page');
        const nextPageBtn = document.getElementById('next-page');
        const pageInfo = document.getElementById('page-info');
        const zoomInBtn = document.getElementById('zoom-in');
        const zoomOutBtn = document.getElementById('zoom-out');
        const currentFileName = document.getElementById('current-file-name');
        const currentFileInfo = document.getElementById('current-file-info');
        const textEditor = document.getElementById('text-editor');
        const summaryTableContainer = document.getElementById('summary-table-container'); // 보정서 요약표 컨테이너

        const copyTextBtn = document.getElementById('copy-text');
        const clearTextBtn = document.getElementById('clear-text');
        const generateSummaryBtn = document.getElementById('generate-summary'); // 요약표 생성 버튼
        const copySummaryBtn = document.getElementById('copy-summary'); // 요약표 복사 버튼
        const clearSummaryBtn = document.getElementById('clear-summary'); // 요약표 지우기 버튼

        const handlerFilter = document.getElementById('handler-filter');
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');

        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const sendButton = document.getElementById('send-button');
        const modelDropdown = document.getElementById('modelDropdown');
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        const helpModal = new bootstrap.Modal(document.getElementById('helpModal'));
        const pdfTab = document.getElementById('pdf-tab');
        const textTab = document.getElementById('text-tab');
        const summaryTab = document.getElementById('summary-tab');
        
        // 탭 전환 함수
        function switchTab(tabId) {
            // 모든 탭 콘텐츠를 숨김
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // 모든 탭 버튼에서 active 클래스 제거
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.classList.contains('btn-primary')) {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                    btn.style.color = '#0d6efd';
                }
            });
            
            // 선택된 탭 콘텐츠 표시
            const selectedPane = document.getElementById(tabId);
            if (selectedPane) {
                selectedPane.classList.add('show', 'active');
            }
            
            // 선택된 탭 버튼 활성화
            const selectedBtn = document.querySelector(`[data-tab="${tabId}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
                selectedBtn.classList.remove('btn-outline-primary');
                selectedBtn.classList.add('btn-primary');
                selectedBtn.style.color = 'white';
            }
        }
        
        // 탭 버튼 클릭 이벤트 리스너
        pdfTab.addEventListener('click', function() {
            switchTab('pdf-content');
        });
        
        textTab.addEventListener('click', function() {
            switchTab('text-content');
        });
        
        summaryTab.addEventListener('click', function() {
            switchTab('summary-content');
        });
        
        // 모델 선택 이벤트 리스너
        document.querySelectorAll('.model-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                currentModel = this.getAttribute('data-model');
                modelDropdown.textContent = this.textContent;
            });
        });
        

        
        // 요약표 생성 버튼 이벤트 핸들러
        generateSummaryBtn.addEventListener('click', function() {
            // 확인 모달 표시
            const summaryConfirmModal = new bootstrap.Modal(document.getElementById('summaryConfirmModal'));
            summaryConfirmModal.show();
        });
        
        // 요약표 복사 버튼 이벤트 핸들러
        copySummaryBtn.addEventListener('click', function() {
            // 테이블이 있는지 확인
            if (summaryTableContainer.querySelector('table')) {
                // 선택 객체 생성
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(summaryTableContainer);
                selection.removeAllRanges();
                selection.addRange(range);
                
                // 복사 명령 실행
                document.execCommand('copy');
                
                // 선택 해제
                selection.removeAllRanges();
                
                alert('클립보드에 복사되었습니다.');
            } else {
                alert('복사할 요약표가 없습니다.');
            }
        });
        
        // 요약표 지우기 버튼 이벤트 핸들러
        clearSummaryBtn.addEventListener('click', function() {
            if (confirm('요약표를 모두 지우시겠습니까?')) {
                summaryTableContainer.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>
                        요약표 생성 버튼을 클릭하여 보정서 요약표를 생성하세요.
                    </div>
                `;
            }
        });

        // 요약표 생성 확인 버튼 이벤트 핸들러
        document.getElementById('confirm-generate-summary').addEventListener('click', function() {
            // 모달 닫기
            const summaryConfirmModal = bootstrap.Modal.getInstance(document.getElementById('summaryConfirmModal'));
            summaryConfirmModal.hide();
            
            // 요약표 생성 실행
            generateCorrectionSummary();
        });

        // 보정서 요약표 생성 함수
        function generateCorrectionSummary() {
            const correctionText = textEditor.value.trim();
            
            if (!correctionText) {
                alert('보정서 항목정리 탭에 보정서 내용이 없습니다. 텍스트를 먼저 입력해주세요.');
                return;
            }
            
            // 로딩 모달 표시
            document.getElementById('loading-message').textContent = '보정서 요약표를 생성하는 중입니다...';
            loadingModal.show();
            
            // LLM 프롬프트 설정
            const prompt = `다음은 법원의 보정권고 또는 보정명령과 이에 대한 답변이 포함된 텍스트입니다. 
이 내용을 분석하여 두 개의 HTML 표 형식으로 요약해주세요.

첫 번째 표: 보정권고 요약표
1. 보정권고/명령의 각 항목을 식별하고 그 내용을 간략히 요약합니다.
2. 각 보정 항목에 대해 보정 여부를 판단합니다 (완료됐으면 O, 아직 안됐으면 X).
3. 추가보정 예정일자가 언급된 경우 이를 표시합니다.
4. 미보정 사유가 있다면 이를 비고란에 표시합니다.

두 번째 표: 필요 서류 목록
1. 텍스트에서 제출을 요구하는 모든 서류 목록을 추출합니다.
2. 각 서류별로 제출 여부(제출됐으면 O, 아직 안됐으면 X)를 표시합니다.
3. 제출 예정 일자가 언급된 경우 이를 표시합니다.

출력 형식:
다음과 같은 HTML 표 형식으로 출력해주세요:

\`\`\`html
<!-- 첫 번째 표: 보정권고 요약표 -->
<table class="table table-bordered table-striped">
  <thead class="table-primary">
    <tr>
      <th style="width: 5%; text-align: center; vertical-align: middle;">번호</th>
      <th style="width: 50%; text-align: center; vertical-align: middle;">보정권고 내용</th>
      <th style="width: 10%; text-align: center; vertical-align: middle;">보정 여부</th>
      <th style="width: 15%; text-align: center; vertical-align: middle;">추가보정(예정일자)</th>
      <th style="width: 20%; text-align: center; vertical-align: middle;">비고(미보정사유)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>1</td>
      <td>[보정권고 항목 1 요약]</td>
      <td class="text-center">[O 또는 X]</td>
      <td class="text-center">[날짜]</td>
      <td>[미보정 사유]</td>
    </tr>
    <!-- 추가 행 -->
  </tbody>
</table>

<!-- 두 번째 표: 필요 서류 목록 -->
<div class="mt-4">
  <h5 class="mb-3">보정권고에 요청된 서류 목록</h5>
  <table class="table table-bordered table-striped">
    <thead class="table-secondary">
      <tr>
        <th style="width: 5%; text-align: center; vertical-align: middle;">번호</th>
        <th style="width: 60%; text-align: center; vertical-align: middle;">필요 서류</th>
        <th style="width: 15%; text-align: center; vertical-align: middle;">제출 여부</th>
        <th style="width: 20%; text-align: center; vertical-align: middle;">제출 예정일</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="text-center">1</td>
        <td>[서류명]</td>
        <td class="text-center">[O 또는 X]</td>
        <td class="text-center">[날짜]</td>
      </tr>
      <!-- 추가 행 -->
    </tbody>
  </table>
</div>
\`\`\`

중요 지침:
1. 반드시 위에 제공한 HTML 테이블 형식으로 출력해주세요.
2. HTML 태그만 출력하고 마크다운이나 \`\`\`html 같은 코드 블록 표시는 포함하지 마세요.
3. 보정 여부가 'O'인 경우 text-success 클래스를, 'X'인 경우 text-danger 클래스를 td에 추가해주세요.
4. 모든 테이블 헤더는 반드시 가운데 정렬(text-align: center)과 수직 가운데 정렬(vertical-align: middle)로 설정해주세요.
5. 보정권고 내용은 간결하게 요약하되, 핵심 내용이 포함되도록 합니다.
6. 보정 여부는 텍스트에서 '제출합니다', '반영하였습니다', '수정하였습니다' 등의 표현을 통해 판단합니다.
7. 예정일자가 언급된 경우 그대로 표기합니다. (예: "2025-05-30")
8. 미보정 사유는 텍스트에서 미보정 이유가 설명된 경우에만 표기합니다.
9. 테이블 아래에 참고사항이나 추가 메모가 필요하면 부트스트랩 alert-info 클래스로 작성해주세요.
10. HTML 표만 반환하고 다른 부가적인 설명이나 텍스트는 추가하지 마세요.
11. 필요 서류가 없는 경우 "보정권고에 요청된 서류가 없습니다"라는 메시지를 표시한 두 번째 표를 생성해주세요.
12. 필요서류의 경우 서류가 중복되지 않도록 중복 제거 후 표시해 주세요.
13. "관련 소명자료(소득금액증명, 급여명세서, 급여통장내역, 근로소득원천징수영수증, 사업자소득원천징수영수증 등)를 함께 제출한다."와 같이 보정권고에 나와있는 경우 이를 하나의 그룹으로 묶을 수 있다면 이는 '소득관련 소명자료' 라는 번호에에 가지번호를 붙여 4-1. 소득금액증명, 4-2. 급여명세서... 이런 식으로 표시한다. 

분석할 텍스트:
\`\`\`
${correctionText}
\`\`\``;
            
            // API 호출
            fetch('{{ route("legal-chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    question: prompt,
                    model: currentModel
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP 오류: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                loadingModal.hide();
                
                if (data.answer) {
                    // HTML 태그가 이스케이프되어 있을 수 있으므로 텍스트를 정리
                    const cleanedAnswer = data.answer
                        .replace(/```html/g, '')
                        .replace(/```/g, '')
                        .trim();
                    
                    summaryTableContainer.innerHTML = cleanedAnswer;
                    switchTab('summary-content'); // 요약표 탭으로 자동 전환
                } else if (data.conversation_id) {
                    // 응답이 바로 오지 않은 경우 폴링 시작
                    document.getElementById('loading-message').textContent = '응답을 기다리는 중입니다...';
                    pollSummaryGeneration(data.conversation_id);
                } else {
                    throw new Error('요약표 생성 실패: 응답에 내용이 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error generating summary:', error);
                
                // 에러가 발생하면 가져오기 시도
                try {
                    document.getElementById('loading-message').textContent = '타임아웃 에러로 인해 대화목록에서 요약표를 가져오는 중...';
                    
                    // 10초 기다린 후 마지막 응답 가져오기 (LLM이 응답할 시간을 줌)
                    setTimeout(async () => {
                        try {
                            // fetchLastResponsePromise 함수 호출
                            const lastResponse = await fetchLastResponsePromise();
                            
                            // HTML 태그가 이스케이프되어 있을 수 있으므로 텍스트를 정리
                            const cleanedResponse = lastResponse
                                .replace(/```html/g, '')
                                .replace(/```/g, '')
                                .trim();
                            
                            // 응답을 요약표 컨테이너에 설정
                            summaryTableContainer.innerHTML = cleanedResponse;
                            
                            // 모달 닫기
                            closeLoadingModalCompletely();
                            
                            // 요약표 탭으로 전환
                            switchTab('summary-content');
                            
                            console.log('요약표를 대화목록에서 성공적으로 가져왔습니다.');
                        } catch (fetchError) {
                            console.error('응답 가져오기 오류:', fetchError);
                            
                            // 모달 닫기
                            closeLoadingModalCompletely();
                            
                            alert('요약표를 가져오는 중 오류가 발생했습니다. 나중에 다시 시도해주세요.');
                        }
                    }, 10000);
                } catch (e) {
                    loadingModal.hide();
                    alert('요약표 생성 중 오류가 발생했습니다: ' + error.message);
                }
            });
        }
        
        // 요약표 생성 폴링 함수
        function pollSummaryGeneration(conversationId, attempts = 0) {
            // 최대 시도 횟수 (2분 동안 10초마다 = 약 12회)
            const maxAttempts = 12;
            
            if (attempts >= maxAttempts) {
                closeLoadingModalCompletely();
                alert('응답 시간이 너무 오래 걸립니다. 나중에 다시 시도해주세요.');
                return;
            }
            
            // 폴링 진행 메시지 업데이트
            document.getElementById('loading-message').textContent = 
                `요약표 생성 중입니다... (${attempts + 1}/${maxAttempts})`;
            
            // 대화 내용 가져오기
            fetch(`{{ url('legal-chat/conversation') }}/${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length >= 2) {
                        // 마지막 메시지(AI 응답) 가져오기
                        const lastMessage = data.messages[data.messages.length - 1];
                        
                        if (lastMessage.role === 'assistant' && lastMessage.content) {
                            // HTML 태그가 이스케이프되어 있을 수 있으므로 텍스트를 정리
                            const cleanedContent = lastMessage.content
                                .replace(/```html/g, '')
                                .replace(/```/g, '')
                                .trim();
                            
                            // 응답을 요약표 컨테이너에 설정
                            summaryTableContainer.innerHTML = cleanedContent;
                            
                            // 모달 닫기
                            closeLoadingModalCompletely();
                            
                            // 요약표 탭으로 전환
                            switchTab('summary-content');
                            
                            console.log('요약표 생성 완료');
                            return;
                        }
                    }
                    
                    // 응답이 아직 완료되지 않은 경우 10초 후에 다시 폴링
                    setTimeout(() => {
                        pollSummaryGeneration(conversationId, attempts + 1);
                    }, 10000);
                })
                .catch(error => {
                    console.error('Error polling summary generation:', error);
                    closeLoadingModalCompletely();
                    alert('요약표 생성 결과를 가져오는 중 오류가 발생했습니다. 나중에 다시 시도하세요.');
                });
        }
        
        // 파일 목록 로드
        function loadFileList(page = 1, append = false) {
            if (isLoadingFileList) {
                console.log('이미 로딩 중입니다. 요청 무시.');
                return; 
            }
            
            if (!hasMoreFiles && page > 1) {
                console.log('더 이상 로드할 파일이 없습니다.');
                return;
            }
            
            isLoadingFileList = true;
            currentFileListPage = page;
            
            console.log(`파일 목록 페이지 ${page} 로드 중... (추가모드: ${append ? '예' : '아니오'})`);
            
            // 로딩 표시 (append가 아닐 경우 기존 목록 지우고 로딩 표시)
            if (!append) {
                fileList.innerHTML = `
                    <div class="text-center p-3" id="file-list-loading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">로딩 중...</span>
                        </div>
                        <p class="mb-0 mt-2 small">파일 목록 로딩 중...</p>
                    </div>
                `;
            } else {
                // 추가 로딩 시 로딩 표시기 추가
                const loadingMoreEl = document.createElement('div');
                loadingMoreEl.id = 'file-list-loading-more';
                loadingMoreEl.className = 'text-center p-2';
                loadingMoreEl.innerHTML = `
                    <div class="spinner-border spinner-border-sm text-primary" role="status" style="width: 0.8rem; height: 0.8rem;">
                        <span class="visually-hidden">추가 로딩 중...</span>
                    </div>
                    <p class="mb-0 mt-1 small text-muted">추가 로딩 중...</p>
                `;
                fileList.appendChild(loadingMoreEl);
            }
            
            // API 호출
            fetch(`{{ route('document-editor.files') }}?handler=${handlerFilter.value}&search=${searchInput.value}&page=${page}&per_page=10000`)
                .then(response => response.json())
                .then(data => {
                    // 로딩 표시 제거
                    const loadingElement = document.getElementById('file-list-loading');
                    if (loadingElement && !append) loadingElement.remove();
                    const loadingMoreElement = document.getElementById('file-list-loading-more');
                    if (loadingMoreElement) loadingMoreElement.remove();
                    
                    if (data.success) {
                        renderFileList(data.files, append);
                        hasMoreFiles = data.meta.has_more; // 더 로드할 파일 있는지 업데이트
                        console.log(`페이지 ${page} 로드 완료. 더 있음: ${hasMoreFiles}`);
                    } else {
                        if (!append) {
                            fileList.innerHTML = `<div class="text-center p-3 text-danger">파일 목록을 불러오는 데 실패했습니다.</div>`;
                        }
                        hasMoreFiles = false; // 오류 시 더 이상 로드하지 않음
                    }
                })
                .catch(error => {
                    console.error('Error loading file list:', error);
                    // 로딩 표시 제거
                    const loadingElement = document.getElementById('file-list-loading');
                    if (loadingElement && !append) loadingElement.remove();
                    const loadingMoreElement = document.getElementById('file-list-loading-more');
                    if (loadingMoreElement) loadingMoreElement.remove();
                    
                    if (!append) {
                        fileList.innerHTML = `<div class="text-center p-3 text-danger">파일 목록을 불러오는 데 실패했습니다.</div>`;
                    }
                    hasMoreFiles = false; // 오류 시 더 이상 로드하지 않음
                })
                .finally(() => {
                    isLoadingFileList = false;
                });
        }
        
        // 담당자 목록 로드
        function loadHandlers() {
            fetch(`{{ route('document-editor.handlers') }}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 기존 옵션 제거 (첫 번째 옵션 '전체'만 유지)
                        while (handlerFilter.options.length > 0) {
                            handlerFilter.remove(0);
                        }
                        
                        // '전체' 옵션 추가
                        const allOption = new Option('담당자 전체', '');
                        handlerFilter.add(allOption);
                        
                        // 담당자 옵션 추가
                        data.handlers.forEach(handler => {
                            const option = new Option(handler, handler);
                            handlerFilter.add(option);
                        });
                        
                        // 기본값 설정
                        if (data.defaultHandler) {
                            handlerFilter.value = data.defaultHandler;
                        }
                        
                        // 파일 목록 로드 (페이지 1부터)
                        hasMoreFiles = true; // 필터 변경 시 초기화
                        loadFileList(1);
                    }
                })
                .catch(error => {
                    console.error('Error loading handlers:', error);
                });
        }
        
        // 파일 목록 렌더링
        function renderFileList(files, append = false) {
            if (!append && files.length === 0) {
                fileList.innerHTML = `<div class="text-center p-3 text-muted">파일이 없습니다.</div>`;
                return;
            }

            // 파일명 필터링 로직 추가
            const filteredFiles = files.filter(file => {
                const fileNameLower = file.filename.toLowerCase();
                const containsRequiredKeywords = fileNameLower.includes('보정권고') || fileNameLower.includes('보정명령등본');
                const containsExcludedKeyword = fileNameLower.includes('주소');
                return containsRequiredKeywords && !containsExcludedKeyword;
            });

            if (!append && filteredFiles.length === 0) {
                fileList.innerHTML = `<div class="text-center p-3 text-muted">조건에 맞는 파일이 없습니다.</div>`;
                return;
            }
            
            let html = '';
            filteredFiles.forEach(file => {
                html += `
                    <div class="file-item" data-path="${file.path}" data-filename="${file.filename}">
                        <div class="file-item-title text-truncate">[${file.receiptDate}] [${file.caseNumber}] ${file.documentName}</div>
                        <div class="text-muted smaller">${file.handler || '담당자 없음'}</div>
                    </div>
                `;
            });
            
            if (!append) {
                fileList.innerHTML = html;
            } else {
                fileList.insertAdjacentHTML('beforeend', html);
            }
            
            // 파일 클릭 이벤트 리스너 추가 (새로 추가된 요소에만)
            const newlyAddedItems = append ? fileList.querySelectorAll('.file-item:not([data-event-added])') : fileList.querySelectorAll('.file-item');
            
            newlyAddedItems.forEach(item => {
                item.addEventListener('click', function() {
                    // 선택된 아이템 스타일 적용
                    document.querySelectorAll('.file-item').forEach(el => el.classList.remove('active'));
                    this.classList.add('active');
                    
                    // PDF 로드
                    const filePath = this.dataset.path;
                    const fileName = this.dataset.filename;
                    loadPDF(filePath, fileName);
                    
                    // PDF 탭으로 전환
                    switchTab('pdf-content');
                });
                item.dataset.eventAdded = 'true'; // 이벤트 리스너 추가됨 표시
            });
        }
        
        // PDF 로드
        function loadPDF(filePath, fileName) {
            if (!filePath) return;
            
            // 현재 파일 경로 저장
            currentFilePath = filePath;
            
            // UI 업데이트
            currentFileName.textContent = fileName || filePath;
            pdfLoading.classList.remove('d-none');
            pdfPlaceholder.classList.add('d-none');
            
            // PDF.js로 파일 로드
            // 항상 API를 통해 파일을 로드 (프로덕션 및 로컬 환경 모두)
            const pdfUrl = `/document-editor/view-pdf?file_path=${encodeURIComponent(filePath)}`;
            
            // PDF 파일 로드
            const loadTask = pdfjsLib.getDocument({
                url: pdfUrl,
            });
            
            loadTask.promise.then(pdfDoc => {
                currentPDFDoc = pdfDoc;
                totalPageCount = pdfDoc.numPages;
                currentPageNum = 1;
                
                // 페이지 정보 업데이트
                updatePageInfo();
                
                // 첫 페이지 렌더링
                renderPage(currentPageNum);
                
                // 파일 정보 표시
                currentFileInfo.textContent = `${totalPageCount}페이지`;
                
                // 로딩 UI 숨기기
                pdfLoading.classList.add('d-none');
            }).catch(error => {
                console.error('Error loading PDF:', error);
                pdfLoading.classList.add('d-none');
                pdfPlaceholder.classList.remove('d-none');
                pdfPlaceholder.innerHTML = `
                    <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                    <p class="mt-3 text-danger">PDF를 로드하는 데 실패했습니다.</p>
                `;
            });
        }
        
        // PDF 페이지 렌더링
        function renderPage(pageNum) {
            if (!currentPDFDoc) return;
            
            currentPDFDoc.getPage(pageNum).then(page => {
                const viewport = page.getViewport({ scale: currentZoomLevel });
                
                pdfCanvas.height = viewport.height;
                pdfCanvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: pdfCanvas.getContext('2d'),
                    viewport: viewport
                };
                
                page.render(renderContext);
            });
        }
        
        // 페이지 정보 업데이트
        function updatePageInfo() {
            pageInfo.textContent = `${currentPageNum} / ${totalPageCount}`;
            
            // 페이지 이동 버튼 상태 업데이트
            prevPageBtn.disabled = currentPageNum <= 1;
            nextPageBtn.disabled = currentPageNum >= totalPageCount;
        }
        
        
        // 모달을 완전히 닫는 통합 함수
        function closeLoadingModalCompletely() {
            console.log('모달 닫기 시작: ' + new Date().toISOString());
            
            try {
                // bootstrap 모달 객체로 닫기
                if (typeof loadingModal === 'object' && loadingModal !== null) {
                    loadingModal.hide();
                    console.log('bootstrap 모달 객체로 닫기 완료');
                }
                
                // DOM에서도 모달 닫기 시도 (백업)
                const modalElement = document.getElementById('loadingModal');
                if (modalElement) {
                    // 모달 표시 여부 확인
                    if (modalElement.classList.contains('show')) {
                        console.log('모달이 여전히 표시 중, DOM으로 직접 닫기 시도');
                        modalElement.classList.remove('show');
                        modalElement.style.display = 'none';
                        modalElement.setAttribute('aria-hidden', 'true');
                    }
                    
                    const bsModal = bootstrap.Modal.getInstance(modalElement);
                    if (bsModal) {
                        bsModal.hide();
                        console.log('bootstrap.Modal.getInstance로 닫기 완료');
                    }
                }
                
                // 모달 배경 제거
                const modalBackdrops = document.querySelectorAll('.modal-backdrop');
                if (modalBackdrops.length > 0) {
                    console.log(`${modalBackdrops.length}개의 모달 배경 발견, 제거 중`);
                    modalBackdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                }
                
                // body의 modal 클래스 제거
                if (document.body.classList.contains('modal-open')) {
                    console.log('body에서 modal-open 클래스 제거');
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('padding-right');
                }
                
                // 로딩 메시지 초기화
                document.getElementById('loading-message').textContent = '처리 중...';
                
                console.log('모달 닫기 완료: ' + new Date().toISOString());
            } catch (err) {
                console.error('모달 닫기 중 오류 발생:', err);
            }
            
            // 100ms 후에 DOM 상태 확인 (안전 장치)
            setTimeout(() => {
                try {
                    // 모달 요소가 여전히 표시 중인지 확인
                    const modalElement = document.getElementById('loadingModal');
                    if (modalElement && modalElement.classList.contains('show')) {
                        console.log('모달이 여전히 표시 중, 강제로 제거');
                        modalElement.classList.remove('show');
                        modalElement.style.display = 'none';
                        modalElement.setAttribute('aria-hidden', 'true');
                    }
                    
                    // 모달 배경이 여전히 있는지 확인
                    const modalBackdrops = document.querySelectorAll('.modal-backdrop');
                    if (modalBackdrops.length > 0) {
                        console.log(`${modalBackdrops.length}개의 모달 배경이 여전히 존재, 강제 제거`);
                        modalBackdrops.forEach(backdrop => {
                            backdrop.remove();
                        });
                    }
                    
                    // body 클래스 확인
                    if (document.body.classList.contains('modal-open')) {
                        console.log('body에 여전히 modal-open 클래스가 있음, 강제 제거');
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                    }
                } catch (err) {
                    console.error('안전 장치 실행 중 오류 발생:', err);
                }
            }, 100);
        }
        
        
        
        // AI 챗봇 메시지 전송
        function sendChatMessage() {
            const question = chatInput.value.trim();
            if (question === '' || isWaitingForResponse) return;
            
            // 입력 필드 초기화
            chatInput.value = '';
            isWaitingForResponse = true;
            
            // 메시지 시간
            const messageTime = new Date();
            const timeString = messageTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // 초기 로고 제거
            if (chatMessages.querySelector('.text-center')) {
                chatMessages.innerHTML = '';
            }
            
            // 사용자 메시지 추가
            chatMessages.innerHTML += `
                <div class="message user">
                    <div class="message-avatar user-message-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <div class="message-content">${question}</div>
                        <div class="message-time">${timeString}</div>
                    </div>
                </div>
            `;
            
            // 로딩 표시
            const loaderId = `loader-${Date.now()}`;
            chatMessages.innerHTML += `
                <div class="message ai" id="${loaderId}">
                    <div class="message-avatar ai-message-avatar">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="message-content">
                            <span id="loading-text-${loaderId}">생성 중</span><span id="loading-dots-${loaderId}">...</span>
                        </div>
                    </div>
                </div>
            `;
            
            // 로딩 애니메이션
            let dotsCount = 3;
            const loadingDotsInterval = setInterval(() => {
                const loadingDots = document.getElementById(`loading-dots-${loaderId}`);
                if (loadingDots) {
                    dotsCount = (dotsCount % 3) + 1;
                    loadingDots.textContent = '.'.repeat(dotsCount);
                } else {
                    clearInterval(loadingDotsInterval);
                }
            }, 500);
            
            // 메시지 표시 후 스크롤
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // 현재 문서 컨텍스트 추가
            let context = '';
            if (summaryTableContainer.innerHTML.trim() !== '' && 
                !summaryTableContainer.innerHTML.includes('요약표 생성 버튼을 클릭하여 보정서 요약표를 생성하세요')) {
                context = `[현재 보정서 요약표]:
\`\`\`html
${summaryTableContainer.innerHTML.trim()}
\`\`\`

`;
            }
            
            // AI 응답 요청
            fetch('{{ route("legal-chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    question: context + question,
                    conversation_id: currentConversationId,
                    model: currentModel
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP 오류: ${response.status} - ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // 로딩 표시 제거
                const loader = document.getElementById(loaderId);
                if (loader) loader.remove();
                clearInterval(loadingDotsInterval);
                
                // 응답 시간
                const responseTime = new Date();
                const responseTimeString = responseTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                // 타이핑 효과를 위한 ID
                const typingId = `typing-${Date.now()}`;
                
                // AI 메시지 추가
                chatMessages.innerHTML += `
                    <div class="message ai">
                        <div class="message-avatar ai-message-avatar">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div style="width:100%">
                            <div class="message-content markdown-content" id="${typingId}"></div>
                            <div class="message-time">
                                ${responseTimeString}
                                <span class="model-badge" style="font-size: 0.65rem; opacity: 0.7;">
                                    ${data.model ? data.model.split('-')[0].toUpperCase() : 'AI'}
                                </span>
                                <button class="btn btn-sm text-primary border-0 insert-to-editor" 
                                        data-answer="${encodeURIComponent(data.answer || '')}" style="padding: 0 3px; font-size: 0.7rem;">
                                    <i class="bi bi-arrow-left-right"></i> 편집기에 삽입
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                // 마크다운 변환
                const formattedAnswer = marked.parse(data.answer || '응답을 받지 못했습니다.');
                
                // 타이핑 효과
                const typingTarget = document.getElementById(typingId);
                let index = 0;
                const rawText = data.answer || '응답 내용이 없습니다.';
                
                const typeText = () => {
                    if (index >= rawText.length) {
                        clearInterval(typingInterval);
                        typingTarget.innerHTML = formattedAnswer;
                        
                        // 응답 완료 후 스크롤
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                        return;
                    }
                    
                    index += 5; // 타이핑 속도 조절
                    typingTarget.innerHTML = marked.parse(rawText.substring(0, index));
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                };
                
                clearInterval(typingInterval);
                typingInterval = setInterval(typeText, 30);
                
                // 대화 ID 업데이트
                if (data.conversation_id) {
                    currentConversationId = data.conversation_id;
                    console.log('대화 ID 업데이트:', currentConversationId);
                }
                
                isWaitingForResponse = false;
                
                // 메시지 표시 후 스크롤
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // '편집기에 삽입' 버튼에 이벤트 리스너 추가
                document.querySelectorAll('.insert-to-editor').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const answer = decodeURIComponent(this.dataset.answer || '');
                        
                        // 현재 커서 위치에 텍스트 삽입
                        const cursorPos = textEditor.selectionStart;
                        const textBefore = textEditor.value.substring(0, cursorPos);
                        const textAfter = textEditor.value.substring(cursorPos);
                        
                        textEditor.value = textBefore + answer + textAfter;
                        
                        // 커서 위치 업데이트
                        const newCursorPos = cursorPos + answer.length;
                        textEditor.focus();
                        textEditor.setSelectionRange(newCursorPos, newCursorPos);
                    });
                });
            })
            .catch(error => {
                console.error('Error sending message:', error);
                
                // 로딩 표시 제거
                const loader = document.getElementById(loaderId);
                if (loader) loader.remove();
                clearInterval(loadingDotsInterval);
                
                // 에러 메시지 덤프 (디버깅용)
                console.log('에러 객체 덤프:', JSON.stringify({
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                }));
                
                // HTTP 오류 500 직접 확인 (문자열 포함 여부가 아닌 정확한 패턴 매칭)
                const isHttp500Error = error.message && error.message.match(/HTTP 오류: 500/);
                
                // 타임아웃 관련 에러인지 확인 (HTTP 500 추가)
                const isTimeoutError = 
                    isHttp500Error || 
                    (error.message && (
                        error.message.includes('timeout') || 
                        error.message.includes('Timeout') || 
                        error.message.includes('시간 초과') ||
                        error.message.includes('time') ||
                        error.message.includes('취소') ||
                        error.message.includes('cancel') ||
                        error.message.includes('Network') ||
                        error.message.includes('네트워크') ||
                        error.message.includes('기다리는') ||
                        error.message.includes('연결') ||
                        error.message.includes('Connection')
                    ));
                
                console.log('타임아웃 감지:', isTimeoutError, '에러 메시지:', error.message);
                console.log('HTTP 500 에러:', isHttp500Error);
                
                // 타임아웃 에러 처리
                if (isTimeoutError) {
                    // 타임아웃 알림 표시
                    chatMessages.innerHTML += `
                        <div class="message ai">
                            <div class="message-avatar ai-message-avatar">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <div class="message-content text-warning">
                                    <p>응답 생성 시간이 오래 걸리고 있습니다. 대화목록에서 응답을 가져오는 중...</p>
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">로딩 중...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // 메시지 표시 후 스크롤
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    
                    // 10초 후에 마지막 대화의 응답 가져오기 시도 (법률 문서가 길어서 처리 시간 증가)
                    setTimeout(() => {
                        // 마지막 응답 가져오기
                        fetchLastResponsePromise()
                            .then(lastResponse => {
                                // 응답 시간
                                const responseTime = new Date();
                                const responseTimeString = responseTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                
                                // 타임아웃 알림 메시지 제거 (마지막 AI 메시지)
                                const aiMessages = chatMessages.querySelectorAll('.message.ai');
                                if (aiMessages.length > 0) {
                                    aiMessages[aiMessages.length - 1].remove();
                                }
                                
                                // 타이핑 효과를 위한 ID
                                const typingId = `typing-${Date.now()}`;
                                
                                // AI 메시지 추가
                                chatMessages.innerHTML += `
                                    <div class="message ai">
                                        <div class="message-avatar ai-message-avatar">
                                            <i class="bi bi-robot"></i>
                                        </div>
                                        <div style="width:100%">
                                            <div class="message-content markdown-content" id="${typingId}"></div>
                                            <div class="message-time">
                                                ${responseTimeString}
                                                <span class="model-badge" style="font-size: 0.65rem; opacity: 0.7;">
                                                    ${currentModel.split('-')[0].toUpperCase()}
                                                </span>
                                                <button class="btn btn-sm text-primary border-0 insert-to-editor" 
                                                        data-answer="${encodeURIComponent(lastResponse || '')}" style="padding: 0 3px; font-size: 0.7rem;">
                                                    <i class="bi bi-arrow-left-right"></i> 편집기에 삽입
                                                </button>
                                                <small class="text-muted ms-1">(자동 가져오기)</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                // 마크다운 변환
                                const formattedAnswer = marked.parse(lastResponse || '응답을 받지 못했습니다.');
                                
                                // 타이핑 효과
                                const typingTarget = document.getElementById(typingId);
                                let index = 0;
                                const rawText = lastResponse || '응답 내용이 없습니다.';
                                
                                const typeText = () => {
                                    if (index >= rawText.length) {
                                        clearInterval(typingInterval);
                                        typingTarget.innerHTML = formattedAnswer;
                                        
                                        // 응답 완료 후 스크롤
                                        chatMessages.scrollTop = chatMessages.scrollHeight;
                                        return;
                                    }
                                    
                                    index += 5; // 타이핑 속도 조절
                                    typingTarget.innerHTML = marked.parse(rawText.substring(0, index));
                                    chatMessages.scrollTop = chatMessages.scrollHeight;
                                };
                                
                                clearInterval(typingInterval);
                                typingInterval = setInterval(typeText, 30);
                                
                                // '편집기에 삽입' 버튼에 이벤트 리스너 추가
                                document.querySelectorAll('.insert-to-editor').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        const answer = decodeURIComponent(this.dataset.answer || '');
                                        
                                        // 현재 커서 위치에 텍스트 삽입
                                        const cursorPos = textEditor.selectionStart;
                                        const textBefore = textEditor.value.substring(0, cursorPos);
                                        const textAfter = textEditor.value.substring(cursorPos);
                                        
                                        textEditor.value = textBefore + answer + textAfter;
                                        
                                        // 커서 위치 업데이트
                                        const newCursorPos = cursorPos + answer.length;
                                        textEditor.focus();
                                        textEditor.setSelectionRange(newCursorPos, newCursorPos);
                                    });
                                });
                                
                                isWaitingForResponse = false;
                            })
                            .catch(fetchError => {
                                console.error('Error fetching last response:', fetchError);
                                
                                // 타임아웃 알림 메시지 제거 (마지막 AI 메시지)
                                const aiMessages = chatMessages.querySelectorAll('.message.ai');
                                if (aiMessages.length > 0) {
                                    aiMessages[aiMessages.length - 1].remove();
                                }
                                
                                // 오류 메시지 추가
                                chatMessages.innerHTML += `
                                    <div class="message ai">
                                        <div class="message-avatar ai-message-avatar">
                                            <i class="bi bi-robot"></i>
                                        </div>
                                        <div>
                                            <div class="message-content text-danger">
                                                응답을 가져오는 중 오류가 발생했습니다: ${fetchError.message || '알 수 없는 오류'}
                                                <br><small>다시 시도하거나 새로고침 후 진행해 주세요.</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                isWaitingForResponse = false;
                                
                                // 메시지 표시 후 스크롤
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                            });
                    }, 10000); // 10초 후에 가져오기 시도(기존 7초에서 10초로 증가)
                } else {
                    // 일반 오류 메시지 추가
                    chatMessages.innerHTML += `
                        <div class="message ai">
                            <div class="message-avatar ai-message-avatar">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <div class="message-content text-danger">
                                    메시지 전송 중 오류가 발생했습니다: ${error.message || '알 수 없는 오류'}
                                    <br><small>다시 시도하거나 새로고침 후 진행해 주세요.</small>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    isWaitingForResponse = false;
                }
                
                // 메시지 표시 후 스크롤
                chatMessages.scrollTop = chatMessages.scrollHeight;
            });
        }
        
        // 이벤트 리스너
        
        // 페이지 이동 버튼
        prevPageBtn.addEventListener('click', () => {
            if (currentPageNum > 1) {
                currentPageNum--;
                renderPage(currentPageNum);
                updatePageInfo();
            }
        });
        
        nextPageBtn.addEventListener('click', () => {
            if (currentPageNum < totalPageCount) {
                currentPageNum++;
                renderPage(currentPageNum);
                updatePageInfo();
            }
        });
        
        // 줌 버튼
        zoomInBtn.addEventListener('click', () => {
            currentZoomLevel += 0.2;
            renderPage(currentPageNum);
        });
        
        zoomOutBtn.addEventListener('click', () => {
            if (currentZoomLevel > 0.4) {
                currentZoomLevel -= 0.2;
                renderPage(currentPageNum);
            }
        });
        

        
        // 복사 버튼
        copyTextBtn.addEventListener('click', () => {
            textEditor.select();
            document.execCommand('copy');
            alert('클립보드에 복사되었습니다.');
        });
        
        // 지우기 버튼
        clearTextBtn.addEventListener('click', () => {
            if (confirm('텍스트를 모두 지우시겠습니까?')) {
                textEditor.value = '';
            }
        });
        

        
        // 담당자 필터 변경
        handlerFilter.addEventListener('change', () => {
            hasMoreFiles = true; // 필터 변경 시 초기화
            loadFileList(1);
        });
        
        // 검색 버튼
        searchBtn.addEventListener('click', () => loadFileList(1));
        
        // 검색 입력 필드 엔터 키
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadFileList(1);
            }
        });
        

        
        // 채팅 입력 필드 엔터 키
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });
        
        // 채팅 전송 버튼
        sendButton.addEventListener('click', sendChatMessage);
        
        // 텍스트 영역 자동 높이 조절
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
        
        // 단축키
        document.addEventListener('keydown', function(e) {

            
            // Ctrl+Enter: 채팅 메시지 전송
            if (e.ctrlKey && e.key === 'Enter' && document.activeElement === chatInput) {
                e.preventDefault();
                sendChatMessage();
            }
        });
        
        // 파일 리스트 스크롤 이벤트 리스너 (무한 스크롤)
        let scrollDebounceTimer;
        
        fileList.addEventListener('scroll', function() {
            clearTimeout(scrollDebounceTimer);
            
            scrollDebounceTimer = setTimeout(() => {
                const scrollTop = this.scrollTop;
                const clientHeight = this.clientHeight;
                const scrollHeight = this.scrollHeight;
                const remainingScroll = scrollHeight - (scrollTop + clientHeight);
                
                // 디버깅 정보
                if (remainingScroll < 200) {
                    console.log(`스크롤 정보 - 위치: ${scrollTop}, 컨테이너높이: ${clientHeight}, 총높이: ${scrollHeight}, 남은높이: ${remainingScroll}`);
                }
                
                // 스크롤이 맨 아래 근처에 도달했고, 로딩 중이 아니며, 더 로드할 파일이 있을 때
                if (!isLoadingFileList && hasMoreFiles && remainingScroll < 100) {
                    console.log(`무한 스크롤 트리거! 페이지 ${currentFileListPage + 1} 로드 중...`);
                    loadFileList(currentFileListPage + 1, true); // 다음 페이지 로드 (append 모드)
                }
            }, 100); // 디바운스 100ms
        });
        
        // 초기화
        loadHandlers(); // 담당자 로드 후 첫 페이지 파일 목록 로드
        
        
        // 보정서 항목정리 버튼
        document.getElementById('process-correction').addEventListener('click', processDocumentCorrection);
        
        // Promise 기반 PDF 텍스트 변환 함수
        function convertPDFToTextPromise() {
            return new Promise((resolve, reject) => {
                if (!currentFilePath) {
                    reject(new Error('변환할 PDF 파일을 먼저 선택해주세요.'));
                    return;
                }
                
                // API 호출
                fetch('{{ route("document-editor.convert") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        file_path: currentFilePath
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resolve(data.text);
                    } else {
                        reject(new Error('PDF 변환 중 오류가 발생했습니다: ' + data.message));
                    }
                })
                .catch(error => {
                    console.error('Error converting PDF:', error);
                    reject(new Error('PDF 변환 중 오류가 발생했습니다.'));
                });
            });
        }
        
        // Promise 기반 텍스트 처리 함수
        function processTextPromise(text) {
            return new Promise((resolve, reject) => {
                if (!text || text.trim() === '') {
                    reject(new Error('처리할 텍스트가 없습니다.'));
                    return;
                }
                
                // LLM에 전송할 프롬프트
                const prompt = `다음 법원 보정권고/보정명령 텍스트를 항목별로 정리해주세요:
1. 원문의 정보는 모두 유지하고, 내용 누락 없이 그대로 사용해주세요.
2. 각 항목(숫자, 기호로 구분된)을 명확히 구분하고 적절한 행간을 두어 가독성을 높여주세요. 중첩된 목록(예: 가, 나, 다 또는 ①, ②, ③)도 모두 포함하고 형식을 유지해야 합니다.
3. 내용 자체는 절대 수정하지 말고, 원문 그대로 유지하면서 형식만 정리해주세요.
4. 항목 번호 체계를 유지하되, 각 항목 사이에는 줄바꿈을 추가해 구분해주세요.
5. 다음과 같은 형식적인 내용은 모두 제외해주세요:
   - 문서 상단/하단의 법원명, 사건번호, 신청인 정보, 날짜, 담당자 정보 등 헤더/푸터 정보
   - '기재례'와 같이 기재를 어떤 식으로 해야 하는지 안내하는 형식적인 내용
   - 참고사항, 주의사항에 해당하는 내용
   - 자료제출목록 등 체크리스트 양식 등
   - 형식적인 문서 구분선(예: "인천지방법원")
   - "채무자는 이 보정권고를 송달받은 날로부터 14일 이내에 아래사항을 보정하여 주시기 바랍니다" 같은 상투적 안내문구
   - "아래", "등본입니다" 같은 형식적 구분자
6. 오직 사용자가 실제로 해야 할 보정사항만 깔끔하게 정리해주세요.
7. 번호가 매겨진 항목 내용만 추출하여 정리해주세요.
8. 매우 중요: 긴 문서의 경우에도 모든 항목을 빠짐없이 포함해야 합니다. 특히 마지막 항목까지 반드시 모두 처리해주세요.
9. 마지막 항목이 끝까지 완성되지 않은 경우에도 현재 보이는 내용까지 모두 포함해야 합니다.
10. 응답 길이에 주의하세요. 모든 항목을 포함할 수 있도록 내용을 압축하지 말고 원본 그대로 유지하세요.
11. 절대로 항목 중간에서 응답을 멈추지 마세요. 각 항목의 내용은 반드시 끝까지 완성해야 합니다.

원문 텍스트:
\`\`\`
${text}
\`\`\``;
                
                // 모든 텍스트를 단일 요청으로 처리
                fetch('{{ route("legal-chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        question: prompt,
                        model: currentModel
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP 오류: ${response.status} - ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // 응답이 있으면 바로 리턴
                    if (data.answer) {
                        resolve(data.answer);
                        return;
                    }
                    
                    // 응답이 없거나 불완전하면 대화 ID로 폴링 시작
                    if (data.conversation_id) {
                        // 15초 동안만 폴링 시도 (그 이상은 타임아웃으로 간주)
                        pollConversationWithTimeout(data.conversation_id, 15000)
                            .then(result => resolve(result))
                            .catch(error => reject(error));
                    } else {
                        reject(new Error('대화 ID를 받지 못했습니다.'));
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    reject(error);
                });
            });
        }
        
        // 제한된 시간 동안만 폴링하는 함수
        function pollConversationWithTimeout(conversationId, timeoutMs) {
            return new Promise((resolve, reject) => {
                // 타임아웃 설정
                const timeoutId = setTimeout(() => {
                    reject(new Error('응답 대기 시간 초과'));
                }, timeoutMs);
                
                function poll(attempts = 0) {
                    // 최대 시도 횟수 (15초 동안 3초마다 = 약 5회)
                    const maxAttempts = Math.ceil(timeoutMs / 3000);
                    
                    if (attempts >= maxAttempts) {
                        clearTimeout(timeoutId);
                        reject(new Error('최대 폴링 시도 횟수 초과'));
                        return;
                    }
                    
                    // 대화 내용 가져오기
                    fetch(`{{ url('legal-chat/conversation') }}/${conversationId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.messages && data.messages.length >= 2) {
                                // 마지막 메시지(AI 응답) 가져오기
                                const lastMessage = data.messages[data.messages.length - 1];
                                
                                if (lastMessage.role === 'assistant' && lastMessage.content) {
                                    clearTimeout(timeoutId);
                                    resolve(lastMessage.content);
                                    return;
                                }
                            }
                            
                            // 아직 응답이 없으면 3초 후 다시 시도
                            setTimeout(() => poll(attempts + 1), 3000);
                        })
                        .catch(error => {
                            console.error('Error polling conversation:', error);
                            clearTimeout(timeoutId);
                            reject(error);
                        });
                }
                
                // 첫 번째 폴링 시작
                poll();
            });
        }
        
        
        // Promise 기반 마지막 응답 가져오기
        function fetchLastResponsePromise() {
            return new Promise((resolve, reject) => {
                // 대화 목록을 가져와서 가장 최근 대화를 찾음
                fetch('{{ route("legal-chat.conversations") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.conversations || data.conversations.length === 0) {
                            reject(new Error('대화 목록이 비어 있습니다.'));
                            return;
                        }
                        
                        // 가장 최근 대화 ID 가져오기 (정렬 가정)
                        const latestConversation = data.conversations[0];
                        
                        // 해당 대화의 상세 내용 가져오기
                        return fetch(`{{ url('legal-chat/conversation') }}/${latestConversation.id}`);
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.messages || data.messages.length < 2) {
                            reject(new Error('대화에서 응답을 찾을 수 없습니다.'));
                            return;
                        }
                        
                        // AI 응답 메시지 찾기 (마지막 메시지)
                        const lastAiMessage = data.messages
                            .filter(msg => msg.role === 'assistant')
                            .pop();
                        
                        if (!lastAiMessage || !lastAiMessage.content) {
                            reject(new Error('대화에서 유효한 AI 응답을 찾을 수 없습니다.'));
                            return;
                        }
                        
                        // 응답을 반환
                        resolve(lastAiMessage.content);
                    })
                    .catch(error => {
                        console.error('Error fetching last response:', error);
                        reject(error);
                    });
            });
        }
        
        // 보정서 항목정리 통합 함수
        async function processDocumentCorrection() {
            if (!currentFilePath) {
                alert('처리할 PDF 파일을 먼저 선택해주세요.');
                return;
            }
            
            // 작업 진행 확인
            if (!confirm('보정서 항목을 정리하시겠습니까?')) {
                return;
            }
            
            // 상태 모달 표시 준비
            const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            const statusMessage = document.getElementById('status-message');
            
            try {
                // 모달 표시
                statusMessage.textContent = 'PDF 파일을 텍스트로 변환중...';
                statusModal.show();
                
                // 1단계: PDF를 텍스트로 변환
                try {
                    const textContent = await convertPDFToTextPromise();
                    
                    // 텍스트 편집기에 결과 표시
                    textEditor.value = textContent;
                    
                    // 텍스트 탭으로 전환
                    switchTab('text-content');
                    
                    // 변환 완료 표시
                    statusMessage.textContent = '텍스트 생성 완료됨';
                    
                    // 3초 대기
                    await new Promise(resolve => setTimeout(resolve, 3000));
                } catch (error) {
                    console.error('텍스트 변환 오류:', error);
                    statusModal.hide();
                    alert('PDF 변환 중 오류가 발생했습니다: ' + error.message);
                    return;
                }
                
                // 2단계: LLM으로 텍스트 처리
                try {
                    // 상태 메시지 업데이트
                    statusMessage.textContent = '보정 항목 정리중...';
                    
                    // 텍스트 처리 시도
                    const processedText = await processTextPromise(textEditor.value);
                    textEditor.value = processedText;
                    
                    // 처리 완료, 모달 닫기
                    statusModal.hide();
                } catch (error) {
                    console.error('텍스트 처리 중 오류:', error);
                    
                    // 에러가 발생하면 가져오기 시도
                    try {
                        statusMessage.textContent = '타임아웃 에러로 인해 대화목록에서 정리된 보정항목을 가져오는 중...';
                        
                        // 10초 기다린 후 마지막 응답 가져오기 (LLM이 응답할 시간을 줌)
                        await new Promise(resolve => setTimeout(resolve, 10000));
                        
                        const lastResponse = await fetchLastResponsePromise();
                        textEditor.value = lastResponse;
                        
                        // 가져오기 완료, 모달 닫기
                        statusModal.hide();
                    } catch (fetchError) {
                        console.error('응답 가져오기 오류:', fetchError);
                        statusMessage.textContent = '오류가 발생했습니다: ' + fetchError.message;
                        
                        // 3초 후 모달 닫기
                        setTimeout(() => {
                            statusModal.hide();
                        }, 3000);
                    }
                }
            } catch (error) {
                console.error('통합 처리 중 오류:', error);
                statusMessage.textContent = '오류가 발생했습니다: ' + error.message;
                
                // 5초 후 모달 닫기
                setTimeout(() => {
                    statusModal.hide();
                }, 5000);
            }
        }
        
        
    });
</script>
@endpush