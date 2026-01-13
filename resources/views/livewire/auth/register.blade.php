<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Models\Manager;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $riscoin_id = '';
    public string $inviters_code = '';
    public $invested_amount = '';
    public string $password_confirmation = '';
    public string $date_joined = '';
    public string $birth_date = '';
    public string $phone_number = '';
    public string $gender = '';
    public string $occupation = '';
    public string $support_group = '';

    public function mount(): void
    {
        if (request()->has('ref')) {
            $this->inviters_code = request()->get('ref');
        }
    }

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
            'gender' => ['required', 'string', 'max:50'],
            'occupation' => ['required', 'string', 'max:100'],
            'support_group' => ['nullable', 'string', 'max:100'],
            // 'gRecaptcha-response' => ['required', 'captcha'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $validated['riscoind_id'] = strtoupper($validated['riscoin_id']);
        $validated['inviters_code'] = strtoupper($validated['inviters_code']);

        //check if inviters code exists
        $checkInvitersCode = User::where('riscoin_id', $validated['inviters_code'])->first();
        if (!$checkInvitersCode) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'inviters_code' => 'Invalid inviter code. Please enter a valid code.',
            ]);
        }

        event(new Registered(($user = User::create($validated))));

        //promote inviter to manager if eligible
        $this->promoteToManager($user->inviters_code);

        //add role to user
        $user->assignRole('user');

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }

    public function promoteToManager($code): void
    {
        $user = User::where('riscoin_id', strtoupper($code))->first();
        if ($user && $user->invites()->count() >= 3) {
            //check if already a manager
            if (!$user->managerLevel) {

                // dd( 'Promoting user ID ' . $user->id . ' to manager level 1.');
                Manager::create([
                    'user_id' => $user->id,
                    'level' => 1,
                ]);
            }
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name"
            :placeholder="__('Full name')" />

        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autocomplete="email"
            placeholder="email@example.com" />

        <!-- Riscoin ID -->
        <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')" type="text" required autocomplete="riscoin_id"
            :placeholder="__('Riscoin ID')" />
        <!-- Inviters Code -->
        <flux:input wire:model="inviters_code" :label="__('Inviters Code')" type="text" required
            autocomplete="inviters_code" :placeholder="__('Inviters Code')" />

        <!-- Support Group -->
        <flux:input wire:model="support_group" :label="__('Support Group (ask your inviter, bonchat support group)')" type="text" required
            autocomplete="support_group" :placeholder="__('Support Group')" />

        <!-- Invested Amount -->
        <flux:input wire:model="invested_amount" :label="__('Invested Amount (USD)')" type="number" step="0.01"
            required autocomplete="invested_amount" :placeholder="__('Invested Amount')" prefix="$" />
        <!-- Date Joined -->
        <flux:input wire:model="date_joined" :label="__('Date Joined')" type="date" autocomplete="date_joined"
            :placeholder="__('Date Joined')" />
        <!-- Birth Date -->
        <flux:input wire:model="birth_date" :label="__('Birth Date')" type="date" autocomplete="birth_date"
            :placeholder="__('Birth Date')" />
        <!-- Phone Number -->
        <flux:input wire:model="phone_number" :label="__('Phone Number')" type="text" autocomplete="phone_number"
            :placeholder="__('Phone Number')" />
        <!-- Gender -->
        <flux:select wire:model="gender" :label="__('Gender')" required data-test="gender-select">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </flux:select>
        <!-- Occupation -->
        <flux:input wire:model="occupation" :label="__('Occupation')" type="text" required autocomplete="occupation"
            :placeholder="__('Occupation')" />

        <!-- Password -->
        <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password"
            :placeholder="__('Password')" viewable />

        <!-- Confirm Password -->
        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required
            autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

        {{-- <div class="mb-3">
            {!! NoCaptcha::renderJs() !!}
            {!! NoCaptcha::display() !!}
            @error('g-recaptcha-response')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div> --}}

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
