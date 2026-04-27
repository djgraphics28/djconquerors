<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BinanceDownloadPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permission for Binance APK Download management
        $permission = Permission::firstOrCreate(['name' => 'binance-download.manage']);

        // Assign to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        $this->command->info('Binance Download permission created and assigned to admin role successfully!');
    }
}
