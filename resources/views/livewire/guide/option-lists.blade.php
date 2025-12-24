<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\GuideOption;
use Livewire\Attributes\Validate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new class extends Component {
    use WithFileUploads;

    // Modal state
    public $showModal = false;
    public $editMode = false;
    public $guideOptionId = null;

    // Form fields
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|integer|min:0')]
    public $order = 0;

    #[Validate('nullable|boolean')]
    public $is_published = true;

    // Image handling
    public $image;
    public $currentImageUrl;
    public $removeImage = false;

    // List view
    public $search = '';
    public $sortField = 'order';
    public $sortDirection = 'asc';
    public $perPage = 10;

    public function mount()
    {
        // Initialize empty options array
    }

    // Sort function
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Open modal for create/edit
    public function openModal($id = null)
    {
        $this->resetForm();
        $this->resetValidation();

        if ($id) {
            $this->editMode = true;
            $this->guideOptionId = $id;
            $guideOption = GuideOption::findOrFail($id);

            $this->name = $guideOption->name;
            $this->order = $guideOption->order;
            $this->is_published = $guideOption->is_published;

            // Get current image if exists
            if ($guideOption->hasMedia('option-image')) {
                $this->currentImageUrl = $guideOption->getFirstMediaUrl('option-image');
            }
        } else {
            $this->editMode = false;
        }

        $this->showModal = true;
    }

    // Close modal
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    // Reset form
    public function resetForm()
    {
        $this->reset([
            'name',
            'order',
            'is_published',
            'image',
            'currentImageUrl',
            'removeImage',
            'guideOptionId',
        ]);
        $this->editMode = false;
        $this->order = 0;
        $this->is_published = true;
    }

    // Remove image
    public function removeImage()
    {
        if ($this->editMode && $this->guideOptionId) {
            $guideOption = GuideOption::find($this->guideOptionId);
            if ($guideOption && $guideOption->hasMedia('option-image')) {
                $guideOption->clearMediaCollection('option-image');
            }
        }
        $this->currentImageUrl = null;
        $this->removeImage = true;
        $this->image = null;
    }

    // Save/Update guide option
    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            $guideOption = GuideOption::findOrFail($this->guideOptionId);
            $guideOption->update([
                'name' => $this->name,
                'order' => $this->order,
                'is_published' => $this->is_published,
            ]);

            $message = 'Guide option updated successfully!';
        } else {
            $guideOption = GuideOption::create([
                'name' => $this->name,
                'order' => $this->order,
                'is_published' => $this->is_published,
            ]);

            $message = 'Guide option created successfully!';
        }

        // Handle image upload
        if ($this->image) {
            $guideOption->clearMediaCollection('option-image');
            $guideOption->addMedia($this->image->getRealPath())
                ->usingFileName($this->image->getClientOriginalName())
                ->toMediaCollection('option-image');
        } elseif ($this->removeImage && $this->editMode) {
            $guideOption->clearMediaCollection('option-image');
        }

        $this->closeModal();
        session()->flash('success', $message);
    }

    // Delete guide option
    public function delete($id)
    {
        $guideOption = GuideOption::findOrFail($id);

        // Delete associated media
        if ($guideOption->hasMedia('option-image')) {
            $guideOption->clearMediaCollection('option-image');
        }

        $guideOption->delete();

        session()->flash('success', 'Guide option deleted successfully!');
    }

    // Toggle publish status
    public function togglePublish($id)
    {
        $guideOption = GuideOption::findOrFail($id);
        $guideOption->update([
            'is_published' => !$guideOption->is_published
        ]);

        session()->flash('success', 'Status updated successfully!');
    }

    public function with()
    {
        $query = GuideOption::query();

        // Apply search
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $guideOptions = $query->paginate($this->perPage);

        return [
            'guideOptions' => $guideOptions,
        ];
    }
}; ?>

<div class="p-6">
    <!-- Success Message -->
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Header with Search and Create Button -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Guide Options</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your guide options and their display order</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Search guide options...">
            </div>

            <!-- Create Button -->
            <button wire:click="openModal"
                class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Guide Option
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Order
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Image
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <button wire:click="sortBy('name')" class="flex items-center space-x-1">
                                <span>Name</span>
                                @if($sortField === 'name')
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($guideOptions as $option)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 font-medium">
                                    {{ $option->order }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($option->hasMedia('option-image'))
                                    <img src="{{ $option->getFirstMediaUrl('option-image') }}"
                                         alt="{{ $option->name }}"
                                         class="w-12 h-12 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                                @else
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $option->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="togglePublish({{ $option->id }})"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition duration-200
                                        {{ $option->is_published
                                            ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50'
                                            : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/50' }}">
                                    {{ $option->is_published ? 'Published' : 'Draft' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button wire:click="openModal({{ $option->id }})"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition duration-200"
                                        title="Edit">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $option->id }})"
                                        onclick="return confirm('Are you sure you want to delete this guide option?')"
                                        class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition duration-200"
                                        title="Delete">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">No guide options found</p>
                                    <p class="mb-4">Get started by creating your first guide option</p>
                                    <button wire:click="openModal"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Guide Option
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($guideOptions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $guideOptions->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal - Right Side Panel -->
    <div x-data="{ open: @entangle('showModal') }" x-show="open" x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        <!-- Overlay -->
        <div x-show="open" x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
            x-on:click="open = false">
        </div>

        <!-- Modal Panel -->
        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div x-show="open"
                x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                class="w-screen max-w-2xl">
                <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editMode ? 'Edit Guide Option' : 'New Guide Option' }}
                        </h2>
                        <button wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="px-6 py-4">
                            <form wire:submit.prevent="save" class="space-y-6">
                                <!-- Image Upload -->
                                <div class="flex items-start space-x-6">
                                    <div class="flex-shrink-0">
                                        @if ($editMode && $currentImageUrl && !$removeImage)
                                            <img class="h-24 w-24 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                                src="{{ $currentImageUrl }}"
                                                alt="Current image">
                                        @elseif($image)
                                            <img class="h-24 w-24 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                                                src="{{ $image->temporaryUrl() }}" alt="New image">
                                        @else
                                            <div
                                                class="h-24 w-24 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600">
                                                <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Option Image
                                        </label>
                                        <div class="flex flex-col space-y-3">
                                            <div>
                                                <input type="file" wire:model="image"
                                                    accept="image/*"
                                                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                        file:mr-4 file:py-2 file:px-4 file:rounded-lg
                                                        file:border-0 file:text-sm file:font-medium
                                                        file:bg-blue-50 dark:file:bg-blue-900/30
                                                        file:text-blue-700 dark:file:text-blue-300
                                                        hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50">
                                                @error('image')
                                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            @if ($editMode && ($currentImageUrl || $image))
                                                <button type="button" wire:click="removeImage"
                                                    class="inline-flex items-center justify-center px-3 py-2 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition duration-200 text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Remove Image
                                                </button>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            Upload an image for this option. Max 5MB. JPG, PNG, GIF, SVG.
                                        </p>
                                    </div>
                                </div>

                                <!-- Form Fields -->
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Name *
                                        </label>
                                        <input type="text" wire:model="name"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Enter option name"
                                            required>
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Order -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Order
                                        </label>
                                        <input type="number" wire:model="order" min="0"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Display order (0 for first)">
                                        @error('order')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Publish Status -->
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model="is_published" id="is_published"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800">
                                        <label for="is_published" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                            Publish this option
                                        </label>
                                    </div>
                                    @error('is_published')
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Action Buttons -->
                                <div
                                    class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <button type="button" wire:click="closeModal"
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200 font-medium">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        {{ $editMode ? 'Update' : 'Create' }} Option
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
