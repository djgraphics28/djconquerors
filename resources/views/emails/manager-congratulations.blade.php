@component('mail::message')
# 🎉 {{ $isUpgrade ? "You've Been Promoted!" : "Welcome to the Manager Team!" }}

Hello **{{ $user->name }}**,

@if ($isUpgrade)
Outstanding work! Your dedication and leadership have earned you a promotion.

You have been **upgraded to Manager Level {{ $newLevel }}** in the {{ config('app.name') }} network!
@else
Congratulations on reaching a major milestone!

You have officially been **promoted to Manager Level {{ $newLevel }}** in the {{ config('app.name') }} network!
@endif

@component('mail::panel')
## 🏆 Your Manager Status
- **Name:** {{ $user->name }}
- **Riscoin ID:** {{ $user->riscoin_id }}
- **Manager Level:** Level {{ $newLevel }}
@endcomponent

@php
$perks = [
    1 => ['title' => 'Manager Level 1', 'desc' => 'You\'ve built a solid foundation. Keep growing your network!'],
    2 => ['title' => 'Manager Level 2', 'desc' => 'Your team is expanding. You\'re leading the way!'],
    3 => ['title' => 'Manager Level 3', 'desc' => 'Mid-tier manager — your influence is growing strong!'],
    4 => ['title' => 'Manager Level 4', 'desc' => 'Senior manager status — you are a pillar of this network!'],
    5 => ['title' => 'Manager Level 5', 'desc' => 'Elite manager — you are among the top leaders in the network!'],
    6 => ['title' => 'Manager Level 6', 'desc' => 'Grand Manager — you have reached the pinnacle of leadership!'],
];
$perk = $perks[$newLevel] ?? ['title' => "Manager Level {$newLevel}", 'desc' => 'Keep up the great work!'];
@endphp

## {{ $perk['title'] }}
{{ $perk['desc'] }}

@if ($newLevel < 6)
Keep building your team to unlock **Manager Level {{ $newLevel + 1 }}** and earn even greater rewards!
@else
You have reached the highest manager level. You are a true champion of {{ config('app.name') }}!
@endif

@component('mail::button', ['url' => url('/my-team'), 'color' => 'success'])
View My Team
@endcomponent

Thank you for your hard work and commitment to growing the {{ config('app.name') }} community.

Warm regards,

**The {{ config('app.name') }} Team**
[{{ config('app.url') }}]
@endcomponent
