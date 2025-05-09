<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RegisterController extends Controller
{
    /**
     * Show the registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle the registration request
     */
    public function register(Request $request)
    {
        // 유효성 검사
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'resident_id_front' => ['required', 'digits:6'],
            'resident_id_back' => ['required', 'digits:7'],
            'bank' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'max:10'],
            'address_main' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'documents' => ['required', 'array', 'max:10'],
            'documents.*' => [
                'required',
                'file',
                'mimes:pdf,png,jpg,jpeg',
                'max:10240', // 10MB
            ],
        ]);

        // 사용자 생성
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'resident_id_front' => $request->resident_id_front,
            'resident_id_back' => $request->resident_id_back,
            'bank' => $request->bank,
            'account_number' => $request->account_number,
            'phone_number' => $request->phone_number,
            'postal_code' => $request->postal_code,
            'address_main' => $request->address_main,
            'address_detail' => $request->address_detail,
            'join_date' => $request->join_date,
            'is_approved' => false,
        ]);

        // 파일 업로드 처리
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('user_documents/' . $user->id, 'public');
                
                UserDocument::create([
                    'user_id' => $user->id,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // 로그인
        Auth::login($user);

        // 승인 대기 페이지로 리다이렉트
        return redirect()->route('awaiting.approval');
    }

    /**
     * Show the awaiting approval page
     */
    public function awaitingApproval()
    {
        return view('auth.awaiting-approval');
    }

    /**
     * Show pending users list (admin only)
     */
    public function pendingUsers()
    {
        $users = User::where('is_approved', false)
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('admin.users.pending', compact('users'));
    }

    /**
     * Approve a user
     */
    public function approveUser(User $user)
    {
        $user->approve(Auth::id());
        
        return back()->with('success', '사용자가 승인되었습니다.');
    }

    /**
     * Reject a user
     */
    public function rejectUser(User $user)
    {
        $user->reject();
        
        return back()->with('success', '사용자가 거부되었습니다.');
    }
}
