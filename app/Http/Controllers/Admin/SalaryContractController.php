<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryContract;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class SalaryContractController extends Controller
{
    /**
     * 연봉계약서 목록을 표시
     */
    public function index(Request $request)
    {
        $query = SalaryContract::select('salary_contracts.*')
            ->with(['user', 'user.member'])
            ->join('users', 'salary_contracts.user_id', '=', 'users.id')
            ->join('members', 'users.name', '=', 'members.name');

        // 필터 적용
        if ($request->filled('period')) {
            // 계약기간 필터
            $period = explode('~', $request->period);
            $query->whereBetween('contract_start_date', $period);
        }

        if ($request->filled('task')) {
            // 소속팀 필터
            $query->where('members.task', $request->task);
        }

        if ($request->filled('position')) {
            // 직급 필터
            $query->where('members.position', $request->position);
        }

        if ($request->filled('approval_status')) {
            // 승인상태 필터
            if ($request->approval_status === 'pending') {
                $query->whereNull('salary_contracts.approved_at');
            } else {
                $query->whereNotNull('salary_contracts.approved_at');
            }
        }

        if ($request->has('search')) {
            // 이름 검색
            $query->where('users.name', 'like', '%' . $request->search . '%');
        }

        $contracts = $query->orderBy('salary_contracts.created_date', 'desc')
            ->paginate(10);

        return view('admin.salary-contracts.index', compact('contracts'));
    }

    /**
     * 연봉계약서 일괄 생성 폼을 표시
     */
    public function create()
    {
        return view('admin.salary-contracts.create');
    }

    /**
     * 연봉계약서를 일괄 생성
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_start_date' => 'required|date',
            'contract_end_date' => 'required|date|after:contract_start_date',
            'salaries' => 'required|array',
            'memos' => 'array'
        ]);

        try {
            DB::beginTransaction();

            // 모든 재직 중인 멤버 조회
            $activeMembers = Member::where('status', '재직')->get();

            foreach ($activeMembers as $member) {
                // User 조회 시 필요한 필드만 선택
                $user = User::select(['id', 'name'])
                    ->where('name', $member->name)
                    ->first();

                if ($user) {
                    // 해당 멤버의 팀과 직급에 맞는 기본급 찾기
                    $base_salary = 0; // 기본값 0으로 설정
                    if (isset($request->salaries[$member->task][$member->position])) {
                        $base_salary = $request->salaries[$member->task][$member->position];
                    }

                    // 해당 팀의 메모 가져오기
                    $memo = $request->memos[$member->task] ?? null;

                    $contract = SalaryContract::create([
                        'user_id' => $user->id,
                        'position' => $member->position,
                        'base_salary' => $base_salary,
                        'contract_start_date' => $request->contract_start_date,
                        'contract_end_date' => $request->contract_end_date,
                        'created_date' => now(),
                        'memo' => $memo,
                        'created_by' => auth()->id(),
                        'approved_at' => null,
                        'approved_by' => null
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.salary-contracts.index')
                ->with('success', '연봉계약서가 일괄 생성되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating salary contracts: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return back()->with('error', '연봉계약서 생성 중 오류가 발생했습니다.');
        }
    }

    /**
     * 연봉계약서 상세 정보를 표시
     */
    public function show(SalaryContract $salaryContract)
    {
        $salaryContract->load(['user', 'user.member', 'approver', 'creator']);
        return view('admin.salary-contracts.show', compact('salaryContract'));
    }

    /**
     * 연봉계약서 수정 폼을 표시
     */
    public function edit(SalaryContract $salaryContract)
    {
        return view('admin.salary-contracts.edit', compact('salaryContract'));
    }

    /**
     * 연봉계약서를 수정
     */
    public function update(Request $request, SalaryContract $salaryContract)
    {
        $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'contract_start_date' => 'required|date',
            'contract_end_date' => 'required|date|after:contract_start_date',
            'memo' => 'nullable|string'
        ]);

        // 기존 승인 정보 초기화
        $salaryContract->update([
            'base_salary' => $request->base_salary,
            'contract_start_date' => $request->contract_start_date,
            'contract_end_date' => $request->contract_end_date,
            'memo' => $request->memo,
            'approved_at' => null,
            'approved_by' => null
        ]);

        return redirect()->route('admin.salary-contracts.show', $salaryContract)
            ->with('success', '연봉계약서가 수정되었습니다.');
    }

    /**
     * 연봉계약서를 삭제
     */
    public function destroy(SalaryContract $salaryContract)
    {
        $salaryContract->delete();
        return redirect()->route('admin.salary-contracts.index')
            ->with('success', '연봉계약서가 삭제되었습니다.');
    }

    /**
     * 활성 상태인 멤버 목록을 조회
     */
    public function getActiveMembers()
    {
        $members = Member::where('status', '!=', '퇴직')
            ->orderBy('task')
            ->orderBy('position')
            ->get();

        return response()->json($members);
    }

    /**
     * 연봉계약서를 개별 생성
     */
    public function storeIndividual(Request $request)
    {
        $request->validate([
            'contract_start_date' => 'required|date',
            'contract_end_date' => 'required|date|after:contract_start_date',
            'selected_members' => 'required|array',
            'base_salary' => 'required|array',
            'memo' => 'array'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->selected_members as $memberId) {
                $member = Member::find($memberId);
                $user = User::where('name', $member->name)->first();

                if ($user) {
                    SalaryContract::create([
                        'user_id' => $user->id,
                        'position' => $member->position,
                        'base_salary' => $request->base_salary[$memberId] ?? 0,
                        'contract_start_date' => $request->contract_start_date,
                        'contract_end_date' => $request->contract_end_date,
                        'created_date' => now(),
                        'memo' => $request->memo[$memberId] ?? null,
                        'created_by' => auth()->id(),
                        'approved_at' => null,
                        'approved_by' => null
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.salary-contracts.index')
                ->with('success', '선택한 직원들의 연봉계약서가 생성되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating individual salary contracts: ' . $e->getMessage());
            return back()->with('error', '연봉계약서 생성 중 오류가 발생했습니다.');
        }
    }

    /**
     * 연봉계약서 PDF 생성
     */
    public function generatePdf(SalaryContract $salaryContract)
    {
        $salaryContract->load(['user', 'user.member', 'creator']);
        
        // PDF 설정 추가
        PDF::setOptions([
            'defaultFont' => 'NanumGothic',
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultPaperSize' => 'a4',
            'defaultEncoding' => 'UTF-8'
        ]);
        
        $pdf = PDF::loadView('admin.salary-contracts.pdf', compact('salaryContract'));
        
        $filename = sprintf(
            '연봉계약서_%s_%s.pdf',
            $salaryContract->contract_start_date->format('Y'),
            $salaryContract->user->name
        );
        
        return $pdf->download($filename);
    }
}