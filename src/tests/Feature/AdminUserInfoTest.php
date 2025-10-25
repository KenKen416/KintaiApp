<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminUserInfoTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'is_admin' => 1,
        ]);
    }

    protected function createUserWithAttendance(string $name, string $email, Carbon $workDate, string $clockIn = '09:00:00', ?string $clockOut = '17:00:00')
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'is_admin' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => $workDate->toDateString(),
            'clock_in'  => $workDate->copy()->setTimeFromTimeString($clockIn)->toDateTimeString(),
            'clock_out' => $clockOut ? $workDate->copy()->setTimeFromTimeString($clockOut)->toDateTimeString() : null,
        ]);

        return [$user, $attendance];
    }

    // 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる
    public function test_admin_can_view_all_users_names_and_emails_in_staff_list()
    {
        $admin = $this->createAdminUser();

        $users = collect([
            ['name' => '小林次郎', 'email' => 'kobayashi@example.com'],
            ['name' => '山本彩',   'email' => 'yamamoto@example.com'],
            ['name' => '中村誠',   'email' => 'nakamura@example.com'],
        ])->map(function ($attrs) {
            return User::factory()->create(array_merge($attrs, ['is_admin' => 0]));
        });

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);

        foreach ($users as $user) {
            $response->assertSeeText($user->name);
            $response->assertSeeText($user->email);
        }
    }

    // 選択したユーザーの勤怠情報が正しく表示される
    public function test_admin_can_view_selected_user_attendances()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendance('佐々木隆', 'sasaski@example.com', $today, '08:30:00', '17:15:00');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);
        $response->assertSeeText($user->name);

        // 出勤時刻（HH:MM）が表示されていることを確認
        $this->assertStringContainsString(
            Carbon::parse($attendance->clock_in)->format('H:i'),
            $response->getContent()
        );
    }

    // 「前月」ボタンを押下した時に表示月の前月の情報が表示される
    public function test_admin_staff_attendance_prev_month_shows_previous_month()
    {
        Carbon::setTestNow($today = Carbon::create(2025, 10, 25));
        $prevMonthDate = $today->copy()->subMonth()->startOfMonth()->addDays(4); 

        $admin = $this->createAdminUser();

        [$user, $attendancePrev] = $this->createUserWithAttendance('長谷川真', 'hasegawa@example.com', $prevMonthDate, '09:10:00', '18:00:00');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=" . $prevMonthDate->format('Y-m'));

        $response->assertStatus(200);

        // 画面上に前月の表示
        $response->assertSeeText($prevMonthDate->format('Y/m'));

        // 前月の勤怠データが表示されていること
        $response->assertSeeText($user->name);
        $this->assertStringContainsString(
            Carbon::parse($attendancePrev->clock_in)->format('H:i'),
            $response->getContent()
        );
    }

    // 「翌月」ボタンを押下した時に表示月の翌月の情報が表示される
    public function test_admin_staff_attendance_next_month_shows_next_month()
    {
        Carbon::setTestNow($today = Carbon::create(2025, 10, 25));
        $nextMonthDate = $today->copy()->addMonth()->startOfMonth()->addDays(5);

        $admin = $this->createAdminUser();

        [$user, $attendanceNext] = $this->createUserWithAttendance('小川理恵', 'ogawa@example.com', $nextMonthDate, '10:05:00', '19:00:00');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=" . $nextMonthDate->format('Y-m'));

        $response->assertStatus(200);
        // 画面上に翌月の表示
        $response->assertSeeText(
            $nextMonthDate->format('Y/m')
        );

        $response->assertSeeText($user->name);
        $this->assertStringContainsString(
            Carbon::parse($attendanceNext->clock_in)->format('H:i'),
            $response->getContent()
        );
    }

    // 「詳細」ボタンを押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_staff_list_detail_button_navigates_to_attendance_detail()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendance('松本葵', 'matsumoto@example.com', $today, '09:20:00', '18:10:00');

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        // 「詳細」ボタン押下を想定して直接詳細ページへアクセス
        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSeeText($user->name);
        $this->assertStringContainsString(
            Carbon::parse($attendance->clock_in)->format('H:i'),
            $response->getContent()
        );
    }
}
