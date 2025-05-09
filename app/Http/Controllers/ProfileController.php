<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * 회원정보 수정 폼 표시
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * 회원정보 업데이트
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // 유효성 검사
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'resident_id_front' => ['required', 'digits:6'],
            'resident_id_back' => ['required', 'digits:7'],
            'bank' => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'max:10'],
            'address_main' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'documents.*' => [
                'nullable',
                'file',
                'mimes:pdf,png,jpg,jpeg',
                'max:10240', // 10MB
            ],
            'current_password' => ['nullable', 'required_with:password', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('현재 비밀번호가 일치하지 않습니다.');
                }
            }],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // 사용자 정보 업데이트
        $user->name = $request->name;
        $user->email = $request->email;
        $user->resident_id_front = $request->resident_id_front;
        $user->resident_id_back = $request->resident_id_back;
        $user->bank = $request->bank;
        $user->account_number = $request->account_number;
        $user->phone_number = $request->phone_number;
        $user->postal_code = $request->postal_code;
        $user->address_main = $request->address_main;
        $user->address_detail = $request->address_detail;
        $user->join_date = $request->join_date;
        
        // 비밀번호 변경이 요청된 경우
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

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

        return redirect()->route('profile.edit')->with('success', '회원정보가 성공적으로 업데이트되었습니다.');
    }

    /**
     * 첨부 파일 삭제
     */
    public function deleteDocument(UserDocument $document)
    {
        // 권한 확인 (본인 파일만 삭제 가능)
        if ($document->user_id !== Auth::id()) {
            abort(403, '권한이 없습니다.');
        }

        // 파일이 존재하는지 확인
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // DB에서 레코드 삭제
        $document->delete();

        return redirect()->route('profile.edit')->with('success', '파일이 삭제되었습니다.');
    }
} 