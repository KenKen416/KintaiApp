<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class AttendanceDetailEditTest extends TestCase
{
  use RefreshDatabase;

  protected function createVerifiedUser(array $overrides = []): User
  {
    return User::factory()->create(array_merge([
      'email_verified_at' => Carbon::now(),
    ], $overrides));
  }


  protected function createUserWithAttendance(string $name, string $email, Carbon $date, string $clockIn = '09:00:00', ?string $clockOut = '17:00:00')
  {
    $user = $this->createVerifiedUser([
      'name' => $name,
      'email' => $email,
      'is_admin' => 0,
    ]);

    $attendance = Attendance::factory()->create([
      'user_id'   => $user->id,
      'work_date' => $date->toDateString(),
      'clock_in'  => $date->copy()->setTimeFromTimeString($clockIn)->toDateTimeString(),
      'clock_out' => $clockOut ? $date->copy()->setTimeFromTimeString($clockOut)->toDateTimeString() : null,
      'note'      => '初期メモ',
    ]);

    return [$user, $attendance];
  }

  /**
   * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
   */
  public function test_clock_in_after_clock_out_shows_validation_error(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('田中次郎', 'tanaka@example.com', $today, '09:00:00', '17:00:00');

    $this->actingAs($user);

    $payload = [
      'requested_clock_in'  => '18:00', // 出勤が退勤より後
      'requested_clock_out' => '09:00',
      'requested_note'      => '誤った出勤時刻',
    ];

    $response = $this->post(route('attendance.detail.correction.store', ['id' => $attendance->id]), $payload);

    $response->assertSessionHasErrors();
    $this->assertTrue(session('errors')->has('requested_clock_in'));
    $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('requested_clock_in'));
  }

  /**
   * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
   */
  public function test_break_start_after_clock_out_shows_validation_error(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('佐藤花子', 'sato@example.com', $today, '09:00:00', '17:00:00');

    $this->actingAs($user);

    // AttendanceCorrectionRequest は requested_break_start/requested_break_end を期待するのでそれで検証
    $payload = [
      'requested_clock_in'    => '09:00',
      'requested_clock_out'   => '17:00',
      'requested_break_start' => ['18:00'], // 休憩開始が退勤後
      'requested_break_end'   => ['18:30'],
      'requested_note'        => '休憩開始が退勤後',
    ];

    $response = $this->post(route('attendance.detail.correction.store', ['id' => $attendance->id]), $payload);

    $response->assertSessionHasErrors();
    // withValidator に「休憩開始が退勤より後」のチェックがある想定で開始側のエラーを確認
    $this->assertTrue(session('errors')->has('requested_break_start.0'));
    $this->assertStringContainsString('休憩時間が不適切な値です', session('errors')->first('requested_break_start.0'));
  }

  /**
   * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
   */
  public function test_break_end_after_clock_out_shows_validation_error(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('鈴木一郎', 'suzuki@example.com', $today, '09:00:00', '17:00:00');

    $this->actingAs($user);

    $payload = [
      'requested_clock_in'    => '09:00',
      'requested_clock_out'   => '17:00',
      'requested_break_start' => ['16:30'],
      'requested_break_end'   => ['18:00'], // 終了が退勤後
      'requested_note'        => '休憩終了が退勤後',
    ];

    $response = $this->post(route('attendance.detail.correction.store', ['id' => $attendance->id]), $payload);

    $response->assertSessionHasErrors();
    $this->assertTrue(session('errors')->has('requested_break_end.0'));
    $this->assertStringContainsString('休憩時間もしくは退勤時間が不適切な値です', session('errors')->first('requested_break_end.0'));
  }

  /**
   * 備考欄が未入力の場合のエラーメッセージが表示される
   */
  public function test_missing_note_shows_validation_error(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('近藤裕子', 'kondo@example.com', $today, '09:00:00', '17:00:00');

    $this->actingAs($user);

    $payload = [
      'requested_clock_in'  => '09:00',
      'requested_clock_out' => '17:00',
      // requested_note を送らない（必須）
    ];

    $response = $this->post(route('attendance.detail.correction.store', ['id' => $attendance->id]), $payload);

    $response->assertSessionHasErrors();
    $this->assertTrue(session('errors')->has('requested_note'));
    $this->assertStringContainsString('備考を記入してください', session('errors')->first('requested_note'));
  }

  /**
   * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
   */
  public function test_pending_tab_shows_user_pending_corrections(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('井上太郎', 'inoue@example.com', $today);

    // ユーザーによる申請（pending）
    AttendanceCorrection::create([
      'attendance_id' => $attendance->id,
      'requested_clock_in' => null,
      'requested_clock_out' => null,
      'requested_breaks' => null,
      'requested_note' => 'ユーザー申請A',
      'status' => 'pending',
    ]);

    $this->actingAs($user);
    $response = $this->get(route('stamp_correction_request.list', ['tab' => 'pending']));
    $response->assertStatus(200);
    $response->assertSeeText('ユーザー申請A');
  }

  /**
   * 「承認済み」に管理者が承認した修正申請が全て表示されている
   */
  public function test_approved_tab_shows_admin_approved_corrections(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    // 一般ユーザーと勤怠・申請を作成
    [$user, $attendance] = $this->createUserWithAttendance('高橋健', 'takahashi@example.com', $today);

    AttendanceCorrection::create([
      'attendance_id' => $attendance->id,
      'requested_clock_in' => null,
      'requested_clock_out' => null,
      'requested_breaks' => null,
      'requested_note' => '承認済申請B',
      'status' => 'approved',
    ]);

    // 管理者で一覧を確認
    $admin = $this->createVerifiedUser(['is_admin' => 1]);
    $this->actingAs($admin);
    $response = $this->get(route('stamp_correction_request.list', ['tab' => 'approved']));
    $response->assertStatus(200);
    $response->assertSeeText('承認済申請B');
  }

  /**
   * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
   */
  public function test_clicking_detail_navigates_to_attendance_detail(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    [$user, $attendance] = $this->createUserWithAttendance('石井一郎', 'ishii@example.com', $today);

    $correction = AttendanceCorrection::create([
      'attendance_id' => $attendance->id,
      'requested_clock_in' => null,
      'requested_clock_out' => null,
      'requested_breaks' => null,
      'requested_note' => '詳細遷移確認',
      'status' => 'pending',
    ]);

    $this->actingAs($user);
    // まず一覧に詳細リンク（attendance.detail）が含まれていることを確認
    $listResp = $this->get(route('stamp_correction_request.list', ['tab' => 'pending']));
    $listResp->assertStatus(200);
    $this->assertStringContainsString(route('attendance.detail', ['id' => $attendance->id]), $listResp->getContent());

    // 詳細リンク先へ遷移して勤怠詳細が表示されることを確認
    $detailResp = $this->get(route('attendance.detail', ['id' => $attendance->id]));
    $detailResp->assertStatus(200);
    $detailResp->assertSeeText('初期メモ'); // 作成時の note が表示されることを確認
  }

  /**
   * 修正申請が実行され、申請はユーザーの「承認待ち」一覧および管理者の一覧に表示される
   */
  public function test_successful_correction_request_is_saved_and_visible_in_lists(): void
  {
    Carbon::setTestNow($today = Carbon::today());

    // ユーザー側で申請
    [$user, $attendance] = $this->createUserWithAttendance('山下太郎', 'yamasita@example.com', $today, '09:00:00', '17:00:00');

    $this->actingAs($user);

    // コントローラ側が保存処理で期待するフィールド名（breaks）で送信
    $payload = [
      'requested_clock_in'  => '08:45',
      'requested_clock_out' => '17:10',
      'breaks' => [
        ['break_start' => '12:00', 'break_end' => '12:30'],
      ],
      'requested_note' => '勤務時間修正のお願い',
    ];

    $response = $this->post(route('attendance.detail.correction.store', ['id' => $attendance->id]), $payload);

    // controller は back()->with('status', ...) を返す想定
    $response->assertSessionHas('status');

    // DB に attendance_corrections が作成されていること
    $this->assertDatabaseHas('attendance_corrections', [
      'attendance_id'   => $attendance->id,
      'requested_note'  => '勤務時間修正のお願い',
      'status'          => 'pending',
    ]);

    // ユーザーの一覧ページ（承認待ちタブ）に該当申請が表示されていること
    $listResp = $this->actingAs($user)->get(route('stamp_correction_request.list'));
    $listResp->assertStatus(200);
    // ユーザー一覧ビューは短縮表示する場合があるため先頭数文字で確認
    $this->assertStringContainsString(mb_substr('勤務時間修正のお願い', 0, 5), $listResp->getContent());

    // 管理者側で申請一覧に表示されること
    $admin = $this->createVerifiedUser(['is_admin' => 1]);
    $adminResp = $this->actingAs($admin)->get(route('stamp_correction_request.list'));
    $adminResp->assertStatus(200);
    $this->assertStringContainsString(mb_substr('勤務時間修正のお願い', 0, 5), $adminResp->getContent());
  }
}
