<?php

use App\Models\User;
use App\Models\AdjustmentRequest;
use App\Mail\AdjustmentRequestReviewed;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Mail;

new class extends Component {

    public $requests;
    public string $statusFilter = 'pending';
    public string $search = '';

    // Review modal
    public bool $showReviewModal = false;
    public ?int $reviewingId = null;
    public string $adminNotes = '';
    public string $reviewAction = ''; // 'approve' or 'reject'

    // View modal
    public bool $showViewModal = false;
    public $viewingRequest = null;

    public function mount(): void
    {
        $this->loadRequests();
    }

    public function loadRequests(): void
    {
        $this->requests = AdjustmentRequest::with(['user', 'correctAssistant', 'reviewer'])
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('riscoin_id', 'like', '%' . $this->search . '%'));
            })
            ->latest()
            ->get();
    }

    public function updatedStatusFilter(): void { $this->loadRequests(); }
    public function updatedSearch(): void { $this->loadRequests(); }

    public function openView(int $id): void
    {
        $this->viewingRequest = AdjustmentRequest::with(['user', 'correctAssistant', 'reviewer'])->find($id);
        $this->showViewModal = true;
    }

    public function closeView(): void
    {
        $this->showViewModal = false;
        $this->viewingRequest = null;
    }

    public function openReview(int $id, string $action): void
    {
        $this->reviewingId = $id;
        $this->reviewAction = $action;
        $this->adminNotes = '';
        $this->showReviewModal = true;
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->reviewingId = null;
        $this->adminNotes = '';
        $this->reviewAction = '';
    }

    public function confirm(): void
    {
        $this->validate([
            'adminNotes' => 'nullable|string|max:2000',
        ]);

        $request = AdjustmentRequest::with('user')->find($this->reviewingId);

        if (!$request || !$request->isPending()) {
            session()->flash('error', 'Request not found or already reviewed.');
            $this->closeReview();
            return;
        }

        $isApprove = $this->reviewAction === 'approve';

        // If approving, apply the changes to the user
        if ($isApprove) {
            $user = $request->user;
            $updates = [];

            if ($request->correct_name) $updates['name'] = $request->correct_name;
            if ($request->correct_riscoin_id) $updates['riscoin_id'] = $request->correct_riscoin_id;
            if ($request->correct_inviters_code) $updates['inviters_code'] = $request->correct_inviters_code;
            if ($request->correct_assistant_id) $updates['assistant_id'] = $request->correct_assistant_id;

            if (!empty($updates)) {
                $user->update($updates);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($user)
                    ->withProperties($updates)
                    ->log('adjustment request approved — user data updated');
            }
        }

        $request->update([
            'status'      => $isApprove ? 'approved' : 'rejected',
            'admin_notes' => $this->adminNotes ?: null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Send email notification
        try {
            Mail::to($request->user->email)->send(new AdjustmentRequestReviewed($request->fresh(['user', 'correctAssistant'])));
        } catch (\Throwable $e) {
            // Don't block on mail failure
        }

        $this->closeReview();
        $this->loadRequests();
        session()->flash('message', 'Request has been ' . ($isApprove ? 'approved and user updated.' : 'rejected.'));
    }

}; ?>

<div class="py-6 px-4 sm:px-6 max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Adjustment Requests</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Review and process user account correction requests.</p>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-300">{{ session('message') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, email, riscoin ID..."
            class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" />
        <select wire:model.live="statusFilter"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="all">All</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Requested Changes</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Notes</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Submitted</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($requests as $req)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                        {{-- User --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $req->user->name }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $req->user->riscoin_id ?? '—' }}</div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ $req->user->email }}</div>
                        </td>

                        {{-- Changes --}}
                        <td class="px-4 py-3">
                            <div class="space-y-0.5 text-xs text-gray-600 dark:text-gray-400">
                                @if ($req->correct_name)
                                    <div><span class="font-semibold text-gray-700 dark:text-gray-300">Name:</span> {{ $req->correct_name }}</div>
                                @endif
                                @if ($req->correct_riscoin_id)
                                    <div><span class="font-semibold text-gray-700 dark:text-gray-300">Riscoin ID:</span> <span class="font-mono">{{ $req->correct_riscoin_id }}</span></div>
                                @endif
                                @if ($req->correct_inviters_code)
                                    <div><span class="font-semibold text-gray-700 dark:text-gray-300">Inviter Code:</span> <span class="font-mono">{{ $req->correct_inviters_code }}</span></div>
                                @endif
                                @if ($req->correctAssistant)
                                    <div><span class="font-semibold text-gray-700 dark:text-gray-300">Assister:</span> {{ $req->correctAssistant->name }}</div>
                                @endif
                                @if (!$req->correct_name && !$req->correct_riscoin_id && !$req->correct_inviters_code && !$req->correct_assistant_id)
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </div>
                        </td>

                        {{-- Notes --}}
                        <td class="px-4 py-3 max-w-[200px]">
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ $req->notes ?? '—' }}</p>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($req->isPending())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">Pending</span>
                            @elseif ($req->isApproved())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Approved</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Rejected</span>
                            @endif
                            @if ($req->reviewed_at)
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $req->reviewed_at->format('M j, Y') }}</div>
                            @endif
                        </td>

                        {{-- Submitted --}}
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                            {{ $req->created_at->format('M j, Y') }}
                            <div class="text-gray-400 dark:text-gray-500">{{ $req->created_at->format('g:i A') }}</div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openView({{ $req->id }})"
                                    class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    View
                                </button>
                                @if ($req->isPending())
                                    <button wire:click="openReview({{ $req->id }}, 'approve')"
                                        class="px-2.5 py-1.5 text-xs rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors">
                                        Approve
                                    </button>
                                    <button wire:click="openReview({{ $req->id }}, 'reject')"
                                        class="px-2.5 py-1.5 text-xs rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors">
                                        Reject
                                    </button>
                                @endif
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                            No adjustment requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- View Modal --}}
    <div x-data="{ open: @entangle('showViewModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-on:click="open = false"></div>

        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Adjustment Request Details</h2>
                <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @if ($viewingRequest)
            <div class="px-6 py-5 space-y-4 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Requester</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $viewingRequest->user->name }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $viewingRequest->user->riscoin_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</p>
                        @if ($viewingRequest->isPending())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                        @elseif ($viewingRequest->isApproved())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Approved</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Rejected</span>
                        @endif
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Requested Corrections</p>
                    <div class="space-y-1.5 text-gray-700 dark:text-gray-300">
                        @if ($viewingRequest->correct_name)
                            <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Name:</span> <span class="font-medium">{{ $viewingRequest->correct_name }}</span></div>
                        @endif
                        @if ($viewingRequest->correct_riscoin_id)
                            <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Riscoin ID:</span> <span class="font-mono font-medium">{{ $viewingRequest->correct_riscoin_id }}</span></div>
                        @endif
                        @if ($viewingRequest->correct_inviters_code)
                            <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Inviter Code:</span> <span class="font-mono font-medium">{{ $viewingRequest->correct_inviters_code }}</span></div>
                        @endif
                        @if ($viewingRequest->correctAssistant)
                            <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">Assister:</span> <span class="font-medium">{{ $viewingRequest->correctAssistant->name }}</span></div>
                        @endif
                    </div>
                </div>

                @if ($viewingRequest->notes)
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">User Notes</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ $viewingRequest->notes }}</p>
                    </div>
                @endif

                @if ($viewingRequest->admin_notes)
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Admin Notes</p>
                        <p class="text-gray-700 dark:text-gray-300">{{ $viewingRequest->admin_notes }}</p>
                    </div>
                @endif

                @if ($viewingRequest->reviewer)
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3 text-xs text-gray-400 dark:text-gray-500">
                        Reviewed by <span class="font-medium text-gray-600 dark:text-gray-300">{{ $viewingRequest->reviewer->name }}</span>
                        on {{ $viewingRequest->reviewed_at?->format('M j, Y g:i A') }}
                    </div>
                @endif
            </div>
            @endif
            <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeView"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Review Modal (Approve / Reject) --}}
    <div x-data="{ open: @entangle('showReviewModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" x-on:click="open = false"></div>

        <div x-show="open" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    @if ($reviewAction === 'approve')
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Approve Request</h2>
                    @else
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Reject Request</h2>
                    @endif
                </div>
                <button wire:click="closeReview" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                @if ($reviewAction === 'approve')
                    <p class="text-sm text-gray-600 dark:text-gray-400">Approving this request will <strong class="text-gray-900 dark:text-white">immediately update the user's account</strong> and send them an email notification.</p>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rejecting this request will notify the user by email. Please provide a reason below.</p>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Admin Notes <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea wire:model="adminNotes" rows="3"
                        placeholder="{{ $reviewAction === 'reject' ? 'Reason for rejection...' : 'Any notes for the user...' }}"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    @error('adminNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeReview"
                    class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
                <button wire:click="confirm"
                    class="px-4 py-2 text-sm font-medium rounded-lg text-white transition-colors {{ $reviewAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}">
                    {{ $reviewAction === 'approve' ? 'Yes, Approve & Update' : 'Yes, Reject' }}
                </button>
            </div>
        </div>
    </div>

</div>
