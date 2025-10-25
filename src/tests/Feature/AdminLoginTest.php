<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    // 管理者ログイン：メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_login_requires_email_and_shows_japanese_message()
    {
        User::factory()->create([
            'email' => 'admin1@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
            'context' => 'admin',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    // 管理者ログイン：パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_login_requires_password_and_shows_japanese_message()
    {
        User::factory()->create([
            'email' => 'admin2@example.com',
            'password' => Hash::make('password123'),
            'is_admin' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin2@example.com',
            'password' => '',
            'context' => 'admin',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    // 管理者ログイン：認証情報が誤っている場合、エラーメッセージが表示される
    public function test_admin_login_with_invalid_credentials_shows_error_message()
    {
        User::factory()->create([
            'email' => 'admin3@example.com',
            'password' => Hash::make('correct_password'),
            'is_admin' => 1,
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin3@example.com',
            'password' => 'wrong_password',
            'context' => 'admin',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('ログイン情報が登録されていません', session('errors')->first('email'));
    }

}