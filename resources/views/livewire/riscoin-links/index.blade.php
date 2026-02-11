<?php

use Livewire\Volt\Component;
use App\Models\RiscoinLink;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    use WithPagination;

    public $riscoinLink;
    public $url = '';
    public $is_active = false;
    public $editMode = false;
    public $linkId;
    public $showModal = false;
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $activityLogs = [];
    public $selectedLink = null;
    public $linkToDelete = null;

    // Search and filters
    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        // Initialize if needed
    }

    public function rules()
    {
        return [
            'url' => 'required|url|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function getLinksProperty()
    {
        return RiscoinLink::query()
            ->when($this->search, fn($query) => $query->where('url', 'like', '%' . $this->search . '%'))
            ->when($this->statusFilter !== '', fn($query) => $query->where('is_active', $this->statusFilter))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function create()
    {
        $this->validate();

        $linkData = [
            'url' => $this->url,
            'is_active' => $this->is_active,
        ];

        $link = RiscoinLink::create($linkData);

        // Log the link creation activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($link)
            ->withProperties(['attributes' => $linkData])
            ->log('created');

        $this->resetForm();
        $this->showModal = false;
        session()->flash('message', 'Riscoin link created successfully!');
    }

    public function edit($id)
    {
        $this->editMode = true;
        $this->linkId = $id;
        $this->riscoinLink = RiscoinLink::findOrFail($id);

        $this->url = $this->riscoinLink->url;
        $this->is_active = $this->riscoinLink->is_active;

        $this->showModal = true;
    }

    public function update()
    {
        $this->validate();

        $link = RiscoinLink::findOrFail($this->linkId);

        $linkData = [
            'url' => $this->url,
            'is_active' => $this->is_active,
        ];

        $link->update($linkData);
        $link->refresh();

        // Log the link update activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($link)
            ->withProperties(['attributes' => $linkData])
            ->log('updated');

        $this->resetForm();
        $this->showModal = false;
        session()->flash('message', 'Riscoin link updated successfully!');
    }

    public function confirmDelete($id)
    {
        $this->linkToDelete = RiscoinLink::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->linkToDelete) {
            // Log the link deletion activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($this->linkToDelete)
                ->log('deleted');

            $this->linkToDelete->delete();
            $this->showDeleteModal = false;
            $this->linkToDelete = null;
            session()->flash('message', 'Riscoin link deleted successfully!');
        }
    }

    public function viewLink($id)
    {
        $this->selectedLink = RiscoinLink::findOrFail($id);
        $this->activityLogs = Activity::forSubject($this->selectedLink)
            ->with('causer')
            ->latest()
            ->take(10)
            ->get();
        $this->showViewModal = true;
    }

    public function toggleStatus($id)
    {
        $link = RiscoinLink::findOrFail($id);
        $link->update(['is_active' => !$link->is_active]);

        // Log the status toggle activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($link)
            ->withProperties(['is_active' => $link->is_active])
            ->log('status_toggled');

        session()->flash('message', 'Riscoin link status updated successfully!');
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function closeViewModal()
    {
        $this->selectedLink = null;
        $this->activityLogs = [];
        $this->showViewModal = false;
    }

    public function closeDeleteModal()
    {
        $this->linkToDelete = null;
        $this->showDeleteModal = false;
    }

    public function resetForm()
    {
        $this->editMode = false;
        $this->linkId = null;
        $this->url = '';
        $this->is_active = false;
        $this->riscoinLink = null;
        $this->resetValidation();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->perPage = 10;
        $this->resetPage();
    }
}; ?>

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('riscoin-links.index') }}"
                    class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    Riscoin Links
                </a>
            </li>
        </ol>
    </nav>

    <div>
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Riscoin Links Management</h2>
            <flux:button wire:click="openModal" icon="plus" variant="primary">
                Create New Link
            </flux:button>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-900 dark:border-green-700 dark:text-green-300">
                {{ session('message') }}
            </div>
        @endif

        <!-- Filters -->
        <div class="mb-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <flux:input wire:model.live.debounce.500ms="search" type="text"
                        placeholder="Search by URL..." />
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </flux:select>
                </div>

                <!-- Rows Per Page -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rows Per Page</label>
                    <flux:select wire:model.live="perPage">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </flux:select>
                </div>
            </div>

            <!-- Reset Filters -->
            <div class="mt-4 flex justify-end">
                <flux:button wire:click="resetFilters" variant="ghost" size="sm">
                    Reset Filters
                </flux:button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto relative">
            @if ($this->links->isNotEmpty())
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                #</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                URL</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Created At</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @foreach ($this->links as $index => $link)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $this->links->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    <a href="{{ $link->url }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 break-all">
                                        {{ Str::limit($link->url, 50) }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button wire:click="toggleStatus({{ $link->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $link->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ $link->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $link->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <flux:button wire:click="viewLink({{ $link->id }})" variant="ghost" size="sm" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </flux:button>
                                        <flux:button wire:click="edit({{ $link->id }})" variant="ghost" size="sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </flux:button>
                                        <flux:button wire:click="confirmDelete({{ $link->id }})" variant="danger" size="sm" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $this->links->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No links found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new Riscoin link.</p>
                    <div class="mt-6">
                        <flux:button wire:click="openModal" icon="plus" variant="primary">
                            Create New Link
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal wire:model="showModal" name="link-form-modal" class="min-w-[600px]">
        <div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $editMode ? 'Edit Riscoin Link' : 'Create New Riscoin Link' }}
                </h2>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="px-6 py-4">
                    <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-6">
                        <!-- URL -->
                        <div>
                            <flux:input wire:model="url" label="URL" type="url" required placeholder="https://example.com" />
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center">
                            <flux:checkbox wire:model="is_active" label="Active Link" />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <flux:button type="button" wire:click="closeModal" variant="ghost">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                {{ $editMode ? 'Update' : 'Create' }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- View Modal -->
    <flux:modal wire:model="showViewModal" name="link-view-modal" class="min-w-[800px]">
        <div>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Link Details
                </h2>
                <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="px-6 py-4">
                    @if ($selectedLink)
                        <div class="space-y-6">
                            <!-- Link Information -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Link Information</h3>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">URL</label>
                                        <p class="text-sm text-gray-900 dark:text-white break-all">
                                            <a href="{{ $selectedLink->url }}" target="_blank" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                {{ $selectedLink->url }}
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedLink->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                                {{ $selectedLink->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</label>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedLink->created_at->format('F d, Y h:i A') }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</label>
                                        <p class="text-sm text-gray-900 dark:text-white">
                                            {{ $selectedLink->updated_at->format('F d, Y h:i A') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity Log -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity Log</h3>
                                @if ($activityLogs->count() > 0)
                                    <div class="space-y-3">
                                        @foreach ($activityLogs as $log)
                                            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $log->causer?->name ?? 'System' }}
                                                        </span>
                                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $log->description }}</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $log->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No activity logged yet.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Close Button -->
                        <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                            <flux:button type="button" wire:click="closeViewModal">
                                Close
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" name="link-delete-modal">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full dark:bg-red-900">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h3 class="mt-4 text-lg font-medium text-center text-gray-900 dark:text-white">
                Delete Riscoin Link
            </h3>

            <p class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">
                Are you sure you want to delete this link? This action cannot be undone.
            </p>

            @if ($linkToDelete)
                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <p class="text-sm text-gray-900 dark:text-white break-all">
                        {{ $linkToDelete->url }}
                    </p>
                </div>
            @endif

            <div class="flex justify-end space-x-3 mt-6">
                <flux:button type="button" wire:click="closeDeleteModal" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button type="button" wire:click="delete" variant="danger">
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
