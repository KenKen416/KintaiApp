<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Carbon\Carbon;

class DatetimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    // 現在の日時情報が UI と同じ形式で出力されていること
    public function test_current_time_is_displayed_in_same_format_as_now()
    {
        // テスト時刻を固定
        $now = Carbon::parse('2025-10-24 11:54:00');
        Carbon::setTestNow($now);

        // 一般ユーザーを作成してログイン（メール認証済み）
        $user = User::factory()->create([
            'email' => 'timeuser@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => Carbon::now(),
        ]);
        /** @var User $user */
        $this->actingAs($user);

        // 勤怠画面へアクセス
        $response = $this->get('/attendance');

        $response->assertStatus(200);

        // ビューには H:i フォーマットで現在時刻が表示される想定
        $expected = $now->format('H:i');
        $response->assertSeeText($expected);
    }
}
