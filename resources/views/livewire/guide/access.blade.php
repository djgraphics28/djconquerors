<?php

use Livewire\Volt\Component;
use App\Models\GuideOption;

new class extends Component {
    public $selectedItem = null;
    public $options = [];

    public function mount()
    {
        $this->options = GuideOption::where('is_published', true)->orderBy('order')->get();
    }

    public function selectItem($id)
    {
        $this->selectedItem = $id;

        return redirect()->route('guide.show', $id);
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
                    <h1 class="text-lg font-bold">Learning Center</h1>
                    <p class="text-emerald-100 text-sm">Choose a guide category to get started</p>
                </div>
            </div>
            <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-medium">7 Guides</span>
        </div>
    </div>

    <!-- Options Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-5">
            @if (count($options) > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach ($options as $option)
                        <a href="{{ route('guide.show', $option->id) }}" wire:click.prevent="selectItem({{ $option->id }})"
                            class="group block aspect-square p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border-2 transition-all duration-200 flex flex-col items-center justify-center overflow-hidden
                                {{ $selectedItem === $option->id
                                    ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20 shadow-md scale-105'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-teal-400 dark:hover:border-teal-500 hover:-translate-y-1 hover:shadow-md' }}">
                            @if($option->getFirstMediaUrl('option-image'))
                                <img src="{{ $option->getFirstMediaUrl('option-image') }}" alt="{{ $option->name }}"
                                    class="mb-2 w-14 h-14 object-contain rounded-lg">
                            @else
                                <div class="w-14 h-14 flex items-center justify-center mb-3 bg-teal-100 dark:bg-teal-900/30 rounded-xl group-hover:bg-teal-200 dark:group-hover:bg-teal-900/50 transition-colors duration-200">
                                    <span class="text-xl font-bold text-teal-700 dark:text-teal-300">{{ strtoupper(substr($option->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 capitalize text-center leading-tight">{{ $option->name }}</span>
                            @if($selectedItem === $option->id)
                                <span class="mt-1.5 inline-block w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-1">No guides available</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Check back soon — content is being prepared.</p>
                </div>
            @endif
        </div>
    </div>
</div>
