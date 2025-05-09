<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- 파비콘 -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    
    <title>법무법인 로앤</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/main.min.css' rel="stylesheet" />
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/main.min.css' rel="stylesheet" />
    
    @stack('styles')
    
    <style>
        body {
            overflow-x: hidden;
            font-size: 0.85rem;
        }
        
        /* 사이드바 너비 관련 스타일을 한 곳에서 일관되게 관리 */
        :root {
            --sidebar-width: 15rem;
        }
        
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: 0;
            transition: margin 0.25s ease-out;
            width: var(--sidebar-width) !important;
        }
        
        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
        }
        
        .sidebar {
            width: var(--sidebar-width) !important;
            background-color: #212529;
            position: fixed;
            height: 100%;
            z-index: 1050;
            left: 0;
            top: 0;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 1.2rem;
            padding: 1rem;
        }
        
        .sidebar .nav-link {
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,.8) !important;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff !important;
            background-color: rgba(255,255,255,.1);
        }
        
        /* 데스크톱에서는 toggled 클래스가 있을 때 사이드바 숨김 */
        #wrapper.toggled #sidebar-wrapper {
            margin-left: calc(var(--sidebar-width) * -1);
        }
        
        @media (max-width: 768px) {
            /* 모바일에서는 기본적으로 사이드바 숨김 */
            #sidebar-wrapper {
                margin-left: calc(var(--sidebar-width) * -1);
            }
            
            /* 모바일에서는 toggled 클래스가 있을 때 사이드바 표시 */
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
            }
            
            /* 모바일에서 사이드바가 표시될 때만 오버레이 추가 */
            #wrapper.toggled::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                pointer-events: auto; /* 오버레이 클릭 가능하게 설정 */
            }
            
            /* 모바일에서의 네비게이션 바 스타일 개선 */
            .navbar-brand {
                font-size: 1rem;
            }
        }
        
        .nav-link {
            color: rgba(255,255,255,.8) !important;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: #fff !important;
            background-color: rgba(255,255,255,.1);
        }

        .nav-link.dropdown-toggle::after {
            float: right;
            margin-top: 8px;
        }

        .sidebar .collapse .nav-link {
            padding-left: 1rem;
            font-size: 0.9rem;
        }

        .sidebar .collapse.show {
            background-color: rgba(0,0,0,.1);
        }

        /* 상단 네비게이션 바의 링크는 어두운 색상으로 */
        .navbar .nav-link {
            color: #212529 !important;
        }

        /* 메인 컨텐츠 영역 여백 조정 */
        #page-content-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: margin 0.25s ease-out, width 0.25s ease-out;
        }

        #wrapper.toggled #page-content-wrapper {
            margin-left: 0;
            width: 100%;
        }

        @media (max-width: 768px) {
            #page-content-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }

        /* hover 효과가 있는 요소들의 z-index 조정 */
        .truncate-hover:hover::after {
            z-index: 1040;
        }
        
        /* 상단 네비게이션 바도 사이드바보다 위에 있어야 함 */
        .navbar {
            position: relative;
            z-index: 1051;
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- 사이드바 -->
        <div class="sidebar" id="sidebar-wrapper">
            <div class="d-flex align-items-center ps-3 pt-3">
                <a href="{{ route('dashboard') }}" class="sidebar-brand text-decoration-none">법무법인 로앤</a>
            </div>
            
            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i>대시보드
                        </a>
                    </li>
                    @if(Auth::user() && Auth::user()->is_admin)
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('members*') ? 'active' : '' }}" href="{{ route('members.index') }}">
                            <i class="bi bi-people me-2"></i>구성원
                        </a>
                    </li>
                    @endif
                    
                    <!-- 업무관리 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('task-lists*') || Request::is('work-logs*') ? 'active' : '' }}" 
                           href="#taskSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-clipboard-check me-2"></i>업무관리
                        </a>
                        <div class="collapse {{ Request::is('task-lists*') || Request::is('work-logs*') ? 'show' : '' }}" id="taskSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('task-lists*') ? 'active' : '' }}" href="{{ route('task-lists.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>업무리스트
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('work-logs*') ? 'active' : '' }}" href="{{ route('work-logs.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>업무일지
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('targets*') ? 'active' : '' }}" href="{{ route('targets.index') }}">
                            <i class="bi bi-clipboard-data me-2"></i>신건상담
                        </a>
                    </li>

                    <!-- 수임료 관리 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('fee-calendar*') || Request::is('fee-client*') ? 'active' : '' }}" 
                           href="#feeSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-currency-exchange me-2"></i>수임료 관리
                        </a>
                        <div class="collapse {{ Request::is('fee-calendar*') || Request::is('fee-client*') ? 'show' : '' }}" id="feeSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('fee-calendar*') ? 'active' : '' }}" href="{{ route('fee-calendar.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>수임료 캘린더
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('fee-client*') ? 'active' : '' }}" href="{{ route('fee-client.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>고객별 수임료
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 배당현황 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('case-assignments*') || Request::is('correction-div*') || Request::is('correction-div-manual*') || Request::is('correction-manager*') ? 'active' : '' }}" 
                           href="#assignmentSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-diagram-3 me-2"></i>사건배당관리
                        </a>
                        <div class="collapse {{ Request::is('case-assignments*') || Request::is('correction-div*') || Request::is('correction-div-manual*') || Request::is('correction-manager*') ? 'show' : '' }}" id="assignmentSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('case-assignments*') ? 'active' : '' }}" href="{{ route('case-assignments.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>신건배당 및 보정관리
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('correction-manager*') ? 'active' : '' }}" href="{{ route('correction-manager.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>보정서류관리
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('file-download*') ? 'active' : '' }}" href="{{ route('file-download.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>보정서류 다운로드
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('correction-div*') && !Request::is('correction-div-manual*') ? 'active' : '' }}" href="{{ route('correction-div.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>(구)보정서배당
                                    </a>
                                </li>
                                <!-- 보정서 직접입력 메뉴 숨김 -->
                                <!--
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('correction-div-manual*') ? 'active' : '' }}" href="{{ route('correction-div-manual.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>보정서 직접입력
                                    </a>
                                </li>
                                -->
                            </ul>
                        </div>
                    </li>

                    <!-- 입금확인 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('payments*') || Request::is('transactions*') || Request::is('income_entries*') ? 'active' : '' }}" 
                           href="#paymentSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-cash-stack me-2"></i>입금확인
                        </a>
                        <div class="collapse {{ Request::is('payments*') || Request::is('transactions*') || Request::is('income_entries*') ? 'show' : '' }}" id="paymentSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('payments*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>CMS입금
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('transactions') ? 'active' : '' }}" href="{{ route('transactions.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>서울계좌입금
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('transactions2*') ? 'active' : '' }}" href="{{ route('transactions2.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>대전계좌입금
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('transactions3*') ? 'active' : '' }}" href="{{ route('transactions3.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>부산계좌입금
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('income_entries*') ? 'active' : '' }}" href="{{ route('income_entries.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>매출직접입력
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 입금요청 메뉴 추가 -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('transfers*') ? 'active' : '' }}" href="{{ route('transfers.index') }}">
                            <i class="bi bi-bank me-2"></i>입금요청
                        </a>
                    </li>

                    <!-- 근무현황 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('workhours*') || Request::is('work-management*') || Request::is('work-status*') ? 'active' : '' }}" 
                           href="#workSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-calendar-check me-2"></i>근무현황
                        </a>
                        <div class="collapse {{ Request::is('workhours*') || Request::is('work-management*') || Request::is('work-status*') ? 'show' : '' }}" id="workSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('workhours*') ? 'active' : '' }}" href="{{ route('workhours.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>근무일정
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('work-management*') ? 'active' : '' }}" href="{{ route('work-management.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>근태관리
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('work-status*') ? 'active' : '' }}" href="{{ route('work-status.index') }}">
                                        <i class="bi bi-arrow-right me-2"></i>근무현황
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 사내행정 드롭다운 -->
                    <li class="nav-item">
                        <a class="nav-link dropdown-toggle {{ Request::is('laws*') ? 'active' : '' }}" 
                           href="#adminSubmenu" data-bs-toggle="collapse" role="button">
                            <i class="bi bi-building me-2"></i>사내행정
                            @if(App\Models\Law::where('registration_date', '>=', now()->subDays(3))->exists())
                                <i class="bi bi-stars text-danger ms-1" style="font-size: 0.8rem;"></i>
                            @endif
                        </a>
                        <div class="collapse {{ Request::is('laws*') ? 'show' : '' }}" id="adminSubmenu">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('laws*') ? 'active' : '' }}" href="{{ url('laws') }}">
                                        <i class="bi bi-arrow-right me-2"></i>회사내규
                                        @if(App\Models\Law::where('registration_date', '>=', now()->subDays(3))->exists())
                                            <i class="bi bi-stars text-danger ms-1" style="font-size: 0.8rem;"></i>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- AI 법률 챗봇 메뉴 -->
                    <li class="nav-item">
                        <a class="nav-link {{ Request::is('legal-chat*') ? 'active' : '' }}" href="{{ route('legal-chat.index') }}">
                            <i class="bi bi-chat-dots me-2"></i>SPHERE
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 메인 컨텐츠 래퍼 -->
        <div id="page-content-wrapper" class="flex-grow-1">
            <!-- 상단 네비게이션 바 -->
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
                <div class="container-fluid px-4">
                    <!-- 사이드바 토글 버튼 - 명확한 디자인 -->
                    <button class="btn rounded-circle d-flex align-items-center justify-content-center me-2" 
                            id="sidebarToggle" style="width: 40px; height: 40px; background-color: #a7c7e7; color: white;">
                        <i class="bi bi-list" id="sidebarToggleIcon"></i>
                    </button>

                    <!-- 중앙 로고/타이틀 - 모바일에서도 표시 -->
                    <a class="navbar-brand mx-auto d-md-none" href="{{ route('dashboard') }}">법무법인 로앤</a>

                    <!-- 알림 아이콘 - 모바일에서도 표시 -->
                    @auth
                        @if($notificationCounts['total'] > 0)
                            <a href="{{ route('mypage.notifications.index') }}" class="btn btn-link text-danger position-relative me-2 d-md-none">
                                <i class="bi bi-bell-fill"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $notificationCounts['total'] }}
                                </span>
                            </a>
                        @endif
                    @endauth

                    <!-- 회원 메뉴 토글 버튼 - 디자인 변경 -->
                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <i class="bi bi-person-circle fs-4"></i>
                    </button>

                    <!-- 중앙 알림 메시지 - 데스크톱에서만 표시 -->
                    @auth
                        @if($notificationCounts['total'] > 0)
                            <div class="d-none d-md-block text-center flex-grow-1">
                                <span class="text-danger">
                                    <i class="bi bi-bell-fill me-1"></i>
                                    @if(Auth::user()->is_admin && isset($notificationCounts['pending_requests']) && $notificationCounts['pending_requests'] > 0)
                                        신청함 관리 및 마이페이지를 확인하여 필요한 승인, 답변을 완료하세요.
                                    @else
                                        마이페이지를 확인하여 필요한 승인, 답변을 완료하세요.
                                    @endif
                                </span>
                            </div>
                        @endif
                    @endauth

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- 우측 메뉴 -->
                        <ul class="navbar-nav ms-auto">
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="nav-link text-dark" href="{{ route('login') }}">로그인</a>
                                    </li>
                                @endif

                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link text-dark" href="{{ route('register') }}">회원가입</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-dark" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ Auth::user()->name }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if(Auth::user() && Auth::user()->is_admin)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.users.pending') }}">
                                                사용자 승인
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.notifications.index') }}">
                                                통지보내기
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.salary-contracts.index') }}">
                                                연봉계약서 관리
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.salary-statements.index') }}">
                                                급여명세서 관리
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.social-insurances.index') }}">
                                                사대보험 관리
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.requests.index') }}">
                                                신청함 관리
                                                @if(Auth::user() && Auth::user()->is_admin && isset($notificationCounts['pending_requests']) && $notificationCounts['pending_requests'] > 0)
                                                    <span class="badge bg-danger float-end">{{ $notificationCounts['pending_requests'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item position-relative" href="{{ route('mypage.notifications.index') }}">
                                                나의 통지함
                                                @if($notificationCounts['notifications'] > 0)
                                                    <span class="badge bg-danger float-end">{{ $notificationCounts['notifications'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item position-relative" href="{{ route('mypage.salary-contracts.index') }}">
                                                연봉계약서
                                                @if($notificationCounts['salary_contracts'] > 0)
                                                    <span class="badge bg-danger float-end">{{ $notificationCounts['salary_contracts'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item position-relative" href="{{ route('mypage.salary-statements.index') }}">
                                                급여명세서
                                                @if($notificationCounts['salary_statements'] > 0)
                                                    <span class="badge bg-danger float-end">{{ $notificationCounts['salary_statements'] }}</span>
                                                @endif
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item position-relative" href="{{ route('mypage.requests.index') }}">
                                                나의 신청함
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                                회원정보 수정
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logout') }}"
                                               onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                                로그아웃
                                            </a>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- 메인 컨텐츠 -->
            <main class="p-4">
                <!-- 플래시 메시지 섹션 추가 -->
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

                @yield('content')
            </main>
        </div>
    </div>

    <!-- 스크립트 순서 조정 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    @stack('scripts')

    <!-- 사이드바 스크립트를 마지막에 배치 -->
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');
            const wrapper = document.getElementById('wrapper');

            // 사이드바 토글 함수
            function toggleSidebar() {
                wrapper.classList.toggle('toggled');
                
                // 아이콘 변경으로 현재 상태 표시
                if (wrapper.classList.contains('toggled')) {
                    // 모바일에서는 toggled가 있을 때 사이드바가 보임
                    if (window.innerWidth <= 768) {
                        sidebarToggleIcon.classList.remove('bi-list-nested');
                        sidebarToggleIcon.classList.add('bi-list');
                    } else {
                        // 데스크톱에서는 toggled가 있을 때 사이드바가 숨겨짐
                        sidebarToggleIcon.classList.remove('bi-list');
                        sidebarToggleIcon.classList.add('bi-list-nested');
                    }
                } else {
                    // 모바일에서는 toggled가 없을 때 사이드바가 숨겨짐
                    if (window.innerWidth <= 768) {
                        sidebarToggleIcon.classList.remove('bi-list');
                        sidebarToggleIcon.classList.add('bi-list-nested');
                    } else {
                        // 데스크톱에서는 toggled가 없을 때 사이드바가 보임
                        sidebarToggleIcon.classList.remove('bi-list-nested');
                        sidebarToggleIcon.classList.add('bi-list');
                    }
                }
            }

            // 토글 버튼에 이벤트 리스너 추가
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    event.preventDefault();
                    toggleSidebar();
                });
            }
            
            // 오버레이 클릭 시 사이드바 닫기
            document.addEventListener('click', function(event) {
                // 모바일 모드이고 사이드바가 열려있을 때만 처리
                if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
                    // 클릭된 요소가 사이드바 내부가 아니고 토글 버튼도 아닌 경우
                    if (!event.target.closest('#sidebar-wrapper') && 
                        !event.target.closest('#sidebarToggle')) {
                        // 사이드바 닫기
                        wrapper.classList.remove('toggled');
                        sidebarToggleIcon.classList.remove('bi-list');
                        sidebarToggleIcon.classList.add('bi-list-nested');
                    }
                }
            });

            // 모바일 대응 - 초기 상태 설정
            function initMobileState() {
                if (window.innerWidth <= 768) {
                    // 모바일에서는 기본적으로 사이드바 숨김 (toggled 클래스 제거)
                    wrapper.classList.remove('toggled');
                    sidebarToggleIcon.classList.remove('bi-list');
                    sidebarToggleIcon.classList.add('bi-list-nested');
                } else {
                    // 데스크톱에서는 기본적으로 사이드바 표시 (toggled 클래스 제거)
                    wrapper.classList.remove('toggled');
                    sidebarToggleIcon.classList.remove('bi-list-nested');
                    sidebarToggleIcon.classList.add('bi-list');
                }
            }

            // 초기 로드 시 실행
            initMobileState();
            
            // 화면 크기 변경 시 실행
            window.addEventListener('resize', initMobileState);
        });

        // 3초 후에 알림 자동 제거
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>
</body>
</html>

