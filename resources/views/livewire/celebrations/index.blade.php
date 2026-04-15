<?php

use Livewire\Volt\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?string $riscoinId = null;

    public function mount(?string $riscoinId = null): void
    {
        $this->riscoinId = $riscoinId;
    }
};

?>
<div class="space-y-5">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Celebrations
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Birthday celebrants &amp; monthly milestones for
                @if ($riscoinId)
                    {{ $riscoinId }}'s team
                @else
                    your team
                @endif
            </p>
        </div>
        @if ($riscoinId)
            <a href="{{ route('celebrations.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                My Team
            </a>
        @endif
    </div>

    {{-- Birthday Celebrators --}}
    <div>
        <livewire:widget.birthday-celebrators :riscoinId="$riscoinId" />
    </div>

    {{-- Monthly Milestones --}}
    <div>
        <livewire:widget.monthly-milestone :riscoinId="$riscoinId" />
    </div>
</div>
