<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
        </flux:main>

    <x-chat/>
</x-layouts.app.sidebar>
