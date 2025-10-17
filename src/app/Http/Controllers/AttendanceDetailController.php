<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AttendanceDetailController extends Controller
{
    public function show($id)
    {
        // 対象勤怠を取得（休憩もまとめて取得）
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 本人以外は存在しない扱い（404）
        if ($attendance->user_id !== Auth::id()) {
            abort(404);
        }

        // 当該勤怠に対して承認待ち（pending）が存在するか
        $isPending = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        // 休憩は開始順にソートしてビューへ渡す
        $breaks = $attendance->breakTimes->sortBy('break_start')->values();

        return view('user.attendance.detail', [
            'nav'        => 'user',
            'attendance' => $attendance,
            'breaks'     => $breaks,
            'isPending'  => $isPending,
        ]);
    }

    public function storeCorrection(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        // 対象勤怠を取得（休憩は不要だが安全のためロード）
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 本人以外は 404
        if ($attendance->user_id !== Auth::id()) {
            abort(404);
        }

        // 既に承認待ちの申請がある場合は弾く（早期リターン）
        $hasPending = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return back()
                ->with('status', '承認待ちのため修正はできません。')
                ->withInput();
        }

        // 入力（HH:MM）を当日の日時に合成するヘルパ
        $workDate = $attendance->work_date; // Carbon cast (date)

        $clockIn  = $request->filled('clock_in')
            ? Carbon::parse($workDate->toDateString() . ' ' . $request->input('clock_in'))
            : null;

        $clockOut = $request->filled('clock_out')
            ? Carbon::parse($workDate->toDateString() . ' ' . $request->input('clock_out'))
            : null;

        // 休憩配列を requested_breaks 用に整形
        $breakStarts = (array) $request->input('break_start', []);
        $breakEnds   = (array) $request->input('break_end', []);
        $requestedBreaks = [];

        $count = max(count($breakStarts), count($breakEnds));
        for ($i = 0; $i < $count; $i++) {
            $bs = $breakStarts[$i] ?? null;
            $be = $breakEnds[$i] ?? null;

            $requestedBreaks[] = [
                'break_start' => $bs ? $workDate->toDateString() . ' ' . $bs : null,
                'break_end'   => $be ? $workDate->toDateString() . ' ' . $be : null,
            ];
        }

        // DB トランザクションで作成
        DB::transaction(function () use ($attendance, $clockIn, $clockOut, $requestedBreaks, $request) {
            AttendanceCorrection::create([
                'attendance_id'        => $attendance->id,
                'requested_clock_in'   => $clockIn,
                'requested_clock_out'  => $clockOut,
                'requested_breaks'     => $requestedBreaks,
                'requested_note'       => $request->input('requested_note'),
                'status'               => 'pending',
            ]);
        });

        return back()->with('status', '修正申請を送信しました。承認待ちです。');
    }
}
