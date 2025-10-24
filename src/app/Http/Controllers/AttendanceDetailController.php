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
    /**
     * 勤怠詳細表示
     *
     * - 管理者は任意の勤怠を閲覧できる
     * - 一般ユーザーは自分の勤怠のみ閲覧可能
     * - 承認待ち（pending）の修正申請がある場合はその申請内容を優先表示する
     */
    public function show($id)
    {
        // 対象勤怠を取得（休憩と user をまとめて取得）
        $attendance = Attendance::with(['breakTimes', 'user'])->findOrFail($id);

        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        // 管理者でなければ本人のみ閲覧可
        if (! ($user->is_admin ?? false) && $attendance->user_id !== $user->id) {
            abort(404);
        }

        // 当該勤怠に対して最新の承認待ち（pending）申請を取得
        $pendingCorrection = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->first();

        $isPending = (bool) $pendingCorrection;

        // 休憩は開始順にソートしてビューへ渡す
        $breaks = $attendance->breakTimes->sortBy('break_start')->values();

        return view('user.attendance.detail', [
            'nav'               => $user->is_admin ? 'admin' : 'user',
            'attendance'        => $attendance,
            'breaks'            => $breaks,
            'isPending'         => $isPending,
            'pendingCorrection' => $pendingCorrection,
        ]);
    }

    /**
     * 修正申請を保存する（ユーザーの操作）
     */
    public function storeCorrection(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        // 対象勤怠を取得（休憩は不要だが安全のためロード）
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 本人以外は 404
        if ($attendance->user_id !== Auth::id()) {
            abort(404);
        }

        // 既に pending がある場合は二重申請を阻止
        $hasPending = AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return redirect()->back()->with('error', '既に申請中の修正があります。承認をお待ちください。');
        }

        // 入力値整形（時間入力は "HH:MM" 文字列で受け取り、attendance_corrections の datetime へマージ）
        $clockInInput = $request->input('requested_clock_in');
        $clockOutInput = $request->input('requested_clock_out');

        $clockIn = null;
        $clockOut = null;

        if ($clockInInput) {
            // work_date の日付部分を使って datetime を作成
            $clockIn = Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $clockInInput);
        }
        if ($clockOutInput) {
            $clockOut = Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $clockOutInput);
        }

        // 休憩は配列で受け取って JSON に（フォームの名前付けに依存）
        $requestedBreaks = null;
        if ($request->has('breaks')) {
            $breaksInput = $request->input('breaks'); // 想定: array of ['break_start' => 'HH:MM', 'break_end' => 'HH:MM']
            $normalized = [];
            foreach ($breaksInput as $b) {
                $bs = $b['break_start'] ?? null;
                $be = $b['break_end'] ?? null;
                if ($bs || $be) {
                    $normalized[] = [
                        'break_start' => $bs ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $bs)->toDateTimeString() : null,
                        'break_end'   => $be ? Carbon::parse($attendance->work_date->format('Y-m-d') . ' ' . $be)->toDateTimeString() : null,
                    ];
                }
            }
            $requestedBreaks = ! empty($normalized) ? $normalized : null;
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
