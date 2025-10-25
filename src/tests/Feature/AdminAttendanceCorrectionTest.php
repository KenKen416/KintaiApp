<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class AdminAttendanceCorrectionTest extends TestCase
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

    // 修正申請を作るユーティリティ（work_date を必ず 'Y-m-d' 形式に整形してから時刻を結合します）
    protected function createCorrectionRequest(Attendance $attendance, array $overrides = [])
    {
        // attendance->work_date が Carbon か文字列かにかかわらず 'Y-m-d' 形式を得る
        if ($attendance->work_date instanceof Carbon) {
            $date = $attendance->work_date->toDateString();
        } else {
            // 文字列の場合でも Carbon でパースして日付部分を取り出す（堅牢化）
            $date = Carbon::parse((string) $attendance->work_date)->toDateString();
        }

        $defaults = [
            'attendance_id'       => $attendance->id,
            'requested_clock_in'  => Carbon::parse($date . ' 09:00:00')->toDateTimeString(),
            'requested_clock_out' => Carbon::parse($date . ' 18:00:00')->toDateTimeString(),
            'requested_breaks'    => json_encode([]),
            'requested_note'      => '修正申請のメモ',
            'status'              => 'pending', // 'pending' or 'approved'
        ];

        $attrs = array_merge($defaults, $overrides);

        // AttendanceCorrection がファクトリを持たない場合に備えて直接作成
        return AttendanceCorrection::create($attrs);
    }

    // 承認待ちの修正申請が全て表示されている
    public function test_admin_can_view_all_pending_correction_requests()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$userA, $attA] = $this->createUserWithAttendance('佐藤弘', 'sato@example.com', $today, '08:00:00', '17:00:00');
        [$userB, $attB] = $this->createUserWithAttendance('加藤亮', 'kato@example.com', $today, '09:00:00', '18:00:00');

        $corrA = $this->createCorrectionRequest($attA, [
            'requested_note' => '出勤時刻を08:30に修正したい',
            'status' => 'pending',
        ]);
        $corrB = $this->createCorrectionRequest($attB, [
            'requested_note' => '退勤時刻を18:30に修正したい',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSeeText($userA->name);
        $response->assertSeeText($userB->name);
    }

    // 承認済みの修正申請が全て表示されている
    public function test_admin_can_view_all_approved_correction_requests()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$userC, $attC] = $this->createUserWithAttendance('鈴木明', 'suzuki@example.com', $today, '08:15:00', '17:15:00');
        $corrC = $this->createCorrectionRequest($attC, [
            'requested_note' => '承認済みのサンプル',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSeeText($userC->name);
    }

    // 修正申請の詳細内容が正しく表示されている
    public function test_correction_request_detail_shows_request_content()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendance('田辺智', 'tanabe@example.com', $today, '08:30:00', '17:30:00');

        $requestedClockIn = $today->copy()->setTimeFromTimeString('08:45:00');
        $corr = $this->createCorrectionRequest($attendance, [
            'requested_clock_in' => $requestedClockIn->toDateTimeString(),
            'requested_note' => '出勤時間を08:45にしてください',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$corr->id}");

        $response->assertStatus(200);
        $response->assertSeeText($user->name);
        $this->assertStringContainsString($requestedClockIn->format('H:i'), $response->getContent());
        $response->assertSeeText('出勤時間を08:45にしてください');
    }

    // 修正申請の承認処理が正しく行われる
    public function test_approving_correction_request_updates_attendance_and_sets_status()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendance('松田葵', 'matsuda@example.com', $today, '09:00:00', '18:00:00');

        $requestedClockIn = $today->copy()->setTimeFromTimeString('08:50:00');
        $corr = $this->createCorrectionRequest($attendance, [
            'requested_clock_in' => $requestedClockIn->toDateTimeString(),
            'requested_note' => '出勤時間を08:50に修正',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/stamp_correction_request/approve/{$corr->id}");

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $corr->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $requestedClockIn->toDateTimeString(),
        ]);
    }
}
