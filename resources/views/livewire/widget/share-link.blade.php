<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;

new class extends Component {
    #[Locked]
    public string $referralLink = '';

    #[Locked]
    public string $riscoinId = '';

    public function mount(): void
    {
        $user = auth()->user();
        $appUrl = config('app.url');
        $this->riscoinId = $user->riscoin_id ?? '';
        $this->referralLink = "{$appUrl}/register?ref={$this->riscoinId}";
    }
}; ?>

<div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">{{ __('Portal Invite Link') }}</h3>
                    <p class="text-xs text-blue-100">{{ __('Share with NEW direct invites only') }}</p>
                </div>
            </div>
            @if($riscoinId)
                <div class="flex items-center gap-1.5 bg-white/20 rounded-lg px-3 py-1.5">
                    <svg class="w-3.5 h-3.5 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2" />
                    </svg>
                    <span class="text-xs font-mono font-semibold text-white">{{ $riscoinId }}</span>
                </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="p-5">
            @empty($referralLink)
                <div class="flex items-center justify-center p-6 border-2 border-dashed rounded-xl border-gray-200 dark:border-gray-700">
                    <svg class="animate-spin w-5 h-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Generating your link...') }}</span>
                </div>
            @else
                <div x-data="{
                    copied: false,
                    async copy() {
                        try {
                            const input = this.$refs.referralInput;
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
                            alert('Please manually copy: ' + this.$refs.referralInput.value);
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
                                   value="{{ $referralLink }}"
                                   x-ref="referralInput"
                                   class="w-full pl-9 pr-4 py-3 bg-transparent outline-none text-gray-800 dark:text-gray-200 font-mono text-xs truncate cursor-pointer select-all"
                                   @click="$refs.referralInput.select()" />
                        </div>

                        {{-- Copy button --}}
                        <button @click="copy()"
                                type="button"
                                :class="copied ? 'bg-green-500 hover:bg-green-600 focus:ring-green-400' : 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'"
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
                        <a href="{{ $referralLink }}" target="_blank" rel="noopener"
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
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">{{ __('Portal link copied to clipboard!') }}</span>
                    </div>

                    {{-- Tip --}}
                    <div class="flex items-start gap-2 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('Only share this portal link with people who are NEW to DJC and have not yet registered.') }}</p>
                    </div>
                </div>
            @endempty
        </div>
    </div>
</div>
