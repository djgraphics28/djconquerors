<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Appointment;
use App\Models\User;
use App\Models\AvailabilitySlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

new class extends Component {

    use WithFileUploads;

    // ── Navigation ────────────────────────────────────────────
    public string $activeTab = 'availability';

    // ── My Availability ───────────────────────────────────────
    public string $availDate     = '';
    public array  $slotGrid      = [];
    public array  $pendingSlots  = [];   // 'H:i' keys the user has toggled open
    public array  $bookedTimes   = [];   // 'H:i' keys already booked (read-only)
    public bool   $isDirty       = false;

    // ── Book Appointment ──────────────────────────────────────
    public int    $bookStep         = 1;
    public string $memberSearch     = '';
    public ?int   $selectedMemberId = null;
    public string $bookDate         = '';
    public array  $selectedSlots    = [];   // [['id'=>int,'start'=>'H:i:s','end'=>'H:i:s'], ...]
    public string $bookingNotes     = '';
    public string $bookingVenue     = '';

    // ── My Bookings ───────────────────────────────────────────
    public string $bookingsSubTab = 'mine';

    // ── Complete with Evidence Modal ──────────────────────────
    public bool   $showCompleteModal       = false;
    public ?int   $completeAppointmentId   = null;
    public array  $evidencePhotos          = [];

    // ── Evidence View / Edit Modal ───────────────────────────
    public bool   $showEvidenceModal       = false;
    public ?int   $evidenceModalApptId     = null;
    public bool   $evidenceEditMode        = false;
    public array  $pendingDeleteMediaIds   = [];  // saved media IDs queued for removal
    public array  $newEvidencePhotos       = [];  // new uploads in edit mode

    // ── Confirm Modal ─────────────────────────────────────────
    public bool   $showConfirmModal    = false;
    public ?int   $confirmModalId      = null;
    public string $confirmModalAction  = '';
    public string $confirmModalTitle   = '';
    public string $confirmModalMessage = '';

    // ── Mount ─────────────────────────────────────────────────
    public function mount(): void
    {
        $this->availDate = now()->format('Y-m-d');
        $this->bookDate  = now()->addDay()->format('Y-m-d');
        $this->loadSlotGrid();
    }

    // ══════════════════════════════════════════════════════════
    //  MY AVAILABILITY
    // ══════════════════════════════════════════════════════════

    public function loadSlotGrid(): void
    {
        $this->pendingSlots = AvailabilitySlot::where('user_id', Auth::id())
            ->where('date', $this->availDate)
            ->where('is_available', true)
            ->pluck('start_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->filter(fn($t) => in_array((int) substr($t, 3, 2), [0, 30]))
            ->values()
            ->toArray();

        $this->bookedTimes = Appointment::where('host_user_id', Auth::id())
            ->whereDate('start_time', $this->availDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->flatMap(function ($a) {
                $times = [];
                $cur   = Carbon::parse($a->start_time);
                $end   = Carbon::parse($a->end_time);
                while ($cur->lt($end)) {
                    $times[] = $cur->format('H:i');
                    $cur->addMinutes(30);
                }
                return $times;
            })
            ->toArray();

        $this->isDirty = false;
        $this->rebuildGrid();
    }

    protected function rebuildGrid(): void
    {
        $grid = [];
        for ($h = 7; $h < 22; $h++) {
            $minSlots = [];
            for ($m = 0; $m < 60; $m += 30) {
                $timeKey  = sprintf('%02d:%02d', $h, $m);
                $minSlots[] = [
                    'time'      => $timeKey,
                    'label'     => Carbon::createFromFormat('H:i', $timeKey)->format('g:i A'),
                    'available' => in_array($timeKey, $this->pendingSlots),
                    'booked'    => in_array($timeKey, $this->bookedTimes),
                ];
            }
            $grid[] = [
                'label' => Carbon::createFromFormat('H:i', sprintf('%02d:00', $h))->format('g A'),
                'hour'  => $h,
                'slots' => $minSlots,
            ];
        }
        $this->slotGrid = $grid;
    }

    public function toggleSlot(string $time): void
    {
        if (in_array($time, $this->bookedTimes)) return;

        if (in_array($time, $this->pendingSlots)) {
            $this->pendingSlots = array_values(array_filter($this->pendingSlots, fn($t) => $t !== $time));
        } else {
            $this->pendingSlots[] = $time;
        }

        $this->isDirty = true;
        $this->rebuildGrid();
    }

    public function toggleHour(int $hour): void
    {
        $hourTimes = [];
        for ($m = 0; $m < 60; $m += 30) {
            $t = sprintf('%02d:%02d', $hour, $m);
            if (!in_array($t, $this->bookedTimes)) {
                $hourTimes[] = $t;
            }
        }

        $openInHour = array_intersect($hourTimes, $this->pendingSlots);
        $allOpen    = count($openInHour) === count($hourTimes) && count($hourTimes) > 0;

        if ($allOpen) {
            $this->pendingSlots = array_values(array_diff($this->pendingSlots, $hourTimes));
        } else {
            $this->pendingSlots = array_values(array_unique(array_merge($this->pendingSlots, $hourTimes)));
        }

        $this->isDirty = true;
        $this->rebuildGrid();
    }

    public function setAllAvailable(): void
    {
        $all = [];
        for ($h = 7; $h < 22; $h++) {
            for ($m = 0; $m < 60; $m += 30) {
                $all[] = sprintf('%02d:%02d', $h, $m);
            }
        }
        $this->pendingSlots = $all;
        $this->isDirty = true;
        $this->rebuildGrid();
    }

    public function clearAllSlots(): void
    {
        // Keep booked times — can't remove a slot that has an appointment
        $this->pendingSlots = array_values(array_intersect($this->pendingSlots, $this->bookedTimes));
        $this->isDirty = true;
        $this->rebuildGrid();
    }

    public function updatedAvailDate(): void
    {
        $this->loadSlotGrid(); // reloads from DB, resets $isDirty
    }

    public function availDateSummary(): array
    {
        return [
            'available' => count($this->pendingSlots),
            'booked'    => count($this->bookedTimes),
        ];
    }

    public function saveAvailability(): void
    {
        // Delete all non-booked slots for this date, then re-create the pending set
        $bookedFull = array_map(fn($t) => $t . ':00', $this->bookedTimes);

        AvailabilitySlot::where('user_id', Auth::id())
            ->where('date', $this->availDate)
            ->when(!empty($bookedFull), fn($q) => $q->whereNotIn('start_time', $bookedFull))
            ->delete();

        foreach ($this->pendingSlots as $time) {
            $endTime = Carbon::createFromFormat('H:i', $time)->addMinutes(30)->format('H:i:s');
            AvailabilitySlot::create([
                'user_id'      => Auth::id(),
                'date'         => $this->availDate,
                'start_time'   => $time . ':00',
                'end_time'     => $endTime,
                'is_available' => true,
            ]);
        }

        $this->isDirty = false;
        $this->dispatch('toast', type: 'success', message: 'Schedule saved for ' . Carbon::parse($this->availDate)->format('M j, Y') . '!');
    }

    // ══════════════════════════════════════════════════════════
    //  BOOK APPOINTMENT
    // ══════════════════════════════════════════════════════════

    public function getMembers()
    {
        return User::where('id', '!=', Auth::id())
            ->where('is_active', true)
            ->when($this->memberSearch, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'LIKE', '%' . $this->memberSearch . '%')
                          ->orWhere('email', 'LIKE', '%' . $this->memberSearch . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function selectMember(int $userId): void
    {
        $this->selectedMemberId = $userId;
        $this->bookStep = 2;
    }

    public function getSelectedMember(): ?User
    {
        return $this->selectedMemberId ? User::find($this->selectedMemberId) : null;
    }

    public function getMemberAvailability(): array
    {
        if (!$this->selectedMemberId) return [];

        $bookedTimes = Appointment::where('host_user_id', $this->selectedMemberId)
            ->whereDate('start_time', $this->bookDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->flatMap(function ($a) {
                $times = [];
                $cur   = Carbon::parse($a->start_time);
                $end   = Carbon::parse($a->end_time);
                while ($cur->lt($end)) {
                    $times[] = $cur->format('H:i');
                    $cur->addMinutes(30);
                }
                return $times;
            })
            ->toArray();

        return AvailabilitySlot::where('user_id', $this->selectedMemberId)
            ->where('date', $this->bookDate)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get()
            ->filter(fn($slot) =>
                !in_array(substr($slot->start_time, 0, 5), $bookedTimes) &&
                in_array((int) substr($slot->start_time, 3, 2), [0, 30])
            )
            ->values()
            ->toArray();
    }

    public function toggleBookSlot(int $slotId, string $slotStart, string $slotEnd): void
    {
        $entry  = ['id' => $slotId, 'start' => $slotStart, 'end' => $slotEnd];
        $slots  = $this->selectedSlots;
        $count  = count($slots);

        // Find if already selected
        $idx = null;
        foreach ($slots as $i => $s) {
            if ($s['id'] === $slotId) { $idx = $i; break; }
        }

        if ($idx !== null) {
            // Deselect: only trim edges to preserve contiguity
            if ($count === 1) {
                $this->selectedSlots = [];
            } elseif ($idx === 0) {
                $this->selectedSlots = array_values(array_slice($slots, 1));
            } elseif ($idx === $count - 1) {
                $this->selectedSlots = array_values(array_slice($slots, 0, -1));
            } else {
                // Middle slot tapped — reset to just this one
                $this->selectedSlots = [$entry];
            }
            return;
        }

        // New slot: start fresh if nothing selected
        if ($count === 0) {
            $this->selectedSlots = [$entry];
            return;
        }

        // Adjacency check using Carbon to avoid string-format issues
        $newStart   = Carbon::createFromFormat('H:i:s', $slotStart);
        $newEnd     = Carbon::createFromFormat('H:i:s', $slotEnd);
        $firstStart = Carbon::createFromFormat('H:i:s', $slots[0]['start']);
        $lastEnd    = Carbon::createFromFormat('H:i:s', $slots[$count - 1]['end']);

        if ($newEnd->eq($firstStart)) {
            // Adjacent before range → prepend
            $this->selectedSlots = array_merge([$entry], $slots);
        } elseif ($newStart->eq($lastEnd)) {
            // Adjacent after range → append
            $this->selectedSlots = array_merge($slots, [$entry]);
        } else {
            // Not adjacent → start fresh
            $this->selectedSlots = [$entry];
        }
    }

    public function proceedToConfirm(): void
    {
        if (empty($this->selectedSlots)) return;
        $this->bookStep = 3;
    }

    public function backToMemberList(): void
    {
        $this->bookStep         = 1;
        $this->selectedMemberId = null;
        $this->selectedSlots    = [];
    }

    public function backToSlotPick(): void
    {
        $this->bookStep = 2;
    }

    public function confirmBooking(): void
    {
        $this->validate([
            'bookingNotes' => 'nullable|string|max:500',
            'bookingVenue' => 'nullable|string|max:200',
        ]);

        if (empty($this->selectedSlots)) {
            $this->dispatch('toast', type: 'error', message: 'Please select at least one time slot.');
            $this->bookStep = 2;
            return;
        }

        if ($this->bookDate <= now()->format('Y-m-d')) {
            $this->dispatch('toast', type: 'error', message: 'Same-day bookings are not allowed. Please select a future date.');
            $this->bookStep = 2;
            return;
        }

        // Verify all slots in the range are still available
        $unavailable = 0;
        foreach ($this->selectedSlots as $entry) {
            $slot = AvailabilitySlot::where('id', $entry['id'])
                ->where('user_id', $this->selectedMemberId)
                ->where('is_available', true)
                ->first();

            $alreadyBooked = Appointment::where('host_user_id', $this->selectedMemberId)
                ->where('start_time', $this->bookDate . ' ' . substr($entry['start'], 0, 5))
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if (!$slot || $alreadyBooked) {
                $unavailable++;
            }
        }

        if ($unavailable > 0) {
            $this->dispatch('toast', type: 'error', message: 'Some slots in your selected range are no longer available. Please choose a different time.');
            $this->selectedSlots = [];
            $this->bookStep = 2;
            return;
        }

        // Create a single appointment spanning the full selected range
        $startTime = substr($this->selectedSlots[0]['start'], 0, 5);
        $endTime   = substr(end($this->selectedSlots)['end'], 0, 5);

        Appointment::create([
            'user_id'      => Auth::id(),
            'host_user_id' => $this->selectedMemberId,
            'start_time'   => $this->bookDate . ' ' . $startTime,
            'end_time'     => $this->bookDate . ' ' . $endTime,
            'status'       => 'pending',
            'notes'        => $this->bookingNotes ?: null,
            'venue'        => $this->bookingVenue ?: null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Appointment booked! Awaiting confirmation from the host.');
        $this->activeTab      = 'bookings';
        $this->bookingsSubTab = 'mine';
        $this->bookStep       = 1;
        $this->reset(['selectedMemberId', 'selectedSlots', 'bookingNotes', 'bookingVenue', 'memberSearch']);
    }

    public function updatedBookDate(): void
    {
        $this->selectedSlots = []; // clear selections when date changes
    }
    public function updatedMemberSearch(): void {}

    // ══════════════════════════════════════════════════════════
    //  MY BOOKINGS
    // ══════════════════════════════════════════════════════════

    public function getMyBookings()
    {
        return Appointment::with('host')
            ->where('user_id', Auth::id())
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function getBookingsWithMe()
    {
        return Appointment::with('user')
            ->where('host_user_id', Auth::id())
            ->orderBy('start_time', 'desc')
            ->get();
    }

    public function confirmAppointment(int $id): void
    {
        $appointment = Appointment::where('id', $id)->where('host_user_id', Auth::id())->firstOrFail();
        $appointment->update(['status' => 'confirmed']);
        $this->dispatch('toast', type: 'success', message: 'Appointment confirmed!');
    }

    public function cancelAppointment(int $id): void
    {
        $appointment = Appointment::where('id', $id)
            ->where(fn($q) => $q->where('user_id', Auth::id())->orWhere('host_user_id', Auth::id()))
            ->firstOrFail();
        $appointment->update(['status' => 'cancelled']);
        $this->dispatch('toast', type: 'success', message: 'Appointment cancelled.');
    }

    public function completeAppointment(int $id): void
    {
        $appointment = Appointment::where('id', $id)->where('host_user_id', Auth::id())->firstOrFail();
        $appointment->update(['status' => 'completed']);
        $this->dispatch('toast', type: 'success', message: 'Appointment marked as completed!');
    }

    public function openCompleteModal(int $id): void
    {
        $this->completeAppointmentId = $id;
        $this->evidencePhotos        = [];
        $this->showCompleteModal     = true;
    }

    public function openEvidenceModal(int $appointmentId, bool $editMode = false): void
    {
        $this->evidenceModalApptId   = $appointmentId;
        $this->evidenceEditMode      = $editMode;
        $this->pendingDeleteMediaIds = [];
        $this->newEvidencePhotos     = [];
        $this->showEvidenceModal     = true;
    }

    public function closeEvidenceModal(): void
    {
        $this->showEvidenceModal     = false;
        $this->evidenceModalApptId   = null;
        $this->evidenceEditMode      = false;
        $this->pendingDeleteMediaIds = [];
        $this->newEvidencePhotos     = [];
    }

    public function toggleDeleteEvidence(int $mediaId): void
    {
        if (in_array($mediaId, $this->pendingDeleteMediaIds)) {
            $this->pendingDeleteMediaIds = array_values(array_filter($this->pendingDeleteMediaIds, fn($id) => $id !== $mediaId));
        } else {
            $this->pendingDeleteMediaIds[] = $mediaId;
        }
    }

    public function removeNewEvidencePhoto(int $index): void
    {
        $photos = $this->newEvidencePhotos;
        array_splice($photos, $index, 1);
        $this->newEvidencePhotos = array_values($photos);
    }

    public function updatedNewEvidencePhotos(): void
    {
        $appt            = Appointment::find($this->evidenceModalApptId);
        $existingCount   = $appt ? $appt->getMedia('evidence')->count() - count($this->pendingDeleteMediaIds) : 0;
        $totalAllowed    = 3 - $existingCount;
        if (count($this->newEvidencePhotos) > $totalAllowed) {
            $this->newEvidencePhotos = array_slice($this->newEvidencePhotos, 0, max(0, $totalAllowed));
            $this->dispatch('toast', type: 'error', message: 'Maximum 3 evidence photos total.');
        }
        $this->validate(['newEvidencePhotos.*' => 'image|max:5120']);
    }

    public function saveEvidenceEdit(): void
    {
        $this->validate(['newEvidencePhotos.*' => 'image|max:5120']);

        $appointment = Appointment::where('id', $this->evidenceModalApptId)
            ->where('host_user_id', Auth::id())
            ->firstOrFail();

        // Delete queued media
        foreach ($this->pendingDeleteMediaIds as $mediaId) {
            $media = $appointment->getMedia('evidence')->firstWhere('id', $mediaId);
            if ($media) $media->delete();
        }

        // Add new uploads
        foreach ($this->newEvidencePhotos as $photo) {
            $appointment
                ->addMedia($photo->getRealPath())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('evidence');
        }

        $this->closeEvidenceModal();
        $this->dispatch('toast', type: 'success', message: 'Evidence photos updated!');
    }

    public function closeCompleteModal(): void
    {
        $this->showCompleteModal     = false;
        $this->completeAppointmentId = null;
        $this->evidencePhotos        = [];
    }

    public function removeEvidencePhoto(int $index): void
    {
        $photos = $this->evidencePhotos;
        array_splice($photos, $index, 1);
        $this->evidencePhotos = array_values($photos);
    }

    public function updatedEvidencePhotos(): void
    {
        if (count($this->evidencePhotos) > 3) {
            $this->evidencePhotos = array_slice($this->evidencePhotos, 0, 3);
            $this->dispatch('toast', type: 'error', message: 'Maximum 3 images allowed.');
        }
        $this->validate([
            'evidencePhotos.*' => 'image|max:5120',
        ]);
    }

    public function submitCompleteAppointment(): void
    {
        $this->validate([
            'evidencePhotos'   => 'required|array|min:1|max:3',
            'evidencePhotos.*' => 'image|max:5120',
        ]);

        $appointment = Appointment::where('id', $this->completeAppointmentId)
            ->where('host_user_id', Auth::id())
            ->firstOrFail();

        foreach ($this->evidencePhotos as $photo) {
            $appointment
                ->addMedia($photo->getRealPath())
                ->usingFileName($photo->getClientOriginalName())
                ->toMediaCollection('evidence');
        }

        $appointment->update(['status' => 'completed']);
        $this->closeCompleteModal();
        $this->dispatch('toast', type: 'success', message: 'Appointment marked as completed with evidence!');
    }

    // ══════════════════════════════════════════════════════════
    //  CONFIRM MODAL
    // ══════════════════════════════════════════════════════════

    public function openConfirmModal(int $id, string $action, string $title, string $message): void
    {
        $this->confirmModalId      = $id;
        $this->confirmModalAction  = $action;
        $this->confirmModalTitle   = $title;
        $this->confirmModalMessage = $message;
        $this->showConfirmModal    = true;
    }

    public function closeConfirmModal(): void
    {
        $this->showConfirmModal    = false;
        $this->confirmModalId      = null;
        $this->confirmModalAction  = '';
        $this->confirmModalTitle   = '';
        $this->confirmModalMessage = '';
    }

    public function executeConfirmAction(): void
    {
        if (!$this->confirmModalId || !$this->confirmModalAction) {
            $this->closeConfirmModal();
            return;
        }

        $id     = $this->confirmModalId;
        $action = $this->confirmModalAction;
        $this->closeConfirmModal();

        match ($action) {
            'cancel', 'decline' => $this->cancelAppointment($id),
            'confirm'           => $this->confirmAppointment($id),
            default             => null,
        };
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'confirmed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
            'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            default     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }
}; ?>

<div class="min-h-screen py-4 sm:py-8">

    {{-- ── Toast notifications ─────────────────────────────────── --}}
    <div
        x-data="{
            toasts: [],
            add(t) {
                const id = Date.now() + Math.random();
                this.toasts.push({ ...t, id, visible: false });
                this.$nextTick(() => {
                    const item = this.toasts.find(x => x.id === id);
                    if (item) item.visible = true;
                });
                setTimeout(() => this.remove(id), 4500);
            },
            remove(id) {
                const item = this.toasts.find(x => x.id === id);
                if (item) item.visible = false;
                setTimeout(() => this.toasts = this.toasts.filter(x => x.id !== id), 400);
            }
        }"
        @toast.window="add($event.detail)"
        class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 w-80 pointer-events-none"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-8"
                :class="toast.type === 'success'
                    ? 'border-l-4 border-green-500 bg-gray-900'
                    : 'border-l-4 border-red-500 bg-gray-900'"
                class="pointer-events-auto flex items-start gap-3 px-4 py-3.5 rounded-xl shadow-2xl text-sm text-white"
            >
                <div
                    x-show="toast.type === 'success'"
                    class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-green-500 flex items-center justify-center"
                >
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div
                    x-show="toast.type === 'error'"
                    class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-red-500 flex items-center justify-center"
                >
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span x-text="toast.message" class="flex-1 leading-snug"></span>
                <button @click="remove(toast.id)" class="shrink-0 opacity-40 hover:opacity-100 transition-opacity ml-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </template>
    </div>

    <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8">

        {{-- PAGE HEADER --}}
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Appointment Scheduler
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Set your availability, book time with members, and manage your appointments.
            </p>
        </div>


        {{-- TAB NAV --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="flex overflow-x-auto">
                @php
                    $tabs = [
                        ['id' => 'availability', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'My Availability'],
                        ['id' => 'book',         'icon' => 'M12 4v16m8-8H4', 'label' => 'Book Appointment'],
                        ['id' => 'bookings',     'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0', 'label' => 'My Bookings'],
                    ];
                @endphp
                @foreach ($tabs as $tab)
                    <button
                        wire:click="$set('activeTab', '{{ $tab['id'] }}')"
                        class="flex items-center gap-2 px-4 sm:px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150
                            {{ $activeTab === $tab['id']
                                ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300' }}"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                        </svg>
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════ --}}
        {{--  TAB: MY AVAILABILITY                              --}}
        {{-- ══════════════════════════════════════════════════ --}}
        @if ($activeTab === 'availability')
            <div class="mb-5 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl text-sm text-blue-800 dark:text-blue-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                    <div>
                        <p class="font-semibold mb-1">How to set your availability:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                            <li>Choose a date, then click any time slot to <strong>toggle it open or closed</strong>. Changes are not saved yet.</li>
                            <li>Use <em>Set All</em> to open the entire day or <em>Clear All</em> to remove all slots — then hit <strong>Save Schedule</strong>.</li>
                            <li>
                                <span class="inline-block w-3 h-3 bg-green-400 rounded-sm align-middle mr-1"></span>Green = open &nbsp;
                                <span class="inline-block w-3 h-3 bg-amber-400 rounded-sm align-middle mr-1"></span>Amber = already booked &nbsp;
                                <span class="inline-block w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-sm align-middle mr-1"></span>Gray = not set
                            </li>
                            <li>Click <strong>Save Schedule</strong> to apply your changes in one go.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Date picker + actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Select Date</label>
                        <input
                            type="date"
                            wire:model.live="availDate"
                            min="{{ now()->format('Y-m-d') }}"
                            class="w-full sm:w-56 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    @php $summary = $this->availDateSummary(); @endphp
                    <div class="flex flex-wrap gap-2 pb-0.5">
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 rounded-lg text-xs font-medium">
                            {{ $summary['available'] }} open
                        </span>
                        <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded-lg text-xs font-medium">
                            {{ $summary['booked'] }} booked
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2 pb-0.5">
                        <button
                            wire:click="setAllAvailable"
                            class="flex-1 sm:flex-none px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-medium transition-colors"
                        >Set All</button>
                        <button
                            wire:click="clearAllSlots"
                            class="flex-1 sm:flex-none px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-medium transition-colors"
                        >Clear All</button>
                        <button
                            wire:click="saveAvailability"
                            wire:loading.attr="disabled"
                            wire:target="saveAvailability"
                            @class([
                                'flex-1 sm:flex-none px-5 py-2 rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-2',
                                'bg-blue-600 hover:bg-blue-700 text-white shadow-sm' => $isDirty,
                                'bg-blue-100 dark:bg-blue-900/30 text-blue-400 dark:text-blue-500 cursor-default' => !$isDirty,
                            ])
                        >
                            <span wire:loading.remove wire:target="saveAvailability">
                                @if ($isDirty)
                                    <svg class="inline w-3.5 h-3.5 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Save Schedule
                                @else
                                    Saved
                                @endif
                            </span>
                            <span wire:loading wire:target="saveAvailability">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Time slot grid --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ Carbon::parse($availDate)->format('l, F j, Y') }} &mdash; 7:00 AM to 10:00 PM &nbsp;&bull;&nbsp; 30-min intervals
                    </h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($slotGrid as $hourRow)
                        @php
                            $hourFree   = collect($hourRow['slots'])->filter(fn($s) => !$s['booked']);
                            $hourAllOpen = $hourFree->isNotEmpty() && $hourFree->every(fn($s) => $s['available']);
                        @endphp
                        <div class="flex items-start px-3 sm:px-6 py-2 gap-2 sm:gap-4">
                            <div class="w-12 sm:w-14 shrink-0 text-right pt-1">
                                <button
                                    wire:click="toggleHour({{ $hourRow['hour'] }})"
                                    title="{{ $hourAllOpen ? 'Deselect all ' . $hourRow['label'] : 'Select all ' . $hourRow['label'] }}"
                                    class="w-full text-xs font-semibold px-1 py-1 rounded-lg transition-colors
                                        {{ $hourAllOpen
                                            ? 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 hover:bg-green-100 dark:hover:bg-green-900/50'
                                            : 'text-gray-400 dark:text-gray-500 hover:text-blue-500 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20' }}"
                                >
                                    {{ $hourRow['label'] }}
                                    <span class="block text-[9px] leading-tight mt-0.5 opacity-70">{{ $hourAllOpen ? '✕ all' : '+ all' }}</span>
                                </button>
                            </div>
                            <div class="flex gap-1 sm:gap-2 flex-1 flex-wrap py-1">
                                @foreach ($hourRow['slots'] as $slot)
                                    <button
                                        wire:click="toggleSlot('{{ $slot['time'] }}')"
                                        @if ($slot['booked']) disabled title="Already booked" @endif
                                        class="flex-1 min-w-[58px] sm:min-w-[72px] max-w-[100px] px-1 sm:px-2 py-1.5 rounded-lg text-xs font-medium text-center transition-all border
                                            @if ($slot['booked'])
                                                bg-amber-100 dark:bg-amber-900/40 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300 cursor-not-allowed
                                            @elseif ($slot['available'])
                                                bg-green-100 dark:bg-green-900/40 border-green-300 dark:border-green-700 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/60
                                            @else
                                                bg-gray-50 dark:bg-gray-900/30 border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700
                                            @endif"
                                    >
                                        {{ $slot['label'] }}
                                        @if ($slot['booked'])
                                            <span class="block text-[10px] leading-tight opacity-80">booked</span>
                                        @elseif ($slot['available'])
                                            <span class="block text-[10px] leading-tight opacity-80">✓ open</span>
                                        @else
                                            <span class="block text-[10px] leading-tight opacity-50">+ add</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════ --}}
        {{--  TAB: BOOK APPOINTMENT                             --}}
        {{-- ══════════════════════════════════════════════════ --}}
        @if ($activeTab === 'book')
            <div class="mb-5 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl text-sm text-purple-800 dark:text-purple-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                    <div>
                        <p class="font-semibold mb-1">How to book an appointment:</p>
                        <ol class="list-decimal list-inside space-y-1 text-purple-700 dark:text-purple-300">
                            <li><strong>Step 1:</strong> Search and select the member you want to meet.</li>
                            <li><strong>Step 2:</strong> Pick a date and choose an open time slot from their schedule.</li>
                            <li><strong>Step 3:</strong> Add optional notes or venue, then confirm your booking.</li>
                            <li>The member will be notified and can confirm or decline.</li>
                        </ol>
                    </div>
                </div>
            </div>

            {{-- Step indicator --}}
            <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-1">
                @foreach ([1 => 'Pick Member', 2 => 'Pick Date & Time', 3 => 'Confirm'] as $num => $label)
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="flex items-center gap-1.5">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                                {{ $bookStep >= $num ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                {{ $bookStep > $num ? '✓' : $num }}
                            </div>
                            <span class="text-xs font-medium {{ $bookStep >= $num ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $label }}
                            </span>
                        </div>
                        @if ($num < 3)
                            <div class="w-8 sm:w-16 h-0.5 {{ $bookStep > $num ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- STEP 1: Pick Member --}}
            @if ($bookStep === 1)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Choose a Member</h2>
                    <div class="relative mb-4">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input
                            type="text"
                            wire:model.live="memberSearch"
                            placeholder="Search by name or email…"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                    @php $members = $this->getMembers(); @endphp
                    @if ($members->isEmpty())
                        <p class="text-center text-gray-400 dark:text-gray-500 text-sm py-8">No members found.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($members as $member)
                                <button
                                    wire:click="selectMember({{ $member->id }})"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-left transition-all"
                                >
                                    @if ($member->getFirstMediaUrl('profile'))
                                        <img src="{{ $member->getFirstMediaUrl('profile') }}" class="w-10 h-10 rounded-full object-cover shrink-0" alt="{{ $member->name }}">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center shrink-0">
                                            <span class="text-white text-sm font-bold">{{ strtoupper(substr($member->name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $member->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</div>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- STEP 2: Pick Date & Time (multi-select) --}}
            @if ($bookStep === 2)
                @php
                    $selectedMember     = $this->getSelectedMember();
                    $selectedSlotIds    = collect($selectedSlots)->pluck('id')->toArray();
                    $selectedSlotCount  = count($selectedSlots);
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <button wire:click="backToMemberList" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        @if ($selectedMember)
                            @if ($selectedMember->getFirstMediaUrl('profile'))
                                <img src="{{ $selectedMember->getFirstMediaUrl('profile') }}" class="w-9 h-9 rounded-full object-cover shrink-0" alt="{{ $selectedMember->name }}">
                            @else
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center shrink-0">
                                    <span class="text-white text-sm font-bold">{{ strtoupper(substr($selectedMember->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedMember->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Pick a date, then select one or more time slots</div>
                            </div>
                        @endif
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Select Date</label>
                        <input
                            type="date"
                            wire:model.live="bookDate"
                            min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="w-full sm:w-56 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    @php $memberSlots = $this->getMemberAvailability(); @endphp
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Available slots on {{ Carbon::parse($bookDate)->format('l, M j, Y') }}
                        </h3>
                        @if ($selectedSlotCount > 0)
                            <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full text-xs font-semibold">
                                {{ $selectedSlotCount }} selected
                            </span>
                        @endif
                    </div>

                    @if (empty($memberSlots))
                        <div class="text-center py-10 text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm font-medium">No available slots for this date.</p>
                            <p class="text-xs mt-1">Try a different date, or ask the member to open their schedule.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-5">
                            @foreach ($memberSlots as $slot)
                                @php
                                    $isSelected = in_array($slot['id'], $selectedSlotIds);
                                    $startLabel = Carbon::createFromFormat('H:i:s', $slot['start_time'])->format('g:i A');
                                    $endLabel   = Carbon::createFromFormat('H:i:s', $slot['end_time'])->format('g:i A');
                                @endphp
                                <button
                                    wire:key="slot-{{ $slot['id'] }}"
                                    wire:click="toggleBookSlot({{ $slot['id'] }}, '{{ $slot['start_time'] }}', '{{ $slot['end_time'] }}')"
                                    class="relative px-3 py-2.5 rounded-xl border text-sm font-medium text-center transition-all
                                        {{ $isSelected
                                            ? 'border-blue-500 bg-blue-600 text-white shadow-sm'
                                            : 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/40 text-green-700 dark:text-green-300' }}"
                                >
                                    @if ($isSelected)
                                        <span class="absolute top-1 right-1 w-3.5 h-3.5 bg-white rounded-full flex items-center justify-center">
                                            <svg class="w-2.5 h-2.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                    @endif
                                    {{ $startLabel }}
                                    <span class="block text-xs opacity-75 mt-0.5">{{ $endLabel }}</span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Continue bar --}}
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @if ($selectedSlotCount === 0)
                                    Tap slots to build a schedule
                                @else
                                    @php
                                        $rangeStart = Carbon::createFromFormat('H:i:s', $selectedSlots[0]['start'])->format('g:i A');
                                        $rangeEnd   = Carbon::createFromFormat('H:i:s', end($selectedSlots)['end'])->format('g:i A');
                                    @endphp
                                    <span class="font-medium text-blue-600 dark:text-blue-400">{{ $rangeStart }} – {{ $rangeEnd }}</span>
                                @endif
                            </p>
                            <button
                                wire:click="proceedToConfirm"
                                @if($selectedSlotCount === 0) disabled @endif
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors
                                    {{ $selectedSlotCount > 0
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed' }}"
                            >
                                Continue
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- STEP 3: Confirm Booking --}}
            @if ($bookStep === 3)
                @php $selectedMember = $this->getSelectedMember(); @endphp
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <button wire:click="backToSlotPick" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Confirm Your Appointment</h2>
                    </div>

                    {{-- Summary card --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-5 space-y-3 text-sm">
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="font-medium">With:</span>
                            <span>{{ $selectedMember?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-medium">Date:</span>
                            <span>{{ Carbon::parse($bookDate)->format('l, F j, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-700 dark:text-gray-200">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/>
                            </svg>
                            <span class="font-medium">Time Sched:</span>
                            @if (!empty($selectedSlots))
                                <span class="px-2.5 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-semibold">
                                    {{ Carbon::createFromFormat('H:i:s', $selectedSlots[0]['start'])->format('g:i A') }}
                                    &ndash;
                                    {{ Carbon::createFromFormat('H:i:s', end($selectedSlots)['end'])->format('g:i A') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea
                                wire:model="bookingNotes"
                                rows="3"
                                placeholder="What is this appointment about? Any special requests?"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"
                            ></textarea>
                            @error('bookingNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Venue / Meeting Link <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input
                                type="text"
                                wire:model="bookingVenue"
                                placeholder="e.g. Zoom link, office address, phone call…"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            />
                            @error('bookingVenue') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button
                        wire:click="confirmBooking"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                        <span wire:loading.remove wire:target="confirmBooking">Confirm Booking</span>
                        <span wire:loading wire:target="confirmBooking">Booking…</span>
                    </button>
                </div>
            @endif
        @endif

        {{-- ══════════════════════════════════════════════════ --}}
        {{--  TAB: MY BOOKINGS                                  --}}
        {{-- ══════════════════════════════════════════════════ --}}
        @if ($activeTab === 'bookings')
            <div class="mb-5 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl text-sm text-green-800 dark:text-green-200">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                    <div>
                        <p class="font-semibold mb-1">Managing your appointments:</p>
                        <ul class="list-disc list-inside space-y-1 text-green-700 dark:text-green-300">
                            <li><strong>Booked by Me</strong> — appointments you've scheduled with others. Cancel pending ones if needed.</li>
                            <li><strong>Booked with Me</strong> — when others book your open slots. You can confirm, complete, or decline.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Sub-tab --}}
            <div class="flex gap-1 mb-5 bg-gray-100 dark:bg-gray-900 rounded-xl p-1 w-full sm:w-auto sm:inline-flex">
                <button
                    wire:click="$set('bookingsSubTab', 'mine')"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg text-sm font-medium transition-colors
                        {{ $bookingsSubTab === 'mine' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                >
                    Booked by Me
                    @php $mineCount = $this->getMyBookings()->count(); @endphp
                    @if ($mineCount > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs">{{ $mineCount }}</span>
                    @endif
                </button>
                <button
                    wire:click="$set('bookingsSubTab', 'with_me')"
                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg text-sm font-medium transition-colors
                        {{ $bookingsSubTab === 'with_me' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                >
                    Booked with Me
                    @php $withMeCount = $this->getBookingsWithMe()->count(); @endphp
                    @if ($withMeCount > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-full text-xs">{{ $withMeCount }}</span>
                    @endif
                </button>
            </div>

            @php
                $appointmentCardClasses = 'bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5';
            @endphp

            {{-- Booked by Me --}}
            @if ($bookingsSubTab === 'mine')
                @php $myBookings = $this->getMyBookings(); @endphp
                @if ($myBookings->isEmpty())
                    <div class="{{ $appointmentCardClasses }} p-10 text-center text-gray-400 dark:text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="font-medium">No appointments yet.</p>
                        <p class="text-sm mt-1">Go to <em>Book Appointment</em> to schedule one.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($myBookings as $appt)
                            <div class="{{ $appointmentCardClasses }}">
                                <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                    <div class="shrink-0">
                                        @if ($appt->host && $appt->host->getFirstMediaUrl('profile'))
                                            <img src="{{ $appt->host->getFirstMediaUrl('profile') }}" class="w-10 h-10 rounded-full object-cover" alt="">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center">
                                                <span class="text-white text-sm font-bold">{{ strtoupper(substr($appt->host?->name ?? '?', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $appt->host?->name ?? 'Unknown' }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $this->statusBadgeClass($appt->status) }}">{{ ucfirst($appt->status) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                            <div>📅 {{ $appt->start_time->format('l, F j, Y') }}</div>
                                            <div>🕐 {{ $appt->start_time->format('g:i A') }} – {{ $appt->end_time->format('g:i A') }}</div>
                                            @if ($appt->venue) <div>📍 {{ $appt->venue }}</div> @endif
                                            @if ($appt->notes) <div class="mt-1 text-gray-600 dark:text-gray-400">💬 {{ $appt->notes }}</div> @endif
                                        </div>
                                        {{-- Evidence thumbnails --}}
                                        @if ($appt->status === 'completed')
                                            @php $evidenceMedia = $appt->getMedia('evidence'); @endphp
                                            @if ($evidenceMedia->isNotEmpty())
                                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                                    @foreach ($evidenceMedia->take(3) as $media)
                                                        <img src="{{ $media->getUrl() }}" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-600" alt="Evidence">
                                                    @endforeach
                                                    <span class="text-[11px] text-gray-400 ml-1">{{ $evidenceMedia->count() }} photo{{ $evidenceMedia->count() !== 1 ? 's' : '' }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="shrink-0 flex flex-wrap gap-2">
                                        @if ($appt->status === 'completed')
                                            <button wire:click="openEvidenceModal({{ $appt->id }}, false)"
                                                class="px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium hover:bg-gray-100 transition-colors border border-gray-200 dark:border-gray-600">
                                                📷 View Evidence
                                            </button>
                                        @elseif ($appt->status === 'pending')
                                            <button
                                                wire:click="openConfirmModal({{ $appt->id }}, 'cancel', 'Cancel Appointment', 'Are you sure you want to cancel this appointment? This cannot be undone.')"
                                                class="px-3 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium hover:bg-red-100 transition-colors border border-red-200 dark:border-red-800"
                                            >Cancel</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            {{-- Booked with Me --}}
            @if ($bookingsSubTab === 'with_me')
                @php $bookingsWithMe = $this->getBookingsWithMe(); @endphp
                @if ($bookingsWithMe->isEmpty())
                    <div class="{{ $appointmentCardClasses }} p-10 text-center text-gray-400 dark:text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <p class="font-medium">No one has booked with you yet.</p>
                        <p class="text-sm mt-1">Make sure your availability is set so others can find open slots.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($bookingsWithMe as $appt)
                            <div class="{{ $appointmentCardClasses }}">
                                <div class="flex flex-col sm:flex-row sm:items-start gap-3">
                                    <div class="shrink-0">
                                        @if ($appt->user && $appt->user->getFirstMediaUrl('profile'))
                                            <img src="{{ $appt->user->getFirstMediaUrl('profile') }}" class="w-10 h-10 rounded-full object-cover" alt="">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center">
                                                <span class="text-white text-sm font-bold">{{ strtoupper(substr($appt->user?->name ?? '?', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $appt->user?->name ?? 'Unknown' }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $this->statusBadgeClass($appt->status) }}">{{ ucfirst($appt->status) }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                                            <div>📅 {{ $appt->start_time->format('l, F j, Y') }}</div>
                                            <div>🕐 {{ $appt->start_time->format('g:i A') }} – {{ $appt->end_time->format('g:i A') }}</div>
                                            @if ($appt->venue) <div>📍 {{ $appt->venue }}</div> @endif
                                            @if ($appt->notes) <div class="mt-1 text-gray-600 dark:text-gray-400">💬 {{ $appt->notes }}</div> @endif
                                        </div>
                                        {{-- Evidence thumbnails --}}
                                        @if ($appt->status === 'completed')
                                            @php $evidenceMedia = $appt->getMedia('evidence'); @endphp
                                            @if ($evidenceMedia->isNotEmpty())
                                                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                                    @foreach ($evidenceMedia->take(3) as $media)
                                                        <img src="{{ $media->getUrl() }}" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-600" alt="Evidence">
                                                    @endforeach
                                                    <span class="text-[11px] text-gray-400 ml-1">{{ $evidenceMedia->count() }} photo{{ $evidenceMedia->count() !== 1 ? 's' : '' }}</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="shrink-0 flex flex-wrap gap-2">
                                        @if ($appt->status === 'pending')
                                            <button wire:click="openConfirmModal({{ $appt->id }}, 'confirm', 'Confirm Appointment', 'Confirm this booking? The member will be notified.')"
                                                class="px-3 py-1.5 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium hover:bg-green-100 transition-colors border border-green-200 dark:border-green-800">
                                                Confirm
                                            </button>
                                            <button wire:click="openConfirmModal({{ $appt->id }}, 'decline', 'Decline Booking', 'Decline this booking request? The member will be notified.')"
                                                class="px-3 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium hover:bg-red-100 transition-colors border border-red-200 dark:border-red-800">
                                                Decline
                                            </button>
                                        @elseif ($appt->status === 'confirmed')
                                            <button wire:click="openCompleteModal({{ $appt->id }})"
                                                class="px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium hover:bg-blue-100 transition-colors border border-blue-200 dark:border-blue-800">
                                                Mark Complete
                                            </button>
                                            <button wire:click="openConfirmModal({{ $appt->id }}, 'cancel', 'Cancel Appointment', 'Cancel this confirmed appointment? This cannot be undone.')"
                                                class="px-3 py-1.5 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium hover:bg-red-100 transition-colors border border-red-200 dark:border-red-800">
                                                Cancel
                                            </button>
                                        @elseif ($appt->status === 'completed')
                                            <button wire:click="openEvidenceModal({{ $appt->id }}, false)"
                                                class="px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium hover:bg-gray-100 transition-colors border border-gray-200 dark:border-gray-600">
                                                📷 View Evidence
                                            </button>
                                            <button wire:click="openEvidenceModal({{ $appt->id }}, true)"
                                                class="px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium hover:bg-amber-100 transition-colors border border-amber-200 dark:border-amber-800">
                                                ✏️ Edit Evidence
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        @endif

    </div>

    {{-- ── Evidence View / Edit Modal ──────────────────────── --}}
    @if ($showEvidenceModal)
        @php
            $evidenceAppt   = $evidenceModalApptId ? Appointment::find($evidenceModalApptId) : null;
            $savedMedia     = $evidenceAppt ? $evidenceAppt->getMedia('evidence') : collect();
            $savedCount     = $savedMedia->count();
            $canAddMore     = ($savedCount - count($pendingDeleteMediaIds) + count($newEvidencePhotos)) < 3;
        @endphp
        <div
            x-data="{ show: false, lightbox: null, lightboxIndex: 0, images: @js($savedMedia->map(fn($m) => $m->getUrl())->values()->toArray()) }"
            x-init="$nextTick(() => show = true)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9997] flex items-center justify-center p-4"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" wire:click="closeEvidenceModal"></div>

            {{-- Modal card --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-xl {{ $evidenceEditMode ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-500' : 'bg-blue-100 dark:bg-blue-900/50 text-blue-500' }} flex items-center justify-center shrink-0">
                        @if ($evidenceEditMode)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 0l.172.172a2 2 0 010 2.828L12 15H9v-3z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ $evidenceEditMode ? 'Edit Evidence Photos' : 'Evidence Photos' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $evidenceEditMode ? 'Remove or add photos. Max 3 total.' : 'Click any photo to browse.' }}
                        </p>
                    </div>
                    @if (!$evidenceEditMode)
                        <button wire:click="openEvidenceModal({{ $evidenceModalApptId }}, true)" class="shrink-0 px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium hover:bg-amber-100 transition-colors border border-amber-200 dark:border-amber-800">
                            ✏️ Edit
                        </button>
                    @endif
                    <button wire:click="closeEvidenceModal" class="shrink-0 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Saved media --}}
                @if ($savedMedia->isEmpty() && empty($newEvidencePhotos))
                    <div class="py-10 text-center text-gray-400 dark:text-gray-500">
                        <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-medium">No evidence photos uploaded yet.</p>
                    </div>
                @else
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        {{-- Existing saved media --}}
                        @foreach ($savedMedia as $media)
                            @php $markedDelete = in_array($media->id, $pendingDeleteMediaIds); @endphp
                            <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700
                                {{ $markedDelete ? 'opacity-40 ring-2 ring-red-400' : '' }}">
                                <img
                                    src="{{ $media->getUrl() }}"
                                    class="w-full h-full object-cover {{ $evidenceEditMode ? '' : 'cursor-pointer' }}"
                                    alt="Evidence"
                                    @if (!$evidenceEditMode) @click="lightboxIndex = {{ $loop->index }}; lightbox = images[{{ $loop->index }}]" @endif
                                />
                                @if (!$evidenceEditMode)
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center pointer-events-none">
                                        <svg class="w-7 h-7 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </div>
                                @endif
                                @if ($evidenceEditMode)
                                    <button
                                        wire:click="toggleDeleteEvidence({{ $media->id }})"
                                        type="button"
                                        class="absolute top-1 right-1 w-6 h-6 rounded-full flex items-center justify-center shadow-md transition-colors
                                            {{ $markedDelete ? 'bg-amber-500 text-white' : 'bg-red-600 text-white opacity-0 group-hover:opacity-100' }}"
                                        title="{{ $markedDelete ? 'Undo remove' : 'Remove' }}"
                                    >
                                        @if ($markedDelete)
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        @endif
                                    </button>
                                    @if ($markedDelete)
                                        <div class="absolute bottom-1 left-1/2 -translate-x-1/2 px-1.5 py-0.5 bg-red-600 text-white text-[9px] font-semibold rounded-md whitespace-nowrap">
                                            Will remove
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach

                        {{-- New uploads preview (edit mode) --}}
                        @if ($evidenceEditMode)
                            @foreach ($newEvidencePhotos as $i => $photo)
                                <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 ring-2 ring-blue-400">
                                    <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover" alt="New evidence {{ $i + 1 }}">
                                    <button
                                        wire:click="removeNewEvidencePhoto({{ $i }})"
                                        type="button"
                                        class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md"
                                        title="Remove"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                    <div class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-blue-600 text-white text-[9px] font-semibold rounded-md">New</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                {{-- Add more photos (edit mode) --}}
                @if ($evidenceEditMode && $canAddMore)
                    <div class="mb-4">
                        <label
                            for="evidence-edit-upload"
                            class="flex items-center justify-center gap-2 w-full h-14 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors text-sm text-gray-500 dark:text-gray-400"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add photos
                        </label>
                        <input type="file" id="evidence-edit-upload" wire:model="newEvidencePhotos" accept="image/*" multiple class="hidden" />
                        @error('newEvidencePhotos.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div wire:loading wire:target="newEvidencePhotos" class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 mb-3">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Uploading…
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-3 pt-2">
                    <button
                        wire:click="closeEvidenceModal"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{ $evidenceEditMode ? 'Cancel' : 'Close' }}
                    </button>
                    @if ($evidenceEditMode)
                        <button
                            wire:click="saveEvidenceEdit"
                            wire:loading.attr="disabled"
                            wire:target="saveEvidenceEdit"
                            @disabled(empty($pendingDeleteMediaIds) && empty($newEvidencePhotos))
                            class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors disabled:opacity-50 flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600"
                        >
                            <span wire:loading.remove wire:target="saveEvidenceEdit">Save Changes</span>
                            <span wire:loading wire:target="saveEvidenceEdit">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Lightbox gallery — placed AFTER modal card so it renders on top --}}
            <div
                x-show="lightbox !== null"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="absolute inset-0 z-20 flex items-center justify-center bg-black/95"
                @click.self="lightbox = null"
                @keydown.escape.window="lightbox = null"
                @keydown.arrow-left.window="if (lightbox !== null) { lightboxIndex = (lightboxIndex - 1 + images.length) % images.length; lightbox = images[lightboxIndex]; }"
                @keydown.arrow-right.window="if (lightbox !== null) { lightboxIndex = (lightboxIndex + 1) % images.length; lightbox = images[lightboxIndex]; }"
            >
                {{-- Close --}}
                <button @click="lightbox = null" class="absolute top-4 right-4 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white/80 hover:text-white transition-colors z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                {{-- Counter --}}
                <div class="absolute top-4 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-medium z-10" x-text="(lightboxIndex + 1) + ' / ' + images.length"></div>

                {{-- Prev --}}
                <button
                    x-show="images.length > 1"
                    @click.stop="lightboxIndex = (lightboxIndex - 1 + images.length) % images.length; lightbox = images[lightboxIndex]"
                    class="absolute left-3 p-2.5 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors z-10"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                {{-- Image --}}
                <img :src="images[lightboxIndex]" class="max-h-[85vh] max-w-[80vw] rounded-xl object-contain shadow-2xl" alt="Evidence full view">

                {{-- Next --}}
                <button
                    x-show="images.length > 1"
                    @click.stop="lightboxIndex = (lightboxIndex + 1) % images.length; lightbox = images[lightboxIndex]"
                    class="absolute right-3 p-2.5 rounded-full bg-white/10 hover:bg-white/25 text-white transition-colors z-10"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Dot indicators --}}
                <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2 z-10" x-show="images.length > 1">
                    <template x-for="(img, i) in images" :key="i">
                        <button
                            @click.stop="lightboxIndex = i; lightbox = images[i]"
                            :class="i === lightboxIndex ? 'bg-white w-5' : 'bg-white/40 w-2'"
                            class="h-2 rounded-full transition-all duration-200"
                        ></button>
                    </template>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Evidence Upload Modal (Complete Appointment) ──── --}}
    @if ($showCompleteModal)
        <div
            x-data="{ show: false }"
            x-init="$nextTick(() => show = true)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9998] flex items-center justify-center p-4"
        >
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeCompleteModal"></div>

            {{-- Modal card --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-500 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4M7 21l-4-4 4-4m6 4H3"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Mark Appointment as Completed</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload 1–3 photos as proof the meeting took place.</p>
                    </div>
                    <button wire:click="closeCompleteModal" class="ml-auto p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Info banner --}}
                <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-amber-800 dark:text-amber-200 flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0"/>
                    </svg>
                    <span>Evidence photos are required before completing. Accepted formats: JPG, PNG, GIF, WebP. Max 5 MB each.</span>
                </div>

                {{-- Upload area --}}
                <div class="mb-4">
                    <label
                        for="evidence-upload"
                        class="flex flex-col items-center justify-center w-full h-28 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors"
                        @class(['opacity-50 cursor-not-allowed pointer-events-none' => count($evidencePhotos) >= 3])
                    >
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            @if (count($evidencePhotos) >= 3)
                                Maximum 3 photos reached
                            @else
                                Click to upload photos
                                <span class="font-normal text-xs ml-1">({{ 3 - count($evidencePhotos) }} remaining)</span>
                            @endif
                        </span>
                    </label>
                    <input
                        type="file"
                        id="evidence-upload"
                        wire:model="evidencePhotos"
                        accept="image/*"
                        multiple
                        class="hidden"
                    />
                    @error('evidencePhotos') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    @error('evidencePhotos.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Uploading indicator --}}
                <div wire:loading wire:target="evidencePhotos" class="flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 mb-3">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Uploading…
                </div>

                {{-- Image previews --}}
                @if (!empty($evidencePhotos))
                    <div class="grid grid-cols-3 gap-2 mb-5">
                        @foreach ($evidencePhotos as $i => $photo)
                            <div class="relative group rounded-xl overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700">
                                <img
                                    src="{{ $photo->temporaryUrl() }}"
                                    class="w-full h-full object-cover"
                                    alt="Evidence {{ $i + 1 }}"
                                />
                                <button
                                    wire:click="removeEvidencePhoto({{ $i }})"
                                    type="button"
                                    class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-md"
                                    title="Remove"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                <div class="absolute bottom-1 left-1 px-1.5 py-0.5 bg-black/50 text-white text-[10px] rounded-md">
                                    {{ $i + 1 }}/3
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-3 pt-1">
                    <button
                        wire:click="closeCompleteModal"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="submitCompleteAppointment"
                        wire:loading.attr="disabled"
                        wire:target="submitCompleteAppointment"
                        @if (empty($evidencePhotos)) disabled @endif
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors disabled:opacity-50 flex items-center justify-center gap-2
                            {{ !empty($evidencePhotos) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-300 dark:bg-blue-800 cursor-not-allowed' }}"
                    >
                        <span wire:loading.remove wire:target="submitCompleteAppointment">
                            <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Complete ({{ count($evidencePhotos) }}/3 photos)
                        </span>
                        <span wire:loading wire:target="submitCompleteAppointment">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Confirm Modal ─────────────────────────────────── --}}
    @if ($showConfirmModal)
        <div
            x-data="{ show: false }"
            x-init="$nextTick(() => show = true)"
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 z-[9998] flex items-center justify-center p-4"
        >
            {{-- Backdrop --}}
            <div
                class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                wire:click="closeConfirmModal"
            ></div>

            {{-- Modal card --}}
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-6"
            >
                {{-- Icon --}}
                @php
                    $iconBg = match($confirmModalAction) {
                        'cancel', 'decline' => 'bg-red-100 dark:bg-red-900/50 text-red-500',
                        'confirm'           => 'bg-green-100 dark:bg-green-900/50 text-green-500',
                        'complete'          => 'bg-blue-100 dark:bg-blue-900/50 text-blue-500',
                        default             => 'bg-gray-100 dark:bg-gray-700 text-gray-500',
                    };
                    $iconPath = match($confirmModalAction) {
                        'cancel', 'decline' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
                        'confirm'           => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0',
                        'complete'          => 'M9 12l2 2 4-4M7 21l-4-4 4-4m6 4H3',
                        default             => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0',
                    };
                    $confirmBtnClass = match($confirmModalAction) {
                        'cancel', 'decline' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
                        'confirm'           => 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
                        'complete'          => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
                        default             => 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-500',
                    };
                    $confirmBtnLabel = match($confirmModalAction) {
                        'cancel'   => 'Yes, cancel it',
                        'decline'  => 'Yes, decline',
                        'confirm'  => 'Yes, confirm',
                        'complete' => 'Mark complete',
                        default    => 'Confirm',
                    };
                @endphp

                <div class="flex justify-center mb-5">
                    <div class="w-16 h-16 rounded-2xl {{ $iconBg }} flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $iconPath }}"/>
                        </svg>
                    </div>
                </div>

                <h3 class="text-center text-lg font-bold text-gray-900 dark:text-white mb-2">
                    {{ $confirmModalTitle }}
                </h3>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-7 leading-relaxed">
                    {{ $confirmModalMessage }}
                </p>

                <div class="flex gap-3">
                    <button
                        wire:click="closeConfirmModal"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        No, go back
                    </button>
                    <button
                        wire:click="executeConfirmAction"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $confirmBtnClass }}"
                    >
                        {{ $confirmBtnLabel }}
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
