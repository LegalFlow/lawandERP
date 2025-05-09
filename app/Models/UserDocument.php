<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'file_path',
        'original_filename',
        'file_type',
        'file_size',
    ];

    /**
     * 이 문서를 업로드한 사용자
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 