<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketStatusUpdated;
use Livewire\Volt\Component;

new class extends Component {
    public ?Ticket $ticket = null;
    public string $comment = '';
    public bool $isInternal = false;
    public string $newStatus = '';
    public ?int $assignTo = null;
    public $adminUsers = [];

    public function mount(int $ticketId): void
    {
        $this->ticket = Ticket::with(['user', 'assignee', 'comments.user', 'media'])
            ->findOrFail($ticketId);

        if (!auth()->user()->can('tickets.manage') && $this->ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $this->newStatus = $this->ticket->status;
        $this->assignTo  = $this->ticket->assigned_to;

        if (auth()->user()->can('tickets.manage')) {
            $this->adminUsers = User::select('id', 'name')->orderBy('name')->get()->toArray();
        }
    }

    public function addComment(): void
    {
        $this->validate([
            'comment' => 'required|min:5|max:5000',
        ]);

        if ($this->isInternal && !auth()->user()->can('tickets.manage')) {
            abort(403);
        }

        TicketComment::create([
            'ticket_id'   => $this->ticket->id,
            'user_id'     => auth()->id(),
            'body'        => $this->comment,
            'is_internal' => $this->isInternal,
        ]);

        // Notify the other party
        if (auth()->id() !== $this->ticket->user_id && !$this->isInternal) {
            $this->ticket->user->notify(new \App\Notifications\TicketStatusUpdated(
                $this->ticket,
                $this->ticket->status,
            ));
        }

        $this->comment    = '';
        $this->isInternal = false;

        $this->ticket = Ticket::with(['user', 'assignee', 'comments.user', 'media'])
            ->find($this->ticket->id);
    }

    public function updateStatus(): void
    {
        if (!auth()->user()->can('tickets.manage')) {
            abort(403);
        }

        $this->validate(['newStatus' => 'required|in:open,in_progress,pending,resolved,closed']);

        $oldStatus = $this->ticket->status;

        $this->ticket->update([
            'status'      => $this->newStatus,
            'resolved_at' => in_array($this->newStatus, ['resolved', 'closed']) ? now() : null,
        ]);

        if ($oldStatus !== $this->newStatus) {
            $this->ticket->user->notify(new TicketStatusUpdated($this->ticket, $oldStatus));
        }

        $this->ticket->refresh();

        session()->flash('statusMessage', 'Status updated successfully.');
    }

    public function assignTicket(): void
    {
        if (!auth()->user()->can('tickets.manage')) {
            abort(403);
        }

        $this->ticket->update(['assigned_to' => $this->assignTo]);

        if ($this->assignTo) {
            $assignee = User::find($this->assignTo);
            $assignee?->notify(new TicketAssigned($this->ticket));
        }

        $this->ticket = Ticket::with(['user', 'assignee', 'comments.user', 'media'])
            ->find($this->ticket->id);

        session()->flash('assignMessage', 'Ticket assigned successfully.');
    }
}; ?>

<div>
    <!-- Back + Header -->
    <div class="flex items-start gap-3 mb-6">
        <a href="{{ route('tickets.index') }}" wire:navigate
            class="mt-1 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition flex-shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $ticket->ticket_number }}</span>
                @php
                    $sColor = match($ticket->status) {
                        'open'        => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        'in_progress' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'pending'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                        'resolved'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                        'closed'      => 'bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300',
                        default       => 'bg-gray-100 text-gray-600',
                    };
                    $pColor = match($ticket->priority) {
                        'high'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        default  => 'bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300',
                    };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sColor }}">
                    {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pColor }}">
                    {{ ucfirst($ticket->priority) }} Priority
                </span>
            </div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white truncate">{{ $ticket->subject }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Submitted by <span class="font-medium text-gray-700 dark:text-gray-300">{{ $ticket->user?->name }}</span>
                on {{ $ticket->created_at->format('M d, Y \a\t h:i A') }}
                @if ($ticket->category) · {{ $ticket->category }} @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Description -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Description</h2>
                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $ticket->description }}</div>
            </div>

            <!-- Attachments -->
            @if ($ticket->getMedia('attachments')->isNotEmpty())
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Attachments</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($ticket->getMedia('attachments') as $media)
                            <a href="{{ $media->getUrl() }}" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $media->file_name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Comments -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Conversation
                        <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $ticket->comments->count() }})</span>
                    </h2>
                </div>

                @if ($ticket->comments->isEmpty())
                    <div class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">No replies yet.</div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                        @foreach ($ticket->comments as $c)
                            @if (!$c->is_internal || auth()->user()->can('tickets.manage'))
                                <div class="px-5 py-4 {{ $c->is_internal ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($c->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $c->user?->name }}</span>
                                                @if ($c->is_internal)
                                                    <span class="px-1.5 py-0.5 text-xs bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded font-medium">Internal</span>
                                                @endif
                                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $c->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $c->body }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <!-- Add Comment -->
                <div class="px-5 py-4 border-t border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                    @if (session('commentError'))
                        <p class="mb-2 text-xs text-red-500">{{ session('commentError') }}</p>
                    @endif
                    <form wire:submit="addComment" class="space-y-3">
                        <textarea wire:model="comment" rows="3"
                            placeholder="Write a reply…"
                            class="w-full px-3 py-2.5 text-sm border rounded-xl bg-white dark:bg-zinc-800 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 placeholder-gray-400 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 @error('comment') border-red-400 @enderror"></textarea>
                        @error('comment') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @can('tickets.manage')
                                    <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
                                        <input type="checkbox" wire:model="isInternal"
                                            class="w-3.5 h-3.5 rounded text-amber-500 border-gray-300 dark:border-zinc-600">
                                        Internal note (admin only)
                                    </label>
                                @endcan
                            </div>
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                                <span wire:loading.remove>Send Reply</span>
                                <span wire:loading>Sending…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5">
            @can('tickets.manage')
                <!-- Status Update -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Update Status</h3>
                    @if (session('statusMessage'))
                        <div class="mb-3 text-xs text-green-600 dark:text-green-400 font-medium">{{ session('statusMessage') }}</div>
                    @endif
                    <select wire:model="newStatus"
                        class="w-full px-3 py-2.5 mb-3 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending">Pending</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                    <button wire:click="updateStatus"
                        class="w-full py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Update Status
                    </button>
                </div>

                <!-- Assign -->
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Assign To</h3>
                    @if (session('assignMessage'))
                        <div class="mb-3 text-xs text-green-600 dark:text-green-400 font-medium">{{ session('assignMessage') }}</div>
                    @endif
                    <select wire:model="assignTo"
                        class="w-full px-3 py-2.5 mb-3 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Unassigned</option>
                        @foreach ($adminUsers as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                    <button wire:click="assignTicket"
                        class="w-full py-2 text-sm font-medium bg-gray-700 hover:bg-gray-800 text-white rounded-lg transition">
                        Assign
                    </button>
                </div>
            @endcan

            <!-- Ticket Info -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ticket Details</h3>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Ticket #</dt>
                        <dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Category</dt>
                        <dd class="text-gray-700 dark:text-gray-300">{{ $ticket->category ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Assigned To</dt>
                        <dd class="text-gray-700 dark:text-gray-300">{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    @if ($ticket->resolved_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Resolved At</dt>
                            <dd class="text-gray-700 dark:text-gray-300 text-xs">{{ $ticket->resolved_at->format('M d, Y') }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Submitted</dt>
                        <dd class="text-gray-700 dark:text-gray-300 text-xs">{{ $ticket->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>
