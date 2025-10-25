<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;


    //名前が未入力の場合
    public function test_registration_requires_name_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'user1@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['name']);

        $this->assertSame('お名前を入力してください', session('errors')->first('name'));
    }

    //メールアドレスが未入力の場合
    public function test_registration_requires_email_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertSame('メールアドレスを入力してください', session('errors')->first('email'));
    }

    //パスワードが8文字未満の場合
    public function test_registration_password_min_length_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user2@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    //パスワードが一致しない場合
    public function test_registration_password_confirmation_mismatch_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user3@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password_confirmation']);
        $this->assertSame('パスワードと一致しません', session('errors')->first('password_confirmation'));
    }

    //パスワードが未入力の場合
    public function test_registration_requires_password_and_shows_japanese_message()
    {
        $response = $this->post('/register', [
            'name' => 'テスト ユーザー',
            'email' => 'user4@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertSame('パスワードを入力してください', session('errors')->first('password'));
    }

    //フォームに内容が入力されていた場合、データが正常に保存される
    public function test_registration_saves_user_when_valid()
    {
        $email = 'validuser@example.com';

        $response = $this->post('/register', [
            'name' => '有効 ユーザー',
            'email' => $email,
            'password' => 'validpassword',
            'password_confirmation' => 'validpassword',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => '有効 ユーザー',
        ]);
    }
}
