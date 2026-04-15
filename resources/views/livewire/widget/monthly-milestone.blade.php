<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?string $riscoinId = null;
    public array $membershipAnniversaries = [];
    public string $bulkMessage = '';
    public int $milestonesPage = 1;
    public int $milestonesPerPage = 10;
    public bool $milestonesHasMore = false;

    public function mount(?string $riscoinId = null): void
    {
        $this->riscoinId = $riscoinId;
        $this->calculateMembershipAnniversaries();
    }

    public function loadMoreMilestones(): void
    {
        $this->milestonesPage++;
        $this->milestonesHasMore = count($this->membershipAnniversaries) > ($this->milestonesPage * $this->milestonesPerPage);
    }

    public function getCurrentMilestonesProperty(): array
    {
        return array_slice($this->membershipAnniversaries, 0, $this->milestonesPage * $this->milestonesPerPage);
    }

    private function calculateMembershipAnniversaries(): void
    {
        // Determine current node (either by riscoinId or auth user)
        if ($this->riscoinId) {
            $currentNode = User::where('riscoin_id', $this->riscoinId)->first();
        } else {
            $currentNode = Auth::user();
        }

        if (! $currentNode) {
            $this->membershipAnniversaries = [];
            return;
        }

        // Get all team member IDs
        $teamMemberIds = $this->getAllTeamMemberIds($currentNode->id);

        $this->membershipAnniversaries = User::whereIn('id', $teamMemberIds)
            ->whereNotNull('date_joined')
            ->whereDay('date_joined', now()->day)
            ->with('media')
            ->select('id', 'name', 'date_joined', 'riscoin_id', 'invested_amount', 'is_monthly_milestone_mention')
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
        $this->milestonesPage = 1;
        $this->milestonesHasMore = count($this->membershipAnniversaries) > $this->milestonesPerPage;
    }

    private function getAllTeamMemberIds(int $userId): array
    {
        $root = DB::table('users')->where('id', $userId)->value('riscoin_id');
        if (!$root) return [$userId];

        $allIds            = [$userId];
        $currentRiscoinIds = [$root];

        while (!empty($currentRiscoinIds)) {
            $batch = DB::table('users')
                ->whereIn('inviters_code', $currentRiscoinIds)
                ->whereNull('deleted_at')
                ->select('id', 'riscoin_id')
                ->get();

            if ($batch->isEmpty()) break;

            $currentRiscoinIds = [];
            foreach ($batch as $row) {
                $allIds[]            = $row->id;
                $currentRiscoinIds[] = $row->riscoin_id;
            }
        }

        return array_unique($allIds);
    }

    public function getIndividualMessage($memberIndex)
    {
        if (!isset($this->membershipAnniversaries[$memberIndex])) {
            return '';
        }

        $member = $this->membershipAnniversaries[$memberIndex];
        $name = $member['name'];
        $joinDate = Carbon::parse($member['date_joined'])->format('M j, Y');
        $investedAmount = $member['invested_amount'] ?? 0;

        if ($member['is_today_joined']) {
            return "Welcome to DJ Conquerors! 🍾\nLet's grow, conquer, and succeed together 💪🔥\n\n{$name}\nDate invested: {$joinDate}\nAmount invested: \${$investedAmount} USDT";
        }

        $messages = [
            "🌟 DJ Conquerors Monthly Milestone!\nAnother month of teamwork, dedication, and breakthroughs! Let's celebrate our success and prepare to conquer new horizons ahead.\nWith gratitude,\nDJ Conquerors Team",
            "🔥 This month was an incredible one for DJ Conquerors!\nEvery challenge faced and every goal achieved shows our unstoppable spirit. Here's to more victories, stronger teamwork, and endless success in the coming months!\nProudly,\nDJ Conquerors Family",
            "💥 Cheers to our Monthly Milestone!\nWe've proven once again that dedication and unity make us unstoppable. Let's keep the fire burning as we set our sights on even greater goals.\nKeep conquering,\nDJ Conquerors",
            "👏 Monthly Milestone Celebration!\nEach member of DJ Conquerors played a part in this success story. Thank you for your hard work, energy, and passion. Together, we rise — higher and stronger every month.\nWith appreciation,\nDJ Conquerors Team",
            "🚀 This Month Was One to Remember!\nWe hit our targets, strengthened our bond, and kept our Conqueror spirit alive. Let's take this momentum into the next chapter — the journey continues!\nMuch respect,\nDJ Conquerors Family",
        ];

        $selectedMessage = $messages[array_rand($messages)];

        return "{$selectedMessage}\n\n🎊 Celebrating {$member['months_with_team']} month" . ($member['months_with_team'] > 1 ? 's' : '') . " with {$name}!\nJoined: {$joinDate}";
    }

    public function getBulkMilestoneMessage()
    {
        // Find eligible members (exclude those who joined today and those not eligible)
        $bulk = collect($this->membershipAnniversaries)->filter(function ($m) {
            return !($m['is_today_joined'] ?? false) && ($m['is_monthly_milestone_mention'] ?? false);
        })->values();

        if ($bulk->isEmpty()) {
            return '';
        }

        // Use the specific header preset as requested
        $header = "🌟 DJ Conquerors Monthly Milestone!\nAnother month of teamwork, dedication, and breakthroughs! Let's celebrate our success and prepare to conquer new horizons ahead.\nWith gratitude,\nDJ Conquerors Team";

        $parts = [];
        foreach ($bulk as $member) {
            $parts[] = "🎊 Celebrating {$member['months_with_team']} month" . ((int)$member['months_with_team'] > 1 ? 's' : '') . " with {$member['name']}!\nJoined: {$member['join_date']}";
        }

        return $header . "\n\n" . implode("\n\n", $parts);
    }

    public function copyBulkMilestones()
    {
        $this->bulkMessage = $this->getBulkMilestoneMessage();
        return $this->bulkMessage;
    }

    public function clearBulkMessage()
    {
        $this->bulkMessage = '';
    }

    private function getDefaultAvatar()
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
};

?>

<div>
    @php
        $bulkMilestones = collect($membershipAnniversaries)->filter(fn($m) => !($m['is_today_joined'] ?? false) && ($m['is_monthly_milestone_mention'] ?? false))->values()->toArray();
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Gradient Header --}}
        <div class="px-5 pt-5 pb-4 bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-lg shadow-sm">
                        🏆
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-white">Membership Milestones</h3>
                        <p class="text-xs text-white/70">Monthly anniversaries today</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold bg-white/20 text-white px-2.5 py-1 rounded-full backdrop-blur-sm">
                        {{ count($membershipAnniversaries) }} today
                    </span>
                    @if(count($bulkMilestones) > 0)
                        <button type="button"
                            wire:click="copyBulkMilestones"
                            onclick="copyMilestoneBulk()"
                            class="flex items-center gap-1.5 px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-full transition-colors backdrop-blur-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy all milestones
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Milestones List --}}
        <div class="p-5">
            @if(count($membershipAnniversaries) > 0)
                <div
                    wire:ignore.self
                    class="space-y-2 overflow-y-auto pr-0.5 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700"
                    style="max-height: 420px;"
                    x-data="{
                        observer: null,
                        init() {
                            this.$nextTick(() => this.setupObserver());
                        },
                        setupObserver() {
                            if (this.observer) { this.observer.disconnect(); this.observer = null; }
                            const sentinel = this.$refs.sentinel;
                            if (!sentinel) return;
                            this.observer = new IntersectionObserver((entries) => {
                                if (entries[0].isIntersecting) {
                                    $wire.loadMoreMilestones().then(() => {
                                        this.$nextTick(() => this.setupObserver());
                                    });
                                }
                            }, { root: this.$el, threshold: 0.1 });
                            this.observer.observe(sentinel);
                        }
                    }"
                >
                    @foreach($this->currentMilestones as $index => $member)
                        @php $isTodayJoined = \Carbon\Carbon::parse($member['date_joined'])->format('Y-m-d') === now()->format('Y-m-d'); @endphp
                        <div x-data="{
                                copied: false,
                                async copyToClipboard() {
                                    if (!{{ $member['is_monthly_milestone_mention'] ? 'true' : 'false' }}) return;
                                    const message = await $wire.getIndividualMessage({{ $index }});
                                    if (navigator.clipboard && navigator.clipboard.writeText) {
                                        try { await navigator.clipboard.writeText(message); this.showSuccess(); return; } catch(err) {}
                                    }
                                    this.fallbackCopy(message);
                                },
                                fallbackCopy(text) {
                                    const ta = document.createElement('textarea');
                                    ta.value = text; ta.style.position='fixed'; ta.style.left='-999999px';
                                    document.body.appendChild(ta); ta.focus(); ta.select();
                                    try { document.execCommand('copy'); this.showSuccess(); } catch(e) { this.showError(); } finally { document.body.removeChild(ta); }
                                },
                                showSuccess() { this.copied = true; setTimeout(() => this.copied = false, 2000); },
                                showError() { alert('Failed to copy. Please try again.'); }
                            }"
                            class="relative group flex items-center gap-3 p-3 {{ $isTodayJoined ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-indigo-200 dark:ring-indigo-700/50' : 'bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700/60' }} rounded-xl transition-colors duration-150">

                            {{-- Avatar --}}
                            <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}"
                                class="w-10 h-10 rounded-full object-cover ring-2 {{ $isTodayJoined ? 'ring-indigo-300 dark:ring-indigo-600' : 'ring-white dark:ring-gray-700' }} flex-shrink-0" />

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">
                                    {{ $member['name'] }}
                                    @if($isTodayJoined)<span class="ml-1">🎊🎉</span>@endif
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                    Joined {{ $member['join_date'] }} ·
                                    {{ $member['months_with_team'] }} month{{ $member['months_with_team'] > 1 ? 's' : '' }} with team
                                </p>
                            </div>

                            {{-- Badge --}}
                            @if($isTodayJoined)
                                <span class="flex-shrink-0 text-xs font-bold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full whitespace-nowrap">Joined Today 🎉</span>
                            @else
                                <span class="flex-shrink-0 text-xs font-semibold bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 px-2 py-0.5 rounded-full whitespace-nowrap">{{ $member['months_with_team'] }}mo 🏅</span>
                            @endif

                            {{-- Copy button --}}
                            @if($member['is_monthly_milestone_mention'])
                                <button @click="copyToClipboard()" :disabled="copied"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors duration-150"
                                    title="Copy milestone message">
                                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            @else
                                <div class="flex-shrink-0 p-1.5 text-gray-200 dark:text-gray-700" title="Copy disabled">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Copied overlay --}}
                            <div x-show="copied" x-transition class="absolute inset-0 bg-green-500/90 flex items-center justify-center rounded-xl z-10">
                                <span class="text-white font-semibold text-sm">Copied! 📋</span>
                            </div>
                        </div>
                    @endforeach

                    {{-- IntersectionObserver sentinel --}}
                    @if($milestonesHasMore)
                        <div x-ref="sentinel" class="flex items-center justify-center py-3 gap-2 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Loading more…
                        </div>
                    @else
                        <div class="py-2 text-center text-xs text-gray-400 dark:text-gray-500">
                            All {{ count($this->currentMilestones) }} milestones loaded
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <span class="text-4xl mb-2">🏆</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No anniversaries today</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Bulk copy holder (always present) -->
    <div id="bulkMessageHolder" style="display: none;">{{ $bulkMessage }}</div>

    <script>
        async function copyMilestoneBulk() {
            try {
                const users = @json($bulkMilestones ?? []);
                if (!users || users.length === 0) return;

                const header = `🌟 DJ Conquerors Monthly Milestone!\nAnother month of teamwork, dedication, and breakthroughs! Let's celebrate our success and prepare to conquer new horizons ahead.\nWith gratitude,\nDJ Conquerors Team`;

                const parts = users.map(u => {
                    const months = u.months_with_team;
                    return `🎊 Celebrating ${months} month${parseInt(months) > 1 ? 's' : ''} with ${u.name}!\nJoined: ${u.join_date}`;
                });

                const message = header + "\n\n" + parts.join("\n\n");

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(message);
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    toast.textContent = 'All milestones copied to clipboard!';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2000);
                    return true;
                }

                // fallback
                const ta = document.createElement('textarea');
                ta.value = message;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch(e) { console.warn(e); }
                document.body.removeChild(ta);
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                toast.textContent = 'All milestones copied to clipboard!';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2000);
                return true;
            } catch (err) {
                console.error('copyToClipboard failed', err);
                alert('Failed to copy milestones');
                return false;
            }
        }
    </script>

    <!-- Global copy function for bulk copy -->
    <script>
        // This will be called by the Livewire event
        function copyTextToClipboard(text) {
            return new Promise((resolve) => {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        resolve(true);
                    }).catch(() => {
                        // Fallback
                        const success = fallbackCopy(text);
                        resolve(success);
                    });
                } else {
                    // Fallback for older browsers
                    const success = fallbackCopy(text);
                    resolve(success);
                }
            });
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                return true;
            } catch (err) {
                console.error('Fallback copy failed:', err);
                return false;
            } finally {
                document.body.removeChild(textArea);
            }
        }
    </script>
</div>
