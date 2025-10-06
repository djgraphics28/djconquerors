<?php

use Livewire\Volt\Component;
use App\Models\EmailReceiver;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $editingId = null;
    public $showModal = false;
    public $deleteModal = false;
    public $receiverToDelete = null;

    // Form fields
    public $user_id;
    public $receive_appointment_notifications = true;
    public $receive_system_notifications = false;
    public $receive_user_registrations = false;
    public $is_active = true;
    public $custom_settings = '';

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'receive_appointment_notifications' => 'boolean',
        'receive_system_notifications' => 'boolean',
        'receive_user_registrations' => 'boolean',
        'is_active' => 'boolean',
        'custom_settings' => 'nullable|json',
    ];

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editingId = null;
    }

    public function edit($id)
    {
        $receiver = EmailReceiver::findOrFail($id);

        $this->editingId = $id;
        $this->user_id = $receiver->user_id;
        $this->receive_appointment_notifications = $receiver->receive_appointment_notifications;
        $this->receive_system_notifications = $receiver->receive_system_notifications;
        $this->receive_user_registrations = $receiver->receive_user_registrations;
        $this->is_active = $receiver->is_active;
        $this->custom_settings = $receiver->custom_settings ? json_encode($receiver->custom_settings) : '';

        $this->showModal = true;
    }

    public function save()
    {
        // Custom validation for unique user when creating
        $rules = $this->rules;
        if (!$this->editingId) {
            $rules['user_id'] = 'required|exists:users,id|unique:email_receivers,user_id';
        }

        $this->validate($rules);

        $data = [
            'user_id' => $this->user_id,
            'receive_appointment_notifications' => $this->receive_appointment_notifications,
            'receive_system_notifications' => $this->receive_system_notifications,
            'receive_user_registrations' => $this->receive_user_registrations,
            'is_active' => $this->is_active,
            'custom_settings' => $this->custom_settings ? json_decode($this->custom_settings, true) : null,
        ];

        if ($this->editingId) {
            EmailReceiver::find($this->editingId)->update($data);
            session()->flash('message', 'Email receiver updated successfully!');
        } else {
            EmailReceiver::create($data);
            session()->flash('message', 'Email receiver created successfully!');
        }

        $this->closeModal();
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->receiverToDelete = $id;
        $this->deleteModal = true;
    }

    public function delete()
    {
        EmailReceiver::find($this->receiverToDelete)->delete();
        $this->deleteModal = false;
        $this->receiverToDelete = null;
        session()->flash('message', 'Email receiver deleted successfully!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal()
    {
        $this->deleteModal = false;
        $this->receiverToDelete = null;
    }

    private function resetForm()
    {
        $this->reset(['editingId', 'user_id', 'receive_appointment_notifications', 'receive_system_notifications', 'receive_user_registrations', 'is_active', 'custom_settings']);
        $this->resetErrorBag();
    }

    public function getReceiversProperty()
    {
        return EmailReceiver::with('user')->latest()->paginate(10);
    }

    // Get available users (users not already in email receivers)
    public function getAvailableUsersProperty()
    {
        $existingUserIds = EmailReceiver::pluck('user_id')->toArray();

        return User::whereNotIn('id', $existingUserIds)
            ->orWhere('id', $this->user_id) // Include current user when editing
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }
}; ?>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Email Receivers</h2>
                        <button wire:click="create"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Add New Receiver
                        </button>
                    </div>

                    <!-- Flash Message -->
                    @if (session()->has('message'))
                        <div
                            class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 dark:bg-green-800 dark:border-green-600 dark:text-green-200">
                            {{ session('message') }}
                        </div>
                    @endif

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white dark:bg-gray-800">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-left text-gray-800 dark:text-white">
                                        User</th>
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-center text-gray-800 dark:text-white">
                                        Appointment</th>
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-center text-gray-800 dark:text-white">
                                        System</th>
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-center text-gray-800 dark:text-white">
                                        Registrations</th>
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-center text-gray-800 dark:text-white">
                                        Status</th>
                                    <th
                                        class="py-3 px-4 border-b dark:border-gray-600 text-center text-gray-800 dark:text-white">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->receivers as $receiver)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="py-3 px-4 border-b dark:border-gray-600">
                                            <div class="text-gray-800 dark:text-white">
                                                {{ $receiver->user->name ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $receiver->user->email ?? '' }}</div>
                                        </td>
                                        <td class="py-3 px-4 border-b dark:border-gray-600 text-center">
                                            @if ($receiver->receive_appointment_notifications)
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded dark:bg-green-800 dark:text-green-200">Yes</span>
                                            @else
                                                <span
                                                    class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded dark:bg-red-800 dark:text-red-200">No</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 border-b dark:border-gray-600 text-center">
                                            @if ($receiver->receive_system_notifications)
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded dark:bg-green-800 dark:text-green-200">Yes</span>
                                            @else
                                                <span
                                                    class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded dark:bg-red-800 dark:text-red-200">No</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 border-b dark:border-gray-600 text-center">
                                            @if ($receiver->receive_user_registrations)
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded dark:bg-green-800 dark:text-green-200">Yes</span>
                                            @else
                                                <span
                                                    class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded dark:bg-red-800 dark:text-red-200">No</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 border-b dark:border-gray-600 text-center">
                                            @if ($receiver->is_active)
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded dark:bg-green-800 dark:text-green-200">Active</span>
                                            @else
                                                <span
                                                    class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded dark:bg-red-800 dark:text-red-200">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 border-b dark:border-gray-600">
                                            <div class="flex justify-center space-x-2">
                                                <button wire:click="edit({{ $receiver->id }})"
                                                    class="bg-yellow-500 hover:bg-yellow-700 text-white text-xs px-3 py-1 rounded transition duration-200">
                                                    Edit
                                                </button>
                                                <button wire:click="confirmDelete({{ $receiver->id }})"
                                                    class="bg-red-500 hover:bg-red-700 text-white text-xs px-3 py-1 rounded transition duration-200">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">
                                            No email receivers found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $this->receivers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">
                        {{ $editingId ? 'Edit' : 'Create' }} Email Receiver
                    </h3>

                    <form wire:submit.prevent="save">
                        <!-- User Selection -->
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="user_id">
                                Select User
                            </label>
                            <select id="user_id" wire:model="user_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select a user</option>
                                @foreach ($this->availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                            @if (!$this->editingId && count($this->availableUsers) === 0)
                                <span class="text-yellow-600 dark:text-yellow-400 text-xs">All users are already added
                                    as email receivers.</span>
                            @endif
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3 mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="receive_appointment_notifications"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Receive Appointment
                                    Notifications</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="receive_system_notifications"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Receive System
                                    Notifications</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="receive_user_registrations"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Receive User
                                    Registrations</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_active"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-300">Active</span>
                            </label>
                        </div>

                        <!-- Custom Settings -->
                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2"
                                for="custom_settings">
                                Custom Settings (JSON)
                            </label>
                            <textarea id="custom_settings" wire:model="custom_settings" rows="3" placeholder='{"key": "value"}'
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            @error('custom_settings')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="closeModal"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                Cancel
                            </button>
                            <button type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                {{ $editingId ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($deleteModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Confirm Delete</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">Are you sure you want to delete this email
                        receiver? This action cannot be undone.</p>

                    <div class="flex justify-end space-x-3">
                        <button wire:click="closeDeleteModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Cancel
                        </button>
                        <button wire:click="delete"
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
