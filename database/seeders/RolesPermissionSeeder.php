<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create Permissions
        $adminPermissions = [
            //Dashboard
            'dashboard.view',

            //Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.disable-enable',

            //Roles
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            //My Withrwawals
            'my-withdrawals.view',
            'my-withdrawals.create',
            'my-withdrawals.edit',
            'my-withdrawals.delete',

            //Genealogy
            'genealogy.view',

            //Turorials Management
            'tutorials.view',
            'tutorials.create',
            'tutorials.edit',
            'tutorials.delete',

            //Tutorials Access
            'tutorials.access',

            //Activity Logs
            'activity-logs.view',

            //My Team
            'my-team.access',
            'my-team.view',
            'my-team.edit',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        $admin = Role::updateOrCreate(['name' => 'admin']);
        $admin->givePermissionTo($adminPermissions);

        $user = Role::updateOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'dashboard.view',
            'my-withdrawals.view',
            'my-withdrawals.create',
            'my-withdrawals.edit',
            'my-withdrawals.delete',
            'genealogy.view',
            'tutorials.access',
            'my-team.access',
            'my-team.view'
        ]);

        $adminUser = User::where('email', 'admin@djconquerors.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($admin);
        }

    }
}
