<?php

namespace App\Http\Controllers\MyPage;

use App\Http\Controllers\Controller;
use App\Models\SalaryStatement;
use Illuminate\Http\Request;
use PDF;
use Carbon\Carbon;

class SalaryStatementController extends Controller
{
    /**
     * 급여명세서 목록 조회
     */
    public function index(Request $request)
    {
        // 현재 날짜로부터 32일 전 날짜 계산
        $thirtyTwoDaysAgo = now()->subDays(32);
        
        $statements = SalaryStatement::where('user_id', auth()->id())
            ->where('statement_date', '<=', $thirtyTwoDaysAgo)
            ->orderBy('statement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $hasStandard = auth()->user()->member->standard > 0;
        
        // 선택된 연도와 분기 가져오기 (기본값은 현재 연도와 분기)
        $selectedYear = $request->input('year');
        $selectedQuarter = $request->input('quarter');
        
        // availableQuarters 생성 (관리자 페이지와 동일한 로직)
        $availableQuarters = [];
        $currentYear = now()->year;
        $currentQuarter = ceil(now()->month / 3);
        
        // 2025년 1분기부터 시작
        $startYear = 2025;
        $startQuarter = 1;
        
        // 현재 연도/분기부터 시작해서 2025년 1분기까지 거슬러 올라감
        for ($y = $currentYear; $y >= $startYear; $y--) {
            $maxQuarter = ($y == $currentYear) ? $currentQuarter : 4;
            $minQuarter = ($y == $startYear) ? $startQuarter : 1;
            
            for ($q = $maxQuarter; $q >= $minQuarter; $q--) {
                $availableQuarters[] = [
                    'year' => $y,
                    'quarter' => $q,
                    'name' => "{$y}년 {$q}분기"
                ];
            }
        }
        
        $statistics = $hasStandard 
            ? $this->calculateQuarterlyStatistics($selectedYear, $selectedQuarter) 
            : null;

        return view('mypage.salary-statements.index', compact(
            'statements', 
            'statistics', 
            'hasStandard', 
            'availableQuarters',
            'selectedYear',
            'selectedQuarter'
        ));
    }

    /**
     * 급여명세서 상세 조회
     */
    public function show(SalaryStatement $salaryStatement)
    {
        // 본인의 급여명세서만 조회 가능하도록 체크
        if ($salaryStatement->user_id !== auth()->id()) {
            abort(403);
        }

        return view('mypage.salary-statements.show', compact('salaryStatement'));
    }

    /**
     * 급여명세서 승인
     */
    public function approve(SalaryStatement $salaryStatement)
    {
        // 본인의 급여명세서만 승인 가능하도록 체크
        if ($salaryStatement->user_id !== auth()->id()) {
            abort(403);
        }

        $salaryStatement->update([
            'approved_at' => now(),
            'approved_by' => auth()->id()
        ]);

        return redirect()
            ->back()
            ->with('success', '급여명세서가 승인되었습니다.');
    }

    /**
     * 급여명세서 PDF 생성
     */
    public function generatePdf(SalaryStatement $salaryStatement)
    {
        // 본인의 급여명세서만 다운로드 가능하도록 체크
        if ($salaryStatement->user_id !== auth()->id()) {
            abort(403);
        }

        $salaryStatement->load(['user']);
        
        // PDF 설정 추가
        PDF::setOptions([
            'defaultFont' => 'NanumGothic',
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultPaperSize' => 'a4',
            'defaultEncoding' => 'UTF-8'
        ]);
        
        $pdf = PDF::loadView('admin.salary-statements.pdf', compact('salaryStatement'));
        
        $filename = sprintf(
            '급여명세서_%s_%s.pdf',
            $salaryStatement->statement_date->format('Y-m'),
            $salaryStatement->user ? $salaryStatement->user->name : $salaryStatement->name
        );
        
        return $pdf->download($filename);
    }

    private function calculateQuarterlyStatistics($requestYear = null, $requestQuarter = null)
    {
        // 현재 날짜 (시간 제외)
        $now = now()->startOfDay();
        
        // 요청된 연도와 분기가 있으면 해당 분기의 시작일과 종료일 계산
        if ($requestYear && $requestQuarter) {
            // 분기 시작일 계산
            $quarterStart = Carbon::createFromDate($requestYear, ($requestQuarter - 1) * 3 + 1, 1)->startOfDay();
            // 분기 종료일 계산
            $quarterEnd = $quarterStart->copy()->endOfQuarter();
            
            // 분기가 완전히 지났는지 확인
            $isCompletedQuarter = $quarterEnd->lt($now);
            
            // 과거 분기의 경우 분기 마지막 날로 설정, 현재 분기의 경우 오늘로 설정
            if (!$isCompletedQuarter && $quarterEnd->gt($now)) {
                $quarterEnd = $now;
            }
        } else {
            // 요청된 분기 정보가 없으면 현재 분기 사용
            $quarterStart = $now->copy()->startOfQuarter()->startOfDay();
            $quarterEnd = $now;
            $isCompletedQuarter = false;
        }
        
        // 현재 분기의 총 일수 계산
        $totalDays = $quarterStart->daysInQuarter;
        
        // 분기가 완료되었으면 경과일을 총 일수와 동일하게 설정
        if (isset($isCompletedQuarter) && $isCompletedQuarter) {
            $elapsedDays = $totalDays;
        } else {
            // 분기 시작일부터 오늘(또는 종료일)까지의 경과일수
            $elapsedDays = $quarterStart->diffInDays($quarterEnd) + 1; // 시작일 포함
        }
        
        // 경과율 계산 - 완료된 분기는 100%로 설정
        $elapsedRate = isset($isCompletedQuarter) && $isCompletedQuarter 
            ? 100 
            : round(($elapsedDays / $totalDays) * 100, 2);

        // 기준 매출액 조회
        $standardAmount = auth()->user()->member->standard;
        $quarterStandard = $standardAmount * 3;

        // 현재 매출액 조회
        $currentAmount = \DB::table('service_sales')
            ->where('manager', auth()->user()->name)
            ->where('account', '서비스매출')
            ->whereBetween('date', [
                $quarterStart->format('Y-m-d'),
                $quarterEnd->format('Y-m-d')
            ])
            ->sum('amount');

        // 분기별 기준액 계산
        $quarterlyStandard = $standardAmount * 3;
        
        // 분기가 완료된 경우
        if (isset($isCompletedQuarter) && $isCompletedQuarter) {
            // 이미 완료된 분기는 일일 평균 계산 불필요, 실제 금액을 그대로 사용
            $estimatedAmount = $currentAmount;
            $currentStandard = $quarterlyStandard; // 분기 기준액과 동일하게 설정
        } else {
            // 진행 중인 분기는 일일 평균으로 예상 매출액 계산
            $dailyAverage = $elapsedDays > 0 ? ($currentAmount / $elapsedDays) : 0;
            $estimatedAmount = round($dailyAverage * $totalDays);
            
            // 현재 기준 매출액 계산 (분기 기준 × 경과율)
            $currentStandard = round($quarterStandard * ($elapsedDays / $totalDays));
        }

        // 기준 구간 계산
        $standardPlus20 = $quarterStandard * 1.2;
        $standardPlus10 = $quarterStandard * 1.1;
        $standardMinus10 = $quarterStandard * 0.9;
        $standardMinus20 = $quarterStandard * 0.8;

        // 예상 성과금 계산
        $expectedBonus = $this->calculateExpectedBonus($estimatedAmount, $quarterStandard);

        // 보상/제재 결정
        $reward = $this->determineRewardOrPenalty($estimatedAmount, $quarterStandard);

        // 현재 구간 결정
        $currentRange = $this->determineCurrentRange(
            $estimatedAmount, 
            $standardPlus20, 
            $standardPlus10, 
            $standardMinus10, 
            $standardMinus20
        );

        // 분기 정보 계산
        $quarterNumber = $requestQuarter ?? ceil($quarterStart->month / 3);
        $yearNumber = $requestYear ?? $quarterStart->year;

        return [
            'elapsedDays' => $elapsedDays,
            'totalDays' => $totalDays,
            'elapsedRate' => $elapsedRate,
            'monthlyStandard' => $standardAmount,
            'quarterlyStandard' => $quarterStandard,
            'currentStandard' => $currentStandard,
            'standardPlus20' => $standardPlus20,
            'standardPlus10' => $standardPlus10,
            'standardMinus10' => $standardMinus10,
            'standardMinus20' => $standardMinus20,
            'currentAmount' => $currentAmount,
            'estimatedAmount' => $estimatedAmount,
            'expectedBonus' => $expectedBonus,
            'reward' => $reward,
            'currentRange' => $currentRange,
            'quarter' => $quarterNumber,
            'year' => $yearNumber,
            'date' => $quarterEnd->format('Y년 m월 d일'),
            'isCompletedQuarter' => $isCompletedQuarter ?? false
        ];
    }

    private function calculateExpectedBonus($estimated, $standard)
    {
        if ($estimated <= $standard) {
            return 0;
        }

        $excess = $estimated - $standard;
        $standardTenPercent = $standard * 0.1;
        $bonus = 0;

        // 구간별 성과급 계산
        $ranges = [
            [0, 10, 0.1],
            [10, 20, 0.2],
            [20, 30, 0.3],
            [30, 40, 0.4],
            [40, PHP_FLOAT_MAX, 0.5]
        ];

        foreach ($ranges as $range) {
            $rangeExcess = min(
                max(0, ($excess - ($standard * $range[0] / 100))),
                $standardTenPercent
            );
            $bonus += floor($rangeExcess * $range[2]);
        }

        return floor($bonus);
    }

    private function determineRewardOrPenalty($estimated, $standard)
    {
        $rate = ($estimated - $standard) / $standard * 100;

        if ($rate >= 20) return '연차 1일 부여';
        if ($rate >= 10) return '반차 1일 부여';
        if ($rate <= -20) return '주4일 근무 제외';
        if ($rate <= -10) return '재택근무 제외';
        return '-';
    }

    private function determineCurrentRange($estimated, $plus20, $plus10, $minus10, $minus20)
    {
        if ($estimated >= $plus20) return 'plus20';
        if ($estimated >= $plus10) return 'plus10';
        if ($estimated <= $minus20) return 'minus20';
        if ($estimated <= $minus10) return 'minus10';
        return 'normal';
    }

    /**
     * 연말정산 파일 다운로드
     */
    public function downloadTaxFile(Request $request)
    {
        // 세션 재생성만 수행
        $request->session()->regenerate();
        
        $year = $request->input('year', date('Y', strtotime('-1 year'))); // 기본값은 작년
        
        // 파일 경로 설정
        $folderPath = "/var/www/html/lawandERP/resources/views/mypage/salary-statements/{$year}_tax";
        
        // 폴더가 존재하는지 확인
        if (!file_exists($folderPath) || !is_dir($folderPath)) {
            return redirect()
                ->back()
                ->with('error', "{$year}년도 연말정산 파일이 준비되지 않았습니다.");
        }
        
        // 현재 로그인한 사용자 이름 가져오기 (DB에서 직접 조회)
        $user = \App\Models\User::find(auth()->id());
        $userName = $user ? $user->name : auth()->user()->name;
        
        // 디버깅 정보 저장 (주석 처리)
        // session()->flash('debug_info', "검색 중인 사용자 이름: {$userName}, 사용자 ID: " . auth()->id());
        
        // 디렉토리 내 모든 파일 스캔
        $files = scandir($folderPath);
        $userFile = null;
        // $allFiles = []; // 디버깅용 파일 목록 (주석 처리)
        
        // 사용자 이름이 포함된 파일 찾기
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            // $allFiles[] = $file; // 디버깅용 파일 목록에 추가 (주석 처리)
            
            // 파일명에 사용자 이름이 정확히 포함되어 있는지 확인 (정규식 사용)
            if (preg_match('/\d+_\d+_(' . preg_quote($userName, '/') . ')(\(.*\))?.pdf/i', $file)) {
                $userFile = $file;
                break;
            }
        }
        
        // 파일을 찾지 못한 경우
        if (!$userFile) {
            // 디버깅 정보 추가 (주석 처리)
            // session()->flash('debug_files', $allFiles);
            
            return redirect()
                ->back()
                ->with('error', "{$year}년도 연말정산 파일을 찾을 수 없습니다. 관리자에게 문의하세요.");
        }
        
        // 디버깅 정보 추가 (주석 처리)
        // session()->flash('found_file', $userFile);
        
        // 파일 다운로드 (캐시 비활성화 헤더는 미리 설정)
        $filePath = $folderPath . '/' . $userFile;
        
        // 헤더를 미리 설정
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sat, 01 Jan 1990 00:00:00 GMT'
        ];
        
        return response()->download($filePath, null, $headers);
    }
}