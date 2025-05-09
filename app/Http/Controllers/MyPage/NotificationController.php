<?php

namespace App\Http\Controllers\Mypage;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $notifications = Notification::where(function($query) use ($user) {
            $query->where('notified_user_id', $user->id)
                  ->orWhere('via_user_id', $user->id);
        })
        ->with(['notifiedUser', 'viaUser', 'creator'])
        ->latest()
        ->paginate(15);

        return view('mypage.notifications.index', compact('notifications'));
    }

    public function show(Notification $notification)
    {
        $user = auth()->user();
        
        // 해당 통지의 피통지자나 경유자가 아니면 403
        if ($notification->notified_user_id !== $user->id && $notification->via_user_id !== $user->id) {
            abort(403);
        }

        $notification->load(['notifiedUser', 'viaUser', 'creator', 'response.responder']);
        return view('mypage.notifications.show', compact('notification'));
    }

    public function storeResponse(Request $request, Notification $notification)
    {
        $user = auth()->user();
        
        // 피통지자가 아니면 403
        if ($notification->notified_user_id !== $user->id) {
            abort(403);
        }

        // 이미 답변이 있거나 답변이 불필요한 경우 403
        if ($notification->response || $notification->status === Notification::STATUS_NOT_REQUIRED) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string'
        ]);

        NotificationResponse::create([
            'notification_id' => $notification->id,
            'content' => $validated['content'],
            'responded_by' => $user->id,
            'responded_at' => now()
        ]);

        $notification->update(['status' => Notification::STATUS_COMPLETED]);

        return redirect()
            ->route('mypage.notifications.show', $notification)
            ->with('success', '답변이 등록되었습니다.');
    }
}