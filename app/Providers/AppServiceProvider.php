<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\NotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        if(config('app.env') === 'production') {
            \URL::forceScheme('https');
        }

        view()->composer('*', function ($view) {
            if (auth()->check()) {
                $notificationService = new NotificationService();
                $notificationCounts = $notificationService->getNotificationCounts(auth()->id());
                $view->with('notificationCounts', $notificationCounts);
            } else {
                $view->with('notificationCounts', [
                    'notifications' => 0,
                    'salary_contracts' => 0,
                    'salary_statements' => 0,
                    'total' => 0
                ]);
            }
        });
    }
}
