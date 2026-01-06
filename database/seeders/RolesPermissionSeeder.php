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
            'dashboard.viewSpecialOccasions',
            'dashboard.copyMessageToMartin',
            'dashboard.viewNewInvestorsAnalytics',
            'dashboard.viewTopAssisters',

            //Users
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.disable-enable',
            'users.cop-welcome-message',
            'users.verify-email',
            'users.promote',
            'users.impersonate',

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

            //Apointments
            'appointments.book',
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            //Email Receivers
            'email-receivers.view',
            'email-receivers.create',
            'email-receivers.edit',
            'email-receivers.delete',

            //Guide Management
            'guide.view',
            'guide.create',
            'guide.edit',
            'guide.delete',

            //Guide Access
            'guide.access',

            //Managesrs
            'managers.view',
            'managers.create',
            'managers.edit',
            'managers.delete',

            // Compound Interest Calculator
            'calculator.view',
            'calculator.access',

            'opalite.view',
            'opalite.manage',
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
            'my-team.view',
            'appointments.book',
            // 'guide.access',

            // Compound Interest Calculator
            'calculator.view',
            'calculator.access',

        ]);

        $adminUser = User::where('email', 'admin@djconquerors.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($admin);
        }

    }
}
