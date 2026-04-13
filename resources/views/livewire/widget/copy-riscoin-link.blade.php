<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;
use App\Models\RiscoinLink;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

new class extends Component {
    #[Locked]
    public string $riscoinLinkUrl = '';

    #[Locked]
    public string $riscoinCode = '';

    #[Locked]
    public bool $hasActiveLink = false;

    #[Locked]
    public string $qrCodeSvg = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->riscoinCode = $user->riscoin_id ?? '';

        $activeLink = RiscoinLink::where('is_active', true)->first();

        if ($activeLink) {
            $this->hasActiveLink = true;
            $this->riscoinLinkUrl = $activeLink->url . '?code=' . $this->riscoinCode;

            $renderer = new ImageRenderer(new RendererStyle(300), new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $svg = $writer->writeString($this->riscoinLinkUrl);
            $this->qrCodeSvg = str_replace('width="300" height="300"', 'width="100%" height="100%"', $svg);
        }
    }
}; ?>

<div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">{{ __('Your Riscoin Link') }}</h3>
                    <p class="text-xs text-orange-100">{{ __('Share your personalized Riscoin registration link') }}</p>
                </div>
            </div>
            @if($riscoinCode)
                <div class="flex items-center gap-1.5 bg-white/20 rounded-lg px-3 py-1.5">
                    <svg class="w-3.5 h-3.5 text-orange-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                    <span class="text-xs font-mono font-semibold text-white">{{ $riscoinCode }}</span>
                </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="p-5">
            @if(!$hasActiveLink)
                {{-- No active link state --}}
                <div class="flex items-start gap-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-800/50 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('No active Riscoin link available') }}</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1">{{ __('Please contact an administrator to set up a Riscoin registration link.') }}</p>
                    </div>
                </div>
            @else
                <div x-data="{
                    copied: false,
                    showQr: false,
                    async copy() {
                        try {
                            const input = this.$refs.riscoinLinkInput;
                            input.select();
                            input.setSelectionRange(0, 99999);
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(input.value);
                            } else {
                                document.execCommand('copy');
                            }
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2500);
                        } catch (err) {
                            alert('Please manually copy: ' + this.$refs.riscoinLinkInput.value);
                        }
                    }
                }" class="space-y-3">

                    {{-- Link input row --}}
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 min-w-0 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <input type="text"
                                   readonly
                                   value="{{ $riscoinLinkUrl }}"
                                   x-ref="riscoinLinkInput"
                                   class="w-full pl-9 pr-4 py-3 bg-transparent outline-none text-gray-800 dark:text-gray-200 font-mono text-xs truncate cursor-pointer select-all"
                                   @click="$refs.riscoinLinkInput.select()" />
                        </div>

                        {{-- Copy button --}}
                        <button @click="copy()"
                                type="button"
                                :class="copied ? 'bg-green-500 hover:bg-green-600 focus:ring-green-400' : 'bg-orange-500 hover:bg-orange-600 focus:ring-orange-400'"
                                class="shrink-0 flex items-center gap-1.5 px-4 py-3 rounded-xl text-white text-sm font-medium transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-1">
                            <template x-if="!copied">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Copy
                                </span>
                            </template>
                            <template x-if="copied">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Copied!
                                </span>
                            </template>
                        </button>

                        {{-- QR Code button --}}
                        <button @click="showQr = true"
                                type="button"
                                class="shrink-0 flex items-center gap-1.5 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                            QR
                        </button>
                    </div>

                    {{-- Success toast --}}
                    <div x-show="copied"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         x-cloak
                         class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">{{ __('Riscoin link copied to clipboard!') }}</span>
                    </div>

                    {{-- Info row: your code --}}
                    <div class="flex items-center gap-2 p-3 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800">
                        <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-orange-700 dark:text-orange-300">
                            {{ __('Your Riscoin ID') }} <strong class="font-mono font-semibold">{{ $riscoinCode }}</strong> {{ __('is automatically embedded in this link.') }}
                        </p>
                    </div>

                    {{-- QR Code Modal --}}
                    <div x-show="showQr"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-cloak
                         @keydown.escape.window="showQr = false"
                         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                         style="display:none;">

                        {{-- Backdrop --}}
                        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showQr = false"></div>

                        {{-- Sheet / Modal --}}
                        <div x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                             x-transition:enter-end="translate-y-0 sm:scale-100 sm:opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="translate-y-0 sm:scale-100 sm:opacity-100"
                             x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 sm:opacity-0"
                             class="relative w-full sm:max-w-sm bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">

                            {{-- Handle (mobile) --}}
                            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-5 pt-4 pb-3 sm:pt-5 border-b border-gray-100 dark:border-gray-700">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Riscoin Link QR Code</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Scan to open the Riscoin registration page</p>
                                </div>
                                <button @click="showQr = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- QR Code --}}
                            <div class="flex flex-col items-center px-6 py-5 gap-3">
                                <div class="w-full max-w-[220px] aspect-square p-3 bg-white rounded-2xl shadow-sm border border-gray-200">
                                    {!! $qrCodeSvg !!}
                                </div>
                                <p class="text-xs font-mono text-gray-400 dark:text-gray-500 text-center break-all leading-relaxed px-1">{{ $riscoinLinkUrl }}</p>
                                @if($riscoinCode)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 text-xs font-semibold">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                        </svg>
                                        {{ $riscoinCode }}
                                    </span>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="px-5 pb-6 sm:pb-5">
                                <button @click="copy(); showQr = false"
                                        type="button"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold transition-colors active:scale-95">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Copy Link
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            @endif
        </div>
    </div>
</div>
