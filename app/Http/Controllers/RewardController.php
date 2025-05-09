<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\Member;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index()
    {
        $rewards = Reward::with('member')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        $members = Member::where('status', '재직')
            ->orderBy('name')
            ->get(['id', 'name']);
            
        return view('rewards.index', compact('rewards', 'members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'reward_type' => ['required', 'string', 'in:' . implode(',', Reward::REWARD_TYPES)],
            'content' => 'required|string',
            'memo' => 'nullable|string',
            'usable_date' => 'nullable|date',
        ]);

        $validated['is_auto_generated'] = false;

        Reward::create($validated);

        return redirect()->route('rewards.index')
            ->with('success', '보상/제재가 성공적으로 등록되었습니다.');
    }

    public function edit(Reward $reward)
    {
        return response()->json($reward);
    }

    public function update(Request $request, Reward $reward)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'reward_type' => ['required', 'string', 'in:' . implode(',', Reward::REWARD_TYPES)],
            'content' => 'required|string',
            'memo' => 'nullable|string',
            'usable_date' => 'nullable|date',
        ]);

        $reward->update($validated);

        return redirect()->route('rewards.index')
            ->with('success', '보상/제재가 성공적으로 수정되었습니다.');
    }

    public function destroy(Reward $reward)
    {
        $reward->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => '성공적으로 삭제되었습니다.']);
        }

        return redirect()->route('rewards.index')
            ->with('success', '보상/제재가 성공적으로 삭제되었습니다.');
    }
}
