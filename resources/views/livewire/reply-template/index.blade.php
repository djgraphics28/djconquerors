<?php

use Livewire\Volt\Component;
use App\Models\ReplyTemplate;
use App\Models\ReplyTemplateItem;

new class extends Component {
    public $templates = [];
    public $selectedTemplate = null;
    public $templateItems = [];

    // Template modal
    public $showTemplateModal = false;
    public $editingTemplate = null;
    public $templateName = '';
    public $templateDescription = '';
    public $templateIsActive = true;

    // Item modal
    public $showItemModal = false;
    public $editingItem = null;
    public $itemTitle = '';
    public $itemContent = '';
    public $itemIsActive = true;

    public function mount(): void
    {
        $this->loadTemplates();
    }

    public function loadTemplates(): void
    {
        $this->templates = ReplyTemplate::withCount('items')->ordered()->get();
    }

    public function selectTemplate($templateId): void
    {
        $this->selectedTemplate = ReplyTemplate::with(['items' => fn($q) => $q->orderBy('order')])->find($templateId);
        $this->templateItems = $this->selectedTemplate->items;
    }

    // ─── Template CRUD ────────────────────────────────────────────────────────────

    public function openTemplateModal($id = null): void
    {
        if ($id) {
            $this->editingTemplate = ReplyTemplate::findOrFail($id);
            $this->templateName = $this->editingTemplate->name;
            $this->templateDescription = $this->editingTemplate->description ?? '';
            $this->templateIsActive = $this->editingTemplate->is_active;
        } else {
            $this->editingTemplate = null;
            $this->templateName = '';
            $this->templateDescription = '';
            $this->templateIsActive = true;
        }
        $this->resetValidation(['templateName']);
        $this->showTemplateModal = true;
    }

    public function saveTemplate(): void
    {
        $this->validate(['templateName' => 'required|min:3|max:255']);

        if ($this->editingTemplate) {
            $this->editingTemplate->update([
                'name'        => $this->templateName,
                'description' => $this->templateDescription,
                'is_active'   => $this->templateIsActive,
            ]);
            session()->flash('message', 'Template updated successfully.');
        } else {
            ReplyTemplate::create([
                'name'        => $this->templateName,
                'description' => $this->templateDescription,
                'is_active'   => $this->templateIsActive,
                'order'       => (ReplyTemplate::max('order') ?? 0) + 1,
            ]);
            session()->flash('message', 'Template created successfully.');
        }

        $this->showTemplateModal = false;
        $this->editingTemplate = null;
        $this->loadTemplates();
    }

    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
        $this->editingTemplate = null;
        $this->templateName = '';
        $this->templateDescription = '';
        $this->templateIsActive = true;
    }

    public function toggleTemplateActive($id): void
    {
        $template = ReplyTemplate::findOrFail($id);
        $template->update(['is_active' => !$template->is_active]);
        $this->loadTemplates();
        if ($this->selectedTemplate?->id == $id) {
            $this->selectTemplate($id);
        }
    }

    public function deleteTemplate($id): void
    {
        ReplyTemplate::findOrFail($id)->delete();
        if ($this->selectedTemplate?->id == $id) {
            $this->selectedTemplate = null;
            $this->templateItems = [];
        }
        $this->loadTemplates();
        session()->flash('message', 'Template deleted successfully.');
    }

    public function updateTemplateOrder($orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplate::where('id', $id)->update(['order' => $index + 1]);
        }
        $this->loadTemplates();
    }

    // ─── Item CRUD ────────────────────────────────────────────────────────────────

    public function openItemModal($id = null): void
    {
        if ($id) {
            $this->editingItem  = ReplyTemplateItem::findOrFail($id);
            $this->itemTitle    = $this->editingItem->title;
            $this->itemContent  = $this->editingItem->content;
            $this->itemIsActive = $this->editingItem->is_active;
        } else {
            $this->editingItem  = null;
            $this->itemTitle    = '';
            $this->itemContent  = '';
            $this->itemIsActive = true;
        }
        $this->resetValidation(['itemTitle', 'itemContent']);
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemTitle'   => 'required|min:3|max:255',
            'itemContent' => 'required|min:3',
        ]);

        if ($this->editingItem) {
            $this->editingItem->update([
                'title'     => $this->itemTitle,
                'content'   => $this->itemContent,
                'is_active' => $this->itemIsActive,
            ]);
            session()->flash('message', 'Item updated successfully.');
        } else {
            ReplyTemplateItem::create([
                'reply_template_id' => $this->selectedTemplate->id,
                'title'             => $this->itemTitle,
                'content'           => $this->itemContent,
                'is_active'         => $this->itemIsActive,
                'order'             => ($this->selectedTemplate->items()->max('order') ?? 0) + 1,
            ]);
            session()->flash('message', 'Item created successfully.');
        }

        $this->showItemModal = false;
        $this->editingItem = null;
        $this->selectTemplate($this->selectedTemplate->id);
    }

    public function closeItemModal(): void
    {
        $this->showItemModal = false;
        $this->editingItem  = null;
        $this->itemTitle    = '';
        $this->itemContent  = '';
        $this->itemIsActive = true;
    }

    public function toggleItemActive($id): void
    {
        $item = ReplyTemplateItem::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        $this->selectTemplate($this->selectedTemplate->id);
    }

    public function deleteItem($id): void
    {
        ReplyTemplateItem::findOrFail($id)->delete();
        $this->selectTemplate($this->selectedTemplate->id);
        session()->flash('message', 'Item deleted successfully.');
    }

    public function updateItemOrder($orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplateItem::where('id', $id)->update(['order' => $index + 1]);
        }
        $this->selectTemplate($this->selectedTemplate->id);
    }
}; ?>

<div>
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 rounded-2xl p-5 mb-5 text-white shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold">Reply Templates</h1>
                    <p class="text-indigo-200 text-sm">Manage message templates with dynamic variables</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="openTemplateModal()"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold bg-white text-indigo-700 hover:bg-indigo-50 px-3 py-1.5 rounded-full transition-colors shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Template
                </button>
                <span class="text-xs bg-white/20 text-white px-3 py-1 rounded-full font-medium">
                    {{ $templates->count() }} {{ \Illuminate\Support\Str::plural('Template', $templates->count()) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session('message'))
        <div class="mb-5 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ session('message') }}</p>
        </div>
    @endif

    <!-- Main Two-Column Layout -->
    <div class="grid lg:grid-cols-3 gap-5">

        <!-- ── Templates Sidebar ─────────────────────────────────────────── -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Templates</h2>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">Drag to reorder</span>
                </div>

                @if ($templates->isEmpty())
                    <div class="text-center py-14 px-4">
                        <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No templates yet</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click <strong>New Template</strong> to create one</p>
                    </div>
                @else
                    <div
                        class="p-3 space-y-2"
                        x-data="{
                            draggedTpl: null,
                            reorder(parent) {
                                const ids = Array.from(parent.querySelectorAll(':scope > [data-id]')).map(el => parseInt(el.getAttribute('data-id')));
                                $wire.updateTemplateOrder(ids);
                            }
                        }">
                        @foreach ($templates as $template)
                            <div
                                wire:key="tpl-{{ $template->id }}"
                                data-id="{{ $template->id }}"
                                draggable="true"
                                @dragstart="draggedTpl = $event.currentTarget; setTimeout(() => $event.currentTarget.classList.add('opacity-40'), 0);"
                                @dragend="$event.currentTarget.classList.remove('opacity-40'); draggedTpl = null;"
                                @dragenter.prevent="$event.currentTarget.classList.add('ring-2','ring-indigo-400','ring-offset-1')"
                                @dragover.prevent
                                @dragleave="if (!$event.currentTarget.contains($event.relatedTarget)) $event.currentTarget.classList.remove('ring-2','ring-indigo-400','ring-offset-1');"
                                @drop.prevent="
                                    $event.currentTarget.classList.remove('ring-2','ring-indigo-400','ring-offset-1');
                                    if (draggedTpl && draggedTpl !== $event.currentTarget) {
                                        const parent = $event.currentTarget.parentNode;
                                        const siblings = Array.from(parent.querySelectorAll(':scope > [data-id]'));
                                        const fromIdx = siblings.indexOf(draggedTpl);
                                        const toIdx = siblings.indexOf($event.currentTarget);
                                        if (fromIdx < toIdx) parent.insertBefore(draggedTpl, $event.currentTarget.nextSibling);
                                        else parent.insertBefore(draggedTpl, $event.currentTarget);
                                        reorder(parent);
                                    }
                                "
                                class="cursor-grab active:cursor-grabbing transition-all duration-150">

                                <div class="rounded-xl border-2 overflow-hidden transition-all duration-200
                                    {{ $selectedTemplate && $selectedTemplate->id === $template->id
                                        ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50/60 dark:bg-indigo-900/20 shadow-sm'
                                        : 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 hover:border-indigo-300 dark:hover:border-indigo-600' }}">

                                    <!-- Clickable top area -->
                                    <div class="flex items-start gap-2 p-3 cursor-pointer select-none"
                                        wire:click="selectTemplate({{ $template->id }})">
                                        <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 mt-1 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                        </svg>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-800 dark:text-white leading-tight">{{ $template->name }}</span>
                                                @if (!$template->is_active)
                                                    <span class="text-[10px] px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-full font-medium">Inactive</span>
                                                @endif
                                            </div>
                                            @if ($template->description)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 rt-clamp-2">{{ $template->description }}</p>
                                            @endif
                                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ $template->items_count }} {{ \Illuminate\Support\Str::plural('item', $template->items_count) }}</p>
                                        </div>
                                    </div>

                                    <!-- Actions bar -->
                                    <div class="flex items-center px-2 py-1.5 bg-white/60 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700/60 gap-1">
                                        <!-- Active toggle -->
                                        <button
                                            wire:click="toggleTemplateActive({{ $template->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleTemplateActive({{ $template->id }})"
                                            title="{{ $template->is_active ? 'Deactivate' : 'Activate' }}"
                                            draggable="false"
                                            class="flex-1 inline-flex items-center justify-center gap-1 text-[10px] font-medium px-1.5 py-1 rounded-lg transition-colors duration-150
                                                {{ $template->is_active
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                            @if ($template->is_active)
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Active
                                            @else
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Inactive
                                            @endif
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            wire:click="openTemplateModal({{ $template->id }})"
                                            draggable="false"
                                            title="Edit template"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors duration-150">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            wire:click="deleteTemplate({{ $template->id }})"
                                            wire:confirm="Delete '{{ $template->name }}'? All items will be permanently removed."
                                            draggable="false"
                                            title="Delete template"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-150">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- ── Items Panel ────────────────────────────────────────────────── -->
        <div class="lg:col-span-2">
            @if ($selectedTemplate)
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

                    <!-- Panel header -->
                    <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-bold text-gray-800 dark:text-white">{{ $selectedTemplate->name }}</h2>
                                @if (!$selectedTemplate->is_active)
                                    <span class="text-[10px] px-2 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-full font-medium">Inactive</span>
                                @endif
                            </div>
                            @if ($selectedTemplate->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $selectedTemplate->description }}</p>
                            @endif
                        </div>
                        <button wire:click="openItemModal()"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Item
                        </button>
                    </div>

                    <!-- Variables reference bar -->
                    <div class="px-5 py-3 bg-indigo-50/60 dark:bg-indigo-900/10 border-b border-indigo-100 dark:border-indigo-900/30">
                        <p class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wide mb-2">Available Variables — click to copy</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['{name}', '{riscoin_id}', '{email}', '{invested_amount}', '{age}', '{gender}', '{inviters_code}', '{primary_language}', '{secondary_language}', '{language}', '{assistant.riscoin_id}'] as $var)
                                <div x-data="{ copied: false }">
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $var }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                        :title="copied ? 'Copied!' : 'Click to copy'"
                                        class="inline-flex items-center gap-1 text-[11px] font-mono px-2 py-0.5 rounded-md border transition-all duration-200 cursor-pointer"
                                        :class="copied
                                            ? 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-white dark:bg-gray-700 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30'">
                                        <span x-show="!copied">{{ $var }}</span>
                                        <span x-show="copied" class="flex items-center gap-0.5">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            Copied!
                                        </span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Items List -->
                    @if ($templateItems->isEmpty())
                        <div class="text-center py-14">
                            <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No items yet</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click <strong>Add Item</strong> to create a reply snippet</p>
                        </div>
                    @else
                        <div
                            class="p-4 space-y-3"
                            x-data="{
                                draggedItem: null,
                                reorder(parent) {
                                    const ids = Array.from(parent.querySelectorAll(':scope > [data-id]')).map(el => parseInt(el.getAttribute('data-id')));
                                    $wire.updateItemOrder(ids);
                                }
                            }">
                            @foreach ($templateItems as $item)
                                <div
                                    wire:key="item-{{ $item->id }}"
                                    data-id="{{ $item->id }}"
                                    draggable="true"
                                    @dragstart="draggedItem = $event.currentTarget; setTimeout(() => $event.currentTarget.classList.add('opacity-40'), 0);"
                                    @dragend="$event.currentTarget.classList.remove('opacity-40'); draggedItem = null;"
                                    @dragenter.prevent="$event.currentTarget.classList.add('ring-2','ring-indigo-400','ring-offset-1')"
                                    @dragover.prevent
                                    @dragleave="if (!$event.currentTarget.contains($event.relatedTarget)) $event.currentTarget.classList.remove('ring-2','ring-indigo-400','ring-offset-1');"
                                    @drop.prevent="
                                        $event.currentTarget.classList.remove('ring-2','ring-indigo-400','ring-offset-1');
                                        if (draggedItem && draggedItem !== $event.currentTarget) {
                                            const parent = $event.currentTarget.parentNode;
                                            const siblings = Array.from(parent.querySelectorAll(':scope > [data-id]'));
                                            const fromIdx = siblings.indexOf(draggedItem);
                                            const toIdx = siblings.indexOf($event.currentTarget);
                                            if (fromIdx < toIdx) parent.insertBefore(draggedItem, $event.currentTarget.nextSibling);
                                            else parent.insertBefore(draggedItem, $event.currentTarget);
                                            reorder(parent);
                                        }
                                    "
                                    class="cursor-grab active:cursor-grabbing transition-all duration-150">

                                    <div
                                        x-data="{ content: @js($item->content), copied: false }"
                                        class="border-2 rounded-xl overflow-hidden transition-all duration-200
                                            {{ $item->is_active
                                                ? 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800'
                                                : 'border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-800/40 opacity-70' }}">

                                        <div class="p-4">
                                            <div class="flex items-start gap-3">
                                                <!-- Drag handle -->
                                                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 flex-shrink-0 mt-0.5 cursor-grab" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                </svg>

                                                <div class="flex-1 min-w-0">
                                                    <!-- Title row -->
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ $item->title }}</h3>
                                                        @if (!$item->is_active)
                                                            <span class="text-[10px] px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded-full font-medium">Inactive</span>
                                                        @endif
                                                    </div>

                                                    <!-- Content with highlighted variables -->
                                                    <div class="text-xs text-gray-600 dark:text-gray-300 font-mono leading-relaxed bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 rounded-lg p-3 whitespace-pre-wrap break-words">
                                                        {!! preg_replace('/\{([a-zA-Z0-9_\.]+)\}/', '<span class="inline-block px-1 py-0.5 bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded font-mono text-[11px]">{\1}</span>', e($item->content)) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions bar -->
                                        <div class="flex items-center px-3 py-2 bg-gray-50 dark:bg-gray-900/20 border-t border-gray-200 dark:border-gray-700/60 gap-1.5">
                                            <!-- Copy button -->
                                            <button
                                                @click="navigator.clipboard.writeText(content).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                                draggable="false"
                                                :title="copied ? 'Copied!' : 'Copy content'"
                                                class="inline-flex items-center gap-1.5 text-[11px] font-medium px-2.5 py-1 rounded-lg transition-colors duration-150 flex-1 justify-center"
                                                :class="copied
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                                <template x-if="!copied">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                                    </svg>
                                                </template>
                                                <template x-if="copied">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </template>
                                                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                            </button>

                                            <!-- Active toggle -->
                                            <button
                                                wire:click="toggleItemActive({{ $item->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleItemActive({{ $item->id }})"
                                                draggable="false"
                                                title="{{ $item->is_active ? 'Deactivate item' : 'Activate item' }}"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg transition-colors duration-150
                                                    {{ $item->is_active
                                                        ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/50'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                                @if ($item->is_active)
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                @endif
                                            </button>

                                            <!-- Edit -->
                                            <button
                                                wire:click="openItemModal({{ $item->id }})"
                                                draggable="false"
                                                title="Edit item"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors duration-150">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>

                                            <!-- Delete -->
                                            <button
                                                wire:click="deleteItem({{ $item->id }})"
                                                wire:confirm="Delete this item permanently?"
                                                draggable="false"
                                                title="Delete item"
                                                class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-150">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            @else
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-14 text-center">
                    <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Select a Template</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Choose a template from the sidebar to manage its items</p>
                </div>
            @endif
        </div>
    </div>


    <!-- ── Template Modal ─────────────────────────────────────────────────────── -->
    <div
        x-data="{ open: @entangle('showTemplateModal') }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;">

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
            x-on:click="open = false">
        </div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $editingTemplate ? 'Edit Template' : 'New Template' }}
                </h3>
                <button wire:click="closeTemplateModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name <span class="text-red-500">*</span></label>
                    <input wire:model="templateName" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="e.g. Welcome Message"/>
                    @error('templateName')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea wire:model="templateDescription" rows="2"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                        placeholder="What is this template for?"></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="$toggle('templateIsActive')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200
                            {{ $templateIsActive ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                            style="{{ $templateIsActive ? 'transform: translateX(1.125rem)' : 'transform: translateX(0.125rem)' }}"></span>
                    </button>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 font-medium leading-none">
                            {{ $templateIsActive ? 'Active' : 'Inactive' }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $templateIsActive ? 'Visible and available for use' : 'Hidden from use' }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="closeTemplateModal"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="saveTemplate"
                    wire:loading.attr="disabled"
                    wire:target="saveTemplate"
                    class="inline-flex items-center gap-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="saveTemplate">{{ $editingTemplate ? 'Save Changes' : 'Create Template' }}</span>
                    <span wire:loading wire:target="saveTemplate" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>


    <!-- ── Item Modal ─────────────────────────────────────────────────────────── -->
    <div
        x-data="{
            open: @entangle('showItemModal'),
            insertVariable(variable) {
                const ta = this.$refs.contentArea;
                const start = ta.selectionStart;
                const end = ta.selectionEnd;
                const newVal = ta.value.substring(0, start) + variable + ta.value.substring(end);
                ta.value = newVal;
                ta.dispatchEvent(new Event('input'));
                this.$nextTick(() => {
                    ta.selectionStart = ta.selectionEnd = start + variable.length;
                    ta.focus();
                });
            }
        }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;">

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
            x-on:click="open = false">
        </div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $editingItem ? 'Edit Item' : 'Add Item' }}
                </h3>
                <button wire:click="closeItemModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Title -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input wire:model="itemTitle" type="text"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="e.g. Greeting, Investment Info..."/>
                    @error('itemTitle')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Content -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Content <span class="text-red-500">*</span></label>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">Click a variable to insert at cursor</span>
                    </div>

                    <!-- Variable insertion chips -->
                    <div class="flex flex-wrap gap-1.5 mb-2 p-2.5 bg-indigo-50/60 dark:bg-indigo-900/10 rounded-xl border border-indigo-100 dark:border-indigo-900/30">
                        @foreach (['{name}', '{riscoin_id}', '{email}', '{invested_amount}', '{age}', '{gender}', '{inviters_code}', '{primary_language}', '{secondary_language}', '{language}', '{assistant.riscoin_id}'] as $var)
                            <button type="button"
                                @click.prevent="insertVariable('{{ $var }}')"
                                class="inline-block text-[11px] font-mono px-2 py-0.5 rounded-md border bg-white dark:bg-gray-700 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 transition-colors cursor-pointer">
                                {{ $var }}
                            </button>
                        @endforeach
                    </div>

                    <textarea
                        wire:model.live="itemContent"
                        x-ref="contentArea"
                        rows="8"
                        class="w-full px-3 py-2.5 text-sm font-mono border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none leading-relaxed"
                        placeholder="Hi {name}, your Riscoin ID is {riscoin_id}..."></textarea>

                    @error('itemContent')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active toggle -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="$toggle('itemIsActive')"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200
                            {{ $itemIsActive ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200"
                            style="{{ $itemIsActive ? 'transform: translateX(1.125rem)' : 'transform: translateX(0.125rem)' }}"></span>
                    </button>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                        {{ $itemIsActive ? 'Active' : 'Inactive' }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 bg-gray-50 dark:bg-gray-900/30 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                <button wire:click="closeItemModal"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium px-4 py-2 rounded-lg transition-colors">
                    Cancel
                </button>
                <button wire:click="saveItem"
                    wire:loading.attr="disabled"
                    wire:target="saveItem"
                    class="inline-flex items-center gap-2 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2 rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="saveItem">{{ $editingItem ? 'Save Changes' : 'Add Item' }}</span>
                    <span wire:loading wire:target="saveItem" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <style>
        [draggable] { user-select: none; }
        .rt-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>

