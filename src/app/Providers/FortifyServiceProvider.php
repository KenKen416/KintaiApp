<?php

namespace App\Providers;


use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortifyのログイン・登録POSTアクションを自作コントローラーへbind
        $this->app->bind(LoginResponse::class, LoginController::class);
        $this->app->bind(RegisterResponse::class, RegisterController::class);

        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(function () {
            if (request()->is('admin/login')) {
                return view('admin.auth.login');
            }
            return view('user.auth.login');
        });

        Fortify::registerView(function () {
            return view('user.auth.register');
        });

        Fortify::verifyEmailView(fn() => view('user.auth.verify-email'));

        // 管理者ログイン判定（FormRequestでcontext=adminをhiddenで送ること）
        Fortify::authenticateUsing(function (Request $request) {
            $credentials = $request->only('email', 'password');
            $user = User::where('email', $credentials['email'])->first();

            if ($user && Hash::check($credentials['password'], $user->password)) {
                // 管理者ログインフォームからのPOSTなら is_admin=true のみ許可
                if ($request->input('context') === 'admin' && !$user->is_admin) {
                    return null;
                }
                // 一般ログインはどちらでもOK
                return $user;
            }
            return null;
        });
    }
}
