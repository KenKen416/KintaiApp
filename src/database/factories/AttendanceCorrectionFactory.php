<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;


class AttendanceCorrectionFactory extends Factory
{
    public function definition()
    {
        $date = Carbon::now()->startOfDay();
        $requestedBreaks = [
            [
                'break_start' => $date->copy()->setTime(12, 0)->toDateTimeString(),
                'break_end' => $date->copy()->setTime(13, 0)->toDateTimeString(),
            ],
        ];

        return [
            'requested_clock_in' => $date->copy()->setTime(9, 0)->toDateTimeString(),
            'requested_clock_out' => $date->copy()->setTime(18, 0)->toDateTimeString(),
            'requested_breaks' => $requestedBreaks,
            'requested_note' => $this->faker->sentence(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
