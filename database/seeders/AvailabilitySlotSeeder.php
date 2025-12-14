<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use App\Models\AvailabilitySlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AvailabilitySlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startDate = Carbon::now();

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);

            // Add morning slots
            AvailabilitySlot::create([
                'date' => $date,
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
            ]);

            // Add afternoon/evening slots
            AvailabilitySlot::create([
                'date' => $date,
                'start_time' => '17:00:00',
                'end_time' => '19:00:00',
            ]);

            AvailabilitySlot::create([
                'date' => $date,
                'start_time' => '19:00:00',
                'end_time' => '21:00:00',
            ]);
        }
    }
}
