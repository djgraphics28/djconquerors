<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use App\Models\GuideOption;

new class extends Component {
    use WithFileUploads;

    public $selectedItem = null;
    public $options = [];

    // Edit modal state
    public $showEditModal = false;
    public $editingOptionId = null;

    #[Validate('required|string|min:2|max:255')]
    public $editName = '';

    #[Validate('nullable|integer|min:0')]
    public $editOrder = 0;

    #[Validate('nullable|image|max:2048')]
    public $editImage;

    // Create modal state
    public $showCreateModal = false;

    #[Validate('required|string|min:2|max:255')]
    public $createName = '';

    #[Validate('nullable|integer|min:0')]
    public $createOrder = 0;

    #[Validate('nullable|image|max:2048')]
    public $createImage;

    public function mount()
    {
        $this->options = GuideOption::orderBy('order')->get();
    }

    public function selectItem($item)
    {
        $this->selectedItem = $item;
        return redirect()->route('guide.info', $item);
    }

    public function updateOptionOrder($orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            GuideOption::where('id', $id)->update(['order' => $order + 1]);
        }
        $this->options = GuideOption::orderBy('order')->get();
    }

    public function togglePublish($id): void
    {
        $option = GuideOption::findOrFail($id);
        $option->update(['is_published' => !$option->is_published]);
        $this->options = GuideOption::orderBy('order')->get();
    }

    public function openEditModal($id): void
    {
        $this->editingOptionId = $id;
        $option = GuideOption::findOrFail($id);
        $this->editName = $option->name;
        $this->editOrder = $option->order;
        $this->editImage = null;
        $this->resetValidation(['editName', 'editOrder', 'editImage']);
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|min:2|max:255',
            'editOrder' => 'nullable|integer|min:0',
            'editImage' => 'nullable|image|max:2048',
        ]);

        $option = GuideOption::findOrFail($this->editingOptionId);
        $option->update([
            'name'  => $this->editName,
            'order' => $this->editOrder ?? 0,
        ]);

        if ($this->editImage) {
            $option->clearMediaCollection('option-image');
            $option->addMedia($this->editImage->getRealPath())
                ->usingFileName($this->editImage->getClientOriginalName())
                ->toMediaCollection('option-image');
        }

        $this->showEditModal = false;
        $this->editingOptionId = null;
        $this->editImage = null;
        $this->options = GuideOption::orderBy('order')->get();
        session()->flash('success', 'Guide option updated.');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingOptionId = null;
        $this->editName = '';
        $this->editOrder = 0;
        $this->editImage = null;
    }

    public function removeOptionImage($id): void
    {
        $option = GuideOption::findOrFail($id);
        $option->clearMediaCollection('option-image');
        $this->options = GuideOption::orderBy('order')->get();
        session()->flash('success', 'Image removed.');
    }

    public function openCreateModal(): void
    {
        $this->createName = '';
        $this->createOrder = ($this->options->max('order') ?? 0) + 1;
        $this->createImage = null;
        $this->resetValidation(['createName', 'createOrder', 'createImage']);
        $this->showCreateModal = true;
    }

    public function saveCreate(): void
    {
        $this->validate([
            'createName'  => 'required|string|min:2|max:255',
            'createOrder' => 'nullable|integer|min:0',
            'createImage' => 'nullable|image|max:2048',
        ]);

        $option = GuideOption::create([
            'name'         => $this->createName,
            'order'        => $this->createOrder ?? 0,
            'is_published' => false,
        ]);

        if ($this->createImage) {
            $option->addMedia($this->createImage->getRealPath())
                ->usingFileName($this->createImage->getClientOriginalName())
                ->toMediaCollection('option-image');
        }

        $this->showCreateModal = false;
        $this->createName = '';
        $this->createOrder = 0;
        $this->createImage = null;
        $this->options = GuideOption::orderBy('order')->get();
        session()->flash('success', 'Guide option created.');
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->createName = '';
        $this->createOrder = 0;
        $this->createImage = null;
    }
}; ?>

<div>
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 rounded-2xl p-5 mb-5 text-white shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold">Manage Option Guide</h1>
                    <p class="text-indigo-200 text-sm">Select a category to manage its guides</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="openCreateModal"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white text-indigo-700 hover:bg-indigo-50 px-3 py-1.5 rounded-full transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    New
                </button>
                <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-medium">
                    {{ $options->count() }} {{ \Illuminate\Support\Str::plural('Category', $options->count()) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session('success'))
        <div class="mb-5 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Options Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-5">
            @if ($options->count() > 0)
                <div
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4"
                    x-data="{
                        draggedCard: null,
                        reorderCards(parent) {
                            const ids = Array.from(parent.querySelectorAll(':scope > [data-id]')).map(el => parseInt(el.getAttribute('data-id')));
                            $wire.updateOptionOrder(ids);
                        }
                    }">
                    @foreach ($options as $option)
                        <div
                            wire:key="option-{{ $option->id }}"
                            data-id="{{ $option->id }}"
                            draggable="true"
                            @dragstart="
                                draggedCard = $event.currentTarget;
                                setTimeout(() => $event.currentTarget.classList.add('opacity-30', 'scale-95'), 0);
                            "
                            @dragend="
                                $event.currentTarget.classList.remove('opacity-30', 'scale-95');
                                draggedCard = null;
                            "
                            @dragenter.prevent="$event.currentTarget.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-2', 'scale-105')"
                            @dragover.prevent
                            @dragleave="
                                if (!$event.currentTarget.contains($event.relatedTarget)) {
                                    $event.currentTarget.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2', 'scale-105');
                                }
                            "
                            @drop.prevent="
                                $event.currentTarget.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2', 'scale-105');
                                if (draggedCard && draggedCard !== $event.currentTarget) {
                                    const parent = $event.currentTarget.parentNode;
                                    const siblings = Array.from(parent.querySelectorAll(':scope > [data-id]'));
                                    const fromIdx = siblings.indexOf(draggedCard);
                                    const toIdx = siblings.indexOf($event.currentTarget);
                                    if (fromIdx < toIdx) {
                                        parent.insertBefore(draggedCard, $event.currentTarget.nextSibling);
                                    } else {
                                        parent.insertBefore(draggedCard, $event.currentTarget);
                                    }
                                    reorderCards(parent);
                                }
                            "
                            class="cursor-grab active:cursor-grabbing transition-transform duration-150">

                            <!-- Card body -->
                            <div class="group flex flex-col bg-gray-50 dark:bg-gray-700/50 rounded-xl border-2 transition-all duration-200 overflow-hidden
                                {{ $selectedItem === $option->id
                                    ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 shadow-md'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-md' }}">

                                <!-- Card top: image + name (no navigation) -->
                                <div class="flex flex-col items-center justify-center p-4 pb-3 flex-1 select-none min-h-[120px]">

                                    <!-- Image area with camera overlay -->
                                    <div class="relative mb-2">
                                        @if($option->getFirstMediaUrl('option-image'))
                                            <img src="{{ $option->getFirstMediaUrl('option-image') }}" alt="{{ $option->name }}"
                                                class="w-12 h-12 object-contain rounded-lg" />
                                        @else
                                            <div class="w-12 h-12 flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30 rounded-xl transition-colors duration-200">
                                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ strtoupper(substr($option->name, 0, 1)) }}</span>
                                            </div>
                                        @endif

                                        <!-- Camera overlay -->
                                        <button
                                            wire:click="openEditModal({{ $option->id }})"
                                            draggable="false"
                                            title="Update image"
                                            class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                            <svg class="w-4 h-4 text-white pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </button>
                                    </div>

                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 capitalize text-center leading-tight">{{ $option->name }}</span>

                                    @if(!$option->is_published)
                                        <span class="mt-1 text-[10px] text-amber-600 dark:text-amber-400 font-medium">Draft</span>
                                    @endif
                                </div>

                                <!-- Quick actions bar -->
                                <div class="flex items-center px-2 py-2 bg-white/60 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700/60 gap-1">
                                    <!-- Publish toggle -->
                                    <button
                                        wire:click="togglePublish({{ $option->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="togglePublish({{ $option->id }})"
                                        title="{{ $option->is_published ? 'Unpublish' : 'Publish' }}"
                                        draggable="false"
                                        class="flex-1 inline-flex items-center justify-center gap-1 text-[10px] font-medium px-1.5 py-1 rounded-lg transition-colors duration-150
                                            {{ $option->is_published
                                                ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50'
                                                : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/50' }}">
                                        @if($option->is_published)
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Live
                                        @else
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Draft
                                        @endif
                                    </button>

                                    <!-- Edit button -->
                                    <button
                                        wire:click="openEditModal({{ $option->id }})"
                                        draggable="false"
                                        title="Edit name/order"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Manage guides button -->
                                    <a href="{{ route('guide.info', $option->id) }}"
                                        wire:navigate
                                        draggable="false"
                                        title="Manage guides"
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 hover:bg-violet-200 dark:hover:bg-violet-900/50 transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">No guide options yet</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Create categories in
                        <a href="{{ route('guide.options') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Guide Options</a>
                        to get started.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Create Modal -->
    <div
        x-data="{ open: @entangle('showCreateModal') }"
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
            class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">New Guide Category</h3>
                <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input wire:model="createName" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="e.g. Binance, GCash..." />
                    @error('createName')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Display Order</label>
                    <input wire:model="createOrder" type="number" min="0"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    @error('createOrder')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Image -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image <span class="text-gray-400 font-normal">(optional)</span></label>

                    @if($createImage)
                        <div class="flex justify-center mb-3">
                            <img src="{{ $createImage->temporaryUrl() }}" class="w-20 h-20 rounded-xl object-cover border-2 border-indigo-300 shadow" />
                        </div>
                    @endif

                    <div x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="dragging = false"
                        :class="dragging ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400'"
                        class="relative flex flex-col items-center justify-center gap-1.5 border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-colors duration-200">
                        <input type="file" wire:model="createImage" accept="image/*"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-indigo-600 dark:text-indigo-400">Click</span> or drag & drop
                        </p>
                    </div>

                    <div wire:loading wire:target="createImage" class="mt-1.5 flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Uploading...
                    </div>
                    @error('createImage')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button wire:click="closeCreateModal"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="saveCreate"
                    wire:loading.attr="disabled"
                    wire:target="saveCreate"
                    class="inline-flex items-center gap-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="saveCreate">Create</span>
                    <span wire:loading wire:target="saveCreate" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Creating...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div
        x-data="{ open: @entangle('showEditModal') }"
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
            class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Edit Guide Category</h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <!-- Name -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                    <input wire:model="editName" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Option name" />
                    @error('editName')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Order -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Display Order</label>
                    <input wire:model="editOrder" type="number" min="0"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    @error('editOrder')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Image -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Image</label>

                    @if($editImage)
                        <div class="flex justify-center mb-3">
                            <img src="{{ $editImage->temporaryUrl() }}" class="w-20 h-20 rounded-xl object-cover border-2 border-indigo-300 shadow" />
                        </div>
                    @elseif($editingOptionId)
                        @php $editPreviewOption = $options->firstWhere('id', $editingOptionId); @endphp
                        @if($editPreviewOption?->getFirstMediaUrl('option-image'))
                            <div class="flex flex-col items-center gap-2 mb-3">
                                <img src="{{ $editPreviewOption->getFirstMediaUrl('option-image') }}" class="w-20 h-20 rounded-xl object-cover border border-gray-200 dark:border-gray-600" />
                                <button wire:click="removeOptionImage({{ $editingOptionId }})"
                                    class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove image
                                </button>
                            </div>
                        @endif
                    @endif

                    <div x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave="dragging = false"
                        @drop.prevent="dragging = false"
                        :class="dragging ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400'"
                        class="relative flex flex-col items-center justify-center gap-1.5 border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition-colors duration-200">
                        <input type="file" wire:model="editImage" accept="image/*"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-indigo-600 dark:text-indigo-400">Click</span> or drag & drop to replace
                        </p>
                    </div>

                    <div wire:loading wire:target="editImage" class="mt-1.5 flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Uploading...
                    </div>
                    @error('editImage')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button wire:click="closeEditModal"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="saveEdit"
                    wire:loading.attr="disabled"
                    wire:target="saveEdit"
                    class="inline-flex items-center gap-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="saveEdit">Save</span>
                    <span wire:loading wire:target="saveEdit" class="flex items-center gap-2">
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
</div>
