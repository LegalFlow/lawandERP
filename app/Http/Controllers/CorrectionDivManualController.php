<?php

namespace App\Http\Controllers;

use App\Models\CorrectionDiv;
use App\Models\Member;
use Illuminate\Http\Request;

class CorrectionDivManualController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::orderBy('name')->get();
        $query = CorrectionDiv::whereNull('order');  // 직접입력 데이터만 조회

        // 수신여부 필터
        if ($request->has('unconfirmed_only') && $request->unconfirmed_only) {
            $query->where('receipt_status', '미확인');
        }

        // 날의 사건만 보기 필터
        if ($request->has('my_cases_only') && $request->my_cases_only) {
            $query->where('case_manager', auth()->user()->name);
        }

        // 날짜 필터
        if ($request->date_type && $request->start_date && $request->end_date) {
            $dateColumn = match($request->date_type) {
                'receipt_date' => 'receipt_date',
                'summit_date' => 'summit_date',
                'deadline' => 'deadline',
                default => 'shipment_date'
            };
            $query->whereBetween($dateColumn, [$request->start_date, $request->end_date]);
            $query->orderBy($dateColumn, 'desc');
        } else {
            // 날짜 필터가 없을 때는 기본적으로 발송일자 내림차순 정렬
            $query->orderBy('shipment_date', 'desc');
        }

        // 텍스트 검색
        if ($request->search_text) {
            $query->where(function($q) use ($request) {
                $q->where('court_name', 'like', "%{$request->search_text}%")
                  ->orWhere('case_number', 'like', "%{$request->search_text}%")
                  ->orWhere('name', 'like', "%{$request->search_text}%")
                  ->orWhere('document_name', 'like', "%{$request->search_text}%");
            });
        }

        // 문서 분류 필터 추가
        if ($request->document_type && $request->document_type !== '선택없음') {
            $query->where('document_type', $request->document_type);
        }

        // 상담자/담당자 필터
        if ($request->consultant) {
            $query->where('consultant', $request->consultant);
        }
        if ($request->case_manager) {
            if ($request->case_manager === 'none') {
                $query->where(function($q) {
                    $q->whereNull('case_manager')
                      ->orWhere('case_manager', '');
                });
            } else {
                $query->where('case_manager', $request->case_manager);
            }
        }

        // 제출여부 필터
        if ($request->submission_status) {
            $query->where('submission_status', $request->submission_status);
        }

        $correctionDivs = $query->paginate(15);

        return view('correction_div_manual.index', [
            'correctionDivs' => $correctionDivs,
            'submissionStatuses' => [
                '미제출',
                '제출완료',
                '안내완료',
                '처리완료',
                '연기신청',
                '제출불요',
                '계약해지',
                '연락두절'
            ],
            'documentTypes' => [
                '선택없음',
                '명령',
                '기타',
                '보정',
                '예외'
            ],
            'members' => $members,
            'filters' => $request->all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipment_date' => 'required|date',
            'receipt_date' => 'nullable|date',
            'court_name' => 'nullable|string|max:50',
            'case_number' => 'required|string|max:50',
            'name' => 'nullable|string|max:50',
            'document_name' => 'required|string|max:50',
            'document_type' => 'nullable|in:선택없음,명령,기타,보정,예외',
            'consultant' => 'nullable|exists:members,name',
            'case_manager' => 'nullable|exists:members,name',
            'deadline' => 'nullable|date',
            'submission_status' => 'nullable|in:미제출,제출완료,안내완료,처리완료,연기신청,제출불요,계약해지,연락두절',
            'summit_date' => 'nullable|date'
        ]);

        // 기본값 설정
        $validated['document_type'] = $validated['document_type'] ?? '선택없음';
        $validated['submission_status'] = $validated['submission_status'] ?? '미제출';

        try {
            CorrectionDiv::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => '보정서가 성공적으로 등록되었습니다.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $correctionDiv = CorrectionDiv::findOrFail($id);
        $correctionDiv->delete();

        return response()->json(['success' => true]);
    }

    public function download(Request $request)
    {
        try {
            \Log::info('Download requested with path parameter: ' . $request->path);
            
            // base64 디드 후 URL 디코딩
            $decoded_path = urldecode(base64_decode($request->path));
            \Log::info('Decoded path: ' . $decoded_path);
            
            // URL 형식의 경로를 실제 서버 경로로 변환
            $real_path = str_replace('/download/', '/home/ec2-user/rbdocs/', $decoded_path);
            \Log::info('Real path: ' . $real_path);
            
            if (!file_exists($real_path)) {
                \Log::error('File not found at path: ' . $real_path);
                return response()->json(['error' => '파일을 찾을 수 없습니다.'], 404);
            }

            // 파일명 추출
            $filename = basename($real_path);
            
            \Log::info('File exists, attempting to download: ' . $filename);
            return response()->file($real_path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
            ]);
        } catch (\Exception $e) {
            \Log::error('File download error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => '파일 다운로드 중 오류가 발생했습니다.'], 500);
        }
    }

    public function updateMemo(Request $request, $id)
    {
        try {
            $correctionDiv = CorrectionDiv::findOrFail($id);
            
            $validated = $request->validate([
                'memo' => 'nullable|string|max:65535'  // TEXT 필드 최대 길이
            ]);

            $correctionDiv->update([
                'command' => $validated['memo']
            ]);

            return response()->json([
                'success' => true,
                'message' => '메모가 성공적으로 저장되었습니다.',
                'memo' => $correctionDiv->command
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '메모 저장 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            \Log::info('Update request received:', [
                'id' => $id,
                'field' => $request->field,
                'value' => $request->value,
                'all_data' => $request->all()
            ]);
            
            $correction = CorrectionDiv::findOrFail($id);
            
            $validFields = ['document_type', 'deadline', 'submission_status', 'summit_date'];
            
            if (!in_array($request->field, $validFields)) {
                \Log::warning('Invalid field attempted to update', [
                    'field' => $request->field,
                    'valid_fields' => $validFields
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => '잘못된 필드입니다.'
                ], 400);
            }
            
            // null 값 처리
            $value = $request->value !== '' ? $request->value : null;
            
            // 업데이트 시도
            $correction->{$request->field} = $value;
            $correction->save();
            
            \Log::info('Update successful', [
                'id' => $id,
                'field' => $request->field,
                'value' => $value
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            \Log::error('Update error:', [
                'id' => $id,
                'field' => $request->field ?? 'unknown',
                'value' => $request->value ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
