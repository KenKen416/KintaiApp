<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;

class AttendanceListController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $monthParam    = $request->query('month');
        $baseDate      = $this->resolveBaseDate($monthParam);
        $startOfMonth  = $baseDate->copy()->startOfMonth();
        $endOfMonth    = $baseDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->with('breakTimes')
            ->get()
            ->keyBy(fn($a) => $a->work_date->format('Y-m-d'));

        $days = $this->buildDaysArray($startOfMonth, $endOfMonth, $attendances);

        return view('user.attendance.list', [
            'nav'          => 'user',
            'displayMonth' => $baseDate,
            'prevMonth'    => $baseDate->copy()->subMonth()->format('Y-m'),
            'nextMonth'    => $baseDate->copy()->addMonth()->format('Y-m'),
            'days'         => $days,
        ]);
    }

    private function resolveBaseDate(?string $monthParam): Carbon
    {
        // クエリ未指定 → 当月
        if (empty($monthParam)) {
            return Carbon::today()->startOfMonth();
        }

        // 形式チェック失敗 -> 当月
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
            return Carbon::today()->startOfMonth();
        }

        // Carbon でのパース失敗 -> 当月
        $dt = Carbon::createFromFormat('Y-m', $monthParam);
        if ($dt === false) {
            return Carbon::today()->startOfMonth();
        }
        //問題ない場合は当該月の初日を返す
        return $dt->startOfMonth();
    }


    private function buildDaysArray(Carbon $start, Carbon $end, $attendances): array
    {
        $result = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $key        = $day->format('Y-m-d');
            $attendance = $attendances->get($key);

            $result[] = [
                'date'        => $day,
                'attendance'  => $attendance,
                'clock_in'    => $attendance?->clock_in?->format('H:i'),
                'clock_out'   => $attendance?->clock_out?->format('H:i'),
                'break_total' => $attendance ? $this->formatMinutes($this->calcBreakMinutes($attendance)) : '',
                'work_total'  => $attendance ? $this->calcWorkTotalFormatted($attendance) : '',
                'has_detail'  => (bool)$attendance,
            ];
        }
        return $result;
    }

    private function calcBreakMinutes($attendance): int
    {
        $total = 0;

        foreach ($attendance->breakTimes as $b) {
            if (!$b->break_end) {
                continue;
            }

            $minutes = $b->break_end->diffInMinutes($b->break_start);
            $total += $minutes;
        }

        return $total;
    }

    private function calcWorkTotalFormatted($attendance): string
    {
        if (!$attendance->clock_in || !$attendance->clock_out) {
            return '';
        }
        $break = $this->calcBreakMinutes($attendance);
        $total = $attendance->clock_out->diffInMinutes($attendance->clock_in) - $break;
        if ($total < 0) {
            return '';
        }
        return $this->formatMinutes($total);
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0:00';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return sprintf('%d:%02d', $h, $m);
    }
}
