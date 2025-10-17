<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrection;
use Illuminate\View\View;

class AttendanceCorrectionAdminController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        // タブ: pending / approved
        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['pending', 'approved'], true)) {
            $tab = 'pending';
        }

        // 全ユーザー分の申請を取得（attendance と user を eager load）
        $corrections = AttendanceCorrection::with(['attendance.user'])
            ->where('status', $tab)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.requests.index', [
            'nav' => 'admin',
            'corrections' => $corrections,
            'activeTab' => $tab,
        ]);
    }
}
