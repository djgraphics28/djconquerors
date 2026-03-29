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
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">{{ __('Reply to Sir Martin') }}</h3>
                    <p class="text-xs text-violet-200">{{ __('Copy your support form message') }}</p>
                </div>
            </div>
            @if($currentNode?->riscoin_id)
                <div class="flex items-center gap-1.5 bg-white/20 rounded-lg px-3 py-1.5">
                    <svg class="w-3.5 h-3.5 text-violet-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2" />
                    </svg>
                    <span class="text-xs font-mono font-semibold text-white">{{ $currentNode->riscoin_id }}</span>
                </div>
            @endif
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">

            {{-- Template selector --}}
            @if($templates->isNotEmpty())
                <div>
                    <label for="template-select" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">
                        Choose Template
                    </label>
                    <div class="relative">
                        <select id="template-select"
                                wire:model.live="selectedTemplateId"
                                class="w-full appearance-none rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 text-gray-800 dark:text-gray-200 text-sm px-4 py-2.5 pr-9 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-colors">
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    @php $activeTemplate = $templates->firstWhere('id', $selectedTemplateId); @endphp
                    @if($activeTemplate?->description)
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $activeTemplate->description }}</p>
                    @endif
                </div>
            @endif

            <div x-data="{
                copied: false,
                async copyTemplateMessage() {
                    // Get the current rendered template from Livewire
                    const message = $wire.renderedTemplate;
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
            }" class="space-y-3">

                {{-- Copy button --}}
                <button @click="copyTemplateMessage()"
                        :class="copied ? 'bg-green-500 hover:bg-green-600 focus:ring-green-400' : 'bg-violet-600 hover:bg-violet-700 focus:ring-violet-500'"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-white text-sm font-semibold transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-1">
                    <template x-if="!copied">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Copy reply to Sir Martin
                        </span>
                    </template>
                    <template x-if="copied">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Copied!
                        </span>
                    </template>
                </button>

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
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">{{ __('Message copied to clipboard!') }}</span>
                </div>

                {{-- Preview --}}
                @if($renderedTemplate)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between px-3.5 py-2 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Preview</span>
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <pre class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 whitespace-pre-line leading-relaxed bg-white dark:bg-gray-800">{{ $renderedTemplate }}</pre>
                    </div>
                @else
                    <div class="flex items-start gap-3 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800">
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs text-amber-700 dark:text-amber-300">
                            No templates available.
                            @can('reply-template.access')
                                <a href="{{ route('reply-template.index') }}" class="underline font-medium">Create one now</a>
                            @endcan
                        </p>
                    </div>
                @endif

            </div>

        </div>
    </div>
</div>
