<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CelebrationsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = 'celebrations.view';

        Permission::firstOrCreate(['name' => $permission]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permission);

        $this->command->info('Celebrations permission created and assigned to admin role.');
    }
}
