@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- 대화 목록 -->
        <div class="col-md-3">
            <div class="sidebar-chat" style="background-color: white; border: 1px solid #e9ecef; border-radius: 0.25rem; overflow: hidden; display: flex; flex-direction: column; height: calc(100vh - 180px);">
                <div class="sidebar-header" style="padding: 15px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                    <div class="sidebar-title" style="font-size: 1.1rem; font-weight: 600;">대화 목록</div>
                    <button class="new-chat-btn" id="newChatBtn" style="background-color: #4361ee; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                
                <div class="chat-search" style="padding: 10px 15px; position: relative;">
                    <input type="text" placeholder="대화 검색..." style="width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid #e9ecef; font-size: 0.85rem; background-color: #f7f9fc;">
                    <i class="bi bi-search" style="position: absolute; right: 25px; top: 18px; color: #6c757d;"></i>
                </div>
                
                <div class="chat-list" id="conversationList" style="flex: 1; overflow-y: auto; padding: 10px;">
                    <!-- 대화 목록에 내용이 없을 때 빈 공간만 표시 -->
                </div>
            </div>
        </div>
        
        <!-- 메인 채팅 영역 -->
        <div class="col-md-9">
            <div class="main-chat" style="display: flex; flex-direction: column; background-color: white; border: 1px solid #e9ecef; border-radius: 0.25rem; height: calc(100vh - 180px);">
                <div class="chat-header" style="padding: 15px 20px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center;">
                    <!-- 작은 원형 애니메이션만 표시 -->
                    <div class="mini-pastel-circle" style="width: 38px; height: 38px;"></div>
                </div>
                
                <div class="chat-messages" id="chatMessages" style="flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                    <div class="text-center my-5">
                        <!-- 로고, 설명 텍스트 및 샘플 질문 제거 -->
                        <div class="pastel-circle-container" style="display: flex; justify-content: center; align-items: center; height: 300px;">
                            <div class="pastel-circle"></div>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input-container" style="padding: 15px; border-top: 1px solid #e9ecef;">
                    <div class="chat-input-wrapper" style="display: flex; align-items: flex-end; background-color: #f7f9fc; border-radius: 10px; padding: 8px 15px; position: relative;">
                        <textarea id="userInput" class="chat-input" placeholder="메시지를 입력하세요..." style="flex: 1; border: none; background: transparent; resize: none; max-height: 120px; min-height: 24px; padding: 5px 0; font-size: 0.9rem; outline: none;"></textarea>
                        <div class="input-actions" style="display: flex; align-items: center; margin-left: 10px;">
                            <!-- 모델 선택 드롭다운 추가 -->
                            <div class="dropdown" style="margin-right: 8px;">
                                <button class="btn btn-sm dropdown-toggle" type="button" id="modelDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; padding: 2px 8px; background-color: #f7f9fc; color: #6c757d; border: 1px solid #dee2e6;">
                                    GPT-4.1
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="modelDropdown" style="font-size: 0.75rem;">
                                    <li><a class="dropdown-item model-item" href="#" data-model="claude-3-5-sonnet">Claude 3.5 Sonnet</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="gpt-4o">GPT-4o</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="gpt-4.1">GPT-4.1</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="gpt-4.1-mini">GPT-4.1 Mini</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="gpt-4o-mini">GPT-4o Mini</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="o1">o1</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="o3">o3</a></li>
                                    <li><a class="dropdown-item model-item" href="#" data-model="o4-mini">o4 Mini</a></li>
                                </ul>
                            </div>
                            
                            <!-- 모드 선택 드롭다운 추가 -->
                            <div class="dropdown" style="margin-right: 8px;">
                                <button class="btn btn-sm dropdown-toggle" type="button" id="chatModeDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; padding: 2px 8px; background-color: #f7f9fc; color: #6c757d; border: 1px solid #dee2e6;">
                                    GENERAL
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="chatModeDropdown" style="font-size: 0.75rem;">
                                    <li><a class="dropdown-item chat-mode-item" href="#" data-mode="GENERAL">GENERAL</a></li>
                                    <li><a class="dropdown-item chat-mode-item" href="#" data-mode="채무자회생법">채무자회생법</a></li>
                                </ul>
                            </div>
                            <button id="sendButton" class="send-btn" style="background-color: #4361ee; color: white; border: none; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 소스 정보 모달 -->
<div class="modal fade" id="sourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">참고 법률 조항</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sourceModalBody" style="max-height: 70vh; overflow-y: auto;">
                <!-- 소스 정보가 여기에 동적으로 로드됩니다 -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root {
        --primary-color: #4361ee;
        --light-bg: #f7f9fc;
        --dark-text: #333;
        --gray-text: #6c757d;
        --light-text: #f8f9fa;
        --border-color: #e9ecef;
        --highlight: #e9f3ff;
        --success: #2ecc71;
    }
    
    /* 파스텔 색상 원형 애니메이션 */
    .pastel-circle {
        width: 160px;
        height: 160px;
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
        box-shadow: 0 0 30px rgba(0,0,0,0.03);
        position: relative;
    }
    
    /* 헤더의 작은 원형 애니메이션 */
    .mini-pastel-circle {
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
    
    .mini-pastel-circle::after {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: inherit;
        border-radius: 50%;
        filter: blur(8px);
        opacity: 0.2;
        z-index: -1;
        animation: pulseAnimation 8s ease-in-out infinite alternate;
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
    
    /* 채팅 메시지 스타일 */
    .message {
        display: flex;
        gap: 15px;
        max-width: 85%;
        margin-bottom: 20px;
    }
    
    .message.ai {
        max-width: 90%;
    }
    
    .message.user {
        align-self: flex-end;
        flex-direction: row-reverse;
        max-width: 75%;
        justify-content: flex-end;
    }
    
    .message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .ai-message-avatar {
        background-color: var(--primary-color);
        color: white;
    }
    
    .user-message-avatar {
        background-color: #e9ecef;
        color: var(--dark-text);
    }
    
    .message-content {
        background-color: var(--light-bg);
        padding: 12px 18px;
        border-radius: 18px;
        position: relative;
        max-width: 85%;
    }
    
    .message.user .message-content {
        background-color: var(--primary-color);
        color: white;
        border-top-right-radius: 2px;
    }
    
    .message.ai .message-content {
        background-color: var(--light-bg);
        border-top-left-radius: 2px;
    }
    
    .message-time {
        font-size: 0.7rem;
        color: var(--gray-text);
        margin-top: 5px;
        text-align: right;
    }
    
    .message.user .message-time {
        text-align: left;
    }
    
    /* 대화 목록 스타일 */
    .conversation-item {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: background-color 0.2s;
        position: relative;
    }
    
    .conversation-item:hover {
        background-color: var(--highlight);
    }
    
    .conversation-item.active {
        background-color: var(--highlight);
        border-left: 3px solid var(--primary-color);
    }
    
    .conversation-item-title {
        font-weight: 500;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .conversation-item-preview {
        font-size: 0.8rem;
        color: var(--gray-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .chat-time {
        font-size: 0.7rem;
        color: var(--gray-text);
        display: block;
        text-align: right;
        margin-top: 5px;
    }
    
    /* 텍스트 영역 자동 높이 조절 */
    .chat-input {
        overflow: auto;
    }
    
    /* 마크다운 스타일 개선 */
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
    
    .markdown-content table {
        width: 100%;
        margin-bottom: 1rem;
        border-collapse: collapse;
    }
    
    .markdown-content table th,
    .markdown-content table td {
        padding: 0.5rem;
        border: 1px solid #dee2e6;
    }
    
    .markdown-content table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .markdown-content blockquote {
        padding: 0.5rem 1rem;
        margin-bottom: 1rem;
        border-left: 4px solid var(--primary-color);
        background-color: #f8f9fa;
    }
    
    .markdown-content code {
        padding: 0.2rem 0.4rem;
        background-color: #f8f9fa;
        border-radius: 3px;
        font-family: monospace;
    }
    
    .markdown-content pre {
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f8f9fa;
        border-radius: 5px;
        overflow-x: auto;
    }
    
    .markdown-content hr {
        margin: 1.5rem 0;
        border: 0;
        border-top: 1px solid #dee2e6;
    }
    
    .time-stamp {
        font-size: 0.75rem;
        color: var(--gray-text);
        margin-top: 5px;
    }
    
    .user-message .time-stamp {
        text-align: right;
    }
    
    .source-link {
        margin-top: 10px;
    }
    
    .source-link button {
        font-size: 0.8rem;
        padding: 2px 8px;
    }
    
    .chat-loader {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .chat-loader .spinner-border {
        width: 1.5rem;
        height: 1.5rem;
        margin-right: 10px;
    }
    
    .delete-conversation {
        visibility: hidden;
    }
    
    .conversation-item:hover .delete-conversation {
        visibility: visible;
    }
    
    /* 법률 조항 모달 스타일 */
    .source-item {
        margin-bottom: 1.5rem;
    }
    
    .source-content {
        white-space: pre-line;  /* 줄바꿈 처리 */
        word-break: keep-all;   /* 한글 단어 단위 줄바꿈 */
        overflow-wrap: break-word;
        line-height: 1.6;
    }
    
    /* 모달 스크롤바 스타일링 */
    #sourceModalBody::-webkit-scrollbar {
        width: 8px;
    }
    
    #sourceModalBody::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    #sourceModalBody::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    #sourceModalBody::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* 사이드바 채팅 영역 스타일 */
    .sidebar-chat {
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    /* 메인 채팅 영역 스타일 */
    .main-chat {
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    /* 모바일 반응형 */
    @media (max-width: 768px) {
        .sidebar-chat {
            height: auto !important;
            margin-bottom: 20px;
        }
        
        .main-chat {
            height: calc(100vh - 250px) !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 변수 초기화
        let currentConversationId = null;
        let isWaitingForResponse = false;
        let typingInterval = null;
        let currentChatMode = 'GENERAL'; // 채팅 모드 변수 추가
        let currentModel = 'gpt-4.1'; // 모델 변수 추가
        let userHasScrolled = false; // 사용자의 스크롤 상태 추적
        
        // DOM 요소
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');
        const chatMessages = document.getElementById('chatMessages');
        const conversationList = document.getElementById('conversationList');
        const newChatBtn = document.getElementById('newChatBtn');
        const currentChatTitle = document.getElementById('currentChatTitle');
        const chatModeDropdown = document.getElementById('chatModeDropdown');
        const modelDropdown = document.getElementById('modelDropdown');
        
        // 스크롤 이벤트 처리
        chatMessages.addEventListener('scroll', function() {
            // 현재 스크롤 위치가 맨 아래보다 위에 있다면 사용자가 스크롤했다고 판단
            if (chatMessages.scrollHeight - chatMessages.scrollTop > chatMessages.clientHeight + 100) {
                userHasScrolled = true;
            } else {
                userHasScrolled = false;
            }
        });
        
        // 스크롤 함수 - 사용자 스크롤 상태를 확인
        function scrollToBottomIfNeeded() {
            if (!userHasScrolled) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }
        
        // 채팅 모드 선택 이벤트 추가
        document.querySelectorAll('.chat-mode-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const mode = this.getAttribute('data-mode');
                currentChatMode = mode;
                chatModeDropdown.textContent = mode;
            });
        });
        
        // 모델 선택 이벤트 추가
        document.querySelectorAll('.model-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const model = this.getAttribute('data-model');
                currentModel = model;
                modelDropdown.textContent = this.textContent;
            });
        });
        
        // 텍스트 영역 자동 높이 조절
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        
        // marked.js 설정
        marked.setOptions({
            breaks: true,        // 줄바꿈 허용
            gfm: true,           // GitHub Flavored Markdown 활성화
            headerIds: true,     // 헤더에 ID 부여
            mangle: false,       // ID 변환 비활성화
            sanitize: false,     // HTML 태그 허용 (주의: XSS 위험 있음)
            tables: true         // 테이블 활성화
        });
        
        // 대화 목록 로드
        loadConversations();
        
        // 메시지 전송 이벤트
        sendButton.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // 새 대화 버튼 클릭 이벤트
        newChatBtn.addEventListener('click', function() {
            currentConversationId = null;
            
            // 이전에 제거된 currentChatTitle 엘리먼트 체크 후 있을 때만 텍스트 설정
            if (currentChatTitle) {
                currentChatTitle.textContent = 'AI 법률 챗봇';
            }
            
            chatMessages.innerHTML = `
                <div class="text-center my-5">
                    <div class="pastel-circle-container" style="display: flex; justify-content: center; align-items: center; height: 300px;">
                        <div class="pastel-circle"></div>
                    </div>
                </div>
            `;
            
            // 대화 목록에서 활성화된 항목 제거
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
        });
        
        // 메시지 전송 함수
        function sendMessage() {
            const question = userInput.value.trim();
            if (question === '' || isWaitingForResponse) return;
            
            // 입력 필드 초기화
            userInput.value = '';
            userInput.style.height = 'auto';
            isWaitingForResponse = true;
            
            // 사용자 메시지 표시
            const messageTime = new Date();
            const timeString = messageTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            // 처음 메시지인 경우 대화 화면 초기화
            if (chatMessages.querySelector('.text-center')) {
                chatMessages.innerHTML = '';
            }
            
            // 새로운 메시지 스타일로 사용자 메시지 추가
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
            
            // 로딩 표시 (텍스트와 스피너 제거)
            const loaderId = `loader-${Date.now()}`;
            chatMessages.innerHTML += `
                <div class="message ai" id="${loaderId}">
                    <div class="mini-pastel-circle message-avatar" style="width: 36px; height: 36px;"></div>
                    <div>
                        <div class="message-content">
                            <span id="loading-text-${loaderId}">Generating</span><span id="loading-dots-${loaderId}">...</span>
                        </div>
                    </div>
                </div>
            `;
            
            // 로딩 마침표 애니메이션
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
            
            // 맨 처음에는 스크롤을 아래로 내립니다 (사용자가 아직 스크롤하지 않은 상태)
            userHasScrolled = false;
            scrollToBottomIfNeeded();
            
            // 백엔드에서는 스트리밍으로 처리하지만, 클라이언트에서는 일반 응답으로 받음
            // 이렇게 하면 서버에서는 타임아웃을 방지하면서 클라이언트에서는 안정적으로 표시 가능
            fetch('{{ route("legal-chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    question: question,
                    conversation_id: currentConversationId,
                    mode: currentChatMode,
                    model: currentModel
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP 오류: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // 로딩 표시 제거
                const chatLoader = document.getElementById(loaderId);
                if (chatLoader) chatLoader.remove();
                clearInterval(loadingDotsInterval); // 로딩 마침표 애니메이션 중지
                
                // 응답 시간
                const responseTime = new Date();
                const responseTimeString = responseTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                
                // 타이핑 효과를 위한 고유 ID 생성
                const typingId = `typing-${Date.now()}`;
                
                // 새로운 AI 메시지 DOM 구조 - 로봇 아이콘 대신 원형 애니메이션
                const aiMessageHTML = `
                    <div class="message ai">
                        <div class="mini-pastel-circle message-avatar" style="width: 36px; height: 36px;"></div>
                        <div>
                            <div class="message-content markdown-content" id="${typingId}"></div>
                            <div class="message-time">
                                ${responseTimeString} 
                                <span class="model-badge" style="font-size: 0.65rem; opacity: 0.7;">${data.model ? (data.model.startsWith('claude') ? 'Claude' : (data.model.startsWith('o') ? data.model.toUpperCase() : 'GPT')) : 'AI'}</span>
                            </div>
                            ${data.sources && data.sources.length > 0 ? 
                                `<div class="source-link" style="display: none;" id="source-${typingId}">
                                    <button class="btn btn-sm btn-outline-primary show-sources" data-sources='${JSON.stringify(data.sources).replace(/'/g, "&#39;")}'>
                                        참고 법률 조항 보기
                                    </button>
                                </div>` : ''}
                        </div>
                    </div>
                `;
                
                // AI 메시지 추가
                chatMessages.insertAdjacentHTML('beforeend', aiMessageHTML);
                
                // 마크다운으로 변환된 응답
                const formattedAnswer = marked.parse(data.answer || '응답을 받지 못했습니다.');
                
                // 타이핑 효과 구현
                const typingTarget = document.getElementById(typingId);
                let index = 0;
                
                // 원본 텍스트
                const rawText = data.answer;
                
                // 텍스트를 타이핑 속도에 맞게 점진적으로 표시
                const typeText = () => {
                    // 이미 타이핑이 완료된 경우
                    if (index >= rawText.length) {
                        clearInterval(typingInterval);
                        
                        // 소스 링크 표시
                        const sourceLink = document.getElementById(`source-${typingId}`);
                        if (sourceLink) {
                            sourceLink.style.display = 'block';
                        }
                        
                        // 최종 마크다운 렌더링 결과물로 교체
                        typingTarget.innerHTML = formattedAnswer;
                        
                        // 소스 버튼 이벤트 리스너 추가
                        const sourceButtons = typingTarget.closest('.message').querySelectorAll('.show-sources');
                        sourceButtons.forEach(button => {
                            button.addEventListener('click', function() {
                                const sources = JSON.parse(this.getAttribute('data-sources'));
                                showSourcesModal(sources);
                            });
                        });
                        
                        // 최종 응답이 완료되면 사용자 스크롤 상태 초기화
                        userHasScrolled = false;
                        scrollToBottomIfNeeded();
                        
                        return;
                    }
                    
                    // 타이핑 효과 계속 진행 - 속도 조절 (한 번에 더 많은 글자 표시)
                    index += 10; // 표시 속도 증가 (원래 5였으나, 좀 더 빠르게 10으로 조정)
                    const partialText = rawText.substring(0, index);
                    
                    // 현재까지 타이핑된 텍스트를 마크다운으로 변환하여 표시
                    typingTarget.innerHTML = marked.parse(partialText);
                    
                    // 사용자 스크롤 상태에 따라 스크롤 유지
                    scrollToBottomIfNeeded();
                };
                
                // 타이핑 효과 시작 (타이핑 속도 더 빠르게, 30ms)
                clearInterval(typingInterval); // 기존 타이머 제거
                typingInterval = setInterval(typeText, 30);
                
                // 현재 대화 ID 업데이트
                if (data.conversation_id) {
                    currentConversationId = data.conversation_id;
                    // 대화 목록 새로고침
                    loadConversations(currentConversationId);
                }
                
                isWaitingForResponse = false;
                
                // 스크롤 맨 아래로
                scrollToBottomIfNeeded();
            })
            .catch(error => {
                console.error('Error:', error);
                // 로딩 표시 제거
                const chatLoader = document.getElementById(loaderId);
                if (chatLoader) chatLoader.remove();
                clearInterval(loadingDotsInterval); // 로딩 마침표 애니메이션 중지
                
                // 오류 메시지 표시 (새 스타일)
                chatMessages.innerHTML += `
                    <div class="message ai">
                        <div class="mini-pastel-circle message-avatar" style="width: 36px; height: 36px;"></div>
                        <div>
                            <div class="message-content text-danger">
                                죄송합니다. 요청을 처리하는 중 오류가 발생했습니다. 다시 시도해 주세요.<br>
                                <small>오류 세부정보: ${error.message || '알 수 없는 오류'}</small>
                            </div>
                        </div>
                    </div>
                `;
                
                isWaitingForResponse = false;
                
                // 스크롤 맨 아래로
                scrollToBottomIfNeeded();
            });
        }
        
        // 응답 형식화 함수
        function formatResponse(text) {
            // null/undefined 체크 추가
            if (!text) return '응답을 받지 못했습니다. API 서버 연결을 확인해주세요.';
            
            // 마크다운을 HTML로 변환
            try {
                return marked.parse(text);
            } catch (e) {
                console.error('마크다운 변환 오류:', e);
                // 오류 발생 시 기본 변환 적용
                return text.replace(/\n/g, '<br>');
            }
        }
        
        // 대화 목록 로드 함수
        function loadConversations(activeId = null) {
            fetch('{{ route("legal-chat.conversations") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.conversations && data.conversations.length > 0) {
                        let html = '';
                        data.conversations.forEach(conv => {
                            const date = new Date(conv.created_at);
                            const dateStr = date.toLocaleDateString();
                            
                            // 제목 표시 (최대 30자)
                            const title = conv.title.length > 30 ? 
                                conv.title.substring(0, 27) + '...' : 
                                conv.title;
                            
                            // 새로운 채팅 항목 스타일 - 삭제 버튼을 마이너스 아이콘으로 변경
                            html += `
                                <div class="conversation-item ${activeId === conv.id ? 'active' : ''}" data-id="${conv.id}">
                                    <div class="conversation-item-title">${title}</div>
                                    <div class="conversation-item-preview">${conv.preview || '대화 내용 없음'}</div>
                                    <span class="chat-time">${dateStr}</span>
                                    <button class="btn btn-sm btn-danger delete-conversation" style="position: absolute; right: 10px; top: 10px; display: none; width: 24px; height: 24px; padding: 0; border-radius: 50%;" data-id="${conv.id}">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                </div>
                            `;
                        });
                        conversationList.innerHTML = html;
                        
                        // 채팅 항목 호버 시 삭제 버튼 표시
                        document.querySelectorAll('.conversation-item').forEach(item => {
                            item.addEventListener('mouseenter', function() {
                                this.querySelector('.delete-conversation').style.display = 'block';
                            });
                            
                            item.addEventListener('mouseleave', function() {
                                this.querySelector('.delete-conversation').style.display = 'none';
                            });
                        });
                        
                        // 대화 클릭 이벤트
                        document.querySelectorAll('.conversation-item').forEach(item => {
                            item.addEventListener('click', function(e) {
                                if (e.target.closest('.delete-conversation')) return;
                                
                                const id = this.getAttribute('data-id');
                                loadConversation(id);
                                
                                // 활성화 표시
                                document.querySelectorAll('.conversation-item').forEach(i => {
                                    i.classList.remove('active');
                                });
                                this.classList.add('active');
                            });
                        });
                        
                        // 삭제 버튼 이벤트
                        document.querySelectorAll('.delete-conversation').forEach(button => {
                            button.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const id = this.getAttribute('data-id');
                                deleteConversation(id);
                            });
                        });
                    } else {
                        // 대화 목록이 없는 경우 - 빈 공간만 표시
                        conversationList.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('Error loading conversations:', error);
                    conversationList.innerHTML = `
                        <div class="text-danger p-3">
                            대화 목록을 불러오는 중 오류가 발생했습니다.
                        </div>
                    `;
                });
        }
        
        // 대화 로드 함수
        function loadConversation(id) {
            currentConversationId = id;
            
            fetch(`{{ url('legal-chat/conversation') }}/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        // 대화 제목 업데이트 - 요소가 존재할 때만 설정
                        if (currentChatTitle) {
                            currentChatTitle.textContent = data.title || '';
                        }
                        
                        // 메시지 표시
                        chatMessages.innerHTML = '';
                        
                        data.messages.forEach((msg, index) => {
                            const date = new Date(msg.timestamp);
                            const timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const uniqueId = `msg-${id}-${index}`;
                            
                            if (msg.role === 'user') {
                                // 사용자 메시지 (새 스타일)
                                chatMessages.innerHTML += `
                                    <div class="message user">
                                        <div class="message-avatar user-message-avatar">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div>
                                            <div class="message-content">${msg.content}</div>
                                            <div class="message-time">${timeString}</div>
                                        </div>
                                    </div>
                                `;
                            } else if (msg.role === 'assistant') {
                                // AI 메시지 (새 스타일) - 로봇 아이콘 대신 원형 애니메이션
                                let sourcesHtml = '';
                                if (msg.sources && msg.sources.length > 0) {
                                    sourcesHtml = `
                                        <div class="source-link">
                                            <button class="btn btn-sm btn-outline-primary show-sources" data-sources='${JSON.stringify(msg.sources).replace(/'/g, "&#39;")}'>
                                                참고 법률 조항 보기
                                            </button>
                                        </div>
                                    `;
                                }
                                
                                chatMessages.innerHTML += `
                                    <div class="message ai">
                                        <div class="mini-pastel-circle message-avatar" style="width: 36px; height: 36px;"></div>
                                        <div>
                                            <div class="message-content markdown-content" id="${uniqueId}">${formatResponse(msg.content)}</div>
                                            <div class="message-time">${timeString}</div>
                                            ${sourcesHtml}
                                        </div>
                                    </div>
                                `;
                            }
                        });
                        
                        // 소스 버튼 이벤트 리스너
                        document.querySelectorAll('.show-sources').forEach(button => {
                            button.addEventListener('click', function() {
                                const sources = JSON.parse(this.getAttribute('data-sources'));
                                showSourcesModal(sources);
                            });
                        });
                        
                        // 스크롤 맨 아래로
                        scrollToBottomIfNeeded();
                    }
                })
                .catch(error => {
                    console.error('Error loading conversation:', error);
                    chatMessages.innerHTML = `
                        <div class="alert alert-danger m-3">
                            대화를 불러오는 중 오류가 발생했습니다.
                        </div>
                    `;
                });
        }
        
        // 대화 삭제 함수
        function deleteConversation(id) {
            if (!confirm('이 대화를 삭제하시겠습니까?')) return;
            
            fetch(`{{ url('legal-chat/conversation') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 대화 목록 새로고침
                    loadConversations();
                    
                    // 현재 대화였다면 초기화
                    if (currentConversationId === id) {
                        newChatBtn.click();
                    }
                }
            })
            .catch(error => {
                console.error('Error deleting conversation:', error);
                alert('대화를 삭제하는 중 오류가 발생했습니다.');
            });
        }
        
        // 소스 모달 표시 함수
        function showSourcesModal(sources) {
            const sourceModalBody = document.getElementById('sourceModalBody');
            let html = '';
            
            // 법률 조항 스타일 개선
            sources.forEach((source, index) => {
                html += `
                    <div class="source-item mb-3">
                        <h5 class="mb-2">${source.law_name} ${source.article_no} ${source.article_title ? `(${source.article_title})` : ''}</h5>
                        <div class="source-content p-3 bg-light rounded">
                            ${source.content}
                        </div>
                    </div>
                `;
                
                if (index < sources.length - 1) {
                    html += '<hr>';
                }
            });
            
            if (sources.length === 0) {
                html = '<p class="text-center text-muted">참고 법률 조항이 없습니다.</p>';
            }
            
            sourceModalBody.innerHTML = html;
            
            // 모달 표시
            const modal = new bootstrap.Modal(document.getElementById('sourceModal'));
            modal.show();
        }
    });
</script>
@endpush 