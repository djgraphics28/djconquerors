<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component {
    public $user;
    public $currentNode;
    public $riscoinId;
    public $totalTeamMembers = 0;
    public $totalTeamInvestment = 0;
    public $directMembersCount = 0;

    public function mount($riscoinId = null)
    {
        if ($riscoinId) {
            // Viewing a specific user's genealogy
            $this->currentNode = User::where('riscoin_id', $riscoinId)
                ->with(['invites' => function($query) {
                    $query->withCount('invites');
                }])
                ->firstOrFail();
        } else {
            // Viewing current user's genealogy
            $this->currentNode = Auth::user();
            $this->currentNode->load(['invites' => function($query) {
                $query->withCount('invites');
            }]);
        }

        $this->riscoinId = $riscoinId;
        $this->calculateStatistics();
    }

    private function calculateStatistics()
    {
        // Direct members count
        $this->directMembersCount = $this->currentNode->invites->count();

        // Calculate total team members recursively
        $this->totalTeamMembers = 1; // Start with 1 to include current node
        $this->totalTeamInvestment = $this->currentNode->invested_amount;

        $currentLevel = $this->currentNode->invites;

        while ($currentLevel->isNotEmpty()) {
            $this->totalTeamMembers += $currentLevel->count();
            $this->totalTeamInvestment += $currentLevel->sum('invested_amount');

            $nextLevel = collect();
            foreach ($currentLevel as $user) {
                $nextLevel = $nextLevel->merge($user->invites);
            }
            $currentLevel = $nextLevel;
        }
    }
}; ?>

<div class="p-6">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li>
                <a href="{{ route('genealogy') }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                    My Genealogy
                </a>
            </li>
            @if($riscoinId)
            <li>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $currentNode->riscoin_id }}</span>
            </li>
            @endif
        </ol>
    </nav>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Direct Members</h3>
            <p class="text-2xl font-bold dark:text-white">{{ $directMembersCount }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Total Team Members</h3>
            <p class="text-2xl font-bold dark:text-white">{{ $totalTeamMembers }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Team Investment</h3>
            <p class="text-2xl font-bold dark:text-white">${{ number_format($totalTeamInvestment, 2) }}</p>
        </div>
    </div>

    <!-- Genealogy Tree -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold dark:text-white">
                @if($riscoinId)
                    Genealogy of {{ $currentNode->name }} ({{ $currentNode->riscoin_id }})
                @else
                    My Genealogy Tree
                @endif
            </h2>
        </div>

        @if($currentNode->invites->count() > 0)
            <div class="genealogy-tree flex justify-center">
                <x-genealogy-node :node="$currentNode" :level="0" :showChildren="true" />
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No direct members yet.</p>
                @if($riscoinId)
                    <a href="{{ route('genealogy') }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mt-2 inline-block">
                        ← Back to my genealogy
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
