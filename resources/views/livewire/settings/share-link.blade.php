<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Locked;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

new class extends Component {
    #[Locked]
    public string $referralLink = '';

    #[Locked]
    public string $referralQrCodeSvg = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->generateReferralLink();
    }

    /**
     * Generate referral link and QR code
     */
    private function generateReferralLink(): void
    {
        $user = auth()->user();
        $appUrl = config('app.url');
        $riscoinId = $user->riscoin_id;

        // Generate the referral link
        $this->referralLink = "{$appUrl}/register?ref={$riscoinId}";

        // Generate QR code using Bacon
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $this->referralQrCodeSvg = $writer->writeString($this->referralLink);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Invite Link')" :subheading="__('Share your invite link to your personal invite')">
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
            <div class="space-y-4">
                {{-- <flux:text>
                    {{ __('Share your invite link with friends and earn rewards when they sign up using your link.') }}
                </flux:text> --}}

                <!-- Invite Link Section -->
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('Your Invite Link') }}</flux:heading>

                    <div class="flex items-center space-x-2" x-data="{
                        copied: false,
                        copy() {
                            const input = this.$refs.referralInput;
                            input.select();
                            input.setSelectionRange(0, 99999);
                            document.execCommand('copy');
                            this.copied = true;
                            setTimeout(() => this.copied = false, 1500);
                        }
                    }">
                        <div class="flex items-stretch w-full border rounded-xl dark:border-stone-700">
                            @empty($referralLink)
                                <div class="flex items-center justify-center w-full p-3 bg-stone-100 dark:bg-stone-700">
                                    <flux:icon.loading variant="mini" />
                                </div>
                            @else
                                <input type="text" readonly value="{{ $referralLink }}" x-ref="referralInput"
                                    class="w-full p-3 bg-transparent outline-none text-stone-900 dark:text-stone-100" />

                                <button @click="copy()"
                                    class="px-3 transition-colors border-l cursor-pointer border-stone-200 dark:border-stone-600 hover:bg-stone-100 dark:hover:bg-stone-700"
                                    title="Copy to clipboard">
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" class="w-5 h-5">
                                        </flux:icon>
                                        <flux:icon.check x-show="copied" variant="solid" class="w-5 h-5 text-green-500">
                                            </flux:icon>
                                </button>
                            @endempty
                        </div>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('QR Code') }}</flux:heading>
                    <flux:text variant="subtle">
                        {{ __('Scan this QR code to share your invite link easily.') }}
                    </flux:text>

                    <div class="flex justify-center">
                        <div
                            class="relative w-64 overflow-hidden border rounded-lg border-stone-200 dark:border-stone-700 aspect-square">
                            @empty($referralQrCodeSvg)
                                <div class="absolute inset-0 flex items-center justify-center bg-white dark:bg-stone-700">
                                    <div class="text-center">
                                        <flux:icon.qr-code class="w-16 h-16 mx-auto mb-2 text-stone-400" />
                                        <flux:text variant="subtle">{{ __('QR Code will appear here') }}</flux:text>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center justify-center h-full p-4 bg-white">
                                    {!! $referralQrCodeSvg !!}
                                </div>
                            @endempty
                        </div>
                    </div>
                </div>

                <!-- Share Buttons -->
                {{-- <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Share via') }}</flux:heading>
                    <div class="flex space-x-3">
                        <flux:button variant="outline" icon="link"
                            @click="window.open('https://wa.me/?text=' + encodeURIComponent('{{ $referralLink }}'), '_blank')">
                            WhatsApp
                        </flux:button>

                        <flux:button variant="outline" icon="envelope"
                            @click="window.open('mailto:?body=' + encodeURIComponent('{{ $referralLink }}'), '_blank')">
                            Email
                        </flux:button>

                        <flux:button variant="outline" icon="share"
                            @click="navigator.share?.({ url: '{{ $referralLink }}' })"
                            x-bind:disabled="!navigator.share">
                            Share
                        </flux:button>
                    </div>
                </div> --}}
            </div>
        </div>
    </x-settings.layout>
</section>
