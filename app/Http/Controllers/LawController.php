<?php

namespace App\Http\Controllers;

use App\Models\Law;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LawController extends Controller
{
    public function index(Request $request)
    {
        $query = Law::query();

        // 검색 필터 적용
        if ($request->filled('search_text')) {
            $searchText = $request->search_text;
            $query->where(function($q) use ($searchText) {
                $q->where('title', 'like', "%{$searchText}%")
                  ->orWhere('content', 'like', "%{$searchText}%");
            });
        }

        // 기간 검색
        if ($request->filled(['start_date', 'end_date', 'date_type'])) {
            $dateField = match($request->date_type) {
                'registration_date' => 'registration_date',
                'enforcement_date' => 'enforcement_date',
                'abolition_date' => 'abolition_date',
                default => 'registration_date'
            };
            
            $query->whereBetween($dateField, [$request->start_date, $request->end_date]);
        }

        // 시행여부 필터
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 기본 정렬: 등록일 내림차순
        $query->orderBy('registration_date', 'desc');

        $laws = $query->paginate(15)->withQueryString();

        return view('laws.index', [
            'laws' => $laws,
            'statusOptions' => Law::getStatusOptions(),
            'isAdmin' => Law::isAdminUser(),
            'dateTypes' => [
                'registration_date' => '등록일',
                'enforcement_date' => '시행일',
                'abolition_date' => '폐기일'
            ]
        ]);
    }

    public function show(Law $law)
    {
        return view('laws.show', [
            'law' => $law,
            'isAdmin' => Law::isAdminUser()
        ]);
    }

    public function create()
    {
        Law::authorizeAdmin();
        
        return view('laws.create', [
            'statusOptions' => Law::getStatusOptions()
        ]);
    }

    public function store(Request $request)
    {
        Law::authorizeAdmin();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'registration_date' => 'required|date',
            'enforcement_date' => 'required|date',
            'status' => 'required|string|in:시행중,폐기',
            'abolition_date' => 'nullable|date'
        ]);

        DB::beginTransaction();
        try {
            Law::create($validated);
            DB::commit();
            return redirect()->route('laws.index')->with('success', '내규가 성공적으로 등록되었습니다.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '내규 등록 중 오류가 발생했습니다.');
        }
    }

    public function edit(Law $law)
    {
        Law::authorizeAdmin();

        return view('laws.edit', [
            'law' => $law,
            'statusOptions' => Law::getStatusOptions()
        ]);
    }

    public function update(Request $request, Law $law)
    {
        Law::authorizeAdmin();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'registration_date' => 'required|date',
            'enforcement_date' => 'required|date',
            'status' => 'required|string|in:시행중,폐기',
            'abolition_date' => 'nullable|date'
        ]);

        DB::beginTransaction();
        try {
            $law->update($validated);
            DB::commit();
            return redirect()->route('laws.show', $law)->with('success', '내규가 성공적으로 수정되었습니다.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '내규 수정 중 오류가 발생했습니다.');
        }
    }

    public function destroy(Law $law)
    {
        Law::authorizeAdmin();

        DB::beginTransaction();
        try {
            $law->delete();
            DB::commit();
            return redirect()->route('laws.index')->with('success', '내규가 성공적으로 삭제되었습니다.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '내규 삭제 중 오류가 발생했습니다.');
        }
    }
}