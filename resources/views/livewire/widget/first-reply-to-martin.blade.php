<?php

use Livewire\Volt\Component;
use App\Models\ReplyTemplate;

new class extends Component {

    public $currentNode;
    public $templates = [];
    public $selectedTemplateId = null;
    public $renderedTemplate = '';

    /**
     * Mount the component.
     */
    public function mount($currentNode): void
    {
        $this->currentNode = $currentNode;

        // Load active templates ordered
        $this->templates = ReplyTemplate::active()->ordered()->with('items')->get();

        // Select "Martin Support Form" by default if available, otherwise first template
        if ($this->templates->isNotEmpty()) {
            $martinTemplate = $this->templates->firstWhere('name', 'Martin Support Form');
            $this->selectedTemplateId = $martinTemplate?->id ?? $this->templates->first()->id;
            $this->updateRenderedTemplate();
        }
    }

    /**
     * Update the rendered template when selection changes
     */
    public function updatedSelectedTemplateId()
    {
        $this->updateRenderedTemplate();
    }

    /**
     * Render the selected template with current node data
     */
    private function updateRenderedTemplate()
    {
        if (!$this->selectedTemplateId) {
            $this->renderedTemplate = '';
            return;
        }

        $template = ReplyTemplate::with('items')->find($this->selectedTemplateId);

        if ($template && $this->currentNode) {
            $this->renderedTemplate = $template->renderAllItems($this->currentNode);
        }
    }
}; ?>

<div>
    {{-- @can('dashboard.copyMessageToMartin') --}}
        <!-- Reply Templates Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <!-- Template Selector -->
            @if($templates->isNotEmpty())
                <div class="mb-4">
                    <label for="template-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Select Reply Template
                    </label>
                    <select id="template-select"
                            wire:model.live="selectedTemplateId"
                            class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm">
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    @if($templates->first()?->description)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $templates->firstWhere('id', $selectedTemplateId)?->description }}
                        </p>
                    @endif
                </div>
            @endif

            <div x-data="{
                copied: false,
                async copyTemplateMessage() {
                    const message = @js($renderedTemplate);
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
                    console.error('Copy to clipboard failed');
                    alert('Copy failed. Please select and copy the text manually.');
                }
            }" class="relative">
                <button @click="copyTemplateMessage()" :disabled="copied"
                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!copied">Click to copy reply to Sir Martin</span>
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
            @if($renderedTemplate)
                <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</h4>
                    <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $renderedTemplate }}</pre>
                </div>
            @else
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        No templates available.
                        @can('reply-template.access')
                            <a href="{{ route('reply-template.index') }}" class="underline font-medium">Create one now</a>
                        @endcan
                    </p>
                </div>
            @endif
        </div>

        <!-- Original Static Form (Backup) -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4">Original Support Form</h3>
            <div x-data="{
                copied: false,
                async copySupportForm() {
                    const message = `
Your own Riscoin account ID: {{ $currentNode->riscoin_id ?? '' }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
My Name: {{ $currentNode->name ?? '' }}
Language: English, Tagalog
Nationality: Filipino
Age: {{ $currentNode->age ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Inviter: {{ $currentNode->inviters_code ?? '' }}
Assistant: {{ $currentNode->assistant?->riscoin_id ?? '' }}`;

                    await this.copyTextToClipboard(message);
                },
                async copyTextToClipboard(text) {
                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(text);
                            this.showCopyFeedback();
                            return true;
                        }
                    } catch (err) {
                        console.log('Modern clipboard API failed, trying fallback...');
                    }
                    return this.fallbackCopyTextToClipboard(text);
                },
                fallbackCopyTextToClipboard(text) {
                    try {
                        const textArea = document.createElement('textarea');
                        textArea.value = text;
                        textArea.style.position = 'fixed';
                        textArea.style.left = '-999999px';
                        textArea.style.top = '-999999px';
                        textArea.style.opacity = '0';
                        textArea.style.pointerEvents = 'none';
                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();
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
                    console.error('Copy to clipboard failed');
                    alert('Copy failed. Please select and copy the text manually.');
                }
            }" class="relative">
                <button @click="copySupportForm()" :disabled="copied"
                    class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition duration-200 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!copied">Click to copy original format</span>
                    <span x-show="copied" class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Copied!
                    </span>
                </button>

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

            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview:</h4>
                <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line">
Your own Riscoin account ID: {{ $currentNode->riscoin_id ?? '' }}
Deposit Amount: ${{ number_format($currentNode->invested_amount ?? 0, 2) }}
My Name: {{ $currentNode->name ?? '' }}
Language: English, Tagalog
Nationality: Filipino
Age: {{ $currentNode->age ?? 'Not specified' }}
Gender: {{ $currentNode->gender ?? 'Not specified' }}
Inviter: {{ $currentNode->inviters_code ?? '' }}
Assistant: {{ $currentNode->assistant?->riscoin_id ?? '' }}</pre>
            </div>
        </div>
    {{-- @endcan --}}
</div>
