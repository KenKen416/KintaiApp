<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    // 勤務外の場合、画面に「勤務外」と表示される
    public function test_status_none_shows_kinmugai()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 09:00:00'));
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        // 当日分の Attendance を作成しない（勤務外の状態）
        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('勤務外');
    }

    // 出勤中の場合、画面に「出勤中」と表示される
    public function test_status_working_shows_shukkinchu()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 09:00:00'));
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
            'note' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }

    // 休憩中の場合、画面に「休憩中」と表示される
    public function test_status_on_break_shows_kyuukeichu()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 13:00:00'));
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(4)->toDateTimeString(),
            'clock_out' => null,
            'note' => null,
        ]);

        // 休憩開始のみ（break_end が null） -> 休憩中
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subHour()->toDateTimeString(),
            'break_end' => null,
        ]);
        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
    }

    // 退勤済の場合、画面に「退勤済」と表示される
    public function test_status_finished_shows_taikinzumi()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 19:00:00'));
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(10)->toDateTimeString(),
            'clock_out' => Carbon::now()->toDateTimeString(),
            'note' => null,
        ]);
        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
    }
}
