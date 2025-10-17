<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = $guards ?: [null];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // 管理者は管理側へ、一般ユーザーはユーザー側へ
                $routeName = $user->is_admin
                    ? 'admin.attendance.list'
                    : 'attendance.index';

                return redirect()->route($routeName)->with('error', '既にログインしています。');
            }
        }

        return $next($request);
    }
}
