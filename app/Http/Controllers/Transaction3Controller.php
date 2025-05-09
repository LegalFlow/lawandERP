<?php

namespace App\Http\Controllers;

use App\Models\Transaction3;
use App\Models\Member;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Transaction3Controller extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction3::query();
        
        // amount가 null이거나 0인 데이터 제외
        $query->where(function($q) {
            $q->whereNotNull('amount')
              ->where('amount', '>', 0);
        });
        
        // 송달환급금 등 필터 (기본값: true)
        $excludeRefunds = $request->input('exclude_refunds', true);
        if ($excludeRefunds) {
            $query->where(function($q) {
                $q->whereRaw("REPLACE(description, ' ', '') NOT LIKE '%법무법인로앤%'")
                  ->whereRaw("description NOT REGEXP '[0-9]{4}'")
                  ->whereRaw("REPLACE(description, ' ', '') NOT LIKE '%카드자동집금%'");
            });
        }

        // 기간 필터
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        } else {
            $query->whereBetween('date', [
                now()->startOfMonth()->format('Y-m-d'),
                now()->format('Y-m-d')
            ]);
        }

        // 금액 필터
        if ($request->filled('amount')) {
            $query->where('amount', $request->amount);
        }

        // 고객명 필터
        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        // 계정 필터
        if ($request->filled('account')) {
            if ($request->account === 'none') {
                $query->whereNull('account');
            } else {
                $query->where('account', $request->account);
            }
        }

        // 담당자 필터
        if ($request->filled('manager')) {
            if ($request->manager === 'none') {
                $query->whereNull('manager');
            } else {
                $query->where('manager', $request->manager);
            }
        }

        // 메모 필터 추가
        if ($request->filled('memo')) {
            $query->where('memo', 'like', '%' . $request->memo . '%');
        }

        // 통계 데이터 계산
        $statistics = [
            'total_count' => $query->count(),
            'service_sales' => $query->clone()->where('account', '서비스매출')->sum('amount'),
            'songinbu' => $query->clone()->where('account', '송인부')->sum('amount'),
        ];

        $transactions = $query->orderBy('date', 'desc')
                             ->orderBy('time', 'desc')
                             ->paginate(15);
        $members = Member::all();

        return view('transactions3.index', compact('transactions', 'members', 'statistics'));
    }

public function store(Request $request)
{
    $transactions = $request->all();
    $inserted = 0;
    $duplicates = 0;
    
    foreach ($transactions as $transaction) {
        try {
            Transaction3::create([
                'date' => $transaction['date'],
                'time' => $transaction['time'],
                'amount' => $transaction['amount'],
                'payment' => $transaction['payment'],
                'description' => $transaction['description']
            ]);
            $inserted++;
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {  // Duplicate entry error
                $duplicates++;
                continue;
            }
            throw $e;
        }
    }
    
    return response()->json([
        'message' => '거래내역 처리 완료',
        'inserted' => $inserted,
        'duplicates' => $duplicates
    ]);
}
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'account' => 'nullable|in:서비스매출,송인부',
            'manager' => 'nullable|exists:members,name',
            'memo' => 'nullable|string',
            'cash_receipt' => 'nullable|boolean',
        ]);

        $transaction = Transaction3::findOrFail($id);
        $transaction->update($validatedData);

        if ($request->has('cash_receipt')) {
            // 명시적으로 $request->input('cash_receipt') 값을 사용
            $cashReceiptStatus = $request->input('cash_receipt');
            $transaction->cash_receipt = $cashReceiptStatus;
            $transaction->save();
            
            return response()->json([
                'success' => true,
                'message' => $cashReceiptStatus ? '현금영수증이 발행완료로 변경되었습니다.' : '현금영수증이 미발행으로 변경되었습니다.',
                'cash_receipt' => $cashReceiptStatus
            ]);
        }

        return redirect()->back()->with('success', '업데이트되었습니다.');
    }

    public function export(Request $request)
    {
        try {
            $query = Transaction3::query();
            
            // 현금영수증 미발행이면서 서비스매출인 데이터만 필터링
            $query->where('cash_receipt', 0)
                  ->where('account', '서비스매출');
            
            // 기본 필터 적용 (기존 필터 로직 유지)
            // 송달환급금 등 필터 (기본값: true)
            $excludeRefunds = $request->input('exclude_refunds', true);
            if ($excludeRefunds) {
                $query->where(function($q) {
                    $q->whereRaw("REPLACE(description, ' ', '') NOT LIKE '%법무법인로앤%'")
                      ->whereRaw("description NOT REGEXP '[0-9]{4}'")
                      ->whereRaw("REPLACE(description, ' ', '') NOT LIKE '%카드자동집금%'");
                });
            }

            // 기간 필터
            if ($request->filled(['start_date', 'end_date'])) {
                $query->whereBetween('date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereBetween('date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->format('Y-m-d')
                ]);
            }

            // 고객명 필터
            if ($request->filled('description')) {
                $query->where('description', 'like', '%' . $request->description . '%');
            }

            // 메모 필터
            if ($request->filled('memo')) {
                $query->where('memo', 'like', '%' . $request->memo . '%');
            }

            // 데이터 조회
            $transactions = $query->orderBy('date', 'desc')
                                 ->orderBy('time', 'desc')
                                 ->get();

            // 새로운 Spreadsheet 객체 생성
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // 헤더 설정
            $headers = [
                'H', 
                '거래일자 당일/전일 (텍스트)', 
                '상품명 (텍스트)', 
                '공급가액 (숫자)', 
                '부가세 (숫자)', 
                '봉사료 (숫자)', 
                '거래총액 (숫자)', 
                '거래자구분 0:소득공제용 1:지출증빙용 2:도서공연비 (숫자)', 
                '주민번호/핸드폰/사업자번호 (\'-\' 제외하고 숫자만 입력하되 설치식은 텍스트로 설정)', 
                '상점연락처 (\'-\' 포함하여 숫자로 입력)'
            ];
            $sheet->fromArray([$headers], null, 'A1');

            // 데이터 입력
            $row = 2;
            foreach ($transactions as $transaction) {
                // 공급가액 계산 (amount ÷ 1.1) - 소수점 이하 버림
                $supplyAmount = floor($transaction->amount / 1.1);
                
                // 부가세 계산 (거래총액 - 공급가액)
                $vat = $transaction->amount - $supplyAmount;
                
                // 날짜 포맷 변경 (YYYY-MM-DD -> YYYYMMDD)
                $dateOnly = substr($transaction->date, 0, 10); // 날짜 부분만 추출 (YYYY-MM-DD)
                $formattedDate = str_replace('-', '', $dateOnly); // 하이픈 제거
                
                // 데이터 입력
                $sheet->setCellValueExplicit('A' . $row, 'D', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $row, $formattedDate);
                $sheet->setCellValueExplicit('C' . $row, '사건위임', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('D' . $row, $supplyAmount);
                $sheet->setCellValue('E' . $row, $vat);
                $sheet->setCellValue('F' . $row, 0);
                $sheet->setCellValue('G' . $row, $transaction->amount);
                $sheet->setCellValue('H' . $row, 0);
                $sheet->setCellValueExplicit('I' . $row, '0100001234', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('J' . $row, '02-0000-0000', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                
                $row++;
            }

            // 열 너비 자동 조정
            foreach(range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // 헤더 행 스타일 설정
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);

            // 파일명 생성
            $filename = '현금영수증_부산_' . now()->format('Y-m-d') . '.xls';

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

        } catch (\Exception $e) {
            \Log::error('Excel 생성 중 오류 발생: ' . $e->getMessage());
            return back()->with('error', 'Excel 파일 다운로드 중 오류가 발생했습니다.');
        }
    }
}