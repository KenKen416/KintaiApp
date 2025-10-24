<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            // テスト用に共通パスワード（変更推奨）
            'password' => Hash::make('password'),
            'is_admin' => false,
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 管理者ユーザーを作る場合
     */
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => true,
            ];
        });
    }
}
