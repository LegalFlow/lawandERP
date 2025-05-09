<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query();
        
        // 기간 필터
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        } else {
            $query->whereBetween('payment_date', [
                now()->startOfMonth()->format('Y-m-d'),
                now()->format('Y-m-d')
            ]);
        }

        // 결제금액 필터
        if ($request->filled('payment_amount')) {
            $query->where('payment_amount', $request->payment_amount);
        }

        // 고객명 필터
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
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

        // 결제상태 필터
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // 메모 필터
        if ($request->filled('memo')) {
            $query->where('memo', 'like', '%' . $request->memo . '%');
        }

        // 노트 필터
        if ($request->filled('note')) {
            $query->where('note', 'like', '%' . $request->note . '%');
        }

        // 통계 데이터 계산
        $statistics = [
            'total_count' => $query->count(),
            'service_sales' => $query->clone()->where('account', '서비스매출')->sum('payment_amount'),
            'songinbu' => $query->clone()->where('account', '송인부')->sum('payment_amount'),
        ];

        $payments = $query->orderBy('payment_date', 'desc')->paginate(15);
        $members = \App\Models\Member::all();

        return view('payments.index', compact('payments', 'members', 'statistics'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $inserted = 0;
            $skipped = 0;

            foreach ($data as $payment) {
                try {
                    Payment::create([
                        'name' => $payment['name'],
                        'payment_date' => $payment['payment_date'],
                        'payment_status' => $payment['payment_status'],
                        'payment_amount' => $payment['payment_amount'],
                        'cancel_amount' => $payment['cancel_amount'],
                        'cancel_date' => $payment['cancel_date'],
                        'memo' => $payment['memo'],
                        'manager' => $payment['manager']
                    ]);
                    $inserted++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) {  // Duplicate entry error
                        $skipped++;
                        continue;
                    }
                    throw $e;
                }
            }
            
            return response()->json([
                'message' => '처리가 완료되었습니다.',
                'inserted' => $inserted,
                'skipped' => $skipped
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'inserted' => $inserted ?? 0,
                'skipped' => $skipped ?? 0
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        
        $validatedData = $request->validate([
            'account' => 'nullable|in:서비스매출,송인부',
            'note' => 'nullable|string',
            'manager' => 'nullable|exists:members,name',
            'cash_receipt' => 'nullable|boolean'
        ]);

        $payment->update($validatedData);

        if ($request->has('cash_receipt')) {
            // 명시적으로 $request->input('cash_receipt') 값을 사용
            $cashReceiptStatus = $request->input('cash_receipt');
            $payment->cash_receipt = $cashReceiptStatus;
            $payment->save();
            
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
            $query = Payment::query();
            
            // 현금영수증 미발행이면서 서비스매출인 데이터만 필터링
            $query->where('cash_receipt', 0)
                  ->where('account', '서비스매출');
            
            // 기간 필터
            if ($request->filled(['start_date', 'end_date'])) {
                $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereBetween('payment_date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->format('Y-m-d')
                ]);
            }

            // 고객명 필터
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // 메모 필터
            if ($request->filled('memo')) {
                $query->where('memo', 'like', '%' . $request->memo . '%');
            }

            // 노트 필터
            if ($request->filled('note')) {
                $query->where('note', 'like', '%' . $request->note . '%');
            }

            // 데이터 조회
            $payments = $query->orderBy('payment_date', 'desc')->get();

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
            foreach ($payments as $payment) {
                // 공급가액 계산 (amount ÷ 1.1) - 소수점 이하 버림
                $supplyAmount = floor($payment->payment_amount / 1.1);
                
                // 부가세 계산 (거래총액 - 공급가액)
                $vat = $payment->payment_amount - $supplyAmount;
                
                // 날짜 포맷 변경 (YYYY-MM-DD -> YYYYMMDD)
                $dateOnly = substr($payment->payment_date, 0, 10); // 날짜 부분만 추출 (YYYY-MM-DD)
                $formattedDate = str_replace('-', '', $dateOnly); // 하이픈 제거
                
                // 데이터 입력
                $sheet->setCellValueExplicit('A' . $row, 'D', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $row, $formattedDate);
                $sheet->setCellValueExplicit('C' . $row, '사건위임', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('D' . $row, $supplyAmount);
                $sheet->setCellValue('E' . $row, $vat);
                $sheet->setCellValue('F' . $row, 0);
                $sheet->setCellValue('G' . $row, $payment->payment_amount);
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
            $filename = '현금영수증_CMS_' . now()->format('Y-m-d') . '.xls';

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