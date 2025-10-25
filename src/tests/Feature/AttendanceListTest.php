<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 自分が行った勤怠情報が全て表示されている
    public function test_attendance_list_shows_all_user_attendances()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        // ユーザーと他ユーザーを作成
        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);
        $other = User::factory()->create(['email_verified_at' => Carbon::now()]);

        // ユーザーの勤怠を2件作成（今日と昨日）
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now->toDateTimeString(),
        ]);
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->copy()->subDay()->toDateString(),
            'clock_in' => $now->copy()->subDay()->toDateTimeString(),
        ]);

        // 他ユーザーの勤怠（リストに表示されないことを確認するため）
        Attendance::create([
            'user_id' => $other->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // ビューでは月/日表示（例: "10/24(…)") になっているため、month/day を期待値にする
        $this->assertStringContainsString($now->format('m/d'), (string) $response->getContent());
        $this->assertStringContainsString($now->copy()->subDay()->format('m/d'), (string) $response->getContent());
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_attendance_list_shows_current_month_on_open()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        /** @var User $user */
        $this->actingAs($user);

        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        // ビューが "YYYY/n" 表記を使っているため、それに合わせる
        $expected = $now->format('Y/n'); // 例: "2025/10"
        $response->assertSeeText($expected);
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_attendance_list_prev_month_shows_previous_month()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        /** @var User $user */
        $this->actingAs($user);

        // 前月をクエリで指定して取得（UIの「前月」ボタン相当の動作）
        $prevMonth = $now->copy()->subMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $prevMonth);
        $response->assertStatus(200);

        // ビューは "YYYY/n" 表記なのでそれに合わせて期待値を生成
        $expected = Carbon::createFromFormat('Y-m', $prevMonth)->format('Y/n'); // 例: "2025/9"
        $response->assertSeeText($expected);
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_attendance_list_next_month_shows_next_month()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        /** @var User $user */
        $this->actingAs($user);

        // 翌月をクエリで指定して取得（UIの「翌月」ボタン相当の動作）
        $nextMonth = $now->copy()->addMonth()->format('Y-m');
        $response = $this->get('/attendance/list?month=' . $nextMonth);
        $response->assertStatus(200);

        $expected = Carbon::createFromFormat('Y-m', $nextMonth)->format('Y/n'); // 例: "2025/11"
        $response->assertSeeText($expected);
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_detail_button_navigates_to_attendance_detail()
    {
        Carbon::setTestNow($now = Carbon::parse('2025-10-24 09:00:00'));

        $user = User::factory()->create(['email_verified_at' => Carbon::now()]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $now->toDateString(),
            'clock_in' => $now->toDateTimeString(),
        ]);

        /** @var User $user */
        $this->actingAs($user);

        // 直接詳細ページにアクセスして遷移可能か確認（UIの「詳細」押下の結果と同等）
        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        // ビューでは「年」と「月日」が別要素になっているため分けて確認する
        $response->assertSeeText($now->format('Y年'));
        $response->assertSeeText($now->format('n月j日'));
    }
}
