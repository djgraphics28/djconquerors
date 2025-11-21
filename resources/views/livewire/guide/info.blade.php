<?php

use Livewire\Volt\Component;
use App\Models\Guide;
use App\Models\GuideItem;

new class extends Component {
    public $classification;
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

    public function mount()
    {
        $this->classification = request()->route('class');
        $this->loadGuides();
    }

    public function loadGuides()
    {
        $this->guides = Guide::where('classification', $this->classification)->where('is_published', true)->orderBy('order')->get();
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
        $this->guides = Guide::where('classification', $this->classification)->orderBy('order')->get();
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
            'classification' => $this->classification,
            'is_published' => $this->guideIsPublished,
            'order' => Guide::where('classification', $this->classification)->max('order') + 1,
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

    public function createItem()
    {
        $this->validate([
            'itemTitle' => 'required|min:3|max:255',
            'itemContent' => 'required|min:10',
        ]);

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
    }

    public function cancelForm()
    {
        $this->resetForm();
        $this->resetItemForm();
        $this->showGuideForm = false;
        $this->showItemForm = false;
    }
}; ?>

<div class="min-h-screen bg-white dark:bg-gray-900 transition-colors duration-300 py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white capitalize">{{ $classification }} Guides</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Comprehensive guides and tutorials</p>
            </div>

            <!-- Management Toggle -->
            <button wire:click="toggleManagement"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                @if ($managementMode)
                    <i class="fas fa-eye mr-2"></i>View Mode
                @else
                    <i class="fas fa-cog mr-2"></i>Manage Guides
                @endif
            </button>
        </div>

        <!-- Management Mode -->
        @if ($managementMode)
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8 transition-colors duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Guide Management</h2>
                    <button wire:click="$set('showGuideForm', true)"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>New Guide
                    </button>
                </div>

                <!-- Guide Form -->
                @if ($showGuideForm)
                    <div
                        class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg mb-6 border border-gray-200 dark:border-gray-600 transition-colors duration-300">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">
                            {{ $editingGuide ? 'Edit Guide' : 'Create New Guide' }}
                        </h3>
                        <div class="grid gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                                <input type="text" wire:model="guideTitle"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-200"
                                    placeholder="Enter guide title">
                                @error('guideTitle')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                <textarea wire:model="guideDescription" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-200"
                                    placeholder="Enter guide description"></textarea>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="guideIsPublished" id="guideIsPublished"
                                    class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-600 transition-colors duration-200">
                                <label for="guideIsPublished"
                                    class="ml-2 text-sm text-gray-700 dark:text-gray-300">Publish immediately</label>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="{{ $editingGuide ? 'updateGuide' : 'createGuide' }}"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                                    {{ $editingGuide ? 'Update Guide' : 'Create Guide' }}
                                </button>
                                <button wire:click="cancelForm"
                                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg transition-colors duration-200">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Guides List for Management -->
                <div class="space-y-4" x-data="{
                    draggedGuide: null,
                    reorderGuides() {
                        const orderedIds = Array.from(this.$el.children).map(child => child.getAttribute('data-id'));
                        $wire.updateGuideOrder(orderedIds);
                    }
                }">
                    @foreach ($guides as $guide)
                        <div data-id="{{ $guide->id }}"
                            class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 cursor-move hover:border-blue-300 dark:hover:border-blue-400 transition-all duration-300"
                            draggable="true" @dragstart="draggedGuide = $event.target"
                            @dragover.prevent="$event.target.classList.add('bg-blue-50', 'dark:bg-blue-900/20')"
                            @dragleave.prevent="$event.target.classList.remove('bg-blue-50', 'dark:bg-blue-900/20')"
                            @drop.prevent="
                            $event.target.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                            if (draggedGuide && draggedGuide !== $event.target) {
                                $event.target.parentNode.insertBefore(draggedGuide, $event.target.nextSibling);
                                reorderGuides();
                            }
                        ">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center space-x-4">
                                    <i class="fas fa-grip-vertical text-gray-400 dark:text-gray-500 cursor-move"></i>
                                    <div>
                                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ $guide->title }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $guide->description }}
                                        </p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span
                                                class="text-xs px-2 py-1 rounded-full {{ $guide->is_published ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' }}">
                                                {{ $guide->is_published ? 'Published' : 'Draft' }}
                                            </span>
                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400">{{ $guide->items_count ?? $guide->items->count() }}
                                                items</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button wire:click="selectGuide({{ $guide->id }})"
                                        class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-200">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="editGuide({{ $guide->id }})"
                                        class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors duration-200">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="deleteGuide({{ $guide->id }})"
                                        class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-200"
                                        onclick="return confirm('Are you sure you want to delete this guide?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="grid lg:grid-cols-4 gap-8">
            <!-- Guides Sidebar -->
            <div class="lg:col-span-1">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-4 transition-colors duration-300">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Available Guides</h2>
                    <div class="space-y-2">
                        @foreach ($guides as $guide)
                            <button wire:click="selectGuide({{ $guide->id }})"
                                class="w-full text-left p-3 rounded-lg border transition-colors duration-200 {{ $selectedGuide && $selectedGuide->id === $guide->id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-700 dark:text-blue-300' : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                <div class="font-medium">{{ $guide->title }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ $guide->description }}</div>
                                <div class="flex justify-between items-center mt-2">
                                    <span
                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $guide->items_count ?? $guide->items->count() }}
                                        items</span>
                                    @if ($managementMode)
                                        <span
                                            class="text-xs px-2 py-1 rounded-full {{ $guide->is_published ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' }}">
                                            {{ $guide->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Guide Content -->
            <div class="lg:col-span-3">
                @if ($selectedGuide)
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-300">
                        <!-- Guide Header -->
                        <div class="border-b border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                                        {{ $selectedGuide->title }}</h2>
                                    <p class="text-gray-600 dark:text-gray-400 mt-2">{{ $selectedGuide->description }}
                                    </p>
                                </div>
                                @if ($managementMode)
                                    <div class="flex space-x-2">
                                        <button wire:click="$set('showItemForm', true)"
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                            <i class="fas fa-plus mr-2"></i>Add Item
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Item Form -->
                        @if ($showItemForm && $managementMode)
                            <div
                                class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 p-6 transition-colors duration-300">
                                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">
                                    {{ $editingItem ? 'Edit Item' : 'Create New Item' }}
                                </h3>
                                <div class="grid gap-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                                        <input type="text" wire:model="itemTitle"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-200"
                                            placeholder="Enter item title">
                                        @error('itemTitle')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</label>
                                        <textarea wire:model="itemContent" rows="6"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 bg-white dark:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-200"
                                            placeholder="Enter item content"></textarea>
                                        @error('itemContent')
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="{{ $editingItem ? 'updateItem' : 'createItem' }}"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                                            {{ $editingItem ? 'Update Item' : 'Create Item' }}
                                        </button>
                                        <button wire:click="cancelForm"
                                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white rounded-lg transition-colors duration-200">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Guide Items -->
                        <div class="p-6" x-data="{
                            draggedItem: null,
                            reorderItems() {
                                const orderedIds = Array.from(this.$el.children).map(child => child.getAttribute('data-id'));
                                $wire.updateItemOrder(orderedIds);
                            }
                        }">
                            @if (count($guideItems) > 0)
                                <div class="space-y-6">
                                    @foreach ($guideItems as $item)
                                        <div data-id="{{ $item->id }}"
                                            class="border border-gray-200 dark:border-gray-600 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-500 transition-colors duration-300 {{ $managementMode ? 'cursor-move' : '' }}"
                                            @if ($managementMode) draggable="true"
                                        @dragstart="draggedItem = $event.target"
                                        @dragover.prevent="$event.target.classList.add('bg-blue-50', 'dark:bg-blue-900/20')"
                                        @dragleave.prevent="$event.target.classList.remove('bg-blue-50', 'dark:bg-blue-900/20')"
                                        @drop.prevent="
                                            $event.target.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                                            if (draggedItem && draggedItem !== $event.target) {
                                                $event.target.parentNode.insertBefore(draggedItem, $event.target.nextSibling);
                                                reorderItems();
                                            }
                                        " @endif>
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <div class="flex items-start space-x-3">
                                                        @if ($managementMode)
                                                            <i
                                                                class="fas fa-grip-vertical text-gray-400 dark:text-gray-500 cursor-move mt-1"></i>
                                                        @endif
                                                        <div class="flex-1">
                                                            <h3
                                                                class="text-xl font-semibold text-gray-800 dark:text-white mb-3">
                                                                {{ $item->title }}</h3>
                                                            <div
                                                                class="prose max-w-none text-gray-700 dark:text-gray-300">
                                                                {!! nl2br(e($item->content)) !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if ($managementMode)
                                                    <div class="flex space-x-2 ml-4">
                                                        <button wire:click="editItem({{ $item->id }})"
                                                            class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors duration-200">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button wire:click="deleteItem({{ $item->id }})"
                                                            class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors duration-200"
                                                            onclick="return confirm('Are you sure you want to delete this item?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <i class="fas fa-file-alt text-4xl text-gray-400 dark:text-gray-500 mb-4"></i>
                                    <h3 class="text-lg font-semibold text-gray-600 dark:text-gray-400">No items yet
                                    </h3>
                                    <p class="text-gray-500 dark:text-gray-400 mt-2">Start by adding some content to
                                        this guide.</p>
                                    @if ($managementMode)
                                        <button wire:click="$set('showItemForm', true)"
                                            class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                                            <i class="fas fa-plus mr-2"></i>Add First Item
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- Empty State -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center transition-colors duration-300">
                        <i class="fas fa-book-open text-4xl text-gray-400 dark:text-gray-500 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400">Select a Guide</h3>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">Choose a guide from the sidebar to view its
                            content.</p>
                        @if ($managementMode && count($guides) === 0)
                            <button wire:click="$set('showGuideForm', true)"
                                class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>Create Your First Guide
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>


    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        [draggable] {
            user-select: none;
        }

        .prose {
            color: inherit;
        }

        .prose p {
            margin-bottom: 1em;
        }

        .prose p:last-child {
            margin-bottom: 0;
        }
    </style>
</div>
