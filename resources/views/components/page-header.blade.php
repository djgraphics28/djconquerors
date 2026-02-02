@props(['title', 'description' => null])

<div class="mb-6 border-b border-zinc-200 bg-white pb-6 dark:border-zinc-700 dark:bg-zinc-800">
    <div class="px-6 pt-6">
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
            {{ $title }}
        </h1>
        @if($description)
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                {{ $description }}
            </p>
        @endif
    </div>
</div>
