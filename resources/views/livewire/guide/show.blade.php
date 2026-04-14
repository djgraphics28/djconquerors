<?php

use Livewire\Volt\Component;
use App\Models\Guide;
use App\Models\GuideOption;
use Illuminate\Support\Str;

new class extends Component {
    public $guideOptionId;
    public $guides;
    public $selectedGuide = null;
    public $guideItems;
    public $optionName = '';

    public function mount()
    {
        $this->guideOptionId = request()->route('class');

        $option = GuideOption::find($this->guideOptionId);
        $this->optionName = $option ? $option->name : 'guide';

        $this->guides = Guide::where('guide_option_id', $this->guideOptionId)
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        $this->guideItems = collect();

        if ($this->guides->isNotEmpty()) {
            $this->selectGuide($this->guides->first()->id);
        }
    }

    public function selectGuide($guideId)
    {
        $this->selectedGuide = Guide::with(['items' => fn($q) => $q->orderBy('order')])->find($guideId);
        $this->guideItems = $this->selectedGuide ? $this->selectedGuide->items : collect();
    }
}; ?>

<div>
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-600 rounded-2xl p-5 mb-5 text-white shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold capitalize">{{ $optionName }} Guide</h1>
                    <p class="text-emerald-100 text-sm">Step-by-step guide and tutorials</p>
                </div>
            </div>
            <a href="{{ route('guide.access') }}"
                class="inline-flex items-center gap-1.5 text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg transition-colors duration-200">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </a>
        </div>
    </div>

    @if ($guides->isEmpty())
        <!-- No content yet -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-12 text-center">
            <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">No guides available yet</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Check back soon — content for this section is coming.</p>
        </div>
    @else
        <div class="grid lg:grid-cols-4 gap-5">
            <!-- Guides Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden sticky top-4">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Sections</h2>
                    </div>
                    <div class="p-3 space-y-1.5">
                        @foreach ($guides as $guide)
                            <button wire:click="selectGuide({{ $guide->id }})"
                                class="w-full text-left px-3 py-2.5 rounded-xl border transition-colors duration-200 text-sm
                                    {{ $selectedGuide && $selectedGuide->id === $guide->id
                                        ? 'bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-700 text-teal-700 dark:text-teal-300'
                                        : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                <div class="font-medium truncate">{{ $guide->title }}</div>
                                @if($guide->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $guide->description }}</div>
                                @endif
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $guide->items->count() }} {{ Str::plural('step', $guide->items->count()) }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Guide Content -->
            <div class="lg:col-span-3">
                @if ($selectedGuide)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <!-- Guide Header -->
                        <div class="border-b border-gray-200 dark:border-gray-700 px-5 py-4 bg-gray-50 dark:bg-gray-700/50">
                            <h2 class="text-base font-bold text-gray-800 dark:text-white">{{ $selectedGuide->title }}</h2>
                            @if($selectedGuide->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $selectedGuide->description }}</p>
                            @endif
                        </div>

                        <!-- Guide Items -->
                        <div class="p-5">
                            @if ($guideItems->isNotEmpty())
                                <div class="space-y-5">
                                    @foreach ($guideItems as $index => $item)
                                        <div class="flex gap-4">
                                            <div class="flex-shrink-0 w-7 h-7 bg-teal-100 dark:bg-teal-900/30 rounded-full flex items-center justify-center mt-0.5">
                                                <span class="text-xs font-bold text-teal-700 dark:text-teal-300">{{ $index + 1 }}</span>
                                            </div>
                                            <div class="flex-1 border border-gray-200 dark:border-gray-600 rounded-xl p-4">
                                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">{{ $item->title }}</h3>
                                                <div class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed guide-content">
                                                    {!! $item->content !!}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No content yet for this section.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Guide content display styles */
        .guide-content h1 { font-size: 1.25rem; font-weight: 700; margin: 0.75em 0 0.3em; }
        .guide-content h2 { font-size: 1.1rem; font-weight: 700; margin: 0.7em 0 0.3em; }
        .guide-content h3 { font-size: 1rem; font-weight: 600; margin: 0.6em 0 0.25em; }
        .guide-content p { margin: 0.4em 0; }
        .guide-content ul { list-style-type: disc; padding-left: 1.5em; margin: 0.4em 0; }
        .guide-content ol { list-style-type: decimal; padding-left: 1.5em; margin: 0.4em 0; }
        .guide-content li { margin: 0.2em 0; }
        .guide-content strong { font-weight: 700; }
        .guide-content em { font-style: italic; }
        .guide-content s { text-decoration: line-through; }
        .guide-content a { color: #6366f1; text-decoration: underline; }
        .guide-content blockquote { border-left: 3px solid #6366f1; padding-left: 1em; color: #6b7280; margin: 0.5em 0; }
        .guide-content pre { background: #1e293b; color: #e2e8f0; padding: 0.75em 1em; border-radius: 0.5em; overflow-x: auto; font-size: 0.8rem; margin: 0.5em 0; }
        .guide-content code { background: #f1f5f9; padding: 0.1em 0.35em; border-radius: 0.25em; font-size: 0.85em; }
        .guide-content pre code { background: transparent; padding: 0; }
        .guide-content img { max-width: 100%; border-radius: 0.5em; margin: 0.4em 0; display: block; }
        .guide-content iframe { max-width: 100%; border-radius: 0.5em; margin: 0.5em 0; aspect-ratio: 16/9; width: 100%; }
        .dark .guide-content blockquote { color: #9ca3af; }
        .dark .guide-content code { background: #374151; }
        .dark .guide-content a { color: #818cf8; }
    </style>
</div>
