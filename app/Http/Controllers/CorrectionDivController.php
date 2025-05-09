<?php

namespace App\Http\Controllers;

use App\Models\CorrectionDiv;
use App\Models\Member;
use Illuminate\Http\Request;

class CorrectionDivController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::orderBy('name')->get();
        $query = CorrectionDiv::query();

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
            $query->orderBy($dateColumn, 'desc')
                  ->orderBy('case_number', 'desc');
        } else {
            // 날짜 필터가 없을 때는 기본적으로 발송일자 내림차순, 사건번호 내림차순 정렬
            $query->orderBy('shipment_date', 'desc')
                  ->orderBy('case_number', 'desc');
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
            } elseif ($request->case_manager === 'absent_today') {
                // 오늘 날짜의 데이터만 필터링
                $query->whereDate('receipt_date', today())
                      ->whereExists(function ($subquery) {
                          $subquery->from('work_hours')
                                  ->whereColumn('work_hours.member', 'correction_div.case_manager')
                                  ->whereColumn('work_hours.work_date', 'correction_div.receipt_date')
                                  ->whereIn('work_hours.status', ['연차', '휴무', '공휴일']);
                      });
            } elseif ($request->case_manager === 'resigned') {
                $query->whereNotNull('case_manager')
                      ->where('case_manager', '!=', '')
                      ->where(function($q) {
                          $q->whereExists(function($subquery) {
                              // members 테이블에 있고 재직 상태가 아닌 담당자
                              $subquery->from('members')
                                      ->whereColumn('members.name', 'correction_div.case_manager')
                                      ->where('members.status', '!=', '재직');
                          })->orWhereNotExists(function($subquery) {
                              // members 테이블에 없는 담당자
                              $subquery->from('members')
                                      ->whereColumn('members.name', 'correction_div.case_manager');
                          })->orWhereExists(function($subquery) {
                              // members 테이블에 있고 재직중이지만 사건관리팀이 아닌 담당자
                              $subquery->from('members')
                                      ->whereColumn('members.name', 'correction_div.case_manager')
                                      ->where('members.status', '재직')
                                      ->where('members.task', '!=', '사건관리팀');
                          });
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

        return view('correction_div.index', [
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

    private function logFieldChange($correctionDiv, $field, $oldValue, $newValue)
    {
        $logMessage = sprintf(
            "[%s] 사건번호: %s | 송달문서: %s | 발송일자: %s | 필드: %s | 이전값: %s | 변경값: %s | 변경자: %s",
            now()->format('Y-m-d H:i:s'),
            $correctionDiv->case_number,
            $correctionDiv->document_name,
            $correctionDiv->shipment_date,
            $field,
            $oldValue ?? '없음',
            $newValue ?? '없음',
            auth()->user()->name
        );
        
        \Log::channel('field_changes')->info($logMessage);
    }

    public function update(Request $request, CorrectionDiv $correctionDiv)
    {
        $data = $request->validate([
            'deadline' => 'nullable|date',
            'submission_status' => 'nullable|in:미제출,제출완료,안내완료,처리완료,연기신청,제출불요,계약해지,연락두절',
            'summit_date' => 'nullable|date',
            'document_type' => 'nullable|in:선택없음,명령,기타,보정,예외',
            'consultant' => 'nullable|exists:members,name',
            'case_manager' => 'nullable|exists:members,name'
        ]);

        // 변경사항 로깅
        foreach ($data as $field => $newValue) {
            $oldValue = $correctionDiv->$field;
            if ($oldValue !== $newValue) {
                $this->logFieldChange($correctionDiv, $field, $oldValue, $newValue);
            }
        }

        if (!empty($data['summit_date']) && empty($correctionDiv->summit_date)) {
            $data['submission_status'] = '제출완료';
        }

        $correctionDiv->update($data);
        return response()->json(['success' => true]);
    }

    public function download(Request $request)
    {
        try {
            \Log::info('Download requested with path parameter: ' . $request->path);
            
            // base64 디코딩 후 URL 디코딩 실행
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

    /**
     * 메모 업데이트
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMemo(Request $request, $id)
    {
        try {
            $correctionDiv = CorrectionDiv::findOrFail($id);
            
            $validated = $request->validate([
                'memo' => 'nullable|string|max:65535'
            ]);

            $oldMemo = $correctionDiv->command;
            
            // 메모 변경사항 로깅
            if ($oldMemo !== $validated['memo']) {
                $this->logFieldChange($correctionDiv, 'memo', $oldMemo, $validated['memo']);
            }

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

    public function export(Request $request)
    {
        try {
            $query = CorrectionDiv::query();

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

            // 문서 분류 필터
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
                } elseif ($request->case_manager === 'absent_today') {
                    // 오늘 날짜의 데이터만 필터링
                    $query->whereDate('receipt_date', today())
                          ->whereExists(function ($subquery) {
                              $subquery->from('work_hours')
                                      ->whereColumn('work_hours.member', 'correction_div.case_manager')
                                      ->whereColumn('work_hours.work_date', 'correction_div.receipt_date')
                                      ->whereIn('work_hours.status', ['연차', '휴무', '공휴일']);
                          });
                } elseif ($request->case_manager === 'resigned') {
                    $query->whereNotNull('case_manager')
                          ->where('case_manager', '!=', '')
                          ->where(function($q) {
                              $q->whereExists(function($subquery) {
                                  // members 테이블에 있고 재직 상태가 아닌 담당자
                                  $subquery->from('members')
                                          ->whereColumn('members.name', 'correction_div.case_manager')
                                          ->where('members.status', '!=', '재직');
                              })->orWhereNotExists(function($subquery) {
                                  // members 테이블에 없는 담당자
                                  $subquery->from('members')
                                          ->whereColumn('members.name', 'correction_div.case_manager');
                              })->orWhereExists(function($subquery) {
                                  // members 테이블에 있고 재직중이지만 사건관리팀이 아닌 담당자
                                  $subquery->from('members')
                                          ->whereColumn('members.name', 'correction_div.case_manager')
                                          ->where('members.status', '재직')
                                          ->where('members.task', '!=', '사건관리팀');
                              });
                          });
                } else {
                    $query->where('case_manager', $request->case_manager);
                }
            }

            // 제출여부 필터
            if ($request->submission_status) {
                $query->where('submission_status', $request->submission_status);
            }

            $filename = '보정서배당_' . now()->format('Y-m-d') . '.csv';
            
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
                    '발송일자',
                    '수신일자',
                    '법원',
                    '사건번호',
                    '고객명',
                    '송달문서',
                    '분류',
                    '상담자',
                    '담당자',
                    '제출기한',
                    '제출여부',
                    '제출일자',
                    '메모'
                ]);

                // 데이터 청크 단위로 처리
                $query->orderBy('shipment_date', 'desc')
                      ->orderBy('case_number', 'desc')
                      ->chunk(1000, function($correctionDivs) use ($handle) {
                    foreach ($correctionDivs as $correctionDiv) {
                        fputcsv($handle, [
                            $correctionDiv->shipment_date,
                            $correctionDiv->receipt_date,
                            $correctionDiv->court_name,
                            $correctionDiv->case_number,
                            $correctionDiv->name,
                            $correctionDiv->document_name,
                            $correctionDiv->document_type,
                            $correctionDiv->consultant,
                            $correctionDiv->case_manager,
                            $correctionDiv->deadline,
                            $correctionDiv->submission_status,
                            $correctionDiv->summit_date,
                            $correctionDiv->command
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

    /**
     * 미제출 현황 통계를 조회합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnsubmittedStats(Request $request)
    {
        try {
            $months = $request->input('months', 3);
            $caseTeamOnly = $request->boolean('case_team_only', true);
            
            $endDate = now();
            $startDate = now()->subMonths($months);

            $query = CorrectionDiv::query()
                ->whereNotNull('receipt_date')
                ->whereBetween('receipt_date', [$startDate, $endDate]);

            // members 테이블과 LEFT JOIN
            $query->leftJoin('members', 'correction_div.case_manager', '=', 'members.name');
            
            // 사건관리팀 필터가 체크된 경우
            if ($caseTeamOnly) {
                $query->whereExists(function ($query) {
                    $query->select('members.name')
                          ->from('members')
                          ->whereColumn('members.name', 'correction_div.case_manager')
                          ->where('members.task', '사건관리팀');
                });
            }

            // 나머지 통계 쿼리는 동일
            $stats = $query->select('correction_div.case_manager')
                ->selectRaw('COUNT(CASE WHEN document_type = "명령" AND submission_status = "미제출" THEN 1 END) as order_count')
                ->selectRaw('COUNT(CASE WHEN document_type = "기타" AND submission_status = "미제출" THEN 1 END) as etc_count')
                ->selectRaw('COUNT(CASE WHEN document_type = "보정" AND submission_status = "미제출" THEN 1 END) as correction_count')
                ->selectRaw('COUNT(CASE WHEN document_type = "예외" AND submission_status = "미제출" THEN 1 END) as exception_count')
                ->selectRaw('COUNT(CASE WHEN (document_type IS NULL OR document_type = "선택없음") AND submission_status = "미제출" THEN 1 END) as none_count')
                ->selectRaw('COUNT(CASE WHEN submission_status = "미제출" THEN 1 END) as total_count')
                ->groupBy('correction_div.case_manager')
                ->having('total_count', '>', 0)
                ->orderBy('total_count', 'asc')
                ->get()
                ->map(function ($item, $index) {
                    return [
                        'rank' => $index + 1,
                        'name' => $item->case_manager ?? '담당자없음',
                        'order_count' => $item->order_count,
                        'etc_count' => $item->etc_count,
                        'correction_count' => $item->correction_count,
                        'exception_count' => $item->exception_count,
                        'none_count' => $item->none_count,
                        'total_count' => $item->total_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('미제출 현황 통계 조회 중 오류 발생: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '통계 데이터를 불러오는데 실패했습니다.'
            ], 500);
        }
    }
}