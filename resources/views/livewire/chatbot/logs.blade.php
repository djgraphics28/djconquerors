<?php

use App\Models\ChatbotLog;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $expandedLog = null;

    public function mount(): void
    {
        if (!auth()->user()->can('chatbot.manage')) {
            abort(403);
        }
    }

    public function with(): array
    {
        return [
            'logs' => ChatbotLog::with(['user', 'ticket'])
                ->latest()
                ->paginate(20),
        ];
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedLog = $this->expandedLog === $id ? null : $id;
    }

    public function deleteLog(int $id): void
    {
        ChatbotLog::findOrFail($id)->delete();
        session()->flash('message', 'Log deleted.');
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Chatbot Logs</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">All chatbot conversation sessions</p>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
        @if ($logs->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No chatbot conversations yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">User</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Messages</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Ticket Created</th>
                            <th class="text-left px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Date</th>
                            <th class="text-right px-4 py-3 font-medium text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                    {{ $log->user?->name ?? 'Guest' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ is_array($log->messages) ? count($log->messages) : 0 }} messages
                                </td>
                                <td class="px-4 py-3">
                                    @if ($log->ticket_created)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                Yes
                                            </span>
                                            @if ($log->ticket)
                                                <a href="{{ route('tickets.show', $log->ticket_id) }}" wire:navigate
                                                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-mono">
                                                    {{ $log->ticket->ticket_number }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-gray-400">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $log->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="toggleExpand({{ $log->id }})"
                                            class="text-xs px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                                            {{ $expandedLog === $log->id ? 'Hide' : 'View' }}
                                        </button>
                                        <button wire:click="deleteLog({{ $log->id }})" wire:confirm="Delete this log?"
                                            class="text-xs px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @if ($expandedLog === $log->id)
                                <tr>
                                    <td colspan="5" class="px-4 py-4 bg-gray-50 dark:bg-zinc-900/50">
                                        <div class="max-h-64 overflow-y-auto space-y-2">
                                            @if (is_array($log->messages) && count($log->messages))
                                                @foreach ($log->messages as $msg)
                                                    <div class="flex gap-3 {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                                        <div class="max-w-sm px-3 py-2 rounded-xl text-xs {{ $msg['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-600' }}">
                                                            <p class="font-medium mb-0.5 opacity-60 text-[10px]">{{ ucfirst($msg['role']) }}</p>
                                                            <div class="prose prose-xs dark:prose-invert max-w-none [&_p]:mb-1 [&_ol]:pl-4 [&_ul]:pl-4 [&_li]:mb-0.5">{!! $msg['content'] !!}</div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <p class="text-xs text-gray-400">No messages recorded.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-700">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
