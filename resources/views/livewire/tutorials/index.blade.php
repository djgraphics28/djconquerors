<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public $tutorials;
    public $title;
    public $description;
    public $video_url;
    public $thumbnail_url;
    public $is_published = false;
    public $isOpen = false;
    public $tutorialId;
    public $search = '';

    public function mount()
    {
        $this->loadTutorials();
    }

    public function loadTutorials()
    {
        $this->tutorials = \App\Models\Tutorial::query()
            ->when($this->search, function($query) {
                $query->where('title', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->get();
    }

    public function create()
    {
        $this->resetInputs();
        $this->openModal();
    }

    public function store()
    {
        $this->validate([
            'title' => 'required|min:3',
            'video_url' => 'required|url',
            'thumbnail_url' => 'nullable|url',
            'description' => 'nullable'
        ]);

        \App\Models\Tutorial::create([
            'title' => $this->title,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'is_published' => $this->is_published
        ]);

        $this->closeModal();
        $this->resetInputs();
        $this->dispatch('tutorial-saved');
        $this->loadTutorials();
    }

    public function edit($id)
    {
        $tutorial = \App\Models\Tutorial::findOrFail($id);
        $this->tutorialId = $id;
        $this->title = $tutorial->title;
        $this->description = $tutorial->description;
        $this->video_url = $tutorial->video_url;
        $this->thumbnail_url = $tutorial->thumbnail_url;
        $this->is_published = $tutorial->is_published;

        $this->openModal();
    }

    public function update()
    {
        $this->validate([
            'title' => 'required|min:3',
            'video_url' => 'required|url',
            'thumbnail_url' => 'nullable|url',
            'description' => 'nullable'
        ]);

        $tutorial = \App\Models\Tutorial::findOrFail($this->tutorialId);
        $tutorial->update([
            'title' => $this->title,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'thumbnail_url' => $this->thumbnail_url,
            'is_published' => $this->is_published
        ]);

        $this->closeModal();
        $this->resetInputs();
        $this->dispatch('tutorial-updated');
        $this->loadTutorials();
    }

    public function delete($id)
    {
        \App\Models\Tutorial::findOrFail($id)->delete();
        $this->loadTutorials();
        $this->dispatch('tutorial-deleted');
    }

    public function togglePublish($id)
    {
        $tutorial = \App\Models\Tutorial::findOrFail($id);
        $tutorial->update([
            'is_published' => !$tutorial->is_published
        ]);
        $this->loadTutorials();
    }

    // Extract YouTube/Vimeo video ID for preview
    public function getVideoId($url)
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            // YouTube
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
            return $matches[1] ?? null;
        } elseif (str_contains($url, 'vimeo.com')) {
            // Vimeo
            preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|)(\d+)(?:|\/\?)/', $url, $matches);
            return $matches[2] ?? null;
        }
        return null;
    }

    public function getVideoEmbedUrl($url)
    {
        $videoId = $this->getVideoId($url);
        if (str_contains($url, 'youtube')) {
            return "https://www.youtube.com/embed/{$videoId}";
        } elseif (str_contains($url, 'vimeo')) {
            return "https://player.vimeo.com/video/{$videoId}";
        }
        return null;
    }

    private function resetInputs()
    {
        $this->title = '';
        $this->description = '';
        $this->video_url = '';
        $this->thumbnail_url = '';
        $this->is_published = false;
        $this->tutorialId = null;
    }

    private function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 lg:p-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Manage Tutorials</h2>
                    <flux:button
                        wire:click="create"
                        variant="primary"
                        data-test="new-tutorial-button">
                        Create Tutorial
                    </flux:button>
                </div>

                @if (session()->has('message'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-900 dark:border-green-700 dark:text-green-300">
                        {{ session('message') }}
                    </div>
                @endif

                <!-- Search -->
                <div class="mb-6">
                    <flux:input
                        wire:model.live="search"
                        :label="__('Search Tutorials')"
                        type="text"
                        :placeholder="__('Search by title...')"
                        data-test="search-tutorials-input"
                    />
                </div>

                <!-- Table -->
                <div class="overflow-x-auto relative">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach($tutorials as $tutorial)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center">
                                            @if($tutorial->thumbnail_url)
                                                <img src="{{ $tutorial->thumbnail_url }}" alt="{{ $tutorial->title }}" class="w-10 h-10 rounded-lg object-cover mr-3">
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium">{{ $tutorial->title }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                                    {{ $tutorial->video_url }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <div class="text-sm max-w-md truncate">{{ $tutorial->description ?? 'No description' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $tutorial->is_published ?
                                                       'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
                                                       'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
                                            {{ $tutorial->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-2">
                                            <flux:button
                                                wire:click="edit({{ $tutorial->id }})"
                                                variant="ghost"
                                                size="sm"
                                                data-test="edit-tutorial-{{ $tutorial->id }}">
                                                Edit
                                            </flux:button>
                                            <flux:button
                                                wire:click="togglePublish({{ $tutorial->id }})"
                                                variant="ghost"
                                                size="sm"
                                                class="{{ $tutorial->is_published ? 'text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300' : 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300' }}"
                                                data-test="toggle-publish-{{ $tutorial->id }}">
                                                {{ $tutorial->is_published ? 'Unpublish' : 'Publish' }}
                                            </flux:button>
                                            <flux:button
                                                wire:click="delete({{ $tutorial->id }})"
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                                onclick="return confirm('Are you sure you want to delete this tutorial?')"
                                                data-test="delete-tutorial-{{ $tutorial->id }}">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($tutorials->isEmpty())
                        <div class="text-center py-12">
                            <div class="text-gray-400 dark:text-gray-500 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No tutorials found</h3>
                            <p class="text-gray-500 dark:text-gray-400">Get started by creating your first tutorial.</p>
                        </div>
                    @endif
                </div>

                <!-- Right Side Modal -->
                <div x-data="{ open: @entangle('isOpen') }"
                     x-show="open"
                     x-on:keydown.escape.window="open = false"
                     class="fixed inset-0 z-50 overflow-hidden"
                     style="display: none;">
                    <!-- Overlay -->
                    <div x-show="open"
                         x-transition:enter="ease-in-out duration-500"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in-out duration-500"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                         x-on:click="open = false">
                    </div>

                    <!-- Modal Panel -->
                    <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                        <div x-show="open"
                             x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:enter-start="translate-x-full"
                             x-transition:enter-end="translate-x-0"
                             x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:leave-start="translate-x-0"
                             x-transition:leave-end="translate-x-full"
                             class="w-screen max-w-4xl">
                            <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                                <!-- Header -->
                                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $tutorialId ? 'Edit Tutorial' : 'Create Tutorial' }}
                                    </h2>
                                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 overflow-y-auto">
                                    <div class="px-6 py-4">
                                        <form wire:submit.prevent="{{ $tutorialId ? 'update' : 'store' }}" class="space-y-6">
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                                <!-- Left Column - Form -->
                                                <div class="space-y-6">
                                                    <!-- Title -->
                                                    <flux:input
                                                        wire:model="title"
                                                        :label="__('Title')"
                                                        type="text"
                                                        required
                                                        :placeholder="__('Enter tutorial title')"
                                                        data-test="title-input"
                                                    />

                                                    <!-- Description -->
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                                        <textarea
                                                            wire:model="description"
                                                            rows="4"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                            placeholder="Enter tutorial description"
                                                        ></textarea>
                                                    </div>

                                                    <!-- Video URL -->
                                                    <flux:input
                                                        wire:model="video_url"
                                                        :label="__('Video URL')"
                                                        type="url"
                                                        required
                                                        :placeholder="__('Enter YouTube or Vimeo URL')"
                                                        data-test="video-url-input"
                                                    />

                                                    <!-- Thumbnail URL -->
                                                    <flux:input
                                                        wire:model="thumbnail_url"
                                                        :label="__('Thumbnail URL')"
                                                        type="url"
                                                        :placeholder="__('Enter thumbnail image URL')"
                                                        data-test="thumbnail-url-input"
                                                    />

                                                    <!-- Published Status -->
                                                    <div class="flex items-center">
                                                        <flux:checkbox
                                                            wire:model="is_published"
                                                            :label="__('Publish Tutorial')"
                                                            data-test="is-published-checkbox"
                                                        />
                                                    </div>
                                                </div>

                                                <!-- Right Column - Video Preview -->
                                                <div class="space-y-6">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video Preview</label>
                                                        <div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                                                            @if($video_url && $this->getVideoEmbedUrl($video_url))
                                                                <iframe
                                                                    src="{{ $this->getVideoEmbedUrl($video_url) }}"
                                                                    class="w-full h-64"
                                                                    frameborder="0"
                                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                                    allowfullscreen>
                                                                </iframe>
                                                            @else
                                                                <div class="flex items-center justify-center h-64 text-gray-400 dark:text-gray-500">
                                                                    <div class="text-center">
                                                                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                                        </svg>
                                                                        <p class="text-sm">Enter a YouTube or Vimeo URL to see preview</p>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <!-- Thumbnail Preview -->
                                                    @if($thumbnail_url)
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thumbnail Preview</label>
                                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2">
                                                            <img src="{{ $thumbnail_url }}" alt="Thumbnail preview" class="w-full h-32 object-cover rounded">
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                                                <flux:button
                                                    type="button"
                                                    wire:click="closeModal"
                                                    data-test="cancel-tutorial-button"
                                                >
                                                    Cancel
                                                </flux:button>
                                                <flux:button
                                                    type="submit"
                                                    variant="primary"
                                                    data-test="submit-tutorial-button"
                                                >
                                                    {{ $tutorialId ? 'Update' : 'Create' }} Tutorial
                                                </flux:button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
