<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    // 会員登録後に認証メールが送信される
    public function test_registration_sends_verification_email()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);

        $user = User::where('email', 'yamada@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    //メール認証誘導画面で 「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_email_verification_link_opens_email_verification_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        /** @var \App\Models\User $user */
        $this->actingAs($user);

        $response = $this->get(route('verification.notice'));
        $response->assertStatus(200);

        $response->assertSeeText('認証はこちらから');
        $response->assertSee('http://localhost:8025/'); // メールhogのリンク＝＞メール認証サイトを表示
    }
    //メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
    public function test_email_verification_completes_and_redirects_to_attendance_registration(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        /** @var \App\Models\User $user */
        $this->actingAs($user);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // パスが期待通りであること
        $this->assertEquals(parse_url(route('attendance.index'), PHP_URL_PATH), parse_url($location, PHP_URL_PATH));

    }
}