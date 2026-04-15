<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LeaderboardsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = 'leaderboards.view';

        Permission::firstOrCreate(['name' => $permission]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permission);

        $this->command->info('Leaderboards permission created and assigned to admin role.');
    }
}
