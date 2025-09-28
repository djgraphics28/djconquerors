<?php

use Livewire\Volt\Component;
use App\Models\Tutorial;

new class extends Component {
    public $tutorials;
    public $search = '';
    public $selectedTutorial = null;
    public $showPreview = false;

    public function mount()
    {
        $this->tutorials = Tutorial::where('is_published', true)
            ->latest()
            ->get();
    }

    public function getFilteredTutorialsProperty()
    {
        if (empty($this->search)) {
            return $this->tutorials;
        }

        return $this->tutorials->filter(function($tutorial) {
            return str_contains(strtolower($tutorial->title), strtolower($this->search));
        });
    }

    public function showVideoPreview($tutorialId)
    {
        $this->selectedTutorial = $this->tutorials->find($tutorialId);
        $this->showPreview = true;
    }

    public function closePreview()
    {
        $this->showPreview = false;
        $this->selectedTutorial = null;
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
}; ?>

<div>
    <!-- Breadcrumbs -->
    <div class="bg-gray-100 dark:bg-gray-800 py-4 mb-6">
        <div class="container mx-auto px-4">
            <div class="flex items-center text-gray-600 dark:text-gray-400 text-sm">
                <a href="/" class="hover:text-gray-800 dark:hover:text-gray-200">Home</a>
                <svg class="h-4 w-4 mx-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-gray-800 dark:text-gray-200">Tutorials</span>
            </div>
            <h1 class="text-3xl font-bold mt-2 dark:text-white">Video Tutorials</h1>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="max-w-xl mx-auto px-4">
            <div class="relative">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Search tutorials..."
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                >
                <div class="absolute right-3 top-2.5">
                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutorials Grid -->
    @if($this->filteredTutorials->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 px-4">
            @foreach($this->filteredTutorials as $tutorial)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-4">
                    @if($tutorial->thumbnail_url)
                        <div class="relative mb-3">
                            <img
                                src="{{ $tutorial->thumbnail_url }}"
                                alt="{{ $tutorial->title }}"
                                class="w-full h-40 object-cover rounded-lg"
                                loading="lazy"
                            >
                            <button
                                wire:click="showVideoPreview({{ $tutorial->id }})"
                                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 hover:bg-opacity-50 rounded-lg transition-all duration-300 group"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="w-full h-40 bg-gray-200 dark:bg-gray-700 rounded-lg mb-3 flex items-center justify-center">
                            <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                    <h2 class="text-lg font-bold mb-2 line-clamp-2 dark:text-white">{{ $tutorial->title }}</h2>
                    @if($tutorial->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-3 text-sm line-clamp-3">{{ $tutorial->description }}</p>
                    @endif
                    <div class="flex justify-between items-center">
                        <button
                            wire:click="showVideoPreview({{ $tutorial->id }})"
                            class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium"
                        >
                            Preview
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        @if($tutorial->video_url)
                            <a
                                href="{{ $tutorial->video_url }}"
                                target="_blank"
                                class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition-colors duration-200"
                            >
                                Watch Full
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 px-4">
            <svg class="h-16 w-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No tutorials found</h3>
            <p class="text-gray-600 dark:text-gray-400">
                @if($search)
                    No tutorials match your search "{{ $search }}". Try different keywords.
                @else
                    No tutorials are available at the moment.
                @endif
            </p>
        </div>
    @endif

    <!-- Video Preview Modal -->
    @if($showPreview && $selectedTutorial)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-on:keydown.escape.window="show = false; $wire.closePreview()"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        >
            <div
                x-on:click.outside="show = false; $wire.closePreview()"
                class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden"
            >
                <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-bold truncate dark:text-white">{{ $selectedTutorial->title }}</h3>
                    <button
                        wire:click="closePreview"
                        class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Video Preview</label>
                        <div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                            @if($selectedTutorial->video_url && $this->getVideoEmbedUrl($selectedTutorial->video_url))
                                <iframe
                                    src="{{ $this->getVideoEmbedUrl($selectedTutorial->video_url) }}"
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

                    @if($selectedTutorial->thumbnail_url)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thumbnail Preview</label>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2">
                            <img src="{{ $selectedTutorial->thumbnail_url }}" alt="Thumbnail preview" class="w-full h-32 object-cover rounded">
                        </div>
                    </div>
                    @endif
                </div>
                @if($selectedTutorial->description)
                    <div class="p-4 border-t dark:border-gray-700">
                        <p class="text-gray-600 dark:text-gray-400">{{ $selectedTutorial->description }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
