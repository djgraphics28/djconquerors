<?php

use Livewire\Volt\Component;
use App\Models\ReplyTemplate;
use App\Models\ReplyTemplateItem;
use Livewire\Attributes\On;

new class extends Component {
    public $templates = [];
    public $selectedTemplate = null;
    public $templateItems = [];

    // Management states
    public $showTemplateForm = false;
    public $showItemForm = false;
    public $editingTemplate = null;
    public $editingItem = null;

    // Form properties
    public $templateName = '';
    public $templateDescription = '';
    public $templateIsActive = true;
    public $itemTitle = '';
    public $itemContent = '';
    public $itemIsActive = true;

    // For confirmation dialogs
    public $confirmingTemplateDeletion = null;
    public $confirmingItemDeletion = null;

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = ReplyTemplate::withCount('items')->ordered()->get();
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplate = ReplyTemplate::with(['items' => fn($q) => $q->orderBy('order')])->find($templateId);
        $this->templateItems = $this->selectedTemplate ? $this->selectedTemplate->items : [];
    }

    public function createTemplate()
    {
        $this->validate([
            'templateName' => 'required|min:3|max:255',
        ]);

        $template = ReplyTemplate::create([
            'name' => $this->templateName,
            'description' => $this->templateDescription,
            'is_active' => $this->templateIsActive,
            'order' => ReplyTemplate::max('order') + 1,
        ]);

        $this->resetTemplateForm();
        $this->loadTemplates();
        $this->showTemplateForm = false;
        session()->flash('message', 'Reply template created successfully.');
    }

    public function editTemplate($templateId)
    {
        $this->editingTemplate = ReplyTemplate::find($templateId);
        $this->templateName = $this->editingTemplate->name;
        $this->templateDescription = $this->editingTemplate->description;
        $this->templateIsActive = $this->editingTemplate->is_active;
        $this->showTemplateForm = true;
    }

    public function updateTemplate()
    {
        $this->validate([
            'templateName' => 'required|min:3|max:255',
        ]);

        $this->editingTemplate->update([
            'name' => $this->templateName,
            'description' => $this->templateDescription,
            'is_active' => $this->templateIsActive,
        ]);

        $this->resetTemplateForm();
        $this->loadTemplates();
        $this->showTemplateForm = false;
        session()->flash('message', 'Reply template updated successfully.');
    }

    public function deleteTemplate($templateId)
    {
        ReplyTemplate::find($templateId)->delete();
        $this->loadTemplates();
        $this->selectedTemplate = null;
        $this->confirmingTemplateDeletion = null;
        session()->flash('message', 'Reply template deleted successfully.');
    }

    public function createItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required|min:10',
        ]);

        if (!$this->selectedTemplate) {
            session()->flash('error', 'Please select a template first.');
            return;
        }

        $maxOrder = $this->selectedTemplate->items()->max('order') ?? -1;

        ReplyTemplateItem::create([
            'reply_template_id' => $this->selectedTemplate->id,
            'title' => $this->itemTitle,
            'content' => $this->itemContent,
            'is_active' => $this->itemIsActive,
            'order' => $maxOrder + 1,
        ]);

        $this->resetItemForm();
        $this->selectTemplate($this->selectedTemplate->id);
        $this->showItemForm = false;
        session()->flash('message', 'Template item created successfully.');
    }

    public function editItem($itemId)
    {
        $this->editingItem = ReplyTemplateItem::find($itemId);
        $this->itemTitle = $this->editingItem->title;
        $this->itemContent = $this->editingItem->content;
        $this->itemIsActive = $this->editingItem->is_active;
        $this->showItemForm = true;
    }

    public function updateItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required|min:10',
        ]);

        $this->editingItem->update([
            'title' => $this->itemTitle,
            'content' => $this->itemContent,
            'is_active' => $this->itemIsActive,
        ]);

        $this->resetItemForm();
        $this->selectTemplate($this->selectedTemplate->id);
        $this->showItemForm = false;
        session()->flash('message', 'Template item updated successfully.');
    }

    public function deleteItem($itemId)
    {
        ReplyTemplateItem::find($itemId)->delete();
        $this->selectTemplate($this->selectedTemplate->id);
        $this->confirmingItemDeletion = null;
        session()->flash('message', 'Template item deleted successfully.');
    }

    #[On('templateOrderUpdated')]
    public function updateTemplateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplate::where('id', $id)->update(['order' => $index]);
        }
        $this->loadTemplates();
    }

    #[On('itemOrderUpdated')]
    public function updateItemOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplateItem::where('id', $id)->update(['order' => $index]);
        }
        $this->selectTemplate($this->selectedTemplate->id);
    }

    public function resetTemplateForm()
    {
        $this->reset(['templateName', 'templateDescription', 'editingTemplate']);
        $this->templateIsActive = true;
        $this->showTemplateForm = false;
    }

    public function resetItemForm()
    {
        $this->reset(['itemTitle', 'itemContent', 'editingItem']);
        $this->itemIsActive = true;
        $this->showItemForm = false;
    }

    public function toggleTemplateActive($templateId)
    {
        $template = ReplyTemplate::find($templateId);
        $template->update(['is_active' => !$template->is_active]);
        $this->loadTemplates();

        if ($this->selectedTemplate && $this->selectedTemplate->id === $templateId) {
            $this->selectedTemplate = ReplyTemplate::with(['items' => fn($q) => $q->orderBy('order')])->find($templateId);
        }

        session()->flash('message', 'Template status updated successfully.');
    }

    public function toggleItemActive($itemId)
    {
        $item = ReplyTemplateItem::find($itemId);
        $item->update(['is_active' => !$item->is_active]);
        $this->selectTemplate($this->selectedTemplate->id);
        session()->flash('message', 'Item status updated successfully.');
    }

    public function addItem()
    {
        if (!$this->selectedTemplate) {
            session()->flash('error', 'Please select a template first.');
            return;
        }
        $this->resetItemForm();
        $this->showItemForm = true;
    }

    public function confirmTemplateDeletion($templateId)
    {
        $this->confirmingTemplateDeletion = $templateId;
    }

    public function confirmItemDeletion($itemId)
    {
        $this->confirmingItemDeletion = $itemId;
    }

    public function cancelDeletion()
    {
        $this->confirmingTemplateDeletion = null;
        $this->confirmingItemDeletion = null;
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Reply Templates
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage your reply templates and template items
                </p>
            </div>
            <button type="button" wire:click="$set('showTemplateForm', true)"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-150">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create New Template
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('message'))
                <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg flex items-center" role="alert">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 px-4 py-3 rounded-lg flex items-center" role="alert">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Templates List -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        Templates
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $templates->count() }} total
                                    </p>
                                </div>
                            </div>
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                                <p class="text-xs text-blue-700 dark:text-blue-300 flex items-start">
                                    <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Drag templates to reorder. Use the toggle to enable/disable.</span>
                                </p>
                            </div>

                            @if($templates->isEmpty())
                                <div class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">No templates found.</p>
                                </div>
                            @else
                                <div x-data="{
                                    init() {
                                        const el = this.$el
                                        new Sortable(el, {
                                            animation: 150,
                                            handle: '.template-drag-handle',
                                            ghostClass: 'bg-blue-100 dark:bg-blue-900',
                                            onEnd: (evt) => {
                                                const items = el.querySelectorAll('.template-item')
                                                const orders = Array.from(items).map(item => parseInt(item.dataset.id))
                                                @this.call('updateTemplateOrder', orders)
                                            }
                                        })
                                    }
                                }" id="templates-sortable" class="space-y-2">
                                    @foreach($templates as $template)
                                        <div wire:key="template-{{ $template->id }}"
                                             data-id="{{ $template->id }}"
                                             class="template-item p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-all duration-200 {{ $selectedTemplate && $selectedTemplate->id === $template->id ? 'ring-2 ring-blue-500 shadow-md' : '' }} {{ !$template->is_active ? 'opacity-60' : '' }}">
                                            <div class="flex items-start gap-3">
                                                <!-- Drag Handle -->
                                                <div class="template-drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-1">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                    </svg>
                                                </div>

                                                <!-- Content -->
                                                <div class="flex-1 cursor-pointer min-w-0" wire:click="selectTemplate({{ $template->id }})">
                                                    <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $template->name }}</h4>
                                                    @if($template->description)
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $template->description }}</p>
                                                    @endif
                                                    <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        {{ $template->items_count ?? $template->items->count() }} items
                                                    </div>
                                                </div>

                                                <!-- Actions -->
                                                <div class="flex flex-col items-end gap-2">
                                                    <!-- Toggle Switch -->
                                                    <button type="button"
                                                            wire:click="toggleTemplateActive({{ $template->id }})"
                                                            class="relative inline-flex flex-shrink-0 h-5 w-9 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $template->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                            title="{{ $template->is_active ? 'Active - Click to disable' : 'Inactive - Click to enable' }}">
                                                        <span class="pointer-events-none relative inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $template->is_active ? 'translate-x-4' : 'translate-x-0' }}">
                                                            <span class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity {{ $template->is_active ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }}" aria-hidden="true">
                                                                <svg class="h-3 w-3 text-green-500" fill="currentColor" viewBox="0 0 12 12">
                                                                    <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                                                </svg>
                                                            </span>
                                                            <span class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity {{ $template->is_active ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }}" aria-hidden="true">
                                                                <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                                                    <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </span>
                                                        </span>
                                                    </button>

                                                    <!-- Action Buttons -->
                                                    <div class="flex gap-1">
                                                        <button type="button"
                                                                wire:click="editTemplate({{ $template->id }})"
                                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors"
                                                                title="Edit">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </button>
                                                        <button type="button"
                                                                wire:click="confirmTemplateDeletion({{ $template->id }})"
                                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                                                title="Delete">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Template Items -->
                <div class="lg:col-span-2">
                    @if($selectedTemplate)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            {{ $selectedTemplate->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $templateItems->count() }} items • Drag to reorder
                                        </p>
                                    </div>
                                    <button type="button" wire:click="addItem"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Add Item
                                    </button>
                                </div>

                                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <p class="text-sm text-blue-800 dark:text-blue-200 font-medium mb-2">
                                        Create items with static text and/or dynamic variables:
                                    </p>
                                    <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                                        <p><strong>Static text:</strong> "Language: English, Tagalog" or "Nationality: Filipino"</p>
                                        <p><strong>Dynamic variables:</strong>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{name}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{riscoin_id}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{email}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{invested_amount}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{age}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{gender}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{inviters_code}</code>
                                            <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded">{assistant.riscoin_id}</code>
                                        </p>
                                        <p><strong>Mixed:</strong> "Your ID: {riscoin_id}" or "Deposit: ${invested_amount}"</p>
                                    </div>
                                </div>

                                @if($templateItems->isEmpty())
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">No items found. Add your first item.</p>
                                    </div>
                                @else
                                    <div x-data="{
                                        init() {
                                            const el = this.$el
                                            new Sortable(el, {
                                                animation: 150,
                                                handle: '.item-drag-handle',
                                                ghostClass: 'bg-green-100 dark:bg-green-900',
                                                onEnd: (evt) => {
                                                    const items = el.querySelectorAll('.item-sortable')
                                                    const orders = Array.from(items).map(item => parseInt(item.dataset.id))
                                                    @this.call('updateItemOrder', orders)
                                                }
                                            })
                                        }
                                    }" id="items-sortable" class="space-y-3">
                                        @foreach($templateItems as $item)
                                            <div wire:key="item-{{ $item->id }}"
                                                 data-id="{{ $item->id }}"
                                                 class="item-sortable p-4 rounded-lg hover:shadow-md transition-all duration-200 {{ $item->is_active ? 'bg-gray-50 dark:bg-gray-700' : 'bg-gray-200 dark:bg-gray-800 opacity-60' }}">
                                                <div class="flex items-start gap-3">
                                                    <!-- Drag Handle -->
                                                    <div class="item-drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-1">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                        </svg>
                                                    </div>

                                                    <!-- Content -->
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $item->title }}</h4>
                                                            @if($item->is_active)
                                                                <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs font-medium px-2 py-0.5 rounded">Active</span>
                                                            @else
                                                                <span class="bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded">Inactive</span>
                                                            @endif
                                                        </div>
                                                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600">{{ $item->content }}</pre>
                                                    </div>

                                                    <!-- Actions -->
                                                    <div class="flex flex-col items-end gap-2">
                                                        <!-- Toggle Switch -->
                                                        <button type="button"
                                                                wire:click="toggleItemActive({{ $item->id }})"
                                                                class="relative inline-flex flex-shrink-0 h-5 w-9 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 {{ $item->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                                title="{{ $item->is_active ? 'Active - Click to disable' : 'Inactive - Click to enable' }}">
                                                            <span class="pointer-events-none relative inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $item->is_active ? 'translate-x-4' : 'translate-x-0' }}">
                                                                <span class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity {{ $item->is_active ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }}" aria-hidden="true">
                                                                    <svg class="h-3 w-3 text-green-500" fill="currentColor" viewBox="0 0 12 12">
                                                                        <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                                                    </svg>
                                                                </span>
                                                                <span class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity {{ $item->is_active ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }}" aria-hidden="true">
                                                                    <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                                                        <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </span>
                                                            </span>
                                                        </button>

                                                        <!-- Action Buttons -->
                                                        <div class="flex gap-1">
                                                            <button type="button"
                                                                    wire:click="editItem({{ $item->id }})"
                                                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors"
                                                                    title="Edit">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                            <button type="button"
                                                                    wire:click="confirmItemDeletion({{ $item->id }})"
                                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                                                    title="Delete">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-4 text-gray-500 dark:text-gray-400">Select a template to view and manage its items</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Template Form Modal -->
    @if($showTemplateForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="resetTemplateForm"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
                            {{ $editingTemplate ? 'Edit Template' : 'Create Template' }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Name</label>
                                <input type="text" wire:model="templateName"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('templateName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea wire:model="templateDescription" rows="2"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="templateIsActive"
                                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="{{ $editingTemplate ? 'updateTemplate' : 'createTemplate' }}"
                                class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                            {{ $editingTemplate ? 'Update' : 'Create' }}
                        </button>
                        <button type="button" wire:click="resetTemplateForm"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Item Form Modal -->
    @if($showItemForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="resetItemForm"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl" wire:click.stop>
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
                            {{ $editingItem ? 'Edit Item' : 'Create Item' }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Title</label>
                                <input type="text" wire:model="itemTitle"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('itemTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Content</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    Mix static text with variables. Use {name}, {riscoin_id}, {email}, {invested_amount}, {age}, {gender}, {inviters_code}, {assistant.riscoin_id}
                                </p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">
                                    Example: "Language: English, Tagalog" or "Your ID: {riscoin_id}"
                                </p>
                                <textarea wire:model="itemContent" rows="6"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"></textarea>
                                @error('itemContent') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="itemIsActive"
                                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active (include in rendered output)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="{{ $editingItem ? 'updateItem' : 'createItem' }}"
                                class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto">
                            {{ $editingItem ? 'Update' : 'Create' }}
                        </button>
                        <button type="button" wire:click="resetItemForm"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Confirmation Modal for Template Deletion -->
    @if($confirmingTemplateDeletion)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="cancelDeletion"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.896-.833-2.666 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Delete Template</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this template? This action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="deleteTemplate({{ $confirmingTemplateDeletion }})"
                                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Delete
                        </button>
                        <button type="button" wire:click="cancelDeletion"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Confirmation Modal for Item Deletion -->
    @if($confirmingItemDeletion)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="cancelDeletion"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <div class="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.896-.833-2.666 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Delete Item</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this item? This action cannot be undone.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" wire:click="deleteItem({{ $confirmingItemDeletion }})"
                                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Delete
                        </button>
                        <button type="button" wire:click="cancelDeletion"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Reinitialize Sortable when Livewire updates
        document.addEventListener('livewire:load', function() {
            initSortable();
        });

        document.addEventListener('livewire:navigated', function() {
            initSortable();
        });

        function initSortable() {
            // Templates sortable
            const templatesEl = document.getElementById('templates-sortable');
            if (templatesEl && !templatesEl.sortable) {
                Sortable.create(templatesEl, {
                    animation: 150,
                    handle: '.template-drag-handle',
                    ghostClass: 'bg-blue-100 dark:bg-blue-900',
                    onEnd: function(evt) {
                        const items = templatesEl.querySelectorAll('.template-item');
                        const orders = Array.from(items).map(item => parseInt(item.dataset.id));
                        Livewire.dispatch('templateOrderUpdated', { orderedIds: orders });
                    }
                });
            }

            // Items sortable
            const itemsEl = document.getElementById('items-sortable');
            if (itemsEl && !itemsEl.sortable) {
                Sortable.create(itemsEl, {
                    animation: 150,
                    handle: '.item-drag-handle',
                    ghostClass: 'bg-green-100 dark:bg-green-900',
                    onEnd: function(evt) {
                        const items = itemsEl.querySelectorAll('.item-sortable');
                        const orders = Array.from(items).map(item => parseInt(item.dataset.id));
                        Livewire.dispatch('itemOrderUpdated', { orderedIds: orders });
                    }
                });
            }
        }

        // Reinitialize on Livewire updates
        Livewire.hook('morph.updated', () => {
            setTimeout(initSortable, 50);
        });
    </script>
    @endpush
</div>
