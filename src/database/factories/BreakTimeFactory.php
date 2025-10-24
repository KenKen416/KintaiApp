<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;


class BreakTimeFactory extends Factory
{
    public function definition()
    {
        $date = Carbon::now()->startOfDay();
        $start = $date->copy()->setTime(12, 0);
        $end = $start->copy()->addHour();

        return [
            'break_start' => $start->toDateTimeString(),
            'break_end' => $end->toDateTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
