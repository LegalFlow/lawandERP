<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'file_path',
        'original_name',
        'file_size',
        'is_admin_file',
    ];

    protected $casts = [
        'is_admin_file' => 'boolean',
    ];

    // 신청서와의 관계
    public function request()
    {
        return $this->belongsTo(Request::class);
    }
} 