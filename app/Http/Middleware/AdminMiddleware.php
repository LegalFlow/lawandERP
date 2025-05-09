<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, '관리자만 접근할 수 있습니다.');
        }

        return $next($request);
    }
}