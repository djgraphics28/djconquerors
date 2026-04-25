<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {

    public string $bonchat_server = '';

    public function mount(): void
    {
        $user = Auth::user();
        $managerLevel = $user->managerLevel;

        if (!$managerLevel || $managerLevel->level < 2) {
            abort(403);
        }

        $this->bonchat_server = $user->bonchat_server ?? '';
    }

    public function updateBonchatServer(): void
    {
        $user = Auth::user();
        $managerLevel = $user->managerLevel;

        if (!$managerLevel || $managerLevel->level < 2) {
            $this->addError('bonchat_server', 'You do not have permission to update the Bonchat Server.');
            return;
        }

        $this->validate([
            'bonchat_server' => ['nullable', 'string', 'max:255'],
        ]);

        $user->bonchat_server = $this->bonchat_server ?: null;
        $user->save();

        session()->flash('status', 'bonchat-server-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Bonchat Server')" :subheading="__('Update your Bonchat Server')">
        <form wire:submit="updateBonchatServer" class="w-full space-y-6">
            <flux:input
                wire:model="bonchat_server"
                :label="__('Bonchat Server')"
                type="text"
                :placeholder="__('e.g. sg555, sg666, etc.')"
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                @if (session('status') === 'bonchat-server-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-green-600 dark:text-green-400"
                    >{{ __('Saved.') }}</p>
                @endif
            </div>
        </form>
    </x-settings.layout>
</section>
