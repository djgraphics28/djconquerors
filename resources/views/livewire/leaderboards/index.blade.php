<?php

use Livewire\Volt\Component;

new class extends Component {};

?>
<div class="space-y-5">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Leaderboards</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Top assisters &amp; top inviters across the network
        </p>
    </div>

    {{-- Top Inviters (New Investors Analytics) --}}
    <livewire:widget.new-investors-analytics :filter="'today'" />

    {{-- Top Assisters --}}
    <livewire:widget.top-assisters />
</div>
