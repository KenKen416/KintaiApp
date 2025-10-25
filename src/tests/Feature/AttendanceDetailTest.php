<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_detail_shows_user_name()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create([
            'name' => '山田 太郎',
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        // ユーザーの氏名が表示されていることを確認
        $response->assertSeeText('山田 太郎');
    }

    // 勤怠詳細画面の「日付」が選択した日付になっている
    public function test_detail_shows_selected_date()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        // ビューは年と月日を別要素で出力しているため両方確認する
        $response->assertSeeText($now->format('Y年'));
        $response->assertSeeText($now->format('n月j日'));
    }

    // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_detail_shows_clock_in_and_clock_out_times()
    {
        // 固定時刻で出勤・退勤を作成
        Carbon::setTestNow($in = Carbon::parse('2025-10-24 09:00:00'));
        $out = $in->copy()->addHours(8);

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $in->toDateString(),
            'clock_in' => $in->toDateTimeString(),
            'clock_out' => $out->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        // 出勤/退勤は input の value 属性に入っているため value= を確認する
        // assertSee の第2引数に false を渡して HTML エスケープを無効化する
        $response->assertSee('value="' . $in->format('H:i') . '"', false);
        $response->assertSee('value="' . $out->format('H:i') . '"', false);
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_detail_shows_break_times()
    {
        Carbon::setTestNow($start = Carbon::parse('2025-10-24 12:00:00'));
        $end = $start->copy()->addHour();

        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $start->toDateString(),
            'clock_in' => $start->copy()->subHours(3)->toDateTimeString(),
            'clock_out' => null,
        ]);

        // 休憩レコードを作成（表示は input の value に H:i 形式で入る）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $start->toDateTimeString(),
            'break_end' => $end->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        // 休憩時刻も input の value 属性で表示されるため value= を確認する（エスケープ無効）
        $response->assertSee('value="' . $start->format('H:i') . '"', false);
        $response->assertSee('value="' . $end->format('H:i') . '"', false);
    }
}
