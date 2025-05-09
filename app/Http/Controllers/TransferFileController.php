<?php

namespace App\Http\Controllers;

use App\Models\TransferFile;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransferFileController extends Controller
{
    public function download(TransferFile $file)
    {
        // 삭제된 파일인지 확인
        if ($file->del_flag) {
            return back()->with('error', '삭제된 파일입니다.');
        }

        $filePath = $file->full_path;

        // 파일 존재 여부 및 읽기 권한 확인
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return back()->with('error', '파일을 찾을 수 없거나 읽을 수 없습니다.');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"'
        ]);
    }

    public function destroy(TransferFile $file)
    {
        // 이미 삭제된 파일인지 확인
        if ($file->del_flag) {
            return back()->with('error', '이미 삭제된 파일입니다.');
        }

        // 물리적 파일 삭제는 하지 않고 del_flag만 변경
        $file->update(['del_flag' => true]);
        
        return response()->json(['success' => true]); // AJAX 응답으로 변경
    }

    public function downloadAll($transferId)
    {
        $files = TransferFile::where('transfer_id', $transferId)
            ->where('del_flag', false)
            ->get();

        if ($files->isEmpty()) {
            return back()->with('error', '다운로드할 파일이 없습니다.');
        }

        // 단일 파일인 경우 바로 다운로드
        if ($files->count() === 1) {
            $file = $files->first();
            $filePath = $file->full_path;
            
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return back()->with('error', '파일을 찾을 수 없거나 읽을 수 없습니다.');
            }

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"'
            ]);
        }

        // 여러 파일인 경우 ZIP으로 압축하여 다운로드
        $zipFileName = 'files_' . $transferId . '_' . date('YmdHis') . '.zip';
        $zipFilePath = storage_path('app/temp/' . $zipFileName);

        // temp 디렉토리가 없으면 생성
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($files as $file) {
                $filePath = $file->full_path;
                if (file_exists($filePath) && is_readable($filePath)) {
                    $zip->addFile($filePath, $file->original_name);
                }
            }
            $zip->close();

            // ZIP 파일 다운로드 후 삭제를 위해 afterResponse 콜백 사용
            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'ZIP 파일 생성 중 오류가 발생했습니다.');
    }
}