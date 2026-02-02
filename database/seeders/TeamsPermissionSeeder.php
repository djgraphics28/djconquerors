<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TeamsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for teams
        $permissions = [
            'teams.view',
            'teams.create',
            'teams.edit',
            'teams.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all teams permissions to admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        // Optionally assign view permission to other roles
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo('teams.view');

        $this->command->info('Teams permissions created and assigned successfully!');
    }
}
