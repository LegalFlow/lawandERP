<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserApproval
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->isApproved()) {
            // 승인 대기 페이지는 접근 허용
            if ($request->routeIs('awaiting.approval') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('awaiting.approval');
        }

        return $next($request);
    }
}