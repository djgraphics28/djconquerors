<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\DonationMethod;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $methods = [];

    // Modal
    public $showModal = false;
    public $editingId = null;

    // Form fields
    public $name = '';
    public $type = 'gcash';
    public $accountName = '';
    public $accountNumber = '';
    public $instructions = '';
    public $isActive = true;
    public $qrCode = null;               // new upload
    public $currentQrUrl = null;         // for preview when editing

    public function mount(): void
    {
        $this->loadMethods();
    }

    public function loadMethods(): void
    {
        $this->methods = DonationMethod::ordered()->get();
    }

    // ── Modal ────────────────────────────────────────────────────────────────────

    public function openModal($id = null): void
    {
        if ($id) {
            $method = DonationMethod::findOrFail($id);
            $this->editingId    = $id;
            $this->name         = $method->name;
            $this->type         = $method->type;
            $this->accountName  = $method->account_name ?? '';
            $this->accountNumber = $method->account_number ?? '';
            $this->instructions = $method->instructions ?? '';
            $this->isActive     = $method->is_active;
            $this->currentQrUrl = $method->qr_code_url;
        } else {
            $this->reset(['editingId', 'name', 'type', 'accountName', 'accountNumber', 'instructions', 'isActive', 'qrCode', 'currentQrUrl']);
            $this->type     = 'gcash';
            $this->isActive = true;
        }
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->qrCode = null;
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|min:2|max:100',
            'type'        => 'required|in:gcash,maya,bank,other',
            'accountName' => 'nullable|max:100',
            'accountNumber' => 'nullable|max:100',
            'instructions' => 'nullable|max:1000',
            'qrCode'      => 'nullable|image|max:4096',
        ]);

        $qrPath = null;
        if ($this->qrCode) {
            $qrPath = $this->qrCode->store('donation-qr', 'public');
        }

        if ($this->editingId) {
            $method = DonationMethod::findOrFail($this->editingId);

            // Remove old QR if replaced
            if ($qrPath && $method->qr_code_path) {
                Storage::disk('public')->delete($method->qr_code_path);
            }

            $method->update([
                'name'           => $this->name,
                'type'           => $this->type,
                'account_name'   => $this->accountName,
                'account_number' => $this->accountNumber,
                'instructions'   => $this->instructions,
                'is_active'      => $this->isActive,
                'qr_code_path'   => $qrPath ?? $method->qr_code_path,
            ]);
            session()->flash('message', 'Payment method updated successfully.');
        } else {
            DonationMethod::create([
                'name'           => $this->name,
                'type'           => $this->type,
                'account_name'   => $this->accountName,
                'account_number' => $this->accountNumber,
                'instructions'   => $this->instructions,
                'is_active'      => $this->isActive,
                'qr_code_path'   => $qrPath,
                'order'          => (DonationMethod::max('order') ?? 0) + 1,
            ]);
            session()->flash('message', 'Payment method added successfully.');
        }

        $this->closeModal();
        $this->loadMethods();
    }

    public function removeQr($id): void
    {
        $method = DonationMethod::findOrFail($id);
        $method->deleteQrCode();
        $this->currentQrUrl = null;
        $this->loadMethods();
        session()->flash('message', 'QR code removed.');
    }

    public function toggleActive($id): void
    {
        $method = DonationMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);
        $this->loadMethods();
    }

    public function delete($id): void
    {
        $method = DonationMethod::findOrFail($id);
        $method->deleteQrCode();
        $method->delete();
        $this->loadMethods();
        session()->flash('message', 'Payment method deleted.');
    }

    public function updateOrder($orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            DonationMethod::where('id', $id)->update(['order' => $index + 1]);
        }
        $this->loadMethods();
    }
}; ?>

<div>
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-rose-500 via-pink-500 to-orange-400 rounded-2xl p-5 mb-5 text-white shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold">Donation Methods</h1>
                    <p class="text-rose-100 text-sm">Manage payment channels shown to members</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="openModal()"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-full transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Method
                </button>
                <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-medium">
                    {{ $methods->count() }} {{ \Illuminate\Support\Str::plural('Method', $methods->count()) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session('message'))
        <div class="mb-5 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('message') }}</p>
        </div>
    @endif

    <!-- Info Banner -->
    <div class="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            Active methods are shown to all members on the <strong>Donate</strong> page. Upload a QR code so members can scan and send directly. Drag cards to change display order.
        </p>
    </div>

    <!-- Methods Grid -->
    @if ($methods->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-14 text-center">
            <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">No payment methods yet</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Click <strong>Add Method</strong> to add your first donation channel</p>
        </div>
    @else
        <div
            class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4"
            x-data="{
                dragged: null,
                reorder(parent) {
                    const ids = Array.from(parent.querySelectorAll(':scope > [data-id]')).map(el => parseInt(el.getAttribute('data-id')));
                    $wire.updateOrder(ids);
                }
            }">
            @foreach ($methods as $method)
                <div
                    wire:key="dm-{{ $method->id }}"
                    data-id="{{ $method->id }}"
                    draggable="true"
                    @dragstart="dragged = $event.currentTarget; setTimeout(() => $event.currentTarget.classList.add('opacity-40'), 0);"
                    @dragend="$event.currentTarget.classList.remove('opacity-40'); dragged = null;"
                    @dragenter.prevent="$event.currentTarget.classList.add('ring-2','ring-rose-400','ring-offset-1')"
                    @dragover.prevent
                    @dragleave="if (!$event.currentTarget.contains($event.relatedTarget)) $event.currentTarget.classList.remove('ring-2','ring-rose-400','ring-offset-1');"
                    @drop.prevent="
                        $event.currentTarget.classList.remove('ring-2','ring-rose-400','ring-offset-1');
                        if (dragged && dragged !== $event.currentTarget) {
                            const parent = $event.currentTarget.parentNode;
                            const siblings = Array.from(parent.querySelectorAll(':scope > [data-id]'));
                            const fromIdx = siblings.indexOf(dragged);
                            const toIdx = siblings.indexOf($event.currentTarget);
                            if (fromIdx < toIdx) parent.insertBefore(dragged, $event.currentTarget.nextSibling);
                            else parent.insertBefore(dragged, $event.currentTarget);
                            reorder(parent);
                        }
                    "
                    class="cursor-grab active:cursor-grabbing">

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 overflow-hidden transition-all duration-200
                        {{ $method->is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-200 dark:border-gray-700 opacity-60' }}">

                        <!-- QR Preview -->
                        <div class="relative bg-gray-50 dark:bg-gray-900/40 flex items-center justify-center h-48 border-b border-gray-200 dark:border-gray-700">
                            @if ($method->qr_code_url)
                                <img src="{{ $method->qr_code_url }}" alt="QR Code" class="h-40 w-40 object-contain rounded-lg"/>
                                <button
                                    wire:click="removeQr({{ $method->id }})"
                                    wire:confirm="Remove this QR code?"
                                    draggable="false"
                                    title="Remove QR"
                                    class="absolute top-2 right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @else
                                <div class="flex flex-col items-center gap-2 text-gray-300 dark:text-gray-600">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <span class="text-xs">No QR uploaded</span>
                                </div>
                            @endif

                            <!-- Type badge -->
                            <span class="absolute top-2 left-2 text-[11px] font-bold px-2 py-0.5 rounded-full
                                @if($method->type === 'gcash') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                                @elseif($method->type === 'maya') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                @elseif($method->type === 'bank') bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300
                                @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 @endif">
                                {{ $method->type_label }}
                            </span>
                        </div>

                        <!-- Info -->
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-gray-800 dark:text-white truncate">{{ $method->name }}</h3>
                                    @if ($method->account_name)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $method->account_name }}</p>
                                    @endif
                                    @if ($method->account_number)
                                        <p class="text-xs font-mono text-gray-600 dark:text-gray-300 mt-0.5">{{ $method->account_number }}</p>
                                    @endif
                                    @if ($method->instructions)
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 line-clamp-2">{{ $method->instructions }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center px-3 py-2 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700 gap-1.5">
                            <!-- Active toggle -->
                            <button
                                wire:click="toggleActive({{ $method->id }})"
                                draggable="false"
                                title="{{ $method->is_active ? 'Deactivate' : 'Activate' }}"
                                class="flex-1 inline-flex items-center justify-center gap-1 text-[10px] font-medium px-2 py-1 rounded-lg transition-colors
                                    {{ $method->is_active
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200' }}">
                                @if ($method->is_active)
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Active
                                @else
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636"/></svg>
                                    Inactive
                                @endif
                            </button>

                            <!-- Edit -->
                            <button
                                wire:click="openModal({{ $method->id }})"
                                draggable="false"
                                title="Edit"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>

                            <!-- Delete -->
                            <button
                                wire:click="delete({{ $method->id }})"
                                wire:confirm="Delete '{{ $method->name }}'? This cannot be undone."
                                draggable="false"
                                title="Delete"
                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 hover:bg-red-200 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif


    <!-- ── Add / Edit Modal ───────────────────────────────────────────────────── -->
    <div
        x-data="{ open: @entangle('showModal') }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;">

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
            x-on:click="open = false">
        </div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $editingId ? 'Edit Payment Method' : 'Add Payment Method' }}
                </h3>
                <button wire:click="closeModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1">

                <!-- Name -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                        placeholder="e.g. GCash — DJ Conquerors"/>
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <select wire:model="type"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent">
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Account Name + Number -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Name</label>
                        <input wire:model="accountName" type="text"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                            placeholder="Full name"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Account Number</label>
                        <input wire:model="accountNumber" type="text"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                            placeholder="09xxxxxxxxx"/>
                    </div>
                </div>

                <!-- Instructions -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea wire:model="instructions" rows="2"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent resize-none"
                        placeholder="e.g. Send to the number above, then screenshot and send to admin for acknowledgment."></textarea>
                </div>

                <!-- QR Code Upload -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">QR Code Image <span class="text-gray-400 font-normal">(optional, max 4MB)</span></label>

                    @if ($currentQrUrl && !$qrCode)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ $currentQrUrl }}" alt="Current QR" class="w-24 h-24 object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-white"/>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Current QR code. Upload a new one to replace it.</p>
                        </div>
                    @endif

                    <div
                        x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="dragging = false; $refs.qrInput.files = $event.dataTransfer.files; $refs.qrInput.dispatchEvent(new Event('change'))"
                        :class="dragging ? 'border-rose-400 bg-rose-50 dark:bg-rose-900/10' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40'"
                        class="relative border-2 border-dashed rounded-xl p-6 text-center transition-colors cursor-pointer"
                        @click="$refs.qrInput.click()">
                        <input
                            wire:model="qrCode"
                            x-ref="qrInput"
                            type="file"
                            accept="image/*"
                            class="hidden"/>

                        @if ($qrCode)
                            <div class="flex flex-col items-center gap-2">
                                <img src="{{ $qrCode->temporaryUrl() }}" class="w-28 h-28 object-contain rounded-lg mx-auto"/>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Ready to upload</p>
                            </div>
                        @else
                            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Drop QR image here, or <span class="text-rose-500 font-medium">browse</span></p>
                        @endif
                    </div>
                    @error('qrCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <!-- Active toggle -->
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$toggle('isActive')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200
                            {{ $isActive ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                            style="{{ $isActive ? 'transform: translateX(1.125rem)' : 'transform: translateX(0.125rem)' }}"></span>
                    </button>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ $isActive ? 'Active — visible to members' : 'Inactive — hidden from members' }}</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button wire:click="closeModal"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex items-center gap-2 text-sm font-medium bg-rose-600 hover:bg-rose-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Add Method' }}</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <style>
        [draggable] { user-select: none; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>
