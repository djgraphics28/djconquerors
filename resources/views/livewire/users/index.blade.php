<?php

use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    public $users = [];
    public $user;
    public $name;
    public $email;
    public $riscoin_id;
    public $password;
    public $inviters_code;
    public $invested_amount;
    public $is_active = true;
    public $roles = [];
    public $selectedRoles = [];
    public $editMode = false;
    public $userId;
    public $showModal = false;
    public $showViewModal = false;
    public $activityLogs = [];
    public $selectedUser = null;

    public function mount()
    {
        $this->loadUsers();
        $this->loadRoles();
    }

    public function loadUsers()
    {
        $this->users = User::with('roles')->get();
    }

    public function loadRoles()
    {
        $this->roles = Role::all();
    }

    public function loadActivityLogs($userId)
    {
        // Get user's activity logs using Spatie Activitylog
        $this->activityLogs = Activity::where('causer_id', $userId)->where('causer_type', User::class)->with('causer')->orderBy('created_at', 'desc')->limit(50)->get();
    }

    public function viewUser($userId)
    {
        $this->selectedUser = User::with('roles')->find($userId);
        $this->loadActivityLogs($userId);
        $this->showViewModal = true;
    }

    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email' . ($this->userId ? ',' . $this->userId : ''),
            'riscoin_id' => 'nullable|string',
            'password' => $this->editMode ? 'nullable|min:8' : 'required|min:8',
            'inviters_code' => 'nullable|string',
            'invested_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
        ];
    }

    public function create()
    {
        $validated = $this->validate();

        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'riscoin_id' => $this->riscoin_id,
            'password' => bcrypt($this->password),
            'inviters_code' => $this->inviters_code,
            'invested_amount' => $this->invested_amount ?? 0,
            'is_active' => $this->is_active,
            'email_verified_at' => now(),
        ];

        $user = User::create($userData);

        // Assign roles
        if (!empty($this->selectedRoles)) {
            $user->syncRoles($this->selectedRoles);
        }

        // Log the user creation activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'attributes' => $userData,
                'roles' => $this->selectedRoles,
            ])
            ->log('created');

        $this->resetForm();
        $this->loadUsers();
        $this->showModal = false;
        session()->flash('message', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $this->editMode = true;
        $this->userId = $user->id;
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->riscoin_id = $user->riscoin_id;
        $this->inviters_code = $user->inviters_code;
        $this->invested_amount = $user->invested_amount;
        $this->is_active = $user->is_active;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function update()
    {
        $validated = $this->validate();

        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
            'riscoin_id' => $this->riscoin_id,
            'inviters_code' => $this->inviters_code,
            'invested_amount' => $this->invested_amount ?? 0,
            'is_active' => $this->is_active,
        ];

        // Only update password if provided
        if ($this->password) {
            $updateData['password'] = bcrypt($this->password);
        }

        $user = User::find($this->userId);
        $oldData = $user->toArray();

        $user->update($updateData);

        // Sync roles
        $oldRoles = $user->roles->pluck('name')->toArray();
        $user->syncRoles($this->selectedRoles);

        // Log the user update activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'old' => $oldData,
                'attributes' => $updateData,
                'old_roles' => $oldRoles,
                'new_roles' => $this->selectedRoles,
            ])
            ->log('updated');

        $this->editMode = false;
        $this->resetForm();
        $this->loadUsers();
        $this->showModal = false;
        session()->flash('message', 'User updated successfully.');
    }

    public function delete(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $userData = $user->toArray();
        $userRoles = $user->roles->pluck('name')->toArray();

        // Log the user deletion activity before deleting
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'deleted_user' => $userData,
                'roles' => $userRoles,
            ])
            ->log('deleted user: ' . $user->name);

        $user->delete();

        $this->loadUsers();
        session()->flash('message', 'User deleted successfully.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'riscoin_id', 'password', 'inviters_code', 'invested_amount', 'is_active', 'userId', 'user', 'selectedRoles']);
        $this->is_active = true;
        $this->editMode = false;
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedUser = null;
        $this->activityLogs = [];
    }

    // Format the activity log description for better display
    public function formatActivityDescription($activity)
    {
        $description = $activity->description;
        $properties = $activity->properties->toArray();

        switch ($description) {
            case 'created':
                return 'User account was created';
            case 'updated':
                return 'User account was updated';
            case 'deleted':
                return 'User account was deleted';
            default:
                return $description;
        }
    }

    // Get changed fields for update activities
    public function getChangedFields($activity)
    {
        $properties = $activity->properties->toArray();
        $changedFields = [];

        if (isset($properties['old']) && isset($properties['attributes'])) {
            foreach ($properties['attributes'] as $key => $value) {
                if ($key === 'password') {
                    continue;
                } // Skip password changes for security

                $oldValue = $properties['old'][$key] ?? null;
                if ($oldValue != $value) {
                    $changedFields[$key] = [
                        'from' => $oldValue,
                        'to' => $value,
                    ];
                }
            }
        }

        return $changedFields;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Manage Users</h2>
                    @can('users.create')
                        <flux:button wire:click="openModal" variant="primary" data-test="new-user-button">
                            New User
                        </flux:button>
                    @endcan
                </div>

                @if (session()->has('message'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('message') }}
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Table -->
                <div class="overflow-x-auto relative">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Email</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Roles</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Riscoin ID</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Invested Amount</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($user->roles as $role)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full capitalize">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                            @if ($user->roles->isEmpty())
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                                    No roles
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->riscoin_id ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        ${{ number_format($user->invested_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            @can('users.view')
                                                <flux:button wire:click="viewUser({{ $user->id }})" variant="ghost"
                                                    size="sm" data-test="view-user-{{ $user->id }}">
                                                    View Info
                                                </flux:button>
                                            @endcan
                                            @can('users.edit')
                                                <flux:button wire:click="edit({{ $user->id }})" variant="ghost"
                                                    size="sm" data-test="edit-user-{{ $user->id }}">
                                                    Edit
                                                </flux:button>
                                            @endcan
                                            @can('users.delete')
                                                <flux:button wire:click="delete({{ $user->id }})" variant="ghost"
                                                    size="sm"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                                    data-test="delete-user-{{ $user->id }}">
                                                    Delete
                                                </flux:button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($users->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 dark:text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No users found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Get started by creating your first user.</p>
                        </div>
                    @endif
                </div>

                <!-- Create/Edit User Modal -->
                <flux:modal wire:model="showModal" :title="$editMode ? 'Edit User' : 'New User'" max-width="lg">

                    <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-6">
                        <!-- Name -->
                        <flux:input wire:model="name" :label="__('Name')" type="text" required
                            :placeholder="__('Enter full name')" data-test="name-input" />

                        <!-- Email -->
                        <flux:input wire:model="email" :label="__('Email')" type="email" required
                            :placeholder="__('Enter email address')" data-test="email-input" />

                        <!-- Riscoin ID -->
                        <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')" type="text"
                            :placeholder="__('Enter Riscoin ID')" data-test="riscoin-id-input" />

                        <!-- Password -->
                        <flux:input wire:model="password" :label="__('Password')" type="password" :required="!$editMode"
                            :placeholder="__('Enter password')" data-test="password-input" />

                        <!-- Inviter's Code -->
                        <flux:input wire:model="inviters_code" :label="__('Inviter\'s Code')" type="text"
                            :placeholder="__('Enter inviter\'s code')" data-test="inviters-code-input" />

                        <!-- Invested Amount -->
                        <flux:input wire:model="invested_amount" :label="__('Invested Amount')" type="number"
                            step="0.01" :placeholder="__('0.00')" prefix="$"
                            data-test="invested-amount-input" />

                        <!-- Roles -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                            <div class="space-y-2">
                                @foreach ($roles as $role)
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span
                                            class="ml-2 text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                                @if ($roles->isEmpty())
                                    <p class="text-sm text-gray-500">No roles available. Create roles first.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <flux:checkbox wire:model="is_active" :label="__('Active User')"
                                data-test="is-active-checkbox" />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-6">
                            <flux:button type="button" wire:click="closeModal" data-test="cancel-user-button">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary" data-test="submit-user-button">
                                {{ $editMode ? 'Update' : 'Create' }} User
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>

                <!-- View User Info Modal -->
                <flux:modal wire:model="showViewModal" title="User Information & Activity Logs" max-width="7xl">

                    @if ($selectedUser)
                        <div class="space-y-6">
                            <!-- User Information -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">User Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</label>
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $selectedUser->name }}</p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                                        <p class="text-sm text-gray-900 dark:text-white">{{ $selectedUser->email }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Riscoin
                                            ID</label>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedUser->riscoin_id ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Invested
                                            Amount</label>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            ${{ number_format($selectedUser->invested_amount, 2) }}</p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <p class="text-sm">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $selectedUser->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $selectedUser->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Roles</label>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach ($selectedUser->roles as $role)
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full capitalize">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                            @if ($selectedUser->roles->isEmpty())
                                                <span
                                                    class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                                    No roles
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Withdrawals -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Withdrawal History
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                        <thead class="bg-gray-100 dark:bg-gray-600">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Date</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Amount</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Status</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Transaction ID</th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white divide-y divide-gray-200 dark:bg-gray-700 dark:divide-gray-600">
                                            @forelse ($selectedUser->withdrawals ?? [] as $withdrawal)
                                                <tr>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        {{ $withdrawal->created_at->format('M j, Y H:i') }}
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                        ${{ number_format($withdrawal->amount, 2) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if ($withdrawal->status === 'completed') bg-green-100 text-green-800
                                            @elseif($withdrawal->status === 'pending') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800 @endif">
                                                            {{ ucfirst($withdrawal->status) }}
                                                        </span>
                                                    </td>
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $withdrawal->transaction_id ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4"
                                                        class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                                        No withdrawal history found
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Activity Logs -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Activity Logs
                                </h3>

                                @if ($activityLogs->count() > 0)
                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                        @foreach ($activityLogs as $log)
                                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $this->formatActivityDescription($log) }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            By: {{ $log->causer->name ?? 'System' }} •
                                                            {{ $log->created_at->format('M j, Y g:i A') }} •
                                                            Model: {{ class_basename($log->subject_type) }}
                                                        </p>

                                                        @if ($log->description === 'updated')
                                                            @php $changedFields = $this->getChangedFields($log); @endphp
                                                            @if (count($changedFields) > 0)
                                                                <div class="mt-2 text-xs">
                                                                    <p
                                                                        class="font-medium text-gray-700 dark:text-gray-300">
                                                                        Changes:</p>
                                                                    <ul class="mt-1 space-y-1">
                                                                        @foreach ($changedFields as $field => $changes)
                                                                            <li
                                                                                class="text-gray-600 dark:text-gray-400">
                                                                                <span
                                                                                    class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                                <span
                                                                                    class="line-through text-red-500">{{ $changes['from'] ?? 'Empty' }}</span>
                                                                                →
                                                                                <span
                                                                                    class="text-green-500">{{ $changes['to'] ?? 'Empty' }}</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <div class="text-gray-400 dark:text-gray-500 mb-4">
                                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No activity
                                            logs found</h3>
                                        <p class="text-gray-500 dark:text-gray-400">This user hasn't performed any
                                            activities yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Close Button -->
                        <div class="flex justify-end pt-6">
                            <flux:button type="button" wire:click="closeViewModal">
                                Close
                            </flux:button>
                        </div>
                    @endif
                </flux:modal>

            </div>
        </div>
    </div>
</div>
