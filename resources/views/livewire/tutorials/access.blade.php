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
        // Auto-detect video type from URL
        if (str_contains($url, 'drive.google.com')) {
            // For Google Drive, we'll use a direct approach
            return $this->getDirectDriveVideoUrl($url);
        } else {
            // YouTube/Vimeo
            $videoId = $this->getVideoId($url);
            if (str_contains($url, 'youtube')) {
                return $videoId ? "https://www.youtube.com/embed/{$videoId}?rel=0" : null;
            } elseif (str_contains($url, 'vimeo')) {
                return $videoId ? "https://player.vimeo.com/video/{$videoId}" : null;
            }
        }
        return null;
    }

    private function getDirectDriveVideoUrl($url)
    {
        // Extract file ID from Google Drive URL
        $fileId = $this->extractDriveFileId($url);

        if ($fileId) {
            // Use the direct preview URL for Google Drive
            return "https://drive.google.com/file/d/{$fileId}/preview";
        }

        return $url;
    }

    private function extractDriveFileId($url)
    {
        // Handle different Google Drive URL formats
        if (str_contains($url, '/file/d/')) {
            // Format: https://drive.google.com/file/d/FILE_ID/view
            preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches);
            return $matches[1] ?? null;
        } elseif (str_contains($url, 'id=')) {
            // Format: https://drive.google.com/open?id=FILE_ID
            preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches);
            return $matches[1] ?? null;
        } elseif (str_contains($url, '/uc?')) {
            // Format: https://drive.google.com/uc?id=FILE_ID
            preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    // Check if it's a direct video file
    public function isDirectVideoUrl($url)
    {
        $directVideoExtensions = ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.wmv'];

        foreach ($directVideoExtensions as $extension) {
            if (str_contains($url, $extension)) {
                return true;
            }
        }

        return false;
    }

    // Get video type for display
    public function getVideoType($url)
    {
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        } elseif (str_contains($url, 'drive.google.com')) {
            return 'drive';
        } elseif (str_contains($url, 'vimeo.com')) {
            return 'vimeo';
        } elseif ($this->isDirectVideoUrl($url)) {
            return 'direct';
        }
        return 'other';
    }

    // Get video type badge color
    public function getVideoTypeBadge($url)
    {
        $type = $this->getVideoType($url);

        switch ($type) {
            case 'youtube':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'drive':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'vimeo':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'direct':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    }
}; ?>

<div>
    <!-- Header -->
    <div class="max-w-10xl mx-auto">
        <!-- Breadcrumb Navigation -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('tutorials.access') }}"
                        class="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Video Tutorials
                    </a>
                </li>
            </ol>
        </nav>

        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Main Video Player Section -->
                <div class="lg:w-8/12">
                    @if ($selectedTutorial)
                        <!-- Video Player -->
                        <div class="bg-black rounded-lg overflow-hidden mb-4">
                            @if ($selectedTutorial->video_url)
                                @if ($this->getVideoType($selectedTutorial->video_url) === 'drive')
                                    <!-- Google Drive with better error handling -->
                                    <div class="relative pb-[56.25%] h-0">
                                        <iframe src="{{ $this->getVideoEmbedUrl($selectedTutorial->video_url) }}"
                                            class="absolute top-0 left-0 w-full h-full" frameborder="0"
                                            allow="autoplay; fullscreen" allowfullscreen
                                            onerror="this.style.display='none'; document.getElementById('drive-fallback-{{ $selectedTutorial->id }}').style.display='block';">
                                        </iframe>
                                        <div id="drive-fallback-{{ $selectedTutorial->id }}"
                                            class="absolute top-0 left-0 w-full h-full bg-gray-800 hidden flex items-center justify-center text-center p-6">
                                            <div>
                                                <svg class="mx-auto h-16 w-16 mb-3 text-gray-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                <h3 class="text-lg font-medium text-white mb-2">Google Drive Video</h3>
                                                <p class="text-sm text-gray-300 mb-4">
                                                    If the video doesn't load, please check:
                                                </p>
                                                <ul
                                                    class="text-xs text-gray-400 text-left max-w-md mx-auto mb-4 space-y-1">
                                                    <li>• The file is shared with "Anyone with the link can view"</li>
                                                    <li>• You're signed into Google account</li>
                                                    <li>• The file is a supported video format</li>
                                                </ul>
                                                <a href="{{ $selectedTutorial->video_url }}" target="_blank"
                                                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                    Open in Google Drive
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($this->getVideoEmbedUrl($selectedTutorial->video_url))
                                    <!-- YouTube, Vimeo, or other embeddable URLs -->
                                    <div class="relative pb-[56.25%] h-0">
                                        <iframe src="{{ $this->getVideoEmbedUrl($selectedTutorial->video_url) }}"
                                            class="absolute top-0 left-0 w-full h-full" frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                    </div>
                                @else
                                    <!-- Direct video file or unsupported URL -->
                                    <div
                                        class="flex items-center justify-center h-96 text-gray-400 dark:text-gray-500 bg-gray-800">
                                        <div class="text-center">
                                            <svg class="mx-auto h-16 w-16 mb-3" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <p class="text-lg">Video format not supported</p>
                                            <a href="{{ $selectedTutorial->video_url }}" target="_blank"
                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors mt-2">
                                                Open Video Link
                                            </a>
                                        </div>
                                    </div>
                                @endif
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
                            <div class="flex items-start justify-between mb-2">
                                <h2 class="text-xl font-bold dark:text-white">{{ $selectedTutorial->title }}</h2>
                                <span
                                    class="px-2 py-1 text-xs font-medium rounded-full {{ $this->getVideoTypeBadge($selectedTutorial->video_url) }}">
                                    {{ ucfirst($this->getVideoType($selectedTutorial->video_url)) }}
                                </span>
                            </div>
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
                                    <p class="text-gray-700 dark:text-gray-300">{{ $selectedTutorial->description }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- Placeholder when no video is selected -->
                        <div
                            class="bg-gray-100 dark:bg-gray-800 rounded-lg h-96 flex flex-col items-center justify-center text-center p-6">
                            <i class="fas fa-play-circle text-gray-400 dark:text-gray-600 text-6xl mb-4"></i>
                            <h3 class="text-xl font-medium text-gray-700 dark:text-gray-300 mb-2">Select a tutorial to
                                watch
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-md">Choose a video from the list on the
                                right
                                to start watching.</p>
                        </div>
                    @endif
                </div>

                <!-- Tutorials List Section -->
                <div class="lg:w-4/12">
                    <!-- Search Bar -->
                    <div class="mb-6">
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="search" type="text"
                                placeholder="Search tutorials..."
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                            <div class="absolute right-3 top-2.5">
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-300" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Tutorials List -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 border-b dark:border-gray-700">
                            <h3 class="font-bold dark:text-white">Tutorials ({{ $this->filteredTutorials->count() }})
                            </h3>
                        </div>

                        @if ($this->filteredTutorials->count() > 0)
                            <div class="max-h-[calc(100vh-200px)] overflow-y-auto">
                                @foreach ($this->filteredTutorials as $tutorial)
                                    <div wire:click="selectTutorial({{ $tutorial->id }})"
                                        class="flex p-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors {{ $selectedTutorial && $selectedTutorial->id === $tutorial->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                        @if ($tutorial->thumbnail_url)
                                            <div class="flex-shrink-0 mr-3 relative">
                                                <img src="{{ $tutorial->thumbnail_url }}"
                                                    alt="{{ $tutorial->title }}"
                                                    class="w-40 h-24 object-cover rounded" loading="lazy">
                                                <div
                                                    class="absolute bottom-1 right-1 bg-black bg-opacity-80 text-white text-xs px-1 rounded">
                                                    10:30
                                                </div>
                                                <!-- Video Type Badge -->
                                                <div class="absolute top-1 left-1">
                                                    <span
                                                        class="px-1 py-0.5 text-xs font-medium rounded {{ $this->getVideoTypeBadge($tutorial->video_url) }}">
                                                        {{ ucfirst($this->getVideoType($tutorial->video_url)) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <div
                                                class="flex-shrink-0 w-40 h-24 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center mr-3 relative">
                                                <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <!-- Video Type Badge -->
                                                <div class="absolute top-1 left-1">
                                                    <span
                                                        class="px-1 py-0.5 text-xs font-medium rounded {{ $this->getVideoTypeBadge($tutorial->video_url) }}">
                                                        {{ ucfirst($this->getVideoType($tutorial->video_url)) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-sm line-clamp-2 dark:text-white mb-1">
                                                {{ $tutorial->title }}</h4>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Tutorial Channel
                                            </p>
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
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No tutorials found
                                </h3>
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
</div>
