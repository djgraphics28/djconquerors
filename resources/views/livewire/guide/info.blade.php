<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Models\Guide;
use App\Models\GuideItem;
use App\Models\GuideOption;

new class extends Component {
    use WithFileUploads;

    public $classification;
    public $optionName = '';
    public $guides = [];
    public $selectedGuide = null;
    public $guideItems = [];

    // Management states
    public $managementMode = false;
    public $showGuideForm = false;
    public $showItemForm = false;
    public $editingGuide = null;
    public $editingItem = null;

    // Form properties
    public $guideTitle = '';
    public $guideDescription = '';
    public $guideIsPublished = false;
    public $itemTitle = '';
    public $itemContent = '';
    public $editorImage = null;

    public function mount()
    {
        $this->classification = request()->route('class');
        $option = GuideOption::find($this->classification);
        $this->optionName = $option ? $option->name : '';
        $this->loadGuides();
    }

    public function loadGuides()
    {
        $this->guides = Guide::where('guide_option_id', $this->classification)->where('is_published', true)->orderBy('order')->get();
    }

    public function selectGuide($guideId)
    {
        $this->selectedGuide = Guide::with(['items' => fn($q) => $q->orderBy('order')])->find($guideId);
        $this->guideItems = $this->selectedGuide->items;
    }

    // Management Methods
    public function toggleManagement()
    {
        $this->managementMode = !$this->managementMode;
        if ($this->managementMode) {
            $this->loadAllGuides();
        } else {
            $this->loadGuides();
        }
    }

    public function loadAllGuides()
    {
        $this->guides = Guide::where('guide_option_id', $this->classification)->orderBy('order')->get();
    }

    public function createGuide()
    {
        $this->validate([
            'guideTitle' => 'required|min:3|max:255',
        ]);

        $guide = Guide::create([
            'title' => $this->guideTitle,
            'slug' => \Illuminate\Support\Str::slug($this->guideTitle),
            'description' => $this->guideDescription,
            'guide_option_id' => $this->classification,
            'is_published' => $this->guideIsPublished,
            'order' => Guide::where('guide_option_id', $this->classification)->max('order') + 1,
        ]);

        $this->resetForm();
        $this->loadAllGuides();
        $this->showGuideForm = false;
    }

    public function editGuide($guideId)
    {
        $this->editingGuide = Guide::find($guideId);
        $this->guideTitle = $this->editingGuide->title;
        $this->guideDescription = $this->editingGuide->description;
        $this->guideIsPublished = $this->editingGuide->is_published;
        $this->showGuideForm = true;
    }

    public function updateGuide()
    {
        $this->validate([
            'guideTitle' => 'required|min:3|max:255',
        ]);

        $this->editingGuide->update([
            'title' => $this->guideTitle,
            'slug' => \Illuminate\Support\Str::slug($this->guideTitle),
            'description' => $this->guideDescription,
            'is_published' => $this->guideIsPublished,
        ]);

        $this->resetForm();
        $this->loadAllGuides();
        $this->showGuideForm = false;
    }

    public function deleteGuide($guideId)
    {
        Guide::find($guideId)->delete();
        $this->loadAllGuides();
        $this->selectedGuide = null;
    }

    public function updatedEditorImage(): void
    {
        if ($this->editorImage) {
            $path = $this->editorImage->store('guide-images', 'public');
            $url = asset(Storage::url($path));
            $this->dispatch('editor-image-ready', url: $url);
            $this->editorImage = null;
        }
    }

    public function openItemForm(): void
    {
        $this->resetItemForm();
        $this->showItemForm = true;
        $this->dispatch('load-editor-content', content: '');
    }

    public function createItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required',
        ]);

        if (empty(trim(strip_tags($this->itemContent)))) {
            $this->addError('itemContent', 'Content cannot be empty.');
            return;
        }

        GuideItem::create([
            'guide_id' => $this->selectedGuide->id,
            'title' => $this->itemTitle,
            'content' => $this->itemContent,
            'order' => $this->selectedGuide->items()->max('order') + 1,
        ]);

        $this->resetItemForm();
        $this->selectGuide($this->selectedGuide->id);
        $this->showItemForm = false;
    }

    public function editItem($itemId)
    {
        $this->editingItem = GuideItem::find($itemId);
        $this->itemTitle = $this->editingItem->title;
        $this->itemContent = $this->editingItem->content ?? '';
        $this->showItemForm = true;
        $this->dispatch('load-editor-content', content: $this->itemContent);
    }

    public function updateItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required',
        ]);

        if (empty(trim(strip_tags($this->itemContent)))) {
            $this->addError('itemContent', 'Content cannot be empty.');
            return;
        }

        $this->editingItem->update([
            'title' => $this->itemTitle,
            'content' => $this->itemContent,
        ]);

        $this->resetItemForm();
        $this->selectGuide($this->selectedGuide->id);
        $this->showItemForm = false;
    }

    public function deleteItem($itemId)
    {
        GuideItem::find($itemId)->delete();
        $this->selectGuide($this->selectedGuide->id);
    }

    // Draggable Sorting Methods
    public function updateGuideOrder($orderedIds)
    {
        foreach ($orderedIds as $order => $id) {
            Guide::where('id', $id)->update(['order' => $order]);
        }
        $this->loadAllGuides();
    }

    public function updateItemOrder($orderedIds)
    {
        foreach ($orderedIds as $order => $id) {
            GuideItem::where('id', $id)->update(['order' => $order]);
        }
        $this->selectGuide($this->selectedGuide->id);
    }

    // Helper Methods
    public function resetForm()
    {
        $this->guideTitle = '';
        $this->guideDescription = '';
        $this->guideIsPublished = false;
        $this->editingGuide = null;
    }

    public function resetItemForm()
    {
        $this->itemTitle = '';
        $this->itemContent = '';
        $this->editingItem = null;
        $this->editorImage = null;
    }

    public function cancelForm()
    {
        $this->resetForm();
        $this->resetItemForm();
        $this->showGuideForm = false;
        $this->showItemForm = false;
    }
}; ?>

<div>
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header Card -->
        <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 rounded-2xl p-5 mb-5 text-white shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold capitalize">{{ $optionName }} Guides</h1>
                        <p class="text-indigo-200 text-sm">Comprehensive guides and tutorials</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('guide.index') }}"
                        class="inline-flex items-center gap-1.5 text-xs bg-white/20 hover:bg-white/30 text-white px-3 py-1.5 rounded-lg transition-colors duration-200">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </a>
                    <button wire:click="toggleManagement"
                        class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg transition-colors duration-200 font-medium
                            {{ $managementMode ? 'bg-white text-indigo-700' : 'bg-white/20 hover:bg-white/30 text-white' }}">
                        @if ($managementMode)
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View Mode
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Manage Guides
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <!-- Management Mode -->
        @if ($managementMode)
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-5">
                <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Guide Management</h2>
                    </div>
                    <button wire:click="$set('showGuideForm', true)"
                        class="inline-flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors duration-200 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Guide
                    </button>
                </div>

                <!-- Guide Form -->
                @if ($showGuideForm)
                    <div class="p-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-4">
                            {{ $editingGuide ? 'Edit Guide' : 'Create New Guide' }}
                        </h3>
                        <div class="grid gap-4">
                            <div>
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Title</label>
                                <input type="text" wire:model="guideTitle"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 text-sm transition-colors duration-200"
                                    placeholder="Enter guide title">
                                @error('guideTitle')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Description</label>
                                <textarea wire:model="guideDescription" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 text-sm transition-colors duration-200"
                                    placeholder="Enter guide description"></textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" wire:model="guideIsPublished" id="guideIsPublished"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                                <label for="guideIsPublished" class="text-sm text-gray-700 dark:text-gray-300">Publish immediately</label>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="{{ $editingGuide ? 'updateGuide' : 'createGuide' }}"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors duration-200">
                                    {{ $editingGuide ? 'Update Guide' : 'Create Guide' }}
                                </button>
                                <button wire:click="cancelForm"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-colors duration-200">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Guides List for Management -->
                <div class="p-5 space-y-3" x-data="{
                    draggedGuide: null,
                    reorderGuides() {
                        const orderedIds = Array.from(this.$el.children).map(child => child.getAttribute('data-id'));
                        $wire.updateGuideOrder(orderedIds);
                    }
                }">
                    @foreach ($guides as $guide)
                        <div data-id="{{ $guide->id }}"
                            class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl p-4 cursor-move hover:border-indigo-300 dark:hover:border-indigo-500 transition-all duration-200"
                            draggable="true" @dragstart="draggedGuide = $event.target"
                            @dragover.prevent="$event.target.classList.add('bg-indigo-50')"
                            @dragleave.prevent="$event.target.classList.remove('bg-indigo-50')"
                            @drop.prevent="
                            $event.target.classList.remove('bg-indigo-50');
                            if (draggedGuide && draggedGuide !== $event.target) {
                                $event.target.parentNode.insertBefore(draggedGuide, $event.target.nextSibling);
                                reorderGuides();
                            }
                        ">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 cursor-move flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                    </svg>
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ $guide->title }}</h3>
                                        @if($guide->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $guide->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-3 mt-1.5">
                                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                                {{ $guide->is_published
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                    : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' }}">
                                                {{ $guide->is_published ? 'Published' : 'Draft' }}
                                            </span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $guide->items_count ?? $guide->items->count() }} items</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1.5">
                                    <button wire:click="selectGuide({{ $guide->id }})"
                                        class="p-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors duration-200"
                                        title="View">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click="editGuide({{ $guide->id }})"
                                        class="p-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors duration-200"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="deleteGuide({{ $guide->id }})"
                                        class="p-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors duration-200"
                                        title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this guide?')">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="grid lg:grid-cols-4 gap-5">
            <!-- Guides Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden sticky top-4">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Available Guides</h2>
                    </div>
                    <div class="p-3 space-y-1.5">
                        @forelse ($guides as $guide)
                            <button wire:click="selectGuide({{ $guide->id }})"
                                class="w-full text-left px-3 py-2.5 rounded-xl border transition-colors duration-200 text-sm
                                    {{ $selectedGuide && $selectedGuide->id === $guide->id
                                        ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300'
                                        : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                <div class="font-medium truncate">{{ $guide->title }}</div>
                                @if($guide->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $guide->description }}</div>
                                @endif
                                <div class="flex justify-between items-center mt-1.5">
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $guide->items_count ?? $guide->items->count() }} items</span>
                                    @if ($managementMode)
                                        <span class="text-xs px-1.5 py-0.5 rounded-full
                                            {{ $guide->is_published
                                                ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                                                : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' }}">
                                            {{ $guide->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-500 dark:text-gray-400">No guides yet</p>
                                @if ($managementMode)
                                    <button wire:click="$set('showGuideForm', true)"
                                        class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Create one</button>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Guide Content -->
            <div class="lg:col-span-3">
                @if ($selectedGuide)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <!-- Guide Header -->
                        <div class="border-b border-gray-200 dark:border-gray-700 px-5 py-4 bg-gray-50 dark:bg-gray-700/50">
                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <h2 class="text-base font-bold text-gray-800 dark:text-white">{{ $selectedGuide->title }}</h2>
                                    @if($selectedGuide->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $selectedGuide->description }}</p>
                                    @endif
                                </div>
                                @if ($managementMode)
                                    <button wire:click="openItemForm"
                                        class="inline-flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors duration-200 font-medium flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Item
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Item Form -->
                        @if ($showItemForm && $managementMode)
                            <div class="border-b border-gray-200 dark:border-gray-700 p-5 bg-gray-50 dark:bg-gray-700/50">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-4">
                                    {{ $editingItem ? 'Edit Item' : 'Create New Item' }}
                                </h3>
                                <div class="grid gap-4">
                                    <div>
                                        <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Title</label>
                                        <input type="text" wire:model="itemTitle"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-400 text-sm"
                                            placeholder="Enter item title">
                                        @error('itemTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Content</label>

                                        <!-- WYSIWYG Editor (wire:ignore prevents Livewire from touching Quill's DOM) -->
                                        <div
                                            x-data="{
                                                editor: null,
                                                uploading: false,
                                                init() {
                                                    const self = this;
                                                    this.editor = new Quill(this.$refs.editorBody, {
                                                        theme: 'snow',
                                                        modules: {
                                                            toolbar: {
                                                                container: [
                                                                    [{ header: [1, 2, 3, false] }],
                                                                    ['bold', 'italic', 'underline', 'strike'],
                                                                    [{ color: [] }, { background: [] }],
                                                                    ['blockquote', 'code-block'],
                                                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                                                    [{ indent: '-1' }, { indent: '+1' }],
                                                                    ['link', 'image', 'video'],
                                                                    ['clean']
                                                                ],
                                                                handlers: {
                                                                    image() {
                                                                        document.getElementById('quill-image-picker').click();
                                                                    },
                                                                    video() {
                                                                        const url = prompt('Paste YouTube or Vimeo URL:');
                                                                        if (!url) return;
                                                                        let embed = url;
                                                                        const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/);
                                                                        if (yt) embed = 'https://www.youtube.com/embed/' + yt[1];
                                                                        const vi = url.match(/vimeo\.com\/(\d+)/);
                                                                        if (vi) embed = 'https://player.vimeo.com/video/' + vi[1];
                                                                        const range = self.editor.getSelection(true) || { index: 0 };
                                                                        self.editor.insertEmbed(range.index, 'video', embed, 'user');
                                                                        self.editor.setSelection(range.index + 1);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    });

                                                    // Set initial content at mount
                                                    this.editor.root.innerHTML = @js($itemContent) || '';

                                                    // Sync content to Livewire on every change (no server roundtrip)
                                                    this.editor.on('text-change', () => {
                                                        $wire.set('itemContent', self.editor.root.innerHTML, false);
                                                    });

                                                    // When editing an already-open form item changes (server dispatches)
                                                    window.addEventListener('load-editor-content', (e) => {
                                                        if (self.editor) {
                                                            self.editor.root.innerHTML = e.detail.content || '';
                                                        }
                                                    });

                                                    // Insert uploaded image URL at cursor position
                                                    window.addEventListener('editor-image-ready', (e) => {
                                                        const range = self.editor.getSelection(true) || { index: 0 };
                                                        self.editor.insertEmbed(range.index, 'image', e.detail.url, 'user');
                                                        self.editor.setSelection(range.index + 1);
                                                        self.uploading = false;
                                                    });
                                                }
                                            }"
                                            wire:ignore
                                        >
                                            <div class="rounded-xl overflow-hidden border border-gray-300 dark:border-gray-600">
                                                <div x-ref="editorBody" class="bg-white min-h-[220px] text-gray-900 dark:bg-gray-800 dark:text-gray-100"></div>
                                            </div>

                                            <!-- Upload progress -->
                                            <div x-show="uploading" class="mt-1.5 flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                                </svg>
                                                Uploading image...
                                            </div>

                                            <!-- Hidden file input for image upload -->
                                            <input
                                                type="file"
                                                id="quill-image-picker"
                                                accept="image/*"
                                                class="hidden"
                                                x-on:change="
                                                    const file = $event.target.files[0];
                                                    if (file) {
                                                        uploading = true;
                                                        $wire.upload('editorImage', file,
                                                            () => {},
                                                            () => { uploading = false; }
                                                        );
                                                    }
                                                    $event.target.value = '';
                                                "
                                            />
                                        </div>

                                        @error('itemContent') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="{{ $editingItem ? 'updateItem' : 'createItem' }}"
                                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition-colors duration-200">
                                            {{ $editingItem ? 'Update Item' : 'Create Item' }}
                                        </button>
                                        <button wire:click="cancelForm"
                                            class="px-4 py-2 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-colors duration-200">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Guide Items -->
                        <div class="p-5" x-data="{
                            draggedItem: null,
                            reorderItems() {
                                const orderedIds = Array.from(this.$el.children).map(child => child.getAttribute('data-id'));
                                $wire.updateItemOrder(orderedIds);
                            }
                        }">
                            @if (count($guideItems) > 0)
                                <div class="space-y-4">
                                    @foreach ($guideItems as $item)
                                        <div data-id="{{ $item->id }}"
                                            class="border border-gray-200 dark:border-gray-600 rounded-xl p-5 hover:border-gray-300 dark:hover:border-gray-500 transition-colors duration-200 {{ $managementMode ? 'cursor-move' : '' }}"
                                            @if ($managementMode)
                                                draggable="true"
                                                @dragstart="draggedItem = $event.target"
                                                @dragover.prevent="$event.target.classList.add('bg-indigo-50')"
                                                @dragleave.prevent="$event.target.classList.remove('bg-indigo-50')"
                                                @drop.prevent="
                                                    $event.target.classList.remove('bg-indigo-50');
                                                    if (draggedItem && draggedItem !== $event.target) {
                                                        $event.target.parentNode.insertBefore(draggedItem, $event.target.nextSibling);
                                                        reorderItems();
                                                    }
                                                "
                                            @endif>
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <div class="flex items-start gap-3">
                                                        @if ($managementMode)
                                                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 cursor-move mt-1 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                            </svg>
                                                        @endif
                                                        <div class="flex-1">
                                                            <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-2">{{ $item->title }}</h3>
                                                            <div class="guide-content text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                                                {!! $item->content !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($managementMode)
                                                    <div class="flex gap-1.5 ml-3 flex-shrink-0">
                                                        <button wire:click="editItem({{ $item->id }})"
                                                            class="p-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors duration-200"
                                                            title="Edit">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                        <button wire:click="deleteItem({{ $item->id }})"
                                                            class="p-1.5 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors duration-200"
                                                            title="Delete"
                                                            onclick="return confirm('Are you sure you want to delete this item?')">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400">No items yet</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start by adding some content to this guide.</p>
                                    @if ($managementMode)
                                        <button wire:click="openItemForm"
                                            class="mt-3 inline-flex items-center gap-1.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg transition-colors duration-200 font-medium">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Add First Item
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-12 text-center">
                        <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300">Select a Guide</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose a guide from the sidebar to view its content.</p>
                        @if ($managementMode && count($guides) === 0)
                            <button wire:click="$set('showGuideForm', true)"
                                class="mt-4 inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl transition-colors duration-200 font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Your First Guide
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        [draggable] { user-select: none; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Quill editor dark mode overrides */
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

        /* Guide content display styles */
        .guide-content h1 { font-size: 1.25rem; font-weight: 700; margin: 0.75em 0 0.3em; }
        .guide-content h2 { font-size: 1.1rem; font-weight: 700; margin: 0.7em 0 0.3em; }
        .guide-content h3 { font-size: 1rem; font-weight: 600; margin: 0.6em 0 0.25em; }
        .guide-content p { margin: 0.4em 0; }
        .guide-content ul { list-style-type: disc; padding-left: 1.5em; margin: 0.4em 0; }
        .guide-content ol { list-style-type: decimal; padding-left: 1.5em; margin: 0.4em 0; }
        .guide-content li { margin: 0.2em 0; }
        .guide-content strong { font-weight: 700; }
        .guide-content em { font-style: italic; }
        .guide-content s { text-decoration: line-through; }
        .guide-content a { color: #6366f1; text-decoration: underline; }
        .guide-content blockquote { border-left: 3px solid #6366f1; padding-left: 1em; color: #6b7280; margin: 0.5em 0; }
        .guide-content pre { background: #1e293b; color: #e2e8f0; padding: 0.75em 1em; border-radius: 0.5em; overflow-x: auto; font-size: 0.8rem; margin: 0.5em 0; }
        .guide-content code { background: #f1f5f9; padding: 0.1em 0.35em; border-radius: 0.25em; font-size: 0.85em; }
        .guide-content pre code { background: transparent; padding: 0; }
        .guide-content img { max-width: 100%; border-radius: 0.5em; margin: 0.4em 0; display: block; }
        .guide-content iframe { max-width: 100%; border-radius: 0.5em; margin: 0.5em 0; aspect-ratio: 16/9; width: 100%; }
        .dark .guide-content blockquote { color: #9ca3af; }
        .dark .guide-content code { background: #374151; }
        .dark .guide-content a { color: #818cf8; }
    </style>
</div>

@assets
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
@endassets
