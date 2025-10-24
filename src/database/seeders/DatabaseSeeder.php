<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run()
    {
        $this->call([
            \Database\Seeders\UsersTableSeeder::class,
            \Database\Seeders\AttendancesTableSeeder::class,
            \Database\Seeders\AttendanceCorrectionsTableSeeder::class,
        ]);
    }
}
