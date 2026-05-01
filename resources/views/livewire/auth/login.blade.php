<?php

use App\Models\User;
use App\Models\BinanceApkDownload;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Features;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function getApksProperty()
    {
        return BinanceApkDownload::active()
            ->orderByRaw("FIELD(app_type, 'binance', 'okx', 'bitget', 'other')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function directDownloadUrl(?BinanceApkDownload $apk): string
    {
        if (!$apk) {
            return '#';
        }

        $apkUrl = $apk->getFirstMediaUrl('apk');
        if ($apkUrl) {
            return $apkUrl;
        }

        $url = $apk->download_url ?? '';

        if (!$url) {
            return '#';
        }

        if (preg_match('/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        return $url;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = $this->validateCredentials();

        if (Features::canManageTwoFactorAuthentication() && $user->hasEnabledTwoFactorAuthentication()) {
            Session::put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            $this->redirect(route('two-factor.login'), navigate: true);

            return;
        }

        Auth::login($user, $this->remember);

        // Update last login
        Auth::user()->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
    /**
     * Validate the user's credentials.
     */
    protected function validateCredentials(): User
    {
        $user = Auth::getProvider()->retrieveByCredentials(['email' => $this->email, 'password' => $this->password]);

        if (!$user || !Auth::getProvider()->validateCredentials($user, ['password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    {{-- <div class="mx-auto flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
    </div> --}}
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autofocus autocomplete="email"
            placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <flux:input wire:model="password" :label="__('Password')" type="password" required
                autocomplete="current-password" :placeholder="__('Password')" viewable />

            @if (Route::has('password.request'))
                <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif

    @if ($this->apks->isNotEmpty())
        <!-- APK Downloads for New Users -->
        <div class="mt-2 pt-5 border-t border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-semibold text-center text-zinc-500 dark:text-zinc-400 mb-3 flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Download Trading Apps (Android)
            </p>
            <div class="flex flex-col gap-2">
                @foreach ($this->apks as $apk)
                    @php
                        $btnStyle = match($apk->app_type) {
                            'binance' => 'bg-yellow-400 hover:bg-yellow-500 text-black',
                            'okx'     => 'bg-gray-800 hover:bg-gray-900 text-white dark:bg-gray-700 dark:hover:bg-gray-600',
                            'bitget'  => 'bg-teal-500 hover:bg-teal-600 text-white',
                            default   => 'bg-purple-500 hover:bg-purple-600 text-white',
                        };
                        $downloadUrl = $this->directDownloadUrl($apk);
                    @endphp
                    @if ($downloadUrl !== '#')
                        <a href="{{ $downloadUrl }}"
                            class="inline-flex items-center justify-center gap-2 {{ $btnStyle }} font-semibold px-4 py-2 rounded-xl transition-colors duration-150 text-xs w-full">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            {{ $apk->title }}
                            @if ($apk->version)
                                <span class="opacity-70">v{{ $apk->version }}</span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
            <p class="text-[10px] text-center text-zinc-400 dark:text-zinc-500 mt-2">Android only &bull; Free download</p>
        </div>
    @endif
</div>
