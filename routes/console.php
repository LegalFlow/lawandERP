<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 연차촉진 통지 체크 (수정된 버전)
Artisan::command('check:annual-leave-notification', function () {
    $this->call('notification:check-annual-leave');
})->purpose('연차 종료 6개월 전인 구성원들에게 자동으로 통지를 보냅니다.')->dailyAt('01:30');

// 지각 통지 체크
Artisan::command('check:late-attendance-notification', function () {
    $this->call('notification:check-late-attendance');
})->purpose('분기별 지각 3회 이상인 구성원들에게 자동으로 통지를 보냅니다.')->dailyAt('01:30');

// 기각/불허가결정 통지 체크
Artisan::command('check:dismissal-notification', function () {
    $this->call('notification:check-dismissal');
})->purpose('개인회생절차개시신청 기각결정 및 면책신청 불허가결정에 대한 경위서 제출 통지를 자동으로 생성합니다.')->dailyAt('01:30');
