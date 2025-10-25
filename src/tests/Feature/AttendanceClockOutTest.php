<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    // 退勤ボタンが正しく機能する
    public function test_clock_out_button_visible_and_changes_to_finished()
    {
        // テスト時刻を固定
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 18:00:00'));

        // ユーザーを作成（メール認証済み）
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        // 当日の出勤中状態を作成（clock_in 記録あり、clock_out は null）
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in' => Carbon::now()->subHours(8)->toDateTimeString(),
            'clock_out' => null,
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 画面に「退勤」ボタンが表示されていることを確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSeeText('退勤');

        // 退勤処理を実行
        $post = $this->post(route('attendance.clock_out'));

        // 処理後、勤怠画面へリダイレクトされる想定
        $post->assertRedirect(route('attendance.index'));

        // リダイレクト先でステータスが「退勤済」になっていることを確認
        $follow = $this->get(route('attendance.index'));
        $follow->assertStatus(200);
        $follow->assertSeeText('退勤済');
    }

    // 退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_visible_in_attendance_list()
    {
        // 出勤→退勤の時刻を固定して処理を行う
        Carbon::setTestNow($inTime = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 出勤処理を実行（at $inTime）
        $this->post(route('attendance.clock_in'));

        // 退勤時刻を進めて退勤処理を実行
        $outTime = $inTime->copy()->addHours(8);
        Carbon::setTestNow($outTime);
        $this->post(route('attendance.clock_out'));

        // 勤怠一覧画面で退勤時刻（H:i）が表示されていることを確認
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $expected = $outTime->format('H:i');
        $response->assertSeeText($expected);
    }
}
