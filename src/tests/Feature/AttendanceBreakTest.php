<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    // 休憩ボタンが正しく機能する
    public function test_break_button_visible_and_changes_to_on_break()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 12:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        // 当日の出勤状態を作成（出勤中）
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 画面に「休憩入」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSeeText('休憩入');

        // 休憩開始処理を実行
        $post = $this->post(route('attendance.break_start'));
        $post->assertRedirect(route('attendance.index'));

        // 処理後に画面上のステータスが「休憩中」になることを確認
        $follow = $this->get(route('attendance.index'));
        $follow->assertStatus(200);
        $follow->assertSeeText('休憩中');
    }

    // 休憩は一日に何回でもできる
    public function test_break_can_be_done_multiple_times_and_break_button_reappears()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 12:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 1回目の休憩開始→終了
        $this->post(route('attendance.break_start'));
        Carbon::setTestNow($now->copy()->addMinutes(30));
        $this->post(route('attendance.break_end'));

        // 終了後は再度「休憩入」ボタンが表示される（＝もう一度休憩できる）
        $res = $this->get('/attendance');
        $res->assertStatus(200);
        $res->assertSeeText('休憩入');
    }

    // 休憩戻ボタンが正しく機能する
    public function test_break_end_changes_status_back_to_working()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 13:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(2)->toDateTimeString(),
            'clock_out' => null,
            'note' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 休憩開始
        $this->post(route('attendance.break_start'));
        // 画面上のステータスが「休憩中」になり、休憩戻ボタンが表示されることを確認
        $mid = $this->get(route('attendance.index'));
        $mid->assertStatus(200);
        $mid->assertSeeText('休憩中');
        $mid->assertSeeText('休憩戻');

        // 時刻を進めて休憩終了
        Carbon::setTestNow($now->copy()->addMinutes(15));
        $this->post(route('attendance.break_end'));

        // 処理後、画面上のステータスが「出勤中」になる
        $follow = $this->get(route('attendance.index'));
        $follow->assertStatus(200);
        $follow->assertSeeText('出勤中');
    }

    // 休憩戻は一日に何回でもできる
    public function test_multiple_break_cycles_create_multiple_records_and_show_break_return_button()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 10:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->toDateTimeString(),
            'clock_out' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 1回目の休憩開始→終了
        $this->post(route('attendance.break_start'));
        Carbon::setTestNow($now->copy()->addMinutes(20));
        $this->post(route('attendance.break_end'));

        // 2回目の休憩開始
        Carbon::setTestNow($now->copy()->addHours(1));
        $this->post(route('attendance.break_start'));

        // 休憩中なので「休憩戻」ボタンが表示されることを確認
        $res = $this->get('/attendance');
        $res->assertStatus(200);
        $res->assertSeeText('休憩戻');

        // 最終的に休憩を戻して DB に2レコード存在することを確認
        Carbon::setTestNow($now->copy()->addHours(1)->addMinutes(30));
        $this->post(route('attendance.break_end'));

        $this->assertDatabaseCount('break_times', 2);
        $this->assertEquals(2, $attendance->fresh()->breakTimes()->count());
    }

    // 休憩時間（合計）が勤怠一覧画面に表示されていることを確認する
    public function test_break_times_are_visible_in_attendance_list()
    {
        // 休憩の開始・終了を明確に設定（合計 1時間）
        $start = Carbon::parse('2025-10-24 12:00:00');
        $end = $start->copy()->addHour();

        // 休憩開始時刻にテスト時刻を固定
        Carbon::setTestNow($start);

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $start->toDateString(),
            'clock_in' => $start->copy()->subHours(3)->toDateTimeString(),
            'clock_out' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 休憩開始（at $start）
        $this->post(route('attendance.break_start'));

        // 休憩終了
        Carbon::setTestNow($end);
        $this->post(route('attendance.break_end'));

        // 休憩合計を分で計算して表示フォーマット（H:MM）を作成
        $breakMinutes = Carbon::parse($end)->diffInMinutes($start);
        $h = intdiv($breakMinutes, 60);
        $m = $breakMinutes % 60;
        $expected = sprintf('%d:%02d', $h, $m);

        // 勤怠一覧画面に休憩合計が表示されていることを確認
        $res = $this->get('/attendance/list');
        $res->assertStatus(200);
        $res->assertSeeText($expected);
    }
}