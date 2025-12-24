<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;
use App\Models\GuideOption;

new class extends Component {
    public $selectedItem = null;
    public $options = [];

    public function mount()
    {
        $this->options = GuideOption::orderBy('order')->get();
    }

    public function selectItem($item)
    {
        $this->selectedItem = $item;

        return redirect()->route('guide.info', $item);
        // You can add additional logic here when an item is selected
    }
}; ?>

<div class="p-4 dark:bg-gray-900">
    <h2 class="text-xl font-bold mb-6 text-center text-gray-800 dark:text-gray-200">Manage Option Guide</h2>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 max-w-4xl mx-auto">
        @foreach ($options as $option)
            <a href="{{ route('guide.info', $option->id) }}" wire:click.prevent="selectItem('{{ $option->id }}')"
                class="selection-item block aspect-square p-4 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 flex flex-col items-center justify-center {{ $selectedItem === $option->id ? 'selected border-blue-500 shadow-lg scale-105' : '' }}">
                @if($option->getFirstMediaUrl('option-image'))
                    <img src="{{ $option->getFirstMediaUrl('option-image') }}" alt="{{ $option->name }}" class="mb-2 w-16 h-16 object-contain" />
                @else
                    <div class="w-12 h-12 md:w-16 md:h-16 flex items-center justify-center mb-2 bg-gray-200 rounded">
                        <span class="text-gray-600">{{ strtoupper(substr($option->name,0,1)) }}</span>
                    </div>
                @endif
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $option->name }}</span>
            </a>
        @endforeach
    </div>

    @if ($selectedItem)
        <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg max-w-md mx-auto text-center">
            <p class="text-blue-800 dark:text-blue-200 font-medium">Selected: <span
                    class="capitalize">{{ $selectedItem }}</span></p>
        </div>
    @endif

    <style>
        .selection-item {
            transition: all 0.3s ease;
        }

        .selection-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .selection-item.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .dark .selection-item:hover {
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .dark .selection-item.selected {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
    </style>
</div>
