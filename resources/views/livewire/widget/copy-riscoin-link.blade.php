<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;
use App\Models\RiscoinLink;

new class extends Component {
    #[Locked]
    public string $riscoinLinkUrl = '';

    #[Locked]
    public string $riscoinCode = '';

    #[Locked]
    public bool $hasActiveLink = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->riscoinCode = $user->riscoin_id ?? '';

        $activeLink = RiscoinLink::where('is_active', true)->first();

        if ($activeLink) {
            $this->hasActiveLink = true;
            $this->riscoinLinkUrl = $activeLink->url . '?code=' . $this->riscoinCode;
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
                        <div class="relative flex-1 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                        {{-- Open link button --}}
                        <a href="{{ $riscoinLinkUrl }}" target="_blank" rel="noopener"
                           class="shrink-0 flex items-center gap-1.5 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Open
                        </a>
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
                </div>
            @endif
        </div>
    </div>
</div>
