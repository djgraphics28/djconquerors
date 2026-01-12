<?php

use Livewire\Volt\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $user;
    public $name;
    public $email;
    public $riscoin_id;
    public $password;
    public $inviters_code;
    public $invested_amount;
    public $birth_date;
    public $date_joined;
    public $is_active = true;
    public $roles = [];
    public $inviters = [];
    public $selectedRoles = [];
    public $editMode = false;
    public $userId;
    public $showModal = false;
    public $showViewModal = false;
    public $showAssistantModal = false;
    public $activityLogs = [];
    public $selectedUser = null;
    public $assistants = [];

    // Assistant selection
    public $assistantUserId;
    public $assistantTargetUserId;
    public $assistantTargetUser = null;
    public $assistantSearch = '';

    // Media properties
    public $avatar;
    public $avatarToRemove = false;

    // Search and filters
    public $search = '';
    public $dateJoined = '';
    public $statusFilter = '';
    public $inviterFilter = '';
    public $capitalRecoveryFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateJoined' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'capitalRecoveryFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
        'inviterFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->loadRoles();
        $this->loadInviters();
        $this->loadAssistants();
    }

    public function loadAssistants()
    {
        // Load full user models excluding authenticated user and target user
        $this->assistants = User::where('is_active', true)
            ->where('id', '!=', 1) // Exclude super admin
            ->when($this->assistantTargetUserId, function ($query) {
                $query->where('id', '!=', $this->assistantTargetUserId);
            })
            ->get();
    }

    public function loadInviters()
    {
        $this->inviters = User::select('id', 'name', 'riscoin_id')->get();
    }

    public function loadRoles()
    {
        $this->roles = Role::all();
    }

    public function loadActivityLogs($userId)
    {
        $this->activityLogs = Activity::where('causer_id', $userId)->where('causer_type', User::class)->with('causer')->orderBy('created_at', 'desc')->limit(50)->get();
    }

    public function viewUser($userId)
    {
        $this->selectedUser = User::with(['roles', 'withdrawals'])->find($userId);
        $this->loadActivityLogs($userId);
        $this->showViewModal = true;
    }

    public function addAssistant($userId)
    {
        // Set the target user that will receive the assistant
        $this->assistantTargetUserId = $userId;
        $this->assistantTargetUser = User::find($userId);
        // If target already has an assistant, preselect it
        $this->assistantUserId = $this->assistantTargetUser->assistant_id ?? null;
        $this->assistantSearch = '';
        $this->showAssistantModal = true;
    }

    // Get total withdrawals amount for a user (only paid status)
    public function getTotalWithdrawals($userId)
    {
        $user = User::with('withdrawals')->find($userId);
        if (!$user || !$user->withdrawals) {
            return 0;
        }

        return $user->withdrawals->where('status', 'paid')->sum('amount');
    }

    // Get capital recovery status
    public function getCapitalRecoveryStatus($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'status' => 'unknown',
                'label' => 'Unknown',
                'color' => 'gray',
            ];
        }

        $totalWithdrawals = $this->getTotalWithdrawals($userId);
        $investedAmount = $user->invested_amount ?? 0;

        if ($investedAmount == 0) {
            return [
                'status' => 'no_investment',
                'label' => 'No Investment',
                'color' => 'gray',
            ];
        }

        if ($totalWithdrawals < $investedAmount) {
            $percentage = ($totalWithdrawals / $investedAmount) * 100;
            return [
                'status' => 'recovering',
                'label' => 'Recovering (' . number_format($percentage, 1) . '%)',
                'color' => 'yellow',
            ];
        } else {
            return [
                'status' => 'recovered',
                'label' => 'Capital Recovered',
                'color' => 'green',
            ];
        }
    }

    private function getFilteredUserIdsByCapitalRecovery()
    {
        $currentUser = User::find(auth()->user()->id);
        $userRiscoindId = $currentUser->riscoin_id;
        $allTeamMembers = $this->getAllTeamMembers($userRiscoindId);
        $filteredUserIds = [];

        foreach ($allTeamMembers as $user) {
            $capitalStatus = $this->getCapitalRecoveryStatus($user->id);

            switch ($this->capitalRecoveryFilter) {
                case 'recovered':
                    if ($capitalStatus['status'] === 'recovered') {
                        $filteredUserIds[] = $user->id;
                    }
                    break;
                case 'recovering':
                    if ($capitalStatus['status'] === 'recovering') {
                        $filteredUserIds[] = $user->id;
                    }
                    break;
                case 'no_investment':
                    if ($capitalStatus['status'] === 'no_investment') {
                        $filteredUserIds[] = $user->id;
                    }
                    break;
            }
        }

        return $filteredUserIds;
    }

    // Get all team members recursively (direct invites + their invites + their invites, etc.)
    public function getAllTeamMembers($riscoinId)
    {
        $allMembers = collect();

        // Get direct invites
        $directInvites = User::with(['roles', 'inviter'])
            ->where('inviters_code', $riscoinId)
            ->where('inviters_code', '!=', '')
            ->get();

        foreach ($directInvites as $invite) {
            $allMembers->push($invite);
            // Recursively get invites of this invite
            $nestedInvites = $this->getAllTeamMembers($invite->riscoin_id);
            $allMembers = $allMembers->merge($nestedInvites);
        }

        return $allMembers;
    }

    // Get team level for display (1 for direct, 2 for their invites, etc.)
    public function getTeamLevel($userId, $currentUserRiscoinId)
    {
        $level = 1;
        $user = User::find($userId);

        if (!$user || !$user->inviters_code) {
            return $level;
        }

        // If user's inviter is the current user, it's level 1
        if ($user->inviters_code === $currentUserRiscoinId) {
            return $level;
        }

        // Otherwise, find the level by checking the hierarchy
        $currentInviterCode = $user->inviters_code;
        $level = 2; // Start from level 2 since we already checked level 1

        while ($currentInviterCode && $currentInviterCode !== $currentUserRiscoinId) {
            $inviter = User::where('riscoin_id', $currentInviterCode)->first();
            if (!$inviter || !$inviter->inviters_code) {
                break;
            }

            if ($inviter->inviters_code === $currentUserRiscoinId) {
                return $level;
            }

            $currentInviterCode = $inviter->inviters_code;
            $level++;

            // Safety check to prevent infinite loops
            if ($level > 10) {
                break;
            }
        }

        return $level;
    }

    // Get total team count for a user (recursive)
    public function getTeamCount($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return 0;
        }

        $directInvites = User::where('inviters_code', $user->riscoin_id)->count();
        $totalCount = $directInvites;

        // Get direct invites to calculate their teams recursively
        $directUsers = User::where('inviters_code', $user->riscoin_id)->get();

        foreach ($directUsers as $directUser) {
            $totalCount += $this->getTeamCount($directUser->id);
        }

        return $totalCount;
    }

    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email' . ($this->editMode ? ',' . $this->userId : ''),
            'riscoin_id' => 'nullable|string',
            'password' => $this->editMode ? 'nullable|min:8' : 'required|min:8',
            'inviters_code' => 'nullable|string',
            'invested_amount' => 'nullable|numeric|min:0',
            'birth_date' => 'required',
            'date_joined' => 'required',
            'is_active' => 'boolean',
            'selectedRoles' => 'array',
            'avatar' => 'nullable|image|max:2048', // 2MB max
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
            'birth_date' => $this->birth_date,
            'date_joined' => $this->date_joined,
            'is_active' => $this->is_active,
            'email_verified_at' => now(),
        ];

        $user = User::create($userData);

        // Handle avatar upload
        if ($this->avatar) {
            $user
                ->addMedia($this->avatar->getRealPath())
                ->usingName('avatar')
                ->usingFileName($this->avatar->getClientOriginalName())
                ->toMediaCollection('avatar');
        }

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
        $this->birth_date = $user->birth_date;
        $this->date_joined = $user->date_joined;
        $this->is_active = $user->is_active;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->avatarToRemove = false;
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

        // Handle avatar upload/removal
        if ($this->avatarToRemove) {
            $user->clearMediaCollection('avatar');
        } elseif ($this->avatar) {
            $user->clearMediaCollection('avatar');
            $user
                ->addMedia($this->avatar->getRealPath())
                ->usingName('avatar')
                ->usingFileName($this->avatar->getClientOriginalName())
                ->toMediaCollection('avatar');
        }

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
        $this->showModal = false;
        session()->flash('message', 'User updated successfully.');
    }

    public function removeAvatar()
    {
        $this->avatarToRemove = true;
        $this->avatar = null;
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

        session()->flash('message', 'User deleted successfully.');
    }

    public function resetForm()
    {
        $this->reset(['name', 'email', 'riscoin_id', 'password', 'inviters_code', 'invested_amount', 'is_active', 'userId', 'user', 'selectedRoles', 'avatar', 'avatarToRemove']);
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

    public function closeAssistantModal()
    {
        $this->showAssistantModal = false;
        $this->resetForm();
    }

    // Select an assistant from the list
    public function selectAssistant($assistantId)
    {
        $this->assistantUserId = $assistantId;
    }

    // Deselect currently selected assistant
    public function deselectAssistant()
    {
        $this->assistantUserId = null;
        $target = User::find($this->assistantTargetUserId);
        if (!$target) {
            session()->flash('error', 'Target user not found.');
            return;
        }

        $target->assistant_id = null;
        $target->save();

        session()->flash('message', 'Assistant removed successfully.');
    }

    // Add/assign assistant to the target user
    public function addAssistantUser()
    {
        if (!$this->assistantTargetUserId) {
            session()->flash('error', 'Target user not set.');
            return;
        }

        if (!$this->assistantUserId) {
            session()->flash('error', 'Please select an assistant.');
            return;
        }

        $target = User::find($this->assistantTargetUserId);
        if (!$target) {
            session()->flash('error', 'Target user not found.');
            return;
        }

        $target->assistant_id = $this->assistantUserId;
        $target->save();

        // Update local target user for immediate UI reflection
        if ($this->assistantTargetUser && $this->assistantTargetUser->id === $target->id) {
            $this->assistantTargetUser->assistant_id = $this->assistantUserId;
        }

        session()->flash('message', 'Assistant assigned successfully.');
        // $this->closeAssistantModal();
    }

    // Filter assistants by search
    public function getFilteredAssistantsProperty()
    {
        $search = trim(strtolower($this->assistantSearch));
        if ($search === '') {
            return $this->assistants;
        }

        return $this->assistants->filter(function ($u) use ($search) {
            return Str::contains(strtolower($u->name ?? ''), $search) || Str::contains(strtolower($u->email ?? ''), $search) || Str::contains(strtolower($u->riscoin_id ?? ''), $search);
        });
    }

    // Sample text for textarea
    public function getAssistantSampleTextProperty()
    {
        $depositorId = $this->assistantTargetUser->riscoin_id ?? 'N/A';
        $inviterId = $this->assistantTargetUser->inviters_code ?? 'N/A';

        $assistantUser = $this->assistantUserId ? User::find($this->assistantUserId) : null;
        // prefer riscoin_id if available, otherwise fallback to id
        $assistantId = $assistantUser ? $assistantUser->riscoin_id ?? $assistantUser->id : 'N/A';

        $now = now()->toIsoString();

        return "Hi Sir Martin\nHere is my application reward request from my investor, {$this->assistantTargetUser?->name}\n\nInviter's Riscoin Account : {$inviterId}\n\nDepositor's Riscoin Account : {$depositorId}\n\nAssister's Riscoin Account: {$assistantId}\n\n";
    }

    // Reset filters
    public function resetFilters()
    {
        $this->reset(['search', 'dateJoined', 'statusFilter', 'capitalRecoveryFilter', 'inviterFilter']);
        $this->resetPage();
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

    public function getUsersProperty()
    {
        $currentUser = User::find(auth()->user()->id);
        $userRiscoindId = $currentUser->riscoin_id;

        // Get all team members (direct invites + their invites recursively)
        $allTeamMembers = $this->getAllTeamMembers($userRiscoindId);

        // Convert to query for filtering and pagination
        $userIds = $allTeamMembers->pluck('id')->toArray();

        if (empty($userIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        return User::with(['roles', 'inviter', 'withdrawals'])
            ->whereIn('id', $userIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->dateJoined, function ($query) {
                $query->whereDate('created_at', $this->dateJoined);
            })
            ->when($this->statusFilter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->statusFilter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->when($this->inviterFilter, function ($query) {
                $query->where('inviters_code', $this->inviterFilter);
            })
            ->when($this->capitalRecoveryFilter, function ($query) {
                // NEW: Capital recovery filter
                $query->whereIn('id', function ($subquery) {
                    $subquery->select('id')->from('users')->whereIn('id', $this->getFilteredUserIdsByCapitalRecovery());
                });
            })
            ->orderBy('date_joined', 'desc')
            ->paginate($this->perPage);
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDateJoined()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingInviterFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="max-w-10xl mx-auto">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('my-team') }}"
                    class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    My Team
                </a>
            </li>
        </ol>
    </nav>
    <div>
        <div>
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">My Team</h2>
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

            <!-- Filters -->
            <div class="mb-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4"> <!-- Changed to 5 columns -->
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <flux:input wire:model.live.debounce.500ms="search" type="text"
                            placeholder="Search by name or email..." data-test="search-input" />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <flux:select wire:model.live="statusFilter" data-test="status-filter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </flux:select>
                    </div>

                    <!-- NEW: Capital Recovery Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capital
                            Status</label>
                        <flux:select wire:model.live="capitalRecoveryFilter" data-test="capital-recovery-filter">
                            <option value="">All Capital Status</option>
                            <option value="recovered">Capital Recovered</option>
                            <option value="recovering">Recovering</option>
                            {{-- <option value="no_investment">No Investment</option> --}}
                        </flux:select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rows Per
                            Page</label>
                        <flux:select wire:model.live="perPage" data-test="per-page-selector">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </flux:select>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <flux:button wire:click="resetFilters" variant="ghost" size="sm" data-test="reset-filters">
                            Reset Filters
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto relative">
                @if ($this->users->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Level</th>
                                <th scope="col"
                                    class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Avatar</th>
                                <th scope="col"
                                    class="md:sticky left-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Name</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Inviter</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Assistant</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Email</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Invested Amount</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Total Withdrawals
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Capital Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Is Verified?</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Age</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Roles</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Riscoin ID</th>

                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Team Size</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Date Joined</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Tenure</th>

                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="md:sticky right-0 z-10 bg-gray-50 dark:bg-gray-700 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($this->users as $user)
                                @php
                                    $currentUserRiscoinId = auth()->user()->riscoin_id;
                                    $teamLevel = $this->getTeamLevel($user->id, $currentUserRiscoinId);
                                @endphp
                                <tr
                                    class="{{ $teamLevel > 1 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                                    <td
                                        class="sticky left-0 z-10 px-6 py-4 whitespace-nowrap text-center
                                        {{ $teamLevel > 1 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-full
                                            @if ($teamLevel === 1) bg-blue-100 text-blue-800
                                            @elseif($teamLevel === 2) bg-green-100 text-green-800
                                            @elseif($teamLevel === 3) bg-yellow-100 text-yellow-800
                                            @else bg-purple-100 text-purple-800 @endif
                                            text-sm font-medium">
                                            {{ $teamLevel }}
                                        </span>
                                    </td>
                                    <td
                                        class="sticky left-0 z-10 px-6 py-4 whitespace-nowrap
                                        {{ $teamLevel > 1 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                                        <a href="{{ route('genealogy.show', $user->riscoin_id) }}">
                                            <div class="flex-shrink-0 h-10 w-10 {{ $teamLevel > 1 ? 'ml-4' : '' }}">
                                                @if ($user->getFirstMediaUrl('avatar'))
                                                    <img class="h-10 w-10 rounded-full object-cover"
                                                        src="{{ $user->getFirstMediaUrl('avatar') }}"
                                                        alt="{{ $user->name }} avatar">
                                                @else
                                                    <div
                                                        class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                        <span
                                                            class="text-gray-600 dark:text-gray-300 font-medium text-sm">
                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td
                                        class="md:sticky left-0 z-10 px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white
                                        {{ $teamLevel > 1 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                                        {{ $user->name }}
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <span>Riscoin ID: <span
                                                    onclick="copyToClipboard('{{ $user->riscoin_id }}')"
                                                    class="border-b border-radius font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ $user->riscoin_id ?? 'N/A' }}</span></span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <span>Bonchat ID: <span
                                                    onclick="copyToClipboard('{{ $user->bonchat_id }}')"
                                                    class="border-b border-radius font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ $user->bonchat_id ?? 'N/A' }}</span></span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <small>Last Logged In: {{ $user->last_login }}</small>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->inviter->name }}
                                        <div class="text-sm text-gray-400">
                                            <span>Riscoin ID: <span
                                                    onclick="copyToClipboard('{{ $user->inviter->riscoin_id }}')"
                                                    class="font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ $user->inviter->riscoin_id ?? 'N/A' }}</span></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        @if ($user->assistant)
                                            {{ $user->assistant->name ?? 'N/A' }}
                                            <div class="text-sm text-gray-400">
                                                <span>Riscoin ID: <span
                                                        onclick="copyToClipboard('{{ $user->assistant?->riscoin_id }}')"
                                                        class="font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ $user->assistant?->riscoin_id ?? 'N/A' }}</span></span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">No Assistant</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                       <span onclick="copyToClipboard('{{ $user->email }}')" class="font-medium text-gray-900 dark:text-gray-300 cursor-pointer">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        ${{ number_format($user->invested_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        ${{ number_format($this->getTotalWithdrawals($user->id), 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $capitalStatus = $this->getCapitalRecoveryStatus($user->id);
                                        @endphp
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if ($capitalStatus['color'] === 'green') bg-green-100 text-green-800
                                                @elseif($capitalStatus['color'] === 'yellow') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                            {{ $capitalStatus['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->email_verified_at)
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Not Verified
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->age }}
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
                                        <div class="flex items-center space-x-1">
                                            <span class="font-medium">{{ $this->getTeamCount($user->id) }}</span>
                                            <span class="text-xs text-gray-400">members</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->date_joined ? \Carbon\Carbon::parse($user->date_joined)->format('M j, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $user->months_and_days_since_joined }}
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td
                                        class="md:sticky right-0 z-10 px-6 py-4 whitespace-nowrap
                                        {{ $teamLevel > 1 ? 'bg-gray-50 dark:bg-gray-700' : 'bg-white dark:bg-gray-800' }}">
                                        <div class="flex space-x-2">
                                            @if (auth()->user()->riscoin_id === $user->inviters_code || auth()->user()->hasRole('admin') || auth()->user()->hasPermissionTo('my-team.add-assister'))
                                                <flux:button wire:click="addAssistant({{ $user->id }})"
                                                    variant="ghost" size="sm"
                                                    data-test="view-user-{{ $user->id }}"
                                                    title="Add Assistant ID">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                </flux:button>
                                            @endif
                                            @can('my-team.view')
                                                <flux:button wire:click="viewUser({{ $user->id }})" variant="ghost"
                                                    size="sm" data-test="view-user-{{ $user->id }}"
                                                    title="View Info">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </flux:button>
                                            @endcan
                                            @can('my-team.edit')
                                                <flux:button wire:click="edit({{ $user->id }})" variant="ghost"
                                                    size="sm" data-test="edit-user-{{ $user->id }}"
                                                    title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </flux:button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-12 px-4">
                        <svg class="h-16 w-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No team members found
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400">
                            @if ($search)
                                No team members match your search "{{ $search }}". Try different keywords.
                            @else
                                No team members are available at the moment.
                            @endif
                        </p>
                    </div>
                @endif
            </div> <!-- Pagination -->
            <div class="mt-6">
                {{ $this->users->links() }}
            </div>

            <!-- Create/Edit User Modal - Right Side Panel -->
            <div x-data="{ open: @entangle('showModal') }" x-show="open" x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
                <!-- Overlay -->
                <div x-show="open" x-transition:enter="ease-in-out duration-500"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                    x-on:click="open = false">
                </div>

                <!-- Modal Panel -->
                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <div x-show="open"
                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="w-screen max-w-2xl">
                        <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                            <!-- Header -->
                            <div
                                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $editMode ? 'Edit User' : 'New User' }}
                                </h2>
                                <button wire:click="closeModal"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto">
                                <div class="px-6 py-4">
                                    <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}"
                                        class="space-y-6">
                                        <!-- Avatar Upload -->
                                        <div class="flex items-center space-x-6">
                                            <div class="flex-shrink-0">
                                                @if ($editMode && $user && $user->getFirstMediaUrl('avatar') && !$avatarToRemove)
                                                    <img class="h-20 w-20 rounded-full object-cover"
                                                        src="{{ $user->getFirstMediaUrl('avatar') }}"
                                                        alt="Current avatar">
                                                @elseif($avatar)
                                                    <img class="h-20 w-20 rounded-full object-cover"
                                                        src="{{ $avatar->temporaryUrl() }}" alt="New avatar">
                                                @else
                                                    <div
                                                        class="h-20 w-20 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                        <span
                                                            class="text-gray-600 dark:text-gray-300 font-medium text-xl">
                                                            {{ $name ? strtoupper(substr($name, 0, 1)) : 'U' }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    Profile Avatar
                                                </label>
                                                <div class="flex space-x-3">
                                                    <div>
                                                        <input type="file" wire:model="avatar" accept="image/*"
                                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                                        @error('avatar')
                                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    @if ($editMode && (($user && $user->getFirstMediaUrl('avatar')) || $avatar))
                                                        <flux:button type="button" wire:click="removeAvatar"
                                                            variant="ghost" size="sm" class="text-red-600">
                                                            Remove Avatar
                                                        </flux:button>
                                                    @endif
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Upload a profile picture. Max 2MB. JPG, PNG, GIF.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-6">
                                            <!-- Name -->
                                            <flux:input wire:model="name" :label="__('Name')" type="text"
                                                required :placeholder="__('Enter full name')" data-test="name-input" />

                                            <!-- Email -->
                                            <flux:input wire:model="email" :label="__('Email')" type="email"
                                                required :placeholder="__('Enter email address')"
                                                data-test="email-input" />

                                            <!-- Riscoin ID -->
                                            <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')"
                                                type="text" :placeholder="__('Enter Riscoin ID')"
                                                data-test="riscoin-id-input" />

                                            <!-- Password -->
                                            <flux:input wire:model="password" :label="__('Password')" type="password"
                                                :required="!$editMode" :placeholder="__('Enter password')"
                                                data-test="password-input" />

                                            <!-- Inviter's Code -->
                                            <flux:input wire:model="inviters_code" :label="__('Inviter\'s Code')"
                                                type="text" :placeholder="__('Enter inviter\'s code')"
                                                data-test="inviters-code-input" />

                                            <!-- Invested Amount -->
                                            <flux:input wire:model="invested_amount" :label="__('Invested Amount')"
                                                type="number" step="0.01" :placeholder="__('0.00')"
                                                prefix="$" data-test="invested-amount-input" />

                                            <!-- Birth Date -->
                                            <flux:input wire:model="birth_date" :label="__('Birth Date')"
                                                type="date" :placeholder="__('Select birth date')"
                                                data-test="birth-date-input" />

                                            <!-- Date Joined -->
                                            <flux:input wire:model="date_joined" :label="__('Date Joined')"
                                                type="date" :placeholder="__('Select date joined')"
                                                data-test="date-joined-input" />

                                            <!-- Roles -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                                                <div class="space-y-2">
                                                    @foreach ($roles as $role)
                                                        <label class="flex items-center">
                                                            <input type="checkbox" wire:model="selectedRoles"
                                                                value="{{ $role->name }}"
                                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            <span
                                                                class="ml-2 text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $role->name }}</span>
                                                        </label>
                                                    @endforeach
                                                    @if ($roles->isEmpty())
                                                        <p class="text-sm text-gray-500">No roles available. Create
                                                            roles first.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Active Status -->
                                            <div class="flex items-center">
                                                <flux:checkbox wire:model="is_active" :label="__('Active User')"
                                                    data-test="is-active-checkbox" />
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div
                                            class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                            <flux:button type="button" wire:click="closeModal"
                                                data-test="cancel-user-button">
                                                Cancel
                                            </flux:button>
                                            <flux:button type="submit" variant="primary"
                                                data-test="submit-user-button">
                                                {{ $editMode ? 'Update' : 'Create' }} User
                                            </flux:button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View User Info Modal - Right Side Panel -->
            <div x-data="{ open: @entangle('showViewModal') }" x-show="open" x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
                <!-- Overlay -->
                <div x-show="open" x-transition:enter="ease-in-out duration-500"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                    x-on:click="open = false">
                </div>

                <!-- Modal Panel -->
                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <div x-show="open"
                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="w-screen max-w-4xl">
                        <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                            <!-- Header -->
                            <div
                                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    User Information & Activity Logs
                                </h2>
                                <button wire:click="closeViewModal"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto">
                                <div class="px-6 py-4">
                                    @if ($selectedUser)
                                        <div class="space-y-6">
                                            <!-- User Information -->
                                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                                <div class="flex items-center space-x-4 mb-4">
                                                    <!-- Avatar in View Modal -->
                                                    <div class="flex-shrink-0">
                                                        @if ($selectedUser->getFirstMediaUrl('avatar'))
                                                            <img class="h-16 w-16 rounded-full object-cover"
                                                                src="{{ $selectedUser->getFirstMediaUrl('avatar') }}"
                                                                alt="{{ $selectedUser->name }} avatar">
                                                        @else
                                                            <div
                                                                class="h-16 w-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                                <span
                                                                    class="text-gray-600 dark:text-gray-300 font-medium text-lg">
                                                                    {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                                            {{ $selectedUser->name }}
                                                        </h3>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            {{ $selectedUser->email }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <label
                                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Riscoin
                                                            ID</label>
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            {{ $selectedUser->riscoin_id ?? 'N/A' }}</p>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Invested
                                                            Amount</label>
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            ${{ number_format($selectedUser->invested_amount, 2) }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Total
                                                            Withdrawals</label>
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            ${{ number_format($this->getTotalWithdrawals($selectedUser->id), 2) }}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Capital
                                                            Status</label>
                                                        <p class="text-sm">
                                                            @php
                                                                $capitalStatus = $this->getCapitalRecoveryStatus(
                                                                    $selectedUser->id,
                                                                );
                                                            @endphp
                                                            <span
                                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                                    @if ($capitalStatus['color'] === 'green') bg-green-100 text-green-800
                                                                    @elseif($capitalStatus['color'] === 'yellow') bg-yellow-100 text-yellow-800
                                                                    @else bg-gray-100 text-gray-800 @endif">
                                                                {{ $capitalStatus['label'] }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-sm font-medium text-gray-500 dark:text-gray-400">Date
                                                            Joined</label>
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            {{ $selectedUser->date_joined->format('M j, Y') }}
                                                        </p>
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
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                                    Withdrawal History
                                                </h3>
                                                <div class="overflow-x-auto">
                                                    <table
                                                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
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
                                                                        {{ Carbon\Carbon::parse($withdrawal->paid_date)->format('M j, Y  ') }}
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
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                                    Recent Activity Logs
                                                </h3>

                                                @if ($activityLogs->count() > 0)
                                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                                        @foreach ($activityLogs as $log)
                                                            <div
                                                                class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                                                <div class="flex justify-between items-start">
                                                                    <div class="flex-1">
                                                                        <p
                                                                            class="text-sm font-medium text-gray-900 dark:text-white">
                                                                            {{ $this->formatActivityDescription($log) }}
                                                                        </p>
                                                                        <p
                                                                            class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                            By:
                                                                            {{ $log->causer->name ?? 'System' }} •
                                                                            {{ $log->created_at->format('M j, Y g:i A') }}
                                                                            •
                                                                            Model:
                                                                            {{ class_basename($log->subject_type) }}
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
                                                            <svg class="mx-auto h-12 w-12" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="1.5"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </div>
                                                        <h3
                                                            class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                                            No activity
                                                            logs found</h3>
                                                        <p class="text-gray-500 dark:text-gray-400">This user
                                                            hasn't performed any
                                                            activities yet.</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Close Button -->
                                        <div
                                            class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                                            <flux:button type="button" wire:click="closeViewModal">
                                                Close
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Assistant User Modal - Right Side Panel -->
            <div x-data="{ open: @entangle('showAssistantModal') }" x-show="open" x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
                <!-- Overlay --> <!-- Overlay -->
                <div x-show="open" x-transition:enter="ease-in-out duration-500"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                    x-on:click="open = false">
                </div>

                <!-- Modal Panel -->
                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <div x-show="open"
                        x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                        class="w-screen max-w-2xl">
                        <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                            <!-- Header -->
                            <div
                                class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Add Assistant User
                                </h2>
                                <div class="text-sm text-gray-500">Target:
                                    {{ $this->assistantTargetUser ? $this->assistantTargetUser->name : 'None' }}</div>
                                <button wire:click="closeAssistantModal"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto">
                                <div class="px-6 py-4">
                                    <div class="w-full">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('Search & Select Assistant') }}
                                        </label>

                                        <input type="text" wire:model.live="assistantSearch"
                                            placeholder="Search users by name, email or riscoin id"
                                            class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-2 border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" />

                                        <div class="mt-3 grid grid-cols-1 gap-2 max-h-48 overflow-y-auto">
                                            @foreach ($this->filteredAssistants as $a)
                                                <button type="button"
                                                    wire:click="selectAssistant({{ $a->id }})"
                                                    class="w-full flex items-center space-x-3 px-3 py-2 rounded-md text-left border border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 {{ $assistantUserId == $a->id ? 'bg-indigo-700 dark:bg-indigo-700' : '' }}">
                                                    <div class="flex-shrink-0 h-8 w-8">
                                                        @if ($a->getFirstMediaUrl('avatar'))
                                                            <img class="h-8 w-8 rounded-full object-cover"
                                                                src="{{ $a->getFirstMediaUrl('avatar') }}"
                                                                alt="{{ $a->name }}">
                                                        @else
                                                            <div
                                                                class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center {{ $assistantUserId == $a->id ? 'ring-2 ring-indigo-300 dark:ring-indigo-500' : '' }}">
                                                                <span
                                                                    class="text-xs font-medium {{ $assistantUserId == $a->id ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">{{ strtoupper(substr($a->name, 0, 1)) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="flex-1">
                                                        <div
                                                            class="text-sm font-medium {{ $assistantUserId == $a->id ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                                            {{ $a->name }}</div>
                                                        <div
                                                            class="text-xs {{ $assistantUserId == $a->id ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                                            {{ $a->email }} • {{ $a->riscoin_id }}</div>
                                                    </div>
                                                    <div>
                                                        @if ($assistantUserId == $a->id)
                                                            <svg class="h-5 w-5 text-green-600" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>

                                        @if (session()->has('message'))
                                            <div
                                                class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                                {{ session('message') }}
                                            </div>
                                        @endif

                                        @if (session()->has('error'))
                                            <div
                                                class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                                {{ session('error') }}
                                            </div>
                                        @endif

                                        <div class="mt-4">
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Apply
                                                Reward Message to Sir Martin</label>
                                            <textarea readonly rows="6" class="w-full p-3 border rounded-md bg-gray-50 dark:bg-gray-700 text-sm"
                                                id="assistantSample">{{ $this->assistantSampleText }}</textarea>
                                            <div class="mt-2 flex justify-end">
                                                <button type="button"
                                                    onclick="(async function(){const t=document.getElementById('assistantSample').value; try{if(navigator.clipboard && navigator.clipboard.writeText){await navigator.clipboard.writeText(t);} else {const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();} alert('Copied to clipboard');}catch(e){try{const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();alert('Copied to clipboard');}catch(err){alert('Copy failed');}}})()"
                                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Copy</button>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Action Buttons -->
                                    <div
                                        class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                                        <button type="button" wire:click="closeAssistantModal"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Cancel
                                        </button>
                                        <button type="button" wire:click="deselectAssistant"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">
                                            Remove Assistant
                                        </button>
                                        <button type="submit" wire:click="addAssistantUser"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Save Assistant
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                if (window.showToast) return;
                const containerId = 'global-toast-container';

                function ensureContainer() {
                    let c = document.getElementById(containerId);
                    if (!c) {
                        c = document.createElement('div');
                        c.id = containerId;
                        c.style =
                            'position:fixed;top:1rem;right:1rem;display:flex;flex-direction:column;gap:0.5rem;z-index:99999;pointer-events:none';
                        document.body.appendChild(c);
                    }
                    return c;
                }
                window.showToast = function(message, type = 'success', duration = 3000) {
                    const c = ensureContainer();
                    const toast = document.createElement('div');
                    toast.className = 'global-toast';
                    toast.style =
                        'pointer-events:auto;min-width:200px;max-width:360px;background:rgba(0,0,0,0.85);color:#fff;padding:12px 14px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;opacity:0;transform:translateX(12px);transition:opacity .18s ease,transform .18s ease';
                    const icon = document.createElement('div');
                    icon.innerHTML = type === 'success' ? '✓' : (type === 'error' ? '⚠' : 'ℹ');
                    icon.style = 'font-weight:700;font-size:14px';
                    const msg = document.createElement('div');
                    msg.style = 'flex:1;font-size:13px;line-height:1.2';
                    msg.textContent = message;
                    const close = document.createElement('button');
                    close.innerHTML = '✕';
                    close.style = 'background:none;border:none;color:inherit;font-size:12px;cursor:pointer';
                    close.onclick = () => {
                        if (toast.parentNode) toast.parentNode.removeChild(toast);
                    };
                    toast.appendChild(icon);
                    toast.appendChild(msg);
                    toast.appendChild(close);
                    c.appendChild(toast);
                    requestAnimationFrame(() => {
                        toast.style.opacity = '1';
                        toast.style.transform = 'translateX(0)';
                    });
                    let removed = false;
                    const timer = setTimeout(() => {
                        if (removed) return;
                        removed = true;
                        toast.style.opacity = '0';
                        toast.style.transform = 'translateX(12px)';
                        setTimeout(() => {
                            if (toast.parentNode) toast.parentNode.removeChild(toast);
                        }, 180);
                    }, duration);
                    toast.addEventListener('mouseenter', () => clearTimeout(timer));
                    toast.addEventListener('mouseleave', () => setTimeout(() => {
                        if (!removed) {
                            removed = true;
                            toast.style.opacity = '0';
                            toast.style.transform = 'translateX(12px)';
                            setTimeout(() => {
                                if (toast.parentNode) toast.parentNode.removeChild(toast);
                            }, 180);
                        }
                    }, 500));
                };
            })();

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('copyToClipboard', async (event) => {
                    try {
                        await navigator.clipboard.writeText(event.message);
                        if (window.showToast) {
                            window.showToast('Welcome message copied to clipboard!', 'success');
                        } else {
                            // Fallback for older browsers
                            const textArea = document.createElement('textarea');
                            textArea.value = event.message;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                            alert('Welcome message copied to clipboard!');
                        }
                    } catch (err) {
                        console.error('Failed to copy:', err);
                        // Fallback copy method
                        const textArea = document.createElement('textarea');
                        textArea.value = event.message;
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            document.execCommand('copy');
                            if (window.showToast) {
                                window.showToast('Welcome message copied to clipboard!', 'success');
                            } else {
                                alert('Welcome message copied to clipboard!');
                            }
                        } catch (err) {
                            alert('Failed to copy to clipboard. Please try again.');
                        }
                        document.body.removeChild(textArea);
                    }
                });
            });

            function copyToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    // For modern browsers
                    navigator.clipboard.writeText(text).then(() => {
                        if (window.showToast) {
                            window.showToast('Copied to clipboard!', 'success');
                        } else {
                            alert('Copied to clipboard!');
                        }
                    }).catch(() => {
                        fallbackCopyToClipboard(text);
                    });
                } else {
                    // Fallback for older browsers
                    fallbackCopyToClipboard(text);
                }
            }

            function fallbackCopyToClipboard(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();

                try {
                    document.execCommand('copy');
                    if (window.showToast) {
                        window.showToast('Copied to clipboard!', 'success');
                    } else {
                        alert('Copied to clipboard!');
                    }
                } catch (err) {
                    alert('Failed to copy text to clipboard');
                }

                document.body.removeChild(textArea);
            }
        </script>
    </div>
