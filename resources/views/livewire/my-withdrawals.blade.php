<?php

use App\Models\Withdrawal;
use Livewire\Volt\Component;

new class extends Component {
    public $withdrawals;
    public $withdrawal;
    public $amount;
    public $status;
    public $transaction_id;
    public $paid_date;
    public $showModal = false;

    public function mount()
    {
        // dd($this->withdrawal);
        $this->loadWithdrawals();
    }

    public function loadWithdrawals()
    {
        $this->withdrawals = Withdrawal::where('user_id', auth()->id())->latest()->get();
    }

    public function create()
    {
        $validated = $this->validate([
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date'
        ]);

        $validated['user_id'] = auth()->id();

        Withdrawal::create($validated);

        $this->resetForm();
        $this->loadWithdrawals();
        $this->showModal = false;
    }

    public function edit(Withdrawal $withdrawal)
    {
        if($withdrawal->user_id !== auth()->id()) {
            return;
        }

        $this->withdrawal = $withdrawal;
        $this->amount = $withdrawal->amount;
        $this->status = $withdrawal->status;
        $this->transaction_id = $withdrawal->transaction_id;
        $this->paid_date = $withdrawal->paid_date;
        $this->showModal = true;
    }

    public function update()
    {
        if($this->withdrawal->user_id !== auth()->id()) {
            return;
        }

        $validated = $this->validate([
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date'
        ]);

        $this->withdrawal->update($validated);

        $this->resetForm();
        $this->loadWithdrawals();
        $this->showModal = false;
    }

    public function delete(Withdrawal $withdrawal)
    {
        if($withdrawal->user_id !== auth()->id()) {
            return;
        }

        $withdrawal->delete();
        $this->loadWithdrawals();
    }

    public function resetForm()
    {
        $this->reset(['withdrawal', 'amount', 'status', 'transaction_id', 'paid_date']);
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

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Withdrawals</h2>
                    <flux:button
                        wire:click="openModal"
                        variant="primary"
                        data-test="new-withdrawal-button">
                        New Withdrawal
                    </flux:button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto relative">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                {{-- <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Transaction ID</th> --}}
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Paid Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($withdrawals as $withdrawal)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                        ${{ number_format($withdrawal->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $withdrawal->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} capitalize">
                                            {{ str_replace('-', ' ', $withdrawal->status) }}
                                        </span>
                                    </td>
                                    {{-- <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $withdrawal->transaction_id ?? 'N/A' }}
                                    </td> --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $withdrawal->paid_date ? \Carbon\Carbon::parse($withdrawal->paid_date)->format('M j, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            <flux:button
                                                wire:click="edit({{ $withdrawal->id }})"
                                                variant="ghost"
                                                size="sm"
                                                data-test="edit-withdrawal-{{ $withdrawal->id }}">
                                                Edit
                                            </flux:button>
                                            <flux:button
                                                wire:click="delete({{ $withdrawal->id }})"
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                data-test="delete-withdrawal-{{ $withdrawal->id }}">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($withdrawals->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 dark:text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No withdrawals found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Get started by creating your first withdrawal request.</p>
                        </div>
                    @endif
                </div>

                <!-- Modal -->
                <flux:modal
                    wire:model="showModal"
                    :title="$withdrawal ? 'Edit Withdrawal' : 'New Withdrawal'"
                    max-width="lg">

                    <form wire:submit.prevent="{{ $withdrawal ? 'update' : 'create' }}" class="space-y-6">
                        <!-- Amount -->
                        <flux:input
                            wire:model="amount"
                            :label="__('Amount')"
                            type="number"
                            step="0.01"
                            required
                            :placeholder="__('0.00')"
                            prefix="$"
                            data-test="amount-input"
                        />

                        <!-- Status -->
                        <flux:select
                            wire:model="status"
                            :label="__('Status')"
                            required
                            data-test="status-select"
                        >
                            <option value="">Select Status</option>
                            <option value="under-audit">Under Audit</option>
                            <option value="paid">Paid</option>
                        </flux:select>

                        <!-- Transaction ID -->
                        {{-- <flux:input
                            wire:model="transaction_id"
                            :label="__('Transaction ID')"
                            type="text"
                            :placeholder="__('Enter transaction ID')"
                            data-test="transaction-id-input"
                        /> --}}

                        <!-- Paid Date -->
                        <flux:input
                            wire:model="paid_date"
                            :label="__('Paid Date')"
                            type="date"
                            :placeholder="__('Select paid date')"
                            data-test="paid-date-input"
                        />

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-6">
                            <flux:button
                                type="button"
                                wire:click="closeModal"
                                data-test="cancel-withdrawal-button"
                            >
                                Cancel
                            </flux:button>
                            <flux:button
                                type="submit"
                                variant="primary"
                                data-test="submit-withdrawal-button"
                            >
                                {{-- {{ !is_null($withdrawal) ? 'Update' : 'Create' }} --}}
                                Save
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            </div>
        </div>
    </div>
</div>
