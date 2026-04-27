<?php

use Livewire\Volt\Component;
use App\Models\BinanceApkDownload;

new class extends Component {
    public ?BinanceApkDownload $apk = null;

    public function mount(): void
    {
        $this->apk = BinanceApkDownload::active()->latest()->first();
    }

    /**
     * Resolve the best download URL.
     * Priority: 1) Uploaded APK via Spatie Media Library, 2) Google Drive link (converted to direct download).
     */
    public function directDownloadUrl(): string
    {
        // Prefer directly uploaded APK file
        $apkUrl = $this->apk?->getFirstMediaUrl('apk');
        if ($apkUrl) {
            return $apkUrl;
        }

        $url = $this->apk?->download_url ?? '';

        if (!$url) {
            return '#';
        }

        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        return $url;
    }
}; ?>

<div>
    <!-- Hero Banner -->
    <div class="relative bg-gradient-to-br from-yellow-400 via-yellow-500 to-amber-500 rounded-2xl p-6 mb-6 text-white shadow-sm overflow-hidden">
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-white/10 rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-8 -left-4 w-24 h-24 bg-white/10 rounded-full pointer-events-none"></div>

        <div class="relative flex items-start gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                <!-- Binance logo icon -->
                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0L7.31 4.69l1.77 1.77L12 3.54l2.92 2.92 1.77-1.77L12 0zM4.69 7.31L0 12l4.69 4.69 1.77-1.77L3.54 12l2.92-2.92-1.77-1.77zM19.31 7.31l-1.77 1.77L20.46 12l-2.92 2.92 1.77 1.77L24 12l-4.69-4.69zM9.08 9.08L4.69 12l4.39 2.92L12 14.46l-1.54 1.54L12 17.54l1.54-1.54L12 14.46l-1.54-1.54 3.46-2.31-2.84-1.53zM12 9.54l2.92 2.92L12 15.38l-2.92-2.92L12 9.54zm2.92 3.38L12 17.54l2.92 1.54 1.77-1.77L14.92 12.92zM12 20.46l-2.92-2.92-1.77 1.77L12 24l4.69-4.69-1.77-1.77L12 20.46z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold mb-1">Download Binance for Android</h1>
                <p class="text-yellow-100 text-sm leading-relaxed max-w-xl">
                    Get the official Binance APK for Android. Download and install directly on your device to start trading.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">Android Only</span>
                    <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">Free Download</span>
                    @if ($apk?->version)
                        <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">v{{ $apk->version }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (!$apk)
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-14 text-center">
            <div class="w-16 h-16 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Download link not available yet</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Please check back soon.</p>
        </div>
    @else
        <div class="grid lg:grid-cols-5 gap-6">

            <!-- Left: Download Card -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Download Box -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 text-center">
                    <div class="w-20 h-20 bg-yellow-100 dark:bg-yellow-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4 overflow-hidden">
                        @if ($apk->getFirstMediaUrl('logo'))
                            <img src="{{ $apk->getFirstMediaUrl('logo') }}" alt="Binance Logo" class="w-full h-full object-contain p-1">
                        @else
                            <svg class="w-10 h-10 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0L7.31 4.69l1.77 1.77L12 3.54l2.92 2.92 1.77-1.77L12 0zM9.08 9.08L4.69 12l4.39 2.92L12 14.46l-1.54 1.54L12 17.54l1.54-1.54L12 14.46l-1.54-1.54 3.46-2.31-2.84-1.53zM12 9.54l2.92 2.92L12 15.38l-2.92-2.92L12 9.54zm0 10.92l-2.92-2.92-1.77 1.77L12 24l4.69-4.69-1.77-1.77L12 20.46z"/>
                            </svg>
                        @endif
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">{{ $apk->title }}</h2>
                    @if ($apk->version)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Version {{ $apk->version }}</p>
                    @endif

                    <a href="{{ $this->directDownloadUrl() }}"
                        class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-black font-semibold px-6 py-3 rounded-xl transition-colors duration-150 w-full justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download APK
                    </a>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">File will download automatically</p>
                </div>

                <!-- Android Only Notice -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">Android Devices Only</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">This APK is intended for Android smartphones and tablets only. It is not compatible with iOS (iPhone/iPad).</p>
                    </div>
                </div>
            </div>

            <!-- Right: Instructions -->
            <div class="lg:col-span-3 space-y-4">
                @if ($apk->description)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            About
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $apk->description }}</p>
                    </div>
                @endif

                <!-- Step-by-step install guide -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        How to Install (Android)
                    </h3>

                    <ol class="space-y-4">
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-yellow-400 text-black font-bold text-xs rounded-full flex items-center justify-center">1</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Tap the Download APK button</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">This will open the Google Drive link. Tap the download icon (↓) in Google Drive to save the APK file to your device.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-yellow-400 text-black font-bold text-xs rounded-full flex items-center justify-center">2</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Enable "Install Unknown Apps"</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Go to <strong class="text-gray-700 dark:text-gray-300">Settings → Apps → Special App Access → Install Unknown Apps</strong>, then allow your file manager or browser to install APKs.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-yellow-400 text-black font-bold text-xs rounded-full flex items-center justify-center">3</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Open the downloaded APK file</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Go to your <strong class="text-gray-700 dark:text-gray-300">Downloads</strong> folder and tap the <strong class="text-gray-700 dark:text-gray-300">Binance APK</strong> file to begin installation.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-yellow-400 text-black font-bold text-xs rounded-full flex items-center justify-center">4</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Tap "Install" and wait</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Android will prompt you to confirm the installation. Tap <strong class="text-gray-700 dark:text-gray-300">Install</strong> and wait for the process to complete.</p>
                            </div>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-yellow-400 text-black font-bold text-xs rounded-full flex items-center justify-center">5</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-white">Open Binance and log in</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Once installed, open the Binance app, log in with your credentials, and you're ready to trade!</p>
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- Security note -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800 p-4 flex gap-3">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Security Reminder</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Only download the APK from the link provided above. Never share your Binance login credentials with anyone. Enable 2FA on your account for added security.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
