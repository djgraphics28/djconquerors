<?php

use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

new class extends Component {
    public $roles;
    public $permissions;
    public $name;
    public $selectedPermissions = [];
    public $search = '';
    public $editingRoleId = null;
    public $editName = '';
    public $editSelectedPermissions = [];
    public $permissionName = '';
    public $permissionGroup = '';
    public $showPermissionModal = false;
    public $selectedRoleForPermissions = null;
    public $editSearch = '';

    public function mount()
    {
        $this->roles = Role::with('permissions')->get();
        $this->permissions = Permission::all();
    }

    public function getGroupedPermissionsProperty()
    {
        return $this->permissions
            ->filter(function ($permission) {
                return str_contains(strtolower($permission->name), strtolower($this->search));
            })
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0] ?? 'Other';
            });
    }

    public function getEditGroupedPermissionsProperty()
    {
        return $this->permissions
            ->filter(function ($permission) {
                return str_contains(strtolower($permission->name), strtolower($this->editSearch));
            })
            ->groupBy(function ($permission) {
                return explode('.', $permission->name)[0] ?? 'Other';
            });
    }

    public function createRole()
    {
        $this->validate([
            'name' => 'required|min:3|unique:roles,name',
            'selectedPermissions' => 'required|array|min:1',
        ]);

        $role = Role::create(['name' => $this->name]);
        $role->syncPermissions($this->selectedPermissions);

        $this->reset(['name', 'selectedPermissions']);
        $this->roles = Role::with('permissions')->get();
        $this->dispatch('role-created');

        session()->flash('message', 'Role created successfully!');
    }

    public function editRole($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->editingRoleId = $roleId;
        $this->editName = $role->name;
        $this->editSelectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->editSearch = '';
    }

    public function updateRole()
    {
        $this->validate([
            'editName' => ['required', 'min:3', Rule::unique('roles', 'name')->ignore($this->editingRoleId)],
            'editSelectedPermissions' => 'required|array|min:1',
        ]);

        $role = Role::findOrFail($this->editingRoleId);
        $role->update(['name' => $this->editName]);
        $role->syncPermissions($this->editSelectedPermissions);

        $this->cancelEdit();
        $this->roles = Role::with('permissions')->get();
        $this->dispatch('role-updated');

        session()->flash('message', 'Role updated successfully!');
    }

    public function cancelEdit()
    {
        $this->editingRoleId = null;
        $this->editName = '';
        $this->editSelectedPermissions = [];
        $this->editSearch = '';
    }

    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);

        // Prevent deletion of admin role
        if ($role->name === 'admin') {
            session()->flash('error', 'Cannot delete admin role!');
            return;
        }

        $role->delete();
        $this->roles = Role::with('permissions')->get();
        $this->dispatch('role-deleted');

        session()->flash('message', 'Role deleted successfully!');
    }

    public function createPermission()
    {
        $this->validate([
            'permissionName' => 'required|min:3|unique:permissions,name',
            'permissionGroup' => 'required|min:2',
        ]);

        $permissionName = strtolower($this->permissionGroup) . '.' . strtolower($this->permissionName);

        Permission::create(['name' => $permissionName]);

        $this->reset(['permissionName', 'permissionGroup', 'showPermissionModal']);
        $this->permissions = Permission::all();
        $this->dispatch('permission-created');

        session()->flash('message', 'Permission created successfully!');
    }

    public function deletePermission($permissionId)
    {
        $permission = Permission::findOrFail($permissionId);
        $permission->delete();

        $this->permissions = Permission::all();
        $this->dispatch('permission-deleted');

        session()->flash('message', 'Permission deleted successfully!');
    }

    public function selectAllEditPermissions($group)
    {
        $groupPermissions = $this->permissions
            ->filter(function ($permission) use ($group) {
                $permissionGroup = explode('.', $permission->name)[0] ?? 'Other';
                return $permissionGroup === $group;
            })
            ->pluck('name')
            ->toArray();

        // Merge with existing selected permissions, avoiding duplicates
        $this->editSelectedPermissions = array_unique(array_merge($this->editSelectedPermissions, $groupPermissions));
    }
}; ?>

<div>
    <div class="p-6 dark:bg-gray-800 min-h-screen">
        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="bg-green-500 text-white p-4 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold dark:text-white">Advanced Role Management</h1>
            <button wire:click="$set('showPermissionModal', true)"
                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-green-500">
                Create Permission
            </button>
        </div>

        <!-- Create Role Form -->
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">Create New Role</h2>
            <form wire:submit="createRole">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Role Name</label>
                    <input type="text" wire:model="name"
                        class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter role name">
                    @error('name')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Search
                        Permissions</label>
                    <input type="text" wire:model.live="search"
                        class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
                        placeholder="Search permissions...">

                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @foreach ($this->groupedPermissions as $group => $permissions)
                            <div class="border dark:border-gray-600 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-bold text-gray-700 dark:text-gray-200">{{ ucfirst($group) }}</h3>
                                    <button type="button" wire:click="selectAllPermissions('{{ $group }}')"
                                        class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                                        Select All
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach ($permissions as $permission)
                                        <label
                                            class="inline-flex items-center p-2 border dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-600">
                                            <input type="checkbox" wire:model="selectedPermissions"
                                                value="{{ $permission->name }}"
                                                class="form-checkbox dark:bg-gray-800 dark:border-gray-600 text-blue-500">
                                            <span
                                                class="ml-2 text-gray-700 dark:text-gray-200 text-sm">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedPermissions')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Create Role
                </button>
            </form>
        </div>

        <!-- Roles Table -->
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">Roles List</h2>

            @if ($roles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-600">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                    Role Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                    Permissions Count
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                    Permissions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
                            @foreach ($roles as $role)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    @if ($editingRoleId === $role->id)
                                        <!-- Edit Mode -->
                                        <td colspan="4" class="px-6 py-4">
                                            <form wire:submit="updateRole" class="space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <label
                                                            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                                            Role Name
                                                        </label>
                                                        <input type="text" wire:model="editName"
                                                            class="w-full shadow-sm border dark:border-gray-600 rounded-md px-3 py-2 text-gray-700 dark:text-gray-200 dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                        @error('editName')
                                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label
                                                            class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                                                            Search Permissions
                                                        </label>
                                                        <input type="text" wire:model.live="editSearch"
                                                            class="w-full shadow-sm border dark:border-gray-600 rounded-md px-3 py-2 text-gray-700 dark:text-gray-200 dark:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-3"
                                                            placeholder="Search permissions...">

                                                        <div
                                                            class="max-h-64 overflow-y-auto border dark:border-gray-600 rounded-md p-4 space-y-4">
                                                            @foreach ($this->editGroupedPermissions as $group => $permissions)
                                                                <div class="border dark:border-gray-600 rounded-lg p-3">
                                                                    <div class="flex justify-between items-center mb-2">
                                                                        <h4
                                                                            class="font-semibold text-gray-700 dark:text-gray-200 text-sm">
                                                                            {{ ucfirst($group) }}</h4>
                                                                        <button type="button"
                                                                            wire:click="selectAllEditPermissions('{{ $group }}')"
                                                                            class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                                                                            Select All
                                                                        </button>
                                                                    </div>
                                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                        @foreach ($permissions as $permission)
                                                                            <label
                                                                                class="inline-flex items-center space-x-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-600 rounded">
                                                                                <input type="checkbox"
                                                                                    wire:model="editSelectedPermissions"
                                                                                    value="{{ $permission->name }}"
                                                                                    class="form-checkbox dark:bg-gray-800 dark:border-gray-600 text-blue-500">
                                                                                <span
                                                                                    class="text-sm text-gray-700 dark:text-gray-200">{{ $permission->name }}</span>
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @error('editSelectedPermissions')
                                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="flex space-x-2">
                                                    <button type="submit"
                                                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                        Update Role
                                                    </button>
                                                    <button type="button" wire:click="cancelEdit"
                                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    @else
                                        <!-- View Mode -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $role->name }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $role->permissions->count() > 0 ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' }}">
                                                {{ $role->permissions->count() }} permissions
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1 max-w-md">
                                                @foreach ($role->permissions->take(3) as $permission)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                        {{ $permission->name }}
                                                    </span>
                                                @endforeach
                                                @if ($role->permissions->count() > 3)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200">
                                                        +{{ $role->permissions->count() - 3 }} more
                                                    </span>
                                                @endif
                                                @if ($role->permissions->count() === 0)
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">No
                                                        permissions</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button wire:click="editRole({{ $role->id }})"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                    Edit
                                                </button>
                                                @if ($role->name !== 'admin')
                                                    <button wire:click="deleteRole({{ $role->id }})"
                                                        wire:confirm="Are you sure you want to delete this role?"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                        Delete
                                                    </button>
                                                @else
                                                    <span
                                                        class="text-gray-400 dark:text-gray-500 cursor-not-allowed">Delete</span>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">No roles found. Create your first role above.</p>
                </div>
            @endif
        </div>

        <!-- Permission Management -->
        <div class="bg-white dark:bg-gray-700 rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold mb-4 dark:text-white">Permission Management</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->groupedPermissions as $group => $permissions)
                    <div class="border dark:border-gray-600 rounded-lg p-4">
                        <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-3 flex justify-between items-center">
                            <span>{{ ucfirst($group) }}</span>
                            <span
                                class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">{{ $permissions->count() }}</span>
                        </h3>
                        <div class="space-y-2">
                            @foreach ($permissions as $permission)
                                <div class="flex justify-between items-center p-2 border dark:border-gray-600 rounded">
                                    <span
                                        class="text-sm text-gray-700 dark:text-gray-200">{{ $permission->name }}</span>
                                    <button wire:click="deletePermission({{ $permission->id }})"
                                        wire:confirm="Are you sure you want to delete this permission?"
                                        class="text-red-500 hover:text-red-700 text-xs">
                                        Delete
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Create Permission Modal -->
        @if ($showPermissionModal)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-gray-700 rounded-lg shadow-xl p-6 w-full max-w-md">
                    <h2 class="text-lg font-semibold mb-4 dark:text-white">Create New Permission</h2>
                    <form wire:submit="createPermission">
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Group</label>
                            <input type="text" wire:model="permissionGroup"
                                class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., user, product, order">
                            @error('permissionGroup')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-200 text-sm font-bold mb-2">Permission
                                Name</label>
                            <input type="text" wire:model="permissionName"
                                class="shadow appearance-none border dark:border-gray-600 rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="e.g., create, edit, delete">
                            @error('permissionName')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" wire:click="$set('showPermissionModal', false)"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Create Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
