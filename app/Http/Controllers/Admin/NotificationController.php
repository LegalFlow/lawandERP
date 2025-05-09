<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['notifiedUser', 'viaUser', 'creator'])
            ->latest()
            ->paginate(15);

        $users = User::where('is_approved', true)
            ->where('is_admin', false)
            ->get();

        return view('admin.notifications.index', compact('notifications', 'users'));
    }

    public function create()
    {
        $users = User::where('is_approved', true)->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notified_user_id' => 'required|exists:users,id',
            'via_user_id' => 'nullable|exists:users,id',
            'response_deadline' => 'nullable|required_if:response_required,on|integer|min:1',
        ]);

        // 체크박스 값에 따라 상태 결정
        $response_required = $request->has('response_required');
        
        $data = array_merge($validated, [
            'created_by' => auth()->id(),
            'response_required' => $response_required,
            'status' => $response_required ? Notification::STATUS_WAITING : Notification::STATUS_NOT_REQUIRED
        ]);

        Notification::create($data);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', '통지가 생성되었습니다.');
    }

    public function show(Notification $notification)
    {
        $users = User::where('is_approved', true)
            ->where('is_admin', false)
            ->get();

        return view('admin.notifications.show', compact('notification', 'users'));
    }

    public function edit(Notification $notification)
    {
        $users = User::where('is_approved', true)->get();
        return view('admin.notifications.edit', compact('notification', 'users'));
    }

    public function update(Request $request, Notification $notification)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notified_user_id' => 'required|exists:users,id',
            'via_user_id' => 'nullable|exists:users,id',
            'response_required' => 'boolean',
            'response_deadline' => 'nullable|integer|min:1',
            'status' => 'required|in:' . implode(',', Notification::getAvailableStatuses()),
        ]);

        $notification->update($validated);

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', '통지가 수정되었습니다.');
    }

    public function destroy(Notification $notification)
    {
        if ($notification->status === Notification::STATUS_COMPLETED) {
            return redirect()
                ->route('admin.notifications.index')
                ->with('error', '답변이 완료된 통지는 삭제할 수 없습니다.');
        }

        $notification->delete();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', '통지가 삭제되었습니다.');
    }
}
