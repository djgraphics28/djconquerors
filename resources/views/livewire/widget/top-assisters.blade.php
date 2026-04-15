<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;

new class extends Component {
    public string $filter = 'today';
    public string $startDate = '';
    public string $endDate = '';
    public array $topAssisters = [];
    public bool $showCustomRange = false;
    public int $topAssistersPage = 1;
    public int $topAssistersPerPage = 10;
    public bool $topAssistersHasMore = false;

    protected const PERIODS = [
        'today' => 'today',
        'week' => 'week',
        'month' => 'month',
        'year' => 'year',
        'custom' => 'custom'
    ];

    public function mount(): void
    {
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadTopAssisters();
    }

    public function loadMoreAssisters(): void
    {
        $this->topAssistersPage++;
        $this->topAssistersHasMore = count($this->topAssisters) > ($this->topAssistersPage * $this->topAssistersPerPage);
    }

    public function changePeriod(string $period): void
    {
        $this->filter = $period;

        if ($period !== 'custom') {
            $this->showCustomRange = false;
            $dateRange = $this->getDateRangeForPeriod($period);
            $this->startDate = $dateRange[0]->format('Y-m-d');
            $this->endDate = $dateRange[1]->format('Y-m-d');
        } else {
            $this->showCustomRange = true;
        }

        $this->loadTopAssisters();
    }

    public function applyCustomRange(): void
    {
        $this->filter = 'custom';
        $this->loadTopAssisters();
    }

    public function updatedStartDate(): void
    {
        if ($this->filter === 'custom') {
            $this->loadTopAssisters();
        }
    }

    public function updatedEndDate(): void
    {
        if ($this->filter === 'custom') {
            $this->loadTopAssisters();
        }
    }

    private function loadTopAssisters(): void
    {
        $dateRange = $this->getDateRangeForPeriod($this->filter);
        $this->topAssisters = $this->getTopAssisters($dateRange);
        $this->topAssistersPage = 1;
        $this->topAssistersHasMore = count($this->topAssisters) > $this->topAssistersPerPage;
    }

    private function getDateRangeForPeriod(string $period): array
    {
        $now = now();

        return match($period) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay()
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek()
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth()
            ],
            'year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ],
            'custom' => [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay()
            ]
        };
    }

    private function getTopAssisters(array $dateRange): array
    {
        $start = $dateRange[0]->toDateString();
        $end = $dateRange[1]->toDateString();

        $top = User::query()
            ->whereHas('assistedUsers', function($q) use ($start, $end) {
                $q->whereBetween('date_joined', [$start, $end]);
            })
            ->withCount(['assistedUsers as assists_count' => function($q) use ($start, $end) {
                $q->whereBetween('date_joined', [$start, $end]);
            }])
            ->with('media')
            ->orderByDesc('assists_count')
            ->get(['id', 'name', 'riscoin_id']);

        return $top->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'riscoin_id' => $user->riscoin_id ?? null,
                'avatar' => $user->getFirstMediaUrl('avatar') ?? $this->getDefaultAvatar(),
                'assists_count' => (int) $user->assists_count,
            ];
        })->toArray();
    }

    private function getDefaultAvatar(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function getCurrentTopAssistersProperty(): array
    {
        return array_slice($this->topAssisters, 0, $this->topAssistersPage * $this->topAssistersPerPage);
    }

    public function getMaxAssistsProperty(): int
    {
        if (empty($this->topAssisters)) {
            return 1;
        }

        return max(array_column($this->topAssisters, 'assists_count'));
    }

    public function calculateProgress(int $assistsCount): float
    {
        if ($this->maxAssists <= 0) {
            return 0;
        }

        $percentage = ($assistsCount / $this->maxAssists) * 100;
        return min(100, max(0, $percentage));
    }

    public function getDateRangeLabelProperty(): string
    {
        $dateRange = $this->getDateRangeForPeriod($this->filter);
        $start = $dateRange[0];
        $end = $dateRange[1];

        if ($this->filter === 'today') {
            return $start->format('F d, Y');
        } elseif ($this->filter === 'week') {
            return $start->format('M d') . ' - ' . $end->format('M d, Y');
        } elseif ($this->filter === 'month') {
            return $start->format('F Y');
        } elseif ($this->filter === 'year') {
            return $start->format('Y');
        } else {
            return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
        }
    }

    public function getTotalAssistsProperty(): int
    {
        return collect($this->topAssisters)->sum('assists_count');
    }
};

?>

<div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Gradient Header --}}
        <div class="relative px-5 pt-5 pb-4 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-white">Top Assisters</h3>
                        <p class="text-xs text-white/70">{{ $this->dateRangeLabel }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold bg-white/20 text-white px-2.5 py-1 rounded-full backdrop-blur-sm">
                        {{ $this->totalAssists }} assists
                    </span>
                    <span class="text-xs font-medium bg-white/15 text-white/80 px-2.5 py-1 rounded-full backdrop-blur-sm">
                        {{ count($topAssisters) }} referrers
                    </span>
                </div>
            </div>
        </div>

        {{-- Period Filter Bar --}}
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-1.5">
            @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year', 'custom' => 'Custom'] as $period => $label)
                <button
                    wire:click="changePeriod('{{ $period }}')"
                    class="px-3 py-1 text-xs font-medium rounded-full border transition-all duration-150 {{ $filter === $period ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm' : 'bg-gray-50 dark:bg-gray-700/60 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Custom Date Range --}}
        @if($showCustomRange)
            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 flex flex-col sm:flex-row items-start sm:items-center gap-2">
                <input type="date" wire:model.live="startDate" class="px-3 py-1.5 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 w-full sm:w-auto" />
                <span class="text-xs text-gray-400 hidden sm:inline">to</span>
                <input type="date" wire:model.live="endDate" class="px-3 py-1.5 text-xs border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 w-full sm:w-auto" />
                <button wire:click="applyCustomRange" class="px-4 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors text-xs font-medium whitespace-nowrap">Apply</button>
            </div>
        @endif

        {{-- Assisters List --}}
        <div class="p-5">
            @if(empty($this->currentTopAssisters))
                <div class="flex flex-col items-center justify-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No assisters this period</p>
                </div>
            @else
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
                                    $wire.loadMoreAssisters().then(() => {
                                        this.$nextTick(() => this.setupObserver());
                                    });
                                }
                            }, { root: this.$el, threshold: 0.1 });
                            this.observer.observe(sentinel);
                        }
                    }"
                >
                    @foreach($this->currentTopAssisters as $index => $assister)
                        @php
                            $rankColors = [
                                0 => ['badge' => 'bg-gradient-to-br from-yellow-400 to-amber-500', 'bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-amber-600 dark:text-amber-400'],
                                1 => ['badge' => 'bg-gradient-to-br from-gray-300 to-gray-400', 'bar' => 'from-gray-400 to-gray-500', 'text' => 'text-gray-600 dark:text-gray-400'],
                                2 => ['badge' => 'bg-gradient-to-br from-orange-400 to-amber-600', 'bar' => 'from-orange-400 to-amber-600', 'text' => 'text-orange-600 dark:text-orange-400'],
                            ];
                            $rank = $rankColors[$index] ?? ['badge' => 'bg-gray-100 dark:bg-gray-600', 'bar' => 'from-emerald-400 to-teal-500', 'text' => 'text-emerald-600 dark:text-emerald-400'];
                        @endphp
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors duration-150">
                            {{-- Rank --}}
                            <div class="flex-shrink-0 w-7 h-7 {{ $rank['badge'] }} rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                {{ $index < 3 ? ['🥇','🥈','🥉'][$index] : $index + 1 }}
                            </div>

                            {{-- Avatar --}}
                            <img src="{{ $assister['avatar'] }}" alt="{{ $assister['name'] }}"
                                class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 flex-shrink-0"
                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGNsYXNzPSJoLTEwIHctMTAiIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3Ryb2tlPSJjdXJyZW50Q29sb3IiPjxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMTYgN2E0IDQgMCAxMS04IDAgNCA0IDAgMDE4IDB6TTEyIDE0YTcgNyAwIDAwLTcgN2gxNGE3IDcgMCAwMC03LTd6IiAvPjwvc3ZnPg=='" />

                            {{-- Info + bar --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">{{ $assister['name'] }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $assister['riscoin_id'] ?? $assister['id'] }}</p>
                                    </div>
                                    <span class="text-sm font-bold {{ $rank['text'] }} ml-2 whitespace-nowrap">{{ $assister['assists_count'] }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r {{ $rank['bar'] }} transition-all duration-500"
                                        style="width: {{ $this->calculateProgress($assister['assists_count']) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- IntersectionObserver sentinel --}}
                    @if($topAssistersHasMore)
                        <div x-ref="sentinel" class="flex items-center justify-center py-3 gap-2 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Loading more…
                        </div>
                    @else
                        <div class="py-2 text-center text-xs text-gray-400 dark:text-gray-500">
                            All {{ count($this->currentTopAssisters) }} assisters loaded
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
