<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')


    <link rel="manifest" href="/manifest.json">

    <link rel="apple-touch-icon" sizes="16x16" href="/pwa/icons/ios/16.png">
    <link rel="apple-touch-icon" sizes="20x20" href="/pwa/icons/ios/20.png">
    <link rel="apple-touch-icon" sizes="29x29" href="/pwa/icons/ios/29.png">
    <link rel="apple-touch-icon" sizes="32x32" href="/pwa/icons/ios/32.png">
    <link rel="apple-touch-icon" sizes="40x40" href="/pwa/icons/ios/40.png">
    <link rel="apple-touch-icon" sizes="50x50" href="/pwa/icons/ios/50.png">
    <link rel="apple-touch-icon" sizes="57x57" href="/pwa/icons/ios/57.png">
    <link rel="apple-touch-icon" sizes="58x58" href="/pwa/icons/ios/58.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/pwa/icons/ios/60.png">
    <link rel="apple-touch-icon" sizes="64x64" href="/pwa/icons/ios/64.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/pwa/icons/ios/72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/pwa/icons/ios/76.png">
    <link rel="apple-touch-icon" sizes="80x80" href="/pwa/icons/ios/80.png">
    <link rel="apple-touch-icon" sizes="87x87" href="/pwa/icons/ios/87.png">
    <link rel="apple-touch-icon" sizes="100x100" href="/pwa/icons/ios/100.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/pwa/icons/ios/114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/pwa/icons/ios/120.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/pwa/icons/ios/128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/pwa/icons/ios/144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/pwa/icons/ios/152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/pwa/icons/ios/167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/pwa/icons/ios/180.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/pwa/icons/ios/192.png">
    <link rel="apple-touch-icon" sizes="256x256" href="/pwa/icons/ios/256.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/pwa/icons/ios/512.png">
    <link rel="apple-touch-icon" sizes="1024x1024" href="/pwa/icons/ios/1024.png">

    <link href="/pwa/icons/ios/1024.png" sizes="1024x1024" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/512.png" sizes="512x512" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/256.png" sizes="256x256" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/192.png" sizes="192x192" rel="apple-touch-startup-image">

    <link href="/pwa/icons/android/android-launchericon-48-48.png" sizes="48x48" rel="icon" type="image/png">
    <link href="/pwa/icons/android/android-launchericon-72-72.png" sizes="72x72" rel="icon" type="image/png">
    <link href="/pwa/icons/android/android-launchericon-96-96.png" sizes="96x96" rel="icon" type="image/png">
    <link href="/pwa/icons/android/android-launchericon-144-144.png" sizes="144x144" rel="icon"
        type="image/png">
    <link href="/pwa/icons/android/android-launchericon-192-192.png" sizes="192x192" rel="icon"
        type="image/png">
    <link href="/pwa/icons/android/android-launchericon-512-512.png" sizes="512x512" rel="icon"
        type="image/png">
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                @can('dashboard.view')
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                        wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                @endcan
                @can('genealogy.view')
                    <flux:navlist.item icon="users" :href="route('genealogy')" :current="request()->routeIs('genealogy*')"
                        wire:navigate>{{ __('Genealogy') }}</flux:navlist.item>
                @endcan
                @can('my-team.access')
                    <flux:navlist.item icon="users" :href="route('my-team')" :current="request()->routeIs('my-team')"
                        wire:navigate>{{ __('My Team') }}</flux:navlist.item>
                @endcan
                @can('managers.view')
                    <flux:navlist.item icon="briefcase" :href="route('managers.index')"
                        :current="request()->routeIs('managers.index')" wire:navigate>{{ __('Managers') }}
                    </flux:navlist.item>
                @endcan
                @can('my-withdrawals.view')
                    <flux:navlist.item icon="credit-card" :href="route('my-withdrawals')"
                        :current="request()->routeIs('my-withdrawals')" wire:navigate>{{ __('My Withdrawals') }}
                    </flux:navlist.item>
                @endcan
                @can('appointments.book')
                    <flux:navlist.item icon="calendar" :href="route('appointments.book')"
                        :current="request()->routeIs('book-appointment')" wire:navigate>{{ __('Book An Appointment') }}
                    </flux:navlist.item>
                @endcan
                @can('appointments.view')
                    <flux:navlist.item icon="calendar" :href="route('appointments.index')"
                        :current="request()->routeIs('appointments')" wire:navigate>{{ __('Manage Appointments') }}
                    </flux:navlist.item>
                @endcan
                @can('tutorials.view')
                    <flux:navlist.item icon="play-circle" :href="route('tutorials.index')"
                        :current="request()->routeIs('tutorials.index')" wire:navigate>{{ __('Tutorials Management') }}
                    </flux:navlist.item>
                @endcan
                @can('tutorials.access')
                    <flux:navlist.item icon="video-camera" :href="route('tutorials.access')"
                        :current="request()->routeIs('tutorials.access')" wire:navigate>{{ __('Tutorials') }}
                    </flux:navlist.item>
                @endcan
                @can('guide.view')
                    <flux:navlist.item icon="book-open" :href="route('guide.index')"
                        :current="request()->routeIs('guide.index')" wire:navigate>{{ __('Guide Management') }}
                    </flux:navlist.item>
                @endcan
                @can('guide.access')
                    <flux:navlist.item icon="book-open" :href="route('guide.access')"
                        :current="request()->routeIs('guide.access')" wire:navigate>{{ __('Guide') }}
                    </flux:navlist.item>
                @endcan
                @can('users.view')
                    <flux:navlist.item icon="user-circle" :href="route('users.index')"
                        :current="request()->routeIs('users.index')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                @endcan
                @can('roles.view')
                    <flux:navlist.item icon="shield-check" :href="route('roles.index')"
                        :current="request()->routeIs('roles.index')" wire:navigate>{{ __('Roles & Permissions') }}
                    </flux:navlist.item>
                @endcan
                @can('activity-logs.view')
                    <flux:navlist.item icon="clipboard" :href="route('activity-logs')"
                        :current="request()->routeIs('activity-logs')" wire:navigate>{{ __('Activity Logs') }}
                    </flux:navlist.item>
                @endcan
                @can('email-receivers.view')
                    <flux:navlist.item icon="envelope" :href="route('email-receivers')"
                        :current="request()->routeIs('email-receivers')" wire:navigate>{{ __('Email Receivers') }}
                    </flux:navlist.item>
                @endcan

                @can('calculator.view')
                    <flux:navlist.item icon="chart-bar" :href="route('compound-calculator')"
                        :current="request()->routeIs('compound-calculator')" wire:navigate>{{ __('Compound Calculator') }}
                    </flux:navlist.item>
                @endcan

                @can('opalite.view')
                    <flux:navlist.item icon="sparkles" :href="route('opalite.index')"
                        :current="request()->routeIs('opalite.index')" wire:navigate>{{ __('Opalite Dance Winners') }}
                    </flux:navlist.item>
                @endcan
                @can('opalite.manage')
                    <flux:navlist.item icon="wrench" :href="route('opalite.manage')"
                        :current="request()->routeIs('opalite.manage')" wire:navigate>{{ __('Manage Opalite Dance') }}
                    </flux:navlist.item>
                @endcan
            </flux:navlist.group>
        </flux:navlist>

        <flux:spacer />

        {{-- <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist> --}}

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down" data-test="sidebar-menu-button" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>

</html>
