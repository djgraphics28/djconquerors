<?php

use Livewire\Volt\Component;
use App\Models\Manager;

new class extends Component {
    public $message;

    // Build levels from managers_table (only records present in managers_table)
    public function getLevelsProperty()
    {
        $managers = Manager::with('user')->get();
        $levels = collect([1 => collect(), 2 => collect(), 3 => collect(), 4 => collect(), 5 => collect(), 6 => collect()]);

        foreach ($managers as $manager) {
            $level = intval($manager->level) ?: 1;
            $level = min(max($level, 1), 6);
            $levels[$level]->push($manager);
        }

        return $levels;
    }

    // Update manager.level when card is dropped into a new column
    public function reassignUser($managerId, $targetLevel, $currentLevel = null)
    {
        $manager = Manager::find($managerId);
        if (!$manager) {
            $this->dispatch('show-message', type: 'error', text: 'Manager record not found.');
            return;
        }

        $level = intval($targetLevel);
        if ($level < 1 || $level > 6) {
            $this->dispatch('show-message', type: 'error', text: 'Invalid level.');
            return;
        }

        // If currentLevel is provided, check if it's the same as targetLevel
        if ($currentLevel !== null && intval($currentLevel) === $level) {
            $this->dispatch('show-message', type: 'info', text: 'Manager is already at this level.');
            return;
        }

        $manager->level = $level;
        $manager->save();

        $this->dispatch('show-message', type: 'success', text: 'Manager level updated successfully.');
    }

    // Delete manager from the managers table
    public function deleteManager($managerId)
    {
        $manager = Manager::find($managerId);
        if (!$manager) {
            $this->dispatch('show-message', type: 'error', text: 'Manager record not found.');
            return;
        }

        $userName = $manager->user?->name ?? 'Unknown';
        $manager->delete();

        $this->dispatch('show-message', type: 'success', text: "Manager '{$userName}' removed successfully.");
    }
}; ?>

<div class="max-w-10xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">DJ Conquerors Managers</h2>
        <p class="text-sm text-gray-500">Drag user chips between levels to reassign (admin only).</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">{{ session('error') }}</div>
    @endif

    <div x-data="{
        showMessage: false,
        messageText: '',
        messageType: '',
        draggedFromLevel: null,
        showConfirmModal: false,
        confirmData: null,
        showToast(type, text) {
            this.messageType = type;
            this.messageText = text;
            this.showMessage = true;
            setTimeout(() => this.showMessage = false, 3000);
        },
        showConfirm(managerId, currentLevel, targetLevel, userName) {
            this.confirmData = {
                managerId: managerId,
                currentLevel: currentLevel,
                targetLevel: targetLevel,
                userName: userName
            };
            this.showConfirmModal = true;
        },
        confirmMove() {
            if (this.confirmData) {
                @this.reassignUser(
                    parseInt(this.confirmData.managerId),
                    this.confirmData.targetLevel,
                    this.confirmData.currentLevel
                );
            }
            this.showConfirmModal = false;
            this.confirmData = null;
        },
        cancelMove() {
            this.showConfirmModal = false;
            this.confirmData = null;
        }
    }" x-init="
        $watch('showMessage', (value) => {
            if (value) {
                setTimeout(() => showMessage = false, 3000);
            }
        });
        Livewire.on('show-message', (data) => {
            showToast(data.type, data.text);
        });
    " class="relative">
        <!-- Toast Notification -->
        <div x-show="showMessage" x-transition
             :class="{
                'bg-green-100 border-green-300 text-green-800': messageType === 'success',
                'bg-red-100 border-red-300 text-red-800': messageType === 'error',
                'bg-blue-100 border-blue-300 text-blue-800': messageType === 'info'
             }"
             class="fixed top-4 right-4 z-50 p-3 border rounded-lg shadow-lg">
            <span x-text="messageText"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            @foreach ($this->levels as $level => $managers)
                <div class="kanban-column bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700 kanban-dropzone"
                     data-level="{{ $level }}"
                     ondragover="handleDragOver(event)"
                     ondragleave="handleDragLeave(event)"
                     ondrop="handleDrop(event, {{ $level }})">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-200">Level {{ $level }}</h3>
                        <span class="text-xs text-gray-500">{{ $managers->count() }} </span>
                    </div>
                    <div class="space-y-2 min-h-[120px]">
                        @forelse ($managers as $manager)
                            @php $user = $manager->user; @endphp
                            @if ($user)
                            <div class="kanban-card bg-white dark:bg-gray-700 rounded-lg shadow-sm p-3 flex items-center gap-3 hover:shadow-md transition-all duration-200 group"
                                 data-manager-id="{{ $manager->id }}"
                                 data-current-level="{{ $level }}">
                                <div class="h-10 w-10 flex-shrink-0 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600 flex items-center justify-center cursor-move"
                                     draggable="true"
                                     ondragstart="handleDragStart(event, {{ $level }})"
                                     ondragend="handleDragEnd(event)">
                                    @if ($user->getFirstMediaUrl('avatar'))
                                        <img src="{{ $user->getFirstMediaUrl('avatar') }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 cursor-move"
                                     draggable="true"
                                     ondragstart="handleDragStart(event, {{ $level }})"
                                     ondragend="handleDragEnd(event)">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-300 truncate">{{ $user->riscoin_id ?? '—' }}</div>
                                </div>
                                <button type="button"
                                        @click.stop="if(confirm('Are you sure you want to remove {{ addslashes($user->name) }} from managers?')) { $wire.deleteManager({{ $manager->id }}) }"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 rounded"
                                        title="Remove manager">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            @endif
                        @empty
                            <div class="text-xs text-gray-400">No users</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Confirmation Modal -->
        <div x-show="showConfirmModal"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             @keydown.escape.window="cancelMove()">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="cancelMove()"></div>

            <!-- Modal -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div x-show="showConfirmModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
                     @click.stop>
                    <!-- Icon -->
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 dark:bg-blue-900 rounded-full mb-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>

                    <!-- Content -->
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Confirm Manager Reassignment
                        </h3>
                        <template x-if="confirmData">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                Move <span class="font-semibold text-gray-900 dark:text-white" x-text="confirmData.userName"></span>
                                from <span class="font-semibold text-blue-600 dark:text-blue-400">Level <span x-text="confirmData.currentLevel"></span></span>
                                to <span class="font-semibold text-green-600 dark:text-green-400">Level <span x-text="confirmData.targetLevel"></span></span>?
                            </p>
                        </template>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-3 mt-6">
                        <button @click="cancelMove()"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                            Cancel
                        </button>
                        <button @click="confirmMove()"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Confirm Move
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let draggedElement = null;
        let draggedFromLevel = null;

        function handleDragStart(event, currentLevel) {
            // Find the card element (might be dragging from child elements)
            const card = event.target.closest('.kanban-card');
            if (!card) {
                console.error('Could not find kanban-card parent');
                return;
            }

            draggedElement = card;
            draggedFromLevel = currentLevel;

            // Get manager ID from the card's data attribute
            const managerId = card.getAttribute('data-manager-id');
            const userName = card.querySelector('.text-sm.font-medium')?.textContent || 'Unknown';

            if (!managerId) {
                console.error('No manager ID found on card');
                return;
            }

            // Store manager ID, current level, and user name in data transfer
            event.dataTransfer.setData('text/plain', JSON.stringify({
                managerId: managerId,
                currentLevel: currentLevel,
                userName: userName
            }));

            card.classList.add('opacity-70', 'scale-95');

            // Add a custom drag image
            const dragImage = card.cloneNode(true);
            dragImage.style.position = 'absolute';
            dragImage.style.top = '-1000px';
            document.body.appendChild(dragImage);
            event.dataTransfer.setDragImage(dragImage, 20, 20);
            setTimeout(() => document.body.removeChild(dragImage), 0);
        }

        function handleDragEnd(event) {
            // Find the card element
            const card = event.target.closest('.kanban-card');
            if (card) {
                card.classList.remove('opacity-70', 'scale-95');
            }
            draggedFromLevel = null;

            // Remove all dropzone highlights
            document.querySelectorAll('.kanban-dropzone').forEach(zone => {
                zone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-300', 'bg-gray-100', 'dark:bg-gray-700', 'ring-gray-300');
            });
        }

        function handleDragOver(event) {
            event.preventDefault();
            const dropzone = event.target.closest('.kanban-dropzone') || event.target;

            // Remove highlights from all dropzones
            document.querySelectorAll('.kanban-dropzone').forEach(zone => {
                zone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-300', 'bg-gray-100', 'dark:bg-gray-700', 'ring-gray-300');
            });

            // Highlight current dropzone
            if (dropzone && dropzone.classList.contains('kanban-dropzone')) {
                const targetLevel = parseInt(dropzone.getAttribute('data-level'));

                // Don't highlight if it's the same level
                if (draggedFromLevel !== null && draggedFromLevel === targetLevel) {
                    dropzone.classList.add('bg-gray-100', 'dark:bg-gray-700', 'ring-2', 'ring-gray-300');
                } else {
                    dropzone.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-300');
                }
            }
        }

        function handleDragLeave(event) {
            // Only remove highlight if we're leaving the dropzone entirely
            const relatedTarget = event.relatedTarget;
            const dropzone = event.target.closest('.kanban-dropzone');

            if (dropzone && !dropzone.contains(relatedTarget)) {
                dropzone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-300', 'bg-gray-100', 'dark:bg-gray-700', 'ring-gray-300');
            }
        }

        function handleDrop(event, targetLevel) {
            event.preventDefault();

            // Remove highlights
            const dropzone = event.target.closest('.kanban-dropzone') || event.target;
            dropzone.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'ring-2', 'ring-blue-300', 'bg-gray-100', 'dark:bg-gray-700', 'ring-gray-300');

            try {
                const dragData = JSON.parse(event.dataTransfer.getData('text/plain'));
                const managerId = dragData.managerId;
                const currentLevel = dragData.currentLevel;

                if (!managerId) {
                    console.error('No manager ID found in drag data');
                    return;
                }

                // Check if dropping on the same level
                if (currentLevel === targetLevel) {
                    // Show info message - no action needed
                    @this.dispatch('show-message', {
                        type: 'info',
                        text: 'Manager is already at Level ' + targetLevel
                    });
                    return;
                }

                if (confirm('Move this manager from Level ' + currentLevel + ' to Level ' + targetLevel + '?')) {
                    // Call Livewire method with both managerId and targetLevel
                    @this.reassignUser(parseInt(managerId), targetLevel, currentLevel);
                }
            } catch (error) {
                console.error('Error parsing drag data:', error);
            }
        }

        // Initialize drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            // Set up event listeners for dropzones
            document.querySelectorAll('.kanban-dropzone').forEach(zone => {
                zone.addEventListener('dragover', handleDragOver);
                zone.addEventListener('dragleave', handleDragLeave);
            });

            // Set up event listeners for cards
            document.querySelectorAll('.kanban-card').forEach(card => {
                // Get current level from data attribute
                const currentLevel = card.getAttribute('data-current-level');
                if (currentLevel) {
                    card.addEventListener('dragstart', (e) => handleDragStart(e, parseInt(currentLevel)));
                    card.addEventListener('dragend', handleDragEnd);
                }
            });
        });

        // Reinitialize when Livewire updates the DOM
        document.addEventListener('livewire:load', function() {
            // Initial setup
            document.querySelectorAll('.kanban-dropzone').forEach(zone => {
                zone.addEventListener('dragover', handleDragOver);
                zone.addEventListener('dragleave', handleDragLeave);
            });

            document.querySelectorAll('.kanban-card').forEach(card => {
                const currentLevel = card.getAttribute('data-current-level');
                if (currentLevel) {
                    card.addEventListener('dragstart', (e) => handleDragStart(e, parseInt(currentLevel)));
                    card.addEventListener('dragend', handleDragEnd);
                }
            });
        });

        // Re-setup after Livewire updates
        Livewire.hook('message.processed', (message, component) => {
            setTimeout(() => {
                document.querySelectorAll('.kanban-dropzone').forEach(zone => {
                    zone.removeEventListener('dragover', handleDragOver);
                    zone.removeEventListener('dragleave', handleDragLeave);
                    zone.addEventListener('dragover', handleDragOver);
                    zone.addEventListener('dragleave', handleDragLeave);
                });

                document.querySelectorAll('.kanban-card').forEach(card => {
                    const currentLevel = card.getAttribute('data-current-level');
                    if (currentLevel) {
                        card.removeEventListener('dragstart', handleDragStart);
                        card.removeEventListener('dragend', handleDragEnd);
                        card.addEventListener('dragstart', (e) => handleDragStart(e, parseInt(currentLevel)));
                        card.addEventListener('dragend', handleDragEnd);
                    }
                });
            }, 50);
        });
    </script>
</div>
