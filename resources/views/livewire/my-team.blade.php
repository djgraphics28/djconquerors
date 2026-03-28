<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\RiscoinLink;
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
    public $managerLevelFilter = '';
    public $perPage = 10;

    // Export / selection
    public array $selectedUsers = [];
    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateJoined' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'capitalRecoveryFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
        'inviterFilter' => ['except' => ''],
        'managerLevelFilter' => ['except' => ''],
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

    // Get Riscoin Link with user's riscoin_id
    public function getRiscoinLinkWithCode($riscoinId)
    {
        // Get the first active Riscoin link
        $activeLink = RiscoinLink::where('is_active', true)->first();

        if (!$activeLink) {
            return null;
        }

        // Append the riscoin_id as a query parameter
        return $activeLink->url . '?code=' . $riscoinId;
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
        $this->reset(['search', 'dateJoined', 'statusFilter', 'capitalRecoveryFilter', 'inviterFilter', 'managerLevelFilter']);
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

        return User::with(['roles', 'inviter', 'withdrawals', 'managerLevel'])
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
            ->when($this->managerLevelFilter, function ($query) {
                $query->whereHas('managerLevel', function ($q) {
                    $q->where('level', $this->managerLevelFilter);
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

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearFilter(string $field)
    {
        $this->reset($field);
        $this->resetPage();
    }

    public function getTeamStatsProperty()
    {
        $currentUser = auth()->user();
        $allMembers = $this->getAllTeamMembers($currentUser->riscoin_id);
        $memberIds = $allMembers->pluck('id');

        $directCount = User::where('inviters_code', $currentUser->riscoin_id)->count();
        $activeCount = $allMembers->where('is_active', true)->count();
        $totalInvested = $allMembers->sum('invested_amount');

        $totalWithdrawn = \App\Models\Withdrawal::whereIn('user_id', $memberIds)
            ->where('status', 'paid')
            ->sum('amount');

        $managersCount = \App\Models\Manager::whereIn('user_id', $memberIds)->count();

        $capitalRecovered = $allMembers->filter(function ($member) {
            $withdrawn = $member->withdrawals ? $member->withdrawals->where('status', 'paid')->sum('amount') : 0;
            return $member->invested_amount > 0 && $withdrawn >= $member->invested_amount;
        })->count();

        return [
            'total'             => $allMembers->count(),
            'direct'            => $directCount,
            'active'            => $activeCount,
            'inactive'          => $allMembers->count() - $activeCount,
            'total_invested'    => $totalInvested,
            'total_withdrawn'   => $totalWithdrawn,
            'managers_count'    => $managersCount,
            'capital_recovered' => $capitalRecovered,
        ];
    }

    public function getDjcInsightProperty(): array
    {
        $currentUser = auth()->user()->load('managerLevel');
        $stats = $this->teamStats;

        $currentLevel = $currentUser->managerLevel?->level ?? 0;
        $directs = $stats['direct'];
        $total   = $stats['total'];

        $levels = [
            1 => ['directs' => 3,  'members' => 0,   'reward' => 15],
            2 => ['directs' => 5,  'members' => 15,  'reward' => 40],
            3 => ['directs' => 6,  'members' => 50,  'reward' => 100],
            4 => ['directs' => 10, 'members' => 100, 'reward' => 250],
            5 => ['directs' => 15, 'members' => 200, 'reward' => 1000],
            6 => ['directs' => 20, 'members' => 500, 'reward' => 3000],
        ];

        $nextLevel      = $currentLevel < 6 ? $currentLevel + 1 : null;
        $nextReq        = $nextLevel ? $levels[$nextLevel] : null;
        $directsNeeded  = $nextReq ? max(0, $nextReq['directs'] - $directs) : 0;
        $membersNeeded  = $nextReq ? max(0, $nextReq['members'] - $total) : 0;

        return [
            'currentLevel'  => $currentLevel,
            'directs'       => $directs,
            'total'         => $total,
            'nextLevel'     => $nextLevel,
            'nextReq'       => $nextReq,
            'directsNeeded' => $directsNeeded,
            'membersNeeded' => $membersNeeded,
            'levels'        => $levels,
        ];
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedUsers = $this->getAllFilteredUserIds();
        } else {
            $this->selectedUsers = [];
        }
    }

    private function getAllFilteredUserIds(): array
    {
        $currentUser = User::find(auth()->user()->id);
        $allTeamMembers = $this->getAllTeamMembers($currentUser->riscoin_id);
        $userIds = $allTeamMembers->pluck('id')->toArray();

        if (empty($userIds)) {
            return [];
        }

        return User::whereIn('id', $userIds)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
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
                $query->whereIn('id', $this->getFilteredUserIdsByCapitalRecovery());
            })
            ->when($this->managerLevelFilter, function ($query) {
                $query->whereHas('managerLevel', function ($q) {
                    $q->where('level', $this->managerLevelFilter);
                });
            })
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    private function getExportData(): array
    {
        $ids = !empty($this->selectedUsers) ? $this->selectedUsers : $this->getAllFilteredUserIds();

        return \App\Models\User::with(['managerLevel'])
            ->whereIn('id', $ids)
            ->get()
            ->map(function ($u) {
                $level = $u->managerLevel?->level;
                return [
                    'name'           => $u->name,
                    'riscoin_id'     => $u->riscoin_id ?? 'N/A',
                    'manager_level'  => $level ? 'Level ' . $level : 'not yet manager',
                    'invested_amount' => $u->invested_amount !== null ? number_format((float) $u->invested_amount, 2) : '0.00',
                    'sort_key'       => $level ?? 0,
                ];
            })
            ->sortByDesc('sort_key')
            ->values()
            ->toArray();
    }

    public function exportExcel()
    {
        $rows = $this->getExportData();
        $filename = 'team-export-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8 support
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Name', 'Riscoin ID', 'Manager Level', 'Capital (USD)']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row['name'], $row['riscoin_id'], $row['manager_level'], $row['invested_amount']]);
            }
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf()
    {
        $rows = $this->getExportData();
        $html = view('exports.team-pdf', ['rows' => $rows])->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'team-export-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}; ?>


<div class="space-y-5" x-data="{ filtersOpen: false }">
    <livewire:components.user-info-modal />

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-700 dark:text-green-300">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="flex items-center gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-sm text-red-700 dark:text-red-300">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Team</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage and monitor your downline network</p>
        </div>
    </div>

    {{-- DJC Insights --}}
    @php $insight = $this->djcInsight; @endphp
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-indigo-950/40 dark:via-gray-900 dark:to-purple-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-800/60 p-5">
        {{-- Decorative blobs --}}
        <div class="absolute -right-12 -top-12 w-56 h-56 bg-indigo-200/25 dark:bg-indigo-700/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-8 -bottom-8 w-40 h-40 bg-purple-200/25 dark:bg-purple-700/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative">
            {{-- Header row --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">DJC Insights</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Your personalized manager progress</p>
                </div>
                @if ($insight['currentLevel'] > 0)
                    <div class="ml-auto">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 dark:bg-indigo-500 text-white shadow-sm">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Level {{ $insight['currentLevel'] }} Manager
                        </span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Message + Progress --}}
                <div class="lg:col-span-2 space-y-3">

                    {{-- Personalized message --}}
                    <div class="bg-white/80 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white dark:border-gray-700/50">
                        @if ($insight['currentLevel'] === 0)
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                You are <strong class="text-gray-900 dark:text-white">not yet a Manager</strong>. You currently have <strong class="text-indigo-600 dark:text-indigo-400">{{ $insight['directs'] }} direct invite{{ $insight['directs'] != 1 ? 's' : '' }}</strong>.
                                @if ($insight['directsNeeded'] > 0)
                                    Invite <strong class="text-indigo-600 dark:text-indigo-400">{{ $insight['directsNeeded'] }} more member{{ $insight['directsNeeded'] != 1 ? 's' : '' }}</strong> directly to unlock <strong>Level 1 Manager</strong> status and start earning rewards! 💪
                                @endif
                            </p>
                        @elseif ($insight['currentLevel'] === 6)
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                🎉 <strong class="text-indigo-600 dark:text-indigo-400">Congratulations!</strong> You have reached the <strong>highest rank — Level 6 Manager!</strong> You earn a minimum of <strong class="text-emerald-600 dark:text-emerald-400">$3,000 every 10 days</strong>. Keep inspiring and growing your network!
                            </p>
                        @else
                            <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                You are a <strong class="text-indigo-600 dark:text-indigo-400">Level {{ $insight['currentLevel'] }} Manager</strong>.
                                @if ($insight['directsNeeded'] === 0 && $insight['membersNeeded'] === 0)
                                    You already qualify for <strong class="text-emerald-600 dark:text-emerald-400">Level {{ $insight['nextLevel'] }}</strong> — congratulations, you're ready for the next promotion! 🎉
                                @else
                                    You only need
                                    @if ($insight['directsNeeded'] > 0)
                                        <strong class="text-indigo-600 dark:text-indigo-400">{{ $insight['directsNeeded'] }} more direct{{ $insight['directsNeeded'] != 1 ? 's' : '' }}</strong>
                                    @endif
                                    @if ($insight['directsNeeded'] > 0 && $insight['membersNeeded'] > 0) and @endif
                                    @if ($insight['membersNeeded'] > 0)
                                        <strong class="text-purple-600 dark:text-purple-400">{{ $insight['membersNeeded'] }} more total member{{ $insight['membersNeeded'] != 1 ? 's' : '' }}</strong>
                                    @endif
                                    to become a <strong>Level {{ $insight['nextLevel'] }} Manager</strong>. Keep it up! 🚀
                                @endif
                            </p>
                        @endif
                    </div>

                    {{-- Progress bars toward next level --}}
                    @if ($insight['currentLevel'] < 6)
                        @php
                            $nextReq = $insight['nextReq'];
                            $directPct = $nextReq['directs'] > 0
                                ? min(100, (int) round(($insight['directs'] / $nextReq['directs']) * 100))
                                : 100;
                            $memberPct = $nextReq['members'] > 0
                                ? min(100, (int) round(($insight['total'] / $nextReq['members']) * 100))
                                : 100;
                        @endphp
                        <div class="bg-white/80 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl px-4 py-3.5 border border-white dark:border-gray-700/50 space-y-3">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Progress to Level {{ $insight['nextLevel'] }}</p>

                            {{-- Directs --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Direct Invites</span>
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $insight['directs'] }} / {{ $nextReq['directs'] }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-700" style="width: {{ $directPct }}%"></div>
                                </div>
                            </div>

                            {{-- Total members (only when required) --}}
                            @if ($nextReq['members'] > 0)
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Team Members</span>
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $insight['total'] }} / {{ $nextReq['members'] }}</span>
                                    </div>
                                    <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-700" style="width: {{ $memberPct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                {{-- Right: Rewards table --}}
                <div class="bg-white/80 dark:bg-gray-800/70 backdrop-blur-sm rounded-xl border border-white dark:border-gray-700/50 overflow-hidden">
                    <div class="px-3.5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <p class="text-xs font-bold text-white uppercase tracking-wide">Manager Rewards</p>
                        <p class="text-[11px] text-indigo-200 mt-0.5">Minimum payout every 10 days</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($insight['levels'] as $lvl => $req)
                            @php
                                $isCurrentLvl = $insight['currentLevel'] === $lvl;
                                $isDone = $insight['currentLevel'] > $lvl;
                            @endphp
                            <div class="flex items-center justify-between px-3.5 py-2 {{ $isCurrentLvl ? 'bg-indigo-50 dark:bg-indigo-900/30' : '' }}">
                                <div class="flex items-center gap-2">
                                    @if ($isCurrentLvl)
                                        <div class="w-4 h-4 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @elseif ($isDone)
                                        <div class="w-4 h-4 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    @else
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex-shrink-0"></div>
                                    @endif
                                    <span class="text-xs font-semibold
                                        {{ $isCurrentLvl ? 'text-indigo-700 dark:text-indigo-300' : ($isDone ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400') }}">
                                        Level {{ $lvl }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold
                                        {{ $isCurrentLvl ? 'text-indigo-700 dark:text-indigo-300' : ($isDone ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400') }}">
                                        ${{ number_format($req['reward']) }}
                                    </span>
                                    <span class="block text-[10px] text-gray-400">/ 10 days</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Analytics Cards --}}
    @php $stats = $this->teamStats; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">

        {{-- Total Members --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total</span>
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $stats['direct'] }} direct</div>
        </div>

        {{-- Active --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Active</span>
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $stats['inactive'] }} inactive</div>
        </div>

        {{-- Managers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Managers</span>
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['managers_count']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">in network</div>
        </div>

        {{-- Capital Recovered --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Recovered</span>
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['capital_recovered']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">capital recovered</div>
        </div>

        {{-- Total Invested --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Invested</span>
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_invested'], 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">team total</div>
        </div>

        {{-- Total Withdrawn --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Withdrawn</span>
                <div class="w-8 h-8 bg-rose-100 dark:bg-rose-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_withdrawn'], 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">paid withdrawals</div>
        </div>

    </div>

    {{-- Filters Panel --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">

        {{-- Filter toggle header --}}
        <div class="flex items-center justify-between px-4 py-3">
            <button @click="filtersOpen = !filtersOpen"
                class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                <svg class="w-4 h-4 transition-transform" :class="filtersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
                @php
                    $activeFiltersCount = collect([$search, $statusFilter, $capitalRecoveryFilter, $inviterFilter, $managerLevelFilter, $dateJoined])->filter()->count();
                @endphp
                @if ($activeFiltersCount > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full">{{ $activeFiltersCount }}</span>
                @endif
            </button>

            {{-- Active filter chips --}}
            <div class="flex items-center flex-wrap gap-1.5">
                @if ($search)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-medium">
                        "{{ Str::limit($search, 15) }}"
                        <button wire:click="clearFilter('search')" class="ml-0.5 hover:text-blue-900 dark:hover:text-blue-100 font-bold">×</button>
                    </span>
                @endif
                @if ($statusFilter)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-medium">
                        {{ ucfirst($statusFilter) }}
                        <button wire:click="clearFilter('statusFilter')" class="ml-0.5 hover:text-green-900 dark:hover:text-green-100 font-bold">×</button>
                    </span>
                @endif
                @if ($managerLevelFilter)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 font-medium">
                        Mgr Lvl {{ $managerLevelFilter }}
                        <button wire:click="clearFilter('managerLevelFilter')" class="ml-0.5 hover:text-purple-900 dark:hover:text-purple-100 font-bold">×</button>
                    </span>
                @endif
                @if ($capitalRecoveryFilter)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 font-medium">
                        {{ ucfirst($capitalRecoveryFilter) }}
                        <button wire:click="clearFilter('capitalRecoveryFilter')" class="ml-0.5 hover:text-yellow-900 dark:hover:text-yellow-100 font-bold">×</button>
                    </span>
                @endif
                @if ($activeFiltersCount > 0)
                    <button wire:click="resetFilters" class="text-xs text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors px-1">
                        Clear all
                    </button>
                @endif
            </div>
        </div>

        {{-- Collapsible filter body --}}
        <div x-show="filtersOpen" x-collapse
            class="border-t border-gray-100 dark:border-gray-700 px-4 py-4">
            <form wire:submit.prevent="applyFilters">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="col-span-2 lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <flux:input wire:model="search" type="text"
                            placeholder="Name, email, riscoin ID…" data-test="search-input" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <flux:select wire:model="statusFilter" data-test="status-filter">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Capital</label>
                        <flux:select wire:model="capitalRecoveryFilter" data-test="capital-recovery-filter">
                            <option value="">All Capital</option>
                            <option value="recovered">Recovered</option>
                            <option value="recovering">Recovering</option>
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Manager Level</label>
                        <flux:select wire:model="managerLevelFilter" data-test="manager-level-filter">
                            <option value="">All Levels</option>
                            @for ($lvl = 1; $lvl <= 6; $lvl++)
                                <option value="{{ $lvl }}">Level {{ $lvl }}</option>
                            @endfor
                        </flux:select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Per Page</label>
                        <flux:select wire:model="perPage" data-test="per-page-selector">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </flux:select>
                    </div>
                </div>
                <div class="flex justify-end mt-3">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Export Toolbar --}}
        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                @if ($selectAll)
                    <span class="font-semibold text-blue-600 dark:text-blue-400">All {{ count($selectedUsers) }}</span> filtered members selected
                    &nbsp;&bull;&nbsp;
                    <button type="button" wire:click="$set('selectAll', false); $set('selectedUsers', [])"
                        class="text-red-500 hover:underline">Clear selection</button>
                @elseif (count($selectedUsers) > 0)
                    <span class="font-semibold text-blue-600 dark:text-blue-400">{{ count($selectedUsers) }}</span> selected
                    &nbsp;&bull;&nbsp;
                    <button type="button" wire:click="$set('selectedUsers', [])"
                        class="text-red-500 hover:underline">Clear</button>
                @else
                    Use the checkbox to select rows, or export all
                @endif
            </span>
            <div class="flex items-center gap-2">
                <button wire:click="exportExcel"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </button>
                <button wire:click="exportPdf"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Export PDF
                </button>
            </div>
        </div>
        @if ($this->users->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700">
                            <th class="px-3 py-2.5 w-8">
                                <input type="checkbox" wire:model.live="selectAll" wire:click="toggleSelectAll"
                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-400 cursor-pointer">
                            </th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Lvl</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Member</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Network</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Financials</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Status</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap">Joined</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide whitespace-nowrap sticky right-0 bg-gray-50 dark:bg-gray-700/60 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($this->users as $user)
                            @php
                                $currentUserRiscoinId = auth()->user()->riscoin_id;
                                $teamLevel = $this->getTeamLevel($user->id, $currentUserRiscoinId);
                                $capitalStatus = $this->getCapitalRecoveryStatus($user->id);
                                $teamLevelColors = [
                                    1 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    2 => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    3 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                ];
                                $tlColor = $teamLevelColors[$teamLevel] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                $mgrLevelColors = [
                                    1 => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    2 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                                    3 => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                    4 => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    5 => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                                    6 => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                                ];
                            @endphp
                            <tr class="group/row hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors {{ $teamLevel > 1 ? 'bg-gray-50/40 dark:bg-gray-800/50' : '' }}">

                                {{-- Select --}}
                                <td class="px-3 py-3 whitespace-nowrap w-8">
                                    <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user->id }}"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-400 cursor-pointer">
                                </td>

                                {{-- Manager Level --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    @if ($user->managerLevel)
                                        @php $mgrClass = $mgrLevelColors[$user->managerLevel->level] ?? 'bg-gray-100 text-gray-700'; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $mgrClass }}">
                                            L{{ $user->managerLevel->level }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">—</span>
                                    @endif
                                </td>

                                {{-- Member --}}
                                <td class="px-3 py-3 min-w-[210px]">
                                    <div class="flex items-center gap-2.5">
                                        <a href="{{ route('genealogy.show', $user->riscoin_id) }}" class="flex-shrink-0">
                                            @if ($user->getFirstMediaUrl('avatar'))
                                                <img class="h-9 w-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 shadow-sm"
                                                    src="{{ $user->getFirstMediaUrl('avatar') }}" alt="{{ $user->name }}">
                                            @else
                                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center ring-2 ring-white dark:ring-gray-700 shadow-sm">
                                                    <span class="text-white font-bold text-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white text-sm leading-tight truncate">{{ $user->name }}</div>
                                            <div class="flex items-center flex-wrap gap-x-1.5 gap-y-0.5 mt-0.5">
                                                <button onclick="copyToClipboard('{{ $user->riscoin_id }}')"
                                                    class="text-xs text-blue-500 dark:text-blue-400 hover:underline font-mono" title="Copy Riscoin ID">{{ $user->riscoin_id ?? '—' }}</button>
                                                @if ($user->bonchat_id)
                                                    <span class="text-gray-300 dark:text-gray-600 text-xs">·</span>
                                                    <button onclick="copyToClipboard('{{ $user->bonchat_id }}')"
                                                        class="text-xs text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 font-mono" title="Copy Bonchat ID">{{ $user->bonchat_id }}</button>
                                                @endif
                                            </div>
                                            @if ($user->email)
                                                <button onclick="copyToClipboard('{{ $user->email }}')"
                                                    class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 truncate max-w-[180px] block mt-0.5 text-left" title="Copy Email">{{ $user->email }}</button>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Network --}}
                                <td class="px-3 py-3 min-w-[150px]">
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $user->inviter->name ?? '—' }}</div>
                                        <button onclick="copyToClipboard('{{ $user->inviter->riscoin_id ?? '' }}')"
                                            class="text-blue-500 dark:text-blue-400 hover:underline font-mono">{{ $user->inviter->riscoin_id ?? '—' }}</button>
                                    </div>
                                    <div class="flex items-center gap-1 mt-1.5">
                                        <svg class="w-3 h-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->getTeamCount($user->id) }} members</span>
                                    </div>
                                    @if ($user->assistant)
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span class="text-xs text-indigo-500 dark:text-indigo-400 truncate">{{ $user->assistant->name }}</span>
                                        </div>
                                    @endif
                                </td>

                                {{-- Financials --}}
                                <td class="px-3 py-3 min-w-[155px]">
                                    @php $totalWithdrawals = $this->getTotalWithdrawals($user->id); @endphp
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs text-gray-400">Invested</span>
                                            <span class="text-xs font-semibold text-gray-900 dark:text-white tabular-nums">${{ number_format($user->invested_amount, 2) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs text-gray-400">Withdrawn</span>
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 tabular-nums">${{ number_format($totalWithdrawals, 2) }}</span>
                                        </div>
                                        <div class="pt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                {{ $capitalStatus['color'] === 'green' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($capitalStatus['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400') }}">
                                                {{ $capitalStatus['label'] }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="space-y-1">
                                        @if (method_exists($user, 'trashed') && $user->trashed())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Trashed</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium
                                                {{ $user->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        @endif
                                        @if ($user->email_verified_at)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Verified
                                            </span>
                                        @endif
                                        <div class="flex flex-wrap gap-0.5 mt-0.5">
                                            @foreach ($user->roles as $role)
                                                <span class="px-1.5 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400 rounded capitalize">{{ $role->name }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>

                                {{-- Joined --}}
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $user->date_joined ? \Carbon\Carbon::parse($user->date_joined)->format('M j, Y') : '—' }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $user->months_and_days_since_joined }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $user->age }}</div>
                                </td>

                                {{-- Actions dropdown --}}
                                <td class="px-2 py-3 whitespace-nowrap sticky right-0 bg-white dark:bg-gray-800 border-l border-gray-100 dark:border-gray-700/50 w-10">
                                    <div class="relative flex items-center justify-center opacity-0 group-hover/row:opacity-100 focus-within:opacity-100 transition-opacity"
                                         x-data="{ open: false }"
                                         @keydown.escape.window="open = false">
                                        <button @click.stop="open = !open"
                                                type="button"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                                :aria-expanded="open"
                                                title="Actions">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>

                                        <div x-show="open"
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             @click.outside="open = false"
                                             class="absolute z-50 top-full right-0 mt-1 w-44 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 text-sm">

                                            @if (auth()->user()->riscoin_id === $user->inviters_code || auth()->user()->hasRole('admin') || auth()->user()->hasPermissionTo('my-team.add-assister'))
                                                <button wire:click="addAssistant({{ $user->id }})" @click="open = false"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    Add Assistant
                                                </button>
                                            @endif

                                            @can('my-team.view')
                                                <button wire:click="viewUser({{ $user->id }})" @click="open = false"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-gray-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                                                        data-test="view-user-{{ $user->id }}">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    View Info
                                                </button>
                                            @endcan

                                            @can('my-team.edit')
                                                <button wire:click="edit({{ $user->id }})" @click="open = false"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-gray-700 dark:text-gray-200 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 hover:text-yellow-700 dark:hover:text-yellow-300 transition-colors"
                                                        data-test="edit-user-{{ $user->id }}">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    Edit
                                                </button>
                                            @endcan

                                            @php $riscoinLink = $this->getRiscoinLinkWithCode($user->riscoin_id); @endphp
                                            @if ($riscoinLink)
                                                <button data-referral-link="{{ $riscoinLink }}"
                                                        @click="copyToClipboard($el.getAttribute('data-referral-link')); open = false"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-700 dark:hover:text-green-300 transition-colors"
                                                        data-test="copy-link-{{ $user->id }}">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                    Copy Referral Link
                                                </button>
                                            @endif

                                            @can('admin')
                                                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                                <button onclick="Livewire.dispatch('loadUserData', { userId: {{ $user->id }} })" @click="open = false"
                                                        class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                                        data-test="info-user-{{ $user->id }}">
                                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    User Details
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $this->users->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-center px-4">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No team members found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                    @if ($search || $statusFilter || $capitalRecoveryFilter || $managerLevelFilter)
                        No members match the current filters.
                        <button wire:click="resetFilters" class="text-blue-500 hover:underline">Clear filters</button>
                    @else
                        Your team is empty. Share your referral link to grow your network.
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- ─────────────────────────── CREATE / EDIT MODAL ─────────────────────────── --}}
    <div x-data="{ open: @entangle('showModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-on:click="open = false">
        </div>
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div x-show="open"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-2xl">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editMode ? 'Edit Member' : 'New Member' }}</h2>
                        <button wire:click="closeModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-6 py-5">
                            <form wire:submit.prevent="{{ $editMode ? 'update' : 'create' }}" class="space-y-5">
                                <div class="flex items-center space-x-5">
                                    <div class="flex-shrink-0">
                                        @if ($editMode && $user && $user->getFirstMediaUrl('avatar') && !$avatarToRemove)
                                            <img class="h-20 w-20 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700"
                                                src="{{ $user->getFirstMediaUrl('avatar') }}" alt="Current avatar">
                                        @elseif ($avatar)
                                            <img class="h-20 w-20 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700"
                                                src="{{ $avatar->temporaryUrl() }}" alt="New avatar">
                                        @else
                                            <div class="h-20 w-20 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center ring-4 ring-gray-100 dark:ring-gray-700">
                                                <span class="text-white font-bold text-2xl">{{ $name ? strtoupper(substr($name, 0, 1)) : 'U' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Profile Avatar</label>
                                        <input type="file" wire:model="avatar" accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                                        @error('avatar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        @if ($editMode && (($user && $user->getFirstMediaUrl('avatar')) || $avatar))
                                            <button type="button" wire:click="removeAvatar" class="mt-1 text-xs text-red-500 hover:text-red-700">Remove avatar</button>
                                        @endif
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4">
                                    <flux:input wire:model="name" :label="__('Name')" type="text" required placeholder="Full name" data-test="name-input" />
                                    <flux:input wire:model="email" :label="__('Email')" type="email" required placeholder="email@example.com" data-test="email-input" />
                                    <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')" type="text" placeholder="Riscoin ID" data-test="riscoin-id-input" />
                                    <flux:input wire:model="password" :label="__('Password')" type="password" :required="!$editMode" placeholder="Password" data-test="password-input" />
                                    <flux:input wire:model="inviters_code" :label="__('Inviter\'s Code')" type="text" placeholder="Inviter's code" data-test="inviters-code-input" />
                                    <flux:input wire:model="invested_amount" :label="__('Invested Amount')" type="number" step="0.01" placeholder="0.00" prefix="$" data-test="invested-amount-input" />
                                    <div class="grid grid-cols-2 gap-4">
                                        <flux:input wire:model="birth_date" :label="__('Birth Date')" type="date" data-test="birth-date-input" />
                                        <flux:input wire:model="date_joined" :label="__('Date Joined')" type="date" data-test="date-joined-input" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roles</label>
                                        <div class="flex flex-wrap gap-3">
                                            @foreach ($roles as $role)
                                                <label class="flex items-center gap-1.5">
                                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">{{ $role->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <flux:checkbox wire:model="is_active" :label="__('Active User')" data-test="is-active-checkbox" />
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <flux:button type="button" wire:click="closeModal" data-test="cancel-user-button">Cancel</flux:button>
                                    <flux:button type="submit" variant="primary" data-test="submit-user-button">{{ $editMode ? 'Update' : 'Create' }} Member</flux:button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────── VIEW MODAL ─────────────────────────── --}}
    <div x-data="{ open: @entangle('showViewModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-on:click="open = false">
        </div>
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div x-show="open"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-4xl">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Member Details</h2>
                        <button wire:click="closeViewModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-6 py-5">
                            @if ($selectedUser)
                                <div class="space-y-5">
                                    {{-- User Info Card --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                                        <div class="flex items-center gap-4 mb-4">
                                            @if ($selectedUser->getFirstMediaUrl('avatar'))
                                                <img class="h-14 w-14 rounded-full object-cover ring-4 ring-white dark:ring-gray-700"
                                                    src="{{ $selectedUser->getFirstMediaUrl('avatar') }}" alt="{{ $selectedUser->name }}">
                                            @else
                                                <div class="h-14 w-14 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center ring-4 ring-white dark:ring-gray-700">
                                                    <span class="text-white font-bold text-lg">{{ strtoupper(substr($selectedUser->name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedUser->name }}</h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedUser->email }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                            <div><span class="text-xs text-gray-400 block">Riscoin ID</span><span class="font-medium text-gray-800 dark:text-gray-200">{{ $selectedUser->riscoin_id ?? '—' }}</span></div>
                                            <div><span class="text-xs text-gray-400 block">Invested</span><span class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($selectedUser->invested_amount, 2) }}</span></div>
                                            <div><span class="text-xs text-gray-400 block">Withdrawn</span><span class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($this->getTotalWithdrawals($selectedUser->id), 2) }}</span></div>
                                            <div>
                                                <span class="text-xs text-gray-400 block">Capital Status</span>
                                                @php $cs = $this->getCapitalRecoveryStatus($selectedUser->id); @endphp
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                                    {{ $cs['color'] === 'green' ? 'bg-green-100 text-green-700' : ($cs['color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">{{ $cs['label'] }}</span>
                                            </div>
                                            <div><span class="text-xs text-gray-400 block">Date Joined</span><span class="font-medium text-gray-800 dark:text-gray-200">{{ $selectedUser->date_joined?->format('M j, Y') }}</span></div>
                                            <div>
                                                <span class="text-xs text-gray-400 block">Status</span>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium {{ $selectedUser->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $selectedUser->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                                    {{ $selectedUser->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-xs text-gray-400 block">Roles</span>
                                                <div class="flex flex-wrap gap-1 mt-0.5">
                                                    @foreach ($selectedUser->roles as $role)
                                                        <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded capitalize">{{ $role->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Withdrawals --}}
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Withdrawal History</h4>
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                    <tr>
                                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Tx ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                    @forelse ($selectedUser->withdrawals ?? [] as $withdrawal)
                                                        <tr>
                                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($withdrawal->paid_date)->format('M j, Y') }}</td>
                                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">${{ number_format($withdrawal->amount, 2) }}</td>
                                                            <td class="px-4 py-3">
                                                                <span class="px-2 py-0.5 rounded text-xs font-medium
                                                                    {{ $withdrawal->status === 'completed' ? 'bg-green-100 text-green-700' : ($withdrawal->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                                                    {{ ucfirst($withdrawal->status) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $withdrawal->transaction_id ?? '—' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400">No withdrawal history</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- Activity Logs --}}
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recent Activity</h4>
                                        @if ($activityLogs->count() > 0)
                                            <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                                                @foreach ($activityLogs as $log)
                                                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3">
                                                        <div class="flex justify-between items-start gap-2">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $this->formatActivityDescription($log) }}</p>
                                                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('M j, Y') }}</span>
                                                        </div>
                                                        <p class="text-xs text-gray-500 mt-0.5">By: {{ $log->causer->name ?? 'System' }}</p>
                                                        @if ($log->description === 'updated')
                                                            @php $changedFields = $this->getChangedFields($log); @endphp
                                                            @if (count($changedFields) > 0)
                                                                <ul class="mt-2 space-y-0.5">
                                                                    @foreach ($changedFields as $field => $changes)
                                                                        <li class="text-xs text-gray-500">
                                                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $field)) }}:</span>
                                                                            <span class="line-through text-red-500">{{ $changes['from'] ?? 'Empty' }}</span>
                                                                            <span class="text-gray-400 mx-1">→</span>
                                                                            <span class="text-green-600 dark:text-green-400">{{ $changes['to'] ?? 'Empty' }}</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-8 text-gray-400">
                                                <svg class="mx-auto h-10 w-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <p class="text-sm">No activity logs yet</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex justify-end pt-5 border-t border-gray-200 dark:border-gray-700 mt-5">
                                    <flux:button type="button" wire:click="closeViewModal">Close</flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────── ASSISTANT MODAL ─────────────────────────── --}}
    <div x-data="{ open: @entangle('showAssistantModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <div x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-on:click="open = false">
        </div>
        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div x-show="open"
                x-transition:enter="transform transition ease-in-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-300"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-2xl">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-2xl">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assign Assistant</h2>
                            @if ($this->assistantTargetUser)
                                <p class="text-sm text-gray-500 dark:text-gray-400">For: {{ $this->assistantTargetUser->name }}</p>
                            @endif
                        </div>
                        <button wire:click="closeAssistantModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Search & Select Assistant</label>
                            <input type="text" wire:model.live="assistantSearch"
                                placeholder="Search by name, email or riscoin ID…"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                            <div class="mt-2 space-y-1 max-h-52 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 p-1">
                                @foreach ($this->filteredAssistants as $a)
                                    <button type="button" wire:click="selectAssistant({{ $a->id }})"
                                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-colors
                                            {{ $assistantUserId == $a->id ? 'bg-indigo-600 text-white' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                        @if ($a->getFirstMediaUrl('avatar'))
                                            <img class="h-8 w-8 rounded-full object-cover flex-shrink-0" src="{{ $a->getFirstMediaUrl('avatar') }}" alt="{{ $a->name }}">
                                        @else
                                            <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center flex-shrink-0">
                                                <span class="text-white text-xs font-bold">{{ strtoupper(substr($a->name, 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium {{ $assistantUserId == $a->id ? 'text-white' : 'text-gray-900 dark:text-white' }}">{{ $a->name }}</div>
                                            <div class="text-xs {{ $assistantUserId == $a->id ? 'text-indigo-200' : 'text-gray-500 dark:text-gray-400' }}">{{ $a->email }} · {{ $a->riscoin_id }}</div>
                                        </div>
                                        @if ($assistantUserId == $a->id)
                                            <svg class="h-4 w-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Reward Message (Sir Martin)</label>
                            <textarea readonly rows="6" id="assistantSample"
                                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 resize-none">{{ $this->assistantSampleText }}</textarea>
                            <div class="flex justify-end mt-1.5">
                                <button type="button"
                                    onclick="(async function(){const t=document.getElementById('assistantSample').value;try{await navigator.clipboard.writeText(t);if(window.showToast)window.showToast('Copied!','success');}catch(e){const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();if(window.showToast)window.showToast('Copied!','success');}})()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    Copy Message
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="closeAssistantModal"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancel
                            </button>
                            <button type="button" wire:click="deselectAssistant"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Remove Assistant
                            </button>
                            <button type="button" wire:click="addAssistantUser"
                                class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                Save Assistant
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            if (window.showToast) return;
            const containerId = 'global-toast-container';
            function ensureContainer() {
                let c = document.getElementById(containerId);
                if (!c) {
                    c = document.createElement('div');
                    c.id = containerId;
                    c.style = 'position:fixed;top:1rem;right:1rem;display:flex;flex-direction:column;gap:0.5rem;z-index:99999;pointer-events:none';
                    document.body.appendChild(c);
                }
                return c;
            }
            window.showToast = function (message, type = 'success', duration = 3000) {
                const c = ensureContainer();
                const toast = document.createElement('div');
                toast.style = 'pointer-events:auto;min-width:200px;max-width:360px;background:rgba(15,15,15,0.9);color:#fff;padding:10px 14px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.2);display:flex;align-items:center;gap:10px;opacity:0;transform:translateX(12px);transition:opacity .18s ease,transform .18s ease';
                const icon = document.createElement('div');
                icon.innerHTML = type === 'success' ? '✓' : (type === 'error' ? '⚠' : 'ℹ');
                icon.style = 'font-weight:700;font-size:14px;color:' + (type === 'success' ? '#4ade80' : (type === 'error' ? '#f87171' : '#60a5fa'));
                const msg = document.createElement('div');
                msg.style = 'flex:1;font-size:13px;line-height:1.3';
                msg.textContent = message;
                const close = document.createElement('button');
                close.innerHTML = '✕';
                close.style = 'background:none;border:none;color:rgba(255,255,255,0.5);font-size:12px;cursor:pointer;padding:0;line-height:1';
                close.onclick = () => toast.parentNode && toast.parentNode.removeChild(toast);
                toast.appendChild(icon); toast.appendChild(msg); toast.appendChild(close);
                c.appendChild(toast);
                requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; });
                setTimeout(() => {
                    toast.style.opacity = '0'; toast.style.transform = 'translateX(12px)';
                    setTimeout(() => toast.parentNode && toast.parentNode.removeChild(toast), 200);
                }, duration);
            };
        })();

        document.addEventListener('livewire:initialized', () => {
            Livewire.on('copyToClipboard', async (event) => {
                try {
                    await navigator.clipboard.writeText(event.message);
                    window.showToast && window.showToast('Copied to clipboard!', 'success');
                } catch (err) {
                    fallbackCopyToClipboard(event.message);
                }
            });
        });

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    window.showToast && window.showToast('Copied!', 'success');
                }).catch(() => fallbackCopyToClipboard(text));
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-999999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                window.showToast && window.showToast('Copied!', 'success');
            } catch (err) {
                alert('Failed to copy to clipboard');
            }
            document.body.removeChild(ta);
        }
    </script>
</div>
