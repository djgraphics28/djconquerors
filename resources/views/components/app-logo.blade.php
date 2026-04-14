@php
    $user = auth()->user();
    $team = $user?->team;
    $teamLogo = $team?->getFirstMediaUrl('team_logo');
    $teamFavicon = $team?->getFirstMediaUrl('favicon');
    $teamName = $team?->name ?? 'DJ Conquerors';
@endphp

@if($teamLogo)
    <div class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md">
        <img src="{{ $teamLogo }}" alt="{{ $teamName }}" class="h-full w-full object-cover" />
    </div>
@else
    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
        <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
    </div>
@endif
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{{ $teamName }}</span>
</div>
