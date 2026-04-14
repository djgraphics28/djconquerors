<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;

new class extends Component {
    public string $filter = 'today';
    public string $startDate = '';
    public string $endDate = '';
    public array $newInvestorsCounts = [];
    public array $topInvitersByFilter = [];
    public array $chartLabels = [];
    public array $chartValues = [];
    public bool $showCustomRange = false;
    public string $chartType = 'line';
    public int $topInvitersPage = 1;
    public int $topInvitersPerPage = 10;
    public bool $topInvitersHasMore = false;

    protected const PERIODS = [
        'today' => 'today',
        'week' => 'week',
        'month' => 'month',
        'year' => 'year',
        'custom' => 'custom'
    ];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->calculateAnalytics();
    }

    public function loadMoreInviters(): void
    {
        $this->topInvitersPage++;
        $all = $this->topInvitersByFilter[$this->filter] ?? [];
        $this->topInvitersHasMore = count($all) > ($this->topInvitersPage * $this->topInvitersPerPage);
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

        $this->calculateAnalytics();
    }

    public function applyCustomRange(): void
    {
        $this->filter = 'custom';
        $this->calculateAnalytics();
    }

    public function toggleChartType(): void
    {
        $this->chartType = $this->chartType === 'line' ? 'bar' : 'line';
        // Chart data remains the same, just type changes
    }

    private function calculateAnalytics(): void
    {
        foreach (self::PERIODS as $period) {
            $dateRange = $this->getDateRangeForPeriod($period);

            $this->newInvestorsCounts[$period] = $this->getNewInvestorsCount($dateRange);
            $this->topInvitersByFilter[$period] = $this->getTopInviters($dateRange);
        }

        // Reset pagination when period changes
        $this->topInvitersPage = 1;
        $all = $this->topInvitersByFilter[$this->filter] ?? [];
        $this->topInvitersHasMore = count($all) > $this->topInvitersPerPage;

        $this->generateChartData();
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
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ]
        };
    }

    private function getNewInvestorsCount(array $dateRange): int
    {
        return User::query()
            ->whereNotNull('date_joined')
            ->whereBetween('date_joined', [
                $dateRange[0]->toDateString(),
                $dateRange[1]->toDateString()
            ])
            ->count();
    }

    private function getTopInviters(array $dateRange): array
    {
        $topInviters = User::query()
            ->whereNotNull('date_joined')
            ->whereBetween('date_joined', [
                $dateRange[0]->toDateString(),
                $dateRange[1]->toDateString()
            ])
            ->whereNotNull('inviters_code')
            ->selectRaw('inviters_code, COUNT(*) as invites_count')
            ->groupBy('inviters_code')
            ->orderByDesc('invites_count')
            // ->limit(5)
            ->get();

        return $topInviters->map(function ($row) {
            $inviter = User::where('riscoin_id', $row->inviters_code)->first();

            return [
                'riscoin_id' => $row->inviters_code,
                'name' => $inviter?->name ?? $row->inviters_code,
                'avatar' => $inviter?->getFirstMediaUrl('avatar') ?? $this->getDefaultAvatar(),
                'invites_count' => (int) $row->invites_count,
            ];
        })->toArray();
    }

    private function generateChartData(): void
    {
        $dateRange = $this->getDateRangeForPeriod($this->filter);
        $startDate = $dateRange[0];
        $endDate = $dateRange[1];

        $this->chartLabels = [];
        $this->chartValues = [];

        // Generate data points based on filter period
        if ($this->filter === 'year') {
            // Monthly data for year view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                $count = User::whereNotNull('date_joined')
                    ->whereBetween('date_joined', [
                        $monthStart->toDateString(),
                        $monthEnd->toDateString()
                    ])
                    ->count();

                $this->chartLabels[] = $monthStart->format('M Y');
                $this->chartValues[] = $count;

                $current->addMonth();
            }
        } elseif ($this->filter === 'month') {
            // Daily data for month view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dayStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();

                $count = User::whereNotNull('date_joined')
                    ->whereBetween('date_joined', [
                        $dayStart->toDateString(),
                        $dayEnd->toDateString()
                    ])
                    ->count();

                $this->chartLabels[] = $dayStart->format('d M');
                $this->chartValues[] = $count;

                $current->addDay();
            }
        } elseif ($this->filter === 'week') {
            // Daily data for week view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dayStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();

                $count = User::whereNotNull('date_joined')
                    ->whereBetween('date_joined', [
                        $dayStart->toDateString(),
                        $dayEnd->toDateString()
                    ])
                    ->count();

                $this->chartLabels[] = $dayStart->format('D d');
                $this->chartValues[] = $count;

                $current->addDay();
            }
        } else {
            // For today or custom range, show daily data
            $diffDays = $startDate->diffInDays($endDate);

            if ($diffDays <= 31) {
                // Show daily data for up to 31 days
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $dayStart = $current->copy()->startOfDay();
                    $dayEnd = $current->copy()->endOfDay();

                    $count = User::whereNotNull('date_joined')
                        ->whereBetween('date_joined', [
                            $dayStart->toDateString(),
                            $dayEnd->toDateString()
                        ])
                        ->count();

                    $this->chartLabels[] = $dayStart->format('d M');
                    $this->chartValues[] = $count;

                    $current->addDay();
                }
            } else {
                // Show weekly data for longer periods
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $weekStart = $current->copy()->startOfWeek();
                    $weekEnd = $current->copy()->endOfWeek();

                    if ($weekEnd > $endDate) {
                        $weekEnd = $endDate->copy();
                    }

                    $count = User::whereNotNull('date_joined')
                        ->whereBetween('date_joined', [
                            $weekStart->toDateString(),
                            $weekEnd->toDateString()
                        ])
                        ->count();

                    $this->chartLabels[] = $weekStart->format('d M');
                    $this->chartValues[] = $count;

                    $current->addWeek();
                }
            }
        }
    }

    private function getDefaultAvatar(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function getCurrentCountProperty(): int
    {
        return $this->newInvestorsCounts[$this->filter] ?? 0;
    }

    public function getCurrentTopInvitersProperty(): array
    {
        $all = $this->topInvitersByFilter[$this->filter] ?? [];
        return array_slice($all, 0, $this->topInvitersPage * $this->topInvitersPerPage);
    }

    public function getMaxInvitesProperty(): int
    {
        // Use the full unsliced list so progress bars are always relative to the true max
        $all = $this->topInvitersByFilter[$this->filter] ?? [];
        if (empty($all)) {
            return 1;
        }

        return max(array_column($all, 'invites_count'));
    }

    public function calculateProgress(int $invitesCount): float
    {
        if ($this->maxInvites <= 0) {
            return 0;
        }

        $percentage = ($invitesCount / $this->maxInvites) * 100;
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

    public function getTotalDaysProperty(): int
    {
        $dateRange = $this->getDateRangeForPeriod($this->filter);
        return $dateRange[0]->diffInDays($dateRange[1]) + 1;
    }

    public function getAverageDailyProperty(): float
    {
        if ($this->totalDays <= 0) {
            return 0;
        }
        return round($this->currentCount / $this->totalDays, 1);
    }

    public function getPeakValueProperty(): int
    {
        return empty($this->chartValues) ? 0 : max($this->chartValues);
    }
};

?>

<div>
    {{-- Widget Header --}}
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-5 mb-5 shadow-lg">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 rounded-xl p-2.5">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white leading-tight">New Investors Analytics</h3>
                    <p class="text-blue-200 text-xs">{{ $this->dateRangeLabel }}</p>
                </div>
            </div>
            <div class="flex-shrink-0 text-right">
                <div class="text-2xl font-bold text-white">{{ number_format($this->currentCount) }}</div>
                <div class="text-blue-200 text-xs">this period</div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        {{-- Period Filter Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                {{-- Quick Period Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year', 'custom' => 'Custom'] as $period => $label)
                        <button wire:click="changePeriod('{{ $period }}')"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all duration-150 {{ $this->filter === $period ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                            {{ $label }}
                        </button>
                    @endforeach

                    {{-- Chart Type Toggle --}}
                    <button wire:click="toggleChartType"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all duration-150">
                        @if($chartType === 'line')
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            Bar view
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Line view
                        @endif
                    </button>
                </div>

                {{-- Custom Date Range --}}
                @if($showCustomRange)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/40 p-3 rounded-xl w-full sm:w-auto">
                        <div class="flex items-center gap-2 flex-wrap">
                            <input type="date" wire:model.live="startDate"
                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            <span class="text-xs text-gray-500 dark:text-gray-400">to</span>
                            <input type="date" wire:model.live="endDate"
                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <button wire:click="applyCustomRange"
                            class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-xs font-semibold whitespace-nowrap shadow-sm">
                            Apply
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Stats Summary --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($this->currentCount) }}</div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">New Investors</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 bg-violet-100 dark:bg-violet-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->averageDaily }}</div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Avg / Day</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->peakValue }}</div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">Peak Day</div>
            </div>
        </div>

        {{-- Chart Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Investor Trend</h4>
                </div>
                <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-700/60 px-2 py-1 rounded-md border border-gray-100 dark:border-gray-700">
                    {{ count($chartLabels) }} data points
                </span>
            </div>

            <div
                wire:ignore
                x-data="{
                    chart: null,
                    isDark() {
                        return document.documentElement.classList.contains('dark');
                    },
                    buildOptions(values, labels, type) {
                        const dark = this.isDark();
                        const isBar = type === 'bar';
                        return {
                            chart: {
                                type: isBar ? 'bar' : 'area',
                                height: 220,
                                toolbar: { show: false },
                                zoom: { enabled: false },
                                animations: { enabled: true, speed: 400 },
                                background: 'transparent',
                                foreColor: dark ? '#9ca3af' : '#6b7280',
                            },
                            theme: { mode: dark ? 'dark' : 'light' },
                            series: [{ name: 'Investors', data: values }],
                            xaxis: {
                                categories: labels,
                                labels: {
                                    rotate: -30,
                                    style: { fontSize: '11px', colors: dark ? '#9ca3af' : '#6b7280' },
                                    formatter: (val, i, opts) => {
                                        const total = labels.length;
                                        const step = Math.max(1, Math.ceil(total / 8));
                                        if (typeof i === 'number') return i % step === 0 ? val : '';
                                        return val;
                                    }
                                },
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                tooltip: { enabled: false },
                            },
                            yaxis: {
                                labels: {
                                    style: { fontSize: '11px', colors: dark ? '#9ca3af' : '#6b7280' },
                                    formatter: v => Math.round(v)
                                }
                            },
                            grid: {
                                borderColor: dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
                                strokeDashArray: 4,
                                padding: { left: 0, right: 0 }
                            },
                            fill: isBar ? { type: 'gradient', gradient: { shade: 'light', type: 'vertical', shadeIntensity: 0.3, opacityFrom: 1, opacityTo: 0.8 } } : { type: 'gradient', gradient: { shade: dark ? 'dark' : 'light', type: 'vertical', shadeIntensity: 0.5, opacityFrom: 0.35, opacityTo: 0.0 } },
                            stroke: isBar ? { show: false } : { curve: 'smooth', width: 2.5 },
                            colors: ['#6366f1'],
                            dataLabels: { enabled: false },
                            tooltip: {
                                theme: dark ? 'dark' : 'light',
                                y: { formatter: v => v + ' investor' + (v !== 1 ? 's' : '') }
                            },
                            plotOptions: isBar ? {
                                bar: { borderRadius: 4, columnWidth: '60%' }
                            } : {},
                            markers: isBar ? {} : { size: 3, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 5 } },
                        };
                    },
                    init() {
                        this.chart = new ApexCharts(this.$refs.apex, this.buildOptions(
                            @js($chartValues),
                            @js($chartLabels),
                            @js($chartType)
                        ));
                        this.chart.render();

                        this.$watch(() => JSON.stringify([$wire.chartValues, $wire.chartLabels, $wire.chartType]), () => {
                            if (this.chart) {
                                this.chart.destroy();
                                this.chart = null;
                            }
                            this.chart = new ApexCharts(this.$refs.apex, this.buildOptions(
                                this.$wire.chartValues,
                                this.$wire.chartLabels,
                                this.$wire.chartType
                            ));
                            this.chart.render();
                        });
                    }
                }"
            >
                <div x-ref="apex"></div>
            </div>
        </div>

        {{-- Bottom row: Top Inviters + Period Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Top Inviters --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Top Inviters</h4>
                    <span class="text-xs text-gray-400 bg-gray-50 dark:bg-gray-700/60 dark:text-gray-500 px-2 py-1 rounded-md border border-gray-100 dark:border-gray-700">
                        {{ count($this->currentTopInviters) }} referrers
                    </span>
                </div>

                @if(empty($this->currentTopInviters))
                    <div class="flex flex-col items-center justify-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No inviters this period</p>
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
                                        $wire.loadMoreInviters().then(() => {
                                            this.$nextTick(() => this.setupObserver());
                                        });
                                    }
                                }, { root: this.$el, threshold: 0.1 });
                                this.observer.observe(sentinel);
                            }
                        }"
                    >
                        @foreach($this->currentTopInviters as $index => $inviter)
                            @php
                                $rankColors = [
                                    0 => ['badge' => 'bg-gradient-to-br from-yellow-400 to-amber-500', 'bar' => 'from-yellow-400 to-amber-500', 'text' => 'text-amber-600 dark:text-amber-400'],
                                    1 => ['badge' => 'bg-gradient-to-br from-gray-300 to-gray-400', 'bar' => 'from-gray-400 to-gray-500', 'text' => 'text-gray-600 dark:text-gray-400'],
                                    2 => ['badge' => 'bg-gradient-to-br from-orange-400 to-amber-600', 'bar' => 'from-orange-400 to-amber-600', 'text' => 'text-orange-600 dark:text-orange-400'],
                                ];
                                $rank = $rankColors[$index] ?? ['badge' => 'bg-gray-100 dark:bg-gray-600', 'bar' => 'from-indigo-400 to-blue-500', 'text' => 'text-indigo-600 dark:text-indigo-400'];
                            @endphp
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors duration-150">
                                {{-- Rank --}}
                                <div class="flex-shrink-0 w-7 h-7 {{ $rank['badge'] }} rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                    {{ $index < 3 ? ['🥇','🥈','🥉'][$index] : $index + 1 }}
                                </div>

                                {{-- Avatar --}}
                                <img src="{{ $inviter['avatar'] }}" alt="{{ $inviter['name'] }}"
                                    class="w-9 h-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-700 flex-shrink-0"
                                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGNsYXNzPSJoLTEwIHctMTAiIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3Ryb2tlPSJjdXJyZW50Q29sb3IiPjxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMTYgN2E0IDQgMCAxMS04IDAgNCA0IDAgMDE4IDB6TTEyIDE0YTcgNyAwIDAwLTcgN2gxNGE3IDcgMCAwMC03LTd6IiAvPjwvc3ZnPg=='" />

                                {{-- Info + bar --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">{{ $inviter['name'] }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $inviter['riscoin_id'] }}</p>
                                        </div>
                                        <span class="text-sm font-bold {{ $rank['text'] }} ml-2 whitespace-nowrap">{{ $inviter['invites_count'] }}</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r {{ $rank['bar'] }} transition-all duration-500"
                                            style="width: {{ $this->calculateProgress($inviter['invites_count']) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- IntersectionObserver sentinel --}}
                        @if($topInvitersHasMore)
                            <div x-ref="sentinel" class="flex items-center justify-center py-3 gap-2 text-xs text-gray-400 dark:text-gray-500">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Loading more…
                            </div>
                        @else
                            <div class="py-2 text-center text-xs text-gray-400 dark:text-gray-500">
                                All {{ count($this->currentTopInviters) }} inviters loaded
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Period Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4">Period Summary</h4>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800/30">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Duration</span>
                        </div>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $this->totalDays }} {{ $this->totalDays === 1 ? 'day' : 'days' }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Start</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ Carbon::parse($startDate)->format('M d, Y') }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">End</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ Carbon::parse($endDate)->format('M d, Y') }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Total Investors</span>
                        </div>
                        <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($this->currentCount) }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-violet-50 dark:bg-violet-900/20 rounded-xl border border-violet-100 dark:border-violet-800/30">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Avg / Day</span>
                        </div>
                        <span class="text-sm font-bold text-violet-600 dark:text-violet-400">{{ $this->averageDaily }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
