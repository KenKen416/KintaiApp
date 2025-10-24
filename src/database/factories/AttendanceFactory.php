<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;


class AttendanceFactory extends Factory
{
    public function definition()
    {

        $date = Carbon::now()->startOfDay();
        $clockIn = $date->copy()->setTime(9, 0);
        $clockOut = $date->copy()->setTime(18, 0);

        return [
            'work_date' => $date->toDateString(),
            'clock_in' => $clockIn->toDateTimeString(),
            'clock_out' => $clockOut->toDateTimeString(),
            'note' => $this->faker->optional()->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
