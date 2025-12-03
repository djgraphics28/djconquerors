<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;

new class extends Component {
    #[Locked]
    public string $referralLink = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->generateReferralLink();
    }

    /**
     * Generate referral link
     */
    private function generateReferralLink(): void
    {
        $user = auth()->user();
        $appUrl = config('app.url');
        $riscoinId = $user->riscoin_id;

        // Generate the referral link
        $this->referralLink = "{$appUrl}/register?ref={$riscoinId}";
    }
}; ?>

<div>
    <!-- Invite Link Section -->
    <div class="space-y-6">
        <div class="text-center space-y-2">
            <flux:heading size="lg" class="text-stone-900 dark:text-stone-100">
                {{ __('Your Portal Invite Link') }}
            </flux:heading>
            <flux:text variant="subtle" class="text-sm">
                {{ __('Share this link to your NEW direct invites ONLY') }}
            </flux:text>
        </div>

        <div x-data="{
            copied: false,
            async copy() {
                try {
                    // Get the input element
                    const input = this.$refs.referralInput;

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
                @empty($referralLink)
                    <div class="flex items-center justify-center w-full p-4 border-2 border-dashed rounded-xl border-stone-300 dark:border-stone-600 bg-stone-50 dark:bg-stone-800/50">
                        <flux:icon.loading variant="mini" class="w-5 h-5" />
                        <flux:text class="ml-2 text-stone-500 dark:text-stone-400">
                            {{ __('Generating your link...') }}
                        </flux:text>
                    </div>
                @else
                    <div class="relative overflow-hidden border rounded-xl border-stone-200 dark:border-stone-700 bg-white dark:bg-stone-800 shadow-sm transition-all duration-200 hover:shadow-md">
                        <input type="text"
                               readonly
                               value="{{ $referralLink }}"
                               x-ref="referralInput"
                               class="w-full p-4 pr-12 bg-transparent outline-none text-stone-900 dark:text-stone-100 font-mono text-sm truncate select-all cursor-pointer"
                               @click="$refs.referralInput.select()" />

                        <!-- Copy Button -->
                        <button @click="copy()"
                                type="button"
                                :class="copied ? 'bg-green-500 hover:bg-green-600' : 'bg-blue-600 hover:bg-blue-700'"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg text-white transition-all duration-200 transform active:scale-95 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <template x-if="!copied">
                                <flux:icon.document-duplicate class="w-5 h-5" />
                            </template>
                            <template x-if="copied">
                                <flux:icon.check class="w-5 h-5" />
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
                        <flux:icon.check class="w-5 h-5 text-green-600 dark:text-green-400" />
                        <flux:text class="font-medium text-green-700 dark:text-green-300">
                            {{ __('Link copied to clipboard!') }}
                        </flux:text>
                    </div>
                @endempty
            </div>

            <!-- Instructions -->
            <div class="text-center">
                <flux:text variant="subtle" class="text-sm">
                    {{ __('Click the copy button to share your invite link') }}
                </flux:text>
            </div>
        </div>
    </div>
</div>
