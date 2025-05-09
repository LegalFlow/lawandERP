<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DocumentEditorController extends Controller
{
    // 기본 경로 설정 (환경에 따라 다른 경로 사용)
    protected function getDocumentPath()
    {
        if (app()->environment('production')) {
            return '/home/ec2-user/rbdocs/';
        } else {
            // 로컬 환경에서는 지정된 경로 사용
            return 'C:/Users/bmh31/OneDrive/lawandERP/lawandERP/python/rb_docs/';
        }
    }
    
    /**
     * 문서 편집기 인덱스 페이지
     */
    public function index()
    {
        return view('document-editor.index');
    }
    
    /**
     * 파일 목록 가져오기
     */
    public function getFiles(Request $request)
    {
        try {
            // 파라미터
            $search = $request->input('search', '');
            $handler = $request->input('handler', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);
            
            // 파일 목록 가져오기
            $filesWithInfo = $this->getFilesList($search, $handler);
            
            // 페이지네이션 처리
            $total = count($filesWithInfo);
            $offset = ($page - 1) * $perPage;
            $files = array_slice($filesWithInfo, $offset, $perPage);
            
            return response()->json([
                'success' => true,
                'files' => $files,
                'meta' => [
                    'total' => $total, 
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'has_more' => ($offset + count($files)) < $total
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
    
    /**
     * PDF 파일을 텍스트로 변환
     */
    public function convertToText(Request $request)
    {
        try {
            $filePath = $request->input('file_path');
            if (empty($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => '파일 경로가 제공되지 않았습니다.'
                ], 400);
            }
            
            $fullPath = $this->getDocumentPath() . $filePath;
            
            // 파일 존재 확인
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '파일을 찾을 수 없습니다: ' . $fullPath
                ], 404);
            }
            
            // 파이썬 환경 확인
            $checkPython = Process::run("which python3");
            $pythonPath = trim($checkPython->output());
            
            $checkPackage = Process::run("{$pythonPath} -c 'import sys; print(sys.path)'");
            $pythonSysPath = trim($checkPackage->output());
            
            $checkPyPDF2 = Process::run("{$pythonPath} -c 'import pkg_resources; print(pkg_resources.get_distribution(\"PyPDF2\").version)'");
            $pyPDF2Version = $checkPyPDF2->successful() ? trim($checkPyPDF2->output()) : "패키지 없음: " . $checkPyPDF2->errorOutput();
            
            Log::info("Python Path: " . $pythonPath);
            Log::info("Python Sys Path: " . $pythonSysPath);
            Log::info("PyPDF2 Version: " . $pyPDF2Version);
            
            // 개발 환경에서는 더미 텍스트 반환
            if (app()->environment('local')) {
                $text = $this->getDummyText($filePath);
                
                return response()->json([
                    'success' => true,
                    'text' => $text,
                    'file_name' => basename($filePath)
                ]);
            }
            
            // 프로덕션 환경에서만 파이썬 스크립트 실행
            $pythonScript = base_path('python/pdf_to_text.py');
            
            // 상세한 디버깅 명령어 실행
            Log::info("실행될 스크립트 경로: " . $pythonScript);
            Log::info("PDF 파일 경로: " . $fullPath);
            
            $pythonCommand = $pythonPath;
            $command = "{$pythonCommand} {$pythonScript} \"{$fullPath}\" 2>&1";
            
            Log::info("실행 명령어: " . $command);
            
            $process = Process::run($command);
            
            if ($process->successful()) {
                $text = $process->output();
                Log::info("성공적으로 변환 완료. 텍스트 길이: " . strlen($text));
            } else {
                $error = $process->errorOutput();
                Log::error("PDF 변환 오류: " . $error);
                Log::error("Exit Code: " . $process->exitCode());
                
                return response()->json([
                    'success' => false,
                    'message' => 'PDF 변환 중 오류가 발생했습니다: ' . $error,
                    'details' => [
                        'python_path' => $pythonPath,
                        'pypdf2_version' => $pyPDF2Version,
                        'exit_code' => $process->exitCode()
                    ]
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'text' => $text,
                'file_name' => basename($filePath)
            ]);
        } catch (\Exception $e) {
            Log::error('PDF 변환 오류: ' . $e->getMessage());
            Log::error('스택 트레이스: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'PDF 변환 중 오류가 발생했습니다: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    
    /**
     * 담당자 목록 가져오기
     */
    public function getHandlers()
    {
        try {
            // 재직 중인 멤버 목록 가져오기
            $members = \DB::table('members')
                ->where('status', '재직')
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();
            
            // 현재 로그인한 사용자의 정보 가져오기
            $user = auth()->user();
            $defaultHandler = '';
            
            // 로그인한 사용자의 멤버 정보 확인
            $userMember = \DB::table('members')
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
    
    /**
     * 파일 목록 가져오기 (내부 함수)
     */
    private function getFilesList($search = '', $handler = '')
    {
        $directory = $this->getDocumentPath();
        
        // 실제 환경에서는 디렉토리 파일 목록을 가져옴
        if (app()->environment('production')) {
            $allFiles = File::files($directory);
        } else {
            // 개발 환경이라면 실제 디렉토리에서 파일 목록 가져오기
            try {
                if (File::exists($directory)) {
                    $allFiles = File::files($directory);
                } else {
                    // 디렉토리가 없으면 샘플 파일 리스트 사용
                    $allFiles = $this->getSampleFiles();
                    Log::warning("디렉토리가 존재하지 않습니다: {$directory}. 샘플 데이터를 사용합니다.");
                }
            } catch (\Exception $e) {
                Log::error("디렉토리 접근 오류: " . $e->getMessage());
                $allFiles = $this->getSampleFiles();
            }
        }
        
        $result = [];
        
        foreach ($allFiles as $file) {
            // 개발 환경의 File 객체 또는 샘플 문자열 처리
            if (is_string($file)) {
                $filename = $file;
                $filesize = rand(100, 2000) . 'KB'; // 샘플 크기
            } else {
                // File 객체 처리
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
                
                // 문서명 추출
                $documentName = isset($parts[4]) ? str_replace('.pdf', '', implode('_', array_slice($parts, 4))) : '';
                
                // 결과 배열에 추가
                $result[] = [
                    'filename' => $filename,
                    'path' => $filename,
                    'sentDate' => $this->formatDisplayDate($sentDate),
                    'receiptDate' => $this->formatDisplayDate($receiptDate),
                    'receiptDateRaw' => $receiptDate, // 정렬용
                    'caseNumber' => $caseNumber,
                    'handler' => $fileHandler,
                    'documentName' => $documentName,
                    'size' => $filesize,
                    'displayName' => "[{$this->formatDisplayDate($receiptDate)}] [{$caseNumber}] {$documentName}"
                ];
            }
        }
        
        // 수신일자 기준으로 정렬 (최신순)
        usort($result, function($a, $b) {
            return strcmp($b['receiptDateRaw'], $a['receiptDateRaw']);
        });
        
        return $result;
    }
    
    /**
     * 날짜 파싱 헬퍼 (2025.04.18 또는 20250418 형식을 Y-m-d로 변환)
     */
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
    
    /**
     * 날짜 표시 형식 변환 (YYYY-MM-DD → YYYY.MM.DD)
     */
    private function formatDisplayDate($dateStr)
    {
        if (empty($dateStr)) return '';
        
        $dateParts = explode('-', $dateStr);
        if (count($dateParts) !== 3) return $dateStr;
        
        return $dateParts[0] . '.' . $dateParts[1] . '.' . $dateParts[2];
    }
    
    /**
     * 파일 사이즈 포맷팅
     */
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
    
    /**
     * 테스트용 샘플 파일 목록
     */
    private function getSampleFiles()
    {
        return [
            '2025.04.18_2025개회60019_20250421_정유민_기타결정(명령).pdf',
            '2025.04.18_2025개회54630_20250421_김민지_명의변경(대위변제)_및_계좌신고서.pdf',
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
            '2025.02.25_2025개회60040_20250220_김하늘_확인서.pdf',
            '2025.04.22_2024개회1161771_20250429_김미진_보정권고.pdf',
            '2025.04.23_2025개회165158_20250428_박유은_보정명령등본.pdf'
        ];
    }
    
    /**
     * 개발 환경용 더미 텍스트 생성
     */
    private function getDummyText($filename)
    {
        // 파일명에서 문서 종류 추출
        $parts = explode('_', $filename);
        $documentType = end($parts);
        $documentType = str_replace('.pdf', '', $documentType);
        
        // 문서 종류에 따라 다른 더미 텍스트 반환
        if (stripos($documentType, '보정권고') !== false) {
            return "
아래 기재와 같이 보정을 요청드립니다.

1. 신청인 기본정보 확인
   - 이름: 홍길동
   - 주민번호: 123456-1234567
   - 주소: 서울특별시 강남구 테헤란로 123

2. 제출 서류 목록
   - 소득증빙서류(원천징수영수증)
   - 재산목록
   - 채무자목록
   - 진술서

3. 보정사항
   3-1. 소득증빙자료가 불충분합니다. 최근 1년간의 원천징수영수증 또는 소득금액증명원을 제출해주세요.
   3-2. 재산목록에 기재된 부동산의 등기부등본을 첨부해주세요.
   3-3. 채무자목록에 기재된 내용 중 일부 채권자의 연락처가 누락되었습니다.
   3-4. 진술서에 서명이 누락되었습니다.

4. 보정기한
   위 보정사항을 2025년 5월 10일까지 보정해주시기 바랍니다.

이상입니다.
            ";
        } else if (stripos($documentType, '보정명령등본') !== false) {
            return "
아래와 같이 보정을 명합니다.

1. 대상사건
   - 사건번호: 2025개회165158
   - 신청인: 박유은
   - 접수일자: 2025년 4월 23일

2. 보정명령 사항
   2-1. 신청서에 기재된 채무액과 첨부된 증빙서류 상의 금액이 일치하지 않습니다. 
        정확한 채무액을 확인하여 수정 제출하십시오.
   
   2-2. 다음의 필수서류가 누락되었습니다.
        - 근로소득원천징수영수증(최근 2년)
        - 가족관계증명서
        - 주민등록등본
   
   2-3. 채권자목록표 중 일부 채권자에 대한 정보가 불충분합니다.
        채권자 상호, 주소, 연락처, 채무액을 정확히 기재하십시오.

3. 보정기한
   위 사항을 2025년 5월 15일까지 보정하지 않을 경우, 
   채무자 회생 및 파산에 관한 법률 제41조 제1항에 따라 
   신청을 각하할 수 있음을 유의하시기 바랍니다.

서울회생법원
            ";
        } else {
            // 기본 더미 텍스트
            return "
이 문서는 테스트용 더미 텍스트입니다.

아래와 같이 본문이 시작됩니다.

{$documentType} 관련 주요 내용:

1. 첫 번째 항목
   - 세부사항 1
   - 세부사항 2
   - 세부사항 3

2. 두 번째 항목
   2-1. 세부내용 A
   2-2. 세부내용 B
   2-3. 세부내용 C

3. 세 번째 항목
   * 중요사항 1
   * 중요사항 2
   * 중요사항 3

본 문서에 기재된 사항은 샘플이며, 실제 내용과 무관합니다.
등본입니다.
            ";
        }
    }

    /**
     * PDF 파일 보기
     */
    public function viewPdf(Request $request)
    {
        $filePath = $request->input('file_path');
        
        // 파일 경로가 없거나 유효하지 않은 경우
        if (empty($filePath)) {
            return response()->json(['error' => '파일 경로가 제공되지 않았습니다.'], 400);
        }
        
        // 문서 저장 경로
        $fullPath = $this->getDocumentPath() . $filePath;
        
        // 파일이 존재하는지 확인
        if (!File::exists($fullPath)) {
            return response()->json(['error' => '파일을 찾을 수 없습니다: ' . $filePath], 404);
        }
        
        // PDF 파일 반환
        return response()->file($fullPath);
    }
} 