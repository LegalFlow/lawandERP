<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',  // 모든 API 라우트
        'api/login',  // API 로그인
        'api/payments',  // 결제 데이터
        'api/transactions',  // 거래 데이터
        'api/transactions2',  // 거래 데이터 2
        'api/transactions3'   // 거래 데이터 3
    ];
} 