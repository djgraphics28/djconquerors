<?php

use Livewire\Volt\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?string $riscoinId = null;
    public array $birthdayCelebrators = [];

    public function mount(?string $riscoinId = null): void
    {
        $this->riscoinId = $riscoinId;
        $this->calculateBirthdayCelebrators();
    }

    private function calculateBirthdayCelebrators(): void
    {
        $currentNode = $this->riscoinId ? User::where('riscoin_id', $this->riscoinId)->first() : Auth::user();
        if (! $currentNode) { $this->birthdayCelebrators = []; return; }

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

    private function getDefaultAvatar(): string{ return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>'); }
};

?>

<div x-data="birthdayList()">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-pink-100 dark:bg-pink-900 mr-4">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Birthday Celebrators</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This month</p>
                    </div>
                </div>
                <div class="bg-pink-100 dark:bg-pink-900 text-pink-600 dark:text-pink-400 px-3 py-1 rounded-full text-sm font-semibold">{{ count($birthdayCelebrators) }}</div>
                    @php
                        $todayMd = now()->format('m-d');
                        $todaysCelebrants = collect($birthdayCelebrators)->filter(fn($c)=>($c['birth_md'] ?? '') === $todayMd && ($c['is_birthday_mention'] ?? false))->values()->toArray();
                    @endphp
                    @if(count($todaysCelebrants) > 0)
                        <button type="button" onclick="copyBirthdayToday()" class="ml-2 px-3 py-1 bg-pink-500 text-white text-sm rounded hover:bg-pink-600" title="Copy today's birthday greetings">Copy today's greetings</button>
                    @endif
            </div>
        </div>

        <div class="p-6">
            @if(count($birthdayCelebrators) > 0)
                <div class="space-y-3">
                    @foreach($birthdayCelebrators as $i => $celebrator)
                        @php $isToday = ($celebrator['birth_md'] ?? '') === now()->format('m-d'); @endphp
                        <div x-data="{copied:false, messageText:'', async copyToClipboard(){ try{ this.messageText = await $wire.getRandomBirthdayMessage('{{ addslashes($celebrator['name']) }}'); if(navigator.clipboard && navigator.clipboard.writeText){ await navigator.clipboard.writeText(this.messageText); this.copied=true; setTimeout(()=>this.copied=false,2000); return; } // fallback silently
                                         const ta = document.createElement('textarea'); ta.value = this.messageText; ta.style.position='fixed'; ta.style.left='-9999px'; document.body.appendChild(ta); ta.focus(); ta.select(); try{ document.execCommand('copy'); this.copied=true; setTimeout(()=>this.copied=false,2000);}catch(e){console.error(e);} finally{ document.body.removeChild(ta); } } catch(e){ this.showTextArea=true; setTimeout(()=>this.showTextArea=false,5000); } }, manualCopy(){ const ta=this.$refs['ta'+{{$i}}]; if(!ta) return; ta.focus(); ta.select(); try{ document.execCommand('copy'); }catch(e){} }}"
                            class="group flex items-center p-3 {{ $isToday ? 'bg-pink-50 dark:bg-pink-900/20 border-2 border-pink-200 dark:border-pink-700' : 'bg-gray-50 dark:bg-gray-700' }} rounded-lg relative">
                            <img class="w-10 h-10 rounded-full mr-3" src="{{ $celebrator['avatar'] }}" alt="{{ $celebrator['name'] }}">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $celebrator['name'] }} @if($isToday) <span class="ml-2">🎈</span>@endif</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Birthday: {{ $celebrator['birth_date'] }} • ID: {{ $celebrator['riscoin_id'] }}</p>
                            </div>
                            @if($celebrator['is_birthday_mention'])
                                <button x-on:click.prevent="copyToClipboard()" :disabled="copied" class="ml-3 p-2 text-gray-400 hover:text-blue-600 rounded-lg" title="Copy birthday message">
                                    <svg x-show="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <svg x-show="copied" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @else
                                <div class="ml-3 p-2 text-gray-300" title="Copy disabled">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </div>
                            @endif


                            <div x-show="copied" x-transition class="absolute inset-0 bg-green-500 bg-opacity-90 flex items-center justify-center rounded-lg z-10">
                                <span class="text-white font-semibold">Copied! 📋</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="mt-2 text-gray-500">No birthdays this month</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function birthdayList() {
            return {
                copiedAll: false,
                
                allMessage: '',
                async copyAll() {
                    try {
                        const users = @json($todaysCelebrants ?? []);
                        const parts = [];
                        for (const u of users) {
                            const msg = await $wire.getRandomBirthdayMessage(u.name);
                            parts.push(`🎉 ${u.name} — ${msg}`);
                        }
                        this.allMessage = parts.join('\n\n');
                        if (!this.allMessage) return;

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(this.allMessage);
                            this.copiedAll = true;
                            setTimeout(()=> this.copiedAll = false, 2000);
                            return;
                        }

                        // fallback attempts silently — not showing textarea
                        const ta = document.createElement('textarea'); ta.value = this.allMessage; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); } catch(e){} document.body.removeChild(ta);

                    } catch (e) {
                        console.error(e);
                        // fallback attempt (silent)
                    }
                },
                async copyTodayBulk() {
                    // helper for non-alpine bulk
                    const users = @json($todaysCelebrants ?? []);
                    if (!users || users.length === 0) return;
                    const parts = [];
                    for (const u of users) {
                        const msg = await $wire.getRandomBirthdayMessage(u.name);
                        parts.push(`🎉 ${u.name} — ${msg}`);
                    }
                    const message = parts.join('\n\n');
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(message);
                        const toast = document.createElement('div');
                        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                        toast.textContent = 'Birthday greetings copied!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 2000);
                        return true;
                    }
                    // fallback
                    const ta = document.createElement('textarea');
                    ta.value = message;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); } catch(e) { console.warn(e); }
                    document.body.removeChild(ta);
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    toast.textContent = 'Birthday greetings copied!';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 2000);
                    return true;
                },
                manualCopyAll() {
                    const ta = this.$refs.allTa;
                    if (!ta) return;
                    ta.focus(); ta.select(); try{ document.execCommand('copy'); }catch(e){}
                }
            }
        }
    </script>

</div>
