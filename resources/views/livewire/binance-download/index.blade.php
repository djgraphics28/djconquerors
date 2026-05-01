<?php

use Livewire\Volt\Component;
use App\Models\BinanceApkDownload;

new class extends Component {

    public function getApksProperty()
    {
        return BinanceApkDownload::active()
            ->orderByRaw("FIELD(app_type, 'binance', 'okx', 'bitget', 'other')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function directDownloadUrl(?BinanceApkDownload $apk): string
    {
        if (!$apk) {
            return '#';
        }

        $apkUrl = $apk->getFirstMediaUrl('apk');
        if ($apkUrl) {
            return $apkUrl;
        }

        $url = $apk->download_url ?? '';

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
    <!-- Page Header -->
    <div class="relative bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl p-6 mb-6 text-white shadow-sm overflow-hidden">
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-white/5 rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-8 -left-4 w-24 h-24 bg-white/5 rounded-full pointer-events-none"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold">Download Apps for Android</h1>
                    <p class="text-gray-400 text-sm">Official APK files for your trading apps</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="text-[11px] bg-white/10 text-gray-300 font-medium px-3 py-1 rounded-full">Android Only</span>
                <span class="text-[11px] bg-white/10 text-gray-300 font-medium px-3 py-1 rounded-full">Free Download</span>
                <span class="text-[11px] bg-white/10 text-gray-300 font-medium px-3 py-1 rounded-full">Official APKs</span>
            </div>
        </div>
    </div>

    @if ($this->apks->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-14 text-center">
            <div class="w-16 h-16 bg-gray-50 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">No downloads available yet</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Please check back soon.</p>
        </div>
    @else
        <!-- APK Cards Grid -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            @foreach ($this->apks as $apk)
                @php
                    $brandConfig = match($apk->app_type) {
                        'binance' => [
                            'gradient'  => 'from-yellow-400 via-yellow-500 to-amber-500',
                            'btnBg'     => 'bg-yellow-400 hover:bg-yellow-500',
                            'btnText'   => 'text-black',
                            'iconBg'    => 'bg-white/20',
                            'badgeBg'   => 'bg-white/20',
                            'textMuted' => 'text-yellow-100',
                        ],
                        'okx' => [
                            'gradient'  => 'from-gray-700 via-gray-800 to-gray-900',
                            'btnBg'     => 'bg-white hover:bg-gray-100',
                            'btnText'   => 'text-gray-900',
                            'iconBg'    => 'bg-white/10',
                            'badgeBg'   => 'bg-white/10',
                            'textMuted' => 'text-gray-300',
                        ],
                        'bitget' => [
                            'gradient'  => 'from-teal-400 via-cyan-500 to-blue-500',
                            'btnBg'     => 'bg-white hover:bg-gray-100',
                            'btnText'   => 'text-gray-900',
                            'iconBg'    => 'bg-white/20',
                            'badgeBg'   => 'bg-white/20',
                            'textMuted' => 'text-teal-100',
                        ],
                        default => [
                            'gradient'  => 'from-purple-500 via-purple-600 to-indigo-600',
                            'btnBg'     => 'bg-white hover:bg-gray-100',
                            'btnText'   => 'text-gray-900',
                            'iconBg'    => 'bg-white/20',
                            'badgeBg'   => 'bg-white/20',
                            'textMuted' => 'text-purple-100',
                        ],
                    };
                    $downloadUrl = $this->directDownloadUrl($apk);
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <!-- Brand Header -->
                    <div class="relative bg-gradient-to-br {{ $brandConfig['gradient'] }} p-5 text-white overflow-hidden">
                        <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/5 rounded-full pointer-events-none"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 {{ $brandConfig['iconBg'] }} rounded-xl flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if ($apk->getFirstMediaUrl('logo'))
                                    <img src="{{ $apk->getFirstMediaUrl('logo') }}" alt="{{ $apk->app_label }}" class="w-full h-full object-contain p-1">
                                @else
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h2 class="font-bold text-base leading-tight">{{ $apk->title }}</h2>
                                @if ($apk->version)
                                    <p class="text-xs {{ $brandConfig['textMuted'] }} mt-0.5">v{{ $apk->version }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="text-[10px] {{ $brandConfig['badgeBg'] }} text-white font-medium px-2.5 py-0.5 rounded-full">Android</span>
                            <span class="text-[10px] {{ $brandConfig['badgeBg'] }} text-white font-medium px-2.5 py-0.5 rounded-full">Free</span>
                            <span class="text-[10px] {{ $brandConfig['badgeBg'] }} text-white font-medium px-2.5 py-0.5 rounded-full">{{ $apk->app_label }}</span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 flex flex-col flex-1">
                        @if ($apk->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4 flex-1">{{ $apk->description }}</p>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        @if ($downloadUrl !== '#')
                            <a href="{{ $downloadUrl }}"
                                class="inline-flex items-center justify-center gap-2 {{ $brandConfig['btnBg'] }} {{ $brandConfig['btnText'] }} font-semibold px-4 py-2.5 rounded-xl transition-colors duration-150 text-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download APK
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center gap-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 font-medium px-4 py-2.5 rounded-xl text-sm cursor-not-allowed">
                                Not available yet
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Android Only Notice -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800 p-4 flex gap-3 mb-6">
            <div class="flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">Android Devices Only</p>
                <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">These APKs are intended for Android smartphones and tablets only. Not compatible with iOS (iPhone/iPad).</p>
            </div>
        </div>

        <!-- Installation Guide -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                How to Install on Android
            </h3>
            <ol class="space-y-4">
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-7 h-7 bg-gray-800 dark:bg-gray-600 text-white font-bold text-xs rounded-full flex items-center justify-center">1</span>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">Tap the Download APK button</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Tap the download button on the app you want. If it opens Google Drive, tap the download icon (↓) to save the file.</p>
                    </div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-7 h-7 bg-gray-800 dark:bg-gray-600 text-white font-bold text-xs rounded-full flex items-center justify-center">2</span>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">Enable "Install Unknown Apps"</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Go to <strong class="text-gray-700 dark:text-gray-300">Settings → Apps → Special App Access → Install Unknown Apps</strong>, then allow your browser or file manager.</p>
                    </div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-7 h-7 bg-gray-800 dark:bg-gray-600 text-white font-bold text-xs rounded-full flex items-center justify-center">3</span>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">Open the downloaded APK file</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Go to your <strong class="text-gray-700 dark:text-gray-300">Downloads</strong> folder and tap the APK file to begin installation.</p>
                    </div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-7 h-7 bg-gray-800 dark:bg-gray-600 text-white font-bold text-xs rounded-full flex items-center justify-center">4</span>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">Tap "Install" and wait</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Android will prompt you to confirm. Tap <strong class="text-gray-700 dark:text-gray-300">Install</strong> and wait for the process to complete.</p>
                    </div>
                </li>
                <li class="flex gap-4">
                    <span class="flex-shrink-0 w-7 h-7 bg-gray-800 dark:bg-gray-600 text-white font-bold text-xs rounded-full flex items-center justify-center">5</span>
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-white">Open the app and log in</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Once installed, open the app and sign in with your credentials. Enable 2FA for added security.</p>
                    </div>
                </li>
            </ol>
        </div>

        <!-- Security Note -->
        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800 p-4 flex gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Security Reminder</p>
                <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">Only download APKs from the links provided above. Never share your login credentials with anyone. Always enable 2FA on your trading accounts.</p>
            </div>
        </div>
    @endif
</div>
