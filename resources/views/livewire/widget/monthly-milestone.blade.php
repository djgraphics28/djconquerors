<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?string $riscoinId = null;
    public array $membershipAnniversaries = [];
    public string $bulkMessage = '';

    public function mount(?string $riscoinId = null): void
    {
        $this->riscoinId = $riscoinId;
        $this->calculateMembershipAnniversaries();
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

    private function getAllTeamMemberIds($userId)
    {
        $memberIds = [$userId];

        $user = User::with('invites')->find($userId);
        if (! $user) return $memberIds;

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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900 mr-4">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Membership</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Monthly milestones</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded-full text-sm font-semibold">
                        {{ count($membershipAnniversaries) }}
                    </div>
                    @php
                        $bulkMilestones = collect($membershipAnniversaries)->filter(fn($m) => !($m['is_today_joined'] ?? false) && ($m['is_monthly_milestone_mention'] ?? false))->values()->toArray();
                    @endphp
                    @if(count($bulkMilestones) > 0)
                        <button type="button"
                                wire:click="copyBulkMilestones"
                                onclick="copyToClipboard()"
                                class="ml-2 px-3 py-1 bg-indigo-500 text-white text-sm rounded hover:bg-indigo-600 transition-colors"
                                title="Copy all monthly milestones">
                            Copy all milestones
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="p-6">
            @if (count($membershipAnniversaries) > 0)
                <div class="space-y-3">
                    @foreach ($membershipAnniversaries as $index => $member)
                        @php
                            $isTodayJoined = \Carbon\Carbon::parse($member['date_joined'])->format('Y-m-d') === now()->format('Y-m-d');
                        @endphp
                        <div x-data="{
                            copied: false,
                            async copyToClipboard() {
                                if (!{{ $member['is_monthly_milestone_mention'] }}) return;

                                // Get the message from Livewire
                                const message = await $wire.getIndividualMessage({{ $index }});

                                // Simple copy function that works on both mobile and desktop
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    try {
                                        await navigator.clipboard.writeText(message);
                                        this.showSuccess();
                                        return;
                                    } catch (err) {
                                        console.log('Clipboard API failed, trying fallback...', err);
                                    }
                                }

                                // Fallback method
                                this.fallbackCopy(message);
                            },
                            fallbackCopy(text) {
                                const textArea = document.createElement('textarea');
                                textArea.value = text;
                                textArea.style.position = 'fixed';
                                textArea.style.left = '-999999px';
                                textArea.style.top = '-999999px';
                                document.body.appendChild(textArea);
                                textArea.focus();
                                textArea.select();

                                try {
                                    const successful = document.execCommand('copy');
                                    if (successful) {
                                        this.showSuccess();
                                    } else {
                                        this.showError();
                                    }
                                } catch (err) {
                                    console.error('Fallback copy failed:', err);
                                    this.showError();
                                } finally {
                                    document.body.removeChild(textArea);
                                }
                            },
                            showSuccess() {
                                this.copied = true;
                                setTimeout(() => {
                                    this.copied = false;
                                }, 2000);
                            },
                            showError() {
                                alert('Failed to copy. Please try again or copy manually.');
                            }
                        }"
                            class="group flex items-center p-3 {{ $isTodayJoined ? 'bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-700' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg transition duration-200 relative">
                            <img class="w-10 h-10 rounded-full mr-3" src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white flex items-center truncate">
                                    {{ $member['name'] }}
                                    @if ($isTodayJoined)
                                        <span class="ml-2 inline-flex">🎊🎉✨</span>
                                    @endif
                                </h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    Joined: {{ $member['join_date'] }} •
                                    {{ $member['months_with_team'] }}
                                    month{{ $member['months_with_team'] > 1 ? 's' : '' }} with team
                                    @if ($isTodayJoined)
                                        <span class="ml-2 text-indigo-600 dark:text-indigo-400 font-medium">Joined Today! 🎉</span>
                                    @endif
                                </p>
                            </div>

                            @if ($member['is_monthly_milestone_mention'])
                                <button @click="copyToClipboard()" :disabled="copied"
                                    class="ml-3 p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="Copy anniversary message">
                                    <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg x-show="copied" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            @else
                                <div class="ml-3 p-2 text-gray-300 dark:text-gray-600 cursor-not-allowed" title="Copy disabled">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif

                            <!-- Copy feedback -->
                            <div x-show="copied" x-transition class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg z-10">
                                <span class="text-white font-semibold flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Copied! 📋
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">No anniversaries today</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Bulk copy holder (always present) -->
    <div id="bulkMessageHolder" style="display: none;">{{ $bulkMessage }}</div>
    <!-- The copy action is handled globally by the Livewire hook script below -->

    <script>
        // Client-side bulk copy using server-provided array (avoids race with Livewire)
        async function copyToClipboard() {
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
