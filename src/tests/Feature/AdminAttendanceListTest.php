<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'is_admin' => 1,
        ]);
    }

    protected function createUserWithAttendance(string $name, Carbon $workDate, string $clockIn = '09:00:00', ?string $clockOut = '17:00:00')
    {
        $user = User::factory()->create([
            'name' => $name,
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

    public function test_admin_can_view_all_users_attendances_for_today()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$userA, $attA] = $this->createUserWithAttendance('佐藤太郎', $today, '09:00:00', '18:00:00');
        [$userB, $attB] = $this->createUserWithAttendance('鈴木一郎', $today, '08:45:00', '17:30:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSeeText($userA->name);
        $response->assertSeeText($userB->name);

        $this->assertStringContainsString(
            Carbon::parse($attA->clock_in)->format('H:i'),
            $response->getContent()
        );
        $this->assertStringContainsString(
            Carbon::parse($attB->clock_in)->format('H:i'),
            $response->getContent()
        );
    }

    public function test_admin_attendance_list_shows_current_date_on_load()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        // ヘッダ例: "2025年10月25日の勤怠"
        $response->assertSeeText($today->format('Y年n月j日の勤怠'));
        // 中央の日付表示例: "2025/10/25"
        $response->assertSeeText($today->format('Y/m/d'));
    }

    public function test_admin_can_view_previous_day_attendances_when_click_previous()
    {
        Carbon::setTestNow($today = Carbon::today());
        $yesterday = $today->copy()->subDay();

        $admin = $this->createAdminUser();

        [$userY, $attY] = $this->createUserWithAttendance('高橋花子', $yesterday, '09:15:00', '17:45:00');
        [$userT, $attT] = $this->createUserWithAttendance('田中健',  $today,     '09:00:00', '18:00:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $yesterday->toDateString());

        $response->assertStatus(200);
        $response->assertSeeText($yesterday->format('Y年n月j日の勤怠'));
        $response->assertSeeText($yesterday->format('Y/m/d'));
        $response->assertSeeText($userY->name);
        $this->assertStringContainsString(
            Carbon::parse($attY->clock_in)->format('H:i'),
            $response->getContent()
        );
    }

    public function test_admin_can_view_next_day_attendances_when_click_next()
    {
        Carbon::setTestNow($today = Carbon::today());
        $tomorrow = $today->copy()->addDay();

        $admin = $this->createAdminUser();

        [$userN, $attN] = $this->createUserWithAttendance('伊藤美咲', $tomorrow, '10:00:00', '19:00:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $tomorrow->toDateString());

        $response->assertStatus(200);
        $response->assertSeeText($tomorrow->format('Y年n月j日の勤怠'));
        $response->assertSeeText($tomorrow->format('Y/m/d'));
        $response->assertSeeText($userN->name);
        $this->assertStringContainsString(
            Carbon::parse($attN->clock_in)->format('H:i'),
            $response->getContent()
        );
    }
}
