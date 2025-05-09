<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferFile extends Model
{
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const ALLOWED_FILE_TYPE = 'pdf';
    const STORAGE_PATH = '/home/ec2-user/transfer_pdf';

    protected $fillable = [
        'transfer_id',
        'original_name',
        'stored_name',
        'file_path',
        'file_size',
        'del_flag'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'del_flag' => 'boolean',
        'created_at' => 'datetime'
    ];

    // timestamps 설정 (updated_at 컬럼이 없으므로)
    public $timestamps = false;

    // Transfer 관계 설정
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    // 삭제되지 않은 파일만 조회
    public function scopeNotDeleted($query)
    {
        return $query->where('del_flag', false);
    }

    // 파일 전체 경로 가져오기
    public function getFullPathAttribute()
    {
        return self::STORAGE_PATH . '/' . $this->stored_name;
    }

    // 파일 유효성 검사
    public static function validateFile($file)
    {
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('파일 크기는 10MB를 초과할 수 없습니다.');
        }

        if ($file->getClientOriginalExtension() !== self::ALLOWED_FILE_TYPE) {
            throw new \Exception('PDF 파일만 업로드 가능합니다.');
        }

        return true;
    }
}