<?php

namespace App\Http\Controllers;

use App\Models\Member;  // Member 모델을 불러옵니다.
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 모든 구성원 데이터를 가져옵니다.
        $members = Member::all();

        // members 데이터를 index 뷰에 전달합니다.
        return view('members.index', compact('members'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // create.blade.php 뷰를 반환합니다.
        return view('members.create');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'task' => 'required|string|max:255',
            'affiliation' => 'required|string|max:255',
            'status' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'block_8_17' => 'nullable|integer',
            'block_9_18' => 'nullable|integer',
            'block_10_19' => 'nullable|integer',
            'block_9_16' => 'nullable|integer',
            'paid_holiday' => 'nullable|integer',
            'car_cost' => 'nullable|integer',
            'childcare' => 'nullable|integer',
            'flexible_working' => 'nullable|boolean',
            'annual_start_period' => 'nullable|date',
            'annual_end_period' => 'nullable|date',
            'house_work' => 'nullable|integer',
            'standard' => 'required|integer',
        ]);
    
        Member::create($validatedData);
    
        return redirect()->route('members.index')->with('success', '구성원이 추가되었습니다.');
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // ID로 특정 구성원을 찾습니다.
        $member = Member::findOrFail($id);

        // show.blade.php 뷰에 구성원 데이터를 전달합니다.
        return view('members.show', compact('member'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $member = Member::findOrFail($id);
        return response()->json($member);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'task' => 'nullable|string|max:255',
            'affiliation' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'bank' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'block_8_17' => 'nullable|integer',
            'block_9_18' => 'nullable|integer',
            'block_10_19' => 'nullable|integer',
            'block_9_16' => 'nullable|integer',
            'paid_holiday' => 'nullable|integer',
            'car_cost' => 'nullable|integer',
            'childcare' => 'nullable|integer',
            'flexible_working' => 'nullable|boolean',
            'annual_start_period' => 'nullable|date',
            'annual_end_period' => 'nullable|date',
            'house_work' => 'nullable|integer',
            'standard' => 'required|integer',
        ]);
    
        $member = Member::findOrFail($id);
        $member->update($validatedData);
    
        return redirect()->route('members.index')->with('success', '구성원이 수정되었습니다.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // ID로 구성원을 찾아 삭제합니다.
        $member = Member::findOrFail($id);
        $member->delete();

        // 구성원 목록 페이지로 리다이렉트하며, 성공 메시지 전달
        return redirect()->route('members.index')->with('success', '구성원이 삭제되었습니다.');
    }

}
