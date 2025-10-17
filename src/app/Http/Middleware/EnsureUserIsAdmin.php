<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 管理者でログイン済みなら通過
        if ($request->user() && $request->user()->is_admin) {
            return $next($request);
        }

        // 未ログイン または 一般ユーザー -> 管理者ログイン画面へ
        // 既に admin/login に居る場合に無限リダイレクトを避ける保険（将来付け忘れ対策）
        if ($request->is('admin/login')) {
            return $next($request);
        }

        return redirect()->route('admin.login')
            ->with('error', '管理者としてログインしてください。');
    }
}
