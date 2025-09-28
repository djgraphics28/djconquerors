<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

new class extends Component {
    public $roles;
    public $permissions;
    public $name;
    public $selectedPermissions = [];
    public $search = '';

    public function mount()
    {
        $this->roles = Role::all();
        $this->permissions = Permission::all();
    }

    public function getGroupedPermissionsProperty()
    {
        return $this->permissions
            ->filter(function($permission) {
                return str_contains(strtolower($permission->name), strtolower($this->search));
            })
            ->groupBy(function($permission) {
                return explode('.', $permission->name)[0] ?? 'Other';
            });
    }

    public function createRole()
    {
        $this->validate([
            'name' => 'required|min:3|unique:roles,name',
            'selectedPermissions' => 'required|array|min:1'
        ]);

        $role = Role::create(['name' => $this->name]);
        $role->syncPermissions($this->selectedPermissions);

        $this->reset(['name', 'selectedPermissions']);
        $this->dispatch('role-created');
    }

    public function deleteRole($roleId)
    {
        $role = Role::findById($roleId);
        $role->delete();

        $this->roles = Role::all();
        $this->dispatch('role-deleted');
    }
}; ?>

<div>
    <div class="p-6 dark:bg-gray-800">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold dark:text-white">Role Management</h1>
        </div>

        <form wire:submit="createRole" class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6 mb-6">
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Role Name</label>
                <input type="text" wire:model="name" class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Search Permissions</label>
                <input type="text" wire:model.live="search" class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4">

                <div class="space-y-4">
                    @foreach($this->groupedPermissions as $group => $permissions)
                        <div class="border dark:border-gray-600 rounded-lg p-4">
                            <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-2">{{ ucfirst($group) }}</h3>
                            <div class="grid grid-cols-3 gap-4">
                                @foreach($permissions as $permission)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="form-checkbox dark:bg-gray-800 dark:border-gray-600">
                                        <span class="ml-2 text-gray-700 dark:text-gray-200">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                Create Role
            </button>
        </form>

        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">Existing Roles</h2>
            <div class="grid grid-cols-1 gap-4">
                @foreach($roles as $role)
                    <div class="border dark:border-gray-600 rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <h3 class="font-bold dark:text-white">{{ $role->name }}</h3>
                            <button wire:click="deleteRole({{ $role->id }})" class="text-red-500 hover:text-red-700 dark:hover:text-red-400">
                                Delete
                            </button>
                        </div>
                        <div class="mt-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Permissions:</span>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($role->permissions as $permission)
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-600 rounded-full text-sm dark:text-gray-200">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
