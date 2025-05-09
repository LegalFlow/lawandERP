<?php

namespace App\Http\Controllers;

use App\Models\IncomeEntry; // IncomeEntry 모델을 임포트
use App\Models\Member; // 구성원 목록을 불러오기 위해 Member 모델 임포트
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = IncomeEntry::query();

        // 기간 필터 (기본값: 이번 달 1일부터 오늘까지)
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $query->whereBetween('deposit_date', [$startDate, $endDate]);

        // 금액 필터
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // 고객명 필터
        if ($request->filled('depositor_name')) {
            $query->where('depositor_name', 'like', '%' . $request->depositor_name . '%');
        }

        // 계정 필터
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        // 담당자 필터
        if ($request->filled('representative_id')) {
            $query->where('representative_id', $request->representative_id);
        }

        // 메모 필터 추가
        if ($request->filled('memo')) {
            $query->where('memo', 'like', '%' . $request->memo . '%');
        }

        $incomeEntries = $query
            ->orderBy('deposit_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        $representatives = \App\Models\Member::all();
        
        return view('income_entries.index', compact(
            'incomeEntries', 
            'representatives',
            'startDate',
            'endDate'
        ));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $representatives = \App\Models\Member::all(); // 구성원 목록
        return view('income_entries.create', compact('representatives'));
    }
    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 금액의 쉼표 제거 및 마이너스 처리
        if ($request->has('amount')) {
            $amount = $request->amount;
            $isNegative = strpos($amount, '-') === 0;
            $cleanAmount = str_replace([',', '-'], '', $amount);
            $request->merge(['amount' => $isNegative ? -(int)$cleanAmount : (int)$cleanAmount]);
        }

        $validatedData = $request->validate([
            'deposit_date' => 'required|date',
            'depositor_name' => 'required|string|max:255',
            'amount' => 'required|integer',
            'representative_id' => 'required|exists:members,id',
            'account_type' => 'required|in:서비스매출,송인부',
            'memo' => 'nullable|string',
        ]);

        IncomeEntry::create($validatedData);

        return redirect()->route('income_entries.index')->with('success', '매출 항목이 추가되었습니다.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $incomeEntry = IncomeEntry::findOrFail($id);
        return response()->json($incomeEntry);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 금액의 쉼표 제거 및 마이너스 처리
        if ($request->has('amount')) {
            $amount = $request->amount;
            $isNegative = strpos($amount, '-') === 0;
            $cleanAmount = str_replace([',', '-'], '', $amount);
            $request->merge(['amount' => $isNegative ? -(int)$cleanAmount : (int)$cleanAmount]);
        }

        $validatedData = $request->validate([
            'deposit_date' => 'required|date',
            'depositor_name' => 'required|string|max:255',
            'amount' => 'required|integer',
            'representative_id' => 'required|exists:members,id',
            'account_type' => 'required|in:서비스매출,송인부',
            'memo' => 'nullable|string',
        ]);

        $incomeEntry = IncomeEntry::findOrFail($id);
        $incomeEntry->update($validatedData);

        return redirect()->route('income_entries.index', ['page' => $request->current_page])
                        ->with('success', '매출 항목이 성공적으로 수정되었습니다.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
    $incomeEntry = IncomeEntry::findOrFail($id);
    $incomeEntry->delete();

    return redirect()->route('income_entries.index')->with('success', '매출 항목이 삭제되었습니다.');
    }

    public function export(Request $request)
    {
        try {
            $query = IncomeEntry::query();

            // 기간 필터
            if ($request->filled(['start_date', 'end_date'])) {
                $query->whereBetween('deposit_date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereBetween('deposit_date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->format('Y-m-d')
                ]);
            }

            // 금액 필터
            if ($request->filled('min_amount')) {
                $query->where('amount', '>=', $request->min_amount);
            }
            if ($request->filled('max_amount')) {
                $query->where('amount', '<=', $request->max_amount);
            }

            // 고객명 필터
            if ($request->filled('depositor_name')) {
                $query->where('depositor_name', 'like', '%' . $request->depositor_name . '%');
            }

            // 계정 필터
            if ($request->filled('account_type')) {
                $query->where('account_type', $request->account_type);
            }

            // 담당자 필터
            if ($request->filled('representative_id')) {
                $query->where('representative_id', $request->representative_id);
            }

            $filename = '매출직접입력_' . now()->format('Y-m-d') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            return response()->stream(function() use ($query) {
                $handle = fopen('php://output', 'w');
                
                // UTF-8 BOM 추가
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                // 헤더 행 작성
                fputcsv($handle, [
                    '입금일자',
                    '입금자명',
                    '입금액',
                    '담당자',
                    '계정',
                    '메모'
                ]);

                // 데이터 청크 단위로 처리
                $query->with('representative')
                      ->orderBy('deposit_date', 'desc')
                      ->chunk(1000, function($entries) use ($handle) {
                    foreach ($entries as $entry) {
                        fputcsv($handle, [
                            $entry->deposit_date,
                            $entry->depositor_name,
                            number_format($entry->amount),
                            $entry->representative->name ?? '',
                            $entry->account_type ?? '',
                            $entry->memo ?? ''
                        ]);
                    }
                });

                fclose($handle);
            }, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('CSV 생성 중 오류 발생: ' . $e->getMessage());
            return back()->with('error', 'CSV 파일 다운로드 중 오류가 발생했습니다.');
        }
    }

    public function storeSongInBu(Request $request)
    {
        // 금액의 쉼표 제거 및 마이너스 처리
        if ($request->has('amount')) {
            $amount = $request->amount;
            $isNegative = strpos($amount, '-') === 0;
            $cleanAmount = str_replace([',', '-'], '', $amount);
            $request->merge(['amount' => $isNegative ? -(int)$cleanAmount : (int)$cleanAmount]);
        }

        $validatedData = $request->validate([
            'deposit_date' => 'required|date',
            'depositor_name' => 'required|string|max:255',
            'amount' => 'required|integer',
            'representative_id' => 'required|exists:members,id',
            'memo' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validatedData) {
                // 서비스매출 레코드 생성 (음수 금액)
                IncomeEntry::create([
                    'deposit_date' => $validatedData['deposit_date'],
                    'depositor_name' => $validatedData['depositor_name'],
                    'amount' => -$validatedData['amount'],  // 음수로 변경
                    'representative_id' => $validatedData['representative_id'],
                    'account_type' => '서비스매출',
                    'memo' => $validatedData['memo']
                ]);

                // 송인부 레코드 생성 (양수 금액)
                IncomeEntry::create([
                    'deposit_date' => $validatedData['deposit_date'],
                    'depositor_name' => $validatedData['depositor_name'],
                    'amount' => $validatedData['amount'],  // 양수 그대로 유지
                    'representative_id' => $validatedData['representative_id'],
                    'account_type' => '송인부',
                    'memo' => $validatedData['memo']
                ]);
            });

            return redirect()->route('income_entries.index')->with('success', '송인부처리가 완료되었습니다.');
        } catch (\Exception $e) {
            return redirect()->route('income_entries.index')->with('error', '송인부처리 중 오류가 발생했습니다.');
        }
    }

}
