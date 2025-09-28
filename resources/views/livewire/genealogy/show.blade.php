<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new class extends Component {
    public $currentNode;
    public $riscoinId;
    public $historyStack = [];

    public function mount($riscoinId = null)
    {
        // Initialize history stack from localStorage
        $this->dispatch('initializeHistoryStack');

        if ($riscoinId) {
            $this->currentNode = User::where('riscoin_id', $riscoinId)
                ->with(['invites'])
                ->firstOrFail();

            // Add to history stack
            $this->dispatch('addToHistoryStack', riscoinId: $riscoinId);
        } else {
            $this->currentNode = Auth::user();
            $this->currentNode->load(['invites']);

            // Clear history stack when at root level
            $this->dispatch('clearHistoryStack');
        }

        $this->riscoinId = $riscoinId;
    }

    public function goBack()
    {
        $this->dispatch('navigateBack');
    }

    public function getDirectMembersCountProperty()
    {
        return $this->currentNode->invites->count();
    }

    public function getTotalTeamMembersProperty()
    {
        $count = 1;
        $currentLevel = $this->currentNode->invites;

        while ($currentLevel->isNotEmpty()) {
            $count += $currentLevel->count();
            $nextLevel = collect();
            foreach ($currentLevel as $user) {
                $nextLevel = $nextLevel->merge($user->invites);
            }
            $currentLevel = $nextLevel;
        }

        return $count;
    }

    public function getTotalTeamInvestmentProperty()
    {
        $total = $this->currentNode->invested_amount;
        $currentLevel = $this->currentNode->invites;

        while ($currentLevel->isNotEmpty()) {
            $total += $currentLevel->sum('invested_amount');
            $nextLevel = collect();
            foreach ($currentLevel as $user) {
                $nextLevel = $nextLevel->merge($user->invites);
            }
            $currentLevel = $nextLevel;
        }

        return $total;
    }
}; ?>

<div class="p-6">
    <!-- Breadcrumb Navigation -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li>
                <a href="{{ route('genealogy') }}"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                    My Genealogy
                </a>
            </li>
            @if ($this->riscoinId)
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ $this->currentNode->riscoin_id }}</span>
                </li>
            @endif
        </ol>
    </nav>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Direct Members</h3>
            <p class="text-2xl font-bold dark:text-white">{{ $this->directMembersCount }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Total Team Members</h3>
            <p class="text-2xl font-bold dark:text-white">{{ $this->totalTeamMembers }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
            <h3 class="font-semibold text-gray-600 dark:text-gray-400">Team Investment</h3>
            <p class="text-2xl font-bold dark:text-white">${{ number_format($this->totalTeamInvestment, 2) }}</p>
        </div>
    </div>

    <!-- Genealogy Tree -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold dark:text-white">
                @if ($this->riscoinId)
                    Genealogy of {{ $this->currentNode->name }} ({{ $this->currentNode->riscoin_id }})
                @else
                    My Genealogy Tree
                @endif
            </h2>
        </div>

        @if ($this->currentNode->invites->count() > 0)
            <div class="genealogy-tree flex justify-center">
                <x-genealogy-node :node="$this->currentNode" :level="0" :showChildren="true" />
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-gray-400">No direct members yet.</p>
            </div>
        @endif

        @if ($this->riscoinId)
            <div class="text-center mt-4">
                <button onclick="window.history.back()"
                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 mt-2 inline-block">
                    ← Back to previous genealogy
                </button>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            // Initialize history stack in localStorage
            Livewire.on('initializeHistoryStack', () => {
                if (!window.localStorage.getItem('genealogyHistoryStack')) {
                    window.localStorage.setItem('genealogyHistoryStack', JSON.stringify([]));
                }
            });

            // Clear history stack
            Livewire.on('clearHistoryStack', () => {
                window.localStorage.setItem('genealogyHistoryStack', JSON.stringify([]));
            });

            // Add riscoinId to history stack
            Livewire.on('addToHistoryStack', ({
                riscoinId
            }) => {
                const historyStack = JSON.parse(window.localStorage.getItem('genealogyHistoryStack') ||
                    '[]');

                // Don't add if it's the same as the last item
                if (historyStack.length === 0 || historyStack[historyStack.length - 1] !== riscoinId) {
                    historyStack.push(riscoinId);
                    window.localStorage.setItem('genealogyHistoryStack', JSON.stringify(historyStack));
                }
            });

            // Navigate back functionality
            Livewire.on('navigateBack', () => {
                const historyStack = JSON.parse(window.localStorage.getItem('genealogyHistoryStack') ||
                    '[]');

                if (historyStack.length > 1) {
                    // Remove current node from stack
                    historyStack.pop();

                    // Get the previous node
                    const previousRiscoinId = historyStack.pop();

                    // Update the stack
                    window.localStorage.setItem('genealogyHistoryStack', JSON.stringify(historyStack));

                    // Navigate to previous node
                    if (previousRiscoinId) {
                        window.location.href = "{{ route('genealogy') }}?riscoinId=" + previousRiscoinId;
                    } else {
                        window.location.href = "{{ route('genealogy') }}";
                    }
                } else if (historyStack.length === 1) {
                    // If only one item in stack, go to root
                    window.localStorage.setItem('genealogyHistoryStack', JSON.stringify([]));
                    window.location.href = "{{ route('genealogy') }}";
                } else {
                    // If no history, go to root
                    window.location.href = "{{ route('genealogy') }}";
                }
            });
        });
    </script>
</div>
