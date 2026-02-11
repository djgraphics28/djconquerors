<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;
use App\Models\RiscoinLink;

new class extends Component {
    #[Locked]
    public string $riscoinLinkUrl = '';

    #[Locked]
    public bool $hasActiveLink = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->generateRiscoinLink();
    }

    /**
     * Generate Riscoin link with user's riscoin_id
     */
    private function generateRiscoinLink(): void
    {
        $user = auth()->user();
        $riscoinId = $user->riscoin_id;

        // Get the first active Riscoin link
        $activeLink = RiscoinLink::where('is_active', true)->first();

        if ($activeLink) {
            $this->hasActiveLink = true;
            $this->riscoinLinkUrl = $activeLink->url . '?code=' . $riscoinId;
        } else {
            $this->hasActiveLink = false;
            $this->riscoinLinkUrl = '';
        }
    }
}; ?>

<div>
    <!-- Riscoin Link Section -->
    <div class="space-y-6">
        <div class="text-center space-y-2">
            <flux:heading size="lg" class="text-stone-900 dark:text-stone-100">
                {{ __('Your Riscoin Link') }}
            </flux:heading>
            <flux:text variant="subtle" class="text-sm">
                {{ __('Share this personalized Riscoin link with your riscoin ID') }}
            </flux:text>
        </div>

        @if(!$hasActiveLink)
            <!-- No Active Link Message -->
            <div class="flex items-center justify-center w-full p-6 border-2 border-dashed rounded-xl border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20">
                <div class="text-center space-y-2">
                    <svg class="w-12 h-12 mx-auto text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <flux:text class="font-medium text-amber-700 dark:text-amber-300">
                        {{ __('No active Riscoin link available') }}
                    </flux:text>
                    <flux:text variant="subtle" class="text-xs text-amber-600 dark:text-amber-400">
                        {{ __('Please contact an administrator to set up Riscoin links') }}
                    </flux:text>
                </div>
            </div>
        @else
            <div x-data="{
                copied: false,
                async copy() {
                    try {
                        // Get the input element
                        const input = this.$refs.riscoinLinkInput;

                        // Select the text
                        input.select();
                        input.setSelectionRange(0, 99999);

                        // Modern clipboard API
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(input.value);
                        }
                        // Fallback for older browsers
                        else {
                            // Use document.execCommand as fallback
                            document.execCommand('copy');
                        }

                        // Show success state
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);

                    } catch (err) {
                        console.error('Failed to copy: ', err);
                        // Fallback: Show the text in an alert for manual copy
                        alert('Please manually copy: ' + input.value);
                    }
                }
            }" class="space-y-4">
                <!-- Link Display -->
                <div class="relative group">
                    <div class="relative overflow-hidden border rounded-xl border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 shadow-sm transition-all duration-200 hover:shadow-md">
                        <input type="text"
                               readonly
                               value="{{ $riscoinLinkUrl }}"
                               x-ref="riscoinLinkInput"
                               class="w-full p-4 pr-12 bg-transparent outline-none text-stone-900 dark:text-stone-100 font-mono text-sm truncate select-all cursor-pointer"
                               @click="$refs.riscoinLinkInput.select()" />

                        <!-- Copy Button -->
                        <button @click="copy()"
                                type="button"
                                :class="copied ? 'bg-green-500 hover:bg-green-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg text-white transition-all duration-200 transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <template x-if="!copied">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </template>
                            <template x-if="copied">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                        </button>
                    </div>

                    <!-- Success Message -->
                    <div x-show="copied"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         x-cloak
                         class="flex items-center justify-center p-3 space-x-2 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <flux:text class="font-medium text-green-700 dark:text-green-300">
                            {{ __('Riscoin link copied to clipboard!') }}
                        </flux:text>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="text-center">
                    <flux:text variant="subtle" class="text-sm">
                        {{ __('Click the copy button to share your personalized Riscoin link') }}
                    </flux:text>
                </div>
            </div>
        @endif
    </div>
</div>
