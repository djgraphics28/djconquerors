<?php

use Livewire\Volt\Component;
use App\Models\ReplyTemplate;
use App\Models\ReplyTemplateItem;

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

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = ReplyTemplate::ordered()->get();
    }

    public function selectTemplate($templateId)
    {
        $this->selectedTemplate = ReplyTemplate::with(['items' => fn($q) => $q->orderBy('order')])->find($templateId);
        $this->templateItems = $this->selectedTemplate->items;
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
        session()->flash('message', 'Reply template deleted successfully.');
    }

    public function createItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required|min:10',
        ]);

        ReplyTemplateItem::create([
            'reply_template_id' => $this->selectedTemplate->id,
            'title' => $this->itemTitle,
            'content' => $this->itemContent,
            'order' => $this->selectedTemplate->items()->max('order') + 1,
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
        session()->flash('message', 'Template item deleted successfully.');
    }

    public function updateTemplateOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplate::where('id', $id)->update(['order' => $index]);
        }
        $this->loadTemplates();
    }

    public function updateItemOrder($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            ReplyTemplateItem::where('id', $id)->update(['order' => $index]);
        }
        $this->selectTemplate($this->selectedTemplate->id);
    }

    public function resetTemplateForm()
    {
        $this->templateName = '';
        $this->templateDescription = '';
        $this->templateIsActive = true;
        $this->editingTemplate = null;
        $this->showTemplateForm = false;
    }

    public function resetItemForm()
    {
        $this->itemTitle = '';
        $this->itemContent = '';
        $this->editingItem = null;
        $this->showItemForm = false;
    }
}; ?>

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Reply Templates') }}
            </h2>
            <button wire:click="showTemplateForm = true"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Create New Template
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('message'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Templates List -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100">
                            <h3 class="text-lg font-semibold mb-4">Templates</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Drag to reorder templates
                            </p>

                            @if($templates->isEmpty())
                                <div class="text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">No templates found.</p>
                                </div>
                            @else
                                <div id="templates-sortable" class="space-y-2">
                                    @foreach($templates as $template)
                                        <div wire:key="template-{{ $template->id }}"
                                             data-id="{{ $template->id }}"
                                             class="template-item p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-move hover:shadow-md transition-shadow {{ $selectedTemplate && $selectedTemplate->id === $template->id ? 'ring-2 ring-blue-500' : '' }}">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 cursor-pointer" wire:click="selectTemplate({{ $template->id }})">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $template->name }}</h4>
                                                    @if($template->description)
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($template->description, 50) }}</p>
                                                    @endif
                                                    <div class="flex items-center mt-1 space-x-2">
                                                        <span class="text-xs {{ $template->is_active ? 'text-green-600' : 'text-gray-500' }}">
                                                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                        <span class="text-xs text-gray-500">• {{ $template->items->count() }} items</span>
                                                    </div>
                                                </div>
                                                <div class="flex space-x-1 ml-2">
                                                    <button wire:click="editTemplate({{ $template->id }})"
                                                            class="text-blue-600 hover:text-blue-800 p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="deleteTemplate({{ $template->id }})"
                                                            wire:confirm="Are you sure you want to delete this template?"
                                                            class="text-red-600 hover:text-red-800 p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                </div>

                <!-- Template Items -->
                <div class="lg:col-span-2">
                    @if($selectedTemplate)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-gray-900 dark:text-gray-100">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ $selectedTemplate->name }} - Items</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Drag to reorder items</p>
                                    </div>
                                    <button wire:click="showItemForm = true"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Add Item
                                    </button>
                                </div>

                                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        Available variables:
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{name}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{riscoin_id}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{email}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{invested_amount}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{age}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{gender}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{inviters_code}</code>
                                        <code class="bg-white dark:bg-gray-700 px-2 py-1 rounded text-xs">{assistant.riscoin_id}</code>
                                    </p>
                                </div>

                                @if($templateItems->isEmpty())
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">No items found. Add your first item.</p>
                                    </div>
                                @else
                                    <div id="items-sortable" class="space-y-3">
                                        @foreach($templateItems as $item)
                                            <div wire:key="item-{{ $item->id }}"
                                                 data-id="{{ $item->id }}"
                                                 class="item-sortable p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-move hover:shadow-md transition-shadow">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $item->title }}</h4>
                                                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600">{{ $item->content }}</pre>
                                                    </div>
                                                    <div class="flex space-x-1 ml-4">
                                                        <button wire:click="editItem({{ $item->id }})"
                                                                class="text-blue-600 hover:text-blue-800 p-1">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                        </button>
                                                        <button wire:click="deleteItem({{ $item->id }})"
                                                                wire:confirm="Are you sure you want to delete this item?"
                                                                class="text-red-600 hover:text-red-800 p-1">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"  wire:click.stop>
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
                                    Use {name}, {riscoin_id}, {email}, {invested_amount}, {age}, {gender}, {inviters_code}, {assistant.riscoin_id}
                                </p>
                                <textarea wire:model="itemContent" rows="6"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"></textarea>
                                @error('itemContent') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', function() {
            initSortable();
        });

        document.addEventListener('DOMContentLoaded', function() {
            initSortable();
        });

        function initSortable() {
            // Templates sortable
            const templatesEl = document.getElementById('templates-sortable');
            if (templatesEl) {
                Sortable.create(templatesEl, {
                    animation: 150,
                    handle: '.template-item',
                    ghostClass: 'bg-blue-100 dark:bg-blue-900',
                    onEnd: function(evt) {
                        const items = templatesEl.querySelectorAll('.template-item');
                        const orders = Array.from(items).map(item => parseInt(item.dataset.id));
                        @this.call('updateTemplateOrder', orders);
                    }
                });
            }

            // Items sortable
            const itemsEl = document.getElementById('items-sortable');
            if (itemsEl) {
                Sortable.create(itemsEl, {
                    animation: 150,
                    handle: '.item-sortable',
                    ghostClass: 'bg-green-100 dark:bg-green-900',
                    onEnd: function(evt) {
                        const items = itemsEl.querySelectorAll('.item-sortable');
                        const orders = Array.from(items).map(item => parseInt(item.dataset.id));
                        @this.call('updateItemOrder', orders);
                    }
                });
            }
        }

        // Reinitialize on Livewire updates
        Livewire.hook('morph.updated', () => {
            initSortable();
        });
    </script>
    @endpush
</x-app-layout>
