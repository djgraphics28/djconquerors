<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $riscoin_id = '';
    public string $inviters_code = '';
    public float $invested_amount = 0.0;
    public string $password_confirmation = '';
    public string $date_joined = '';
    public string $birth_date = '';
    public string $phone_number = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'riscoin_id' => ['required', 'string', 'max:255'],
            'inviters_code' => ['required', 'string', 'max:255'],
            'invested_amount' => ['required', 'numeric'],
            'date_joined' => ['nullable', 'date'],
            'birth_date' => ['nullable', 'date'],
            'phone_number' => ['nullable', 'string', 'max:20'],

        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        //add role to user
        $user->assignRole('user');

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <flux:input
            wire:model="name"
            :label="__('Name')"
            type="text"
            required
            autofocus
            autocomplete="name"
            :placeholder="__('Full name')"
        />

        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Riscoin ID -->
        <flux:input
            wire:model="riscoin_id"
            :label="__('Riscoin ID')"
            type="text"
            required
            autocomplete="riscoin_id"
            :placeholder="__('Riscoin ID')"
        />
        <!-- Inviters Code -->
        <flux:input
            wire:model="inviters_code"
            :label="__('Inviters Code')"
            type="text"
            required
            autocomplete="inviters_code"
            :placeholder="__('Inviters Code')"
        />
        <!-- Invested Amount -->
        <flux:input
            wire:model="invested_amount"
            :label="__('Invested Amount (USD)')"
            type="number"
            step="0.01"
            required
            autocomplete="invested_amount"
            :placeholder="__('Invested Amount')"
            prefix="$"
        />
        <!-- Date Joined -->
        <flux:input
            wire:model="date_joined"
            :label="__('Date Joined')"
            type="date"
            autocomplete="date_joined"
            :placeholder="__('Date Joined')"
        />
        <!-- Birth Date -->
        <flux:input
            wire:model="birth_date"
            :label="__('Birth Date')"
            type="date"
            autocomplete="birth_date"
            :placeholder="__('Birth Date')"
        />
        <!-- Phone Number -->
        <flux:input
            wire:model="phone_number"
            :label="__('Phone Number')"
            type="text"
            autocomplete="phone_number"
            :placeholder="__('Phone Number')"
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm password')"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
