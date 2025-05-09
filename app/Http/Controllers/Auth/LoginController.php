<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle the login request
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // 승인되지 않은 사용자는 승인 대기 페이지로
            if (!$user->isApproved()) {
                return redirect()->route('awaiting.approval');
            }

            // 승인된 사용자는 대시보드로
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => '이메일 또는 비밀번호가 일치하지 않습니다.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle the logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Handle API login request
     */
    public function apiLogin(Request $request)
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            // 기존 토큰들 삭제 (선택사항)
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $user->tokens()->delete();
                
                // 승인되지 않은 사용자 체크
                if (!$user->isApproved()) {
                    return response()->json([
                        'message' => '승인 대기 중인 계정입니다.'
                    ], 403);
                }

                // 새 토큰 생성
                $token = $user->createToken('api-token')->plainTextToken;
                
                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ], 200);
            }

            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 일치하지 않습니다.'],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => '이메일 또는 비밀번호가 일치하지 않습니다.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '로그인 처리 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
