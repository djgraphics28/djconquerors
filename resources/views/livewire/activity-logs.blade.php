<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    use WithPagination;

    public string $search   = '';
    public string $dateFrom = '';
    public string $dateTo   = '';
    public string $event    = '';
    public int    $perPage  = 20;

    protected $queryString = [
        'search'   => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo'   => ['except' => ''],
        'event'    => ['except' => ''],
    ];

    public function updatedSearch():   void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo():   void { $this->resetPage(); }
    public function updatedEvent():    void { $this->resetPage(); }
    public function updatedPerPage():  void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo', 'event');
        $this->resetPage();
    }

    public function getActivitiesProperty()
    {
        return Activity::with('causer:id,name')
            ->when($this->search, fn($q) =>
                $q->where('description', 'like', '%'.$this->search.'%')
                  ->orWhereHas('causer', fn($c) =>
                      $c->where('name', 'like', '%'.$this->search.'%')
                  )
            )
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->event,    fn($q) => $q->where('event', $this->event))
            ->latest()
            ->paginate($this->perPage);
    }

    public function getHasActiveFiltersProperty(): bool
    {
        return (bool) ($this->search || $this->dateFrom || $this->dateTo || $this->event);
    }
};

?>

<div class="space-y-5">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Activity Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Monitor all actions taken across the system</p>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search description or user..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Event Type --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Event Type</label>
                <select wire:model.live="event"
                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="">All Events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>

            {{-- Per Page --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Per Page</label>
                <select wire:model.live="perPage"
                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">From Date</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            {{-- Date To --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">To Date</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            {{-- Clear Filters --}}
            @if($this->hasActiveFilters)
            <div class="flex items-end">
                <button wire:click="clearFilters"
                    class="w-full px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                    Clear Filters
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Gradient Header --}}
        <div class="px-5 pt-5 pb-4 bg-gradient-to-r from-violet-500 via-indigo-500 to-blue-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-white">System Activity Log</h3>
                        <p class="text-xs text-white/70">{{ $this->activities->total() }} records found</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Performed By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Changes</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Previous Values</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->activities as $activity)
                        @php
                            $event = $activity->event ?? $activity->description;
                            $eventColor = match($event) {
                                'created' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'updated' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'deleted' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                default   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            {{-- Action --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $eventColor }}">
                                        {{ ucfirst($event) }}
                                    </span>
                                </div>
                                @if($activity->subject_type)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ class_basename($activity->subject_type) }}</p>
                                @endif
                                @if($activity->description && $activity->description !== $event)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ ucfirst($activity->description) }}</p>
                                @endif
                            </td>

                            {{-- Performed By --}}
                            <td class="px-5 py-4">
                                @if($activity->causer)
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $activity->causer->name }}</p>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">System</span>
                                @endif
                            </td>

                            {{-- Changes (new values) --}}
                            <td class="px-5 py-4 max-w-xs">
                                @if($activity->properties?->has('attributes'))
                                    <div class="space-y-0.5">
                                        @foreach(array_slice($activity->properties->get('attributes', []), 0, 5) as $key => $value)
                                            @if(!in_array($key, ['password', 'remember_token']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                </div>
                                            @endif
                                        @endforeach
                                        @if(count($activity->properties->get('attributes', [])) > 5)
                                            <span class="text-xs text-indigo-500">+{{ count($activity->properties->get('attributes', [])) - 5 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            {{-- Previous Values --}}
                            <td class="px-5 py-4 max-w-xs">
                                @if($activity->properties?->has('old'))
                                    <div class="space-y-0.5">
                                        @foreach(array_slice($activity->properties->get('old', []), 0, 5) as $key => $value)
                                            @if(!in_array($key, ['password', 'remember_token']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                    {{ is_array($value) ? json_encode($value) : $value }}
                                                </div>
                                            @endif
                                        @endforeach
                                        @if(count($activity->properties->get('old', [])) > 5)
                                            <span class="text-xs text-indigo-500">+{{ count($activity->properties->get('old', [])) - 5 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            {{-- Timestamp --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-800 dark:text-gray-200">{{ $activity->created_at->format('M j, Y') }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $activity->created_at->format('g:i A') }}</p>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">No activity records found</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    @if($this->hasActiveFilters) Try adjusting your filters @else No activity has been recorded yet @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($this->activities->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $this->activities->links() }}
            </div>
        @endif
    </div>
</div>
