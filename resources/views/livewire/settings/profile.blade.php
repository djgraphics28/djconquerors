<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public $riscoin_id = '';
    public $inviters_code = '';
    public $invested_amount = '';
    public $date_joined = '';
    public $birth_date = '';
    public $phone_number = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->riscoin_id = Auth::user()->riscoin_id;
        $this->inviters_code = Auth::user()->inviters_code;
        $this->invested_amount = Auth::user()->invested_amount;
        $this->date_joined = Auth::user()->date_joined;
        $this->birth_date = Auth::user()->birth_date;
        $this->phone_number = Auth::user()->phone_number;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone_number' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
            'date_joined' => ['required', 'date'],
            'invested_amount' => ['required', 'numeric'],
            'inviters_code' => ['required', 'string', 'max:255'],
            'riscoin_id' => ['required', 'string', 'max:255'],
        ]);

        $validated['riscoin_id'] = strtoupper($validated['riscoin_id']);
        $validated['inviters_code'] = strtoupper($validated['inviters_code']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer"
                                wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')" type="text" disabled />
            <flux:input wire:model="inviters_code" :label="__('Inviters Code')" type="text" disabled />
            <flux:input wire:model="invested_amount" :label="__('Invested Amount (USD)')" type="text" disabled />
            <flux:input wire:model="date_joined" :label="__('Date Joined')" type="text" disabled />
            <flux:input wire:model="birth_date" :label="__('Birth Date')" type="text" />
            <flux:input wire:model="phone_number" :label="__('Phone Number')" type="text" />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
