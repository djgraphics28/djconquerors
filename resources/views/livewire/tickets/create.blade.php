<?php

use App\Models\Ticket;
use App\Notifications\TicketSubmitted;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $subject = '';
    public string $description = '';
    public string $category = '';
    public string $priority = 'medium';
    public array $attachments = [];

    protected function rules(): array
    {
        return [
            'subject'        => 'required|string|max:255',
            'description'    => 'required|string|min:10',
            'category'       => 'nullable|string|max:100',
            'priority'       => 'required|in:low,medium,high',
            'attachments.*'  => 'nullable|file|max:10240',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $ticket = Ticket::create([
            'user_id'     => auth()->id(),
            'subject'     => $this->subject,
            'description' => $this->description,
            'category'    => $this->category ?: null,
            'priority'    => $this->priority,
            'status'      => 'open',
        ]);

        auth()->user()->notify(new TicketSubmitted($ticket));

        foreach ($this->attachments as $file) {
            $ticket->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        $this->redirect(route('tickets.show', $ticket->id), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('tickets.index') }}" wire:navigate
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Submit a Support Ticket</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Describe your issue and our team will help you</p>
            </div>
        </div>

        <form wire:submit="submit" class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 shadow-sm p-6 space-y-5">

            <!-- Subject -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subject <span class="text-red-500">*</span></label>
                <input type="text" wire:model="subject" placeholder="Brief summary of your issue"
                    class="w-full px-3 py-2.5 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('subject') border-red-400 @enderror"/>
                @error('subject') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <!-- Category + Priority Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                    <select wire:model="category"
                        class="w-full px-3 py-2.5 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select a category</option>
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Technical Issue">Technical Issue</option>
                        <option value="Account">Account</option>
                        <option value="Billing">Billing</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Priority</label>
                    <select wire:model="priority"
                        class="w-full px-3 py-2.5 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="low">Low — general question</option>
                        <option value="medium">Medium — needs attention</option>
                        <option value="high">High — urgent issue</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description <span class="text-red-500">*</span></label>
                <textarea wire:model="description" rows="6"
                    placeholder="Please describe your issue in detail. Include any relevant information such as error messages, steps to reproduce, account details, etc."
                    class="w-full px-3 py-2.5 text-sm border rounded-lg bg-white dark:bg-zinc-900 text-gray-800 dark:text-gray-100 border-gray-200 dark:border-zinc-600 placeholder-gray-400 resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-400 @enderror"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <!-- Attachments -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Attachments</label>
                <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 transition bg-gray-50 dark:bg-zinc-900/50">
                    <svg class="w-8 h-8 text-gray-300 dark:text-zinc-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span class="text-sm text-gray-400 dark:text-gray-500">Click to upload files (max 10MB each)</span>
                    <input type="file" wire:model="attachments" multiple class="hidden" accept="image/*,.pdf,.doc,.docx,.txt,.zip">
                </label>
                @error('attachments.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @if (count($attachments))
                    <div class="mt-2 space-y-1">
                        @foreach ($attachments as $file)
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-zinc-900/50 px-3 py-1.5 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                                {{ $file->getClientOriginalName() }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('tickets.index') }}" wire:navigate
                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition">Cancel</a>
                <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-70 cursor-not-allowed"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                    <span wire:loading.remove>Submit Ticket</span>
                    <span wire:loading>Submitting…</span>
                </button>
            </div>
        </form>
    </div>
</div>
