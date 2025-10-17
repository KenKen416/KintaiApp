<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->with(['breakTimes' => fn($q) => $q->orderBy('break_start')])
            ->first();

        $status = $this->determineStatus($attendance);
        if ($status === 'finished') {
            $nav = 'user-after';
        } else {
            $nav = 'user';
        }

        return view('user.attendance.index', [
            'nav'        => $nav,
            'attendance' => $attendance,
            'status'     => $status,
            'now'        => Carbon::now(),
        ]);
    }

    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($existing) {
            return redirect()->route('attendance.index')
                ->with('error', '本日の出勤は既に記録されています。');
        }

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => $today,
            'clock_in'  => Carbon::now(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', '出勤を記録しました。');
    }

    public function breakStart(Request $request)
    {
        $attendance = $this->getTodayAttendanceOrFail();
        $status = $this->determineStatus($attendance);

        if ($status !== 'working') {
            return redirect()->route('attendance.index')
                ->with('error', '休憩を開始できる状態ではありません。');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => now(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', '休憩を開始しました。');
    }

    public function breakEnd(Request $request)
    {
        $attendance = $this->getTodayAttendanceOrFail();
        $status = $this->determineStatus($attendance);

        if ($status !== 'on_break') {
            return redirect()->route('attendance.index')
                ->with('error', '休憩を終了できる状態ではありません。');
        }

        $openBreak = $attendance->breakTimes()
            ->whereNull('break_end')
            ->orderByDesc('break_start')
            ->first();

        if (!$openBreak) {
            return redirect()->route('attendance.index')
                ->with('error', '終了できる休憩が見つかりません。');
        }

        $openBreak->update(['break_end' => now()]);

        return redirect()->route('attendance.index')
            ->with('success', '休憩を終了しました。');
    }

    public function clockOut(Request $request)
    {
        $attendance = $this->getTodayAttendanceOrFail();
        $status = $this->determineStatus($attendance);

        if (!in_array($status, ['working'])) {
            return redirect()->route('attendance.index')
                ->with('error', '退勤できる状態ではありません。');
        }

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', '退勤を記録しました。お疲れ様でした。');
    }

    private function determineStatus(?Attendance $attendance): string
    {
        if (!$attendance) {
            return 'none';      // 勤務外
        }
        if ($attendance->clock_out) {
            return 'finished';  // 退勤済
        }
        $openBreak = $attendance->breakTimes
            ->first(fn($b) => $b->break_end === null);
        if ($openBreak) {
            return 'on_break';  // 休憩中
        }
        return 'working';       // 出勤中
    }

    private function getTodayAttendanceOrFail(): Attendance
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', Carbon::today())
            ->with('breakTimes')
            ->first();

        if (!$attendance) {
            abort(404, '本日の出勤が記録されていません。');
        }
        return $attendance;
    }
}
