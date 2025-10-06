<?php

use Livewire\Volt\Component;
use App\Models\Appointment;
use App\Models\User;
use App\Models\AvailabilitySlot;
use Carbon\Carbon;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Calendar properties
    public $currentDate;
    public $viewMode = 'month'; // month, week, day
    public $selectedDate = null;
    public $selectedTime = null;

    // Modal properties
    public $selectedAppointment = null;
    public $showAppointmentModal = false;
    public $showSlotModal = false;
    public $showBulkSlotModal = false;
    public $showCalendarEventModal = false;

    // Form properties
    public $editingSlot = null;
    public $slotForm = [
        'date' => '',
        'start_time' => '',
        'end_time' => '',
        'is_available' => true,
    ];
    public $bulkSlotForm = [
        'start_date' => '',
        'end_date' => '',
        'days_of_week' => [],
        'start_time' => '',
        'end_time' => '',
        'is_available' => true,
    ];

    // Filter properties
    public $filters = [
        'status' => '',
        'date_from' => '',
        'date_to' => '',
        'type' => '',
    ];
    public $search = '';

    // Calendar navigation
    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
        $this->slotForm['date'] = now()->format('Y-m-d');
        $this->slotForm['start_time'] = '09:00';
        $this->slotForm['end_time'] = '10:00';

        $this->bulkSlotForm['start_date'] = now()->format('Y-m-d');
        $this->bulkSlotForm['end_date'] = now()->addDays(7)->format('Y-m-d');
        $this->bulkSlotForm['start_time'] = '09:00';
        $this->bulkSlotForm['end_time'] = '17:00';
        $this->bulkSlotForm['days_of_week'] = [0, 1, 2, 3, 4, 5, 6]; // All days
    }

    // Calendar navigation methods
    public function previousPeriod()
    {
        $date = Carbon::parse($this->currentDate);

        if ($this->viewMode === 'month') {
            $this->currentDate = $date->subMonth()->format('Y-m-d');
        } elseif ($this->viewMode === 'week') {
            $this->currentDate = $date->subWeek()->format('Y-m-d');
        } else {
            $this->currentDate = $date->subDay()->format('Y-m-d');
        }
    }

    public function nextPeriod()
    {
        $date = Carbon::parse($this->currentDate);

        if ($this->viewMode === 'month') {
            $this->currentDate = $date->addMonth()->format('Y-m-d');
        } elseif ($this->viewMode === 'week') {
            $this->currentDate = $date->addWeek()->format('Y-m-d');
        } else {
            $this->currentDate = $date->addDay()->format('Y-m-d');
        }
    }

    public function goToToday()
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function selectDate($date)
    {
        $this->selectedDate = $date;

        if ($this->viewMode === 'month') {
            $this->viewMode = 'day';
        }
    }

    // Computed properties for calendar data
    public function calendarDays()
    {
        $startDate = Carbon::parse($this->currentDate);

        if ($this->viewMode === 'month') {
            // Start from Sunday of the week that contains the first day of month
            $startDate->startOfMonth()->startOfWeek(Carbon::SUNDAY);
            $endDate = Carbon::parse($this->currentDate)->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        } elseif ($this->viewMode === 'week') {
            $startDate->startOfWeek(Carbon::SUNDAY);
            $endDate = Carbon::parse($this->currentDate)->endOfWeek(Carbon::SATURDAY);
        } else {
            $startDate = Carbon::parse($this->selectedDate)->startOfDay();
            $endDate = Carbon::parse($this->selectedDate)->endOfDay();
        }

        $days = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'formatted' => $current->format('j'),
                'isToday' => $current->isToday(),
                'isCurrentMonth' => $this->viewMode !== 'month' || $current->month == Carbon::parse($this->currentDate)->month,
                'appointments' => $this->getAppointmentsForDate($current->format('Y-m-d')),
                'slots' => $this->getSlotsForDate($current->format('Y-m-d')),
            ];
            $current->addDay();
        }

        return $days;
    }

    public function calendarHeaders()
    {
        $headers = [];

        if ($this->viewMode === 'month' || $this->viewMode === 'week') {
            // Start with Sunday
            $current = Carbon::now()->startOfWeek(Carbon::SUNDAY);

            for ($i = 0; $i < 7; $i++) {
                $headers[] = $current->format('D');
                $current->addDay();
            }
        }

        return $headers;
    }

    public function getAppointmentsForDate($date)
    {
        return Appointment::with('user')
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get();
    }

    public function getSlotsForDate($date)
    {
        return AvailabilitySlot::where('date', $date)
            ->orderBy('start_time')
            ->get();
    }

    public function weekHours()
    {
        $hours = [];
        for ($i = 8; $i <= 20; $i++) {
            $hours[] = [
                'hour' => $i,
                'formatted' => $i <= 12 ? $i . ':00 AM' : ($i - 12) . ':00 PM'
            ];
        }
        return $hours;
    }

    // Week view days starting from Sunday
    public function weekViewDays()
    {
        $days = [];
        $startDate = Carbon::parse($this->currentDate)->startOfWeek(Carbon::SUNDAY);

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D'),
                'day_number' => $date->format('j'),
                'is_today' => $date->isToday(),
                'is_selected' => $date->format('Y-m-d') === $this->selectedDate,
            ];
        }

        return $days;
    }

    // Computed properties for lists
    public function appointments()
    {
        return Appointment::with('user')
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filters['status'], function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->filters['date_from'], function ($query, $dateFrom) {
                $query->whereDate('start_time', '>=', $dateFrom);
            })
            ->when($this->filters['date_to'], function ($query, $dateTo) {
                $query->whereDate('start_time', '<=', $dateTo);
            })
            ->when($this->filters['type'] !== '', function ($query) {
                $query->where('is_sure_investor', $this->filters['type']);
            })
            ->orderBy('start_time', 'desc')
            ->paginate(10);
    }

    public function slots()
    {
        return AvailabilitySlot::orderBy('date', 'desc')
            ->orderBy('start_time')
            ->paginate(10, ['*'], 'slotsPage');
    }

    // Appointment methods
    public function viewAppointment($appointmentId)
    {
        $this->selectedAppointment = Appointment::with('user')->findOrFail($appointmentId);
        $this->showAppointmentModal = true;
    }

    public function updateStatus($appointmentId, $status)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if (in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
            $appointment->update(['status' => $status]);

            session()->flash('message', "Appointment {$status} successfully!");
        }
    }

    public function deleteAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->delete();

        session()->flash('message', 'Appointment deleted successfully!');
        $this->showAppointmentModal = false;
    }

    // Availability Slots CRUD
    public function createSlot()
    {
        $this->editingSlot = null;
        $this->slotForm = [
            'date' => $this->selectedDate ?? now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'is_available' => true,
        ];
        $this->showSlotModal = true;
    }

    public function editSlot($slotId)
    {
        $this->editingSlot = AvailabilitySlot::findOrFail($slotId);
        $this->slotForm = [
            'date' => $this->editingSlot->date->format('Y-m-d'),
            'start_time' => $this->editingSlot->start_time,
            'end_time' => $this->editingSlot->end_time,
            'is_available' => $this->editingSlot->is_available,
        ];
        $this->showSlotModal = true;
    }

    public function saveSlot()
    {
        $this->validate([
            'slotForm.date' => 'required|date',
            'slotForm.start_time' => 'required|date_format:H:i',
            'slotForm.end_time' => 'required|date_format:H:i|after:slotForm.start_time',
            'slotForm.is_available' => 'boolean',
        ]);

        try {
            if ($this->editingSlot) {
                $this->editingSlot->update($this->slotForm);
                $message = 'Slot updated successfully!';
            } else {
                AvailabilitySlot::create($this->slotForm);
                $message = 'Slot created successfully!';
            }

            $this->showSlotModal = false;
            $this->reset('editingSlot', 'slotForm');
            session()->flash('message', $message);
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving slot: ' . $e->getMessage());
        }
    }

    public function deleteSlot($slotId)
    {
        $slot = AvailabilitySlot::findOrFail($slotId);

        // Check if slot has any appointments
        $hasAppointments = Appointment::where('start_time', '>=', $slot->date . ' ' . $slot->start_time)
            ->where('end_time', '<=', $slot->date . ' ' . $slot->end_time)
            ->exists();

        if ($hasAppointments) {
            session()->flash('error', 'Cannot delete slot that has existing appointments!');
            return;
        }

        $slot->delete();
        session()->flash('message', 'Slot deleted successfully!');
    }

    public function toggleSlotAvailability($slotId)
    {
        $slot = AvailabilitySlot::findOrFail($slotId);
        $slot->update(['is_available' => !$slot->is_available]);

        session()->flash('message', 'Slot availability updated!');
    }

    // Bulk slot creation
    public function showBulkSlotCreation()
    {
        $this->showBulkSlotModal = true;
    }

    public function createBulkSlots()
    {
        $this->validate([
            'bulkSlotForm.start_date' => 'required|date',
            'bulkSlotForm.end_date' => 'required|date|after_or_equal:bulkSlotForm.start_date',
            'bulkSlotForm.start_time' => 'required|date_format:H:i',
            'bulkSlotForm.end_time' => 'required|date_format:H:i|after:bulkSlotForm.start_time',
            'bulkSlotForm.days_of_week' => 'required|array|min:1',
            'bulkSlotForm.is_available' => 'boolean',
        ]);

        try {
            $startDate = Carbon::parse($this->bulkSlotForm['start_date']);
            $endDate = Carbon::parse($this->bulkSlotForm['end_date']);
            $createdCount = 0;

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if (in_array($date->dayOfWeek, $this->bulkSlotForm['days_of_week'])) {
                    // Check if slot already exists
                    $existingSlot = AvailabilitySlot::where('date', $date->format('Y-m-d'))
                        ->where('start_time', $this->bulkSlotForm['start_time'])
                        ->where('end_time', $this->bulkSlotForm['end_time'])
                        ->first();

                    if (!$existingSlot) {
                        AvailabilitySlot::create([
                            'date' => $date->format('Y-m-d'),
                            'start_time' => $this->bulkSlotForm['start_time'],
                            'end_time' => $this->bulkSlotForm['end_time'],
                            'is_available' => $this->bulkSlotForm['is_available'],
                        ]);
                        $createdCount++;
                    }
                }
            }

            $this->showBulkSlotModal = false;
            session()->flash('message', "Successfully created {$createdCount} slots!");
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating bulk slots: ' . $e->getMessage());
        }
    }

    public function resetFilters()
    {
        $this->reset('filters', 'search');
    }
}; ?>

<div class="py-6">
    <div class="max-w-12xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Appointments Calendar</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Manage appointments and availability in a calendar view</p>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Calendar View -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
                    <!-- Calendar Navigation -->
                    <div class="flex items-center space-x-4">
                        <button wire:click="goToToday" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Today
                        </button>
                        <div class="flex space-x-2">
                            <button wire:click="previousPeriod" class="p-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button wire:click="nextPeriod" class="p-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            @if($viewMode === 'month')
                                {{ Carbon::parse($currentDate)->format('F Y') }}
                            @elseif($viewMode === 'week')
                                Week of {{ Carbon::parse($currentDate)->startOfWeek(Carbon::SUNDAY)->format('M j') }} - {{ Carbon::parse($currentDate)->endOfWeek(Carbon::SATURDAY)->format('M j, Y') }}
                            @else
                                {{ Carbon::parse($selectedDate)->format('l, F j, Y') }}
                            @endif
                        </h2>
                    </div>

                    <!-- View Mode Selector -->
                    <div class="flex space-x-2">
                        <button wire:click="setViewMode('month')" class="px-4 py-2 rounded-lg {{ $viewMode === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            Month
                        </button>
                        <button wire:click="setViewMode('week')" class="px-4 py-2 rounded-lg {{ $viewMode === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            Week
                        </button>
                        <button wire:click="setViewMode('day')" class="px-4 py-2 rounded-lg {{ $viewMode === 'day' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            Day
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-2">
                        <button wire:click="createSlot" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Add Slot
                        </button>
                        <button wire:click="showBulkSlotCreation" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            Bulk Slots
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="p-4">
                @if($viewMode === 'month')
                    <!-- Month View -->
                    <div class="grid grid-cols-7 gap-1">
                        <!-- Headers (Sunday first) -->
                        @foreach($this->calendarHeaders() as $header)
                            <div class="p-2 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $header }}
                            </div>
                        @endforeach

                        <!-- Days -->
                        @foreach($this->calendarDays() as $day)
                            <div
                                class="min-h-24 p-2 border border-gray-200 dark:border-gray-700 {{ !$day['isCurrentMonth'] ? 'bg-gray-50 dark:bg-gray-900' : '' }} {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                                wire:click="selectDate('{{ $day['date'] }}')"
                            >
                                <div class="flex justify-between items-start mb-1">
                                    <span class="text-sm font-medium {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                                        {{ $day['formatted'] }}
                                    </span>
                                    @if(!$day['isCurrentMonth'])
                                        <span class="text-xs text-gray-400">•</span>
                                    @endif
                                </div>

                                <!-- Appointments for this day -->
                                <div class="space-y-1 max-h-20 overflow-y-auto">
                                    @foreach($day['appointments'] as $appointment)
                                        <div
                                            class="text-xs p-1 rounded cursor-pointer
                                                {{ $appointment->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
                                                   ($appointment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' :
                                                   ($appointment->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' :
                                                   'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200')) }}"
                                            wire:click="viewAppointment({{ $appointment->id }})"
                                            wire:key="appointment-{{ $appointment->id }}"
                                        >
                                            <div class="font-medium truncate">
                                                {{ $appointment->user->name }}
                                            </div>
                                            <div class="truncate">
                                                {{ $appointment->start_time->format('g:i A') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Available slots indicator -->
                                @if($day['slots']->where('is_available', true)->count() > 0)
                                    <div class="mt-1 text-xs text-green-600 dark:text-green-400">
                                        {{ $day['slots']->where('is_available', true)->count() }} available
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                @elseif($viewMode === 'week')
                    <!-- Week View -->
                    <div class="grid grid-cols-8 gap-1">
                        <!-- Time column -->
                        <div class="p-2"></div>

                        <!-- Day headers (Sunday first) -->
                        @foreach($this->weekViewDays() as $day)
                            <div
                                class="p-2 text-center border-b border-gray-200 dark:border-gray-700 cursor-pointer
                                    {{ $day['is_today'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}
                                    {{ $day['is_selected'] ? 'ring-2 ring-blue-500' : '' }}"
                                wire:click="selectDate('{{ $day['date'] }}')"
                            >
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $day['day_name'] }}</div>
                                <div class="text-lg font-semibold {{ $day['is_today'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $day['day_number'] }}
                                </div>
                            </div>
                        @endforeach

                        <!-- Time slots -->
                        @foreach($this->weekHours() as $hour)
                            <div class="grid grid-cols-8 gap-1 border-t border-gray-100 dark:border-gray-800">
                                <!-- Time label -->
                                <div class="p-2 text-xs text-gray-500 dark:text-gray-400 text-right pr-4 -mt-2">
                                    {{ $hour['formatted'] }}
                                </div>

                                <!-- Day columns (Sunday first) -->
                                @foreach($this->weekViewDays() as $day)
                                    @php
                                        $currentDateTime = $day['date'] . ' ' . sprintf('%02d:00:00', $hour['hour']);
                                    @endphp
                                    <div class="p-1 min-h-16 border-l border-gray-100 dark:border-gray-800 relative">
                                        <!-- Appointments for this time slot -->
                                        @foreach($this->getAppointmentsForDate($day['date']) as $appointment)
                                            @php
                                                $appointmentHour = $appointment->start_time->hour;
                                                $appointmentEndHour = $appointment->end_time->hour;
                                            @endphp
                                            @if($appointmentHour <= $hour['hour'] && $appointmentEndHour > $hour['hour'])
                                                <div
                                                    class="absolute left-1 right-1 p-1 rounded text-xs cursor-pointer
                                                        {{ $appointment->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700' :
                                                           ($appointment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700' :
                                                           ($appointment->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-700' :
                                                           'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600')) }}"
                                                    wire:click="viewAppointment({{ $appointment->id }})"
                                                    style="top: {{ ($appointment->start_time->minute / 60) * 64 }}px; height: {{ max(32, (($appointment->end_time->diffInMinutes($appointment->start_time)) / 60) * 64) }}px;"
                                                >
                                                    <div class="font-medium truncate">{{ $appointment->user->name }}</div>
                                                    <div class="truncate">{{ $appointment->start_time->format('g:i A') }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                @else
                    <!-- Day View -->
                    <div class="grid grid-cols-2 gap-1">
                        <!-- Time slots -->
                        <div class="space-y-1">
                            @foreach($this->weekHours() as $hour)
                                <div class="grid grid-cols-2 gap-1 border-t border-gray-100 dark:border-gray-800 min-h-16">
                                    <!-- Time label -->
                                    <div class="p-2 text-sm text-gray-500 dark:text-gray-400 text-right pr-4">
                                        {{ $hour['formatted'] }}
                                    </div>

                                    <!-- Appointments for this time slot -->
                                    <div class="p-1 relative">
                                        @foreach($this->getAppointmentsForDate($selectedDate) as $appointment)
                                            @php
                                                $appointmentHour = $appointment->start_time->hour;
                                                $appointmentEndHour = $appointment->end_time->hour;
                                            @endphp
                                            @if($appointmentHour <= $hour['hour'] && $appointmentEndHour > $hour['hour'])
                                                <div
                                                    class="absolute left-1 right-1 p-2 rounded cursor-pointer
                                                        {{ $appointment->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-700' :
                                                           ($appointment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700' :
                                                           ($appointment->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-700' :
                                                           'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600')) }}"
                                                    wire:click="viewAppointment({{ $appointment->id }})"
                                                    style="top: {{ ($appointment->start_time->minute / 60) * 64 }}px; height: {{ max(32, (($appointment->end_time->diffInMinutes($appointment->start_time)) / 60) * 64) }}px;"
                                                >
                                                    <div class="font-medium">{{ $appointment->user->name }}</div>
                                                    <div class="text-sm">{{ $appointment->start_time->format('g:i A') }} - {{ $appointment->end_time->format('g:i A') }}</div>
                                                    <div class="text-xs mt-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                            {{ $appointment->is_sure_investor ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' }}">
                                                            {{ $appointment->is_sure_investor ? 'Sure Investor' : 'Orientation' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Availability slots for the day -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                                Availability for {{ Carbon::parse($selectedDate)->format('l, F j, Y') }}
                            </h3>

                            <div class="space-y-2">
                                @foreach($this->getSlotsForDate($selectedDate) as $slot)
                                    <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} -
                                                {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $slot->is_available ? 'Available' : 'Unavailable' }}
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="editSlot({{ $slot->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                Edit
                                            </button>
                                            <button wire:click="toggleSlotAvailability({{ $slot->id }})" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">
                                                {{ $slot->is_available ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button wire:click="deleteSlot({{ $slot->id }})" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                @endforeach

                                @if($this->getSlotsForDate($selectedDate)->count() === 0)
                                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        No availability slots for this day
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- The rest of your code remains the same for lists and modals -->
        <!-- Lists Section (Collapsible) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Appointments Section -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Appointments List</h2>
                            <div class="flex space-x-2">
                                <!-- Search -->
                                <div class="relative">
                                    <input type="text" wire:model.live="search"
                                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                        placeholder="Search users...">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <select wire:model.live="filters.status"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>

                            <select wire:model.live="filters.type"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white">
                                <option value="">All Types</option>
                                <option value="1">Sure Investor</option>
                                <option value="0">Orientation</option>
                            </select>

                            <input type="date" wire:model.live="filters.date_from"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white"
                                placeholder="From Date">

                            <input type="date" wire:model.live="filters.date_to"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white"
                                placeholder="To Date">
                        </div>

                        @if ($this->filters['status'] || $this->filters['date_from'] || $this->filters['date_to'] || $this->filters['type'] !== '' || $this->search)
                            <div class="mt-2">
                                <button wire:click="resetFilters"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                    Clear Filters
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($this->appointments() as $appointment)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $appointment->user->name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-white">{{ $appointment->start_time->format('M j, Y') }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $appointment->start_time->format('g:i A') }} - {{ $appointment->end_time->format('g:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $appointment->is_sure_investor ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' }}">
                                                {{ $appointment->is_sure_investor ? 'Sure Investor' : 'Orientation' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $appointment->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
                                                   ($appointment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' :
                                                   ($appointment->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' :
                                                   'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200')) }}">
                                                {{ ucfirst($appointment->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button wire:click="viewAppointment({{ $appointment->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                                View
                                            </button>
                                            <div class="inline-block relative group">
                                                <button class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                                    </svg>
                                                </button>
                                                <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                                                    <button wire:click="updateStatus({{ $appointment->id }}, 'confirmed')" class="block w-full text-left px-4 py-2 text-sm text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900">
                                                        Confirm
                                                    </button>
                                                    <button wire:click="updateStatus({{ $appointment->id }}, 'cancelled')" class="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900">
                                                        Cancel
                                                    </button>
                                                    <button wire:click="updateStatus({{ $appointment->id }}, 'completed')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        Complete
                                                    </button>
                                                    <button wire:click="deleteAppointment({{ $appointment->id }})" onclick="return confirm('Are you sure you want to delete this appointment?')" class="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900">
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            No appointments found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $this->appointments()->links() }}
                    </div>
                </div>
            </div>

            <!-- Availability Slots Section -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Availability Slots</h2>
                            <button wire:click="createSlot" class="bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">
                                Add Slot
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($this->slots() as $slot)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $slot->date->format('M j, Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($slot->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('g:i A') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $slot->is_available ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
                                                {{ $slot->is_available ? 'Available' : 'Unavailable' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            <button wire:click="editSlot({{ $slot->id }})" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                                Edit
                                            </button>
                                            <button wire:click="toggleSlotAvailability({{ $slot->id }})" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 mr-3">
                                                {{ $slot->is_available ? 'Disable' : 'Enable' }}
                                            </button>
                                            <button wire:click="deleteSlot({{ $slot->id }})" onclick="return confirm('Are you sure you want to delete this slot?')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                            No availability slots found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $this->slots()->links() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <!-- Appointment Detail Modal -->
        @if ($showAppointmentModal && $selectedAppointment)
            <div class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Appointment Details</h3>
                            <button wire:click="$set('showAppointmentModal', false)" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">User Information</h4>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAppointment->user->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedAppointment->user->email }}</p>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Appointment Time</h4>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAppointment->start_time->format('l, F j, Y') }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedAppointment->start_time->format('g:i A') }} - {{ $selectedAppointment->end_time->format('g:i A') }}</p>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Appointment Type</h4>
                                <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $selectedAppointment->is_sure_investor ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200' }}">
                                    {{ $selectedAppointment->is_sure_investor ? 'Sure Investor' : 'Orientation' }}
                                </span>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h4>
                                <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $selectedAppointment->status === 'confirmed' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' :
                                       ($selectedAppointment->status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' :
                                       ($selectedAppointment->status === 'cancelled' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' :
                                       'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200')) }}">
                                    {{ ucfirst($selectedAppointment->status) }}
                                </span>
                            </div>

                            @if ($selectedAppointment->notes)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</h4>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedAppointment->notes }}</p>
                                </div>
                            @endif

                            <div class="flex space-x-3 pt-4">
                                <button wire:click="updateStatus({{ $selectedAppointment->id }}, 'confirmed')" class="flex-1 bg-green-600 dark:bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-700 dark:hover:bg-green-600">
                                    Confirm
                                </button>
                                <button wire:click="updateStatus({{ $selectedAppointment->id }}, 'cancelled')" class="flex-1 bg-red-600 dark:bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-700 dark:hover:bg-red-600">
                                    Cancel
                                </button>
                                <button wire:click="updateStatus({{ $selectedAppointment->id }}, 'completed')" class="flex-1 bg-gray-600 dark:bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-700 dark:hover:bg-gray-600">
                                    Complete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Slot Modal -->
        @if ($showSlotModal)
            <div class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $editingSlot ? 'Edit Slot' : 'Create New Slot' }}</h3>
                            <button wire:click="$set('showSlotModal', false)" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="saveSlot">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                                    <input type="date" wire:model="slotForm.date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                    @error('slotForm.date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                                        <input type="time" wire:model="slotForm.start_time" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('slotForm.start_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                                        <input type="time" wire:model="slotForm.end_time" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('slotForm.end_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="slotForm.is_available" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Available for booking</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex space-x-3 mt-6">
                                <button type="button" wire:click="$set('showSlotModal', false)" class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 bg-blue-600 dark:bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">
                                    {{ $editingSlot ? 'Update' : 'Create' }} Slot
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Bulk Slot Modal -->
        @if ($showBulkSlotModal)
            <div class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create Bulk Slots</h3>
                            <button wire:click="$set('showBulkSlotModal', false)" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="createBulkSlots">
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                        <input type="date" wire:model="bulkSlotForm.start_date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('bulkSlotForm.start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                                        <input type="date" wire:model="bulkSlotForm.end_date" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('bulkSlotForm.end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                                        <input type="time" wire:model="bulkSlotForm.start_time" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('bulkSlotForm.start_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                                        <input type="time" wire:model="bulkSlotForm.end_time" class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white" required>
                                        @error('bulkSlotForm.end_time') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Days of Week</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        @foreach([
                                            ['value' => 0, 'label' => 'Sun'],
                                            ['value' => 1, 'label' => 'Mon'],
                                            ['value' => 2, 'label' => 'Tue'],
                                            ['value' => 3, 'label' => 'Wed'],
                                            ['value' => 4, 'label' => 'Thu'],
                                            ['value' => 5, 'label' => 'Fri'],
                                            ['value' => 6, 'label' => 'Sat']
                                        ] as $day)
                                            <label class="flex items-center">
                                                <input type="checkbox" wire:model="bulkSlotForm.days_of_week" value="{{ $day['value'] }}" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $day['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('bulkSlotForm.days_of_week') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="bulkSlotForm.is_available" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Available for booking</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex space-x-3 mt-6">
                                <button type="button" wire:click="$set('showBulkSlotModal', false)" class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 bg-purple-600 dark:bg-purple-500 text-white py-2 px-4 rounded-lg hover:bg-purple-700 dark:hover:bg-purple-600">
                                    Create Slots
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
