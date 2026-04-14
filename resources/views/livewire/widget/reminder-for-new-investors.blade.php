<?php

use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public $showReminder = false;
    public $dontShowAgain = false;

    public function mount()
    {
        $user = auth()->user();

        // Check if user was created within the last 7 days
        if ($user && $user->created_at) {
            $daysSinceCreation = Carbon::parse($user->created_at)->diffInDays(Carbon::now());
            $this->showReminder = $daysSinceCreation <= 7;
        }
    }

    public function closeReminder()
    {
        $this->showReminder = false;
    }
}; ?>

<div x-data="{
    showModal: @entangle('showReminder'),
    dontShowAgain: false,
    init() {
        // Check localStorage on init
        const hideReminder = localStorage.getItem('hideInvestorReminder');
        if (hideReminder === 'true') {
            this.showModal = false;
        }
    },
    close() {
        if (this.dontShowAgain) {
            localStorage.setItem('hideInvestorReminder', 'true');
        }
        this.showModal = false;
        @this.closeReminder();
    }
}">
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
         x-transition.opacity>
        <div class="relative w-full max-w-3xl overflow-y-auto rounded-xl border border-zinc-200 bg-white p-8 shadow-2xl dark:border-zinc-700 dark:bg-zinc-800 max-h-[90vh]">
            <!-- Close Button -->
            <button @click="close()"
                    class="absolute right-4 top-4 rounded-lg p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Icon -->
            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <h2 class="mb-4 text-center text-2xl font-bold text-zinc-900 dark:text-white">
                🔔 Important Investor Reminder
            </h2>

            <!-- Content -->
            <div class="space-y-6 text-zinc-700 dark:text-zinc-300">
                <p class="text-center font-medium">
                    Welcome to your investor portal.
                </p>

                <p class="text-sm leading-relaxed">
                    Please carefully review the following reminders to protect your assets and avoid penalties:
                </p>

                <!-- Warning Box 1: Do Not Modify -->
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.268 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm text-red-800 dark:text-red-200">
                            <p class="font-semibold mb-2">Do Not Modify Trade Assets Without Approval</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Once your assets have been transferred into your Trade Account, please do not move, modify, or transfer them without prior approval.</li>
                                <li>Always consult your Superiors first before taking any action.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Charge Warning Box -->
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-amber-900 dark:text-amber-100">
                            <p class="font-semibold mb-2">Asset Transfer & Trading Volume Reminder</p>
                            <p class="mb-2">Transferring your assets from <strong>Trade to Exchange</strong> without completing <strong>100% of the required Trading Volume</strong> will result in a <span class="text-lg font-bold text-red-600 dark:text-red-400">35% charge</span>.</p>
                        </div>
                    </div>
                </div>

                <!-- Safety Recommendation Box -->
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800/50 dark:bg-green-900/20">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-green-800 dark:text-green-200">
                            <p class="font-semibold mb-2">For your safety and to maximize your benefits:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>We strongly recommend <strong>waiting at least 2 months</strong>, when your assets are expected to double.</li>
                                <li>After this period, you may proceed to cash out your capital safely and correctly.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Info Box: First Reward -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/50 dark:bg-blue-900/20">
                    <h3 class="mb-2 flex items-center gap-2 font-semibold text-blue-900 dark:text-blue-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        First Reward Transfer Reminder
                    </h3>
                    <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                        <ul class="list-disc list-inside space-y-1">
                            <li>When you receive your first reward, you may transfer it from <strong>Exchange to Trade</strong>.</li>
                            <li class="flex items-start gap-2">
                                <span class="text-lg">⚠️</span>
                                <span>Please ensure the transfer direction is <strong>Exchange → Trade</strong> only.</span>
                            </li>
                            <li>Any incorrect transfer from Trade → Exchange may result in applicable charges.</li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Message -->
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="text-center text-sm font-medium text-zinc-900 dark:text-white">
                        Thank you for your cooperation and for following proper procedures to secure your investment.
                    </p>
                </div>
            </div>

            <!-- Don't Show Again Checkbox -->
            <div class="mt-6 flex items-center justify-center gap-2">
                <input
                    type="checkbox"
                    id="dontShowAgain"
                    x-model="dontShowAgain"
                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"
                >
                <label for="dontShowAgain" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer select-none">
                    Don't show this again
                </label>
            </div>

            <!-- Action Button -->
            <div class="mt-4 flex justify-center">
                <button @click="close()"
                        class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-500 dark:hover:bg-blue-600">
                    I Understand
                </button>
            </div>
        </div>
    </div>
</div>
