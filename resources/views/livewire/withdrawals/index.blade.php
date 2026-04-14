<?php

use App\Models\Withdrawal;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;

new class extends Component {
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $withdrawal;
    public $isEdit = false;
    public $amount;
    public $status;
    public $transaction_id;
    public $paid_date;
    public $showModal = false;

    public $totalAmount = 0;
    public $paidAmount = 0;
    public $pendingAmount = 0;

    public $user_id;
    public $users = [];

    public function mount()
    {
        $this->users = User::orderBy('name')->get();
        $this->calculateStatistics();
    }

    public function getWithdrawalsProperty()
    {
        return Withdrawal::with('user')->latest()->paginate(10);
    }

    public function calculateStatistics()
    {
        $this->totalAmount = Withdrawal::sum('amount');
        $this->paidAmount = Withdrawal::where('status', 'paid')->sum('amount');
        $this->pendingAmount = Withdrawal::where('status', 'under-audit')->sum('amount');
    }

    public function create()
    {
        $validated = $this->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        Withdrawal::create($validated);

        $this->resetForm();
        $this->calculateStatistics();
        $this->showModal = false;
    }

    public function edit(Withdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
        $this->user_id = $withdrawal->user_id;
        $this->amount = $withdrawal->amount;
        $this->status = $withdrawal->status;
        $this->transaction_id = $withdrawal->transaction_id;
        $this->paid_date = $withdrawal->paid_date;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function update()
    {
        if (!$this->withdrawal) {
            return;
        }

        $validated = $this->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        $this->withdrawal->update($validated);

        $this->resetForm();
        $this->calculateStatistics();
        $this->showModal = false;
    }

    public function delete(Withdrawal $withdrawal)
    {
        $withdrawal->delete();
        $this->calculateStatistics();
    }

    public function resetForm()
    {
        $this->reset(['withdrawal', 'user_id', 'amount', 'status', 'transaction_id', 'paid_date', 'isEdit']);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }
}; ?>

<div class="max-w-10xl mx-auto">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
            Admin – Manage Withdrawals
        </h2>

        <flux:button wire:click="openModal" variant="primary">
            New Withdrawal
        </flux:button>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Withdrawals</h3>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                ${{ number_format($totalAmount, 2) }}
            </p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid Amount</h3>
            <p class="text-2xl font-semibold text-green-600 dark:text-green-400">
                ${{ number_format($paidAmount, 2) }}
            </p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Under-Audit Amount</h3>
            <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">
                ${{ number_format($pendingAmount, 2) }}
            </p>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto relative bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                @forelse ($this->withdrawals as $withdrawal)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-white">
                            {{ $withdrawal->user->name ?? 'N/A' }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                            ${{ number_format($withdrawal->amount, 2) }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $withdrawal->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst(str_replace('-', ' ', $withdrawal->status)) }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                            {{ $withdrawal->paid_date ? \Carbon\Carbon::parse($withdrawal->paid_date)->format('M d, Y') : 'N/A' }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2">
                                <flux:button wire:click="edit({{ $withdrawal->id }})" variant="ghost" size="sm">
                                    Edit
                                </flux:button>

                                <flux:button wire:click="delete({{ $withdrawal->id }})" variant="ghost" size="sm"
                                    class="text-red-600 hover:text-red-900">
                                    Delete
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No withdrawals found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $this->withdrawals->links() }}
    </div>

    <!-- Modal -->
    <flux:modal wire:model="showModal" :title="$isEdit ? 'Edit Withdrawal' : 'New Withdrawal'" max-width="lg">
        <form wire:submit.prevent="{{ $isEdit ? 'update' : 'create' }}" class="space-y-6">
            <flux:select wire:model="user_id" label="User" required>
                <option value="">Select User</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </flux:select>

            <flux:input wire:model="amount" label="Amount" type="number" step="0.01" required prefix="$" />

            <flux:select wire:model="status" label="Status" required>
                <option value="">Select Status</option>
                <option value="under-audit">Under Audit</option>
                <option value="paid">Paid</option>
            </flux:select>

            <flux:input wire:model="paid_date" label="Paid Date" type="date" />

            <div class="flex justify-end space-x-3 pt-6">
                <flux:button type="button" wire:click="closeModal">
                    Cancel
                </flux:button>

                <flux:button type="submit" variant="primary">
                    {{ $isEdit ? 'Update Withdrawal' : 'Create Withdrawal' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
