@props(['node', 'level' => 0, 'showChildren' => false, 'showSuperior' => false, 'superior' => null])

<div class="genealogy-tree flex flex-col items-center">
    <!-- Superior Section -->
    @if ($showSuperior && $superior)
        <div class="mb-8 flex flex-col items-center">
            <!-- Connecting Line -->
            {{-- <div class="h-6 w-0.5 bg-gray-300 dark:bg-gray-600 mb-2"></div> --}}

            <!-- Superior Card -->
            <div class="relative flex flex-col items-center">
                <!-- Circle Card -->
                <div class="cursor-not-allowed opacity-80" title="Superior node - Viewing disabled">
                    <div
                        class="w-20 h-20 rounded-full border-4 border-purple-500 dark:border-purple-600 overflow-hidden flex items-center justify-center bg-white dark:bg-gray-800 shadow-md">
                        @if ($superior->hasMedia('avatar'))
                            <img src="{{ $superior->getFirstMediaUrl('avatar') }}" alt="{{ $superior->name }}"
                                class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                <span class="text-xl font-bold text-purple-500 dark:text-purple-400">
                                    {{ $superior->name ? substr($superior->name, 0, 1) : '' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Superior Info -->
                <div class="mt-2 text-center max-w-xs">
                    <div class="font-semibold dark:text-white text-sm">{{ $superior->name ?? 'N/A' }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">ID: {{ $superior->riscoin_id ?? 'N/A' }}</div>
                    <div class="text-xs text-purple-600 dark:text-purple-400 font-medium">Superior</div>
                    <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                        ${{ number_format($superior->invested_amount ?? 0, 2) }}</div>
                    <div
                        class="text-xs dark:text-white mt-1 bg-{{ $superior->is_active ? 'green' : 'red' }}-100 dark:bg-{{ $superior->is_active ? 'green' : 'red' }}-900 text-{{ $superior->is_active ? 'green' : 'red' }}-800 dark:text-{{ $superior->is_active ? 'green' : 'red' }}-100 px-2 py-1 rounded inline-block">
                        {{ $superior->is_active ? 'Active' : 'Inactive' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Current User Section -->
    <div class="genealogy-level level-{{ $level }} mb-8 flex flex-col items-center">
        <!-- Connecting Line from Superior -->
        @if ($showSuperior && $superior)
            <div class="h-6 w-0.5 bg-gray-300 dark:bg-gray-600 mb-2"></div>
        @endif

        <!-- Current User Card -->
        <div class="relative flex flex-col items-center">
            <!-- Circle Card -->
            <a href="{{ route('genealogy.show', $node->riscoin_id) }}"
                class="block transition-transform hover:scale-105 hover:shadow-lg">
                <div
                    class="w-24 h-24 rounded-full border-4 {{ $level === 0 ? 'border-blue-500 dark:border-blue-600' : 'border-gray-400 dark:border-gray-600' }} overflow-hidden flex items-center justify-center bg-white dark:bg-gray-800 shadow-md">
                    @if ($node->hasMedia('avatar'))
                        <img src="{{ $node->getFirstMediaUrl('avatar') }}" alt="{{ $node->name }}"
                            class="w-full h-full object-cover">
                    @else
                        <div
                            class="w-full h-full bg-{{ $level === 0 ? 'blue' : 'gray' }}-100 dark:bg-{{ $level === 0 ? 'blue' : 'gray' }}-900 flex items-center justify-center">
                            <span
                                class="text-2xl font-bold text-{{ $level === 0 ? 'blue' : 'gray' }}-500 dark:text-{{ $level === 0 ? 'blue' : 'gray' }}-400">
                                {{ $node->name ? substr($node->name, 0, 1) : '' }}
                            </span>
                        </div>
                    @endif
                </div>
            </a>

            <!-- User Info -->
            <div class="mt-2 text-center max-w-xs">
                <div class="font-semibold dark:text-white text-sm">{{ $node->name ?? 'N/A' }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <small>Last seen online: {{ $node->last_login }}</small>
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">ID: {{ $node->riscoin_id ?? 'N/A' }}</div>
                <div class="text-xs text-green-600 dark:text-green-400 font-medium">
                    ${{ number_format($node->invested_amount ?? 0, 2) }}</div>
                <div
                    class="text-xs dark:text-white mt-1 bg-{{ $node->is_active ? 'green' : 'red' }}-100 dark:bg-{{ $node->is_active ? 'green' : 'red' }}-900 text-{{ $node->is_active ? 'green' : 'red' }}-800 dark:text-{{ $node->is_active ? 'green' : 'red' }}-100 px-2 py-1 rounded inline-block">
                    {{ $node->is_active ? 'Active' : 'Inactive' }}
                </div>

                <!-- Direct members count -->
                @if ($node->invites && $node->invites->count() > 0)
                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        {{ $node->invites->count() }} direct members
                    </div>
                @endif
            </div>
        </div>

        <!-- Children Container -->
        @if ($node->invites && $node->invites->count() > 0 && $showChildren)
            <div class="relative mt-8">
                <!-- Connecting Line to Children -->
                {{-- <div class="absolute top-0 left-1/2 transform -translate-x-1/2 h-6 w-0.5 bg-gray-300 dark:bg-gray-600"></div> --}}

                <div class="flex justify-start space-x-8 relative overflow-x-auto md:justify-center pt-6">
                    @foreach ($node->invites as $index => $invite)
                        <div class="relative flex flex-col items-center">
                            <!-- Connecting line to each child -->
                            <div class="absolute -top-6 h-6 w-0.5 bg-gray-300 dark:bg-gray-600"></div>
                            <x-genealogy-node :node="$invite" :level="$level + 1" :showChildren="false" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
