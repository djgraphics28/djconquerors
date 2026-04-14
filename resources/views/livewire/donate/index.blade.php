<?php

use Livewire\Volt\Component;
use App\Models\DonationMethod;

new class extends Component {
    public $methods;
    public $selectedId = null;

    public function mount(): void
    {
        $this->methods    = DonationMethod::active()->ordered()->get();
        $this->selectedId = $this->methods->first()?->id;
    }

    public function select($id): void
    {
        $this->selectedId = $id;
    }
}; ?>

<div>
    <!-- Hero Banner -->
    <div class="relative bg-gradient-to-br from-rose-500 via-pink-500 to-orange-400 rounded-2xl p-6 mb-6 text-white shadow-sm overflow-hidden">
        <!-- Decorative circles -->
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-white/10 rounded-full pointer-events-none"></div>
        <div class="absolute -bottom-8 -left-4 w-24 h-24 bg-white/10 rounded-full pointer-events-none"></div>

        <div class="relative flex items-start gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold mb-1">Support This Portal</h1>
                <p class="text-rose-100 text-sm leading-relaxed max-w-xl">
                    This platform is <strong class="text-white">completely free</strong> for all members. If you'd like to help keep the servers running and support new features, every contribution — no matter how small — is deeply appreciated. 🙏
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">Any amount welcome</span>
                    <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">No fixed minimum</span>
                    <span class="text-[11px] bg-white/20 text-white font-medium px-3 py-1 rounded-full">100% voluntary</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    @if ($methods->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-14 text-center">
            <div class="w-16 h-16 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-rose-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">No payment methods available yet</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Please check back soon.</p>
        </div>
    @else
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Left: Method Selector -->
            <div class="lg:col-span-1 space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-1">Payment Channel</h2>
                @foreach ($methods as $method)
                    <button
                        wire:click="select({{ $method->id }})"
                        class="w-full text-left p-4 rounded-xl border-2 transition-all duration-150
                            {{ $selectedId === $method->id
                                ? 'border-rose-400 bg-rose-50 dark:bg-rose-900/20 shadow-sm'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-rose-200 dark:hover:border-rose-800' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                                @if($method->type === 'gcash') bg-blue-100 dark:bg-blue-900/30
                                @elseif($method->type === 'maya') bg-green-100 dark:bg-green-900/30
                                @elseif($method->type === 'bank') bg-amber-100 dark:bg-amber-900/30
                                @else bg-gray-100 dark:bg-gray-700 @endif">
                                <span class="text-lg leading-none">{{ $method->type_icon }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $method->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $method->type_label }}</p>
                            </div>
                            @if ($selectedId === $method->id)
                                <svg class="ml-auto w-4 h-4 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.593c-5.63-5.539-11-10.297-11-14.402 0-3.791 3.068-5.191 5.281-5.191 1.312 0 4.151.501 5.719 4.457 1.59-3.968 4.464-4.447 5.726-4.447 2.54 0 5.274 1.621 5.274 5.181 0 4.069-5.136 8.625-11 14.402z"/>
                                </svg>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- Right: Method Detail -->
            <div class="lg:col-span-2">
                @php $active = $methods->firstWhere('id', $selectedId); @endphp
                @if ($active)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                        <!-- Detail header -->
                        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                @if($active->type === 'gcash') bg-blue-100 dark:bg-blue-900/30
                                @elseif($active->type === 'maya') bg-green-100 dark:bg-green-900/30
                                @elseif($active->type === 'bank') bg-amber-100 dark:bg-amber-900/30
                                @else bg-gray-100 dark:bg-gray-700 @endif">
                                <span class="text-base leading-none">{{ $active->type_icon }}</span>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $active->name }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $active->type_label }}</p>
                            </div>
                        </div>

                        <!-- QR + Info -->
                        <div class="p-5 grid sm:grid-cols-2 gap-6 items-start">
                            <!-- QR Code -->
                            <div class="flex flex-col items-center">
                                @if ($active->qr_code_url)
                                    <div class="bg-white rounded-2xl p-3 shadow-sm border border-gray-200 dark:border-gray-600 inline-block">
                                        <img src="{{ $active->qr_code_url }}" alt="QR Code for {{ $active->name }}"
                                            class="w-52 h-52 object-contain"/>
                                    </div>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-2 text-center">Scan QR to send payment</p>
                                    <a href="{{ $active->qr_code_url }}" download
                                        class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/30 px-3 py-1.5 rounded-full transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download QR
                                    </a>
                                @else
                                    <div class="w-52 h-52 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center gap-2 text-gray-300 dark:text-gray-600">
                                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                        <span class="text-xs">No QR available</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Account Info -->
                            <div class="space-y-4">
                                @if ($active->account_name)
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Account Name</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $active->account_name }}</p>
                                    </div>
                                @endif

                                @if ($active->account_number)
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Account / Number</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-mono font-semibold text-gray-900 dark:text-white" id="acct-{{ $active->id }}">{{ $active->account_number }}</span>
                                            <button
                                                x-data
                                                x-on:click="
                                                    navigator.clipboard.writeText('{{ $active->account_number }}');
                                                    $el.querySelector('.copy-icon').classList.add('hidden');
                                                    $el.querySelector('.check-icon').classList.remove('hidden');
                                                    setTimeout(() => {
                                                        $el.querySelector('.copy-icon').classList.remove('hidden');
                                                        $el.querySelector('.check-icon').classList.add('hidden');
                                                    }, 1500)"
                                                class="text-gray-400 hover:text-rose-500 transition-colors">
                                                <svg class="copy-icon w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <svg class="check-icon w-3.5 h-3.5 text-emerald-500 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                @if ($active->instructions)
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-0.5">Instructions</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $active->instructions }}</p>
                                    </div>
                                @endif

                                <!-- Donation note -->
                                <div class="mt-auto pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <p class="text-xs text-gray-400 dark:text-gray-500 italic leading-relaxed">
                                        No fixed amount — give what your heart allows. Your support helps keep this community alive. ❤️
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Thank you footer -->
        <div class="mt-6 text-center py-6">
            <p class="text-sm text-gray-400 dark:text-gray-500">Thank you for being part of this community. Every peso counts. 🙏</p>
        </div>
    @endif
</div>
