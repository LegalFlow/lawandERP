<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\IncomeEntryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Transaction2Controller;
use App\Http\Controllers\Transaction3Controller;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\CaseAssignmentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CorrectionDivController;
use App\Http\Controllers\CorrectionDivManualController;
use App\Http\Controllers\WorkhourController;
use App\Http\Controllers\WorkManagementController;
use App\Http\Controllers\Admin\SalaryContractController;
use App\Http\Controllers\MyPage\SalaryContractController as MypageSalaryContractController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\LawController;
use App\Http\Controllers\Admin\SalaryStatementController;
use App\Http\Controllers\MyPage\SalaryStatementController as MypageSalaryStatementController;
use App\Http\Controllers\WorkStatusController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\MyPage\NotificationController as MypageNotificationController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\TransferFileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\TaskListController;
use App\Http\Controllers\MyPage\RequestController as MypageRequestController;
use App\Http\Controllers\Admin\RequestController as AdminRequestController;
use App\Http\Controllers\LawyerFeeCalendarController;
use App\Http\Controllers\LawyerFeeClientController;
use App\Http\Controllers\CorrectionManagerController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\LegalChatController;
use App\Http\Controllers\DocumentEditorController;

// 인증 관련 라우트
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    // 로그아웃
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // 회원정보 수정
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/document/{document}', [ProfileController::class, 'deleteDocument'])->name('profile.document.delete');
    
    // 승인 대기
    Route::get('/awaiting-approval', [RegisterController::class, 'awaitingApproval'])
        ->name('awaiting.approval');
    
    // 관리자 기능
    Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users/pending', [AdminController::class, 'pendingUsers'])->name('users.pending');
        Route::post('/users/{user}/approve', [AdminController::class, 'approveUser'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('users.reject');
        Route::post('/users/{user}/revoke', [AdminController::class, 'revokeApproval'])->name('users.revoke');
        Route::get('/users/document/{document}/download', [AdminController::class, 'downloadDocument'])->name('users.document.download');
        
        // 사대보험 관련 라우트 추가
        Route::get('/social-insurances', [App\Http\Controllers\Admin\SocialInsuranceController::class, 'index'])
            ->name('social-insurances.index');
        Route::post('/social-insurances/upload', [App\Http\Controllers\Admin\SocialInsuranceController::class, 'upload'])
            ->name('social-insurances.upload');
        
        // 연봉계약서
        Route::resource('salary-contracts', SalaryContractController::class);
        Route::post('/salary-contracts/{salaryContract}/approve', [SalaryContractController::class, 'approve'])
            ->name('salary-contracts.approve');
        Route::get('/members/active', [SalaryContractController::class, 'getActiveMembers'])
            ->name('members.active');
        Route::post('/salary-contracts/store-individual', [SalaryContractController::class, 'storeIndividual'])
            ->name('salary-contracts.store-individual');

        // 급여명세서 관리
        Route::get('/salary-statements/performance-status', [SalaryStatementController::class, 'getPerformanceStatus'])
            ->name('salary-statements.performance-status');
        Route::post('/salary-statements/apply-performance-bonus', [SalaryStatementController::class, 'applyPerformanceBonus'])
            ->name('salary-statements.apply-performance-bonus');
        Route::post('/salary-statements/update-insurance', [SalaryStatementController::class, 'updateInsurance'])
            ->name('salary-statements.update-insurance');
        Route::post('/salary-statements/update-pension', [SalaryStatementController::class, 'updatePension'])
            ->name('salary-statements.update-pension');
        Route::post('/salary-statements/update-employment', [SalaryStatementController::class, 'updateEmployment'])
            ->name('salary-statements.update-employment');
        // 고용보험 재계산 라우트 추가
        Route::post('/salary-statements/recalculate-employment', [SalaryStatementController::class, 'recalculateEmployment'])
            ->name('salary-statements.recalculate-employment');
        // 소득세 재계산 라우트 추가
        Route::post('/salary-statements/recalculate-income-tax', [SalaryStatementController::class, 'recalculateIncomeTax'])
            ->name('salary-statements.recalculate-income-tax');
        // 새로운 필터 라우트 추가
        Route::get('/salary-statements/filter', [SalaryStatementController::class, 'filter'])
            ->name('salary-statements.filter');
        Route::get('/salary-statements/search', [SalaryStatementController::class, 'search'])
            ->name('salary-statements.search');
        Route::get('/salary-statements/download-excel', [SalaryStatementController::class, 'downloadExcel'])
            ->name('salary-statements.download-excel');
        // 급여대장 다운로드 라우트 추가
        Route::get('/salary-statements/download-payroll', [SalaryStatementController::class, 'downloadPayroll'])
            ->name('salary-statements.download-payroll');
        // 퇴직급여 다운로드 라우트 추가
        Route::get('/salary-statements/download-pension', [SalaryStatementController::class, 'downloadPension'])
            ->name('salary-statements.download-pension');
        // 급여 처리 체크리스트 라우트 추가
        Route::get('/salary-statements/process-checklist', [SalaryStatementController::class, 'getProcessChecklist'])
            ->name('salary-statements.process-checklist');
        // PDF 다운로드 라우트 추가
        Route::get('/salary-statements/{salaryStatement}/pdf', [SalaryStatementController::class, 'generatePdf'])
            ->name('salary-statements.pdf');
        Route::resource('salary-statements', SalaryStatementController::class);
        Route::post('/salary-statements/bulk-create', [SalaryStatementController::class, 'bulkCreate'])
            ->name('salary-statements.bulk-create');
        Route::post('/salary-statements/{salaryStatement}/approve', [SalaryStatementController::class, 'approve'])
            ->name('salary-statements.approve');
        Route::post('/salary-statements/sync-social-insurance', [SalaryStatementController::class, 'syncSocialInsurance'])
            ->name('salary-statements.sync-social-insurance');

        // 통지 관리
        Route::resource('notifications', NotificationController::class);

        // 연봉계약서 관련 라우트 내에 PDF 다운로드 라우트 추가
        Route::get('/salary-contracts/{salaryContract}/pdf', [SalaryContractController::class, 'generatePdf'])
            ->name('salary-contracts.pdf');

        // 신청서 관리 라우트
        Route::get('/requests', [AdminRequestController::class, 'index'])
            ->name('requests.index');
        Route::get('/requests/{request}', [AdminRequestController::class, 'show'])
            ->name('requests.show');
        Route::post('/requests/{request}/process', [AdminRequestController::class, 'process'])
            ->name('requests.process');
        Route::get('/requests/files/{file}/download', [AdminRequestController::class, 'downloadFile'])
            ->name('requests.download-file');
    });

    // 메인 대시보드
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 법률 AI 챗봇
    Route::get('/legal-chat', [LegalChatController::class, 'index'])->name('legal-chat.index');
    Route::get('/legal-chat/models', [LegalChatController::class, 'getModels'])->name('legal-chat.models');
    Route::get('/legal-chat/conversations', [LegalChatController::class, 'getConversations'])->name('legal-chat.conversations');
    Route::get('/legal-chat/conversation/{id}', [LegalChatController::class, 'getConversation'])->name('legal-chat.conversation');
    Route::delete('/legal-chat/conversation/{id}', [LegalChatController::class, 'deleteConversation'])->name('legal-chat.delete');
    Route::post('/legal-chat/send', [LegalChatController::class, 'sendMessage'])->name('legal-chat.send');
    Route::get('/legal-chat/send', [LegalChatController::class, 'sendMessage']);
    Route::post('/legal-chat/stream', [LegalChatController::class, 'streamMessage'])->name('legal-chat.stream');

    // export 라우트를 resource 라우트보다 먼저 정의
    Route::get('/targets/export', [TargetController::class, 'export'])->name('targets.export');
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    
    // 서울계좌입금 export 라우트
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::resource('transactions', TransactionController::class);
 
     // 대전계좌입금 export 라우트
     Route::get('/transactions2/export', [Transaction2Controller::class, 'export'])->name('transactions2.export');
     Route::resource('transactions2', Transaction2Controller::class);
 
     // 부산계좌입금 export 라우트
     Route::get('/transactions3/export', [Transaction3Controller::class, 'export'])->name('transactions3.export');
     Route::resource('transactions3', Transaction3Controller::class);
 
     // 매출직접입력 export 라우트
     Route::get('/income_entries/export', [IncomeEntryController::class, 'export'])->name('income_entries.export');
     Route::resource('income_entries', IncomeEntryController::class);

    // 리소스 라우트
    Route::resource('members', MemberController::class)->middleware('admin');
    Route::resource('income_entries', IncomeEntryController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('transactions', TransactionController::class);
    Route::resource('transactions2', Transaction2Controller::class);
    Route::resource('transactions3', Transaction3Controller::class);
    Route::resource('targets', TargetController::class);

    // 이스 할당
    Route::prefix('case-assignments')->name('case-assignments.')->group(function () {
        Route::get('/', [CaseAssignmentController::class, 'index'])->name('index');
        Route::get('/create', [CaseAssignmentController::class, 'create'])->name('create');
        Route::post('/', [CaseAssignmentController::class, 'store'])->name('store');
        Route::post('/assign/{caseId}', [CaseAssignmentController::class, 'assign'])->name('assign');
        Route::delete('/{id}', [CaseAssignmentController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/update-field', [CaseAssignmentController::class, 'updateField'])
             ->name('update-field');
        Route::patch('/{id}', [CaseAssignmentController::class, 'update'])->name('case-assignments.update');
        
        // 기간별 통계를 위한 새로운 라우트 추가
        Route::post('/period-stats', [CaseAssignmentController::class, 'updatePeriodStats'])
             ->name('period-stats');
             
        // 상세 정보를 가져오는 라우트 추가
        Route::get('/{id}/detail', [CaseAssignmentController::class, 'getDetail'])
             ->name('detail');
             
        // 일괄 상태 정보를 가져오는 라우트 추가
        Route::post('/bulk-status', [CaseAssignmentController::class, 'getBulkStatus'])
             ->name('bulk-status');
             
        // 진행현황 일괄 업데이트 라우트 추가
        Route::post('/bulk-update-case-states', [CaseAssignmentController::class, 'bulkUpdateCaseStates'])
             ->name('bulk-update-case-states');
         
        // 차트 데이터를 가져오는 라우트 추가
        Route::get('/chart-data', [CaseAssignmentController::class, 'getChartData'])
             ->name('chart-data');
    });

    Route::get('/correction-div', [CorrectionDivController::class, 'index'])
        ->name('correction-div.index');

    // 새로운 미제출 현황 통계 라우트 추가
    Route::get('/correction-div/unsubmitted-stats', [CorrectionDivController::class, 'getUnsubmittedStats'])
        ->name('correction-div.unsubmitted-stats');

    Route::get('/correction-div/export', [CorrectionDivController::class, 'export'])
        ->name('correction-div.export');

    Route::put('/correction-div/{correctionDiv}', [CorrectionDivController::class, 'update'])
        ->name('correction-div.update');

    Route::post('/correction-div/{correctionDiv}/memo', [CorrectionDivController::class, 'updateMemo'])
        ->name('correction-div.update-memo');

    Route::get('/correction-div/download/{path}', [CorrectionDivController::class, 'download'])
        ->name('correction-div.download')
        ->where('path', '.*');

    // 보정서 직접입력 라우트
    Route::prefix('correction-div-manual')->name('correction-div-manual.')->group(function () {
        Route::get('/', [CorrectionDivManualController::class, 'index'])->name('index');
        Route::post('/', [CorrectionDivManualController::class, 'store'])->name('store');
        Route::delete('/{id}', [CorrectionDivManualController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/update', [CorrectionDivManualController::class, 'update'])->name('update');
        
        Route::post('/{correctionDiv}/memo', [CorrectionDivManualController::class, 'updateMemo'])
            ->name('update-memo');
        Route::get('/download/{path}', [CorrectionDivManualController::class, 'download'])
            ->name('download')
            ->where('path', '.*');
    });    

    Route::post('/income_entries/songinbu', [IncomeEntryController::class, 'storeSongInBu'])
        ->name('income_entries.store_songinbu')
        ->middleware('auth');

    // 근무관리 라우트
    Route::prefix('work-management')->name('work-management.')->group(function () {
        Route::get('/', [WorkManagementController::class, 'index'])->name('index');
        Route::get('/annual-leaves', [WorkManagementController::class, 'getAllAnnualLeaves'])->name('annual-leaves');
    });

    // Workhour 관련 라우트 정리
    Route::prefix('workhours')->name('workhours.')->middleware(['auth'])->group(function () {
        // 기본 뷰 표시
        Route::get('/', [WorkhourController::class, 'index'])->name('index');
        
        // 일정 조회
        Route::get('/stats', [WorkhourController::class, 'getScheduleStats'])->name('stats');
        Route::get('/weekly', [WorkhourController::class, 'getWeeklySchedule'])->name('weekly');
        
        // 일정 저장
        Route::post('/store', [WorkhourController::class, 'store'])->name('store');
        Route::post('/store-weekly', [WorkhourController::class, 'storeWeekly'])->name('store-weekly');
        
        // 초기화 라우트 추가
        Route::post('/reset-weekly', [WorkhourController::class, 'resetWeekly'])->name('reset-weekly');

        // 관리자 전용 자동생성 라우트
        Route::middleware('admin')->group(function () {
            Route::get('/eligible-members', [WorkhourController::class, 'getEligibleMembers'])
                ->name('eligible-members');
            Route::post('/auto-generate/check-holidays', [WorkhourController::class, 'checkHolidays'])
                ->name('check-holidays');
            Route::post('/auto-generate', [WorkhourController::class, 'autoGenerate'])
                ->name('auto-generate');
        });
    });

    // 마이페이지 라우트
    Route::prefix('mypage')->name('mypage.')->group(function () {
        // 연봉계약서
        Route::get('/salary-contracts', [MypageSalaryContractController::class, 'index'])
            ->name('salary-contracts.index');
        Route::get('/salary-contracts/{salaryContract}', [MypageSalaryContractController::class, 'show'])
            ->name('salary-contracts.show');
        Route::post('/salary-contracts/{salaryContract}/approve', [MypageSalaryContractController::class, 'approve'])
            ->name('salary-contracts.approve');
        // PDF 다운로드 라우트 추가
        Route::get('/salary-contracts/{salaryContract}/pdf', [MypageSalaryContractController::class, 'generatePdf'])
            ->name('salary-contracts.pdf');

        // 급여명세서 라우트
        Route::get('/salary-statements', [MypageSalaryStatementController::class, 'index'])
            ->name('salary-statements.index');

        // 연말정산 파일 다운로드 라우트 (구체적인 라우트를 먼저 정의)
        Route::get('/salary-statements/download-tax-file', [MypageSalaryStatementController::class, 'downloadTaxFile'])
            ->name('salary-statements.download-tax-file');

        // 와일드카드 라우트는 나중에 정의
        Route::get('/salary-statements/{salaryStatement}', [MypageSalaryStatementController::class, 'show'])
            ->name('salary-statements.show');
        Route::post('/salary-statements/{salaryStatement}/approve', [MypageSalaryStatementController::class, 'approve'])
            ->name('salary-statements.approve');
        Route::get('/salary-statements/{salaryStatement}/pdf', [MypageSalaryStatementController::class, 'generatePdf'])
            ->name('salary-statements.pdf');
        
        // 통지 관련 라우트
        Route::resource('notifications', MypageNotificationController::class)->only(['index', 'show']);
        Route::post('/notifications/{notification}/response', [MypageNotificationController::class, 'storeResponse'])
            ->name('notifications.response.store');

        // 신청서 관련 라우트
        Route::get('/requests', [MypageRequestController::class, 'index'])
            ->name('requests.index');
        Route::get('/requests/create', [MypageRequestController::class, 'create'])
            ->name('requests.create');
        Route::post('/requests', [MypageRequestController::class, 'store'])
            ->name('requests.store');
        Route::get('/requests/{request}', [MypageRequestController::class, 'show'])
            ->name('requests.show');
        Route::put('/requests/{request}', [MypageRequestController::class, 'update'])
            ->name('requests.update');
        Route::delete('/requests/{request}', [MypageRequestController::class, 'destroy'])
            ->name('requests.destroy');
        Route::get('/requests/files/{file}/download', [MypageRequestController::class, 'downloadFile'])
            ->name('requests.download-file');
        // 재직증명서 다운로드 라우트 추가
        Route::get('/requests/{request}/certificate', [MypageRequestController::class, 'downloadCertificate'])
            ->name('requests.download-certificate');
    });

    // Reward 라우트 - 모든 사용자가 조회 가능
    Route::resource('rewards', RewardController::class)->middleware('admin')->except(['index', 'show']);
    Route::resource('rewards', RewardController::class)->only(['index', 'show']);

    // 내규관리 라우트
    Route::prefix('laws')->name('laws.')->group(function () {
        // 일반 사용자도 접근 가능한 라우트
        Route::get('/', [LawController::class, 'index'])->name('index');
        
        // 관리자만 접근 가능한 라우트들
        Route::middleware('admin')->group(function () {
            Route::get('/create', [LawController::class, 'create'])->name('create');
            Route::post('/', [LawController::class, 'store'])->name('store');
        });

        // show는 마지막에 배치 (와일드카드 라우트)
        Route::get('/{law}', [LawController::class, 'show'])->name('show');
        
        // 관리자만 접근 가능한 와일드카드 라우트들
        Route::middleware('admin')->group(function () {
            Route::get('/{law}/edit', [LawController::class, 'edit'])->name('edit');
            Route::put('/{law}', [LawController::class, 'update'])->name('update');
            Route::delete('/{law}', [LawController::class, 'destroy'])->name('destroy');
        });
    });

    // Transfer 관련 라우트 그룹화
    Route::prefix('transfers')->name('transfers.')->middleware(['auth'])->group(function () {
        // 기본 리소스 라우트
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
        
        // 엑셀 다운로드 라우트 (와일드카드 라우트보다 앞에 배치)
        Route::get('/download-excel', [TransferController::class, 'downloadExcel'])
            ->name('download-excel');
        
        // 와일드카드 라우트들
        Route::get('/{transfer}', [TransferController::class, 'show'])->name('show');
        Route::get('/{transfer}/edit', [TransferController::class, 'edit'])->name('edit');
        Route::put('/{transfer}', [TransferController::class, 'update'])->name('update');
        Route::delete('/{transfer}', [TransferController::class, 'destroy'])->name('destroy');
        
        // 승인 상태 업데이트 라우트 바로 위에 추가
        Route::get('/{transfer}/deposit-history', [TransferController::class, 'getDepositHistory'])
            ->name('deposit-history');
        
        // 승인 상태 업데이트
        Route::post('/{transfer}/approve', [TransferController::class, 'updateApprovalStatus'])
            ->name('approve');
        
        // 납부 상태 업데이트
        Route::post('/{transfer}/payment-status', [TransferController::class, 'updatePaymentStatus'])
            ->name('payment-status');
        
        // 파일 관련 라우트
        Route::get('/files/{file}/download', [TransferFileController::class, 'download'])
            ->name('files.download');
        Route::delete('/files/{file}', [TransferFileController::class, 'destroy'])
            ->name('files.destroy');

        // 모든 파일 다운로드 라우트
        Route::get('/{transfer}/download-files', [TransferFileController::class, 'downloadAll'])
            ->name('download-files');

        // Transfer 관련 라우트 그룹 내에 추가
        Route::post('/{transfer}/update-error-code', [TransferController::class, 'updateErrorCode'])
            ->name('update-error-code');
    });

    // auth 미들웨어 그룹 내부에 추가
    Route::prefix('work-status')->name('work-status.')->group(function () {
        Route::get('/', [WorkStatusController::class, 'index'])->name('index');
    });

    // 테스트용 임시 라우트
    Route::get('/test-notifications', function () {
        $service = new \App\Services\NotificationService();
        $counts = $service->getNotificationCounts(auth()->id());
        $total = $service->getTotalCount(auth()->id());
        
        return [
            'counts' => $counts,
            'total' => $total
        ];
    });

    // 업무일지
    Route::get('/work-logs', [WorkLogController::class, 'index'])->name('work-logs.index');
    Route::post('/work-logs/tasks', [WorkLogController::class, 'addTask'])->name('work-logs.add-task');
    Route::delete('/work-logs/tasks/{id}', [WorkLogController::class, 'deleteTask'])->name('work-logs.delete-task');
    Route::put('/work-logs/tasks/{id}', [WorkLogController::class, 'updateTask'])->name('work-logs.update-task');
    Route::get('/work-logs/import-tasks-modal', [WorkLogController::class, 'showImportTasksModal'])->name('work-logs.import-tasks-modal');
    Route::post('/work-logs/import-tasks', [WorkLogController::class, 'importTasks'])->name('work-logs.import-tasks');

    // 업무 리스트
    Route::get('/task-lists', [TaskListController::class, 'index'])->name('task-lists.index');
    Route::post('/task-lists', [TaskListController::class, 'store'])->name('task-lists.store');
    Route::put('/task-lists/{id}', [TaskListController::class, 'update'])->name('task-lists.update');
    Route::delete('/task-lists/{id}', [TaskListController::class, 'destroy'])->name('task-lists.destroy');
    Route::patch('/task-lists/{id}/status', [TaskListController::class, 'updateStatus'])->name('task-lists.update-status');
    
    // 하위 업무 라우트 추가
    Route::post('/task-lists/{id}/subtasks', [TaskListController::class, 'addSubtask'])->name('task-lists.subtasks.add');
    Route::put('/task-lists/subtasks/{id}', [TaskListController::class, 'updateSubtask'])->name('task-lists.subtasks.update');
    Route::delete('/task-lists/subtasks/{id}', [TaskListController::class, 'deleteSubtask'])->name('task-lists.subtasks.delete');
    Route::patch('/task-lists/subtasks/{id}/toggle', [TaskListController::class, 'toggleSubtaskCompletion'])->name('task-lists.subtasks.toggle');
    
    // 템플릿 라우트 추가
    Route::get('/task-lists/partials/new-task-template', [TaskListController::class, 'getNewTaskTemplate'])->name('task-lists.new-task-template');

    // 수임료 캘린더 라우트
    Route::group(['prefix' => 'fee-calendar'], function () {
        Route::get('/index', [LawyerFeeCalendarController::class, 'index'])->name('fee-calendar.index');
        Route::get('/monthly', [LawyerFeeCalendarController::class, 'getMonthlyData']);
        Route::get('/weekly', [LawyerFeeCalendarController::class, 'getWeeklyData']);
        Route::get('/daily', [LawyerFeeCalendarController::class, 'getDailyData']);
        Route::get('/daily-details', [LawyerFeeCalendarController::class, 'getDailyDetails']);
        Route::get('/overdue', [LawyerFeeCalendarController::class, 'getOverdueData']);
        
        // 수임료 상태 변경 API
        Route::post('/mark-as-completed/{id}', [LawyerFeeCalendarController::class, 'markAsCompleted']);
        Route::post('/mark-as-pending/{id}', [LawyerFeeCalendarController::class, 'markAsPending']);
        Route::post('/batch-mark-as-completed', [LawyerFeeCalendarController::class, 'batchMarkAsCompleted']);
        Route::post('/process-payment-match', [LawyerFeeCalendarController::class, 'processPaymentMatch']);
        
        // 입금 정보 검색 API
        Route::post('/search-matching-payments/{id}', [LawyerFeeCalendarController::class, 'searchMatchingPayments']);
        
        // 멤버 목록 조회 API
        Route::get('/members', [LawyerFeeCalendarController::class, 'getMembers']);
        
        // 사용자 정보 및 기본 필터 설정 API 추가
        Route::get('/user-data', [LawyerFeeCalendarController::class, 'getCurrentUserData']);
    });

    // 고객별 수임료 관리 라우트
    Route::prefix('fee-client')->name('fee-client.')->middleware(['auth'])->group(function () {
        Route::get('/', [LawyerFeeClientController::class, 'index'])->name('index');
        Route::get('/clients', [LawyerFeeClientController::class, 'getClientsList'])->name('clients.list');
        Route::get('/clients/{case_idx}', [LawyerFeeClientController::class, 'getClientDetail'])->name('clients.detail');
        Route::post('/payments/{id}/update-status', [LawyerFeeClientController::class, 'updatePaymentStatus']);
        Route::post('/payments/{id}/update-memo', [LawyerFeeClientController::class, 'updatePaymentMemo']);
        Route::post('/documents/{case_idx}/update-request', [LawyerFeeClientController::class, 'updateDocumentRequest']);
        Route::post('/contract/{case_idx}/update-status', [LawyerFeeClientController::class, 'updateContractStatus']);
        Route::get('/fix-double-encoded-details', [LawyerFeeClientController::class, 'fixDoubleEncodedDetails']);
        Route::get('/members', [LawyerFeeClientController::class, 'getMembersList']);
        Route::get('/user-data', [LawyerFeeClientController::class, 'getCurrentUserData']);
    });

    // 보정서배당 관리 (새로운 UI)
    Route::prefix('correction-manager')->middleware(['auth'])->group(function() {
        Route::get('/', [CorrectionManagerController::class, 'index'])->name('correction-manager.index');
        Route::get('/data', [CorrectionManagerController::class, 'getData'])->name('correction-manager.data');
        Route::post('/', [CorrectionManagerController::class, 'store'])->name('correction-manager.store');
        Route::get('/search-case', [CorrectionManagerController::class, 'searchCase'])->name('correction-manager.search-case');
        Route::put('/{id}', [CorrectionManagerController::class, 'update'])->name('correction-manager.update');
        Route::delete('/{id}', [CorrectionManagerController::class, 'destroy'])->name('correction-manager.destroy');
        Route::get('/download/{path}', [CorrectionManagerController::class, 'download'])->name('correction-manager.download');
        Route::get('/chart-data', [CorrectionManagerController::class, 'getChartData'])->name('correction-manager.chart-data');
    });

    // PDF 파일 다운로드 페이지
    Route::prefix('file-download')->middleware(['auth'])->group(function() {
        Route::get('/', [FileDownloadController::class, 'index'])->name('file-download.index');
        Route::get('/list', [FileDownloadController::class, 'getFiles'])->name('file-download.list');
        Route::get('/calendar-data', [FileDownloadController::class, 'getCalendarData'])->name('file-download.calendar-data');
        Route::get('/handlers', [FileDownloadController::class, 'getHandlers'])->name('file-download.handlers');
        Route::get('/{path}', [FileDownloadController::class, 'download'])->name('file-download.download');
    });

    // 파일 다운로드
    Route::get('/file_download', [FileDownloadController::class, 'index'])->name('file_download');
    Route::get('/file_download/get_files', [FileDownloadController::class, 'getFilesByDate'])->name('file_download.get_files');
    Route::get('/file_download/calendar_data', [FileDownloadController::class, 'getCalendarData'])->name('file_download.calendar_data');

    // 문서 편집기 라우트
    Route::get('/document-editor', [DocumentEditorController::class, 'index'])->name('document-editor.index');
    Route::get('/document-editor/files', [DocumentEditorController::class, 'getFiles'])->name('document-editor.files');
    Route::get('/document-editor/handlers', [DocumentEditorController::class, 'getHandlers'])->name('document-editor.handlers');
    Route::post('/document-editor/convert', [DocumentEditorController::class, 'convertToText'])->name('document-editor.convert');
    Route::get('/document-editor/view-pdf', [DocumentEditorController::class, 'viewPdf'])->name('document-editor.view-pdf');
});

// API 라우트 그룹
Route::group([
    'prefix' => 'api',
    'middleware' => ['api']
], function () {
    // API 로그인
    Route::post('/login', [LoginController::class, 'apiLogin'])
        ->withoutMiddleware(['web', 'csrf', 'verify_csrf_token']);
    
    // Sanctum으로 보호되는 API 엔드포인트들
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/payments', [PaymentController::class, 'store'])
            ->withoutMiddleware(['web', 'csrf', 'verify_csrf_token']);
        Route::post('/transactions', [TransactionController::class, 'store'])
            ->withoutMiddleware(['web', 'csrf', 'verify_csrf_token']);
        Route::post('/transactions2', [Transaction2Controller::class, 'store'])
            ->withoutMiddleware(['web', 'csrf', 'verify_csrf_token']);
        Route::post('/transactions3', [Transaction3Controller::class, 'store'])
            ->withoutMiddleware(['web', 'csrf', 'verify_csrf_token']);
    });
});

Route::get('/health', function () {
    return response('OK', 200);
});
