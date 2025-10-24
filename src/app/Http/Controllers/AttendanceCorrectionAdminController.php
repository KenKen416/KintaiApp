<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceCorrectionAdminController extends Controller
{

    public function show(AttendanceCorrection $attendance_correct_request): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        // attendance と関連データを確実にロード（既にロード済みなら上書きしない）
        $attendance_correct_request->loadMissing(['attendance.user', 'attendance.breakTimes']);

        return view('admin.requests.detail', [
            'correction' => $attendance_correct_request,
            'attendance' => $attendance_correct_request->attendance,
            'nav'        => 'admin',
        ]);
    }


    public function approve(Request $request, AttendanceCorrection $attendance_correct_request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        if ($attendance_correct_request->status === 'approved') {
            return redirect()->route('stamp_correction_request.list')
                ->with('status', 'この申請は既に承認済みです。');
        }

        DB::transaction(function () use ($attendance_correct_request) {
            // 排他ロックして安全に更新
            $attendance = Attendance::where('id', $attendance_correct_request->attendance_id)
                ->lockForUpdate()
                ->firstOrFail();

            // requested_* があれば上書き
            if (! empty($attendance_correct_request->requested_clock_in)) {
                $attendance->clock_in = $attendance_correct_request->requested_clock_in;
            }
            if (! empty($attendance_correct_request->requested_clock_out)) {
                $attendance->clock_out = $attendance_correct_request->requested_clock_out;
            }
            if (! is_null($attendance_correct_request->requested_note)) {
                $attendance->note = $attendance_correct_request->requested_note;
            }
            $attendance->save();

            // 休憩の置換（requested_breaks は array キャスト）
            if (! empty($attendance_correct_request->requested_breaks) && is_array($attendance_correct_request->requested_breaks)) {
                BreakTime::where('attendance_id', $attendance->id)->delete();

                foreach ($attendance_correct_request->requested_breaks as $b) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $b['break_start'] ?? null,
                        'break_end'     => $b['break_end'] ?? null,
                    ]);
                }
            }

            // 申請ステータス更新
            $attendance_correct_request->status = 'approved';
            $attendance_correct_request->save();
        });

        return redirect()->route('stamp_correction_request.list')
            ->with('status', '申請を承認しました。');
    }
}
