<?php

use App\Models\Faq;
use App\Models\FaqCategory;
use Livewire\Volt\Component;

new class extends Component {
    // ── Category form ─────────────────────────────────────────────────
    public string $catName = '';
    public bool   $catActive = true;
    public ?int   $editCatId = null;

    // ── FAQ form ──────────────────────────────────────────────────────
    public ?int   $selectedCatId = null;
    public string $faqQuestion = '';
    public string $faqAnswer   = '';
    public string $faqStatus   = 'draft';
    public ?int   $editFaqId   = null;

    // ── UI flags ──────────────────────────────────────────────────────
    public bool $showCatForm = false;
    public bool $showFaqForm = false;

    public function mount(): void
    {
        if (!auth()->user()->can('faq.manage')) {
            abort(403);
        }
    }

    public function with(): array
    {
        return [
            'categories' => FaqCategory::withCount('faqs')->orderBy('order')->get(),
            'faqs'       => $this->selectedCatId
                ? Faq::where('faq_category_id', $this->selectedCatId)->with('category')->orderBy('order')->get()
                : Faq::with('category')->orderBy('order')->get(),
        ];
    }

    // ── Category CRUD ─────────────────────────────────────────────────
    public function saveCat(): void
    {
        $this->validate(['catName' => 'required|string|max:255']);

        $data = ['name' => $this->catName, 'is_active' => $this->catActive];

        if ($this->editCatId) {
            FaqCategory::findOrFail($this->editCatId)->update($data);
            session()->flash('message', 'Category updated.');
        } else {
            FaqCategory::create(array_merge($data, ['order' => FaqCategory::max('order') + 1]));
            session()->flash('message', 'Category created.');
        }

        $this->resetCatForm();
    }

    public function editCat(int $id): void
    {
        $cat = FaqCategory::findOrFail($id);
        $this->catName    = $cat->name;
        $this->catActive  = $cat->is_active;
        $this->editCatId  = $id;
        $this->showCatForm = true;
    }

    public function deleteCat(int $id): void
    {
        FaqCategory::findOrFail($id)->delete();
        if ($this->selectedCatId === $id) {
            $this->selectedCatId = null;
        }
        session()->flash('message', 'Category deleted.');
    }

    public function toggleCatActive(int $id): void
    {
        $cat             = FaqCategory::findOrFail($id);
        $cat->is_active  = !$cat->is_active;
        $cat->save();
    }

    public function moveCatUp(int $id): void
    {
        $cat  = FaqCategory::findOrFail($id);
        $prev = FaqCategory::where('order', '<', $cat->order)->orderByDesc('order')->first();
        if ($prev) {
            [$cat->order, $prev->order] = [$prev->order, $cat->order];
            $cat->save();
            $prev->save();
        }
    }

    public function moveCatDown(int $id): void
    {
        $cat  = FaqCategory::findOrFail($id);
        $next = FaqCategory::where('order', '>', $cat->order)->orderBy('order')->first();
        if ($next) {
            [$cat->order, $next->order] = [$next->order, $cat->order];
            $cat->save();
            $next->save();
        }
    }

    private function resetCatForm(): void
    {
        $this->catName     = '';
        $this->catActive   = true;
        $this->editCatId   = null;
        $this->showCatForm = false;
    }

    // ── FAQ CRUD ──────────────────────────────────────────────────────
    public function saveFaq(): void
    {
        $this->validate([
            'faqQuestion'  => 'required|string|max:500',
            'faqAnswer'    => 'required|string',
            'faqStatus'    => 'required|in:published,draft',
        ]);

        $data = [
            'faq_category_id' => $this->selectedCatId,
            'question'        => $this->faqQuestion,
            'answer'          => $this->faqAnswer,
            'status'          => $this->faqStatus,
        ];

        if ($this->editFaqId) {
            Faq::findOrFail($this->editFaqId)->update($data);
            session()->flash('message', 'FAQ updated.');
        } else {
            $data['order'] = Faq::max('order') + 1;
            Faq::create($data);
            session()->flash('message', 'FAQ created.');
        }

        $this->resetFaqForm();
    }

    public function editFaq(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $this->faqQuestion    = $faq->question;
        $this->faqAnswer      = $faq->answer;
        $this->faqStatus      = $faq->status;
        $this->selectedCatId  = $faq->faq_category_id;
        $this->editFaqId      = $id;
        $this->showFaqForm    = true;
        $this->dispatch('faq-load-answer', content: $faq->answer);
    }

    public function deleteFaq(int $id): void
    {
        Faq::findOrFail($id)->delete();
        if ($this->editFaqId === $id) {
            $this->resetFaqForm();
        }
        session()->flash('message', 'FAQ deleted.');
    }

    public function toggleFaqStatus(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $faq->status = $faq->status === 'published' ? 'draft' : 'published';
        $faq->save();
    }

    public function moveFaqUp(int $id): void
    {
        $faq  = Faq::findOrFail($id);
        $prev = Faq::where('order', '<', $faq->order)->orderByDesc('order')->first();
        if ($prev) {
            [$faq->order, $prev->order] = [$prev->order, $faq->order];
            $faq->save();
            $prev->save();
        }
    }

    public function moveFaqDown(int $id): void
    {
        $faq  = Faq::findOrFail($id);
        $next = Faq::where('order', '>', $faq->order)->orderBy('order')->first();
        if ($next) {
            [$faq->order, $next->order] = [$next->order, $faq->order];
            $faq->save();
            $next->save();
        }
    }

    private function resetFaqForm(): void
    {
        $this->faqQuestion = '';
        $this->faqAnswer   = '';
        $this->faqStatus   = 'draft';
        $this->editFaqId   = null;
        $this->showFaqForm = false;
        $this->dispatch('faq-load-answer', content: '');
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">FAQ Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage FAQ categories and entries</p>
        </div>
        <a href="{{ route('faq.index') }}" wire:navigate
            class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Preview FAQ →</a>
    </div>

    @if (session('message'))
        <div class="mb-4 px-4 py-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Categories Panel -->
        <div>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Categories</h2>
                    <button wire:click="$set('showCatForm', true)" class="text-xs text-blue-600 dark:text-blue-400 font-medium hover:underline">+ Add</button>
                </div>

                @if ($showCatForm)
                    <form wire:submit="saveCat" class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-blue-50/50 dark:bg-blue-900/10 space-y-3">
                        <input type="text" wire:model="catName" placeholder="Category name"
                            class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-zinc-800 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('catName') border-red-400 @enderror"/>
                        @error('catName') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                            <input type="checkbox" wire:model="catActive" class="rounded text-blue-500"/>
                            Active
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-2 text-xs font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                {{ $editCatId ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" wire:click="$set('showCatForm', false)" class="flex-1 py-2 text-xs font-medium bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                @endif

                @if ($categories->isEmpty())
                    <p class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No categories yet. Add one above.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-zinc-700">
                        @foreach ($categories as $cat)
                            <li class="flex items-center gap-2 px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition {{ $selectedCatId === $cat->id ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                <button wire:click="$set('selectedCatId', {{ $cat->id === $selectedCatId ? 'null' : $cat->id }})"
                                    class="flex-1 text-left text-sm text-gray-700 dark:text-gray-200 truncate">
                                    {{ $cat->name }}
                                    <span class="text-xs text-gray-400 ml-1">({{ $cat->faqs_count }})</span>
                                    @if (!$cat->is_active)
                                        <span class="text-xs text-gray-400 ml-1">[hidden]</span>
                                    @endif
                                </button>
                                <div class="flex items-center gap-1">
                                    <button wire:click="moveCatUp({{ $cat->id }})" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button wire:click="moveCatDown({{ $cat->id }})" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button wire:click="editCat({{ $cat->id }})" class="p-1 text-blue-400 hover:text-blue-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="toggleCatActive({{ $cat->id }})" class="p-1 {{ $cat->is_active ? 'text-green-400 hover:text-green-600' : 'text-gray-300 hover:text-gray-500' }}">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                                    </button>
                                    <button wire:click="deleteCat({{ $cat->id }})"
                                        wire:confirm="Delete this category? FAQs will lose their category association."
                                        class="p-1 text-red-400 hover:text-red-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- FAQ List + Form -->
        <div class="lg:col-span-2 space-y-4">
            <!-- FAQ Form -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ $editFaqId ? 'Edit FAQ' : 'Add New FAQ' }}
                    </h2>
                    @if (!$showFaqForm)
                        <button wire:click="$set('showFaqForm', true)" class="text-xs text-blue-600 dark:text-blue-400 font-medium hover:underline">+ Add FAQ</button>
                    @endif
                </div>

                @if ($showFaqForm)
                    <form wire:submit="saveFaq" class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Category</label>
                            <select wire:model="selectedCatId"
                                class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">No Category</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Question <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="faqQuestion" placeholder="Enter the question"
                                class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('faqQuestion') border-red-400 @enderror"/>
                            @error('faqQuestion') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Answer <span class="text-red-500">*</span></label>
                            <div
                                x-data="{
                                    editor: null,
                                    init() {
                                        const self = this;
                                        this.editor = new Quill(this.$refs.editorBody, {
                                            theme: 'snow',
                                            modules: {
                                                toolbar: [
                                                    [{ header: [1, 2, 3, false] }],
                                                    ['bold', 'italic', 'underline', 'strike'],
                                                    [{ color: [] }, { background: [] }],
                                                    ['blockquote', 'code-block'],
                                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                                    ['link'],
                                                    ['clean']
                                                ]
                                            }
                                        });
                                        this.editor.root.innerHTML = @js($faqAnswer) || '';
                                        this.editor.on('text-change', () => {
                                            $wire.set('faqAnswer', self.editor.root.innerHTML, false);
                                        });
                                        window.addEventListener('faq-load-answer', (e) => {
                                            if (self.editor) {
                                                self.editor.root.innerHTML = e.detail.content || '';
                                            }
                                        });
                                    }
                                }"
                                wire:ignore
                            >
                                <div class="rounded-xl overflow-hidden @error('faqAnswer') ring-1 ring-red-400 @enderror">
                                    <div x-ref="editorBody" class="min-h-[160px]"></div>
                                </div>
                            </div>
                            @error('faqAnswer') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                            <select wire:model="faqStatus"
                                class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="draft">Draft (hidden)</option>
                                <option value="published">Published (visible)</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-2.5 text-sm font-semibold bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition">
                                {{ $editFaqId ? 'Update FAQ' : 'Create FAQ' }}
                            </button>
                            <button type="button" wire:click="resetFaqForm" class="px-4 py-2.5 text-sm font-medium bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- FAQ List -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/50 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        FAQs
                        @if ($selectedCatId)
                            <span class="font-normal text-gray-400">— {{ $categories->firstWhere('id', $selectedCatId)?->name }}</span>
                        @endif
                    </h2>
                    @if ($selectedCatId)
                        <button wire:click="$set('selectedCatId', null)" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">Clear filter</button>
                    @endif
                </div>

                @if ($faqs->isEmpty())
                    <p class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">No FAQs found.</p>
                @else
                    <ul class="divide-y divide-gray-100 dark:divide-zinc-700">
                        @foreach ($faqs as $faq)
                            <li class="flex items-start gap-3 px-4 py-3.5">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $faq->question }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $faq->answer }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if ($faq->category)
                                            <span class="text-xs text-blue-600 dark:text-blue-400">{{ $faq->category->name }}</span>
                                        @endif
                                        <span class="px-1.5 py-0.5 text-xs rounded font-medium {{ $faq->status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-gray-400' }}">
                                            {{ ucfirst($faq->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0 mt-0.5">
                                    <button wire:click="moveFaqUp({{ $faq->id }})" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button wire:click="moveFaqDown({{ $faq->id }})" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button wire:click="editFaq({{ $faq->id }})" class="p-1 text-blue-400 hover:text-blue-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button wire:click="toggleFaqStatus({{ $faq->id }})" class="p-1 {{ $faq->status === 'published' ? 'text-green-400 hover:text-green-600' : 'text-gray-300 hover:text-gray-500' }}" title="{{ $faq->status === 'published' ? 'Unpublish' : 'Publish' }}">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="5"/></svg>
                                    </button>
                                    <button wire:click="deleteFaq({{ $faq->id }})" wire:confirm="Delete this FAQ?" class="p-1 text-red-400 hover:text-red-600">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    <style>
        .dark .ql-toolbar.ql-snow { background: #374151; border-color: #4b5563 !important; }
        .dark .ql-container.ql-snow { background: #1f2937; border-color: #4b5563 !important; color: #f3f4f6; }
        .dark .ql-editor.ql-blank::before { color: #9ca3af; }
        .dark .ql-snow .ql-stroke { stroke: #d1d5db; }
        .dark .ql-snow .ql-fill, .dark .ql-snow .ql-stroke.ql-fill { fill: #d1d5db; }
        .dark .ql-snow .ql-picker { color: #d1d5db; }
        .dark .ql-snow .ql-picker-options { background: #1f2937; border-color: #4b5563; }
        .dark .ql-snow .ql-picker-item:hover, .dark .ql-snow .ql-picker-item.ql-selected { color: #818cf8; }
        .dark .ql-snow button:hover .ql-stroke, .dark .ql-snow .ql-picker-label:hover .ql-stroke { stroke: #818cf8; }
        .dark .ql-snow button.ql-active .ql-stroke { stroke: #818cf8; }
        .dark .ql-snow button:hover .ql-fill, .dark .ql-snow button.ql-active .ql-fill { fill: #818cf8; }
        .dark .ql-snow .ql-tooltip { background: #1f2937; border-color: #4b5563; color: #f3f4f6; box-shadow: none; }
        .dark .ql-snow .ql-tooltip input[type=text] { background: #374151; border-color: #4b5563; color: #f3f4f6; }
        .dark .ql-snow .ql-tooltip a { color: #818cf8; }
    </style>
</div>

@assets
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
@endassets
