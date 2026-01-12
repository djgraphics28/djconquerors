<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReplyTemplatePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the permission
        $permission = Permission::firstOrCreate([
            'name' => 'reply-template.access',
            'guard_name' => 'web',
        ]);

        // Grant to admin role (adjust role name as needed)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
            $this->command->info('✅ Reply template permission granted to admin role');
        } else {
            $this->command->warn('⚠️ Admin role not found. Please grant permission manually.');
        }

        // You can add more roles here
        // $managerRole = Role::where('name', 'manager')->first();
        // if ($managerRole) {
        //     $managerRole->givePermissionTo($permission);
        // }
    }
}
