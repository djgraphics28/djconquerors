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
            ->orderByDesc('assists_count')
            ->take(10)
            ->get();

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
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Top Assisters</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Period: <span class="font-medium text-blue-600 dark:text-blue-400">{{ $this->dateRangeLabel }}</span>
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach(['today', 'week', 'month', 'year', 'custom'] as $period)
                            <button
                                wire:click="changePeriod('{{ $period }}')"
                                class="px-3 py-1.5 text-sm rounded-full border transition-colors duration-200 {{ $this->filter === $period ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}"
                            >
                                {{ ucfirst($period) }}
                            </button>
                        @endforeach
                    </div>

                    @if($showCustomRange)
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg w-full sm:w-auto">
                            <div class="flex items-center gap-2">
                                <input type="date" wire:model.live="startDate" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full" />
                                <span class="text-gray-500 dark:text-gray-400">to</span>
                                <input type="date" wire:model.live="endDate" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-full" />
                            </div>
                            <button wire:click="applyCustomRange" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium whitespace-nowrap mt-2 sm:mt-0">Apply Range</button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">Top Assisters</h4>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($topAssisters) }} assisters found</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                            {{ $this->totalAssists }} total assists
                        </span>
                    </div>
                </div>

                @if(empty($topAssisters))
                    <div class="text-gray-500 dark:text-gray-400 text-center py-8 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                        No assisters found for this period
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($topAssisters as $index => $assister)
                            <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full {{ $index < 3 ? 'bg-gradient-to-br from-yellow-500 to-yellow-600 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }} font-bold mr-3 text-sm">
                                    {{ $index + 1 }}
                                </div>

                                <img
                                    src="{{ $assister['avatar'] }}"
                                    alt="{{ $assister['name'] }}"
                                    class="w-10 h-10 rounded-full mr-3 object-cover border-2 border-white dark:border-gray-600 shadow-sm"
                                    onerror="this.src='{{ $this->getDefaultAvatar() }}'"
                                />

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <div>
                                            <div class="font-semibold text-gray-700 dark:text-gray-300 text-sm truncate">
                                                {{ $assister['name'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                ID: {{ $assister['riscoin_id'] ?? $assister['id'] }}
                                            </div>
                                        </div>
                                        <div class="text-base font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap ml-2">
                                            {{ $assister['assists_count'] }}
                                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">assists</span>
                                        </div>
                                    </div>

                                    <div class="mt-1 bg-gray-200 dark:bg-gray-600 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-1.5 bg-gradient-to-r {{ $index < 3 ? 'from-yellow-500 to-orange-500' : 'from-blue-500 to-blue-600' }} rounded-full transition-all duration-500"
                                             style="width: {{ $this->calculateProgress($assister['assists_count']) }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
