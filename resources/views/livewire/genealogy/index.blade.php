<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component {
    public $user;
    public $currentNode;
    public $superiorNode; // Add superior node
    public $riscoinId;
    public $totalTeamMembers = 0;
    public $totalTeamInvestment = 0;
    public $directMembersCount = 0;

    public function mount($riscoinId = null)
    {
        if ($riscoinId) {
            // Viewing a specific user's genealogy
            $this->currentNode = User::where('riscoin_id', $riscoinId)
                ->with([
                    'invites' => function ($query) {
                        $query->withCount('invites');
                    },
                ])
                ->firstOrFail();
        } else {
            // Viewing current user's genealogy
            $this->currentNode = Auth::user();
            $this->currentNode->load([
                'invites' => function ($query) {
                    $query->withCount('invites');
                },
            ]);
        }

        // Fetch superior if exists
        $this->fetchSuperior();

        $this->riscoinId = $riscoinId;
        $this->calculateStatistics();
    }

    private function fetchSuperior()
    {
        // Assuming you have a 'referred_by' field that stores the riscoin_id of the superior
        if ($this->currentNode->inviters_code) {
            $this->superiorNode = User::where('riscoin_id', $this->currentNode->inviters_code)
                ->withCount('invites')
                ->first();
        } else {
            $this->superiorNode = null;
        }
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

<div class="min-h-screen">
    <!-- Header -->
    <div class="max-w-10xl mx-auto">
        <!-- Breadcrumb Navigation -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('genealogy') }}"
                        class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        My Genealogy
                    </a>
                </li>
                @if ($riscoinId)
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-gray-500 dark:text-gray-400 ml-1">{{ $currentNode->riscoin_id }}</span>
                    </li>
                @endif
            </ol>
        </nav>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-6 lg:mb-8">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 lg:p-6 transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 mr-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Direct Members</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $directMembersCount }}</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 lg:p-6 transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/20 mr-4">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Team Members</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $totalTeamMembers }}</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 lg:p-6 transition-all duration-200 hover:shadow-md">
                <div class="flex items-center">
                    <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-900/20 mr-4">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Team Investment</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            ${{ number_format($totalTeamInvestment, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Genealogy Tree Container -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 lg:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white">
                        @if ($riscoinId)
                            Genealogy of {{ $currentNode->name }}
                        @else
                            My Genealogy Tree
                        @endif
                    </h2>
                    @if ($riscoinId)
                        <p class="text-gray-500 dark:text-gray-400 mt-1">{{ $currentNode->riscoin_id }}</p>
                    @endif
                </div>

                @if ($riscoinId)
                    <a href="{{ route('genealogy') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors duration-200 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to My Genealogy
                    </a>
                @endif
            </div>

            <!-- Tree Content -->
            @if ($currentNode->invites->count() > 0 || $superiorNode)
                <div class="genealogy-tree-container overflow-x-auto">
                    <div class="min-w-max flex justify-center py-4">
                        <x-genealogy-node
                            :node="$currentNode"
                            :level="0"
                            :showChildren="true"
                            :showSuperior="!!$superiorNode"
                            :superior="$superiorNode"
                        />
                    </div>
                </div>
            @else
                <div class="text-center py-12 lg:py-16">
                    <div class="max-w-md mx-auto">
                        <div
                            class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Direct Members Yet</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Start building your team by inviting new
                            members.</p>
                    </div>
                </div>
            @endif

            @if ($this->riscoinId)
                <div class="text-center mt-4">
                    <button onclick="window.history.back()"
                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mt-2 inline-block">
                        ← Back to previous genealogy
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
