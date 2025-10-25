<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    // 出勤ボタンが正しく機能する
    public function test_attendance_clock_in_button_and_status_change()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 画面に「出勤」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSeeText('出勤');

        // 出勤処理を実行
        $post = $this->post(route('attendance.clock_in'));
        $post->assertRedirect(route('attendance.index'));

        // 処理後、画面上のステータスが「出勤中」になることを確認
        $follow = $this->get(route('attendance.index'));
        $follow->assertStatus(200);
        $follow->assertSeeText('出勤中');
    }

    // 出勤は一日一回のみできる
    public function test_clock_in_only_once_per_day()
    {
        Carbon::setTestNow(Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        // 既に当日分の出勤・退勤が存在する（退勤済の状態）
        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in'  => Carbon::now()->subHours(8)->toDateTimeString(),
            'clock_out' => Carbon::now()->subHours(1)->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 出勤処理を実行（できないはず）
        $post = $this->post(route('attendance.clock_in'));
        $post->assertRedirect(route('attendance.index'));
        $post->assertSessionHas('error');

        // レコードが増えていないこと（当日の出勤が1件のまま）
        $count = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', Carbon::today())
            ->count();
        $this->assertEquals(1, $count);
    }

    // 出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_visible_in_attendance_list()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:12:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 出勤処理を実行
        $this->post(route('attendance.clock_in'));

        // 勤怠一覧ページに出勤時刻（H:i）が表示されていること
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $expected = $now->format('H:i');
        $response->assertSeeText($expected);
    }
}
