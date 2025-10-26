<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\AdminAttendanceRequest;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class AttendanceAdminController extends Controller
{

    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        $dateParam = $request->query('date');
        $date = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        // 指定日の attendances を取得（ユーザー情報 / 休憩を合わせて取得）
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $date->toDateString())
            ->get()
            ->sortBy(fn($a) => optional($a->user)->name ?? '');

        // ビューで表示しやすい形に整形（合計休憩分 / 勤務合計を分単位で算出）
        $rows = $attendances->map(function (Attendance $a) {
            $clockIn = $a->clock_in;
            $clockOut = $a->clock_out;

            // 休憩合計（分）
            $breakTotalMinutes = 0;
            foreach ($a->breakTimes as $b) {
                if ($b->break_start && $b->break_end) {
                    $breakTotalMinutes += $b->break_start->diffInMinutes($b->break_end);
                }
            }

            // 勤務合計（分） = (clock_out - clock_in) - breakTotal
            $workTotalMinutes = null;
            if ($clockIn && $clockOut) {
                $totalMinutes = $clockIn->diffInMinutes($clockOut);
                $workTotalMinutes = max(0, $totalMinutes - $breakTotalMinutes);
            }

            return (object) [
                'attendance' => $a,
                'user' => $a->user,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_total_minutes' => $breakTotalMinutes,
                'work_total_minutes' => $workTotalMinutes,
            ];
        });

        return view('admin.attendance.list', [
            'nav' => 'admin',
            'displayDate' => $date,
            'rows' => $rows,
        ]);
    }

    public function show($id): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);

        $breaks = $attendance->breakTimes->sortBy('break_start')->values();

        return view('admin.attendance.detail', [
            'nav' => 'admin',
            'attendance' => $attendance,
            'breaks' => $breaks,
        ]);
    }

    public function update(AdminAttendanceRequest $request, $id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        $attendance = Attendance::with('breakTimes')->findOrFail($id);
        $workDate = $attendance->work_date->format('Y-m-d');

        // 受け取り値から datetime を作成
        $clockIn = $request->input('clock_in') ? Carbon::parse($workDate . ' ' . $request->input('clock_in')) : null;
        $clockOut = $request->input('clock_out') ? Carbon::parse($workDate . ' ' . $request->input('clock_out')) : null;

        // 休憩配列の正規化
        $requestedBreaks = [];
        $breaksInput = $request->input('breaks', []);
        if (is_array($breaksInput)) {
            foreach ($breaksInput as $b) {
                $bs = isset($b['break_start']) && $b['break_start'] !== '' ? Carbon::parse($workDate . ' ' . $b['break_start']) : null;
                $be = isset($b['break_end']) && $b['break_end'] !== '' ? Carbon::parse($workDate . ' ' . $b['break_end']) : null;
                if ($bs || $be) {
                    $requestedBreaks[] = [
                        'break_start' => $bs ? $bs->toDateTimeString() : null,
                        'break_end' => $be ? $be->toDateTimeString() : null,
                    ];
                }
            }
        }

        DB::transaction(function () use ($attendance, $clockIn, $clockOut, $requestedBreaks, $request) {
            if ($clockIn) {
                $attendance->clock_in = $clockIn;
            } else {
                $attendance->clock_in = null;
            }
            if ($clockOut) {
                $attendance->clock_out = $clockOut;
            } else {
                $attendance->clock_out = null;
            }

            $attendance->note = $request->input('note') ?? $attendance->note;
            $attendance->save();

            // 休憩は置換（既存削除→再作成）
            BreakTime::where('attendance_id', $attendance->id)->delete();
            foreach ($requestedBreaks as $b) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $b['break_start'] ?? null,
                    'break_end' => $b['break_end'] ?? null,
                ]);
            }
        });

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id])
            ->with('success', '勤怠を更新しました。');
    }
}
