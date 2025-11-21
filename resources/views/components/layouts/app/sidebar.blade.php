<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
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
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    @endcan
                    @can('genealogy.view')
                        <flux:navlist.item icon="users" :href="route('genealogy')" :current="request()->routeIs('genealogy')" wire:navigate>{{ __('Genealogy') }}</flux:navlist.item>
                    @endcan
                    @can('my-team.access')
                        <flux:navlist.item icon="users" :href="route('my-team')" :current="request()->routeIs('my-team')" wire:navigate>{{ __('My Team') }}</flux:navlist.item>
                    @endcan
                    @can('my-withdrawals.view')
                        <flux:navlist.item icon="credit-card" :href="route('my-withdrawals')" :current="request()->routeIs('my-withdrawals')" wire:navigate>{{ __('My Withdrawals') }}</flux:navlist.item>
                    @endcan
                    @can('appointments.book')
                        <flux:navlist.item icon="calendar" :href="route('appointments.book')" :current="request()->routeIs('book-appointment')" wire:navigate>{{ __('Book An Appointment') }}</flux:navlist.item>
                    @endcan
                    @can('appointments.view')
                        <flux:navlist.item icon="calendar" :href="route('appointments.index')" :current="request()->routeIs('appointments')" wire:navigate>{{ __('Manage Appointments') }}</flux:navlist.item>
                    @endcan
                    @can('tutorials.view')
                        <flux:navlist.item icon="play-circle" :href="route('tutorials.index')" :current="request()->routeIs('tutorials.index')" wire:navigate>{{ __('Tutorials Management') }}</flux:navlist.item>
                    @endcan
                    @can('tutorials.access')
                        <flux:navlist.item icon="video-camera" :href="route('tutorials.access')" :current="request()->routeIs('tutorials.access')" wire:navigate>{{ __('Tutorials') }}</flux:navlist.item>
                    @endcan
                    @can('guide.view')
                        <flux:navlist.item icon="book-open" :href="route('guide.index')" :current="request()->routeIs('guide.index')" wire:navigate>{{ __('Guide Management') }}</flux:navlist.item>
                    @endcan
                    @can('guide.access')
                        <flux:navlist.item icon="book-open" :href="route('guide.access')" :current="request()->routeIs('guide.access')" wire:navigate>{{ __('Guide') }}</flux:navlist.item>
                    @endcan
                    @can('users.view')
                        <flux:navlist.item icon="user-circle" :href="route('users.index')" :current="request()->routeIs('users.index')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                    @endcan
                    @can('roles.view')
                        <flux:navlist.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.index')" wire:navigate>{{ __('Roles & Permissions') }}</flux:navlist.item>
                    @endcan
                    @can('activity-logs.view')
                        <flux:navlist.item icon="clipboard" :href="route('activity-logs')" :current="request()->routeIs('activity-logs')" wire:navigate>{{ __('Activity Logs') }}</flux:navlist.item>
                    @endcan
                    @can('email-receivers.view')
                        <flux:navlist.item icon="envelope" :href="route('email-receivers')" :current="request()->routeIs('email-receivers')" wire:navigate>{{ __('Email Receivers') }}</flux:navlist.item>
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
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
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
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
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
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
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
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
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
