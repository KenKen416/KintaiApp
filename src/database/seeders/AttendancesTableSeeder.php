<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendancesTableSeeder extends Seeder
{

    public function run()
    {
        $staffs = User::where('is_admin', false)->get();

        // 対象期間：前月の開始日 〜 当月の終了日
        $now = Carbon::now();
        $prev = $now->copy()->subMonth();

        $start = $prev->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $period = CarbonPeriod::create($start, $end);

        foreach ($staffs as $staff) {
            foreach ($period as $day) {
                // ランダムで出勤を作成（例: 70% の確率で出勤）
                if (rand(1, 100) <= 70) {
                    $workDate = $day->toDateString();
                    $clockIn = Carbon::parse("{$workDate} 09:00");
                    $clockOut = Carbon::parse("{$workDate} 18:00");

                    $attendance = Attendance::create([
                        'user_id'    => $staff->id,
                        'work_date'  => $workDate,
                        'clock_in'   => $clockIn->toDateTimeString(),
                        'clock_out'  => $clockOut->toDateTimeString(),
                        'note'       => (rand(1, 10) <= 2) ? '在宅勤務' : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 休憩（1件: 12:00-13:00）
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => Carbon::parse("{$workDate} 12:00")->toDateTimeString(),
                        'break_end'     => Carbon::parse("{$workDate} 13:00")->toDateTimeString(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    // たまに追加の短い休憩を追加
                    if (rand(1, 100) <= 10) {
                        BreakTime::create([
                            'attendance_id' => $attendance->id,
                            'break_start'   => Carbon::parse("{$workDate} 15:00")->toDateTimeString(),
                            'break_end'     => Carbon::parse("{$workDate} 15:10")->toDateTimeString(),
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                } else {
                }
            }
        }
    }
}
