<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Withdrawal;
use App\Models\Investment;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $totalTeamWithdrawals = 0;
    public $totalTeamMembers = 0;
    public $totalDirectMembers = 0;
    public $totalTeamFirstInvestments = 0;
    public $currentNode;
    public $riscoinId;
    public $birthdayCelebrators = [];
    public $membershipAnniversaries = [];

    public function mount($riscoinId = null)
    {
        if ($riscoinId) {
            // Viewing a specific user's team data
            $this->currentNode = User::where('riscoin_id', $riscoinId)
                ->with([
                    'invites' => function ($query) {
                        $query->withCount('invites');
                    },
                ])
                ->firstOrFail();
        } else {
            // Viewing current user's team data
            $this->currentNode = Auth::user();
            $this->currentNode->load([
                'invites' => function ($query) {
                    $query->withCount('invites');
                },
            ]);
        }

        $this->riscoinId = $riscoinId;
        $this->calculateStatistics();
        $this->calculateSpecialOccasions();
    }

    private function calculateStatistics()
    {
        // Direct members count
        $this->totalDirectMembers = $this->currentNode->invites->count();

        // Calculate total team members and investments recursively
        $this->totalTeamMembers = 1; // Start with 1 to include current node
        $this->totalTeamFirstInvestments = $this->currentNode->invested_amount ?? 0;

        $currentLevel = $this->currentNode->invites;

        while ($currentLevel->isNotEmpty()) {
            $this->totalTeamMembers += $currentLevel->count();
            $this->totalTeamFirstInvestments += $currentLevel->sum('invested_amount');

            $nextLevel = collect();
            foreach ($currentLevel as $user) {
                $user->load('invites'); // Load invites for the next level
                $nextLevel = $nextLevel->merge($user->invites);
            }
            $currentLevel = $nextLevel;
        }

        // Get all team member IDs for withdrawal calculation
        $teamMemberIds = $this->getAllTeamMemberIds($this->currentNode->id);

        // Calculate total team withdrawals
        $this->totalTeamWithdrawals = Withdrawal::whereIn('user_id', $teamMemberIds)->sum('amount');
    }

    private function calculateSpecialOccasions()
    {
        // Get all team member IDs
        $teamMemberIds = $this->getAllTeamMemberIds($this->currentNode->id);

        // Get current month and year
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Get birthday celebrators with their details
        $this->birthdayCelebrators = User::whereIn('id', $teamMemberIds)
            ->whereNotNull('birth_date')
            ->orderByRaw('DAYOFMONTH(birth_date) ASC')
            ->get()
            ->filter(function ($user) use ($currentMonth) {
                $birthday = Carbon::parse($user->birth_date);
                return $birthday->month == $currentMonth;
            })
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'birth_date' => Carbon::parse($user->birth_date)->format('M d'),
                    'avatar' => $user->getFirstMediaUrl('avatar') ?: $this->getDefaultAvatar(),
                    'riscoin_id' => $user->riscoin_id,
                    'invested_amount' => $user->invested_amount,
                    'date_joined' => $user->date_joined,
                    'is_birthday_mention' => $user->is_birthday_mention == 1 ? true : false,
                ];
            })
            ->values()
            ->toArray();

        // Get membership anniversaries with their details
        $this->membershipAnniversaries = User::whereIn('id', $teamMemberIds)
            ->whereNotNull('date_joined')
            ->get()
            ->filter(function ($user) {
                $joinDate = Carbon::parse($user->date_joined);
                $monthsDifference = $joinDate->diffInMonths(now());

                // Check if it's exactly n months since joining (same day of month)
                return $monthsDifference > 0 && $joinDate->day == now()->day;
            })
            ->map(function ($user) {
                $joinDate = Carbon::parse($user->date_joined);
                $monthsWithTeam = number_format($joinDate->diffInMonths(now()));

                return [
                    'name' => $user->name,
                    'join_date' => $joinDate->format('M d, Y'),
                    'months_with_team' => $monthsWithTeam,
                    'avatar' => $user->getFirstMediaUrl('avatar') ?: $this->getDefaultAvatar(),
                    'riscoin_id' => $user->riscoin_id,
                    'invested_amount' => $user->invested_amount,
                    'date_joined' => $user->date_joined,
                    'is_today_joined' => $joinDate->format('Y-m-d') === now()->format('Y-m-d'),
                    'is_monthly_milestone_mention' => $user->is_monthly_milestone_mention == 1 ? true : false,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getDefaultAvatar()
    {
        // Return a default avatar URL or SVG
        return 'data:image/svg+xml;base64,' .
            base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        ');
    }

    private function getAllTeamMemberIds($userId)
    {
        $memberIds = [$userId];

        // Get the user with their invites
        $user = User::with('invites')->find($userId);
        $currentLevel = $user->invites;

        while ($currentLevel->isNotEmpty()) {
            $currentLevelIds = $currentLevel->pluck('id')->toArray();
            $memberIds = array_merge($memberIds, $currentLevelIds);

            $nextLevel = collect();
            foreach ($currentLevel as $member) {
                $member->load('invites');
                $nextLevel = $nextLevel->merge($member->invites);
            }
            $currentLevel = $nextLevel;
        }

        return array_unique($memberIds);
    }

    public function getRandomBirthdayMessage($name)
    {
        $messages = [
            "Happy Birthday, {$name}!\n\nOn your special day, we want you to know how much you are loved and appreciated. May your heart be filled with joy, your year ahead with blessings, and your life with endless happiness.\n\nWishing you all the very best today and always.\n\nWith love,\nDJ Conquerors Family",
            "Happy Birthday, {$name}!\n\nHope your day is as amazing as you are! Sending you lots of love and good vibes on your special day.\n\nCheers,\nDJ Conquerors Family",
            "It's your birthday, {$name}! Time to conquer the day! 🎉\n\nGet ready for cake, good music, and great times! We hope your day is filled with fantastic moments and unforgettable memories. Let's make some noise!\n\nAll the best,\nDJ Conquerors Family",
            "A very happy birthday to you, {$name}.\n\nOn this wonderful day, we're reminded of how grateful we are to have you in our lives/family. May you be surrounded by love, laughter, and everything that brings you happiness.\n\nWarmest wishes on your birthday.\n\nSincerely,\nDJ Conquerors Family",
            "🎂 HAPPY BIRTHDAY, {$name}! 🎶\n\nAnother year older, wiser, and more awesome! The DJ Conquerors Family is wishing you a day full of good tunes, great company, and non-stop fun. Have a blast!\n\nMuch love,\nDJ Conquerors Family",
            "Dear {$name},\n\nWe extend our warmest wishes to you on the occasion of your birthday. May this new year of your life bring you success, health, and profound happiness.\n\nBest regards,\nDJ Conquerors Family",
        ];

        return $messages[array_rand($messages)];
    }

    public function getMembershipAnniversaryMessage($member)
    {
        $name = $member['name'];
        $joinDate = Carbon::parse($member['date_joined'])->format('M j, Y');
        $investedAmount = $member['invested_amount'] ?? 0;

        // If joined today
        if ($member['is_today_joined']) {
            return "Welcome to DJ Conquerors! 🍾\nLet's grow, conquer, and succeed together 💪🔥\n\n{$name}\nDate invested: {$joinDate}\nAmount invested: \${$investedAmount} USDT";
        }

        // Monthly milestone messages
        $messages = [
            "🎯 Monthly Milestone Unlocked!\nTeam DJ Conquerors, we've made another month of progress, passion, and perseverance. Let's celebrate the wins, learn from the challenges, and keep pushing forward together!\nLet's conquer more milestones ahead.\n— DJ Conquerors Team 💪",

            "🔥 This month was an incredible one for DJ Conquerors!\nEvery challenge faced and every goal achieved shows our unstoppable spirit. Here's to more victories, stronger teamwork, and endless success in the coming months!\nProudly,\nDJ Conquerors Family",

            "💥 Cheers to our Monthly Milestone!\nWe've proven once again that dedication and unity make us unstoppable. Let's keep the fire burning as we set our sights on even greater goals.\nKeep conquering,\nDJ Conquerors",

            "👏 Monthly Milestone Celebration!\nEach member of DJ Conquerors played a part in this success story. Thank you for your hard work, energy, and passion. Together, we rise — higher and stronger every month.\nWith appreciation,\nDJ Conquerors Team",

            "🚀 This Month Was One to Remember!\nWe hit our targets, strengthened our bond, and kept our Conqueror spirit alive. Let's take this momentum into the next chapter — the journey continues!\nMuch respect,\nDJ Conquerors Family",

            "🌟 DJ Conquerors Monthly Milestone!\nAnother month of teamwork, dedication, and breakthroughs! Let's celebrate our success and prepare to conquer new horizons ahead.\nWith gratitude,\nDJ Conquerors Team",
        ];

        $selectedMessage = $messages[array_rand($messages)];

        // Add member-specific information
        return "{$selectedMessage}\n\n🎊 Celebrating {$member['months_with_team']} month" . ($member['months_with_team'] > 1 ? 's' : '') . " with {$name}!\nJoined: {$joinDate}";
    }
}; ?>

<div>
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li>
                <a href="{{ route('dashboard') }}"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                    My Dashboard
                </a>
            </li>
            @if ($riscoinId)
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500 dark:text-gray-400">Team Stats: {{ $currentNode->riscoin_id }}</span>
                </li>
            @endif
        </ol>
    </nav>

    <!-- Page Header -->
    {{-- <div class="mb-6">
        <h1 class="text-2xl font-bold dark:text-white">
            @if ($riscoinId)
                Team Statistics: {{ $currentNode->name }} ({{ $currentNode->riscoin_id }})
            @else
                My Team Statistics
            @endif
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Real-time team performance metrics</p>
    </div> --}}

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <!-- Direct Members Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Direct Members</h3>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ number_format($totalDirectMembers) }}</p>
                </div>
            </div>
        </div>

        <!-- Team Members Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Team Members</h3>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ number_format($totalTeamMembers) }}</p>
                </div>
            </div>
        </div>

        <!-- Team Investments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900 mr-4">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Team Investments</h3>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                        ${{ number_format($totalTeamFirstInvestments, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Team Withdrawals Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Team Withdrawals</h3>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        ${{ number_format($totalTeamWithdrawals, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

   @can('dashboard.copyMessageToMartin')
<!-- Copy Support Form to Clipboard -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
    <div x-data="{
        copied: false,
        copySupportForm() {
            const message = `Support Team: {{ $currentNode->support_team }}
Inviter's Riscoin ID: {{ $currentNode->inviter_code }}
Riscoin Account ID: {{ $currentNode->riscoin_id }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
Your Name: {{ $currentNode->name }}
Occupation: {{ $currentNode->occupation ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Age: {{ $currentNode->age ?? 'Not specified' }}`;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(message).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = message;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();

                try {
                    document.execCommand('copy');
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                } catch (err) {
                    console.error('Copy failed:', err);
                }

                document.body.removeChild(textArea);
            }
        }
    }" class="relative">
        <button @click="copySupportForm()"
            class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 font-medium">
            Copy Support Form to Clipboard
        </button>

        <!-- Copy feedback -->
        <div x-show="copied" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg">
            <span class="text-white font-semibold">Copied to clipboard! 📋</span>
        </div>
    </div>

    <!-- Preview of what will be copied -->
    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</h4>
        <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">
Support Team: {{ $currentNode->support_team }}
Inviter's Riscoin ID: {{ $currentNode->inviter_code }}
Riscoin Account ID: {{ $currentNode->riscoin_id }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
Your Name: {{ $currentNode->name }}
Occupation: {{ $currentNode->occupation ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Age: {{ $currentNode->age ?? 'Not specified' }}</pre>
    </div>
</div>
@endcan

    <!-- New Cards for Special Occasions -->
    @can('dashboard.viewSpecialOccasions')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Birthday Celebrators Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-pink-100 dark:bg-pink-900 mr-4">
                            <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Birthday Celebrators</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">This month</p>
                        </div>
                        <div
                            class="ml-auto bg-pink-100 dark:bg-pink-900 text-pink-600 dark:text-pink-400 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ count($birthdayCelebrators) }}
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if (count($birthdayCelebrators) > 0)
                        <div class="space-y-3">
                            @foreach ($birthdayCelebrators as $celebrator)
                                @php
                                    $isBirthdayToday =
                                        \Carbon\Carbon::parse($celebrator['birth_date'])->format('m-d') ===
                                        now()->format('m-d');
                                @endphp
                                <div x-data="{
                                    copied: false,
                                    async copyToClipboard() {
                                        if (!{{ $celebrator['is_birthday_mention'] }}) return;
                                        try {
                                            // Call the Livewire method to get the message
                                            const message = await $wire.getRandomBirthdayMessage('{{ $celebrator['name'] }}');

                                            // Use the modern Clipboard API
                                            await navigator.clipboard.writeText(message);

                                            // Show feedback
                                            this.copied = true;
                                            setTimeout(() => {
                                                this.copied = false;
                                            }, 2000);
                                        } catch (err) {
                                            // Fallback for older browsers
                                            console.error('Failed to copy: ', err);
                                            const textArea = document.createElement('textarea');
                                            textArea.value = await $wire.getRandomBirthdayMessage('{{ $celebrator['name'] }}');
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);

                                            this.copied = true;
                                            setTimeout(() => {
                                                this.copied = false;
                                            }, 2000);
                                        }
                                    }
                                }" @click="copyToClipboard()"
                                    class="flex items-center p-3 {{ $isBirthdayToday ? 'bg-pink-50 dark:bg-pink-900/20 border-2 border-pink-200 dark:border-pink-700' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg {{ $celebrator['is_birthday_mention'] ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600' : 'cursor-not-allowed opacity-75' }} transition duration-200 relative">
                                    <img class="w-10 h-10 rounded-full mr-3" src="{{ $celebrator['avatar'] }}"
                                        alt="{{ $celebrator['name'] }}">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white flex items-center">
                                            {{ $celebrator['name'] }}
                                            @if ($isBirthdayToday)
                                                <span class="ml-2 inline-flex">
                                                    🎈🎂✨
                                                </span>
                                            @endif
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Birthday: {{ $celebrator['birth_date'] }} •
                                            ID: {{ $celebrator['riscoin_id'] }}
                                            @if ($isBirthdayToday)
                                                <span class="ml-2 text-pink-600 dark:text-pink-400 font-medium">Birthday
                                                    Today! 🎉</span>
                                            @endif
                                        </p>
                                    </div>
                                    <!-- Copy feedback -->
                                    <div x-show="copied" x-transition
                                        class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg">
                                        <span class="text-white font-semibold">Copied to clipboard! 📋</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No birthdays this month</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Membership Anniversaries Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 mr-4">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Membership</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Monthly milestones</p>
                        </div>
                        <div
                            class="ml-auto bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ count($membershipAnniversaries) }}
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if (count($membershipAnniversaries) > 0)
                        <div class="space-y-3">
                            @foreach ($membershipAnniversaries as $member)
                                @php
                                    $isTodayJoined =
                                        \Carbon\Carbon::parse($member['date_joined'])->format('Y-m-d') ===
                                        now()->format('Y-m-d');
                                @endphp
                                <div x-data="{
                                    copied: false,
                                    async copyToClipboard() {
                                        if (!{{ $member['is_monthly_milestone_mention'] }}) return;
                                        try {
                                            // Prepare the member data for Livewire method
                                            const memberData = {
                                                name: '{{ $member['name'] }}',
                                                join_date: '{{ $member['join_date'] }}',
                                                months_with_team: '{{ $member['months_with_team'] }}',
                                                avatar: '{{ $member['avatar'] }}',
                                                riscoin_id: '{{ $member['riscoin_id'] }}',
                                                invested_amount: '{{ $member['invested_amount'] }}',
                                                date_joined: '{{ $member['date_joined'] }}',
                                                is_today_joined: {{ $isTodayJoined ? 'true' : 'false' }}
                                            };

                                            // Call the Livewire method to get the message
                                            const message = await $wire.getMembershipAnniversaryMessage(memberData);

                                            // Use the modern Clipboard API
                                            await navigator.clipboard.writeText(message);

                                            // Show feedback
                                            this.copied = true;
                                            setTimeout(() => {
                                                this.copied = false;
                                            }, 2000);
                                        } catch (err) {
                                            // Fallback for older browsers
                                            console.error('Failed to copy: ', err);

                                            // Prepare member data for fallback
                                            const memberData = {
                                                name: '{{ $member['name'] }}',
                                                join_date: '{{ $member['join_date'] }}',
                                                months_with_team: '{{ $member['months_with_team'] }}',
                                                avatar: '{{ $member['avatar'] }}',
                                                riscoin_id: '{{ $member['riscoin_id'] }}',
                                                invested_amount: '{{ $member['invested_amount'] }}',
                                                date_joined: '{{ $member['date_joined'] }}',
                                                is_today_joined: {{ $isTodayJoined ? 'true' : 'false' }}
                                            };

                                            const textArea = document.createElement('textarea');
                                            textArea.value = await $wire.getMembershipAnniversaryMessage(memberData);
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);

                                            this.copied = true;
                                            setTimeout(() => {
                                                this.copied = false;
                                            }, 2000);
                                        }
                                    }
                                }" @click="copyToClipboard()"
                                    class="flex items-center p-3 {{ $isTodayJoined ? 'bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-700' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg {{ $member['is_monthly_milestone_mention'] ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600' : 'cursor-not-allowed opacity-75' }} transition duration-200 relative">
                                    <img class="w-10 h-10 rounded-full mr-3" src="{{ $member['avatar'] }}"
                                        alt="{{ $member['name'] }}">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white flex items-center">
                                            {{ $member['name'] }}
                                            @if ($isTodayJoined)
                                                <span class="ml-2 inline-flex">
                                                    🎊🎉✨
                                                </span>
                                            @endif
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Joined: {{ $member['join_date'] }} •
                                            {{ $member['months_with_team'] }}
                                            month{{ $member['months_with_team'] > 1 ? 's' : '' }} with team
                                            @if ($isTodayJoined)
                                                <span class="ml-2 text-indigo-600 dark:text-indigo-400 font-medium">Joined
                                                    Today! 🎉</span>
                                            @endif
                                        </p>
                                    </div>
                                    <!-- Copy feedback -->
                                    <div x-show="copied" x-transition
                                        class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg">
                                        <span class="text-white font-semibold">Copied to clipboard! 📋</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                            <p class="mt-2 text-gray-500 dark:text-gray-400">No anniversaries today</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endcan

    <!-- Additional Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Navigation Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('genealogy', ['riscoinId' => $riscoinId ?? $currentNode->riscoin_id]) }}"
                    class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition duration-200">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span class="text-blue-600 dark:text-blue-400 font-medium">View Genealogy Tree</span>
                </a>

                @if ($riscoinId)
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span class="text-gray-600 dark:text-gray-400 font-medium">Back to My Dashboard</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Team Summary Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Team Summary</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Direct Team:</span>
                    <span class="font-semibold dark:text-white">{{ number_format($totalDirectMembers) }}
                        members</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">Extended Network:</span>
                    <span
                        class="font-semibold dark:text-white">{{ number_format($totalTeamMembers - $totalDirectMembers - 1) }}
                        members</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-600 dark:text-gray-400">Total Network:</span>
                    <span class="font-semibold dark:text-white">{{ number_format($totalTeamMembers) }} members</span>
                </div>
            </div>
        </div>
    </div>
</div>
