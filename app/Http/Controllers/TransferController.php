<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\TransferFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Facades\Storage;

class TransferController extends Controller
{
    public function index(Request $request)
    {
        $query = Transfer::with('files');
        
        // show_all 파라미터가 없으면 기본 필터 적용
        if (!$request->has('show_all')) {
            $query->where(function($q) {
                $q->where('approval_status', '승인대기')
                  ->orWhere('payment_status', '납부대기');
            });
        }

        // 날짜 필터링 (등록일자)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // 납부유형 필터링
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        
        // 납부대상 필터링
        if ($request->filled('payment_target')) {
            $query->where('payment_target', $request->payment_target);
        }
        
        // 담당자 필터링
        if ($request->filled('manager')) {
            $query->where('manager', $request->manager);
        }
        
        // 승인상태 필터링
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }
        
        // 납부상태 필터링
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // 검색어 필터링
        if ($request->filled('search_term') && $request->filled('search_field')) {
            $searchTerm = $request->search_term;
            $searchField = $request->search_field;
            
            // 필드별 검색
            $query->where($searchField, 'like', "%{$searchTerm}%");
        }
        
        // 금액 범위 필터링
        if ($request->filled('amount_from')) {
            $query->where('payment_amount', '>=', $request->amount_from);
        }
        
        if ($request->filled('amount_to')) {
            $query->where('payment_amount', '<=', $request->amount_to);
        }
        
        $transfers = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString(); // 페이지네이션 시 현재 쿼리스트링 유지
            
        $members = \App\Models\Member::orderBy('name')->get(['id', 'name']);

        // 승인완료되고 납부대기 상태인 이체요청들의 총액 계산
        $totalPendingAmount = Transfer::where('approval_status', '승인완료')
            ->where('payment_status', '납부대기')
            ->sum('payment_amount');

        return view('transfers.index', compact('transfers', 'members', 'totalPendingAmount'));
    }

    public function store(Request $request)
    {
        \Log::info('File upload request received', [
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'all_data' => $request->all()
        ]);

        $validated = $request->validate([
            'payment_type' => 'required|in:' . implode(',', Transfer::PAYMENT_TYPES),
            'payment_target' => 'required|in:' . implode(',', Transfer::PAYMENT_TARGETS),
            'client_name' => 'required|string',
            'court_name' => 'nullable|string',
            'case_number' => 'nullable|string',
            'consultant' => 'nullable|string',
            'manager' => 'nullable|string',
            'virtual_account' => 'nullable|string|regex:/^[0-9]+$/',
            'payment_amount' => 'required|numeric|min:0',
            'memo' => 'nullable|string',
            'bank' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        DB::beginTransaction();
        try {
            // payment_amount에서 쉼표 제거
            if (isset($validated['payment_amount'])) {
                $validated['payment_amount'] = str_replace(',', '', $validated['payment_amount']);
            }

            $transfer = Transfer::create([
                ...$validated,
                'created_by' => Auth::id(),
                'payment_status' => '납부대기',
                'approval_status' => '승인대기'
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    try {
                        // 파일이 유효한지 먼저 확인
                        if (!$file->isValid()) {
                            throw new \Exception('Invalid file upload');
                        }

                        // 파일 유효성 검사
                        TransferFile::validateFile($file);
                        
                        // 파일명 생성
                        $fileName = time() . '_' . uniqid() . '.pdf';
                        
                        // 파일 저장 디렉토리 확인 및 생성
                        $uploadPath = TransferFile::STORAGE_PATH;
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }

                        // 파일 이동
                        if ($file->move($uploadPath, $fileName)) {
                            // DB 레코드 생성
                            TransferFile::create([
                                'transfer_id' => $transfer->id,
                                'original_name' => $file->getClientOriginalName(),
                                'stored_name' => $fileName,
                                'file_path' => $fileName,
                                'file_size' => filesize($uploadPath . '/' . $fileName),
                            ]);
                        } else {
                            throw new \Exception('Failed to move uploaded file');
                        }

                    } catch (\Exception $e) {
                        \Log::error('File upload failed', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                }
            }

            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '이체요청이 등록되었습니다.',
                    'redirect' => route('transfers.index')
                ]);
            }

            return redirect()->route('transfers.index')->with('success', '이체요청이 등록되었습니다.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '이체요청 등록에 실패했습니다: ' . $e->getMessage()
                ], 400);
            }

            return back()->with('error', '이체요청 등록에 실패했습니다.');
        }
    }

    public function show(Transfer $transfer)
    {
        $transfer->load('files');
        $members = \App\Models\Member::orderBy('name')->get(['id', 'name']);
        
        return view('transfers.show', compact('transfer', 'members'));
    }

    public function update(Request $request, Transfer $transfer)
    {
        \Log::info('File update request received', [
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'all_data' => $request->all()
        ]);

        if ($transfer->payment_status === '납부완료') {
            return back()->with('error', '납부완료된 이체요청은 수정할 수 없습니다.');
        }

        // 금액에서 쉼표 제거
        $request->merge([
            'payment_amount' => str_replace(',', '', $request->payment_amount)
        ]);

        $validated = $request->validate([
            'payment_type' => 'required|string',
            'payment_target' => 'required|string',
            'client_name' => 'required|string',
            'court_name' => 'nullable|string',
            'case_number' => 'nullable|string',
            'consultant' => 'nullable|string',
            'manager' => 'required|string',
            'virtual_account' => 'nullable|string',
            'payment_amount' => 'required|numeric|min:0',
            'memo' => 'nullable|string',
            'bank' => 'nullable|string',
            'payment_status' => 'required|string',
            'payment_completed_at' => 'nullable|date',
            'approval_status' => 'required|string',
            'files.*' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        DB::beginTransaction();
        try {
            $transfer->update([
                ...$validated,
                'updated_by' => Auth::id(),
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    try {
                        // 파일이 유효한지 먼저 확인
                        if (!$file->isValid()) {
                            throw new \Exception('Invalid file upload');
                        }

                        \Log::info('Processing uploaded file', [
                            'original_name' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'error' => $file->getError(),
                            'path' => $file->getRealPath()
                        ]);

                        // 파일 유효성 검사
                        TransferFile::validateFile($file);
                        
                        // 파일명 생성 (더 안전한 방식으로 변경)
                        $extension = $file->getClientOriginalExtension();
                        $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
                        
                        // 파일 이동 전에 디렉토리 확인 및 생성
                        $uploadPath = TransferFile::STORAGE_PATH;
                        if (!file_exists($uploadPath)) {
                            mkdir($uploadPath, 0755, true);
                        }

                        // 파일 이동 시도
                        if ($file->move($uploadPath, $fileName)) {
                            // DB 레코드 생성
                            TransferFile::create([
                                'transfer_id' => $transfer->id,
                                'original_name' => $file->getClientOriginalName(),
                                'stored_name' => $fileName,
                                'file_path' => $fileName,
                                'file_size' => filesize($uploadPath . '/' . $fileName), // 실제 저장된 파일의 크기를 확인
                            ]);

                            \Log::info('File upload successful', [
                                'original_name' => $file->getClientOriginalName(),
                                'stored_name' => $fileName,
                                'final_path' => $uploadPath . '/' . $fileName
                            ]);
                        } else {
                            throw new \Exception('Failed to move uploaded file');
                        }

                    } catch (\Exception $e) {
                        \Log::error('File upload failed', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => '이체요청이 수정되었습니다.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => '이체요청 수정에 실패했습니다: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Transfer $transfer)
    {
        $transfer->update([
            'del_flag' => true,
            'updated_by' => Auth::id()
        ]);

        return redirect()->route('transfers.index')->with('success', '이체요청이 삭제되었습니다.');
    }

    public function updateApprovalStatus(Transfer $transfer)
    {
        if ($transfer->approval_status === '승인대기') {
            $transfer->update([
                'approval_status' => '승인완료',
                'approved_at' => now(),
                'approved_by' => Auth::id()
            ]);
            return back()->with('success', '승인이 완료되었습니다.');
        }
        return back()->with('error', '이미 승인된 이체요청입니다.');
    }

    public function downloadExcel()
    {
        // 승인완료이면서 납부대기인 데이터만 조회
        $transfers = Transfer::where('approval_status', '승인완료')
            ->where('payment_status', '납부대기')
            ->where('del_flag', false)
            ->get();

        // bankcode.csv 파일 읽기
        $bankCodes = [];
        $handle = fopen(resource_path('views/transfers/bankcode.csv'), 'r');
        while (($data = fgetcsv($handle)) !== false) {
            $bankCodes[$data[1]] = $data[0];
        }
        fclose($handle);

        // 새로운 Spreadsheet 객체 생성
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 헤더 설정
        $headers = ['입금은행', '입금계좌', '고객관리성명', '입금액', '출금통장표시내용', '입금통장표시내용', '입금인코드', '비고', '업체사용key'];
        $sheet->fromArray([$headers], null, 'A1');

        // 데이터 입력
        $row = 2;
        foreach ($transfers as $transfer) {
            $sheet->setCellValueExplicit('A' . $row, $bankCodes[$transfer->bank] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $transfer->virtual_account, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, $transfer->client_name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, (string)intval($transfer->payment_amount), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $row, $transfer->client_name . ' ' . $transfer->payment_target, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('I' . $row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            // 모든 셀의 서식을 텍스트로 설정
            $sheet->getStyle('A'.$row.':I'.$row)->getNumberFormat()->setFormatCode('@');
            
            $row++;
        }

        // 파일명 생성
        $filename = '이체요청_' . now()->format('Ymd_His') . '.xls';

        // 엑셀 파일 생성
        $writer = new Xls($spreadsheet);
        
        // 헤더 설정
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // 출력 버퍼 초기화
        ob_end_clean();

        // 파일 출력
        $writer->save('php://output');
        exit;
    }

    public function updatePaymentStatus(Transfer $transfer)
    {
        if ($transfer->payment_status === '납부대기') {
            $transfer->update([
                'payment_status' => '납부완료',
                'payment_completed_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => '납부완료 처리되었습니다.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => '이미 납부완료된 이체요청입니다.'
        ], 400);
    }

    private function getSearchResults($searchKey, $startDate)
    {
        $query = [];
        
        // transactions 테이블 쿼리
        $transactions = DB::table('transactions')
            ->select([
                'date as deposit_date',
                DB::raw("'서울신한' as bank_account"),
                'amount',
                'description as client_name',
                'account',
                'manager',
                'memo',
            ])
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        if ($startDate) {
            $transactions->where('date', '>=', $startDate);
        }
        $query[] = $transactions;

        // transactions2 테이블 쿼리
        $transactions2 = DB::table('transactions2')
            ->select([
                'date as deposit_date',
                DB::raw("'대전신한' as bank_account"),
                'amount',
                'description as client_name',
                'account',
                'manager',
                'memo',
            ])
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        if ($startDate) {
            $transactions2->where('date', '>=', $startDate);
        }
        $query[] = $transactions2;

        // transactions3 테이블 쿼리
        $transactions3 = DB::table('transactions3')
            ->select([
                'date as deposit_date',
                DB::raw("'부산신한' as bank_account"),
                'amount',
                'description as client_name',
                'account',
                'manager',
                'memo',
            ])
            ->where(function($q) use ($searchKey) {
                $q->where('description', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        if ($startDate) {
            $transactions3->where('date', '>=', $startDate);
        }
        $query[] = $transactions3;

        // payments 테이블 쿼리
        $payments = DB::table('payments')
            ->select([
                'payment_date as deposit_date',
                DB::raw("'CMS 카드' as bank_account"),
                'payment_amount as amount',
                'name as client_name',
                'account',
                'manager',
                'note as memo',
            ])
            ->where(function($q) use ($searchKey) {
                $q->where('name', 'like', "%{$searchKey}%")
                  ->orWhere('note', 'like', "%{$searchKey}%")
                  ->orWhere('memo', 'like', "%{$searchKey}%");
            });
        if ($startDate) {
            $payments->where('payment_date', '>=', $startDate);
        }
        $query[] = $payments;

        // income_entries 테이블 쿼리
        $incomeEntries = DB::table('income_entries')
            ->join('members', 'income_entries.representative_id', '=', 'members.id')
            ->select([
                'deposit_date',
                DB::raw("'매출직접입력' as bank_account"),
                'amount',
                'depositor_name as client_name',
                'account_type as account',
                'members.name as manager',
                'income_entries.memo',
            ])
            ->where(function($q) use ($searchKey) {
                $q->where('depositor_name', 'like', "%{$searchKey}%")
                  ->orWhere('income_entries.memo', 'like', "%{$searchKey}%");
            });
        if ($startDate) {
            $incomeEntries->where('deposit_date', '>=', $startDate);
        }
        $query[] = $incomeEntries;

        // 모든 쿼리 결과를 합치고 정렬
        return collect($query)
            ->map(fn($q) => $q->get())
            ->flatten(1)
            ->sortByDesc('deposit_date')
            ->values();
    }

    public function getDepositHistory(Request $request, Transfer $transfer)
    {
        $months = $request->input('period', 1);
        $searchKey = mb_substr($transfer->client_name, 0, 3, 'UTF-8');
        $startDate = ($months === 'all') ? null : now()->subMonths((int)$months);
        
        $results = $this->getSearchResults($searchKey, $startDate);
        
        // 송인부 계정 입금액 합계 계산
        $totalSongInBuAmount = $results
            ->where('account', '송인부')
            ->sum('amount');
        
        return response()->json([
            'data' => $results,
            'status' => $totalSongInBuAmount >= $transfer->payment_amount ? 'green' : 'red',
            'totalAmount' => $totalSongInBuAmount,
            'requestAmount' => $transfer->payment_amount  // 납부해야 할 금액 추가
        ]);
    }

    public function updateErrorCode(Request $request, Transfer $transfer)
    {
        $validated = $request->validate([
            'error_code' => 'nullable|string|max:20'
        ]);

        $transfer->update([
            'error_code' => $validated['error_code']
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}