<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>

    <x-chat/>

    <!-- New Investor Reminder -->
    @auth
        <livewire:widget.reminder-for-new-investors />
        {{-- <livewire:widget.chatbot /> --}}
    @endauth
</x-layouts.app.sidebar>
