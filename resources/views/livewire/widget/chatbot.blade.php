<?php

use App\Models\ChatbotLog;
use App\Models\FaqCategory;
use App\Models\Ticket;
use App\Notifications\TicketSubmitted;
use App\Services\ChatbotService;
use Livewire\Volt\Component;

new class extends Component {
    public array $faqData = [];

    public function mount(): void
    {
        $this->faqData = FaqCategory::active()
            ->with(['publishedFaqs'])
            ->get()
            ->map(fn($cat) => [
                'id'   => $cat->id,
                'name' => $cat->name,
                'faqs' => $cat->publishedFaqs->map(fn($f) => [
                    'id'       => $f->id,
                    'question' => $f->question,
                    'answer'   => $f->answer,
                ])->values()->toArray(),
            ])
            ->values()
            ->toArray();
    }

    public function sendMessage(string $message, array $history = []): void
    {
        $message = trim($message);

        if (empty($message)) {
            return;
        }

        /** @var ChatbotService $service */
        $service = app(ChatbotService::class);
        $result  = $service->respond($message, $history, auth()->id());

        // Persist the conversation log
        $fullHistory = array_merge(
            $history,
            [
                ['role' => 'user',      'content' => $message],
                ['role' => 'assistant', 'content' => $result['message']],
            ]
        );

        ChatbotLog::create([
            'user_id'        => auth()->id(),
            'session_id'     => session()->getId(),
            'messages'       => $fullHistory,
            'ticket_created' => false,
        ]);

        $this->dispatch('chatbot-response',
            message:        $result['message'],
            suggest_ticket: $result['suggest_ticket'],
        );
    }

    public function createTicketFromChat(string $contextSummary, array $history = []): void
    {
        $ticket = Ticket::create([
            'user_id'     => auth()->id(),
            'subject'     => 'Support request from chat',
            'description' => $contextSummary ?: 'No description provided via chat.',
            'category'    => 'General Inquiry',
            'priority'    => 'medium',
            'status'      => 'open',
        ]);

        auth()->user()->notify(new TicketSubmitted($ticket));

        // Update the latest log with ticket info
        ChatbotLog::where('user_id', auth()->id())
            ->where('session_id', session()->getId())
            ->latest()
            ->first()
            ?->update(['ticket_created' => true, 'ticket_id' => $ticket->id]);

        $this->dispatch('chatbot-ticket-created',
            ticket_number: $ticket->ticket_number,
            ticket_id:     $ticket->id,
        );
    }
}; ?>

<div
    x-data="{
        open: false,
        _storageKey: 'djc_chat_msgs_{{ auth()->id() }}',
        messages: JSON.parse(localStorage.getItem('djc_chat_msgs_{{ auth()->id() }}') || '[]'),
        inputMessage: '',
        isTyping: false,
        suggestTicket: false,
        ticketSubject: '',
        showFollowUp: false,

        // FAQ Browser state
        faqData: {{ Js::from($faqData) }},
        view: null,           // 'chat' | 'browse'
        browseCat: null,      // null = category list, object = selected category

        // Idle state
        isIdle: false,
        _idleTimer: null,
        IDLE_MS: 2 * 60 * 1000,

        init() {
            this.view = this.messages.length > 0 ? 'chat' : 'browse';
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.scrollBottom());
                this.resetIdleTimer();
            } else {
                this.clearIdleTimer();
                this.isIdle = false;
            }
        },

        resetIdleTimer() {
            this.clearIdleTimer();
            this._idleTimer = setTimeout(() => { this.isIdle = true; }, this.IDLE_MS);
        },

        dismissIdle() {
            this.isIdle = false;
            this.resetIdleTimer();
        },

        clearIdleTimer() {
            if (this._idleTimer) { clearTimeout(this._idleTimer); this._idleTimer = null; }
        },

        resumeChat() {
            this.dismissIdle();
            this.$nextTick(() => this.$refs.chatInput?.focus());
        },

        addMessage(role, content, extra = {}) {
            this.messages.push({ role, content, time: new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}), ...extra });
            this.saveMessages();
            this.$nextTick(() => this.scrollBottom());
        },

        saveMessages() {
            const toStore = this.messages.slice(-40);
            localStorage.setItem(this._storageKey, JSON.stringify(toStore));
        },

        scrollBottom() {
            const el = this.$refs.msgList;
            if (el) el.scrollTop = el.scrollHeight;
        },

        async sendMessage() {
            const msg = this.inputMessage.trim();
            if (!msg || this.isTyping) return;
            this.inputMessage = '';
            this.view = 'chat';
            this.resetIdleTimer();
            this.showFollowUp = false;
            this.addMessage('user', msg);
            this.isTyping = true;
            this.suggestTicket = false;
            const history = this.messages.slice(-10).map(m => ({ role: m.role, content: m.content }));
            $wire.sendMessage(msg, history);
        },

        handleEnter(e) {
            if (!e.shiftKey) { e.preventDefault(); this.sendMessage(); }
        },

        clearChat() {
            this.messages = [];
            this.suggestTicket = false;
            this.showFollowUp = false;
            this.view = 'browse';
            this.browseCat = null;
            localStorage.removeItem(this._storageKey);
        },

        createTicket() {
            const userMessages = this.messages.filter(m => m.role === 'user').map(m => m.content);
            const summary = userMessages.join(' | ');
            this.ticketSubject = userMessages[0] ? userMessages[0].substring(0, 255) : 'Support request from chat';
            $wire.createTicketFromChat(summary, this.messages.slice(-10).map(m => ({ role: m.role, content: m.content })));
            this.suggestTicket = false;
        },

        // FAQ browser actions
        selectCategory(cat) {
            this.browseCat = cat;
        },

        backToCategories() {
            this.browseCat = null;
        },

        selectFaq(faq) {
            this.view = 'chat';
            this.browseCat = null;
            this.resetIdleTimer();
            this.showFollowUp = false;
            this.addMessage('user', faq.question);
            this.isTyping = true;
            this.$nextTick(() => this.scrollBottom());
            const delay = Math.min(Math.max(faq.answer.replace(/<[^>]*>/g,'').length * 18, 800), 2800);
            setTimeout(() => {
                this.isTyping = false;
                this.addMessage('assistant', faq.answer, { isHtml: true });
                this.showFollowUp = true;
                this.$nextTick(() => this.scrollBottom());
            }, delay);
        },

        openBrowser() {
            this.view = 'browse';
            this.browseCat = null;
        }
    }"
    @chatbot-response.window="
        const _msg = $event.detail.message;
        const _suggest = $event.detail.suggest_ticket ?? false;
        const _delay = Math.min(Math.max(_msg.length * 18, 800), 2800);
        setTimeout(() => {
            isTyping = false;
            addMessage('assistant', _msg, { isHtml: true });
            suggestTicket = _suggest;
            showFollowUp = true;
        }, _delay);
    "
    @chatbot-ticket-created.window="
        suggestTicket = false;
        addMessage('assistant', 'Your ticket has been created! Ticket #' + $event.detail.ticket_number + '. Our support team will respond shortly. You can track it in My Tickets.', { isTicket: true, ticket_id: $event.detail.ticket_id });
    "
    class="fixed bottom-5 right-5 z-50 flex flex-col items-end"
>
    <!-- Chat Panel -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @mousemove="resetIdleTimer()"
        @keydown.window="if (open) resetIdleTimer()"
        class="mb-3 w-80 sm:w-96 bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-zinc-700 flex flex-col overflow-hidden"
        style="max-height: 540px;"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex-shrink-0">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold leading-none">Support Chat</p>
                    <p class="text-xs text-blue-200 mt-0.5" x-text="view === 'browse' ? (browseCat ? browseCat.name : 'Browse FAQs') : 'Ask me anything'"></p>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <!-- Toggle Browse/Chat -->
                <button @click="view === 'browse' && messages.length > 0 ? view = 'chat' : openBrowser()"
                    title="Browse FAQs"
                    :class="view === 'browse' ? 'bg-white/20 text-white' : 'text-blue-200 hover:text-white'"
                    class="p-1.5 rounded-lg hover:bg-white/10 transition text-xs font-medium flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="hidden sm:inline text-[10px]">FAQ</span>
                </button>
                <button @click="clearChat()" title="Clear conversation"
                    class="p-1.5 rounded-lg hover:bg-white/10 transition text-blue-200 hover:text-white">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
                <button @click="toggle()"
                    class="p-1.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- ── FAQ BROWSER VIEW ─────────────────────────────────────── -->
        <div x-show="view === 'browse'" class="flex-1 overflow-y-auto bg-gray-50 dark:bg-zinc-900/50" style="min-height: 310px; max-height: 370px;">

            <!-- Category List -->
            <div x-show="!browseCat" class="p-4">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-3">Select a topic</p>

                <template x-if="faqData.length === 0">
                    <div class="text-center py-10">
                        <p class="text-sm text-gray-400 dark:text-gray-500">No FAQ categories available yet.</p>
                        <button @click="view = 'chat'" class="mt-3 text-xs text-blue-500 hover:underline">Go to chat →</button>
                    </div>
                </template>

                <div class="space-y-2">
                    <template x-for="cat in faqData" :key="cat.id">
                        <button @click="selectCategory(cat)"
                            x-show="cat.faqs.length > 0"
                            class="w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-left group">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400" x-text="cat.name"></span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded-full" x-text="cat.faqs.length + ' FAQs'"></span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </button>
                    </template>
                </div>

                <!-- Type a question instead -->
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-zinc-700 text-center">
                    <button @click="view = 'chat'; $nextTick(() => $refs.chatInput?.focus())"
                        class="text-xs text-blue-500 dark:text-blue-400 hover:underline">
                        Or type your question directly →
                    </button>
                </div>
            </div>

            <!-- FAQ List (after category selected) -->
            <div x-show="browseCat !== null">
                <!-- Category header / back -->
                <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 sticky top-0 flex-shrink-0">
                    <button @click="backToCategories()"
                        class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate" x-text="browseCat?.name"></span>
                    <span class="ml-auto text-xs text-gray-400 flex-shrink-0" x-text="(browseCat?.faqs?.length ?? 0) + ' questions'"></span>
                </div>

                <div class="p-3 space-y-2">
                    <template x-for="faq in (browseCat?.faqs ?? [])" :key="faq.id">
                        <button @click="selectFaq(faq)"
                            class="w-full text-left px-4 py-3 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition group">
                            <div class="flex items-start gap-2">
                                <svg class="w-3.5 h-3.5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 leading-snug" x-text="faq.question"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- ── CHAT VIEW ────────────────────────────────────────────── -->
        <div x-show="view === 'chat'" x-ref="msgList" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50 dark:bg-zinc-900/50" style="min-height: 0; max-height: 370px;">
            <template x-if="messages.length === 0">
                <div class="text-center py-10">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Hi there! 👋</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Type your question or <button @click="openBrowser()" class="text-blue-500 hover:underline font-medium">browse FAQs</button>.</p>
                </div>
            </template>

            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user'
                        ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm'
                        : 'bg-white dark:bg-zinc-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-zinc-600 rounded-2xl rounded-bl-sm'"
                        class="max-w-[80%] px-3 py-2 shadow-sm">
                        <template x-if="msg.isHtml">
                            <div class="text-xs leading-relaxed prose prose-xs dark:prose-invert max-w-none [&_img]:max-w-full [&_img]:rounded [&_p]:mb-1 [&_ul]:pl-4 [&_ol]:pl-4" x-html="msg.content"></div>
                        </template>
                        <template x-if="!msg.isHtml">
                            <p class="text-xs leading-relaxed whitespace-pre-wrap" x-text="msg.content"></p>
                        </template>
                        <template x-if="msg.isTicket && msg.ticket_id">
                            <a :href="'{{ url('/tickets/') }}/' + msg.ticket_id"
                                class="mt-1.5 flex items-center gap-1 text-xs text-blue-400 hover:text-blue-300 font-medium">
                                View Ticket →
                            </a>
                        </template>
                        <p class="text-right mt-0.5 opacity-50" style="font-size:10px" x-text="msg.time"></p>
                    </div>
                </div>
            </template>

            <!-- Typing Indicator -->
            <template x-if="isTyping">
                <div class="flex justify-start items-end gap-2">
                    <!-- Agent avatar -->
                    <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                        </svg>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 ml-1">Support Agent</span>
                        <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-600 rounded-2xl rounded-bl-sm px-4 py-3 shadow-sm">
                            <div class="flex items-center gap-1.5">
                                <span class="typing-dot" style="--i:0"></span>
                                <span class="typing-dot" style="--i:1"></span>
                                <span class="typing-dot" style="--i:2"></span>
                            </div>
                        </div>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 ml-1 italic">typing…</span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Follow-up Prompt -->
        <template x-if="showFollowUp && !isTyping && view === 'chat'">
            <div class="px-4 py-3 border-t border-gray-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex-shrink-0">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 text-center">Was that helpful? What would you like to do next?</p>
                <div class="flex gap-2">
                    <button
                        @click="showFollowUp = false; $nextTick(() => $refs.chatInput?.focus())"
                        class="flex-1 py-2 text-xs font-semibold rounded-xl border border-blue-500 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                        💬 Continue Chatting
                    </button>
                    <button
                        @click="showFollowUp = false; openBrowser()"
                        class="flex-1 py-2 text-xs font-semibold rounded-xl border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                        🔍 Try Another Topic
                    </button>
                </div>
            </div>
        </template>

        <!-- Suggest Ticket Banner -->
        <template x-if="suggestTicket && view === 'chat'">
            <div class="px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-100 dark:border-amber-900/30 flex items-center justify-between gap-3 flex-shrink-0">
                <p class="text-xs text-amber-700 dark:text-amber-400 flex-1">Can't find an answer? Create a support ticket.</p>
                <button @click="createTicket()"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition">
                    Create Ticket
                </button>
            </div>
        </template>

        <!-- Idle Banner -->
        <template x-if="isIdle">
            <div class="px-4 py-3 bg-gray-50 dark:bg-zinc-900/60 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-between gap-3 flex-shrink-0">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <span class="text-lg flex-shrink-0">😴</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-snug">Still there? Chat has been idle for a while.</p>
                </div>
                <button @click="resumeChat()"
                    class="flex-shrink-0 px-3 py-1.5 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition whitespace-nowrap">
                    Start Chat Again
                </button>
            </div>
        </template>

        <!-- Input -->
        <div class="p-3 border-t border-gray-100 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex-shrink-0">
            <div class="flex items-end gap-2">
                <textarea
                    x-ref="chatInput"
                    x-model="inputMessage"
                    @keydown.enter="handleEnter($event)"
                    @focus="view = 'chat'"
                    @input="dismissIdle()"
                    :disabled="isTyping"
                    placeholder="Type your message…"
                    rows="1"
                    class="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-zinc-900/50 border border-gray-200 dark:border-zinc-600 rounded-xl resize-none text-gray-800 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    style="min-height: 38px; max-height: 90px; overflow-y: auto;"
                    @input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 90) + 'px';"
                ></textarea>
                <button @click="sendMessage()" :disabled="!inputMessage.trim() || isTyping"
                    class="flex-shrink-0 w-9 h-9 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl flex items-center justify-center transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
            <p class="mt-1.5 text-center text-[10px] text-gray-400 dark:text-gray-600">Press Enter to send · Shift+Enter for new line</p>
        </div>
    </div>

    <!-- Floating Toggle Button -->
    <button @click="toggle()"
        class="relative w-13 h-13 bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center"
        style="width: 52px; height: 52px;"
        x-tooltip="open ? 'Close chat' : 'Support chat'"
    >
        <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <svg x-show="open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <!-- Unread badge - shown when panel is closed and has messages -->
        <template x-if="!open && messages.length > 0">
            <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                !
            </span>
        </template>
    </button>

    <style>
        @keyframes typingWave {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30%            { transform: translateY(-5px); opacity: 1; }
        }
        .typing-dot {
            display: inline-block;
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #6b7280;
            animation: typingWave 1.2s ease-in-out infinite;
            animation-delay: calc(var(--i) * 0.2s);
        }
        .dark .typing-dot { background: #9ca3af; }
    </style>
</div>
