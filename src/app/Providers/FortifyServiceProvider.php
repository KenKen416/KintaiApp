<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fortify のコントローラを自前へ差し替え
        $this->app->bind(RegisteredUserController::class, RegisterController::class);
        $this->app->bind(AuthenticatedSessionController::class, LoginController::class);
    }

    public function boot(): void
    {
        Fortify::verifyEmailView(fn() => view('auth.verify-email'));

        RateLimiter::for('login', function (Request $request) {

            $key = (string) $request->input('email') . '|' . $request->ip();
            return Limit::perMinute(10)->by($key);
        });
    }
}
