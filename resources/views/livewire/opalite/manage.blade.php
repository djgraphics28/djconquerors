<?php

use Livewire\Volt\Component;
use App\Models\OpaliteDanceWinner;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $items = [];
    public $google_drive_url = '';
    public $entry_by = '';
    public $is_published = false;
    public $isOpen = false;
    public $opaliteId = null;
    public $search = '';
    public $previewUrl = null;
    public $showPreview = false;
    public $loading = false;

    public function mount()
    {
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->items = OpaliteDanceWinner::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('entry_by', 'like', '%' . $this->search . '%')->orWhere('google_drive_url', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    public function create()
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function edit($id)
    {
        $item = OpaliteDanceWinner::findOrFail($id);
        $this->opaliteId = $id;
        $this->google_drive_url = $item->google_drive_url;
        $this->entry_by = $item->entry_by;
        $this->is_published = (bool) $item->is_published;
        $this->isOpen = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'google_drive_url' => ['required', 'url'],
            'entry_by' => ['required', 'string', 'min:2', 'max:255'],
            'is_published' => ['boolean'],
        ]);

        if ($this->opaliteId) {
            $item = OpaliteDanceWinner::findOrFail($this->opaliteId);
            $item->update($validated);
            $message = 'Entry updated successfully';
        } else {
            $maxOrder = OpaliteDanceWinner::max('order') ?? 0;
            OpaliteDanceWinner::create(
                array_merge($validated, [
                    'order' => $maxOrder + 1,
                ]),
            );
            $message = 'Entry created successfully';
        }

        $this->closeModal();
        $this->loadItems();
        session()->flash('message', $message);
    }

    public function delete($id)
    {
        OpaliteDanceWinner::findOrFail($id)->delete();
        $this->loadItems();
        session()->flash('message', 'Entry deleted successfully');
    }

    public function togglePublish($id)
    {
        $item = OpaliteDanceWinner::findOrFail($id);
        $item->update(['is_published' => !$item->is_published]);
        $this->loadItems();
    }

    // FIXED: Updated sortItems method
    public function sortItems($orderedIds)
    {
        // Ensure we have an array and it's not empty
        if (!is_array($orderedIds) || empty($orderedIds)) {
            return;
        }

        // Use database transaction for consistency
        \DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $order => $id) {
                // Cast to integer and update order (starting from 1)
                OpaliteDanceWinner::where('id', (int) $id)->update(['order' => $order + 1]);
            }
        });

        // Reload items to reflect new order
        $this->loadItems();

        // Show success message
        $this->dispatch('notify', 'Order updated successfully');
    }

    public function openPreview($url)
    {
        $this->previewUrl = $this->getEmbedUrl($url);
        $this->showPreview = true;
    }

    public function closePreview()
    {
        $this->showPreview = false;
        $this->previewUrl = null;
    }

    private function getEmbedUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        // YouTube
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}?rel=0&modestbranding=1";
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}?rel=0&modestbranding=1";
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        // Google Drive
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        return $url;
    }

    private function getThumbnail($url)
    {
        if (empty($url)) {
            return null;
        }

        // YouTube
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/mqdefault.jpg";
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/mqdefault.jpg";
        }

        return null;
    }

    private function resetForm()
    {
        $this->reset(['google_drive_url', 'entry_by', 'is_published', 'opaliteId']);
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetForm();
    }
}; ?>
<div class="max-w-7xl mx-auto p-4 sm:p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Opalite Dance Winners</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage and organize video entries</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="create"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Entry
                </button>
            </div>
        </div>

        <!-- Search and Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex-1">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search entries..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100">
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        <span class="font-semibold">{{ count($items) }}</span> entries
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ collect($items)->where('is_published', true)->count() }} published
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cards Grid -->
    @if (count($items) > 0)
        <div id="sortable-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach ($items as $index => $item)
                <div wire:key="item-{{ $item['id'] }}" data-id="{{ $item['id'] }}"
                    class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-200 dark:border-gray-700 sortable-item">

                    <!-- Drag Handle -->
                    <div
                        class="drag-handle absolute top-3 left-3 z-20 cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                        </svg>
                    </div>

                    <!-- Position Badge -->
                    <div class="absolute top-3 right-3 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded z-10">
                        #{{ $index + 1 }}
                    </div>

                    <!-- Thumbnail -->
                    <div class="relative aspect-video bg-gray-100 dark:bg-gray-900 overflow-hidden cursor-pointer"
                        wire:click="openPreview('{{ $item['google_drive_url'] }}')">
                        @php $thumbnail = $this->getThumbnail($item['google_drive_url']); @endphp
                        @if ($thumbnail)
                            <img src="{{ $thumbnail }}" alt="Video thumbnail" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        <div
                            class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                            <div class="bg-white/90 rounded-full p-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $item['entry_by'] }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                              {{ $item['is_published'] ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $item['is_published'] ? 'Published' : 'Draft' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button wire:click="edit({{ $item['id'] }})"
                                class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </button>

                            <button wire:click="togglePublish({{ $item['id'] }})"
                                class="flex-1 px-3 py-2 text-sm font-medium {{ $item['is_published'] ? 'text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-300' : 'text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300' }} rounded-lg transition-colors flex items-center justify-center gap-2">
                                @if ($item['is_published'])
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Published
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Publish
                                @endif
                            </button>

                            <button wire:click="delete({{ $item['id'] }})"
                                onclick="return confirm('Are you sure you want to delete this entry?')"
                                class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No entries yet</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Get started by creating your first entry</p>
            <button wire:click="create"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Create First Entry
            </button>
        </div>
    @endif

    <!-- Preview Modal -->
    <div x-data="{
        show: @entangle('showPreview'),
        closePreview() {
            @this.closePreview();
        }
    }" x-show="show" x-on:keydown.escape.window="closePreview"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                x-on:click="closePreview">
            </div>

            <!-- Modal -->
            <div x-show="show" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">

                <!-- Close Button -->
                <button x-on:click="closePreview"
                    class="absolute top-4 right-4 z-10 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Video Container -->
                <div class="aspect-video bg-black">
                    @if ($previewUrl)
                        <iframe src="{{ $previewUrl }}" class="w-full h-full border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen loading="lazy">
                        </iframe>
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <div class="text-center text-white">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <p class="text-sm">Unable to load preview</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Form Modal -->
    <div x-data="{
        open: @entangle('isOpen'),
        closeModal() {
            @this.closeModal();
        }
    }" x-show="open" x-on:keydown.escape.window="closeModal"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                x-on:click="closeModal">
            </div>

            <!-- Modal -->
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">

                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $opaliteId ? 'Edit Entry' : 'Create Entry' }}
                        </h3>
                        <button x-on:click="closeModal"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="save" class="px-6 py-4 space-y-6">
                    <!-- URL Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Video URL
                        </label>
                        <input type="url" wire:model.live="google_drive_url" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="https://youtube.com/watch?v=... or https://drive.google.com/file/d/...">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Supported: YouTube, Vimeo, Google Drive
                        </p>
                    </div>

                    <!-- Entry By Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Entry Name
                        </label>
                        <input type="text" wire:model="entry_by" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-100"
                            placeholder="Enter name or identifier">
                    </div>

                    <!-- Publish Toggle -->
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="is_published" id="is_published"
                            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_published" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            Publish entry (visible to public)
                        </label>
                    </div>

                    <!-- Preview -->
                    @if ($google_drive_url)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Preview
                            </label>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                @php $embedUrl = $this->getEmbedUrl($google_drive_url); @endphp
                                @if ($embedUrl)
                                    <div class="aspect-video">
                                        <iframe src="{{ $embedUrl }}" class="w-full h-full border-0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                @else
                                    <div
                                        class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Enter a valid YouTube, Vimeo, or Google Drive URL
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" x-on:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            {{ $opaliteId ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2" class="fixed bottom-4 right-4 z-50">
            <div class="bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg">
                {{ session('message') }}
            </div>
        </div>
    @endif

    <!-- Sortable.js with proper Livewire integration - FIXED VERSION -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let sortableInstance = null;
            let isInitialized = false;

            function initializeSortable() {
                const container = document.getElementById('sortable-container');

                if (!container) {
                    isInitialized = false;
                    return;
                }

                // Destroy existing instance
                if (sortableInstance) {
                    sortableInstance.destroy();
                    sortableInstance = null;
                }

                sortableInstance = new Sortable(container, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    forceFallback: false,

                    onStart: function(evt) {
                        document.body.classList.add('sorting-active');
                        evt.item.classList.add('dragging-active');
                    },

                    onEnd: function(evt) {
                        document.body.classList.remove('sorting-active');
                        evt.item.classList.remove('dragging-active');

                        const items = Array.from(container.children);
                        const orderedIds = items.map(item => {
                            const id = parseInt(item.getAttribute('data-id'));
                            return isNaN(id) ? null : id;
                        }).filter(id => id !== null);

                        console.log('Sending new order to Livewire:', orderedIds);

                        if (orderedIds.length > 0) {
                            // Send to Livewire
                            @this.sortItems(orderedIds);
                        }
                    },

                    onSort: function(evt) {
                        // Update position numbers in real-time
                        const items = container.children;
                        for (let i = 0; i < items.length; i++) {
                            const badge = items[i].querySelector('.absolute.top-3.right-3');
                            if (badge) {
                                badge.textContent = `#${i + 1}`;
                            }
                        }
                    }
                });

                isInitialized = true;
                console.log('Sortable initialized');
            }

            // Initialize on load
            setTimeout(initializeSortable, 300);

            // Re-initialize when Livewire updates
            if (window.Livewire) {
                Livewire.hook('message.processed', () => {
                    // Small delay to ensure DOM is updated
                    setTimeout(initializeSortable, 100);
                });
            }

            // Also try to reinitialize periodically (fallback)
            setInterval(() => {
                const container = document.getElementById('sortable-container');
                if (container && container.children.length > 0 && !isInitialized) {
                    initializeSortable();
                }
            }, 1000);
        });
    </script>

    <style>
        /* Sortable.js styles */
        .sortable-ghost {
            opacity: 0.4;
            background: #c7d2fe;
            border: 2px dashed #4f46e5;
        }

        .sortable-chosen {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: rotate(2deg);
            z-index: 9999 !important;
        }

        .sortable-drag {
            opacity: 1 !important;
            cursor: grabbing !important;
            z-index: 10000 !important;
            transform: rotate(5deg) scale(1.02);
        }

        /* Prevent clicks during drag */
        .sorting-active {
            user-select: none;
        }

        .sorting-active .sortable-item * {
            pointer-events: none !important;
        }

        .sorting-active .drag-handle {
            pointer-events: auto !important;
            cursor: grabbing !important;
        }

        .dragging-active {
            opacity: 0.9;
        }

        /* Make drag handle more visible */
        .drag-handle {
            transition: all 0.2s ease;
            cursor: grab;
            z-index: 10;
        }

        .drag-handle:hover {
            transform: scale(1.2);
            color: #4f46e5;
        }

        .sorting-active .drag-handle {
            cursor: grabbing;
        }

        /* Ensure sortable items have proper stacking */
        .sortable-item {
            transition: transform 0.3s ease;
            position: relative;
        }

        /* Disable button interactions during sort */
        .sorting-active button {
            opacity: 0.6;
        }
    </style>
</div>
