<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use Livewire\Volt\Component;

new class extends Component {
    public string $search = '';
    public ?int $selectedCategory = null;
    public ?int $expandedFaq = null;

    public function with(): array
    {
        $categories = FaqCategory::active()
            ->with(['publishedFaqs' => function ($q) {
                if ($this->search) {
                    $q->where(function ($q2) {
                        $q2->where('question', 'like', '%' . $this->search . '%')
                           ->orWhere('answer', 'like', '%' . $this->search . '%');
                    });
                }
            }])
            ->orderBy('order')
            ->get()
            ->filter(fn($c) => $c->publishedFaqs->isNotEmpty());

        $allFaqs = null;
        if ($this->search && !$this->selectedCategory) {
            $allFaqs = Faq::published()
                ->where(function ($q) {
                    $q->where('question', 'like', '%' . $this->search . '%')
                      ->orWhere('answer', 'like', '%' . $this->search . '%');
                })
                ->with('category')
                ->orderBy('order')
                ->get();
        }

        return [
            'categories' => $categories,
            'allFaqs'    => $allFaqs,
        ];
    }

    public function toggleFaq(int $id): void
    {
        $this->expandedFaq = $this->expandedFaq === $id ? null : $id;
    }

    public function selectCategory(?int $id): void
    {
        $this->selectedCategory = $id;
        $this->expandedFaq      = null;
    }

    public function updatingSearch(): void
    {
        $this->selectedCategory = null;
        $this->expandedFaq      = null;
    }
}; ?>

<div>
    <!-- Header -->
    <div class="relative bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl p-6 mb-6 text-white shadow-sm overflow-hidden">
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-white/10 rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-8 -left-4 w-24 h-24 bg-white/10 rounded-full pointer-events-none"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold mb-1">Frequently Asked Questions</h1>
            <p class="text-blue-100 text-sm">Find quick answers to common questions</p>
            <div class="mt-4 max-w-md">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search FAQs…"
                        class="w-full pl-9 pr-4 py-2.5 bg-white/10 placeholder-blue-200 text-white border border-white/20 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-white/30"/>
                </div>
            </div>
        </div>
    </div>

    @if ($categories->isEmpty() && !$allFaqs?->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 font-medium">
                {{ $search ? 'No results found for "' . $search . '"' : 'No FAQs available yet.' }}
            </p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Can't find what you're looking for?</p>
            <a href="{{ route('tickets.create') }}" wire:navigate
                class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">
                Submit a Support Ticket
            </a>
        </div>
    @else
        <div class="flex gap-6">
            <!-- Category Sidebar -->
            @if (!$search && $categories->isNotEmpty())
                <div class="w-48 flex-shrink-0 hidden md:block">
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden sticky top-4">
                        <div class="p-3 border-b border-gray-100 dark:border-zinc-700">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Categories</p>
                        </div>
                        <nav class="p-2">
                            <button wire:click="selectCategory(null)"
                                class="w-full text-left px-3 py-2 text-sm rounded-lg transition {{ !$selectedCategory ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700/50' }}">
                                All Topics
                            </button>
                            @foreach ($categories as $cat)
                                <button wire:click="selectCategory({{ $cat->id }})"
                                    class="w-full text-left px-3 py-2 text-sm rounded-lg transition {{ $selectedCategory === $cat->id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700/50' }}">
                                    {{ $cat->name }}
                                    <span class="text-xs text-gray-400 ml-1">({{ $cat->publishedFaqs->count() }})</span>
                                </button>
                            @endforeach
                        </nav>
                    </div>
                </div>
            @endif

            <!-- FAQ Content -->
            <div class="flex-1 min-w-0 space-y-4">
                @if ($allFaqs)
                    {{-- Search results across all categories --}}
                    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $allFaqs->count() }} result(s) for "<span class="text-blue-600 dark:text-blue-400">{{ $search }}</span>"
                            </p>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                            @foreach ($allFaqs as $faq)
                                <div>
                                    <button wire:click="toggleFaq({{ $faq->id }})"
                                        class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-100 pr-4">{{ $faq->question }}</span>
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform {{ $expandedFaq === $faq->id ? 'rotate-180' : '' }}"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    @if ($expandedFaq === $faq->id)
                                        <div class="px-5 pb-5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none">
                                            {!! $faq->answer !!}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    @foreach ($categories as $cat)
                        @if (!$selectedCategory || $selectedCategory === $cat->id)
                            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $cat->name }}</h2>
                                </div>
                                <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                                    @foreach ($cat->publishedFaqs as $faq)
                                        <div>
                                            <button wire:click="toggleFaq({{ $faq->id }})"
                                                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100 pr-4">{{ $faq->question }}</span>
                                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform {{ $expandedFaq === $faq->id ? 'rotate-180' : '' }}"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                            @if ($expandedFaq === $faq->id)
                                                <div class="px-5 pb-5 text-sm text-gray-600 dark:text-gray-300 leading-relaxed prose prose-sm dark:prose-invert max-w-none">
                                                    {!! $faq->answer !!}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif

                <!-- Help CTA -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-100 dark:border-blue-900/30 p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Still need help?</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Our support team typically responds within 24 hours</p>
                    </div>
                    <a href="{{ route('tickets.create') }}" wire:navigate
                        class="flex-shrink-0 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition">
                        Open a Ticket
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
