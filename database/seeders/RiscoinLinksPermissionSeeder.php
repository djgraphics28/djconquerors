<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RiscoinLinksPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permission for Riscoin Links management
        $permission = Permission::firstOrCreate(['name' => 'riscoin-links.manage']);

        // Assign to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        $this->command->info('Riscoin Links permissions created and assigned to admin role successfully!');
    }
}
