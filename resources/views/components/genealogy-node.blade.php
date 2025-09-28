@props(['node', 'level' => 0, 'showChildren' => false])

<div class="genealogy-level level-{{ $level }} mb-8 flex flex-col items-center">
    <!-- Current User Card -->
    <div class="relative flex flex-col items-center">
        <!-- Top connecting line (for levels > 0) -->
        @if($level > 0)
            <div class="absolute w-px h-8 bg-gray-300 dark:bg-gray-700 top-0 -translate-y-full"></div>
        @endif

        <!-- Circle Card -->
        <a
            href="{{ route('genealogy.show', $node->riscoin_id) }}"
            class="block transition-transform hover:scale-105 hover:shadow-lg"
        >
            <div class="w-24 h-24 rounded-full border-4 {{ $level === 0 ? 'border-blue-500 dark:border-blue-600' : 'border-gray-400 dark:border-gray-600' }} overflow-hidden flex items-center justify-center bg-white dark:bg-gray-800 shadow-md">
                <div class="w-full h-full bg-{{ $level === 0 ? 'blue' : 'gray' }}-100 dark:bg-{{ $level === 0 ? 'blue' : 'gray' }}-900 flex items-center justify-center">
                    <span class="text-2xl font-bold text-{{ $level === 0 ? 'blue' : 'gray' }}-500 dark:text-{{ $level === 0 ? 'blue' : 'gray' }}-400">
                        {{ $node->name ? substr($node->name, 0, 1) : '' }}
                    </span>
                </div>
            </div>
        </a>

        <!-- User Info -->
        <div class="mt-2 text-center max-w-xs">
            <div class="font-semibold dark:text-white text-sm">{{ $node->name ?? 'N/A' }}</div>
            <div class="text-xs text-gray-600 dark:text-gray-400">ID: {{ $node->riscoin_id ?? 'N/A' }}</div>
            <div class="text-xs text-green-600 dark:text-green-400 font-medium">${{ number_format($node->invested_amount ?? 0, 2) }}</div>
            <div class="text-xs dark:text-white mt-1 bg-{{ $node->is_active ? 'green' : 'red' }}-100 dark:bg-{{ $node->is_active ? 'green' : 'red' }}-900 text-{{ $node->is_active ? 'green' : 'red' }}-800 dark:text-{{ $node->is_active ? 'green' : 'red' }}-100 px-2 py-1 rounded inline-block">
                {{ $node->is_active ? 'Active' : 'Inactive' }}
            </div>

            <!-- Direct members count -->
            @if($node->invites && $node->invites->count() > 0)
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    {{ $node->invites->count() }} direct members
                </div>
            @endif
        </div>

        <!-- Bottom connecting line to children -->
        @if($node->invites && $node->invites->count() > 0 && $showChildren)
            <div class="absolute w-px h-8 bg-gray-300 dark:bg-gray-700 bottom-0 translate-y-full"></div>
        @endif
    </div>

    <!-- Children Container with Horizontal Lines -->
    @if($node->invites && $node->invites->count() > 0 && $showChildren)
        <div class="relative mt-8">
            <!-- Horizontal connector line -->
            <div class="absolute left-1/2 right-1/2 w-full h-px bg-gray-300 dark:bg-gray-700 top-0"></div>

            <div class="flex justify-center space-x-8 relative">
                @foreach($node->invites as $index => $invite)
                    <div class="relative flex flex-col items-center">
                        <!-- Vertical connector to horizontal line -->
                        <div class="absolute w-px h-4 bg-gray-300 dark:bg-gray-700 top-0 -translate-y-full"></div>

                        <x-genealogy-node :node="$invite" :level="$level + 1" :showChildren="false" />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
