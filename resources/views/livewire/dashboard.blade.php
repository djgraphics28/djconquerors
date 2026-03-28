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
    public $showFirstReplyToMartin = false;
    public $showLatestInvitesWidget = false;
    public $insight = [];

    public function mount($riscoinId = null)
    {
        if ($riscoinId) {
            // Viewing a specific user's team data
            $this->currentNode = User::where('riscoin_id', $riscoinId)
                ->with([
                    'invites' => function ($query) {
                        $query->withCount('invites');
                    },
                    'assistant',
                    'managerLevel',
                ])
                ->firstOrFail();
        } else {
            // Viewing current user's team data
            $this->currentNode = Auth::user();
            $this->currentNode->load([
                'invites' => function ($query) {
                    $query->withCount('invites');
                },
                'managerLevel',
            ]);
        }

        //if auth user is just first week investor and has no invites, show first reply to martin
        if (Auth::user()->id === $this->currentNode->id) {
            $firstInvestor = User::where('id', Auth::user()->id)
                ->where('date_joined', '>=', now()->subWeek())
                ->whereDoesntHave('invites')
                ->first();

            if ($firstInvestor) {
                $this->showFirstReplyToMartin = true;
            }

            $user = User::whereHas('invites', function ($query) {
                $query->where('created_at', '>=', now()->subWeek());
            })->first();



            $latestInvitesCount = $this->currentNode
                ->invites()
                ->where('created_at', '>=', now()->subWeek())
                ->count();
            if ($latestInvitesCount > 0) {
                $this->showLatestInvitesWidget = true;
            }
        }

        $this->latestInvites = $latestInvites ?? collect();

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

        // Build DJC Insight
        $currentLevel = $this->currentNode->managerLevel?->level ?? 0;
        $levels = [
            1 => ['directs' => 3,  'members' => 0,   'reward' => 15],
            2 => ['directs' => 5,  'members' => 15,  'reward' => 40],
            3 => ['directs' => 6,  'members' => 50,  'reward' => 100],
            4 => ['directs' => 10, 'members' => 100, 'reward' => 250],
            5 => ['directs' => 15, 'members' => 200, 'reward' => 1000],
            6 => ['directs' => 20, 'members' => 500, 'reward' => 3000],
        ];
        $nextLevel     = $currentLevel < 6 ? $currentLevel + 1 : null;
        $nextReq       = $nextLevel ? $levels[$nextLevel] : null;
        $teamTotal     = max(0, $this->totalTeamMembers - 1);
        $directsNeeded = $nextReq ? max(0, $nextReq['directs'] - $this->totalDirectMembers) : 0;
        $membersNeeded = $nextReq ? max(0, $nextReq['members'] - $teamTotal) : 0;
        $this->insight = [
            'currentLevel'  => $currentLevel,
            'directs'       => $this->totalDirectMembers,
            'total'         => $teamTotal,
            'nextLevel'     => $nextLevel,
            'nextReq'       => $nextReq,
            'directsNeeded' => $directsNeeded,
            'membersNeeded' => $membersNeeded,
            'levels'        => $levels,
        ];
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
<div class="space-y-5">
    <!-- User Info Modal Component -->
    <livewire:components.user-info-modal />

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $riscoinId ? 'Team Overview' : 'My Dashboard' }}
            </h1>
            <div class="flex items-center gap-2 mt-0.5">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $riscoinId ? 'Viewing network of' : 'Welcome back,' }} {{ $currentNode->name }}
                </p>
                @if ($riscoinId)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                        {{ $currentNode->riscoin_id }}
                    </span>
                @endif
            </div>
        </div>
        @if ($riscoinId)
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                My Dashboard
            </a>
        @endif
    </div>

    @if ($this->showFirstReplyToMartin === true)
        {{-- first reply to martin --}}
        <div class="mb-6">
            <livewire:widget.first-reply-to-martin :currentNode="$currentNode" />
        </div>
    @endif

    @if ($this->showLatestInvitesWidget)
        {{-- latest invites widget --}}
        <div class="mb-6">
            <livewire:widget.latest-invites :currentNode="$currentNode" />
        </div>
    @endif

    {{-- share link widget --}}
    <div class="mb-6">
        <livewire:widget.share-link />
    </div>

    {{-- copy riscoin link widget --}}
    <div class="mb-6">
        <livewire:widget.copy-riscoin-link />
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        {{-- Direct Members --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Directs</span>
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalDirectMembers) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">direct invites</div>
        </div>

        {{-- Total Team Members --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Network</span>
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalTeamMembers) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">total members</div>
        </div>

        {{-- Team Investments --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Invested</span>
                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalTeamFirstInvestments, 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">team capital</div>
        </div>

        {{-- Total Withdrawals --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Withdrawn</span>
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalTeamWithdrawals, 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">paid out</div>
        </div>

    </div>

    {{-- DJC Insights --}}
    @if (!empty($insight))
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-indigo-950/40 dark:via-gray-900 dark:to-purple-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-800/60 p-5">
        <div class="absolute -right-12 -top-12 w-56 h-56 bg-indigo-200/25 dark:bg-indigo-700/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-8 -bottom-8 w-40 h-40 bg-purple-200/25 dark:bg-purple-700/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative">
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
                <div class="lg:col-span-2 space-y-3">
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
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Direct Invites</span>
                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ $insight['directs'] }} / {{ $nextReq['directs'] }}</span>
                                </div>
                                <div class="h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-700" style="width: {{ $directPct }}%"></div>
                                </div>
                            </div>
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
                                    <span class="text-xs font-semibold {{ $isCurrentLvl ? 'text-indigo-700 dark:text-indigo-300' : ($isDone ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400') }}">
                                        Level {{ $lvl }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold {{ $isCurrentLvl ? 'text-indigo-700 dark:text-indigo-300' : ($isDone ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400') }}">
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
    @endif

    @can('dashboard.viewNewInvestorsAnalytics')
        <!-- New Investors Analytics Widget -->
        <div class="mb-6">
            <livewire:widget.new-investors-analytics :filter="'today'" />
        </div>
    @endcan

    @can('dashboard.viewTopAssisters')
        <!-- Top Assisters Widget -->
        <div class="mb-6">
            <livewire:widget.top-assisters />
        </div>
    @endcan

    <!-- New Cards for Special Occasions -->
    @can('dashboard.viewSpecialOccasions')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <!-- Birthday Celebrators Card -->
            <livewire:widget.birthday-celebrators :riscoinId="$riscoinId" />

            <!-- Membership Anniversaries Card -->
            <livewire:widget.monthly-milestone :riscoinId="$riscoinId" />
        </div>
    @endcan

    {{-- Quick Actions & Team Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Quick Actions
            </h3>
            <div class="space-y-2">
                <a href="{{ route('genealogy', ['riscoinId' => $riscoinId ?? $currentNode->riscoin_id]) }}"
                    class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-800/60 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    </div>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">View Genealogy Tree</span>
                </a>
                <a href="{{ route('my-team') }}"
                    class="flex items-center gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-800/60 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="text-sm font-medium text-purple-700 dark:text-purple-300">Manage My Team</span>
                </a>
                @if ($riscoinId)
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/60 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Back to My Dashboard</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Team Summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Team Summary
            </h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                <div class="flex justify-between items-center py-2.5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Direct Invites</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($totalDirectMembers) }}</span>
                </div>
                <div class="flex justify-between items-center py-2.5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Extended Network</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format(max(0, $totalTeamMembers - $totalDirectMembers - 1)) }}</span>
                </div>
                <div class="flex justify-between items-center py-2.5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Network</span>
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalTeamMembers) }}</span>
                </div>
                <div class="flex justify-between items-center py-2.5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Team Capital</span>
                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($totalTeamFirstInvestments, 2) }}</span>
                </div>
                @if (!empty($insight))
                <div class="flex justify-between items-center py-2.5">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Manager Level</span>
                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                        {{ $insight['currentLevel'] > 0 ? 'Level ' . $insight['currentLevel'] : 'Not yet' }}
                    </span>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
