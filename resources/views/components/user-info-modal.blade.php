@props(['userId' => null, 'triggerText' => 'View Details', 'triggerClass' => ''])

@if($isAdmin())
    <div x-data="{
        open: false,
        userId: @js($userId),
        user: null,
        loading: false,
        async loadUser() {
            if (!this.userId) return;
            this.loading = true;
            try {
                const response = await fetch(`/api/user/${this.userId}/info`);
                if (response.ok) {
                    this.user = await response.json();
                }
            } catch (error) {
                console.error('Failed to load user:', error);
            } finally {
                this.loading = false;
            }
        },
        openModal() {
            this.open = true;
            this.loadUser();
        }
    }">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="openModal()"
            class="{{ $triggerClass ?: 'inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500' }}">
            {{ $triggerText }}
        </button>

        <!-- Modal -->
        <div x-show="open"
             x-on:keydown.escape.window="open = false"
             class="fixed inset-0 z-50 overflow-hidden"
             style="display: none;">
            <!-- Overlay -->
            <div x-show="open"
                 x-transition:enter="ease-in-out duration-500"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-500"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 transition-opacity"
                 @click="open = false">
            </div>

            <!-- Modal Panel -->
            <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                <div x-show="open"
                     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:enter-start="translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="translate-x-full"
                     class="w-screen max-w-4xl">
                    <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Complete User Information
                            </h2>
                            <button @click="open = false"
                                    class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto">
                            <div class="px-6 py-4">
                                <!-- Loading State -->
                                <div x-show="loading" class="flex items-center justify-center py-12">
                                    <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="ml-2 text-gray-600 dark:text-gray-400">Loading user information...</span>
                                </div>

                                @if($user)
                                    <div x-show="!loading" class="space-y-6">
                                        <!-- User Profile Section -->
                                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-700 rounded-lg p-6 border border-indigo-100 dark:border-gray-600">
                                            <div class="flex items-center space-x-6">
                                                <!-- Avatar -->
                                                <div class="flex-shrink-0">
                                                    @if($user->getFirstMediaUrl('avatar'))
                                                        <img class="h-24 w-24 rounded-full object-cover border-4 border-white dark:border-gray-600 shadow-lg"
                                                             src="{{ $user->getFirstMediaUrl('avatar') }}"
                                                             alt="{{ $user->name }} avatar">
                                                    @else
                                                        <div class="h-24 w-24 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center border-4 border-white dark:border-gray-600 shadow-lg">
                                                            <span class="text-white font-bold text-3xl">
                                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                        {{ $user->name }}
                                                    </h3>
                                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                        {{ $user->email }}
                                                    </p>
                                                    <div class="flex flex-wrap gap-2 mt-3">
                                                        @foreach($user->roles as $role)
                                                            <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200 rounded-full capitalize">
                                                                {{ $role->name }}
                                                            </span>
                                                        @endforeach
                                                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                        @if($user->email_verified_at)
                                                            <span class="px-3 py-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                                                Verified
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quick Stats -->
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm">
                                                <div class="flex items-center">
                                                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Invested</p>
                                                        <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($user->invested_amount, 2) }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm">
                                                <div class="flex items-center">
                                                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Withdrawals</p>
                                                        <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($totalWithdrawals, 2) }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm">
                                                <div class="flex items-center">
                                                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Direct Team</p>
                                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($directTeamCount) }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 shadow-sm">
                                                <div class="flex items-center">
                                                    <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                                                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Team</p>
                                                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($teamCount) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Personal Information -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                Personal Information
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Riscoin ID</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->riscoin_id ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Bonchat ID</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->bonchat_id ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Phone Number</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->phone_number ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Gender</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1 capitalize">
                                                        {{ $user->gender ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Age</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->age ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Birth Date</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->birth_date ? $user->birth_date->format('M j, Y') : 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Occupation</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->occupation ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Support Team</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->support_team ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Support Group</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->support_group ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Team Information -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                Team & Network Information
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Inviter</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->inviter?->name ?? 'N/A' }}
                                                    </p>
                                                    @if($user->inviter)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            Riscoin ID: {{ $user->inviter->riscoin_id }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Assistant</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->assistant?->name ?? 'No Assistant' }}
                                                    </p>
                                                    @if($user->assistant)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            Riscoin ID: {{ $user->assistant->riscoin_id }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Inviter's Code</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->inviters_code ?? 'N/A' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Team ID</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->team_id ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Financial Information -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Financial Information
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                                    <label class="text-xs font-medium text-green-700 dark:text-green-400">Invested Amount</label>
                                                    <p class="text-2xl font-bold text-green-900 dark:text-green-200 mt-1">
                                                        ${{ number_format($user->invested_amount, 2) }}
                                                    </p>
                                                </div>
                                                <div class="p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                                    <label class="text-xs font-medium text-blue-700 dark:text-blue-400">Total Withdrawals</label>
                                                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-200 mt-1">
                                                        ${{ number_format($totalWithdrawals, 2) }}
                                                    </p>
                                                </div>
                                                <div class="p-4 bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 md:col-span-2">
                                                    <label class="text-xs font-medium text-yellow-700 dark:text-yellow-400">Capital Recovery Status</label>
                                                    <div class="flex items-center justify-between mt-2">
                                                        <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full
                                                            @if($capitalStatus['color'] === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                            @elseif($capitalStatus['color'] === 'yellow') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 @endif">
                                                            {{ $capitalStatus['label'] }}
                                                        </span>
                                                        @if($user->invested_amount > 0)
                                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                                {{ number_format(($totalWithdrawals / $user->invested_amount) * 100, 1) }}% recovered
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Account Dates -->
                                        <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                Important Dates
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Date Joined</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->date_joined ? $user->date_joined->format('M j, Y') : 'N/A' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $user->months_and_days_since_joined ?? '' }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Account Created</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->created_at->format('M j, Y') }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $user->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Updated</label>
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                        {{ $user->updated_at->format('M j, Y') }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        {{ $user->updated_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Activity Logs -->
                                        @if($activityLogs && $activityLogs->count() > 0)
                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                                    </svg>
                                                    Recent Activity Logs
                                                </h4>
                                                <div class="space-y-3 max-h-96 overflow-y-auto">
                                                    @foreach($activityLogs as $log)
                                                        <div class="flex items-start p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors">
                                                            <div class="flex-shrink-0 mt-1">
                                                                @if($log->description === 'created')
                                                                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-full">
                                                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                        </svg>
                                                                    </div>
                                                                @elseif($log->description === 'updated')
                                                                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-full">
                                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                        </svg>
                                                                    </div>
                                                                @elseif($log->description === 'deleted')
                                                                    <div class="p-2 bg-red-100 dark:bg-red-900 rounded-full">
                                                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                        </svg>
                                                                    </div>
                                                                @else
                                                                    <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-full">
                                                                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="ml-3 flex-1">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {{ ucfirst($log->description) }}
                                                                </p>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                    By: {{ $log->causer?->name ?? 'System' }} • {{ $log->created_at->diffForHumans() }}
                                                                </p>
                                                                @if($log->properties && $log->properties->count() > 0)
                                                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                                        {{ $log->properties }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Withdrawal History -->
                                        @if($user->withdrawals && $user->withdrawals->count() > 0)
                                            <div class="bg-white dark:bg-gray-700 rounded-lg p-6 border border-gray-200 dark:border-gray-600">
                                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                                    </svg>
                                                    Withdrawal History
                                                </h4>
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                                            <tr>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-700 dark:divide-gray-600">
                                                            @foreach($user->withdrawals->take(10) as $withdrawal)
                                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                                        {{ $withdrawal->created_at->format('M j, Y') }}
                                                                    </td>
                                                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">
                                                                        ${{ number_format($withdrawal->amount, 2) }}
                                                                    </td>
                                                                    <td class="px-4 py-3">
                                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                                            @if($withdrawal->status === 'paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                            @elseif($withdrawal->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                                            @elseif($withdrawal->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 @endif">
                                                                            {{ ucfirst($withdrawal->status) }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            <button @click="open = false"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
