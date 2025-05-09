<?php

namespace App\Http\Controllers\MyPage;

use App\Http\Controllers\Controller;
use App\Models\SalaryContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class SalaryContractController extends Controller
{
   /**
    * 내 연봉계약서 목록을 표시
    */
   public function index()
   {
       $contracts = SalaryContract::where('user_id', Auth::id())
           ->with(['creator', 'approver'])
           ->orderBy('created_at', 'desc')
           ->get();

       // 승인 대기중인 계약서가 있는지 확인
       $hasPendingContract = $contracts->contains(function ($contract) {
           return $contract->approved_at === null;
       });

       return view('mypage.salary-contracts.index', compact('contracts', 'hasPendingContract'));
   }

   /**
    * 연봉계약서 상세 정보를 표시
    */
   public function show(SalaryContract $salaryContract)
   {
       // 본인의 계약서인지 확인
       if ($salaryContract->user_id !== Auth::id()) {
           return abort(403);
       }

       $salaryContract->load(['creator', 'approver']);

       return view('mypage.salary-contracts.show', compact('salaryContract'));
   }

   /**
    * 연봉계약서를 승인
    */
   public function approve(Request $request, SalaryContract $salaryContract)
   {
       // 본인의 계약서인지 확인
       if ($salaryContract->user_id !== Auth::id()) {
           return abort(403);
       }

       // 이미 승인된 계약서인지 확인
       if ($salaryContract->approved_at !== null) {
           return back()->with('error', '이미 승인된 계약서입니다.');
       }

       // 승인 처리
       $salaryContract->update([
           'approved_at' => now(),
           'approved_by' => Auth::id()
       ]);

       return redirect()->route('mypage.salary-contracts.show', $salaryContract)
           ->with('success', '연봉계약서가 승인되었습니다.');
   }

   /**
    * 연봉계약서 PDF 생성
    */
   public function generatePdf(SalaryContract $salaryContract)
   {
       // 본인의 계약서인지 확인
       if ($salaryContract->user_id !== Auth::id()) {
           return abort(403);
       }

       $salaryContract->load(['creator', 'approver']);
       
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