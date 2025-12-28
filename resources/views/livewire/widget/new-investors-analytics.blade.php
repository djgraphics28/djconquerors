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
            ->limit(5)
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
        return $this->topInvitersByFilter[$this->filter] ?? [];
    }

    public function getMaxInvitesProperty(): int
    {
        $topInviters = $this->currentTopInviters;
        if (empty($topInviters)) {
            return 1;
        }

        return max(array_column($topInviters, 'invites_count'));
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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="space-y-6">
            <!-- Header with Title and Date Range -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">New Investors Analytics</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Period: <span class="font-medium text-blue-600 dark:text-blue-400">{{ $this->dateRangeLabel }}</span>
                    </p>
                </div>

                <!-- Date Range Filter -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <!-- Quick Period Buttons -->
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach(['today', 'week', 'month', 'year', 'custom'] as $period)
                            <button
                                wire:click="changePeriod('{{ $period }}')"
                                class="px-3 py-1.5 text-sm rounded-full border transition-colors duration-200 {{ $this->filter === $period ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                            >
                                {{ ucfirst($period) }}
                            </button>
                        @endforeach

                        <!-- Chart Type Toggle -->
                        <button
                            wire:click="toggleChartType"
                            class="px-3 py-1.5 text-sm rounded-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                            title="Toggle chart type"
                        >
                            @if($chartType === 'line')
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                Bar
                            @else
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Line
                            @endif
                        </button>
                    </div>

                    <!-- Custom Date Range Input -->
                    @if($showCustomRange)
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg w-full sm:w-auto">
                            <div class="flex items-center gap-2">
                                <input
                                    type="date"
                                    wire:model.live="startDate"
                                    class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                                />
                                <span class="text-gray-500 dark:text-gray-400">to</span>
                                <input
                                    type="date"
                                    wire:model.live="endDate"
                                    class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full"
                                />
                            </div>
                            <button
                                wire:click="applyCustomRange"
                                class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap mt-2 sm:mt-0"
                            >
                                Apply Range
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-4 rounded-xl border border-blue-200 dark:border-blue-700/30">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                        {{ number_format($this->currentCount) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium">New Investors</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">During selected period</div>
                </div>

                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800/50 dark:to-gray-700/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="text-3xl font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ $this->averageDaily }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium">Avg Daily</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Per day average</div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 p-4 rounded-xl border border-green-200 dark:border-green-700/30">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">
                        {{ $this->peakValue }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300 font-medium">Peak Day</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Highest in period</div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">New Investors Trend</h4>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ count($chartLabels) }} data points
                    </div>
                </div>

                <!-- Simple Chart using HTML/CSS -->
                @if(!empty($chartValues))
                    <div class="relative h-64">
                        <div class="absolute inset-0 flex items-end">
                            <!-- Chart bars/line -->
                            @if($chartType === 'bar')
                                <!-- Bar Chart -->
                                <div class="flex items-end justify-between w-full h-48 px-2">
                                    @foreach($chartValues as $index => $value)
                                        @php
                                            $maxValue = max($chartValues);
                                            $height = $maxValue > 0 ? ($value / $maxValue) * 100 : 0;
                                            $isToday = $chartLabels[$index] === now()->format('d M');
                                        @endphp
                                        <div class="flex flex-col items-center flex-1 mx-1">
                                            <div
                                                class="w-full bg-gradient-to-t from-blue-500 to-blue-600 rounded-t-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300 cursor-pointer relative group"
                                                style="height: {{ $height }}%"
                                                title="{{ $chartLabels[$index] }}: {{ $value }} investors"
                                            >
                                                @if($isToday)
                                                    <div class="absolute -top-6 left-1/2 transform -translate-x-1/2 text-xs font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                                        Today
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center truncate w-full">
                                                {{ $chartLabels[$index] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <!-- Line Chart -->
                                <div class="relative w-full h-48 px-2">
                                    <!-- Grid lines -->
                                    <div class="absolute inset-0 flex flex-col justify-between">
                                        @for($i = 0; $i <= 4; $i++)
                                            <div class="border-t border-gray-200 dark:border-gray-700"></div>
                                        @endfor
                                    </div>

                                    <!-- Line path -->
                                    <svg class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                        <path
                                            d="
                                                @php
                                                    $maxValue = max($chartValues);
                                                    $pointCount = count($chartValues);
                                                    $path = '';
                                                    foreach($chartValues as $index => $value) {
                                                        $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                                        $y = $maxValue > 0 ? 100 - ($value / $maxValue) * 100 : 100;
                                                        if($index === 0) {
                                                            $path .= "M {$x} {$y} ";
                                                        } else {
                                                            $path .= "L {$x} {$y} ";
                                                        }
                                                    }
                                                    echo $path;
                                                @endphp
                                            "
                                            fill="none"
                                            stroke="url(#lineGradient)"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                        <defs>
                                            <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#3b82f6" />
                                                <stop offset="100%" stop-color="#8b5cf6" />
                                            </linearGradient>
                                        </defs>
                                    </svg>

                                    <!-- Data points -->
                                    @foreach($chartValues as $index => $value)
                                        @php
                                            $maxValue = max($chartValues);
                                            $pointCount = count($chartValues);
                                            $x = $pointCount > 1 ? ($index / ($pointCount - 1)) * 100 : 50;
                                            $y = $maxValue > 0 ? 100 - ($value / $maxValue) * 100 : 100;
                                            $isToday = $chartLabels[$index] === now()->format('d M');
                                        @endphp
                                        <div
                                            class="absolute w-3 h-3 -ml-1.5 -mt-1.5 rounded-full bg-blue-600 border-2 border-white dark:border-gray-800 shadow-sm cursor-pointer group"
                                            style="left: {{ $x }}%; top: {{ $y }}%;"
                                            title="{{ $chartLabels[$index] }}: {{ $value }} investors"
                                        >
                                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                                {{ $chartLabels[$index] }}: {{ $value }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Y-axis labels -->
                        <div class="absolute left-0 top-0 h-48 flex flex-col justify-between text-xs text-gray-500 dark:text-gray-400 pr-2">
                            @php
                                $maxValue = max($chartValues);
                                $steps = 4;
                            @endphp
                            @for($i = 0; $i <= $steps; $i++)
                                <div>{{ round($maxValue * ($steps - $i) / $steps) }}</div>
                            @endfor
                        </div>
                    </div>
                @else
                    <div class="h-48 flex items-center justify-center bg-gray-50/50 dark:bg-gray-800/50 rounded-lg">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p>No data available for selected period</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Top Inviters Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">Top Inviters</h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ count($this->currentTopInviters) }} active referrers
                        </span>
                    </div>

                    @if(empty($this->currentTopInviters))
                        <div class="text-gray-500 dark:text-gray-400 text-center py-8 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0h-15" />
                            </svg>
                            <p>No inviters for this period</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($this->currentTopInviters as $index => $inviter)
                                <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                    <!-- Rank Badge -->
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full {{ $index < 3 ? 'bg-gradient-to-br from-yellow-500 to-yellow-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }} font-bold mr-3 text-sm">
                                        {{ $index + 1 }}
                                    </div>

                                    <!-- Avatar -->
                                    <img src="{{ $inviter['avatar'] }}"
                                         alt="{{ $inviter['name'] }}"
                                         class="w-10 h-10 rounded-full mr-3 object-cover border-2 border-white dark:border-gray-600 shadow-sm"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGNsYXNzPSJoLTEwIHctMTAiIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3Ryb2tlPSJjdXJyZW50Q29sb3IiPjxwYXRoIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLXdpZHRoPSIyIiBkPSJNMTYgN2E0IDQgMCAxMS04IDAgNCA0IDAgMDE4IDB6TTEyIDE0YTcgNyAwIDAwLTcgN2gxNGE3IDcgMCAwMC03LTd6IiAvPjwvc3ZnPg=='" />

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <div>
                                                <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm truncate">
                                                    {{ $inviter['name'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    ID: {{ $inviter['riscoin_id'] }}
                                                </div>
                                            </div>
                                            <div class="text-base font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap ml-2">
                                                {{ $inviter['invites_count'] }}
                                                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">invites</span>
                                            </div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="mt-1 bg-gray-200 dark:bg-gray-600 h-1.5 rounded-full overflow-hidden">
                                            <div class="h-1.5 bg-gradient-to-r {{ $index < 3 ? 'from-yellow-500 to-orange-500' : 'from-blue-500 to-blue-600' }} rounded-full transition-all duration-500"
                                                 style="width: {{ $this->calculateProgress($inviter['invites_count']) }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Period Details -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-4">Period Details</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-300">Period Duration</div>
                            <div class="text-base font-semibold text-blue-600 dark:text-blue-400">
                                {{ $this->totalDays }} {{ $this->totalDays === 1 ? 'day' : 'days' }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-800/30 dark:to-gray-700/30 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-300">Start Date</div>
                            <div class="text-base font-semibold text-gray-700 dark:text-gray-300">
                                {{ Carbon::parse($startDate)->format('M d, Y') }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-800/30 dark:to-gray-700/30 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-300">End Date</div>
                            <div class="text-base font-semibold text-gray-700 dark:text-gray-300">
                                {{ Carbon::parse($endDate)->format('M d, Y') }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100/50 dark:from-green-900/20 dark:to-green-800/20 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-300">Total Invites</div>
                            <div class="text-base font-semibold text-green-600 dark:text-green-400">
                                {{ $this->currentCount }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
