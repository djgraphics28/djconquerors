<?php

use Livewire\Volt\Component;
use App\Models\Team;
use App\Models\User;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $team;
    public $name = '';
    public $description = '';
    public $domain = '';
    public $editMode = false;
    public $teamId;
    public $showModal = false;
    public $showViewModal = false;
    public $showDeleteModal = false;
    public $activityLogs = [];
    public $selectedTeam = null;
    public $teamToDelete = null;

    // Media properties
    public $team_logo;
    public $favicon;
    public $logoToRemove = false;
    public $faviconToRemove = false;

    // Search and filters
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        // Initialize if needed
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:teams,name' . ($this->editMode ? ',' . $this->teamId : ''),
            'description' => 'nullable|string',
            'domain' => 'nullable|string|max:255',
            'team_logo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048', // 2MB max
            'favicon' => 'nullable|mimes:png,jpg,jpeg|max:1024', // 1MB max, removed ico
        ];
    }

    public function getTeamsProperty()
    {
        return Team::query()
            ->when($this->search, fn($query) => $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%'))
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    public function create()
    {
        $this->validate();

        $teamData = [
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
        ];

        $team = Team::create($teamData);

        // Handle logo upload
        if ($this->team_logo) {
            try {
                $team
                    ->addMedia($this->team_logo->getRealPath())
                    ->usingName($this->name . ' Logo')
                    ->usingFileName($this->team_logo->getClientOriginalName())
                    ->toMediaCollection('team_logo');
            } catch (\Exception $e) {
                \Log::error('Logo upload failed: ' . $e->getMessage());
            }
        }

        // Handle favicon upload
        if ($this->favicon) {
            try {
                $team
                    ->addMedia($this->favicon->getRealPath())
                    ->usingName($this->name . ' Favicon')
                    ->usingFileName($this->favicon->getClientOriginalName())
                    ->toMediaCollection('favicon');
            } catch (\Exception $e) {
                \Log::error('Favicon upload failed: ' . $e->getMessage());
            }
        }

        // Log the team creation activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($team)
            ->log('Team created');

        session()->flash('success', 'Team created successfully.');
        $this->reset(['name', 'description', 'domain', 'team_logo', 'favicon', 'showModal']);
        $this->dispatch('team-created');
    }

    public function edit($id)
    {
        $this->editMode = true;
        $this->teamId = $id;
        $this->team = Team::findOrFail($id);

        $this->name = $this->team->name;
        $this->description = $this->team->description;
        $this->domain = $this->team->domain;

        $this->showModal = true;
    }

    public function update()
    {
        $this->validate();

        $team = Team::findOrFail($this->teamId);

        $team->update([
            'name' => $this->name,
            'description' => $this->description,
            'domain' => $this->domain,
        ]);

        // Reload the team to ensure we have the latest data
        $team->refresh();

        // Handle logo upload
        if ($this->team_logo) {
            // Remove old logo
            $team->clearMediaCollection('team_logo');

            // Add new logo
            $team
                ->addMedia($this->team_logo->getRealPath())
                ->usingName($this->name . ' Logo')
                ->usingFileName($this->team_logo->getClientOriginalName())
                ->toMediaCollection('team_logo');
        }

        // Handle logo removal
        if ($this->logoToRemove) {
            $team->clearMediaCollection('team_logo');
            $this->logoToRemove = false;
        }

        // Handle favicon upload
        if ($this->favicon) {
            // Remove old favicon
            $team->clearMediaCollection('favicon');

            // Add new favicon
            $team
                ->addMedia($this->favicon->getRealPath())
                ->usingName($this->name . ' Favicon')
                ->usingFileName($this->favicon->getClientOriginalName())
                ->toMediaCollection('favicon');
        }

        // Handle favicon removal
        if ($this->faviconToRemove) {
            $team->clearMediaCollection('favicon');
            $this->faviconToRemove = false;
        }

        // Log the team update activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($team)
            ->log('Team updated');

        session()->flash('success', 'Team updated successfully.');
        $this->reset(['name', 'description', 'domain', 'team_logo', 'favicon', 'editMode', 'teamId', 'showModal', 'logoToRemove', 'faviconToRemove', 'team']);
        $this->dispatch('team-updated');
    }

    public function confirmDelete($id)
    {
        $this->teamToDelete = Team::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!$this->teamToDelete) {
            return;
        }

        // Check if team has members
        if ($this->teamToDelete->members()->count() > 0) {
            session()->flash('error', 'Cannot delete team with existing members. Please reassign members first.');
            $this->showDeleteModal = false;
            $this->teamToDelete = null;
            return;
        }

        // Log the team deletion activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->teamToDelete)
            ->log('Team deleted');

        // Delete the team (this will also remove media)
        $this->teamToDelete->delete();

        session()->flash('success', 'Team deleted successfully.');
        $this->showDeleteModal = false;
        $this->teamToDelete = null;
        $this->dispatch('team-deleted');
    }

    public function viewTeam($id)
    {
        $this->selectedTeam = Team::with(['members' => function($query) {
            $query->select('id', 'name', 'email', 'riscoin_id', 'team_id');
        }])->findOrFail($id);

        $this->loadActivityLogs($id);
        $this->showViewModal = true;
    }

    public function loadActivityLogs($teamId)
    {
        $this->activityLogs = Activity::where('subject_id', $teamId)
            ->where('subject_type', Team::class)
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    public function removeLogo()
    {
        $this->logoToRemove = true;
        $this->dispatch('logo-removed');
    }

    public function removeFavicon()
    {
        $this->faviconToRemove = true;
        $this->dispatch('favicon-removed');
    }

    public function openCreateModal()
    {
        $this->reset(['name', 'description', 'domain', 'team_logo', 'favicon', 'editMode', 'teamId', 'logoToRemove', 'faviconToRemove']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->reset(['name', 'description', 'domain', 'team_logo', 'favicon', 'editMode', 'teamId', 'showModal', 'logoToRemove', 'faviconToRemove']);
        $this->resetValidation();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedTeam = null;
        $this->activityLogs = [];
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->teamToDelete = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }
}; ?>

<div>
    <x-page-header :title="__('Teams Management')" :description="__('Manage your organization teams')" />

    <div class="mx-auto max-w-8xl space-y-6 p-6">
        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Teams</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ Team::count() }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Members</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ User::whereNotNull('team_id')->count() }}</p>
                    </div>
                    <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg Members/Team</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ Team::count() > 0 ? number_format(User::whereNotNull('team_id')->count() / Team::count(), 1) : 0 }}
                        </p>
                    </div>
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search teams..." class="max-w-md" icon="magnifying-glass" />

                <flux:select wire:model.live="perPage" class="w-24">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </flux:select>
            </div>

            @can('teams.create')
                <flux:button wire:click="openCreateModal" icon="plus" variant="primary">
                    {{ __('Create Team') }}
                </flux:button>
            @endcan
        </div>

        <!-- Teams Table -->
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Logo
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Team Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Members
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Created
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @forelse ($this->teams as $team)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($team->getFirstMediaUrl('team_logo'))
                                        <img src="{{ $team->getFirstMediaUrl('team_logo') }}" alt="{{ $team->name }}" class="h-12 w-12 rounded-lg object-cover">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                                            <svg class="h-6 w-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $team->name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">
                                        {{ $team->description ?: 'No description' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $team->members_count }} {{ Str::plural('member', $team->members_count) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $team->created_at->format('M d, Y') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('teams.view')
                                            <flux:button wire:click="viewTeam({{ $team->id }})" size="sm" variant="ghost" icon="eye" title="View Details" />
                                        @endcan

                                        @can('teams.edit')
                                            <flux:button wire:click="edit({{ $team->id }})" size="sm" variant="ghost" icon="pencil" title="Edit Team" />
                                        @endcan

                                        @can('teams.delete')
                                            <flux:button wire:click="confirmDelete({{ $team->id }})" size="sm" variant="ghost" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" icon="trash" title="Delete Team" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-4 h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $search ? 'No teams found matching your search.' : 'No teams yet. Create your first team!' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($this->teams->hasPages())
                <div class="border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                    {{ $this->teams->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div>
            <flux:heading size="lg">{{ $editMode ? __('Edit Team') : __('Create New Team') }}</flux:heading>
            <flux:subheading>{{ $editMode ? __('Update team information') : __('Add a new team to your organization') }}</flux:subheading>
        </div>

        <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-6">
            <!-- Team Name -->
            <flux:input wire:model="name" label="Team Name" placeholder="Enter team name" required />

            <!-- Description -->
            <flux:textarea wire:model="description" label="Description" placeholder="Enter team description (optional)" rows="4" />

            <!-- Domain -->
            <flux:input wire:model="domain" label="Domain" placeholder="Enter team domain (optional)" type="text" />

            <!-- Team Logo -->
            <div class="space-y-2">
                <flux:label>Team Logo</flux:label>

                @if($editMode && $team && $team->getFirstMediaUrl('team_logo') && !$logoToRemove)
                    <div class="mb-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $team->getFirstMediaUrl('team_logo') }}" alt="Current logo" class="h-20 w-20 rounded-lg object-cover">
                            <flux:button type="button" wire:click="removeLogo" size="sm" variant="danger">
                                Remove Logo
                            </flux:button>
                        </div>
                    </div>
                @endif

                <input type="file" wire:model="team_logo" accept="image/*" class="w-full rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">

                @error('team_logo')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                @if($team_logo)
                    <div class="mt-2">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Preview:</p>
                        <img src="{{ $team_logo->temporaryUrl() }}" alt="Preview" class="mt-2 h-20 w-20 rounded-lg object-cover">
                    </div>
                @endif

                <p class="text-xs text-zinc-500 dark:text-zinc-400">Maximum file size: 2MB. Supported formats: JPG, PNG, GIF</p>
            </div>

            <!-- Favicon -->
            <div class="space-y-2">
                <flux:label>Favicon</flux:label>

                @if($editMode && $team && $team->getFirstMediaUrl('favicon') && !$faviconToRemove)
                    <div class="mb-4">
                        <div class="flex items-center gap-4">
                            <img src="{{ $team->getFirstMediaUrl('favicon') }}" alt="Current favicon" class="h-12 w-12 rounded object-cover">
                            <flux:button type="button" wire:click="removeFavicon" size="sm" variant="danger">
                                Remove Favicon
                            </flux:button>
                        </div>
                    </div>
                @endif

                <input type="file" wire:model="favicon" accept="image/png,image/jpeg,image/jpg,.png,.jpg,.jpeg" class="w-full rounded-md border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">

                @error('favicon')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                @if($favicon)
                    <div class="mt-2">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            File selected: <span class="font-medium">{{ $favicon->getClientOriginalName() }}</span>
                            ({{ number_format($favicon->getSize() / 1024, 2) }} KB)
                        </p>
                        @if(in_array($favicon->getClientOriginalExtension(), ['png', 'jpg', 'jpeg']))
                            <img src="{{ $favicon->temporaryUrl() }}" alt="Preview" class="mt-2 h-12 w-12 rounded object-cover">
                        @endif
                    </div>
                @endif

                <p class="text-xs text-zinc-500 dark:text-zinc-400">Maximum file size: 1MB. Recommended: 32x32px or 64x64px. Supported formats: PNG, JPG</p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeModal" variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editMode ? __('Update Team') : __('Create Team') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- View Team Modal -->
    <flux:modal wire:model="showViewModal" class="max-w-4xl">
        @if($selectedTeam)
            <div>
                <div class="mb-6 flex items-start gap-4">
                    @if($selectedTeam->getFirstMediaUrl('team_logo'))
                        <img src="{{ $selectedTeam->getFirstMediaUrl('team_logo') }}" alt="{{ $selectedTeam->name }}" class="h-20 w-20 rounded-lg object-cover">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                            <svg class="h-10 w-10 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1">
                        <flux:heading size="lg">{{ $selectedTeam->name }}</flux:heading>
                        <flux:subheading>{{ $selectedTeam->description ?: 'No description provided' }}</flux:subheading>
                        @if($selectedTeam->domain)
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                <span class="font-medium">Domain:</span>
                                <a href="https://{{ $selectedTeam->domain }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $selectedTeam->domain }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Team Stats -->
                <div class="mb-6 grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Members</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $selectedTeam->members->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Created</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedTeam->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Last Updated</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selectedTeam->updated_at->diffForHumans() }}</p>
                    </div>
                </div>

                <!-- Team Members -->
                <div class="mb-6">
                    <flux:heading size="base" class="mb-4">Team Members ({{ $selectedTeam->members->count() }})</flux:heading>

                    @if($selectedTeam->members->count() > 0)
                        <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            @foreach($selectedTeam->members as $member)
                                <div class="flex items-center justify-between rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ Str::upper(Str::substr($member->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $member->name }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $member->riscoin_id }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No members in this team yet.</p>
                        </div>
                    @endif
                </div>

                <!-- Activity Logs -->
                <div>
                    <flux:heading size="base" class="mb-4">Recent Activity</flux:heading>

                    @if(count($activityLogs) > 0)
                        <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            @foreach($activityLogs as $log)
                                <div class="flex items-start gap-3 border-b border-zinc-200 pb-2 last:border-0 dark:border-zinc-700">
                                    <div class="mt-1 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                        <svg class="h-3 w-3 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11H9v-2h2v2zm0-4H9V5h2v4z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm text-zinc-900 dark:text-white">
                                            <span class="font-medium">{{ $log->causer->name ?? 'System' }}</span>
                                            {{ $log->description }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No activity logs available.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <flux:button wire:click="closeViewModal" variant="primary">
                        {{ __('Close') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        @if($teamToDelete)
            <div>
                <div class="mb-4 flex items-center justify-center">
                    <div class="rounded-full bg-red-100 p-3 dark:bg-red-900">
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.268 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <flux:heading size="lg" class="text-center">Delete Team</flux:heading>
                <flux:subheading class="text-center">Are you sure you want to delete <strong>{{ $teamToDelete->name }}</strong>?</flux:subheading>

                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">
                    This action cannot be undone. The team and all associated data will be permanently removed.
                </p>

                @if($teamToDelete->members()->count() > 0)
                    <div class="mt-4 rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Warning:</strong> This team has {{ $teamToDelete->members()->count() }} member(s). Please reassign them before deleting.
                        </p>
                    </div>
                @endif

                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="closeDeleteModal" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="delete" variant="danger">
                        {{ __('Delete Team') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
