<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'is_admin' => 1,
        ]);
    }

    protected function createUserWithAttendanceAndBreaks(string $name, string $email, Carbon $date, string $clockIn = '09:00:00', ?string $clockOut = '17:00:00', array $breaks = [])
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'is_admin' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTimeFromTimeString($clockIn)->toDateTimeString(),
            'clock_out' => $clockOut ? $date->copy()->setTimeFromTimeString($clockOut)->toDateTimeString() : null,
            'note' => '初期メモ',
        ]);

        foreach ($breaks as $b) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => isset($b['start']) ? $date->copy()->setTimeFromTimeString($b['start'])->toDateTimeString() : null,
                'break_end' => isset($b['end']) ? $date->copy()->setTimeFromTimeString($b['end'])->toDateTimeString() : null,
            ]);
        }

        return [$user, $attendance];
    }

    // 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_detail_page_shows_selected_attendance_data()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendanceAndBreaks(
            '村上太郎',
            'murakami@example.com',
            $today,
            '08:30:00',
            '17:15:00',
            [
                ['start' => '12:00', 'end' => '12:30'],
            ]
        );

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', ['id' => $attendance->id]));
        $response->assertStatus(200);

        // 名前・年度・月日が表示されていること
        $response->assertSeeText($user->name);
        $response->assertSeeText($today->format('Y年'));
        $response->assertSeeText($today->format('n月j日'));

        // 出勤・退勤の時刻（HH:MM）が表示されていること
        $response->assertSee('08:30');
        $response->assertSee('17:15');

        // 休憩時刻（HH:MM）が表示されていること
        $this->assertStringContainsString('12:00', $response->getContent());
        $this->assertStringContainsString('12:30', $response->getContent());

        // 備考が表示されていること
        $response->assertSeeText('初期メモ');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_clock_in_after_clock_out_shows_validation_error()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendanceAndBreaks(
            '岡田花子',
            'okada@example.com',
            $today,
            '09:00:00',
            '17:00:00'
        );

        // note は必須なので必ず入れる（note 空だと別エラーになる）
        $payload = [
            'clock_in' => '18:00', // 出勤が退勤より後
            'clock_out' => '09:00',
            'note' => '管理者修正メモ',
            'breaks' => [],
        ];

        $response = $this->actingAs($admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

        // バリデーションでリダイレクトになるはず
        $response->assertSessionHasErrors();
        // 出勤/退勤不整合は clock_in に対するエラーとして追加される実装
        $this->assertTrue(session('errors')->has('clock_in'));
        $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('clock_in'));
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_start_or_end_after_clock_out_shows_validation_error()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendanceAndBreaks(
            '佐藤花',
            'satohana@example.com',
            $today,
            '09:00:00',
            '17:00:00'
        );

        // 休憩が退勤時間を超えているケース（break_end が 18:00）
        $payload = [
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'note' => '管理者修正メモ',
            'breaks' => [
                ['break_start' => '18:30', 'break_end' => '19:00'],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

        $response->assertSessionHasErrors();
        $this->assertTrue(session('errors')->has('breaks.0.break_start'));
        $this->assertStringContainsString('休憩時間が不適切な値です', session('errors')->first('breaks.0.break_start'));
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_break_end_after_clock_out_shows_validation_error()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendanceAndBreaks(
            '藤井誠',
            'fujii@example.com',
            $today,
            '09:00:00',
            '17:00:00'
        );

        // 休憩終了 (break_end) が 退勤時間 (clock_out) より後になっているケースを送信する
        $payload = [
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'note' => '管理者修正メモ',
            'breaks' => [
                ['break_start' => '16:00', 'break_end' => '18:00'],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

        $response->assertSessionHasErrors();
        $this->assertTrue(session('errors')->has('breaks.0.break_end'));
        $this->assertStringContainsString('休憩時間もしくは退勤時間が不適切な値です', session('errors')->first('breaks.0.break_end'));
    }

    // 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_missing_note_shows_validation_error()
    {
        Carbon::setTestNow($today = Carbon::today());

        $admin = $this->createAdminUser();

        [$user, $attendance] = $this->createUserWithAttendanceAndBreaks(
            '近藤裕子',
            'kondo@example.com',
            $today,
            '09:00:00',
            '17:00:00'
        );

        // note を空にして送信
        $payload = [
            'clock_in' => '09:00',
            'clock_out' => '17:00',
            'note' => '',
            'breaks' => [],
        ];

        $response = $this->actingAs($admin)->post(route('admin.attendance.update', ['id' => $attendance->id]), $payload);

        $response->assertSessionHasErrors();
        $this->assertTrue(session('errors')->has('note'));
        $this->assertStringContainsString('備考を記入してください', session('errors')->first('note'));
    }
}
