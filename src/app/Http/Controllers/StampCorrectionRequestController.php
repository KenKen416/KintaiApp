<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // タブ: pending or approved（不正値は pending に）
        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }

        if ($this->isAdmin($user)) {
            $corrections = $this->getAdminCorrections($tab);
            $isAdmin = true;
            $nav = 'admin'; // 管理者用の nav 値
        } else {
            $corrections = $this->getUserCorrections($user->id, $tab);
            $isAdmin = false;
            $nav = 'user'; // 一般ユーザー用の nav 値
        }

        return view('user.requests.index', [
            'corrections' => $corrections,
            'activeTab'   => $tab,
            'isAdmin'     => $isAdmin,
            'nav'         => $nav,
        ]);
    }

    /**
     * 管理者判定
     */
    protected function isAdmin($user): bool
    {
        return (bool) ($user->is_admin ?? false);
    }

    /**
     * 管理者向け: 全ユーザー分の申請取得
     */
    protected function getAdminCorrections(string $status)
    {
        return AttendanceCorrection::with(['attendance.user'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * 一般ユーザー向け: 自分の申請のみ取得
     */
    protected function getUserCorrections(int $userId, string $status)
    {
        return AttendanceCorrection::with(['attendance'])
            ->where('status', $status)
            ->whereHas('attendance', fn(Builder $q) => $q->where('user_id', $userId))
            ->orderByDesc('created_at')
            ->get();
    }
}
