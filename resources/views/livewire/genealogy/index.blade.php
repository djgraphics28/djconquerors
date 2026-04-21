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
                    'managerLevel',
                ])
                ->firstOrFail();
        } else {
            // Viewing current user's genealogy
            $this->currentNode = Auth::user();
            $this->currentNode->load([
                'invites' => function ($query) {
                    $query->withCount('invites');
                },
                'managerLevel',
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
                ->with('managerLevel')
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

<div class="min-h-screen space-y-5">
    <div class="max-w-10xl mx-auto space-y-5">

        {{-- Page Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-5 py-5 sm:px-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        {{-- Avatar --}}
                        <div class="w-14 h-14 rounded-full border-2 border-white/40 overflow-hidden flex-shrink-0 bg-white/20">
                            @if ($currentNode->hasMedia('avatar'))
                                <img src="{{ $currentNode->getFirstMediaUrl('avatar') }}" alt="{{ $currentNode->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <span class="text-2xl font-bold text-white">{{ substr($currentNode->name ?? 'U', 0, 1) }}</span>
                                </div>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-lg sm:text-xl font-bold text-white">
                                    @if ($riscoinId)
                                        {{ $currentNode->name }}'s Genealogy
                                    @else
                                        My Genealogy Tree
                                    @endif
                                </h1>
                                @php $lvl = $currentNode->managerLevel?->level ?? 0; @endphp
                                @if ($lvl > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        Level {{ $lvl }} Manager
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-blue-100 mt-0.5 font-mono">ID: {{ $currentNode->riscoin_id }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($riscoinId)
                            <a href="{{ route('genealogy') }}"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-white text-sm font-medium transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                My Genealogy
                            </a>
                        @endif
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 text-sm font-medium transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats bar --}}
            <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-700">
                <div class="px-5 py-4 text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Direct Members</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $directMembersCount }}</p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Team</p>
                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $totalTeamMembers }}</p>
                </div>
                <div class="px-5 py-4 text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Team Capital</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">${{ number_format($totalTeamInvestment, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Genealogy Tree Container --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            {{-- Card header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                            @if ($riscoinId) {{ $currentNode->name }}'s Network @else My Network Tree @endif
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $directMembersCount }} direct · {{ $totalTeamMembers - 1 }} downline</p>
                    </div>
                </div>
                @if ($riscoinId)
                    <button onclick="window.history.back()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </button>
                @endif
            </div>

            {{-- Tree Content --}}
            <div class="p-4 lg:p-6">
                @if ($currentNode->invites->count() > 0 || $superiorNode)
                    <div class="genealogy-tree-container overflow-x-auto">
                        <div class="min-w-max flex justify-center py-4">
                            <x-genealogy-node
                                :node="$currentNode"
                                :level="0"
                                :showChildren="true"
                                :showSuperior="!!$superiorNode"
                                :superior="$superiorNode"
                                :isSuperiorNode="false"
                                :managerLevel="$currentNode->managerLevel?->level ?? 0"
                            />
                        </div>
                    </div>
                @else
                    <div class="text-center py-14">
                        <div class="max-w-sm mx-auto">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-dashed border-blue-200 dark:border-blue-700">
                                <svg class="w-9 h-9 text-blue-400 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">No Direct Members Yet</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">Share your invite links below to start building your network.</p>
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    Portal Link below
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 border border-orange-100 dark:border-orange-800">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Riscoin Link below
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Share Links --}}
        @if (!$riscoinId)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <livewire:widget.share-link />
                <livewire:widget.copy-riscoin-link />
            </div>
        @endif

    </div>
</div>
