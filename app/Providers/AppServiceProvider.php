<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.app', function ($view): void {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            $view->with([
                'notificationCount' => $user->unreadNotifications()->count(),
                'recentNotifications' => $user->notifications()->limit(6)->get(),
            ]);
        });
    }
}
