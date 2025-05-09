<?php

namespace App\Http\Controllers;

use App\Models\Target;
use App\Models\Member;
use App\Helpers\RegionHelper;
use Illuminate\Http\Request;

class TargetController extends Controller
{
    public function index(Request $request)
    {
        $query = Target::query();
        
        $query->where('del_flag', 0);
        
        $dateType = $request->input('date_type', 'create_dt');
        if ($request->filled('start_date') && $request->filled('end_date')) {
            if ($dateType === 'contract_date') {
                $query->whereNotNull('contract_date')
                      ->whereBetween('contract_date', [
                          $request->input('start_date') . ' 00:00:00',
                          $request->input('end_date') . ' 23:59:59'
                      ]);
            } else {
                $query->whereBetween($dateType, [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59'
                ]);
            }
        }

        $query->orderBy($dateType, 'desc');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim($request->name) . '%');
        }

        if ($request->filled('member')) {
            $query->where('Member', $request->member);
        }

        if ($request->filled('region')) {
            $query->where(function($q) use ($request) {
                foreach (RegionHelper::getAllPlacesForRegion($request->region) as $place) {
                    $q->orWhere('living_place', 'like', '%' . $place . '%');
                }
            });
        }

        if ($request->filled('my_cases_only') && $request->my_cases_only === 'true') {
            $query->where('Member', auth()->user()->name);
        }

        if ($request->filled('contract_only') && $request->contract_only === 'true') {
            $query->whereNotIn('case_state', [5, 10, 11]);
        }

        $targets = $query->paginate(15);
        
        $members = Member::where('status', '재직')->orderBy('name')->get();
        
        $statistics = $this->getStatistics($request);
        
        return view('targets.index', [
            'targets' => $targets,
            'members' => $members,
            'totalCount' => $statistics['totalCount'],
            'totalInvalidCount' => $statistics['totalInvalidCount'],
            'totalReprocessCount' => $statistics['totalReprocessCount'],
            'totalExistingCount' => $statistics['totalExistingCount'],
            'totalIntroducedCount' => $statistics['totalIntroducedCount'],
            'totalNiceCount' => $statistics['totalNiceCount'],
            'totalSNSCount' => $statistics['totalSNSCount'],
            'totalRealConsultCount' => $statistics['totalRealConsultCount'],
            'totalAbsentCount' => $statistics['totalAbsentCount'],
            'seoulCount' => $statistics['seoulCount'],
            'seoulInvalidCount' => $statistics['seoulInvalidCount'],
            'seoulReprocessCount' => $statistics['seoulReprocessCount'],
            'seoulExistingCount' => $statistics['seoulExistingCount'],
            'seoulIntroducedCount' => $statistics['seoulIntroducedCount'],
            'seoulNiceCount' => $statistics['seoulNiceCount'],
            'seoulSNSCount' => $statistics['seoulSNSCount'],
            'seoulRealConsultCount' => $statistics['seoulRealConsultCount'],
            'seoulAbsentCount' => $statistics['seoulAbsentCount'],
            'daejeonCount' => $statistics['daejeonCount'],
            'daejeonInvalidCount' => $statistics['daejeonInvalidCount'],
            'daejeonReprocessCount' => $statistics['daejeonReprocessCount'],
            'daejeonExistingCount' => $statistics['daejeonExistingCount'],
            'daejeonIntroducedCount' => $statistics['daejeonIntroducedCount'],
            'daejeonNiceCount' => $statistics['daejeonNiceCount'],
            'daejeonSNSCount' => $statistics['daejeonSNSCount'],
            'daejeonRealConsultCount' => $statistics['daejeonRealConsultCount'],
            'daejeonAbsentCount' => $statistics['daejeonAbsentCount'],
            'busanCount' => $statistics['busanCount'],
            'busanInvalidCount' => $statistics['busanInvalidCount'],
            'busanReprocessCount' => $statistics['busanReprocessCount'],
            'busanExistingCount' => $statistics['busanExistingCount'],
            'busanIntroducedCount' => $statistics['busanIntroducedCount'],
            'busanNiceCount' => $statistics['busanNiceCount'],
            'busanSNSCount' => $statistics['busanSNSCount'],
            'busanRealConsultCount' => $statistics['busanRealConsultCount'],
            'busanAbsentCount' => $statistics['busanAbsentCount']
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'idx_TblCase' => 'required',
            'create_dt' => 'required|date',
            'name' => 'required|string',
            'living_place' => 'required|string',
            'Member' => 'required',
            'case_state' => 'required|string',
            'contract_date' => 'nullable|date',
            'note' => 'nullable|string',
            'div_case' => 'boolean'
        ]);

        // 중복 체크
        $existing = Target::where('idx_TblCase', $request->idx_TblCase)->first();
        
        if ($existing) {
            $existing->update($validatedData);
        } else {
            Target::create($validatedData);
        }

        return redirect()->route('targets.index')->with('success', '저장되었습니다.');
    }

    public function update(Request $request, Target $target)
    {
        $validatedData = $request->validate([
            'create_dt' => 'required|date',
            'name' => 'required|string',
            'living_place' => 'required|string',
            'Member' => 'required',
            'case_state' => 'required|string',
            'contract_date' => 'nullable|date',
            'note' => 'nullable|string',
            'div_case' => 'boolean'
        ]);

        $target->update($validatedData);
        return redirect()->route('targets.index')->with('success', '수정되었습니다.');
    }

    public function destroy(Target $target)
    {
        $target->delete();
        return redirect()->route('targets.index')->with('success', '삭제되었습니다.');
    }

    private function getStatistics(Request $request)
    {
        $query = Target::query();
        
        $query->where('del_flag', 0);
        
        $dateType = $request->input('date_type', 'create_dt');
        if ($request->filled(['start_date', 'end_date'])) {
            if ($dateType === 'contract_date') {
                $query->whereNotNull('contract_date')
                      ->whereBetween('contract_date', [
                          $request->start_date . ' 00:00:00',
                          $request->end_date . ' 23:59:59'
                      ]);
            } else {
                $query->whereBetween($dateType, [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }
        } else {
            if ($dateType === 'contract_date') {
                $query->whereNotNull('contract_date')
                      ->whereBetween('contract_date', [
                          now()->startOfMonth()->format('Y-m-d') . ' 00:00:00',
                          now()->format('Y-m-d') . ' 23:59:59'
                      ]);
            } else {
                $query->whereBetween($dateType, [
                    now()->startOfMonth()->format('Y-m-d') . ' 00:00:00',
                    now()->format('Y-m-d') . ' 23:59:59'
                ]);
            }
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . trim($request->name) . '%');
        }

        if ($request->filled('member')) {
            $query->where('Member', $request->member);
        }

        // 나의 사건만 보기 필터
        if ($request->filled('my_cases_only') && $request->my_cases_only === 'true') {
            $query->where('Member', auth()->user()->name);
        }

        if ($request->filled('contract_only') && $request->contract_only === 'true') {
            $query->whereNotIn('case_state', [5, 10, 11]);
        }

        $targets = RegionHelper::getTargetsWithRegion($query);

        if ($request->filled('region')) {
            $targets = $targets->filter(function ($target) use ($request) {
                return $target->region === $request->region;
            });
        }

        // 각 항목별 체크 함수 정의
        $isInvalidMember = fn($t) => $t->Member === '무효';
        $isAbsentCase = fn($t) => str_ends_with($t->name, '부재');
        $isReprocess = fn($t) => str_contains($t->name, '재진행');
        $isExisting = fn($t) => str_contains($t->name, '기존');
        $isIntroduced = fn($t) => str_contains($t->name, '소개');
        $isNice = fn($t) => str_contains($t->name, '나이스');
        $isSNS = fn($t) => str_contains(strtolower($t->name), 'sns');

        // 지역별 통계 계산 함수
        $calculateRegionStats = function($regionTargets) use ($isInvalidMember, $isAbsentCase, $isReprocess, $isExisting, $isIntroduced, $isNice, $isSNS) {
            $count = $regionTargets->count();
            $invalidCount = $regionTargets->filter($isInvalidMember)->count();
            $absentCount = $regionTargets->filter($isAbsentCase)->count();
            $reprocessCount = $regionTargets->filter($isReprocess)->count();
            $existingCount = $regionTargets->filter($isExisting)->count();
            $introducedCount = $regionTargets->filter($isIntroduced)->count();
            $niceCount = $regionTargets->filter($isNice)->count();
            $snsCount = $regionTargets->filter($isSNS)->count();
            
            $realConsultCount = $count - ($reprocessCount + $existingCount + $introducedCount + $niceCount + $snsCount);
            
            return [
                'count' => $count,
                'invalidCount' => $invalidCount,
                'absentCount' => $absentCount,
                'reprocessCount' => $reprocessCount,
                'existingCount' => $existingCount,
                'introducedCount' => $introducedCount,
                'niceCount' => $niceCount,
                'snsCount' => $snsCount,
                'realConsultCount' => $realConsultCount
            ];
        };

        // 전체 통계
        $totalStats = $calculateRegionStats($targets);

        // 지역별 통계
        $seoulStats = $calculateRegionStats($targets->filter(fn($t) => $t->region === '서울'));
        $daejeonStats = $calculateRegionStats($targets->filter(fn($t) => $t->region === '대전'));
        $busanStats = $calculateRegionStats($targets->filter(fn($t) => $t->region === '부산'));

        return [
            // 전체 통계
            'totalCount' => $totalStats['count'],
            'totalInvalidCount' => $totalStats['invalidCount'],
            'totalReprocessCount' => $totalStats['reprocessCount'],
            'totalExistingCount' => $totalStats['existingCount'],
            'totalIntroducedCount' => $totalStats['introducedCount'],
            'totalNiceCount' => $totalStats['niceCount'],
            'totalSNSCount' => $totalStats['snsCount'],
            'totalRealConsultCount' => $totalStats['realConsultCount'],
            'totalAbsentCount' => $totalStats['absentCount'],

            // 서울 통계
            'seoulCount' => $seoulStats['count'],
            'seoulInvalidCount' => $seoulStats['invalidCount'],
            'seoulReprocessCount' => $seoulStats['reprocessCount'],
            'seoulExistingCount' => $seoulStats['existingCount'],
            'seoulIntroducedCount' => $seoulStats['introducedCount'],
            'seoulNiceCount' => $seoulStats['niceCount'],
            'seoulSNSCount' => $seoulStats['snsCount'],
            'seoulRealConsultCount' => $seoulStats['realConsultCount'],
            'seoulAbsentCount' => $seoulStats['absentCount'],

            // 대전 통계
            'daejeonCount' => $daejeonStats['count'],
            'daejeonInvalidCount' => $daejeonStats['invalidCount'],
            'daejeonReprocessCount' => $daejeonStats['reprocessCount'],
            'daejeonExistingCount' => $daejeonStats['existingCount'],
            'daejeonIntroducedCount' => $daejeonStats['introducedCount'],
            'daejeonNiceCount' => $daejeonStats['niceCount'],
            'daejeonSNSCount' => $daejeonStats['snsCount'],
            'daejeonRealConsultCount' => $daejeonStats['realConsultCount'],
            'daejeonAbsentCount' => $daejeonStats['absentCount'],

            // 부산 통계
            'busanCount' => $busanStats['count'],
            'busanInvalidCount' => $busanStats['invalidCount'],
            'busanReprocessCount' => $busanStats['reprocessCount'],
            'busanExistingCount' => $busanStats['existingCount'],
            'busanIntroducedCount' => $busanStats['introducedCount'],
            'busanNiceCount' => $busanStats['niceCount'],
            'busanSNSCount' => $busanStats['snsCount'],
            'busanRealConsultCount' => $busanStats['realConsultCount'],
            'busanAbsentCount' => $busanStats['absentCount']
        ];
    }

    public function export(Request $request)
    {
        try {
            $query = Target::query();
            $query->where('del_flag', 0);
            
            // 날짜 필터 적용
            $dateType = $request->input('date_type', 'create_dt');
            if ($request->filled('start_date') && $request->filled('end_date')) {
                if ($dateType === 'contract_date') {
                    $query->whereNotNull('contract_date')
                          ->whereBetween('contract_date', [
                              $request->input('start_date') . ' 00:00:00',
                              $request->input('end_date') . ' 23:59:59'
                          ]);
                } else {
                    $query->whereBetween($dateType, [
                        $request->input('start_date') . ' 00:00:00',
                        $request->input('end_date') . ' 23:59:59'
                    ]);
                }
            }

            // 검색 필터 적용
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . trim($request->name) . '%');
            }

            if ($request->filled('member')) {
                $query->where('Member', $request->member);
            }

            if ($request->filled('region')) {
                $query->where(function($q) use ($request) {
                    foreach (RegionHelper::getAllPlacesForRegion($request->region) as $place) {
                        $q->orWhere('living_place', 'like', '%' . $place . '%');
                    }
                });
            }

            if ($request->filled('contract_only') && $request->contract_only === 'true') {
                $query->whereNotIn('case_state', [5, 10, 11]);
            }

            $filename = '신규상담_' . now()->format('Y-m-d') . '.csv';
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
                    '등록일자',
                    '고객명',
                    '지역',
                    '상담자',
                    '진행현황',
                    '계약일자',
                    '수임료',
                    '송인부',
                    '배당상태'
                ]);

                // 데이터 청크 단위로 처리
                $query->chunk(1000, function($targets) use ($handle) {
                    foreach ($targets as $target) {
                        $initialStates = [5, 10, 11];
                        $isInitialState = in_array($target->case_state, $initialStates);
                        
                        $total = !$isInitialState ? ($target->total_const_delivery ?? 0) + 
                                 ($target->stamp_fee ?? 0) + 
                                 ($target->total_debt_cert_cost ?? 0) : 0;

                        fputcsv($handle, [
                            \Carbon\Carbon::parse($target->create_dt)->format('Y-m-d H:i:s'),
                            $target->name,
                            $target->living_place,
                            $target->Member,
                            \App\Helpers\CaseStateHelper::getStateLabel($target->case_type, $target->case_state),
                            !$isInitialState && $target->contract_date ? \Carbon\Carbon::parse($target->contract_date)->format('Y-m-d') : '',
                            !$isInitialState && isset($target->lawyer_fee) && $target->lawyer_fee > 0 ? number_format($target->lawyer_fee) : '',
                            $total > 0 ? number_format($total) : '',
                            $target->div_case ? '배당완료' : ($isInitialState ? '' : '미배당')
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
} 