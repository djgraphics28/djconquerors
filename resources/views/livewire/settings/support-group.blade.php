<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {

    public string $support_group = '';

    public function mount(): void
    {
        $user = Auth::user();
        $managerLevel = $user->managerLevel;

        if (!$managerLevel || $managerLevel->level < 2) {
            abort(403);
        }

        $this->support_group = $user->support_group ?? '';
    }

    public function updateSupportGroup(): void
    {
        $user = Auth::user();
        $managerLevel = $user->managerLevel;

        if (!$managerLevel || $managerLevel->level < 2) {
            $this->addError('support_group', 'You do not have permission to update the support group.');
            return;
        }

        $this->validate([
            'support_group' => ['nullable', 'string', 'max:100'],
        ]);

        $newSupportGroup = $this->support_group ?: null;

        $user->support_group = $newSupportGroup;
        $user->save();

        // Cascade to members (BFS), stop at sub-manager boundaries (level >= 2)
        $idsToUpdate = [];
        $queue = User::where('inviters_code', $user->riscoin_id)
            ->with('managerLevel')
            ->whereNull('deleted_at')
            ->get()
            ->all();

        while (!empty($queue)) {
            $next = [];
            foreach ($queue as $member) {
                $idsToUpdate[] = $member->id;
                if (!($member->managerLevel && $member->managerLevel->level >= 2)) {
                    $children = User::where('inviters_code', $member->riscoin_id)
                        ->with('managerLevel')
                        ->whereNull('deleted_at')
                        ->get()
                        ->all();
                    $next = array_merge($next, $children);
                }
            }
            $queue = $next;
        }

        if (!empty($idsToUpdate)) {
            User::whereIn('id', $idsToUpdate)->update(['support_group' => $newSupportGroup]);
        }

        $this->dispatch('support-group-updated');

        session()->flash('status', 'support-group-updated');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Support Group')" :subheading="__('Update your Bonchat Support Group')">
        {{-- Info notice --}}
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/40 p-4 text-sm text-blue-800 dark:text-blue-300 space-y-1.5">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <div class="space-y-1">
                    <p class="font-semibold">{{ __('Heads up — this update cascades to your members.') }}</p>
                    <p>{{ __('Saving will also update the Support Group of all members directly or indirectly connected to you.') }}</p>
                    <p>{{ __('Members who are Level 2 managers or above will have their own Support Group updated, but their downline members will not be affected.') }}</p>
                </div>
            </div>
        </div>

        <form wire:submit="updateSupportGroup" class="w-full space-y-6">
            <flux:input
                wire:model="support_group"
                :label="__('Support Group (Bonchat Support Group)')"
                type="text"
                :placeholder="__('e.g. SG Alpha')"
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                @if (session('status') === 'support-group-updated')
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
