<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAdminController extends Controller
{
    /**
     * 管理者: スタッフ一覧
     * GET /admin/staff/list
     *
     * ページネーションを使用せず、全スタッフ（一般ユーザー）を取得して表示します。
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        // 一般ユーザーのみを対象（管理者を除外）
        $users = User::query()
            ->where('is_admin', false)
            ->with(['attendances' => function ($q2) {
                // 最新勤怠1件を取得（一覧で最近打刻日を表示する用途）
                $q2->orderByDesc('work_date')->limit(1);
            }])
            ->orderBy('name')
            ->get();

        return view('admin.staff.list', [
            'nav'   => 'admin',
            'users' => $users,
        ]);
    }

    /**
     * 管理者: 指定スタッフの「現在の月」の月次勤怠一覧表示
     * GET /admin/attendance/staff/{id}
     *
     * 仕様: 常に現在の月を表示します（クエリの month パラメータは無視）。
     *
     * 各日（startOfMonth..endOfMonth）を必ず行として作成し、
     * 当該日に Attendance レコードがあれば表示、なければ空行（出勤・退勤・休憩は空）を表示します。
     *
     * @param Request $request
     * @param int|string $id
     * @return View
     */
    public function attendance(Request $request, $id): View
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        $staff = User::findOrFail($id);

        $monthParam = $request->query('month');
        if (! empty($monthParam) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
            try {
                $base = Carbon::createFromFormat('Y-m', $monthParam);
                if ($base === false) {
                    $base = Carbon::now();
                }
            } catch (\Throwable $e) {
                $base = Carbon::now();
            }
        } else {
            $base = Carbon::now();
        }
        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        // まず当該月の attendances を取って map にする（キー: YYYY-MM-DD）
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($a) => $a->work_date->format('Y-m-d'));

        // CarbonPeriod で当月の日ごとに行を作る（出勤データがなければ attendance=null の行を作成）
        $period = CarbonPeriod::create($start, $end);

        $rows = collect();

        foreach ($period as $day) {
            /** @var Carbon $day */
            $key = $day->format('Y-m-d');
            $a = $attendances->get($key);

            if ($a) {
                $clockIn = $a->clock_in ? $a->clock_in->format('H:i') : '';
                $clockOut = $a->clock_out ? $a->clock_out->format('H:i') : '';

                $breakTotal = 0;
                foreach ($a->breakTimes as $b) {
                    if ($b->break_start && $b->break_end) {
                        $breakTotal += $b->break_end->diffInMinutes($b->break_start);
                    }
                }

                $workTotal = null;
                if ($a->clock_in && $a->clock_out) {
                    $totalMin = $a->clock_out->diffInMinutes($a->clock_in);
                    $workTotal = max(0, $totalMin - $breakTotal);
                }

                $rows->push((object) [
                    'attendance' => $a,
                    'date' => $day,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'break_total_minutes' => $breakTotal,
                    'work_total_minutes' => $workTotal,
                ]);
            } else {
                // 出勤データなしの日：attendance=null、休憩は null にしてビューで空欄表示させる
                $rows->push((object) [
                    'attendance' => null,
                    'date' => $day,
                    'clock_in' => '',
                    'clock_out' => '',
                    // ここを 0 ではなく null にすることで、ビューのフォーマッタが空文字を返し
                    // "0:00" 表示にならず空欄（'-'）になります
                    'break_total_minutes' => null,
                    'work_total_minutes' => null,
                ]);
            }
        }

        return view('admin.attendance.staff-month', [
            'nav' => 'admin',
            'staff' => $staff,
            'rows' => $rows,
            'displayMonth' => $base,
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /**
     * 管理者: 指定スタッフの「現在の月」の月次勤怠を CSV 出力
     * GET /admin/attendance/staff/{id}/export
     *
     * 仕様変更: 出勤実績がない日も含めて当該月の日ごとに CSV に出力します。
     *
     * @param Request $request
     * @param int|string $id
     * @return StreamedResponse
     */
    public function exportAttendance(Request $request, $id): StreamedResponse
    {
        $user = Auth::user();
        if (! $user || ! ($user->is_admin ?? false)) {
            abort(403);
        }

        $staff = User::findOrFail($id);

        $monthParam = $request->query('month');
        if (! empty($monthParam) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthParam)) {
            try {
                $base = Carbon::createFromFormat('Y-m', $monthParam);
                if ($base === false) {
                    $base = Carbon::now();
                }
            } catch (\Throwable $e) {
                $base = Carbon::now();
            }
        } else {
            $base = Carbon::now();
        }
        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        // 取得した attendances をキー付きコレクションにする
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $staff->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn($a) => $a->work_date->format('Y-m-d'));

        $filename = sprintf('%s_attendance_%s.csv', str_replace(' ', '_', $staff->name), $base->format('Y_m'));

        $response = new StreamedResponse(function () use ($attendances, $start, $end) {
            $fh = fopen('php://output', 'w');
            // UTF-8 BOM を追加すると Excel で開いた際に文字化けしにくくなります
            fwrite($fh, "\xEF\xBB\xBF");
            // ヘッダ行（休憩合計を H:MM 表示にする旨を明示）
            fputcsv($fh, ['日付', '出勤', '退勤', '休憩合計(H:MM)', '勤務合計(H:MM)']);

            // 日本語曜日マップ（日:0 ... 土:6）
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

            // 月の日ごとにループして、実績がない日も空欄の行として出力する
            $period = \Carbon\CarbonPeriod::create($start, $end);
            foreach ($period as $day) {
                $key = $day->format('Y-m-d');
                $a = $attendances->get($key);

                // 日付表示を一覧と同じ形式にする（例: 10/01（水））
                $displayDate = $day->format('m/d') . '（' . $weekdays[$day->dayOfWeek] . '）';

                if ($a) {
                    // 実績あり
                    $breakTotal = 0;
                    foreach ($a->breakTimes as $b) {
                        if ($b->break_start && $b->break_end) {
                            // BreakTime の cast が datetime なら diffInMinutes が使える
                            $breakTotal += $b->break_end->diffInMinutes($b->break_start);
                        }
                    }

                    // 休憩合計を H:MM に整形（0 分の場合は空文字列に）
                    $breakTotalStr = '';
                    if ($breakTotal > 0) {
                        $bh = intdiv($breakTotal, 60);
                        $bm = $breakTotal % 60;
                        $breakTotalStr = sprintf('%d:%02d', $bh, $bm);
                    }

                    $workTotal = '';
                    if ($a->clock_in && $a->clock_out) {
                        $mins = max(0, $a->clock_out->diffInMinutes($a->clock_in) - $breakTotal);
                        $h = intdiv($mins, 60);
                        $m = $mins % 60;
                        $workTotal = sprintf('%d:%02d', $h, $m);
                    }

                    fputcsv($fh, [
                        $displayDate,
                        $a->clock_in ? $a->clock_in->format('H:i') : '',
                        $a->clock_out ? $a->clock_out->format('H:i') : '',
                        $breakTotalStr,
                        $workTotal,
                    ]);
                } else {
                    // 実績なし：日付のみ出力し、他は空欄にする
                    fputcsv($fh, [
                        $displayDate,
                        '', // 出勤
                        '', // 退勤
                        '', // 休憩合計(H:MM)
                        '', // 勤務合計(H:MM)
                    ]);
                }
            }

            fclose($fh);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}