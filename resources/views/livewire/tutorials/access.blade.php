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
        $this->tutorials = Tutorial::where('is_published', true)->latest()->get();
    }

    public function getFilteredTutorialsProperty()
    {
        if (empty($this->search)) {
            return $this->tutorials;
        }

        return $this->tutorials->filter(function ($tutorial) {
            return str_contains(strtolower($tutorial->title), strtolower($this->search));
        });
    }

    public function selectTutorial($tutorialId)
    {
        $this->selectedTutorial = $this->tutorials->find($tutorialId);
        $this->showPreview = false;
    }

    public function closeVideo()
    {
        $this->selectedTutorial = null;
    }

    // Extract YouTube/Vimeo video ID
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800 dark:text-gray-200">Tutorials</span>
            </div>
            <h1 class="text-3xl font-bold mt-2 dark:text-white">Video Tutorials</h1>
        </div>
    </div>

    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main Video Player Section -->
            <div class="lg:w-8/12">
                @if ($selectedTutorial)
                    <!-- Video Player -->
                    <div class="bg-black rounded-lg overflow-hidden mb-4">
                        @if ($selectedTutorial->video_url && $this->getVideoEmbedUrl($selectedTutorial->video_url))
                            <div class="relative pb-[56.25%] h-0"> <!-- 16:9 aspect ratio -->
                                <iframe src="{{ $this->getVideoEmbedUrl($selectedTutorial->video_url) }}?autoplay=1"
                                    class="absolute top-0 left-0 w-full h-full" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        @else
                            <div
                                class="flex items-center justify-center h-96 text-gray-400 dark:text-gray-500 bg-gray-800">
                                <div class="text-center">
                                    <svg class="mx-auto h-16 w-16 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <p class="text-lg">No video available</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Video Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
                        <h2 class="text-xl font-bold mb-2 dark:text-white">{{ $selectedTutorial->title }}</h2>
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                <span><i class="fas fa-eye mr-1"></i> 1.2K views</span>
                                <span><i class="far fa-calendar mr-1"></i>
                                    {{ $selectedTutorial->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    class="flex items-center space-x-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    <i class="far fa-thumbs-up"></i>
                                    <span>Like</span>
                                </button>
                                <button
                                    class="flex items-center space-x-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    <i class="far fa-share-square"></i>
                                    <span>Share</span>
                                </button>
                            </div>
                        </div>

                        @if ($selectedTutorial->description)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-gray-700 dark:text-gray-300">{{ $selectedTutorial->description }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- Placeholder when no video is selected -->
                    <div
                        class="bg-gray-100 dark:bg-gray-800 rounded-lg h-96 flex flex-col items-center justify-center text-center p-6">
                        <i class="fas fa-play-circle text-gray-400 dark:text-gray-600 text-6xl mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700 dark:text-gray-300 mb-2">Select a tutorial to watch
                        </h3>
                        <p class="text-gray-500 dark:text-gray-400 max-w-md">Choose a video from the list on the right
                            to start watching.</p>
                    </div>
                @endif
            </div>

            <!-- Tutorials List Section -->
            <div class="lg:w-4/12">
                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tutorials..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                        <div class="absolute right-3 top-2.5">
                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Tutorials List -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700">
                        <h3 class="font-bold dark:text-white">Tutorials ({{ $this->filteredTutorials->count() }})</h3>
                    </div>

                    @if ($this->filteredTutorials->count() > 0)
                        <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
                            @foreach ($this->filteredTutorials as $tutorial)
                                <div wire:click="selectTutorial({{ $tutorial->id }})"
                                    class="flex p-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors {{ $selectedTutorial && $selectedTutorial->id === $tutorial->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    @if ($tutorial->thumbnail_url)
                                        <div class="flex-shrink-0 mr-3 relative">
                                            <img src="{{ $tutorial->thumbnail_url }}" alt="{{ $tutorial->title }}"
                                                class="w-40 h-24 object-cover rounded" loading="lazy">
                                            <div
                                                class="absolute bottom-1 right-1 bg-black bg-opacity-80 text-white text-xs px-1 rounded">
                                                10:30
                                            </div>
                                        </div>
                                    @else
                                        <div
                                            class="flex-shrink-0 w-40 h-24 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center mr-3">
                                            <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-sm line-clamp-2 dark:text-white mb-1">
                                            {{ $tutorial->title }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tutorial Channel</p>
                                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <span>1.2K views</span>
                                            <span class="mx-1">•</span>
                                            <span>2 days ago</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center">
                            <svg class="h-12 w-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No tutorials found</h3>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                @if ($search)
                                    No tutorials match your search "{{ $search }}".
                                @else
                                    No tutorials are available at the moment.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
