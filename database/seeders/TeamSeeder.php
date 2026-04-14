<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::updateOrCreate([
            'name' => 'DJ Conquerors',
        ], [
            'description' => 'Darwin\'s Team.',
            'domain' => 'dj-conquerors.djnetsolutions.org',
        ]);
    }
}
