<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FileDownloadController extends Controller
{
    // PDF 파일 목록 페이지
    public function index()
    {
        return view('file_download.index');
    }
    
    // 파일 목록 API - AJAX 호출용 
    public function getFiles(Request $request)
    {
        try {
            // 파라미터: viewMode(month/week/day/all), date, page, search
            $viewMode = $request->input('view_mode', 'week');
            $date = $request->input('date', Carbon::today()->format('Y-m-d'));
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 30);
            $search = $request->input('search', '');
            $handler = $request->input('handler', '');
            
            // 파일 목록 가져오기
            $filesWithInfo = $this->getFilesByPeriod($viewMode, $date, $search, $handler);
            
            // 페이지네이션 처리 (무한 스크롤용)
            $total = count($filesWithInfo);
            $offset = ($page - 1) * $perPage;
            $files = array_slice($filesWithInfo, $offset, $perPage);
            
            return response()->json([
                'success' => true,
                'files' => $files,
                'meta' => [
                    'total' => $total, 
                    'current_page' => (int)$page,
                    'per_page' => $perPage,
                    'has_more' => ($offset + $perPage) < $total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('파일 목록 로드 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '파일 목록을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }
    
    // 파일 다운로드 처리
    public function download($encodedPath)
    {
        try {
            $decodedPath = urldecode(base64_decode($encodedPath));
            $fullPath = '/home/ec2-user/rbdocs/' . $decodedPath;
            
            if (!file_exists($fullPath)) {
                Log::error('파일 다운로드 오류: 파일이 존재하지 않음 - ' . $fullPath);
                return response()->json(['error' => '파일을 찾을 수 없습니다.'], 404);
            }
            
            $filename = basename($fullPath);
            
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
            ]);
        } catch (\Exception $e) {
            Log::error('파일 다운로드 오류: ' . $e->getMessage());
            return response()->json(['error' => '파일 다운로드 중 오류가 발생했습니다.'], 500);
        }
    }
    
    // 기간별 파일 필터링 (private helper method)
    private function getFilesByPeriod($viewMode, $date, $search = '', $handler = '')
    {
        // Carbon으로 날짜 파싱
        $targetDate = Carbon::parse($date);
        $directory = '/home/ec2-user/rbdocs/';
        
        // 실제 환경에서는 디렉토리 파일 목록을 가져옴
        if (app()->environment('production')) {
            $allFiles = File::files($directory);
        } else {
            // 개발 환경이라면 샘플 파일 리스트 사용
            $allFiles = $this->getSampleFiles();
        }
        
        $result = [];
        
        // 기간에 따라 시작일과 종료일 계산
        switch ($viewMode) {
            case 'month':
                $startDate = $targetDate->copy()->startOfMonth();
                $endDate = $targetDate->copy()->endOfMonth();
                break;
                
            case 'week':
                $startDate = $targetDate->copy()->startOfWeek();
                $endDate = $targetDate->copy()->endOfWeek();
                break;
                
            case 'day':
                $startDate = $targetDate->copy()->startOfDay();
                $endDate = $targetDate->copy()->endOfDay();
                break;
                
            default: // 전체
                $startDate = null;
                $endDate = null;
                break;
        }
        
        foreach ($allFiles as $file) {
            // 개발 환경인 경우 - 문자열로 된 파일명을 처리
            if (is_string($file)) {
                $filename = $file;
                $filesize = rand(100, 2000) . 'KB'; // 샘플 크기
            } else {
                // 프로덕션 환경인 경우 - File 객체 처리
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'pdf') {
                    continue;
                }
                $filename = $file->getFilename();
                $filesize = $this->formatFileSize($file->getSize());
            }
            
            // 검색어 필터링
            if (!empty($search) && stripos($filename, $search) === false) {
                continue;
            }
            
            // 파일명에서 정보 추출 (파일명 예시: 2025.04.18_2025개회60019_20250421_정유민_기타결정(명령).pdf)
            $parts = explode('_', $filename);
            
            if (count($parts) >= 3) {
                $sentDate = $this->parseDate($parts[0]);
                $caseNumber = $parts[1];
                $receiptDate = $this->parseDate($parts[2]);
                
                // 담당자 필터링
                $fileHandler = isset($parts[3]) ? $parts[3] : '';
                if (!empty($handler) && $fileHandler !== $handler) {
                    continue;
                }
                
                // 수신일자 기준으로 필터링
                if ($startDate && $endDate) {
                    $fileDate = Carbon::parse($receiptDate);
                    if ($fileDate < $startDate || $fileDate > $endDate) {
                        continue;
                    }
                }
                
                // 문서명 추출
                $documentName = isset($parts[4]) ? str_replace('.pdf', '', implode('_', array_slice($parts, 4))) : '';
                
                // 결과 배열에 추가
                $result[] = [
                    'filename' => $filename,
                    'path' => base64_encode(urlencode($filename)),
                    'sentDate' => $this->formatDisplayDate($sentDate),
                    'receiptDate' => $this->formatDisplayDate($receiptDate),
                    'receiptDateRaw' => $receiptDate, // 정렬용
                    'caseNumber' => $caseNumber,
                    'handler' => $fileHandler,
                    'documentName' => $documentName,
                    'size' => $filesize
                ];
            }
        }
        
        // 수신일자 기준으로 정렬 (최신순)
        usort($result, function($a, $b) {
            return strcmp($b['receiptDateRaw'], $a['receiptDateRaw']);
        });
        
        return $result;
    }
    
    // 날짜 파싱 헬퍼 (2025.04.18 또는 20250418 형식을 Y-m-d로 변환)
    private function parseDate($dateStr)
    {
        if (strpos($dateStr, '.') !== false) {
            // 2025.04.18 형식
            $parts = explode('.', $dateStr);
            return $parts[0] . '-' . $parts[1] . '-' . $parts[2];
        } else {
            // 20250418 형식
            return substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
        }
    }
    
    // 날짜 표시 형식 변환 (YYYY-MM-DD → YYYY.MM.DD)
    private function formatDisplayDate($dateStr)
    {
        if (empty($dateStr)) return '';
        
        $dateParts = explode('-', $dateStr);
        if (count($dateParts) !== 3) return $dateStr;
        
        return $dateParts[0] . '.' . $dateParts[1] . '.' . $dateParts[2];
    }
    
    // 파일 사이즈 포맷팅
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    // 테스트용 샘플 파일 목록
    private function getSampleFiles()
    {
        return [
            '2025.04.18_2025개회60019_20250421_정유민_기타결정(명령).pdf',
            '2025.04.18_2025개회54630_20250421_김민지_명의변경(대위변제)_및_계좌신고서(25.04.17.자).pdf',
            '2025.04.19_2025개회60020_20250422_박서준_채권양도통지서.pdf',
            '2025.04.19_2025개회60021_20250422_이지원_소송위임장.pdf',
            '2025.04.20_2025개회60022_20250423_최현우_상속재산분할협의서.pdf',
            '2025.04.20_2025개회60023_20250423_강민서_의견서.pdf',
            '2025.04.20_2025개회60024_20250423_조은지_소장.pdf',
            '2025.04.21_2025개회60025_20250424_한지민_준비서면.pdf',
            '2025.04.21_2025개회60026_20250424_윤성준_가압류신청서.pdf',
            '2025.04.21_2025개회60027_20250424_서예진_답변서.pdf',
            '2025.04.21_2025개회60028_20250424_장현우_청구취지변경신청서.pdf',
            '2025.04.22_2025개회60029_20250425_임지원_이의신청서.pdf',
            '2025.04.22_2025개회60030_20250418_김준호_소송위임장.pdf',
            '2025.04.15_2025개회60031_20250411_이수진_답변서.pdf',
            '2025.04.10_2025개회60032_20250405_정재원_준비서면.pdf',
            '2025.03.30_2025개회60033_20250325_송민석_소장.pdf',
            '2025.03.25_2025개회60034_20250320_강지혜_의견서.pdf',
            '2025.03.20_2025개회60035_20250315_임현우_가압류신청서.pdf',
            '2025.03.15_2025개회60036_20250310_김서영_청구취지변경신청서.pdf',
            '2025.03.10_2025개회60037_20250305_이준호_상속포기신고서.pdf',
            '2025.03.05_2025개회60038_20250301_최지은_가처분신청서.pdf',
            '2025.03.01_2025개회60039_20250225_박준혁_준비서면.pdf',
            '2025.02.25_2025개회60040_20250220_김하늘_확인서.pdf'
        ];
    }

    /**
     * 파일 목록을 JSON 형식으로 반환
     */
    public function getFileList(Request $request)
    {
        // ... existing code ...
    }

    /**
     * 캘린더 데이터를 JSON 형식으로 반환
     */
    public function getCalendarData(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $search = $request->input('search', '');
            $handler = $request->input('handler', '');

            // 샘플 파일 목록을 사용하여 캘린더 데이터 생성
            $files = $this->getFilesByPeriod('custom', null, $search, $handler);
            
            // 날짜별로 파일 수 그룹화
            $calendarData = [];
            foreach ($files as $file) {
                $receiptDate = str_replace('.', '-', $file['receiptDateRaw']);
                
                // 날짜 범위 내에 있는지 확인
                if ($receiptDate >= $startDate && $receiptDate <= $endDate) {
                    if (!isset($calendarData[$receiptDate])) {
                        $calendarData[$receiptDate] = 0;
                    }
                    $calendarData[$receiptDate]++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $calendarData
            ]);
        } catch (\Exception $e) {
            Log::error('캘린더 데이터 로드 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 담당자 목록을 가져오는 API
     */
    public function getHandlers()
    {
        try {
            // 재직 중인 멤버 목록 가져오기
            $members = DB::table('members')
                ->where('status', '재직')
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();
            
            // 현재 로그인한 사용자의 정보 가져오기
            $user = auth()->user();
            $defaultHandler = '';
            
            // 로그인한 사용자의 멤버 정보 확인
            $userMember = DB::table('members')
                ->join('users', 'members.name', '=', 'users.name')
                ->where('users.id', $user->id)
                ->select('members.task', 'members.name')
                ->first();
            
            // 사건관리팀인 경우 해당 사용자의 이름을 기본값으로 설정
            if ($userMember && $userMember->task === '사건관리팀') {
                $defaultHandler = $userMember->name;
            }
            
            return response()->json([
                'success' => true,
                'handlers' => $members,
                'defaultHandler' => $defaultHandler
            ]);
        } catch (\Exception $e) {
            Log::error('담당자 목록 로드 오류: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '담당자 목록을 불러오는 중 오류가 발생했습니다.'
            ], 500);
        }
    }
} 