<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function pendingUsers(Request $request)
    {
        // 승인 대기 사용자 정렬
        $pendingSort = $request->input('pending_sort', 'created_at');
        $pendingDirection = $request->input('pending_direction', 'desc');
        
        // 허용된 정렬 필드만 사용
        $allowedSortFields = ['name', 'email', 'created_at'];
        if (!in_array($pendingSort, $allowedSortFields)) {
            $pendingSort = 'created_at';
        }
        
        // 허용된 정렬 방향만 사용
        $allowedDirections = ['asc', 'desc'];
        if (!in_array($pendingDirection, $allowedDirections)) {
            $pendingDirection = 'desc';
        }
        
        $pendingUsers = User::where('is_approved', false)
                    ->orderBy($pendingSort, $pendingDirection)
                    ->paginate(10)
                    ->withQueryString();
        
        // 승인된 사용자 정렬
        $approvedSort = $request->input('approved_sort', 'approved_at');
        $approvedDirection = $request->input('approved_direction', 'desc');
        
        // 허용된 정렬 필드만 사용
        $allowedApprovedSortFields = ['name', 'email', 'created_at', 'approved_at'];
        if (!in_array($approvedSort, $allowedApprovedSortFields)) {
            $approvedSort = 'approved_at';
        }
        
        // 허용된 정렬 방향만 사용
        if (!in_array($approvedDirection, $allowedDirections)) {
            $approvedDirection = 'desc';
        }
        
        $approvedUsers = User::where('is_approved', true)
                    ->orderBy($approvedSort, $approvedDirection)
                    ->paginate(10)
                    ->withQueryString();
                    
        return view('admin.users.pending', compact('pendingUsers', 'approvedUsers'));
    }

    public function approveUser(User $user)
    {
        $user->approve(Auth::id());
        return redirect()->back()->with('success', '사용자가 승인되었습니다.');
    }

    public function rejectUser(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', '사용자가 거부되었습니다.');
    }

    public function revokeApproval(User $user)
    {
        $user->reject();
        return redirect()->back()->with('success', '사용자 승인이 취소되었습니다.');
    }

    /**
     * 사용자 문서 다운로드
     */
    public function downloadDocument(UserDocument $document)
    {
        // 권한 확인 (관리자만 다운로드 가능)
        if (!Auth::user()->is_admin) {
            abort(403, '권한이 없습니다.');
        }

        // 파일이 존재하는지 확인
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        // 파일 다운로드
        return Storage::disk('public')->download(
            $document->file_path, 
            $document->original_filename
        );
    }
}