<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?string $riscoinId = null;
    public array $birthdayCelebrators = [];
    public int $celebratorsPage = 1;
    public int $celebratorsPerPage = 10;
    public bool $celebratorsHasMore = false;

    public function mount(?string $riscoinId = null): void
    {
        $this->riscoinId = $riscoinId;
        $this->calculateBirthdayCelebrators();
    }

    public function loadMoreCelebrators(): void
    {
        $this->celebratorsPage++;
        $this->celebratorsHasMore = count($this->birthdayCelebrators) > ($this->celebratorsPage * $this->celebratorsPerPage);
    }

    public function getCurrentCelebratorsProperty(): array
    {
        return array_slice($this->birthdayCelebrators, 0, $this->celebratorsPage * $this->celebratorsPerPage);
    }

    private function calculateBirthdayCelebrators(): void
    {
        $currentNode = $this->riscoinId ? User::where('riscoin_id', $this->riscoinId)->first() : Auth::user();
        if (! $currentNode) { $this->birthdayCelebrators = []; $this->celebratorsHasMore = false; return; }

        $teamMemberIds = $this->getAllTeamMemberIds($currentNode->id);
        $currentMonth = now()->month;

        $this->birthdayCelebrators = User::whereIn('id', $teamMemberIds)
            ->whereNotNull('birth_date')
            ->orderByRaw('DAYOFMONTH(birth_date) ASC')
            ->get()
            ->filter(fn($u) => Carbon::parse($u->birth_date)->month == $currentMonth)
            ->map(function($u){ $d=Carbon::parse($u->birth_date); return [
                'name'=>$u->name,
                'birth_date'=>$d->format('M d'),
                'birth_md'=>$d->format('m-d'),
                'avatar'=>$u->getFirstMediaUrl('avatar')?:$this->getDefaultAvatar(),
                'riscoin_id'=>$u->riscoin_id,
                'invested_amount'=>$u->invested_amount,
                'date_joined'=>$u->date_joined,
                'is_birthday_mention'=>((int)($u->is_birthday_mention??0)===1),
            ];})->values()->toArray();
    }

    private function getAllTeamMemberIds($userId){
        $ids = [$userId]; $user = User::with('invites')->find($userId); if(! $user) return $ids;
        $lvl = $user->invites; while($lvl->isNotEmpty()){ $ids = array_merge($ids, $lvl->pluck('id')->toArray()); $next=collect(); foreach($lvl as $m){ $m->load('invites'); $next=$next->merge($m->invites);} $lvl=$next; }
        return array_unique($ids);
    }

    public function getRandomBirthdayMessage(string $name): string{
        $msgs = [
            "Happy Birthday, {$name}!\n\nWishing you all the very best today and always.\n\n— DJ Conquerors Family",
            "Happy Birthday, {$name}!\n\nCheers, DJ Conquerors Family",
            "It's your birthday, {$name}! Time to celebrate! 🎉\n\n— DJ Conquerors Family",
        ];
        return $msgs[array_rand($msgs)];
    }

    public function getBulkBirthdayMessage()
    {
        $todayMd = now()->format('m-d');
        $todaysCelebrants = collect($this->birthdayCelebrators)->filter(fn($c) => ($c['birth_md'] ?? '') === $todayMd && ($c['is_birthday_mention'] ?? false))->values();

        if ($todaysCelebrants->isEmpty()) {
            return '';
        }

        // Get all names and join them with commas
        $names = $todaysCelebrants->pluck('name')->toArray();
        $namesList = implode(', ', $names);

        return "🎉 Happy Birthday from DJ Conquerors! 🎉\n\nWishing all of today's celebrators an amazing day filled with joy, laughter, and success!\n\nHappy Birthday, {$namesList}!!\n\nCheers, DJ Conquerors Family\n\n---";
    }

    private function getDefaultAvatar(): string{ return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>'); }
};

?>

<div x-data="birthdayList()">
    @php
        $todayMd = now()->format('m-d');
        $todaysCelebrants = collect($birthdayCelebrators)->filter(fn($c) => ($c['birth_md'] ?? '') === $todayMd && ($c['is_birthday_mention'] ?? false))->values()->toArray();
    @endphp

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">

        {{-- Gradient Header --}}
        <div class="px-5 pt-5 pb-4 bg-gradient-to-r from-pink-500 via-rose-500 to-fuchsia-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-lg shadow-sm">
                        🎂
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-white">Birthday Celebrators</h3>
                        <p class="text-xs text-white/70">{{ now()->format('F Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold bg-white/20 text-white px-2.5 py-1 rounded-full backdrop-blur-sm">
                        {{ count($birthdayCelebrators) }} this month
                    </span>
                    @if(count($todaysCelebrants) > 0)
                        <button type="button" onclick="copyBirthdayBulk()"
                            class="flex items-center gap-1.5 px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs font-medium rounded-full transition-colors backdrop-blur-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Copy today's greetings
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Celebrators List --}}
        <div class="p-5">
            @if(count($birthdayCelebrators) > 0)
                <div
                    wire:ignore.self
                    class="space-y-2 overflow-y-auto pr-0.5 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700"
                    style="max-height: 420px;"
                    x-data="{
                        observer: null,
                        init() {
                            this.$nextTick(() => this.setupObserver());
                        },
                        setupObserver() {
                            if (this.observer) { this.observer.disconnect(); this.observer = null; }
                            const sentinel = this.$refs.sentinel;
                            if (!sentinel) return;
                            this.observer = new IntersectionObserver((entries) => {
                                if (entries[0].isIntersecting) {
                                    $wire.loadMoreCelebrators().then(() => {
                                        this.$nextTick(() => this.setupObserver());
                                    });
                                }
                            }, { root: this.$el, threshold: 0.1 });
                            this.observer.observe(sentinel);
                        }
                    }"
                >
                    @foreach($this->currentCelebrators as $i => $celebrator)
                        @php $isToday = ($celebrator['birth_md'] ?? '') === $todayMd; @endphp
                        <div x-data="{copied:false, messageText:'', async copyToClipboard(){ try{ this.messageText = await $wire.getRandomBirthdayMessage('{{ addslashes($celebrator['name']) }}'); if(navigator.clipboard && navigator.clipboard.writeText){ await navigator.clipboard.writeText(this.messageText); this.copied=true; setTimeout(()=>this.copied=false,2000); return; } const ta = document.createElement('textarea'); ta.value = this.messageText; ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta); ta.focus(); ta.select(); try{ document.execCommand('copy'); this.copied=true; setTimeout(()=>this.copied=false,2000);}catch(e){console.error(e);} finally{ document.body.removeChild(ta); } } catch(e){} }, manualCopy(){ const ta=this.$refs['ta'+{{$i}}]; if(!ta) return; ta.focus(); ta.select(); try{ document.execCommand('copy'); }catch(e){} }}"
                            class="relative group flex items-center gap-3 p-3 {{ $isToday ? 'bg-pink-50 dark:bg-pink-900/20 ring-2 ring-pink-200 dark:ring-pink-700/50' : 'bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700/60' }} rounded-xl transition-colors duration-150">

                            {{-- Avatar --}}
                            <img src="{{ $celebrator['avatar'] }}" alt="{{ $celebrator['name'] }}"
                                class="w-10 h-10 rounded-full object-cover ring-2 {{ $isToday ? 'ring-pink-300 dark:ring-pink-600' : 'ring-white dark:ring-gray-700' }} flex-shrink-0" />

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">
                                    {{ $celebrator['name'] }}
                                    @if($isToday)<span class="ml-1">🎈</span>@endif
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                    {{ $celebrator['riscoin_id'] }} · {{ $celebrator['birth_date'] }}
                                </p>
                            </div>

                            {{-- Date badge --}}
                            @if($isToday)
                                <span class="flex-shrink-0 text-xs font-bold bg-pink-100 dark:bg-pink-900/40 text-pink-600 dark:text-pink-400 px-2 py-0.5 rounded-full whitespace-nowrap">Today 🎉</span>
                            @endif

                            {{-- Copy button --}}
                            @if($celebrator['is_birthday_mention'])
                                <button x-on:click.prevent="copyToClipboard()" :disabled="copied"
                                    class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-pink-500 hover:bg-pink-50 dark:hover:bg-pink-900/30 transition-colors duration-150"
                                    title="Copy birthday message">
                                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <svg x-show="copied" class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            @else
                                <div class="flex-shrink-0 p-1.5 text-gray-200 dark:text-gray-700" title="Copy disabled">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Copied overlay --}}
                            <div x-show="copied" x-transition class="absolute inset-0 bg-green-500/90 flex items-center justify-center rounded-xl z-10">
                                <span class="text-white font-semibold text-sm">Copied! 📋</span>
                            </div>
                        </div>
                    @endforeach

                    {{-- IntersectionObserver sentinel --}}
                    @if($celebratorsHasMore)
                        <div x-ref="sentinel" class="flex items-center justify-center py-3 gap-2 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Loading more…
                        </div>
                    @else
                        <div class="py-2 text-center text-xs text-gray-400 dark:text-gray-500">
                            All {{ count($this->currentCelebrators) }} celebrators loaded
                        </div>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                    <span class="text-4xl mb-2">🎂</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No birthdays this month</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function birthdayList() {
            return {
                copiedAll: false,
                manualCopyAll() {
                    const ta = this.$refs.allTa;
                    if (!ta) return;
                    ta.focus(); ta.select(); try{ document.execCommand('copy'); }catch(e){}
                }
            }
        }

        // Client-side bulk copy using server-provided array (avoids race with Livewire)
        async function copyBirthdayBulk() {
            try {
                const users = @json($todaysCelebrants ?? []);
                if (!users || users.length === 0) return;

                // Get the bulk message from Livewire server
                const bulkMessage = await @this.getBulkBirthdayMessage();

                if (!bulkMessage) return;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(bulkMessage);
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    toast.textContent = 'Birthday greetings copied to clipboard!';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2000);
                    return true;
                }

                // Fallback method for older browsers
                const ta = document.createElement('textarea');
                ta.value = bulkMessage;
                ta.style.position = 'fixed';
                ta.style.left = '-999999px';
                ta.style.top = '-999999px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                        toast.textContent = 'Birthday greetings copied to clipboard!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 2000);
                    }
                } catch (err) {
                    console.error('Fallback copy failed:', err);
                    alert('Failed to copy. Please try again.');
                } finally {
                    document.body.removeChild(ta);
                }

                return true;
            } catch (e) {
                console.error('Copy error:', e);
                alert('Failed to copy. Please try again.');
            }
        }
    </script>

</div>
