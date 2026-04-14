<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TicketFaqPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'tickets.manage',   // Admin: view & manage all tickets, assign, update status
            'faq.manage',       // Admin: CRUD for FAQ categories and FAQ entries
            'chatbot.manage',   // Admin: view chatbot conversation logs
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        $this->command->info('Ticket / FAQ / Chatbot permissions created and assigned to admin role.');
    }
}
