<?php

use Livewire\Volt\Component;
use App\Models\OpaliteDanceWinner;

new class extends Component {
    public $winners = [];
    public $consolationWinners = [];
    public $showVideoModal = false;
    public $currentVideoUrl = '';
    public $currentVideoTitle = '';

    public function mount()
    {
        $this->loadWinners();
    }

    public function loadWinners()
    {
        $allWinners = OpaliteDanceWinner::where('is_published', true)
            ->orderByRaw('CASE
                WHEN `order` = 1 THEN 1
                WHEN `order` = 2 THEN 2
                WHEN `order` = 3 THEN 3
                ELSE 4
            END')
            ->orderBy('order')
            ->get();

        $this->winners = $allWinners->take(3)->values();
        $this->consolationWinners = $allWinners->slice(3)->values();
    }

    public function openVideo($url, $title = '')
    {
        $embedUrl = $this->generateEmbedUrl($url);

        if ($embedUrl) {
            $this->currentVideoUrl = $embedUrl;
            $this->currentVideoTitle = $title;
            $this->showVideoModal = true;
        }
    }

    public function closeVideo()
    {
        $this->showVideoModal = false;
        $this->currentVideoUrl = '';
        $this->currentVideoTitle = '';
    }

    private function generateEmbedUrl($url)
    {
        if (empty($url)) return null;

        $url = trim($url);

        // YouTube
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}?rel=0&modestbranding=1";
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}?rel=0&modestbranding=1";
        }

        if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}?rel=0&modestbranding=1";
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return "https://player.vimeo.com/video/{$matches[1]}";
        }

        // Google Drive - Direct file ID extraction
        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        if (preg_match('/drive\.google\.com\/open\?id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://drive.google.com/file/d/{$matches[1]}/preview";
        }

        // Try to extract ID from any Google Drive URL
        if (str_contains($url, 'drive.google.com')) {
            // Try multiple patterns
            $patterns = [
                '/\/d\/([a-zA-Z0-9_-]+)/',
                '/id=([a-zA-Z0-9_-]+)/',
                '/([a-zA-Z0-9_-]{25,})/' // Google Drive IDs are usually 25+ chars
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $url, $matches)) {
                    return "https://drive.google.com/file/d/{$matches[1]}/preview";
                }
            }
        }

        return null;
    }

    public function getVideoThumbnail($url)
    {
        if (empty($url)) return null;

        // YouTube
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
        }

        if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return "https://img.youtube.com/vi/{$matches[1]}/hqdefault.jpg";
        }

        // Vimeo - we'll use a placeholder or fetch via API
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            // Try to get thumbnail from Vimeo API
            $videoId = $matches[1];
            try {
                $hash = unserialize(file_get_contents("https://vimeo.com/api/v2/video/{$videoId}.php"));
                return $hash[0]['thumbnail_medium'] ?? null;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}; ?>

<div class="min-h-screen bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
    <!-- Header -->
    <header class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                🏆 Opalite Dance Winners
            </h1>
            <p class="text-lg sm:text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto">
                Celebrating the extraordinary talent and creativity of our participants.
            </p>

            <!-- Stats -->
            <div class="flex flex-wrap justify-center gap-6 sm:gap-8 mb-8">
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-blue-600 dark:text-blue-400">
                        {{ count($winners) + count($consolationWinners) }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        Total Winners
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ count($winners) }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        Major Awards
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-bold text-purple-600 dark:text-purple-400">
                        {{ count($consolationWinners) }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                        Consolation Prizes
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pb-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">

            <!-- Major Winners -->
            @if(count($winners) > 0)
                <section class="mb-16">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                            🥇 Major Award Winners
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        @foreach($winners as $index => $winner)
                            @php
                                $position = $index + 1;
                                $positionLabels = ['1st', '2nd', '3rd'];
                                $positionIcons = ['🥇', '🥈', '🥉'];
                            @endphp

                            <div class="relative">
                                <!-- Position Badge -->
                                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                                    <div class="flex flex-col items-center">
                                        <div class="text-3xl sm:text-4xl mb-1">
                                            {{ $positionIcons[$index] ?? '🏆' }}
                                        </div>
                                        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600
                                                    text-white font-bold px-4 py-1 sm:px-6 sm:py-2 rounded-full
                                                    shadow-lg text-sm sm:text-base">
                                            {{ $positionLabels[$index] ?? $position . 'th' }} Place
                                        </div>
                                    </div>
                                </div>

                                <!-- Winner Card -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                                            border border-gray-200 dark:border-gray-700 transform transition-transform
                                            duration-300 hover:-translate-y-1 mt-6">

                                    <!-- Video Thumbnail -->
                                    <div class="relative aspect-video bg-gray-900 overflow-hidden">
                                        @if($thumbnail = $this->getVideoThumbnail($winner->google_drive_url))
                                            <img src="{{ $thumbnail }}"
                                                 alt="{{ $winner->entry_by }}'s performance"
                                                 class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-600 dark:text-gray-400"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif

                                        <!-- Play Button -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent
                                                    flex items-center justify-center opacity-0 hover:opacity-100
                                                    transition-opacity duration-300 cursor-pointer"
                                             wire:click="openVideo('{{ $winner->google_drive_url }}', '{{ $winner->entry_by }}')">
                                            <div class="bg-white/90 rounded-full p-3 sm:p-4">
                                                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-blue-600"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Winner Info -->
                                    <div class="p-6">
                                        <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                            {{ $winner->entry_by }}
                                        </h3>
                                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                                            Position #{{ $position }}
                                        </p>

                                        <!-- Watch Button -->
                                        <button wire:click="openVideo('{{ $winner->google_drive_url }}', '{{ $winner->entry_by }}')"
                                                class="w-full py-3 bg-gradient-to-r from-blue-500 to-blue-600
                                                       hover:from-blue-600 hover:to-blue-700 text-white font-semibold
                                                       rounded-lg transition-all duration-300 transform hover:scale-[1.02]
                                                       active:scale-[0.98] flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                            Watch Performance
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Consolation Prizes -->
            @if(count($consolationWinners) > 0)
                <section class="mb-16">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                            🌟 Consolation Prize Winners
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($consolationWinners as $index => $winner)
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden
                                        border border-gray-200 dark:border-gray-700 transform transition-transform
                                        duration-300 hover:-translate-y-1">

                                <!-- Position Number -->
                                <div class="absolute top-3 right-3 bg-gradient-to-r from-purple-500 to-purple-600
                                            text-white font-bold w-8 h-8 sm:w-10 sm:h-10 rounded-full
                                            flex items-center justify-center text-sm sm:text-base z-10">
                                    #{{ $index + 4 }}
                                </div>

                                <!-- Video Thumbnail -->
                                <div class="relative aspect-video bg-gray-900 overflow-hidden">
                                    @if($thumbnail = $this->getVideoThumbnail($winner->google_drive_url))
                                        <img src="{{ $thumbnail }}"
                                             alt="{{ $winner->entry_by }}'s performance"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-600 dark:text-gray-400"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif

                                    <!-- Play Button Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent
                                                flex items-center justify-center opacity-0 hover:opacity-100
                                                transition-opacity duration-300 cursor-pointer"
                                         wire:click="openVideo('{{ $winner->google_drive_url }}', '{{ $winner->entry_by }}')">
                                        <div class="bg-white/90 rounded-full p-2 sm:p-3">
                                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Winner Info -->
                                <div class="p-4 sm:p-6">
                                    <h4 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ $winner->entry_by }}
                                    </h4>

                                    <button wire:click="openVideo('{{ $winner->google_drive_url }}', '{{ $winner->entry_by }}')"
                                            class="w-full py-2 bg-gradient-to-r from-purple-500 to-purple-600
                                                   hover:from-purple-600 hover:to-purple-700 text-white font-medium
                                                   rounded-lg transition-all duration-300 text-sm sm:text-base
                                                   flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        </svg>
                                        Watch Performance
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Empty State -->
            @if(count($winners) === 0 && count($consolationWinners) === 0)
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-r from-blue-100 to-purple-100
                                dark:from-blue-900/30 dark:to-purple-900/30 rounded-full mb-6">
                        <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        Winners Announcement Coming Soon!
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 max-w-md mx-auto">
                        The results are being finalized. Check back later!
                    </p>
                </div>
            @endif
        </div>
    </main>

    <!-- Video Modal -->
    <div x-data="{
        show: @entangle('showVideoModal'),
        closeModal() {
            @this.closeVideo();
        }
    }" x-show="show" x-on:keydown.escape.window="closeModal"
        class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">

        <div class="flex min-h-full items-end justify-center p-0 text-center sm:items-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/90 transition-opacity"
                 x-on:click="closeModal">
            </div>

            <!-- Modal Content -->
            <div x-show="show"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden w-full h-screen sm:h-auto sm:my-8 sm:w-full sm:max-w-4xl">

                <!-- Close Button -->
                <button x-on:click="closeModal"
                        class="absolute top-4 right-4 z-10 bg-black/50 hover:bg-black/70 text-white rounded-full p-2 sm:p-3 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Video Title -->
                @if($currentVideoTitle)
                    <div class="absolute top-4 left-4 z-10 max-w-xs sm:max-w-md">
                        <div class="bg-black/50 backdrop-blur-sm rounded-lg p-3 sm:p-4 text-white">
                            <h3 class="font-semibold text-sm sm:text-lg truncate">{{ $currentVideoTitle }}</h3>
                        </div>
                    </div>
                @endif

                <!-- Video Container -->
                <div class="h-full flex items-center justify-center p-0 sm:p-4">
                    @if($currentVideoUrl)
                        <div class="w-full h-full sm:h-[75vh] bg-black rounded-none sm:rounded-xl overflow-hidden">
                            <iframe src="{{ $currentVideoUrl }}"
                                    class="w-full h-full border-0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin">
                            </iframe>
                        </div>
                    @else
                        <div class="text-center text-white p-8">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <p class="text-lg">Unable to load video</p>
                        </div>
                    @endif
                </div>

                <!-- Mobile Close Button -->
                <div class="absolute bottom-4 left-0 right-0 flex justify-center sm:hidden">
                    <button x-on:click="closeModal"
                            class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-lg
                                   backdrop-blur-sm transition-colors">
                        Close Video
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-8 px-4 text-center border-t border-gray-200 dark:border-gray-800">
        <p class="text-gray-600 dark:text-gray-400">
            © {{ date('Y') }} Opalite Dance Competition. All rights reserved.
        </p>
    </footer>

    <style>
        /* Video iframe fixes */
        iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .aspect-video {
                aspect-ratio: 16/9;
            }

            /* Force iframe to be visible on mobile */
            iframe {
                min-height: 100vh;
            }
        }

        /* Prevent iframe flickering */
        [x-cloak] {
            display: none !important;
        }

        /* Better scrolling for modal */
        body.modal-open {
            overflow: hidden;
        }

        /* Touch improvements */
        button {
            -webkit-tap-highlight-color: transparent;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading="lazy" to all images for better performance
            document.querySelectorAll('img').forEach(img => {
                img.loading = 'lazy';
            });

            // Handle modal state
            Livewire.on('openVideo', () => {
                document.body.classList.add('modal-open');
            });

            Livewire.on('closeVideo', () => {
                document.body.classList.remove('modal-open');
            });

            // Prevent iframe issues
            document.addEventListener('click', function(e) {
                const videoButton = e.target.closest('[wire\\:click*="openVideo"]');
                if (videoButton) {
                    // Force a small delay to ensure iframe is ready
                    setTimeout(() => {
                        const iframe = document.querySelector('iframe');
                        if (iframe) {
                            // Reset iframe to ensure proper loading
                            iframe.src = iframe.src;
                        }
                    }, 100);
                }
            });
        });
    </script>
</div>
