<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $selectedItem = null;

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
        @foreach (['rules', 'bonchat', 'riscoin', 'binance', 'okx', 'gcash', 'maya'] as $item)
            <a href="{{ route('guide.show', $item) }}" wire:click.prevent="selectItem('{{ $item }}')"
                class="selection-item block aspect-square p-4 bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-300 flex flex-col items-center justify-center {{ $selectedItem === $item ? 'selected border-blue-500 shadow-lg scale-105' : '' }}">
                @if ($item === 'binance')
                    <img src="{{ asset('images/guide/binance.jpg') }}" alt="Binance">
                @elseif ($item === 'rules')
                    <img src="{{ asset('images/guide/rules.png') }}" alt="Rules">
                @elseif ($item === 'bonchat')
                    <img src="{{ asset('images/guide/bonchat.jpeg') }}" alt="Bonchat">
                @elseif ($item === 'riscoin')
                    <img src="{{ asset('images/guide/riscoin.jpeg') }}" alt="Riscoin">
                @elseif ($item === 'okx')
                    <img src="{{ asset('images/guide/okx.png') }}" alt="OKX">
                @elseif ($item === 'gcash')
                    <img src="{{ asset('images/guide/gcash.png') }}" alt="GCash">
                @elseif ($item === 'maya')
                    <img src="{{ asset('images/guide/maya.png') }}" alt="Maya">
                @elseif ($item === 'ios')
                    <img src="{{ asset('images/guide/ios.jpg') }}" alt="iOS">
                @elseif ($item === 'android')
                    <img src="{{ asset('images/guide/android.jpg') }}" alt="Android">
                @endif {{-- <div class="w-12 h-12 md:w-16 md:h-16 flex items-center justify-center mb-2">
                    @if ($item === 'rules')
                        <i class="fas fa-gavel text-2xl md:text-3xl text-blue-600 dark:text-blue-400"></i>
                    @elseif($item === 'bonchat')
                        <i class="fas fa-comments text-2xl md:text-3xl text-green-600 dark:text-green-400"></i>
                    @elseif($item === 'riscoin')
                        <i class="fas fa-coins text-2xl md:text-3xl text-yellow-600 dark:text-yellow-400"></i>
                    @elseif($item === 'binance')
                        <i class="fab fa-bitcoin text-2xl md:text-3xl text-yellow-500 dark:text-yellow-300"></i>
                    @elseif($item === 'okx')
                        <i class="fas fa-exchange-alt text-2xl md:text-3xl text-purple-600 dark:text-purple-400"></i>
                    @elseif($item === 'gcash')
                        <i class="fas fa-mobile-alt text-2xl md:text-3xl text-blue-500 dark:text-blue-300"></i>
                    @elseif($item === 'maya')
                        <i class="fas fa-wallet text-2xl md:text-3xl text-pink-600 dark:text-pink-400"></i>
                    @elseif($item === 'ios')
                        <i class="fab fa-apple text-2xl md:text-3xl text-gray-800 dark:text-gray-200"></i>
                    @elseif($item === 'android')
                        <i class="fab fa-android text-2xl md:text-3xl text-green-500 dark:text-green-300"></i>
                    @endif
                </div> --}}
                <span
                    class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ $item }}</span>
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
