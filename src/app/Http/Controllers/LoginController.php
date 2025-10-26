<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();
        $isAdminLoginPage = $request->is('admin/login');

        // 既にログイン済みの場合
        if ($user) {
            if ($isAdminLoginPage) {
                // 管理者でログイン済み → 管理者ダッシュボードへ
                if ($user->is_admin) {
                    return redirect()
                        ->route('admin.attendance.list')
                        ->with('info', '既に管理者としてログインしています。');
                }

                // 一般ユーザーで管理者ログイン画面に来た → 元ページ or 勤怠画面へ戻す
                $previous = url()->previous();
                $fallback = route('attendance.index', absolute: false) ?? '/attendance';
                // 無限ループ（前頁が今のページ）を避ける
                $target = ($previous && $previous !== $request->fullUrl()) ? $previous : $fallback;

                return redirect($target)
                    ->with('error', '一般ユーザーとしてログイン中です。管理者でログインするには一度ログアウトしてください。');
            }

            // 管理者ログイン中に一般ログイン画面 (/login) にアクセスしている
            if ($user->is_admin) {
                return redirect()
                    ->route('admin.attendance.list')
                    ->with('info', '管理者としてログイン中です。');
            }
            // 一般ユーザーでログイン済み → 勤怠画面へ
            return redirect()
                ->route('attendance.index');
        }

        // 未ログイン時表示
        if ($isAdminLoginPage) {
            return view('admin.auth.login', ['nav' => 'none']);
        }

        return view('user.auth.login', ['nav' => 'none']);
    }

    // POST /login /admin/login
    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // どちらのコンテキストか判定
        $isAdminContext = $request->is('admin/login')
            || $request->input('context') === 'admin';

        $user = User::where('email', $credentials['email'])->first();

        // ユーザーが存在しない or パスワード不一致
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        // 管理者ログイン要求だが is_admin=0
        if ($isAdminContext && !$user->is_admin) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput();
        }

        Auth::login($user, false);

        //メール認証前
        if (is_null($user->email_verified_at)) {
            return redirect()->route('verification.notice')
                ->with('error', 'メール認証が完了していません。メール内のリンクを確認してください。');
        }

        // 遷移先
        if ($user->is_admin) {
            return redirect()->intended('/admin/attendance/list');
        }
        return redirect()->intended('/attendance');
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($user && $user->is_admin) {
            return redirect('/admin/login');
        }
        return redirect('/login');
    }
}
