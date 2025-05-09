<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SocialInsuranceController extends Controller
{
    /**
     * 사대보험 업로드 페이지를 표시합니다.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = SocialInsurance::query();
        
        // 년월로 필터링
        if ($request->filled('statement_date')) {
            $query->whereMonth('statement_date', date('m', strtotime($request->statement_date)))
                  ->whereYear('statement_date', date('Y', strtotime($request->statement_date)));
        }
        
        // 이름으로 검색
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // 주민번호로 검색
        if ($request->filled('resident_id')) {
            $query->where('resident_id', 'like', '%' . $request->resident_id . '%');
        }
        
        $socialInsurances = $query->orderBy('statement_date', 'desc')
                                  ->orderBy('id', 'asc')  // id 기준 정렬 추가
                                  ->paginate(10);
        
        return view('admin.social-insurances.index', compact('socialInsurances'));
    }
    
    /**
     * 엑셀 파일 업로드를 처리합니다.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function upload(Request $request)
    {
        // 직접 로그 파일에 기록
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 다중 업로드 시작\n", FILE_APPEND);
        
        try {
            // 다중 파일 유효성 검사 규칙 수정
            $request->validate([
                'files.*' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain',
                'statement_date' => 'required|date_format:Y-m'
            ]);
            
            $statementDate = $request->statement_date;
            $totalProcessed = 0;
            $errors = [];
            
            // 파일이 없는 경우 체크
            if (!$request->hasFile('files')) {
                return redirect()->route('admin.social-insurances.index')
                             ->with('error', '업로드할 파일이 없습니다.');
            }
            
            $files = $request->file('files');
            
            // 최대 10개 파일로 제한
            if (count($files) > 10) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 수 제한 초과: " . count($files) . "개\n", FILE_APPEND);
                return redirect()->route('admin.social-insurances.index')
                             ->with('error', '한 번에 최대 10개 파일만 업로드할 수 있습니다.');
            }
            
            // 파일을 순차적으로 처리
            foreach ($files as $file) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 처리 시작: " . $file->getClientOriginalName() . "\n", FILE_APPEND);
                
                // 각 파일의 유효성 검사
                if (!$file->isValid()) {
                    $errors[] = $file->getClientOriginalName() . ' 파일이 유효하지 않습니다.';
                    continue;
                }
                
                $fileName = $file->getClientOriginalName();
                
                // 디버깅: 파일 정보 로깅
                $logMessage = date('Y-m-d H:i:s') . " - 파일 정보: {$fileName}, 크기: {$file->getSize()}, 타입: {$file->getMimeType()}, 날짜: {$statementDate}\n";
                file_put_contents($logFile, $logMessage, FILE_APPEND);
                
                Log::emergency('업로드된 파일 정보', [
                    'name' => $fileName,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'statement_date' => $statementDate
                ]);
                
                // 파일 유형 구분
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - getFileType 호출 전\n", FILE_APPEND);
                $fileType = $this->getFileType($fileName);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 유형 결정됨: {$fileType}\n", FILE_APPEND);
                
                Log::emergency('파일 유형 결정', ['type' => $fileType, 'fileName' => $fileName]);
                
                // 파일 유형에 따라 적절한 처리 메서드 호출
                $result = null;
                
                switch ($fileType) {
                    case 'Gungang':
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험 처리 시작\n", FILE_APPEND);
                        $result = $this->processHealthInsurance($file, $statementDate);
                        break;
                        
                    case 'Yeonkum':
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 처리 시작\n", FILE_APPEND);
                        $result = $this->processPensionInsurance($file, $statementDate);
                        break;
                        
                    case 'Goyong':
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 처리 시작\n", FILE_APPEND);
                        $result = $this->processEmploymentInsurance($file, $statementDate);
                        break;
                        
                    default:
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 기본값으로 건강보험 처리 시작\n", FILE_APPEND);
                        $result = $this->processHealthInsurance($file, $statementDate);
                        break;
                }
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 처리 결과: " . json_encode($result) . "\n", FILE_APPEND);
                
                if ($result['error']) {
                    $errors[] = $fileName . ': ' . $result['error'];
                } else {
                    $totalProcessed += $result['processed'];
                }
            }
            
            // 결과 메시지 생성
            if ($totalProcessed > 0) {
                $successMessage = $totalProcessed . '개의 데이터가 성공적으로 처리되었습니다.';
                if (!empty($errors)) {
                    $successMessage .= ' 일부 파일에 오류가 있었습니다: ' . implode(', ', $errors);
                }
                return redirect()->route('admin.social-insurances.index')
                                 ->with('success', $successMessage);
            } else {
                return redirect()->route('admin.social-insurances.index')
                                 ->with('error', '처리된 데이터가 없습니다. ' . implode(', ', $errors));
            }
            
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 예외 발생: {$errorMsg}, 파일: {$errorFile}, 라인: {$errorLine}\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 스택 트레이스: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            Log::emergency('사대보험 업로드 오류: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.social-insurances.index')
                             ->with('error', '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
    
    /**
     * 파일명을 기준으로 파일 유형을 결정합니다.
     *
     * @param  string  $fileName
     * @return string
     */
    private function getFileType($fileName)
    {
        $fileNameLower = strtolower($fileName);
        $logFile = storage_path('logs/social_insurance_debug.log');
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일명 분석 시작: {$fileName} (소문자: {$fileNameLower})\n", FILE_APPEND);
        
        // 우선순위가 높은 특정 파일명 직접 매핑
        $directMapping = [
            'nhisgunganglist' => 'Gungang', // 예시: 특정 파일명 패턴
            'nhisyeonkumlist' => 'Yeonkum',  // 국민연금 파일
            'nhisgoyonglist' => 'Goyong'   // 고용보험 파일
        ];
        
        foreach ($directMapping as $filePattern => $mappedType) {
            if (strpos($fileNameLower, $filePattern) !== false) {
                $message = "특정 파일명 직접 매핑 성공: 파일명={$fileNameLower}, 매핑타입={$mappedType}";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
                Log::emergency('특정 파일명 직접 매핑', ['fileName' => $fileNameLower, 'mappedType' => $mappedType]);
                return $mappedType;
            }
        }
        
        // 키워드 기반 매핑 (더 구체적인 키워드를 먼저)
        $types = [
            'gungang' => ['gungang', '건강보험', '건보', 'nhisgungang'], // 'nhis' 제거, '건강' 보다 '건강보험'이 더 명확할 수 있음
            'yeonkum' => ['yeonkum', '국민연금', '국연', 'nps'], // NPS 관련 키워드 추가 가능성
            'goyong' => ['goyong', '고용보험', '고보', 'ei'] // 고용보험 관련 키워드 추가 가능성
        ];
        
        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($fileNameLower, $keyword) !== false) {
                    $message = "키워드 매칭 성공: 파일명={$fileNameLower}, 매칭타입={$type}, 키워드={$keyword}";
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
                    Log::emergency('파일 유형 키워드 매칭', ['fileName' => $fileNameLower, 'matchedType' => $type, 'keyword' => $keyword]);
                    return ucfirst($type); // 첫 글자만 대문자로 변환
                }
            }
        }
        
        // 일반적인 키워드는 낮은 우선순위로 (선택 사항)
        $generalKeywords = [
            '건강' => 'Gungang',
            '연금' => 'Yeonkum',
            '고용' => 'Goyong'
        ];

        foreach ($generalKeywords as $keyword => $mappedType) {
            if (strpos($fileNameLower, $keyword) !== false) {
                $message = "일반 키워드 매칭 성공: 파일명={$fileNameLower}, 매핑타입={$mappedType}, 키워드={$keyword}";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
                Log::emergency('파일 유형 일반 키워드 매칭', ['fileName' => $fileNameLower, 'mappedType' => $mappedType, 'keyword' => $keyword]);
                return $mappedType;
            }
        }
        
        $message = "매칭되는 파일 유형 없음: 파일명={$fileNameLower}, 기본타입=Unknown";
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - {$message}\n", FILE_APPEND);
        Log::emergency('매칭되는 파일 유형 없음', ['fileName' => $fileNameLower]);
        return 'Unknown';
    }
    
    /**
     * 건강보험 및 장기요양 데이터를 처리합니다.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $statementDate
     * @return array
     */
    private function processHealthInsurance($file, $statementDate)
    {
        $count = 0;
        $error = null;
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - processHealthInsurance 시작\n", FILE_APPEND);
        
        try {
            // 전체 날짜 형식 지정 (년월일까지)
            $fullDate = $statementDate . '-01';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험 처리: 날짜 {$fullDate}\n", FILE_APPEND);
            
            Log::emergency('처리 시작', ['statementDate' => $statementDate, 'fullDate' => $fullDate]);
            
            // 파일 확장자 확인
            $extension = $file->getClientOriginalExtension();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 확장자: {$extension}\n", FILE_APPEND);
            
            // CSV 파일인 경우 PHP 내장 함수로 처리
            if (strtolower($extension) === 'csv') {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 파일 처리 시작 (내장 함수 사용)\n", FILE_APPEND);
                return $this->processCSVFile($file, $statementDate);
            }
            
            // 스프레드시트 파일 읽기 (PhpSpreadsheet 사용)
            try {
                $spreadsheet = IOFactory::load($file->getPathname());
                $sheet = $spreadsheet->getActiveSheet();
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 스프레드시트 로드됨: 시트명={$sheet->getTitle()}, 최대행={$sheet->getHighestRow()}\n", FILE_APPEND);
                
                Log::emergency('스프레드시트 로드됨', ['sheetName' => $sheet->getTitle(), 'highestRow' => $sheet->getHighestRow()]);
                
                // 첫 번째 행부터 10번째 행까지 헤더 검색
                $headerRow = 0;
                $hasHeader = false;
                
                // 모든 행의 데이터 미리 로깅
                for ($i = 1; $i <= min(10, $sheet->getHighestRow()); $i++) {
                    $rowData = $sheet->rangeToArray('A' . $i . ':Z' . $i, null, true, false)[0];
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Row {$i} 데이터: " . json_encode($rowData) . "\n", FILE_APPEND);
                    
                    Log::emergency('Row ' . $i . ' 데이터', ['data' => $rowData]);
                }
                
                for ($i = 1; $i <= 10; $i++) {
                    if ($this->checkIfRowIsHeader($sheet, $i)) {
                        $headerRow = $i;
                        $hasHeader = true;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 발견: 행={$headerRow}\n", FILE_APPEND);
                        
                        Log::emergency('건강보험 헤더 발견', ['row' => $headerRow]);
                        break;
                    }
                }
                
                if (!$hasHeader) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더를 찾을 수 없음\n", FILE_APPEND);
                    
                    Log::emergency('헤더를 찾을 수 없음');
                    return [
                        'processed' => 0,
                        'error' => '헤더를 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                    ];
                }
                
                // 헤더 행 확인 후 각 열의 인덱스 찾기
                $headers = [];
                $headerRowData = $sheet->rangeToArray('A' . $headerRow . ':Z' . $headerRow, null, true, false)[0];
                
                foreach ($headerRowData as $index => $value) {
                    if (is_string($value) && trim($value) !== '') {
                        $headers[trim($value)] = $index;
                    }
                }
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 매핑: " . json_encode($headers) . "\n", FILE_APPEND);
                
                Log::emergency('건강보험 헤더 매핑', ['headers' => $headers]);
                
                // 건강보험과 장기요양보험 열 찾기
                $healthInsuranceIndex = false;
                $longTermCareIndex = false;
                
                // 기존의 동적 검색 대신 고정된 열 인덱스 사용 (사용자 지정)
                foreach ($headers as $headerName => $index) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 '{$headerName}' 위치: {$index}\n", FILE_APPEND);
                    
                    // 건강보험료는 N열(13번째 인덱스)의 '고지보험료'
                    if ($index == 13 && strpos($headerName, '고지보험료') !== false) {
                        $healthInsuranceIndex = $index;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험 고지보험료 발견 (N열/13번): {$headerName}\n", FILE_APPEND);
                    }
                    
                    // 장기요양보험료는 AA열(26번째 인덱스)의 '고지보험료'
                    if ($index == 26 && strpos($headerName, '고지보험료') !== false) {
                        $longTermCareIndex = $index;
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 장기요양 고지보험료 발견 (AA열/26번): {$headerName}\n", FILE_APPEND);
                    }
                }
                
                // 열 인덱스를 찾지 못했을 경우 명시적으로 고정 인덱스 사용
                if ($healthInsuranceIndex === false) {
                    $healthInsuranceIndex = 13; // N열
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험료 인덱스 명시적 지정: 13 (N열)\n", FILE_APPEND);
                }
                
                if ($longTermCareIndex === false) {
                    $longTermCareIndex = 26; // AA열
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 장기요양보험료 인덱스 명시적 지정: 26 (AA열)\n", FILE_APPEND);
                }
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 최종 열 인덱스: 주민번호=" . $residentIdIndex . ", 이름=" . $nameIndex . ", 건강보험료=" . $healthInsuranceIndex . ", 장기요양보험료=" . $longTermCareIndex . "\n", FILE_APPEND);
                
                // 필요한 열 인덱스 찾기
                $residentIdIndex = $this->findColumnIndex($headers, ['주민번호', '주민등록번호', '주민']);
                $nameIndex = $this->findColumnIndex($headers, ['성명', '이름', '가입자명']);
                
                if ($residentIdIndex === false || $nameIndex === false) {
                    Log::emergency('필수 열을 찾을 수 없음', [
                        'residentIdIndex' => $residentIdIndex,
                        'nameIndex' => $nameIndex
                    ]);
                    return [
                        'processed' => 0,
                        'error' => '주민번호나 이름 열을 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                    ];
                }
                
                if ($healthInsuranceIndex === false && $longTermCareIndex === false) {
                    Log::emergency('보험료 열을 찾을 수 없음');
                    return [
                        'processed' => 0,
                        'error' => '보험료 열을 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                    ];
                }
                
                // 데이터 처리
                $dataStartRow = $headerRow + 1;
                $data = [];
                
                for ($row = $dataStartRow; $row <= $sheet->getHighestRow(); $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':Z' . $row, null, true, false)[0];
                    
                    // 빈 행 또는 더 이상의 데이터가 없으면 중단
                    if (!isset($rowData[$residentIdIndex]) || !isset($rowData[$nameIndex]) || 
                        empty($rowData[$residentIdIndex]) || empty($rowData[$nameIndex])) {
                        continue;
                    }
                    
                    // 주민번호, 하이픈(-)이 있으면 제거
                    $residentId = str_replace('-', '', $rowData[$residentIdIndex]);
                    $name = $rowData[$nameIndex];
                    
                    // 보험료 정보 추출
                    $healthInsurance = 0;
                    $longTermCare = 0;
                    
                    if ($healthInsuranceIndex !== false && isset($rowData[$healthInsuranceIndex])) {
                        $healthInsurance = $this->parseAmount($rowData[$healthInsuranceIndex]);
                    }
                    
                    if ($longTermCareIndex !== false && isset($rowData[$longTermCareIndex])) {
                        $longTermCare = $this->parseAmount($rowData[$longTermCareIndex]);
                    }
                    
                    // 데이터가 유효한지 확인
                    if (empty($residentId) || empty($name)) {
                        Log::emergency('유효하지 않은 데이터 건너뜀', [
                            'row' => $row,
                            'residentId' => $residentId,
                            'name' => $name
                        ]);
                        continue;
                    }
                    
                    // 데이터 배열에 추가
                    $data[] = [
                        'statement_date' => $fullDate,
                        'resident_id' => $residentId,
                        'name' => $name,
                        'health_insurance' => $healthInsurance,
                        'long_term_care' => $longTermCare
                    ];
                    
                    // 매 10개 데이터마다 로깅 (모든 데이터를 로깅하면 너무 많을 수 있으므로)
                    if (count($data) % 10 === 0) {
                        Log::emergency($count . '번째 데이터 처리 중', end($data));
                    }
                }
                
                // 샘플 데이터 로깅 (첫 번째와 마지막 항목)
                if (count($data) > 0) {
                    Log::emergency('건강보험 데이터 샘플', [
                        'first' => $data[0],
                        'last' => $data[count($data) - 1],
                        'total' => count($data)
                    ]);
                } else {
                    Log::emergency('추출된 데이터가 없음');
                    return [
                        'processed' => 0,
                        'error' => '파일에서 유효한 데이터를 추출할 수 없습니다.'
                    ];
                }
                
                // 데이터 업데이트 또는 삽입
                Log::emergency('DB 업데이트 시작', ['dataCount' => count($data)]);
                foreach ($data as $item) {
                    try {
                        $result = SocialInsurance::updateOrCreate(
                            [
                                'statement_date' => $item['statement_date'],
                                'name' => $item['name']
                            ],
                            $item
                        );
                        
                        Log::emergency('DB 레코드 업데이트/생성', [
                            'id' => $result->id,
                            'name' => $result->name,
                            'statement_date' => $result->statement_date
                        ]);
                        
                        $count++;
                    } catch (\Exception $e) {
                        Log::emergency('DB 업데이트 오류', [
                            'message' => $e->getMessage(),
                            'data' => $item
                        ]);
                    }
                }
                
                Log::emergency('건강보험 처리 완료', ['count' => $count]);
                return [
                    'processed' => $count,
                    'error' => null
                ];
                
            } catch (\Exception $e) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - PhpSpreadsheet 로드 실패: {$e->getMessage()}\n", FILE_APPEND);
                Log::emergency('PhpSpreadsheet 로드 실패', ['error' => $e->getMessage()]);
                
                // CSV로 시도
                if (strtolower($extension) === 'csv') {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 대체 방법으로 CSV 파일 처리 시도\n", FILE_APPEND);
                    return $this->processCSVFile($file, $statementDate);
                }
                
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::emergency('건강보험 처리 오류: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'processed' => $count,
                'error' => '건강보험 파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * CSV 파일을 PHP 내장 함수로 처리합니다.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $statementDate
     * @return array
     */
    private function processCSVFile($file, $statementDate)
    {
        $count = 0;
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - processCSVFile 시작\n", FILE_APPEND);
        
        try {
            $fullDate = $statementDate . '-01';
            
            // CSV 파일 내용 읽기
            $filepath = $file->getPathname();
            $fileContent = file_get_contents($filepath);
            
            if ($fileContent === false) {
                throw new \Exception('CSV 파일 내용을 읽을 수 없습니다.');
            }
            
            // 인코딩 감지 및 변환 시도
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'EUC-KR', 'CP949', 'ASCII'], true);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 인코딩: " . ($encoding ?: 'unknown') . "\n", FILE_APPEND);
            
            // EUC-KR이나 CP949로 인식되면 UTF-8로 변환
            if ($encoding && $encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 인코딩 변환: {$encoding} -> UTF-8\n", FILE_APPEND);
            } elseif (!$encoding) {
                // 인코딩 감지 실패 시 강제로 CP949 -> UTF-8 변환 시도
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'CP949');
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 강제 인코딩 변환: CP949 -> UTF-8\n", FILE_APPEND);
            }
            
            // BOM 제거
            $bom = pack('H*', 'EFBBBF');
            $fileContent = preg_replace("/^$bom/", '', $fileContent);
            
            // 임시 파일로 저장 (UTF-8 인코딩)
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $fileContent);
            
            // CSV 파일 열기
            $handle = fopen($tempFile, 'r');
            
            if ($handle === false) {
                throw new \Exception('CSV 파일을 열 수 없습니다.');
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 파일 열기 성공\n", FILE_APPEND);
            
            // 구분자 감지 시도
            $delimiter = $this->detectCSVDelimiter($tempFile);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 CSV 구분자: '{$delimiter}'\n", FILE_APPEND);
            
            // 파일 포인터 처음으로 되돌림
            rewind($handle);
            
            // 헤더 및 초기 행 분석을 위한 배열
            $previewRows = [];
            $rowNumber = 0;
            $maxPreviewRows = 15; // 미리보기용으로 최대 15개 행만 로깅
            
            // 파일의 모든 행을 읽되, 처음 15개만 로깅
            $allRows = [];
            
            while (($rowData = fgetcsv($handle, 0, $delimiter)) !== false) {
                // 빈 행 건너뛰기
                if (count($rowData) <= 1 && empty($rowData[0])) {
                    continue;
                }
                
                // 모든 값이 비어있는지 확인
                $isEmpty = true;
                foreach ($rowData as $cell) {
                    if (!empty(trim($cell))) {
                        $isEmpty = false;
                        break;
                    }
                }
                
                if ($isEmpty) {
                    continue;
                }
                
                $allRows[] = $rowData;
                
                // 미리보기용으로 처음 15개 행만 로깅
                if ($rowNumber < $maxPreviewRows) {
                    $previewRows[] = $rowData;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Row {$rowNumber} 데이터: " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                }
                $rowNumber++;
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 총 행 수: " . count($allRows) . "\n", FILE_APPEND);
            
            // 헤더 행 찾기
            $headerRow = -1;
            $hasHeader = false;
            
            for ($i = 0; $i < min(15, count($allRows)); $i++) {
                if ($this->checkIfRowIsHeaderCSV($allRows[$i])) {
                    $headerRow = $i;
                    $hasHeader = true;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 발견: 행={$headerRow}\n", FILE_APPEND);
                    break;
                }
            }
            
            // 헤더를 못 찾았는데 데이터가 있으면 첫 번째 행을 헤더로 간주
            if (!$hasHeader && count($allRows) > 0) {
                $headerRow = 0;
                $hasHeader = true;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더가 명확하지 않아 첫 번째 유효 행을 헤더로 간주: 행={$headerRow}\n", FILE_APPEND);
            }
            
            if (!$hasHeader) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더를 찾을 수 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '헤더를 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                ];
            }
            
            // 헤더 매핑
            $headerRowData = $allRows[$headerRow];
            $headers = [];
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 행 데이터: " . json_encode($headerRowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            foreach ($headerRowData as $index => $value) {
                if (is_string($value) && trim($value) !== '') {
                    $headers[trim($value)] = $index;
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 매핑: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            // 필요한 열 인덱스 찾기
            $residentIdIndex = $this->findColumnIndex($headers, ['주민번호', '주민등록번호', '주민']);
            $nameIndex = $this->findColumnIndex($headers, ['성명', '이름', '가입자명']);
            
            // 건강보험과 장기요양보험 열 찾기
            $healthInsuranceIndex = false;
            $longTermCareIndex = false;
            
            // 기존의 동적 검색 대신 고정된 열 인덱스 사용 (사용자 지정)
            foreach ($headers as $headerName => $index) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 '{$headerName}' 위치: {$index}\n", FILE_APPEND);
                
                // 건강보험료는 N열(13번째 인덱스)의 '고지보험료'
                if ($index == 13 && strpos($headerName, '고지보험료') !== false) {
                    $healthInsuranceIndex = $index;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험 고지보험료 발견 (N열/13번): {$headerName}\n", FILE_APPEND);
                }
                
                // 장기요양보험료는 AA열(26번째 인덱스)의 '고지보험료'
                if ($index == 26 && strpos($headerName, '고지보험료') !== false) {
                    $longTermCareIndex = $index;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 장기요양 고지보험료 발견 (AA열/26번): {$headerName}\n", FILE_APPEND);
                }
            }
            
            // 열 인덱스를 찾지 못했을 경우 명시적으로 고정 인덱스 사용
            if ($healthInsuranceIndex === false) {
                $healthInsuranceIndex = 13; // N열
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 건강보험료 인덱스 명시적 지정: 13 (N열)\n", FILE_APPEND);
            }
            
            if ($longTermCareIndex === false) {
                $longTermCareIndex = 26; // AA열
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 장기요양보험료 인덱스 명시적 지정: 26 (AA열)\n", FILE_APPEND);
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 최종 열 인덱스: 주민번호=" . $residentIdIndex . ", 이름=" . $nameIndex . ", 건강보험료=" . $healthInsuranceIndex . ", 장기요양보험료=" . $longTermCareIndex . "\n", FILE_APPEND);
            
            // 데이터 처리
            $dataStartRow = $headerRow + 1;
            $data = [];
            
            // 데이터 행 처리
            for ($i = $dataStartRow; $i < count($allRows); $i++) {
                $rowData = $allRows[$i];
                
                // 빈 행 또는 더 이상의 데이터가 없으면 중단
                if (!isset($rowData[$residentIdIndex]) || !isset($rowData[$nameIndex]) || 
                    empty(trim($rowData[$residentIdIndex])) || empty(trim($rowData[$nameIndex]))) {
                    continue;
                }
                
                // 주민번호, 하이픈(-)이 있으면 제거
                $residentId = str_replace('-', '', $rowData[$residentIdIndex]);
                $name = $rowData[$nameIndex];
                
                // 보험료 정보 추출
                $healthInsurance = 0;
                $longTermCare = 0;
                
                if ($healthInsuranceIndex !== false && isset($rowData[$healthInsuranceIndex])) {
                    $healthInsurance = $this->parseAmount($rowData[$healthInsuranceIndex]);
                }
                
                if ($longTermCareIndex !== false && isset($rowData[$longTermCareIndex])) {
                    $longTermCare = $this->parseAmount($rowData[$longTermCareIndex]);
                }
                
                // 데이터 배열에 추가
                $data[] = [
                    'statement_date' => $fullDate,
                    'resident_id' => $residentId,
                    'name' => $name,
                    'health_insurance' => $healthInsurance,
                    'long_term_care' => $longTermCare
                ];
            }
            
            // 임시 파일 삭제
            @unlink($tempFile);
            
            // 샘플 데이터 로깅
            if (count($data) > 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 데이터 샘플: " . json_encode(array_slice($data, 0, 2), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 총 데이터 수: " . count($data) . "\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 추출된 데이터가 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '파일에서 유효한 데이터를 추출할 수 없습니다.'
                ];
            }
            
            // 데이터 업데이트 또는 삽입
            foreach ($data as $item) {
                try {
                    $result = SocialInsurance::updateOrCreate(
                        [
                            'statement_date' => $item['statement_date'],
                            'name' => $item['name']
                        ],
                        $item
                    );
                    
                    $count++;
                } catch (\Exception $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - DB 업데이트 오류: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 처리 완료: {$count}개 처리됨\n", FILE_APPEND);
            return [
                'processed' => $count,
                'error' => null
            ];
            
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 처리 오류: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::emergency('CSV 파일 처리 오류: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return [
                'processed' => $count,
                'error' => 'CSV 파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * CSV 파일의 구분자를 감지합니다.
     *
     * @param string $file CSV 파일 경로
     * @return string 감지된 구분자 (기본값: ',')
     */
    private function detectCSVDelimiter($file)
    {
        $delimiters = [',', ';', '\t', '|'];
        $results = [];
        $handle = fopen($file, 'r');
        
        $firstLine = fgets($handle);
        fclose($handle);
        
        foreach ($delimiters as $delimiter) {
            $results[$delimiter] = substr_count($firstLine, $delimiter);
        }
        
        arsort($results);
        
        return key($results) ?: ',';
    }
    
    /**
     * CSV 행이 헤더인지 확인합니다.
     *
     * @param array $rowData
     * @return bool
     */
    private function checkIfRowIsHeaderCSV($rowData)
    {
        $logFile = storage_path('logs/social_insurance_debug.log');
        
        try {
            // 헤더 행에서 기대하는 일부 값들
            $expectedHeaders = [
                '주민번호', '성명', '보수월액', '고지보험료', '정산보험료', 
                '증번호', '순번', '이름', '구분', '산출보험료', '감면사유', 
                '정산사유', '정산적용기간', '연말정산', '환급금이자', '가입자',
                '구분', '성명', '이름', '주민등록번호', '보험료', '금액'
            ];
            
            // 헤더 행의 값을 로그에 기록
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 검사: " . json_encode($rowData) . "\n", FILE_APPEND);
            
            $foundCount = 0;
            $matches = [];
            
            foreach ($rowData as $index => $cell) {
                if (!is_string($cell)) {
                    continue;
                }
                
                $cell = trim($cell);
                
                if (empty($cell)) {
                    continue;
                }
                
                foreach ($expectedHeaders as $header) {
                    if ($cell === $header || stripos($cell, $header) !== false) {
                        $foundCount++;
                        $matches[] = [
                            'col' => $index,
                            'value' => $cell,
                            'matched' => $header
                        ];
                        break;
                    }
                }
            }
            
            // 일부 기대한 헤더가 발견되면 헤더 행으로 간주 (2개 이상)
            $isHeader = $foundCount >= 2;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 검사 결과: 찾음={$foundCount}, 헤더여부={$isHeader}, 매치=" . json_encode($matches) . "\n", FILE_APPEND);
            
            return $isHeader;
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 검사 오류: 오류={$e->getMessage()}\n", FILE_APPEND);
            return false;
        }
    }
    
    /**
     * 지정된 행이 헤더인지 확인합니다.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $row
     * @return bool
     */
    private function checkIfRowIsHeader($sheet, $row)
    {
        $logFile = storage_path('logs/social_insurance_debug.log');
        
        try {
            $rowData = $sheet->rangeToArray('A' . $row . ':Z' . $row, null, true, false)[0];
            
            // 헤더 행에서 기대하는 일부 값들 (더 많은 가능한 헤더 추가)
            $expectedHeaders = [
                '주민번호', '성명', '보수월액', '고지보험료', '정산보험료', 
                '증번호', '순번', '이름', '구분', '산출보험료', '감면사유', 
                '정산사유', '정산적용기간', '연말정산', '환급금이자', '가입자',
                '구분', '성명', '이름', '주민등록번호', '보험료', '금액'
            ];
            
            // 헤더 행의 값을 로그에 기록
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Row {$row} 헤더 검사: " . json_encode($rowData) . "\n", FILE_APPEND);
            
            Log::emergency('Row ' . $row . ' 헤더 확인', ['data' => $rowData]);
            
            $foundCount = 0;
            $matches = [];
            
            foreach ($rowData as $index => $cell) {
                if (!is_string($cell)) {
                    continue;
                }
                
                $cell = trim($cell);
                
                if (empty($cell)) {
                    continue;
                }
                
                foreach ($expectedHeaders as $header) {
                    if ($cell === $header || stripos($cell, $header) !== false) {
                        $foundCount++;
                        $matches[] = [
                            'col' => $index,
                            'value' => $cell,
                            'matched' => $header
                        ];
                        break;
                    }
                }
            }
            
            // 일부 기대한 헤더가 발견되면 헤더 행으로 간주 (2개 이상)
            $isHeader = $foundCount >= 2;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 검사 결과: 행={$row}, 찾음={$foundCount}, 헤더여부={$isHeader}, 매치=" . json_encode($matches) . "\n", FILE_APPEND);
            
            Log::emergency('헤더 확인 결과', [
                'row' => $row, 
                'found' => $foundCount, 
                'isHeader' => $isHeader,
                'matches' => $matches
            ]);
            
            return $isHeader;
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 검사 오류: 행={$row}, 오류={$e->getMessage()}\n", FILE_APPEND);
            
            Log::emergency('헤더 확인 오류', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * 헤더 배열에서 주어진 헤더 이름과 일치하는 열 인덱스를 찾습니다.
     *
     * @param array $headers
     * @param array $possibleNames
     * @return int|false
     */
    private function findColumnIndex($headers, $possibleNames)
    {
        foreach ($possibleNames as $name) {
            if (isset($headers[$name])) {
                return $headers[$name];
            }
        }
        
        return false;
    }
    
    /**
     * 헤더 배열에서 주어진 헤더 이름과 일치하는 열 인덱스를 찾되, 
     * 동일한 이름의 열이 여러 개 있을 때 왼쪽 또는 오른쪽에 있는 열의 인덱스를 반환합니다.
     *
     * @param array $headers
     * @param string $name
     * @param string $position 'left' 또는 'right'
     * @return int|false
     */
    private function findColumnByPosition($headers, $name, $position = 'left')
    {
        $indices = [];
        
        foreach ($headers as $header => $index) {
            if ($header === $name) {
                $indices[] = $index;
            }
        }
        
        if (empty($indices)) {
            return false;
        }
        
        if ($position === 'left') {
            return min($indices);
        } else {
            return max($indices);
        }
    }
    
    /**
     * 금액 문자열을 숫자로 변환합니다.
     *
     * @param string $amount
     * @return float
     */
    private function parseAmount($amount)
    {
        // 쉼표, 원화 기호 등 제거
        $amount = preg_replace('/[^\d.-]/', '', $amount);
        return floatval($amount);
    }
    
    /**
     * 헤더 배열에서 주어진 헤더 이름과 일치하는 모든 열 인덱스를 찾습니다.
     *
     * @param array $headers
     * @param array $possibleNames
     * @return array
     */
    private function findAllColumnIndices($headers, $possibleNames)
    {
        $indices = [];
        
        foreach ($headers as $header => $index) {
            foreach ($possibleNames as $name) {
                if ($header === $name || strpos($header, $name) !== false) {
                    $indices[] = $index;
                    break;
                }
            }
        }
        
        sort($indices);
        return $indices;
    }

    /**
     * 엑셀 헤더를 데이터베이스 필드에 매핑합니다.
     *
     * @param array $headerRow
     * @return array
     */
    private function mapHeadersToColumns($headerRow)
    {
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 매핑 시작: " . json_encode($headerRow) . "\n", FILE_APPEND);
        
        Log::emergency('헤더 매핑 시작', ['headerRow' => $headerRow]);
        
        // 헤더 이름과 데이터베이스 컬럼 매핑
        $headerMapping = [
            // 주민번호 관련 매핑
            '주민번호' => 'resident_id',
            '주민등록번호' => 'resident_id',
            
            // 이름 관련 매핑
            '성명' => 'name',
            '이름' => 'name',
            '가입자성명' => 'name',
            
            // 보수월액 관련 매핑
            '보수월액' => 'monthly_wage',
            '월소득액' => 'monthly_wage',
            
            // 고지보험료 관련 매핑
            '고지보험료' => 'insurance_fee',
            '건강보험료' => 'insurance_fee',
            '보험료' => 'insurance_fee',
            '금액' => 'insurance_fee',
            
            // 장기요양보험료 관련 매핑
            '장기요양보험료' => 'long_term_fee',
            '요양보험료' => 'long_term_fee',
            
            // 정산보험료 관련 매핑
            '정산보험료' => 'settlement_fee',
            '정산금액' => 'settlement_fee',
            
            // 연금보험료 관련 매핑
            '연금보험료' => 'pension_fee',
            '국민연금' => 'pension_fee',
            
            // 고용보험료 관련 매핑
            '고용보험료' => 'employment_fee',
            '고용보험' => 'employment_fee',
            
            // 산재보험료 관련 매핑
            '산재보험료' => 'accident_fee',
            '산재보험' => 'accident_fee',
        ];
        
        $mapping = [];
        $matches = [];
        
        foreach ($headerRow as $index => $headerName) {
            if (!is_string($headerName)) {
                continue;
            }
            
            $headerName = trim($headerName);
            
            if (empty($headerName)) {
                continue;
            }
            
            foreach ($headerMapping as $expectedHeader => $columnName) {
                if ($headerName === $expectedHeader || stripos($headerName, $expectedHeader) !== false) {
                    $mapping[$index] = $columnName;
                    $matches[] = [
                        'index' => $index,
                        'header' => $headerName,
                        'mapped_to' => $columnName,
                        'matched_with' => $expectedHeader
                    ];
                    break;
                }
            }
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 매핑 결과: " . json_encode($mapping) . "\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 매치 상세: " . json_encode($matches) . "\n", FILE_APPEND);
        
        Log::emergency('헤더 매핑 결과', [
            'mapping' => $mapping,
            'matches' => $matches
        ]);
        
        return $mapping;
    }

    /**
     * 국민연금 데이터를 처리합니다.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $statementDate
     * @return array
     */
    private function processPensionInsurance($file, $statementDate)
    {
        $count = 0;
        $error = null;
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - processPensionInsurance 시작\n", FILE_APPEND);
        
        try {
            $fullDate = $statementDate . '-01';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 처리: 날짜 {$fullDate}\n", FILE_APPEND);
            
            Log::emergency('국민연금 처리 시작', ['statementDate' => $statementDate, 'fullDate' => $fullDate]);
            
            // 파일 확장자 확인
            $extension = $file->getClientOriginalExtension();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 확장자: {$extension}\n", FILE_APPEND);
            
            // CSV 파일 내용 읽기
            $filepath = $file->getPathname();
            $fileContent = file_get_contents($filepath);
            
            if ($fileContent === false) {
                throw new \Exception('CSV 파일 내용을 읽을 수 없습니다.');
            }
            
            // 인코딩 감지 및 변환
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'EUC-KR', 'CP949', 'ASCII'], true);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 인코딩: " . ($encoding ?: 'unknown') . "\n", FILE_APPEND);
            
            if ($encoding && $encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 인코딩 변환: {$encoding} -> UTF-8\n", FILE_APPEND);
            } elseif (!$encoding) {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'CP949');
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 강제 인코딩 변환: CP949 -> UTF-8\n", FILE_APPEND);
            }
            
            // BOM 제거
            $bom = pack('H*', 'EFBBBF');
            $fileContent = preg_replace("/^$bom/", '', $fileContent);
            
            // 임시 파일로 저장
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $fileContent);
            
            // CSV 파일 열기
            $handle = fopen($tempFile, 'r');
            
            if ($handle === false) {
                throw new \Exception('CSV 파일을 열 수 없습니다.');
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 CSV 파일 열기 성공\n", FILE_APPEND);
            
            // 구분자 감지
            $delimiter = $this->detectCSVDelimiter($tempFile);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 CSV 구분자: '{$delimiter}'\n", FILE_APPEND);
            
            // 파일 포인터 처음으로 되돌림
            rewind($handle);
            
            // 모든 행 읽기
            $allRows = [];
            $rowNumber = 0;
            $maxPreviewRows = 15;
            
            while (($rowData = fgetcsv($handle, 0, $delimiter)) !== false) {
                // 빈 행 건너뛰기
                if (count($rowData) <= 1 && empty($rowData[0])) {
                    continue;
                }
                
                // 모든 값이 비어있는지 확인
                $isEmpty = true;
                foreach ($rowData as $cell) {
                    if (!empty(trim($cell))) {
                        $isEmpty = false;
                        break;
                    }
                }
                
                if ($isEmpty) {
                    continue;
                }
                
                $allRows[] = $rowData;
                
                // 미리보기용으로 처음 15개 행만 로깅
                if ($rowNumber < $maxPreviewRows) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Row {$rowNumber} 데이터: " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                }
                $rowNumber++;
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 총 행 수: " . count($allRows) . "\n", FILE_APPEND);
            
            // 헤더 행 찾기
            $headerRow = -1;
            $hasHeader = false;
            
            for ($i = 0; $i < min(15, count($allRows)); $i++) {
                if ($this->checkIfRowIsHeaderCSV($allRows[$i])) {
                    $headerRow = $i;
                    $hasHeader = true;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 발견: 행={$headerRow}\n", FILE_APPEND);
                    break;
                }
            }
            
            // 헤더를 못 찾았는데 데이터가 있으면 첫 번째 행을 헤더로 간주
            if (!$hasHeader && count($allRows) > 0) {
                $headerRow = 0;
                $hasHeader = true;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더가 명확하지 않아 첫 번째 유효 행을 헤더로 간주: 행={$headerRow}\n", FILE_APPEND);
            }
            
            if (!$hasHeader) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더를 찾을 수 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '헤더를 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                ];
            }
            
            // 헤더 매핑
            $headerRowData = $allRows[$headerRow];
            $headers = [];
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 행 데이터: " . json_encode($headerRowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            foreach ($headerRowData as $index => $value) {
                if (is_string($value) && trim($value) !== '') {
                    $headers[trim($value)] = $index;
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 CSV 헤더 매핑: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            // 국민연금 파일의 열 인덱스 (C, D, G열)
            $residentIdIndex = 2;    // C열 - 주민번호
            $nameIndex = 3;          // D열 - 가입자명
            $pensionFeeIndex = 6;    // G열 - 결정보험료
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 열 인덱스: 주민번호(C열)={$residentIdIndex}, 이름(D열)={$nameIndex}, 결정보험료(G열)={$pensionFeeIndex}\n", FILE_APPEND);
            
            // 데이터 처리
            $dataStartRow = $headerRow + 1;
            $data = [];
            
            // 데이터 행 처리
            for ($i = $dataStartRow; $i < count($allRows); $i++) {
                $rowData = $allRows[$i];
                
                // 빈 행이나 필수 데이터가 없으면 건너뛰기
                if (!isset($rowData[$residentIdIndex]) || !isset($rowData[$nameIndex]) || !isset($rowData[$pensionFeeIndex]) || 
                    empty(trim($rowData[$residentIdIndex])) || empty(trim($rowData[$nameIndex]))) {
                    continue;
                }
                
                // 데이터 추출
                $residentId = str_replace('-', '', $rowData[$residentIdIndex]);
                $name = $rowData[$nameIndex];
                
                // 결정보험료 처리 (2로 나누고 원단위 절사)
                $originalPensionFee = $this->parseAmount($rowData[$pensionFeeIndex]);
                $pensionFee = intval($originalPensionFee / 2 / 10) * 10;  // 2로 나누고 10원 단위로 절사
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 계산: 원본={$originalPensionFee}, 계산후={$pensionFee} (2로 나누고 원단위 절사)\n", FILE_APPEND);
                
                // 데이터 배열에 추가
                $data[] = [
                    'statement_date' => $fullDate,
                    'resident_id' => $residentId,
                    'name' => $name,
                    'national_pension' => $pensionFee  // 국민연금 보험료
                ];
            }
            
            // 임시 파일 삭제
            @unlink($tempFile);
            
            // 샘플 데이터 로깅
            if (count($data) > 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 데이터 샘플: " . json_encode(array_slice($data, 0, 2), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 총 데이터 수: " . count($data) . "\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 추출된 데이터가 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '파일에서 유효한 데이터를 추출할 수 없습니다.'
                ];
            }
            
            // 데이터베이스에 저장
            foreach ($data as $item) {
                try {
                    $result = SocialInsurance::updateOrCreate(
                        [
                            'statement_date' => $item['statement_date'],
                            'name' => $item['name']
                        ],
                        $item
                    );
                    
                    $count++;
                } catch (\Exception $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - DB 업데이트 오류: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 처리 완료: {$count}개 처리됨\n", FILE_APPEND);
            return [
                'processed' => $count,
                'error' => null
            ];
            
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 국민연금 처리 오류: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::emergency('국민연금 처리 오류: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'processed' => $count,
                'error' => '국민연금 파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 고용보험 데이터를 처리합니다.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $statementDate
     * @return array
     */
    private function processEmploymentInsurance($file, $statementDate)
    {
        $count = 0;
        $error = null;
        $logFile = storage_path('logs/social_insurance_debug.log');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - processEmploymentInsurance 시작\n", FILE_APPEND);
        
        try {
            $fullDate = $statementDate . '-01';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 처리: 날짜 {$fullDate}\n", FILE_APPEND);
            
            Log::emergency('고용보험 처리 시작', ['statementDate' => $statementDate, 'fullDate' => $fullDate]);
            
            // 파일 확장자 확인
            $extension = $file->getClientOriginalExtension();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 파일 확장자: {$extension}\n", FILE_APPEND);
            
            // CSV 파일 내용 읽기
            $filepath = $file->getPathname();
            $fileContent = file_get_contents($filepath);
            
            if ($fileContent === false) {
                throw new \Exception('CSV 파일 내용을 읽을 수 없습니다.');
            }
            
            // 인코딩 감지 및 변환
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'EUC-KR', 'CP949', 'ASCII'], true);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 인코딩: " . ($encoding ?: 'unknown') . "\n", FILE_APPEND);
            
            if ($encoding && $encoding !== 'UTF-8') {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 인코딩 변환: {$encoding} -> UTF-8\n", FILE_APPEND);
            } elseif (!$encoding) {
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'CP949');
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 강제 인코딩 변환: CP949 -> UTF-8\n", FILE_APPEND);
            }
            
            // BOM 제거
            $bom = pack('H*', 'EFBBBF');
            $fileContent = preg_replace("/^$bom/", '', $fileContent);
            
            // 임시 파일로 저장
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($tempFile, $fileContent);
            
            // CSV 파일 열기
            $handle = fopen($tempFile, 'r');
            
            if ($handle === false) {
                throw new \Exception('CSV 파일을 열 수 없습니다.');
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 CSV 파일 열기 성공\n", FILE_APPEND);
            
            // 구분자 감지
            $delimiter = $this->detectCSVDelimiter($tempFile);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 감지된 CSV 구분자: '{$delimiter}'\n", FILE_APPEND);
            
            // 파일 포인터 처음으로 되돌림
            rewind($handle);
            
            // 모든 행 읽기
            $allRows = [];
            $rowNumber = 0;
            $maxPreviewRows = 15;
            
            while (($rowData = fgetcsv($handle, 0, $delimiter)) !== false) {
                // 빈 행 건너뛰기
                if (count($rowData) <= 1 && empty($rowData[0])) {
                    continue;
                }
                
                // 모든 값이 비어있는지 확인
                $isEmpty = true;
                foreach ($rowData as $cell) {
                    if (!empty(trim($cell))) {
                        $isEmpty = false;
                        break;
                    }
                }
                
                if ($isEmpty) {
                    continue;
                }
                
                $allRows[] = $rowData;
                
                // 미리보기용으로 처음 15개 행만 로깅
                if ($rowNumber < $maxPreviewRows) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Row {$rowNumber} 데이터: " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                }
                $rowNumber++;
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 총 행 수: " . count($allRows) . "\n", FILE_APPEND);
            
            // 헤더 행 찾기
            $headerRow = -1;
            $hasHeader = false;
            
            for ($i = 0; $i < min(15, count($allRows)); $i++) {
                if ($this->checkIfRowIsHeaderCSV($allRows[$i])) {
                    $headerRow = $i;
                    $hasHeader = true;
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더 발견: 행={$headerRow}\n", FILE_APPEND);
                    break;
                }
            }
            
            // 헤더를 못 찾았는데 데이터가 있으면 첫 번째 행을 헤더로 간주
            if (!$hasHeader && count($allRows) > 0) {
                $headerRow = 0;
                $hasHeader = true;
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더가 명확하지 않아 첫 번째 유효 행을 헤더로 간주: 행={$headerRow}\n", FILE_APPEND);
            }
            
            if (!$hasHeader) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - CSV 헤더를 찾을 수 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '헤더를 찾을 수 없습니다. 파일 형식을 확인해주세요.'
                ];
            }
            
            // 헤더 매핑
            $headerRowData = $allRows[$headerRow];
            $headers = [];
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 헤더 행 데이터: " . json_encode($headerRowData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            foreach ($headerRowData as $index => $value) {
                if (is_string($value) && trim($value) !== '') {
                    $headers[trim($value)] = $index;
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 CSV 헤더 매핑: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
            
            // 고용보험 파일의 열 인덱스 (C, D, E열)
            $residentIdIndex = 2;    // C열 - 주민번호
            $nameIndex = 3;          // D열 - 가입자명
            $employmentFeeIndex = 4; // E열 - 결정보험료
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 열 인덱스: 주민번호(C열)={$residentIdIndex}, 이름(D열)={$nameIndex}, 결정보험료(E열)={$employmentFeeIndex}\n", FILE_APPEND);
            
            // 데이터 처리
            $dataStartRow = $headerRow + 1;
            $data = [];
            
            // 데이터 행 처리
            for ($i = $dataStartRow; $i < count($allRows); $i++) {
                $rowData = $allRows[$i];
                
                // 빈 행이나 필수 데이터가 없으면 건너뛰기
                if (!isset($rowData[$residentIdIndex]) || !isset($rowData[$nameIndex]) || !isset($rowData[$employmentFeeIndex]) || 
                    empty(trim($rowData[$residentIdIndex])) || empty(trim($rowData[$nameIndex]))) {
                    continue;
                }
                
                // 데이터 추출
                $residentId = str_replace('-', '', $rowData[$residentIdIndex]);
                $name = $rowData[$nameIndex];
                
                // 결정보험료 처리 (0.43902 곱하고 원단위 절사)
                $originalEmploymentFee = $this->parseAmount($rowData[$employmentFeeIndex]);
                $employmentFee = intval($originalEmploymentFee * 0.43902 / 10) * 10;  // 0.43902 곱하고 10원 단위로 절사
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 계산: 원본={$originalEmploymentFee}, 계산후={$employmentFee} (0.43902 곱하고 원단위 절사)\n", FILE_APPEND);
                
                // 데이터 배열에 추가
                $data[] = [
                    'statement_date' => $fullDate,
                    'resident_id' => $residentId,
                    'name' => $name,
                    'employment_insurance' => $employmentFee  // 고용보험료
                ];
            }
            
            // 임시 파일 삭제
            @unlink($tempFile);
            
            // 샘플 데이터 로깅
            if (count($data) > 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 데이터 샘플: " . json_encode(array_slice($data, 0, 2), JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 총 데이터 수: " . count($data) . "\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 추출된 데이터가 없음\n", FILE_APPEND);
                return [
                    'processed' => 0,
                    'error' => '파일에서 유효한 데이터를 추출할 수 없습니다.'
                ];
            }
            
            // 데이터베이스에 저장
            foreach ($data as $item) {
                try {
                    $result = SocialInsurance::updateOrCreate(
                        [
                            'statement_date' => $item['statement_date'],
                            'name' => $item['name']
                        ],
                        $item
                    );
                    
                    $count++;
                } catch (\Exception $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - DB 업데이트 오류: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 처리 완료: {$count}개 처리됨\n", FILE_APPEND);
            return [
                'processed' => $count,
                'error' => null
            ];
            
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - 고용보험 처리 오류: " . $e->getMessage() . "\n", FILE_APPEND);
            Log::emergency('고용보험 처리 오류: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'processed' => $count,
                'error' => '고용보험 파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ];
        }
    }
} 