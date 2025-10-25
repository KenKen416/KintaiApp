<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_requires_email_and_shows_japanese_message()
    {
        // テスト手順: ユーザーを登録する
        User::factory()->create([
            'email' => 'exist1@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_login_requires_password_and_shows_japanese_message()
    {
        // テスト手順: ユーザーを登録する
        User::factory()->create([
            'email' => 'exist2@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'exist2@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    // 登録内容と一致しない場合、エラーメッセージが表示される
    public function test_login_with_invalid_credentials_shows_error_message()
    {
        // テスト手順: ユーザーを登録する
        User::factory()->create([
            'email' => 'exist3@example.com',
            'password' => Hash::make('correct_password'),
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'exist3@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}