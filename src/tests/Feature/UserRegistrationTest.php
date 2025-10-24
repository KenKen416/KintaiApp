<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test cases covered (from テストケース一覧 ID1, rows 5-10):
     *  - Row 5: 名前が未入力の場合、バリデーションメッセージ「お名前を入力してください」が表示されること
     *  - Row 6: メールアドレスが未入力の場合、バリデーションメッセージ「メールアドレスを入力してください」が表示されること
     *  - Row 7: パスワードが8文字未満の場合、バリデーションメッセージ「パスワードは8文字以上で入力してください」が表示されること
     *  - Row 8: パスワードが一致しない場合、バリデーションメッセージ「パスワードと一致しません」が表示されること
     *  - Row 9: パスワードが未入力の場合、バリデーションメッセージ「パスワードを入力してください」が表示されること
     *  - Row 10: フォームに必要項目が入力されていた場合、データが正常に保存されること
     *
     * Note:
     *  - These tests post to /register. If your project uses a different registration route (e.g. route('register')),
     *    replace the URL accordingly.
     *  - The assertions below check both that a validation error exists and that the first error message equals the
     *    required Japanese text specified in the test matrix.
     */

    // === Row 5: 名前が未入力の場合 ===
    public function test_registration_requires_name_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => '', // intentionally empty
            'email' => 'user1@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // validation redirect back with errors
        $response->assertSessionHasErrors(['name']);

        // exact message check (per test requirement)
        $this->assertSame('お名前を入力してください', session('errors')->first('name'));
    }

    // === Row 6: メールアドレスが未入力の場合 ===
    public function test_registration_requires_email_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => '', // intentionally empty
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    // === Row 7: パスワードが8文字未満の場合 ===
    public function test_registration_password_min_length_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user2@example.com',
            'password' => 'short', // < 8 chars
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    // === Row 8: パスワードが一致しない場合 ===
    public function test_registration_password_confirmation_mismatch_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードと一致しません', session('errors')->first('password'));
    }

    // === Row 9: パスワードが未入力の場合 ===
    public function test_registration_requires_password_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user4@example.com',
            'password' => '', // intentionally empty
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    // === Row 10: フォームに内容が入力されていた場合、データが正常に保存される ===
    public function test_registration_saves_user_when_valid()
    {
        $email = 'validuser@example.com';

        $response = $this->post('/register', [
            'name' => '有効 ユーザー',
            'email' => $email,
            'password' => 'validpassword', // >= 8 chars
            'password_confirmation' => 'validpassword',
        ]);

        // On successful registration Laravel usually redirects (status 302 or to a named route).
        $response->assertStatus(302);

        // User should exist in the database
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => '有効 ユーザー',
        ]);
    }
}
