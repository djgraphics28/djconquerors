<?php

use App\Models\User;
use App\Models\AdjustmentRequest;
use Livewire\Volt\Component;

new class extends Component {

    public string $correctName = '';
    public string $correctRiscoinId = '';
    public string $correctInvitersCode = '';
    public string $correctAssistantId = '';
    public string $notes = '';

    public $myRequests;
    public $users = [];

    public function mount(): void
    {
        $this->users = User::select('id', 'name', 'riscoin_id')->orderBy('name')->get();
        $this->loadRequests();
    }

    public function loadRequests(): void
    {
        $this->myRequests = AdjustmentRequest::with(['correctAssistant', 'reviewer'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function submit(): void
    {
        $this->validate([
            'correctName'          => 'nullable|string|max:255',
            'correctRiscoinId'     => 'nullable|string|max:100',
            'correctInvitersCode'  => 'nullable|string|max:100',
            'correctAssistantId'   => 'nullable|exists:users,id',
            'notes'                => 'nullable|string|max:2000',
        ]);

        if (!$this->correctName && !$this->correctRiscoinId && !$this->correctInvitersCode && !$this->correctAssistantId) {
            $this->addError('correctName', 'Please fill in at least one field you want to correct.');
            return;
        }

        // Check if there's already a pending request
        $hasPending = AdjustmentRequest::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            session()->flash('error', 'You already have a pending adjustment request. Please wait for it to be reviewed before submitting a new one.');
            return;
        }

        AdjustmentRequest::create([
            'user_id'               => auth()->id(),
            'correct_name'          => $this->correctName ?: null,
            'correct_riscoin_id'    => $this->correctRiscoinId ?: null,
            'correct_inviters_code' => $this->correctInvitersCode ?: null,
            'correct_assistant_id'  => $this->correctAssistantId ?: null,
            'notes'                 => $this->notes ?: null,
            'status'                => 'pending',
        ]);

        $this->reset(['correctName', 'correctRiscoinId', 'correctInvitersCode', 'correctAssistantId', 'notes']);
        $this->loadRequests();
        session()->flash('message', 'Your adjustment request has been submitted. We will review it and notify you via email.');
    }

}; ?>

<x-settings.layout :heading="__('Adjustment Request')" :subheading="__('Request corrections to your account information.')">

    {{-- Flash messages --}}
    @if (session('message'))
        <div class="mb-4 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-sm text-green-700 dark:text-green-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Info notice --}}
    <div class="mb-5 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-sm text-blue-700 dark:text-blue-300 flex gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Only fill in the fields you want to <strong>correct</strong>. Leave the rest blank. You will be notified by email once your request is reviewed.</span>
    </div>

    {{-- Form --}}
    <form wire:submit="submit" class="space-y-4">

        <div>
            <flux:input wire:model="correctName"
                label="Correct Investor Name"
                type="text"
                placeholder="Leave blank if no change needed" />
            @error('correctName') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <flux:input wire:model="correctRiscoinId"
                label="Correct Riscoin ID"
                type="text"
                placeholder="Leave blank if no change needed" />
            @error('correctRiscoinId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <flux:input wire:model="correctInvitersCode"
                label="Correct Inviter's Code"
                type="text"
                placeholder="Leave blank if no change needed" />
            @error('correctInvitersCode') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correct Assister</label>
            <select wire:model="correctAssistantId"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">— Leave blank if no change needed —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->riscoin_id }})</option>
                @endforeach
            </select>
            @error('correctAssistantId') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <flux:input wire:model="notes"
                label="Notes"
                type="text"
                placeholder="Explain why this correction is needed..." />
            @error('notes') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="pt-1">
            <flux:button type="submit" variant="primary">
                Submit Request
            </flux:button>
        </div>
    </form>

    {{-- My previous requests --}}
    @if ($myRequests && $myRequests->count())
        <div class="mt-10">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">My Previous Requests</h3>
            <div class="space-y-3">
                @foreach ($myRequests as $req)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 text-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $req->created_at->format('M j, Y g:i A') }}</span>
                            @if ($req->isPending())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300">Pending</span>
                            @elseif ($req->isApproved())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Approved</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Rejected</span>
                            @endif
                        </div>
                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                            @if ($req->correct_name) <div><span class="font-medium text-gray-700 dark:text-gray-300">Name:</span> {{ $req->correct_name }}</div> @endif
                            @if ($req->correct_riscoin_id) <div><span class="font-medium text-gray-700 dark:text-gray-300">Riscoin ID:</span> {{ $req->correct_riscoin_id }}</div> @endif
                            @if ($req->correct_inviters_code) <div><span class="font-medium text-gray-700 dark:text-gray-300">Inviter Code:</span> {{ $req->correct_inviters_code }}</div> @endif
                            @if ($req->correctAssistant) <div><span class="font-medium text-gray-700 dark:text-gray-300">Assister:</span> {{ $req->correctAssistant->name }}</div> @endif
                            @if ($req->notes) <div><span class="font-medium text-gray-700 dark:text-gray-300">Notes:</span> {{ $req->notes }}</div> @endif
                            @if ($req->admin_notes)
                                <div class="mt-1.5 p-2 rounded bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Admin Notes:</span> {{ $req->admin_notes }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</x-settings.layout>
