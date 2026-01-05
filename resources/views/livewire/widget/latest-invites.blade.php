<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Str;

new class extends Component {
    public $latestInvites = [];

    // Assistant modal state
    public $showAssistantModal = false;
    public $assistantSearch = '';
    public $assistantUserId = null;
    public $assistantTargetUserId = null;
    public $assistantTargetUser = null;
    public $assistants = [];

    public function mount()
    {
        $this->latestInvites = auth()
            ->user()
            ->invites()
            ->where('created_at', '>=', now()->subWeek())
            ->latest()
            ->take(5)
            ->get();
    }

    public function loadAssistants()
    {
        $query = User::where('is_active', true)
            ->where('id', '!=', auth()->id())
            ->where('id', '!=', 1);
        if ($this->assistantTargetUserId) {
            $query->where('id', '!=', $this->assistantTargetUserId);
        }
        $this->assistants = $query->get();
    }

    public function addAssistant($userId)
    {
        $this->assistantTargetUserId = $userId;
        $this->assistantTargetUser = User::find($userId);
        $this->assistantUserId = $this->assistantTargetUser->assistant_id ?? null;
        $this->assistantSearch = '';
        $this->loadAssistants();
        $this->showAssistantModal = true;
    }

    public function selectAssistant($assistantId)
    {
        $this->assistantUserId = $assistantId;
    }

    public function deselectAssistant()
    {
        if (!$this->assistantTargetUserId) {
            session()->flash('error', 'Target user not set.');
            return;
        }

        $target = User::find($this->assistantTargetUserId);
        if (!$target) {
            session()->flash('error', 'Target user not found.');
            return;
        }

        $target->assistant_id = null;
        $target->save();

        session()->flash('message', 'Assistant removed successfully.');
    }

    public function addAssistantUser()
    {
        if (!$this->assistantTargetUserId) {
            session()->flash('error', 'Target user not set.');
            return;
        }

        if (!$this->assistantUserId) {
            session()->flash('error', 'Please select an assistant.');
            return;
        }

        $target = User::find($this->assistantTargetUserId);
        if (!$target) {
            session()->flash('error', 'Target user not found.');
            return;
        }

        $target->assistant_id = $this->assistantUserId;
        $target->save();

        // update local target user for UI
        if ($this->assistantTargetUser && $this->assistantTargetUser->id === $target->id) {
            $this->assistantTargetUser->assistant_id = $this->assistantUserId;
        }

        session()->flash('message', 'Assistant assigned successfully.');
    }

    public function getFilteredAssistantsProperty()
    {
        $search = trim(strtolower($this->assistantSearch));
        if ($search === '') {
            return $this->assistants;
        }

        return $this->assistants->filter(function ($u) use ($search) {
            return Str::contains(strtolower($u->name ?? ''), $search) || Str::contains(strtolower($u->email ?? ''), $search) || Str::contains(strtolower($u->riscoin_id ?? ''), $search);
        });
    }

    public function closeAssistantModal()
    {
        $this->showAssistantModal = false;
        $this->assistantSearch = '';
        $this->assistantUserId = null;
        $this->assistantTargetUserId = null;
        $this->assistantTargetUser = null;
    }

    public function getAssistantSampleTextProperty()
    {
        $depositorId = $this->assistantTargetUser->riscoin_id ?? 'N/A';
        $inviterId = $this->assistantTargetUser->inviters_code ?? 'N/A';

        $assistantUser = $this->assistantUserId ? User::find($this->assistantUserId) : null;
        $assistantId = $assistantUser ? $assistantUser->riscoin_id ?? $assistantUser->id : 'N/A';

        return "Hi Sir Martin\nHere is my application reward request from my investor, {$this->assistantTargetUser?->name}\n\nInviter's Riscoin Account : {$inviterId}\n\nDepositor's Riscoin Account : {$depositorId}\n\nAssister's Riscoin Account: {$assistantId}\n\n";
    }
}; ?>

<div>
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Latest Invites</h3>
    <div class="mt-4 space-y-4">
        @forelse($latestInvites as $invite)
            <div
                class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        @if ($invite->getFirstMediaUrl('avatar'))
                            <img src="{{ $invite->getFirstMediaUrl('avatar') }}" alt="{{ $invite->name }}"
                                class="h-12 w-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700">
                        @else
                            <div
                                class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                                <span
                                    class="text-base font-semibold text-white">{{ strtoupper(substr($invite->name, 0, 2)) }}</span>
                            </div>
                        @endif
                        <div>
                            <p class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $invite->name }}</p>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $invite->email }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</span>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $invite->date_joined->format('d M Y') }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div>
                        <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Riscoin
                            ID</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $invite->riscoin_id }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Invested
                            Amount</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            ${{ number_format($invite->invested_amount, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Assister</span>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $invite->assistant?->name ?? 'NOT YET ASSIGNED' }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 rtl:space-x-reverse">
                    <button wire:click="addAssistant({{ $invite->id }})"
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium">
                        Enter Assistant
                    </button>
                    <button type="button"
                        class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors duration-200 text-sm font-medium"
                        onclick="copyWelcomeMessage({{ json_encode(['name' => $invite->name, 'joined' => $invite->date_joined->format('M j, Y'), 'amount' => number_format($invite->invested_amount, 2)]) }})">
                        <span class="inline-flex items-center space-x-2"><svg class="w-4 h-4 text-white" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg><span>Copy Welcome Message</span></span>
                    </button>
                </div>
            </div>
        @empty
            <div class="p-6 text-center bg-gray-50 dark:bg-gray-800 rounded-xl">
                <p class="text-gray-500 dark:text-gray-400">No recent invites found.</p>
            </div>
        @endforelse

        <script>
            function showToast(message, type = 'success') {
                const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
                const toast = document.createElement('div');
                toast.className =
                    `fixed bottom-4 left-1/2 transform -translate-x-1/2 ${color} text-white px-4 py-2 rounded-lg shadow-lg z-50`;
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 2500);
            }
        </script>

        <script>
            function copyWelcomeMessage(payload) {
                const {
                    name,
                    joined,
                    amount
                } = payload;
                const message =
                    `🌟 Welcome to DJ Conquerors!\nWe're thrilled to have you join our community of dedicated investors. Together we'll achieve great things!\nWith gratitude,\nDJ Conquerors Team\n\n🎊 Welcome ${name}!\nJoined: ${joined}\nAmount invested: $${amount} USDT`;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(message).then(() => showToast('Welcome message copied'), () => showToast(
                        'Copy failed', 'error'));
                    return;
                }

                try {
                    const ta = document.createElement('textarea');
                    ta.value = message;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                    showToast('Welcome message copied');
                } catch (e) {
                    showToast('Copy failed', 'error');
                }
            }
        </script>

        <!-- Add Assistant User Modal - Right Side Panel -->
        <div x-data="{ open: @entangle('showAssistantModal') }" x-show="open" x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
            <div x-show="open" x-transition:enter="ease-in-out duration-500" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-500"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                x-on:click="open = false"></div>

            <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                <div x-show="open" x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                    class="w-screen max-w-2xl">
                    <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Add Assistant User</h2>
                            <div class="text-sm text-gray-500">Target:
                                {{ $this->assistantTargetUser ? $this->assistantTargetUser->name : 'None' }}</div>
                            <button wire:click="closeAssistantModal"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto">
                            <div class="px-6 py-4">
                                <div class="w-full">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Search & Select Assistant') }}</label>

                                    <input type="text" wire:model.live="assistantSearch"
                                        placeholder="Search users by name, email or riscoin id"
                                        class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-2 border-indigo-300 dark:border-indigo-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" />

                                    <div class="mt-3 grid grid-cols-1 gap-2 max-h-48 overflow-y-auto">
                                        @foreach ($this->filteredAssistants as $a)
                                            <button type="button" wire:click="selectAssistant({{ $a->id }})"
                                                class="w-full flex items-center space-x-3 px-3 py-2 rounded-md text-left border border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 {{ $assistantUserId == $a->id ? 'bg-indigo-700 dark:bg-indigo-700' : '' }}">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    @if ($a->getFirstMediaUrl('avatar'))
                                                        <img class="h-8 w-8 rounded-full object-cover"
                                                            src="{{ $a->getFirstMediaUrl('avatar') }}"
                                                            alt="{{ $a->name }}">
                                                    @else
                                                        <div
                                                            class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center {{ $assistantUserId == $a->id ? 'ring-2 ring-indigo-300 dark:ring-indigo-500' : '' }}">
                                                            <span
                                                                class="text-xs font-medium {{ $assistantUserId == $a->id ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">{{ strtoupper(substr($a->name, 0, 1)) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1">
                                                    <div
                                                        class="text-sm font-medium {{ $assistantUserId == $a->id ? 'text-white' : 'text-gray-900 dark:text-white' }}">
                                                        {{ $a->name }}</div>
                                                    <div
                                                        class="text-xs {{ $assistantUserId == $a->id ? 'text-indigo-100' : 'text-gray-500 dark:text-gray-400' }}">
                                                        {{ $a->email }} {{ $a->riscoin_id }}</div>
                                                </div>
                                                <div>
                                                    @if ($assistantUserId == $a->id)
                                                        <svg class="h-5 w-5 text-green-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    @endif
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>

                                    @if (session()->has('message'))
                                        <div
                                            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                            {{ session('message') }}</div>
                                    @endif

                                    @if (session()->has('error'))
                                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                            {{ session('error') }}</div>
                                    @endif

                                    <div class="mt-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Apply
                                            Reward Message to Sir Martin</label>
                                        <textarea readonly rows="6" class="w-full p-3 border rounded-md bg-gray-50 dark:bg-gray-700 text-sm"
                                            id="assistantSample">{{ $this->assistantSampleText }}</textarea>
                                        <div class="mt-2 flex justify-end">
                                            <button type="button"
                                                onclick="(async function(){const t=document.getElementById('assistantSample').value; try{if(navigator.clipboard && navigator.clipboard.writeText){await navigator.clipboard.writeText(t);} else {const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();} showToast('Copied to clipboard');}catch(e){try{const ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();showToast('Copied to clipboard');}catch(err){showToast('Copy failed', 'error');}}})()"
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Copy</button>
                                        </div>
                                    </div>

                                </div>

                                <div
                                    class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700 mt-6">
                                    <button type="button" wire:click="closeAssistantModal"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</button>
                                    <button type="button" wire:click="deselectAssistant"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50">Remove
                                        Assistant</button>
                                    <button type="submit" wire:click="addAssistantUser"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Save
                                        Assistant</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
