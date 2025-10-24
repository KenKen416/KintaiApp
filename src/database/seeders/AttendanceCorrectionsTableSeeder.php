<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;

class AttendanceCorrectionsTableSeeder extends Seeder
{

    public function run()
    {
        $attendances = Attendance::inRandomOrder()->limit(8)->get();

        foreach ($attendances as $a) {
            $workDate = optional($a->work_date)->format('Y-m-d') ?: Carbon::parse($a->work_date)->format('Y-m-d');

            $requestedClockIn = Carbon::parse("{$workDate} 09:10")->toDateTimeString();
            $requestedClockOut = Carbon::parse("{$workDate} 18:10")->toDateTimeString();

            $requestedBreaks = [
                [
                    'break_start' => Carbon::parse("{$workDate} 12:00")->toDateTimeString(),
                    'break_end' => Carbon::parse("{$workDate} 13:00")->toDateTimeString(),
                ],
            ];

            AttendanceCorrection::create([
                'attendance_id' => $a->id,
                'requested_clock_in' => $requestedClockIn,
                'requested_clock_out' => $requestedClockOut,
                'requested_breaks' => $requestedBreaks,
                'requested_note' => '打刻ミスの修正申請です。',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
