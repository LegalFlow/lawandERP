<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\RequestFile;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    // 모든 신청서 목록 조회
    public function index()
    {
        $requests = Request::with(['user'])
                           ->orderBy('created_at', 'desc')
                           ->paginate(15);

        return view('admin.requests.index', compact('requests'));
    }

    // 신청서 상세 조회
    public function show(Request $request)
    {
        $request->load(['user', 'files', 'processor']);
        
        return view('admin.requests.show', compact('request'));
    }

    // 신청서 처리 (승인/반려)
    public function process(HttpRequest $httpRequest, Request $request)
    {
        // 유효성 검사
        $validator = Validator::make($httpRequest->all(), [
            'status' => 'required|in:승인완료,반려',
            'admin_comment' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240', // 10MB 제한
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 신청서 상태 업데이트
        $request->update([
            'status' => $httpRequest->status,
            'admin_comment' => $httpRequest->admin_comment,
            'processed_by' => Auth::id(),
            'processed_at' => now()
        ]);

        // 관리자 파일 업로드
        if ($httpRequest->hasFile('files')) {
            $files = $httpRequest->file('files');
            $totalSize = 0;
            $count = 0;
            
            foreach ($files as $file) {
                $totalSize += $file->getSize();
                $count++;
                
                // 파일 크기 및 개수 제한
                if ($count > 10 || $totalSize > 10 * 1024 * 1024) {
                    break;
                }
                
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_admin_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('request_pdf', $filename);
                
                RequestFile::create([
                    'request_id' => $request->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'is_admin_file' => true
                ]);
            }
        }

        return redirect()->route('admin.requests.index')
                         ->with('success', '신청서가 성공적으로 처리되었습니다.');
    }

    // 파일 다운로드
    public function downloadFile(RequestFile $file)
    {
        if (!Storage::exists($file->file_path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return Storage::download($file->file_path, $file->original_name);
    }
} 