<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the users seeds.
     *
     * @return void
     */
    public function run()
    {
        // 管理者ユーザー（固定）
        User::factory()->admin()->create([
            'name' => '管理者',
            'email' => 'admin@admin',
            'password' => bcrypt('password'),
        ]);

        $staffs = [
            ['name' => '西 伶奈', 'email' => 'test1@test'],
            ['name' => '山田 太郎', 'email' => 'test2@test'],
            ['name' => '鈴木 花子', 'email' => 'test3@test'],
            ['name' => '田中 一郎', 'email' => 'test4@test'],
            ['name' => '佐藤 美咲', 'email' => 'test5@test'],
        ];

        foreach ($staffs as $s) {
            User::factory()->create([
                'name' => $s['name'],
                'email' => $s['email'],
                'password' => bcrypt('password'),
                'is_admin' => false,
            ]);
        }
    }
}
