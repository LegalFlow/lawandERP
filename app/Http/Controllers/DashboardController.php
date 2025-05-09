<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = request('month', Carbon::now()->format('Y-m'));
        $baseUrl = config('services.retool.dashboard_url');
        
        $embedParams = http_build_query([
            'embed' => 'true',
            'headerless' => 'true',
            'navigationDisabled' => 'true',
            'editDisabled' => 'true',
            'fullScreen' => 'true'
        ]);
        
        $dashboardUrl = $baseUrl . '?' . $embedParams;
        
        try {
            return view('dashboard', compact('currentMonth', 'dashboardUrl'));
        } catch (\Exception $e) {
            \Log::error('Dashboard Error: ' . $e->getMessage());
            return view('dashboard')->with('error', '데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }
}
