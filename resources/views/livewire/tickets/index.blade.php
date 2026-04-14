<?php

use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterPriority = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterPriority(): void { $this->resetPage(); }

    public function with(): array
    {
        $isAdmin = auth()->user()->can('tickets.manage');

        $query = Ticket::with(['user', 'assignee'])
            ->when(!$isAdmin, fn($q) => $q->where('user_id', auth()->id()))
            ->when($this->search, fn($q) => $q->where(function ($q2) {
                $q2->where('subject', 'like', '%' . $this->search . '%')
                   ->orWhere('ticket_number', 'like', '%' . $this->search . '%');
            }))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPriority, fn($q) => $q->where('priority', $this->filterPriority))
            ->latest();

        return [
            'tickets' => $query->paginate(15),
            'isAdmin' => $isAdmin,
        ];
    }

    public function quickStatus(int $ticketId, string $status): void
    {
        if (!auth()->user()->can('tickets.manage')) {
            abort(403);
        }

        $ticket = Ticket::findOrFail($ticketId);
        $oldStatus = $ticket->status;

        $ticket->update([
            'status'      => $status,
            'resolved_at' => in_array($status, ['resolved', 'closed']) ? now() : $ticket->resolved_at,
        ]);

        if ($ticket->user_id !== auth()->id()) {
            $ticket->user->notify(new \App\Notifications\TicketStatusUpdated($ticket, $oldStatus));
        }

        session()->flash('message', 'Status updated.');
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ auth()->user()->can('tickets.manage') ? 'Manage Tickets' : 'My Tickets' }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ auth()->user()->can('tickets.manage') ? 'All submitted support tickets' : 'Your submitted support tickets' }}
            </p>
        </div>
        <a href="{{ route('tickets.create') }}" wire:navigate
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Ticket
        </a>
    </div>

    @if (session('message'))
        <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap gap-3 mb-5">
        <div class="flex-1 min-w-[180px]">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tickets…"
                class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"/>
        </div>
        <select wire:model.live="filterStatus"
            class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="pending">Pending</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        </select>
        <select wire:model.live="filterPriority"
            class="px-3 py-2 text-sm border border-gray-200 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        @if ($tickets->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 font-medium">No tickets found</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                    @if ($search || $filterStatus || $filterPriority)
                        Try adjusting your filters
                    @else
                        Submit a new ticket to get help
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Ticket #</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Subject</th>
                            @if ($isAdmin)
                                <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Submitted By</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Assigned To</th>
                            @endif
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Priority</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Created</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                        @foreach ($tickets as $ticket)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $ticket->ticket_number }}
                                </td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100 max-w-[220px] truncate">
                                    {{ $ticket->subject }}
                                </td>
                                @if ($isAdmin)
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $ticket->user?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        @if ($isAdmin)
                                            <select wire:change="quickStatus({{ $ticket->id }}, $event.target.value)"
                                                class="text-xs border border-gray-200 dark:border-zinc-600 rounded-lg px-1 py-1 bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 focus:outline-none">
                                                @foreach(['open','in_progress','pending','resolved','closed'] as $s)
                                                    <option value="{{ $s }}" @selected($ticket->status === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    @php
                                        $pColor = match($ticket->priority) {
                                            'high'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            default  => 'bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pColor }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $sColor = match($ticket->status) {
                                            'open'        => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'in_progress' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                            'pending'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                            'resolved'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'closed'      => 'bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300',
                                            default       => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sColor }}">
                                        {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap text-xs">
                                    {{ $ticket->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('tickets.show', $ticket->id) }}" wire:navigate
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-700">
                    {{ $tickets->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
