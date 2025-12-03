<?php

use Livewire\Volt\Component;

new class extends Component {

    public $currentNode;
    /**
     * Mount the component.
     */
    public function mount($currentNode): void
    {
        $this->currentNode = $currentNode;
    }
}; ?>

<div>
    @can('dashboard.copyMessageToMartin')
        <!-- Copy Support Form to Clipboard -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <div x-data="{
                copied: false,
                async copySupportForm() {
                    const message = `
Support Team: {{ $currentNode->support_team ?? '' }}
Inviter's Riscoin ID: {{ $currentNode->inviters_code ?? '' }}
Riscoin Account ID: {{ $currentNode->riscoin_id ?? '' }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
Your Name: {{ $currentNode->name ?? '' }}
Occupation: {{ $currentNode->occupation ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Nationality: Filipino
Languages Spoken: English, Filipino
Age: {{ $currentNode->age ?? 'Not specified' }}`;

                    await this.copyTextToClipboard(message);
                },
                async copyTextToClipboard(text) {
                    try {
                        // Modern clipboard API with proper mobile support
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(text);
                            this.showCopyFeedback();
                            return true;
                        }
                    } catch (err) {
                        console.log('Modern clipboard API failed, trying fallback...');
                    }

                    // Fallback method for mobile and older browsers
                    return this.fallbackCopyTextToClipboard(text);
                },
                fallbackCopyTextToClipboard(text) {
                    try {
                        // Create a temporary textarea element
                        const textArea = document.createElement('textarea');
                        textArea.value = text;

                        // Make the textarea out of viewport
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        textArea.style.top = '-999999px';
                        textArea.style.opacity = '0';
                        textArea.style.pointerEvents = 'none';

                        document.body.appendChild(textArea);

                        // For mobile devices, we need to focus and select
                        textArea.focus();
                        textArea.select();

                        // For iOS
                        textArea.setSelectionRange(0, 99999);

                        const successful = document.execCommand('copy');
                        document.body.removeChild(textArea);

                        if (successful) {
                            this.showCopyFeedback();
                            return true;
                        } else {
                            this.showCopyError();
                            return false;
                        }
                    } catch (err) {
                        console.error('Fallback copy failed:', err);
                        this.showCopyError();
                        return false;
                    }
                },
                showCopyFeedback() {
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 2000);
                },
                showCopyError() {
                    // You can add error feedback here if needed
                    console.error('Copy to clipboard failed');
                    alert('Copy failed. Please select and copy the text manually.');
                }
            }" class="relative">
                <button @click="copySupportForm()" :disabled="copied"
                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!copied">Click to copy this first reply to Sir Martin</span>
                    <span x-show="copied" class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Copied!
                    </span>
                </button>

                <!-- Copy feedback -->
                <div x-show="copied" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg">
                    <span class="text-white font-semibold flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Copied to clipboard! 📋
                    </span>
                </div>
            </div>

            <!-- Preview of what will be copied -->
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</h4>
                <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">
Support Team: {{ $currentNode->support_team ?? '' }}
Inviter's Riscoin ID: {{ $currentNode->inviters_code ?? '' }}
Riscoin Account ID: {{ $currentNode->riscoin_id ?? '' }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
Your Name: {{ $currentNode->name ?? '' }}
Occupation: {{ $currentNode->occupation ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Nationality: Filipino
Languages Spoken: English, Filipino
Age: {{ $currentNode->age ?? 'Not specified' }}</pre>
            </div>
        </div>
    @endcan
</div>
