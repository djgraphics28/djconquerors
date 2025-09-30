<?php

use App\Models\Withdrawal;
use Livewire\Volt\Component;

new class extends Component {
    public $withdrawals;
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

    public function mount()
    {
        $this->loadWithdrawals();
        $this->calculateStatistics();
    }

    public function loadWithdrawals()
    {
        $this->withdrawals = Withdrawal::where('user_id', auth()->id())
            ->latest()
            ->get();
    }

    public function calculateStatistics()
    {
        $this->totalAmount = $this->withdrawals->sum('amount');
        $this->paidAmount = $this->withdrawals->where('status', 'paid')->sum('amount');
        $this->pendingAmount = $this->withdrawals->where('status', 'under-audit')->sum('amount');
    }

    public function create()
    {
        $validated = $this->validate([
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        $validated['user_id'] = auth()->id();

        Withdrawal::create($validated);

        $this->resetForm();
        $this->loadWithdrawals();
        $this->calculateStatistics();
        $this->showModal = false;
    }

    public function edit(Withdrawal $withdrawal)
    {
        if ($withdrawal->user_id !== auth()->id()) {
            return;
        }

        $this->withdrawal = $withdrawal;
        $this->amount = $withdrawal->amount;
        $this->status = $withdrawal->status;
        $this->transaction_id = $withdrawal->transaction_id;
        $this->paid_date = $withdrawal->paid_date;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function update()
    {
        if (!$this->withdrawal || $this->withdrawal->user_id !== auth()->id()) {
            return;
        }

        $validated = $this->validate([
            'amount' => 'required|numeric',
            'status' => 'required|in:under-audit,paid',
            'transaction_id' => 'nullable|string',
            'paid_date' => 'nullable|date',
        ]);

        $this->withdrawal->update($validated);

        $this->resetForm();
        $this->loadWithdrawals();
        $this->calculateStatistics();
        $this->showModal = false;
    }

    public function delete(Withdrawal $withdrawal)
    {
        if ($withdrawal->user_id !== auth()->id()) {
            return;
        }

        $withdrawal->delete();
        $this->loadWithdrawals();
        $this->calculateStatistics();
    }

    public function resetForm()
    {
        $this->reset(['withdrawal', 'amount', 'status', 'transaction_id', 'paid_date', 'isEdit']);
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

<div>
    <!-- Header -->
    <div class="max-w-10xl mx-auto">
        <!-- Breadcrumb Navigation -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('my-withdrawals') }}"
                        class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        My Withdrawals
                    </a>
                </li>
            </ol>
        </nav>
        <div>
            <div>
                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Withdrawals</h3>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($totalAmount, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid Amount</h3>
                        <p class="text-2xl font-semibold text-green-600 dark:text-green-400">
                            ${{ number_format($paidAmount, 2) }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Under-Audit Amount</h3>
                        <p class="text-2xl font-semibold text-yellow-600 dark:text-yellow-400">
                            ${{ number_format($pendingAmount, 2) }}</p>
                    </div>
                </div>

                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Withdrawals</h2>
                    <flux:button wire:click="openModal" variant="primary" data-test="new-withdrawal-button">
                        New Withdrawal
                    </flux:button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto relative">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Amount</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Paid Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($withdrawals as $withdrawal)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                        ${{ number_format($withdrawal->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $withdrawal->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} capitalize">
                                            {{ str_replace('-', ' ', $withdrawal->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $withdrawal->paid_date ? \Carbon\Carbon::parse($withdrawal->paid_date)->format('M j, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            <flux:button wire:click="edit({{ $withdrawal->id }})" variant="ghost"
                                                size="sm" data-test="edit-withdrawal-{{ $withdrawal->id }}">
                                                Edit
                                            </flux:button>
                                            <flux:button wire:click="delete({{ $withdrawal->id }})" variant="ghost"
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

                    @if ($withdrawals->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 dark:text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No withdrawals found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Get started by creating your first withdrawal
                                request.</p>
                        </div>
                    @endif
                </div>

                <!-- Modal -->
                <flux:modal wire:model="showModal" :title="$isEdit ? 'Edit Withdrawal' : 'New Withdrawal'"
                    max-width="lg">

                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'create' }}" class="space-y-6">
                        <!-- Amount -->
                        <flux:input wire:model="amount" :label="__('Amount')" type="number" step="0.01" required
                            :placeholder="__('0.00')" prefix="$" data-test="amount-input" />

                        <!-- Status -->
                        <flux:select wire:model="status" :label="__('Status')" required data-test="status-select">
                            <option value="">Select Status</option>
                            <option value="under-audit">Under Audit</option>
                            <option value="paid">Paid</option>
                        </flux:select>

                        <!-- Paid Date -->
                        <flux:input wire:model="paid_date" :label="__('Paid Date')" type="date"
                            :placeholder="__('Select paid date')" data-test="paid-date-input" />

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-6">
                            <flux:button type="button" wire:click="closeModal" data-test="cancel-withdrawal-button">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary" data-test="submit-withdrawal-button">
                                {{ $isEdit ? 'Update Withdrawal' : 'Create Withdrawal' }}
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            </div>
        </div>
    </div>
</div>
