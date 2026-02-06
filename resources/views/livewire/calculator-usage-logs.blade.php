<?php

use Livewire\Volt\Component;
use App\Models\CalculatorUsageLog;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $calculatorType = '';

    public function with()
    {
        return [
            'logs' => CalculatorUsageLog::query()
                ->with('user:id,name,email,riscoin_id')
                ->when($this->search, fn($q) =>
                    $q->whereHas('user', fn($uq) =>
                        $uq->where('name', 'like', '%' . $this->search . '%')
                           ->orWhere('email', 'like', '%' . $this->search . '%')
                           ->orWhere('riscoin_id', 'like', '%' . $this->search . '%')
                    )
                )
                ->when($this->calculatorType, fn($q) =>
                    $q->where('calculator_type', $this->calculatorType)
                )
                ->latest()
                ->paginate($this->perPage)
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}; ?>

<div>
    <x-page-header :title="__('Calculator Usage Logs')" :description="__('Track compound interest calculator usage')" />

    <div class="mx-auto max-w-8xl space-y-6 p-6">
        <!-- Stats Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Calculations</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ \App\Models\CalculatorUsageLog::count() }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Unique Users</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ \App\Models\CalculatorUsageLog::distinct('user_id')->count('user_id') }}</p>
                    </div>
                    <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Today's Usage</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ \App\Models\CalculatorUsageLog::whereDate('created_at', today())->count() }}</p>
                    </div>
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg. Investment</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white">${{ number_format(\App\Models\CalculatorUsageLog::avg('invested_amount') ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-full bg-amber-100 p-3 dark:bg-amber-900">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-4">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by user name, email, or Riscoin ID..." class="max-w-md" icon="magnifying-glass" />

                <flux:select wire:model.live="perPage" class="w-24">
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </flux:select>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                User
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Invested Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Final Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Days
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Signals/Day
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                First Time
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @forelse ($logs as $log)
                            <tr class="transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $log->user->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $log->user->email }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">ID: {{ $log->user->riscoin_id }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                    ${{ number_format($log->invested_amount, 2) }}
                                    @if($log->first_reward > 0)
                                        <div class="text-xs text-green-600 dark:text-green-400">+${{ number_format($log->first_reward, 2) }} reward</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-green-600 dark:text-green-400">
                                    @if($log->final_amount)
                                        ${{ number_format($log->final_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                    {{ $log->number_of_days }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                    {{ $log->signals_per_day }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @if($log->is_first_time)
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            Yes
                                        </span>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">No</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $log->created_at->format('M d, Y') }}
                                    <div class="text-xs">{{ $log->created_at->format('h:i A') }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-4 h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $search ? 'No logs found matching your search.' : 'No calculator usage logs yet.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($logs->hasPages())
                <div class="border-t border-zinc-200 bg-white px-6 py-4 dark:border-zinc-700 dark:bg-zinc-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
