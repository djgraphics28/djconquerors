<?php

namespace Database\Seeders;

use App\Models\GuideOption;
use Illuminate\Database\Seeder;

class GuideOptionSeeder extends Seeder
{
    /**
     * Seed the guide_options table with the original static categories.
     * Uses firstOrCreate so re-running is safe.
     */
    public function run(): void
    {
        $options = [
            ['name' => 'rules',   'order' => 1],
            ['name' => 'bonchat', 'order' => 2],
            ['name' => 'riscoin', 'order' => 3],
            ['name' => 'binance', 'order' => 4],
            ['name' => 'okx',     'order' => 5],
            ['name' => 'gcash',   'order' => 6],
            ['name' => 'maya',    'order' => 7],
        ];

        foreach ($options as $option) {
            GuideOption::firstOrCreate(
                ['name' => $option['name']],
                ['order' => $option['order'], 'is_published' => true]
            );
        }
    }
}
