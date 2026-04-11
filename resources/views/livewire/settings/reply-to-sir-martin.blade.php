<?php

use Livewire\Volt\Component;

new class extends Component {
    public $currentNode;

    public function mount(): void
    {
        $this->currentNode = auth()->user();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Reply to Sir Martin')" :subheading="__('Copy your support form message to send to Sir Martin')">
        <livewire:widget.first-reply-to-martin :currentNode="$currentNode" />
    </x-settings.layout>
</section>
