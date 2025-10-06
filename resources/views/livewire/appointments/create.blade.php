<?php

use Livewire\Volt\Component;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Mail\AdminAppointmentNotification;
use App\Mail\UserAppointmentConfirmation;
use App\Models\EmailReceiver;
use Illuminate\Support\Facades\Mail;

new class extends Component {
    public $currentDate;
    public $selectedDate = null;
    public $selectedSlot = null;
    public $isSureInvestor = null;
    public $notes = '';
    public $venue = '';
    public $availableSlots = [];
    public $appointments = [];
    public $calendarDays = [];
    public $showBookingModal = false;
    public $showTimeSlots = false;
    public $showInvestorPrompt = false;
    public $agreedToTerms = false;

    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->generateCalendar();
        $this->loadUserAppointments();
    }

    public function generateCalendar()
    {
        $startDate = Carbon::parse($this->currentDate)->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $endDate = Carbon::parse($this->currentDate)->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $this->calendarDays = [];
        $currentDay = $startDate->copy();

        while ($currentDay->lte($endDate)) {
            $this->calendarDays[] = [
                'date' => $currentDay->format('Y-m-d'),
                'day' => $currentDay->format('j'),
                'is_current_month' => $currentDay->format('m') === Carbon::parse($this->currentDate)->format('m'),
                'is_today' => $currentDay->isToday(),
                'has_available_slots' => $this->hasAvailableSlots($currentDay->format('Y-m-d')),
                'is_past' => $currentDay->isPast() && !$currentDay->isToday(),
            ];
            $currentDay->addDay();
        }
    }

    public function hasAvailableSlots($date)
    {
        if (Carbon::parse($date)->isPast() && !Carbon::parse($date)->isToday()) {
            return false;
        }

        return AvailabilitySlot::where('date', $date)->where('is_available', true)->exists();
    }

    public function selectDate($date)
    {
        if (Carbon::parse($date)->isPast() && !Carbon::parse($date)->isToday()) {
            return;
        }

        $this->selectedDate = $date;
        $this->reset(['isSureInvestor', 'selectedSlot', 'notes', 'showTimeSlots', 'showInvestorPrompt', 'agreedToTerms']);
        $this->showBookingModal = true;
    }

    public function setSureInvestor($value)
    {
        if ($value === true) {
            $this->showInvestorPrompt = true;
            $this->reset('agreedToTerms');
        } else {
            $this->isSureInvestor = false;
            $this->loadAvailableSlots($this->selectedDate);
            $this->showTimeSlots = true;
        }
    }

    public function confirmInvestorAgreement()
    {
        if (!$this->agreedToTerms) {
            $this->addError('agreement', 'You must agree to the terms and conditions to continue.');
            return;
        }

        $this->isSureInvestor = true;
        $this->showInvestorPrompt = false;
        $this->loadAvailableSlots($this->selectedDate);
        $this->showTimeSlots = true;
        $this->resetErrorBag();
    }

    public function goBackToQuestion()
    {
        $this->showTimeSlots = false;
        $this->showInvestorPrompt = false;
        $this->reset(['selectedSlot', 'isSureInvestor', 'agreedToTerms']);
        $this->resetErrorBag();
    }

    public function loadAvailableSlots($date)
    {
        $slots = AvailabilitySlot::where('date', $date)->where('is_available', true)->orderBy('start_time')->get();

        $filteredSlots = $slots
            ->filter(function ($slot) use ($date) {
                $startTime = Carbon::parse($slot->start_time)->format('H:i:s');
                $endTime = Carbon::parse($slot->end_time)->format('H:i:s');

                $startDateTime = $date . ' ' . $startTime;
                $endDateTime = $date . ' ' . $endTime;

                return Appointment::isTimeAvailable($startDateTime, $endDateTime);
            })
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'formatted_time' => Carbon::parse($slot->start_time)->format('g:i A') . ' - ' . Carbon::parse($slot->end_time)->format('g:i A'),
                    'start_time_only' => Carbon::parse($slot->start_time)->format('H:i'),
                ];
            });

        if (!$this->isSureInvestor) {
            $filteredSlots = $filteredSlots->filter(function ($slot) {
                $startTime = Carbon::parse($slot['start_time'])->format('H:i');
                return $startTime >= '17:00';
            });
        }

        $this->availableSlots = $filteredSlots->values();
    }

    public function selectSlot($slotId)
    {
        $this->selectedSlot = $slotId;
    }

    // public function bookAppointment()
    // {
    //     if (!$this->selectedDate || !$this->selectedSlot || is_null($this->isSureInvestor)) {
    //         $this->addError('booking', 'Please complete all required steps.');
    //         return;
    //     }

    //     $slot = AvailabilitySlot::find($this->selectedSlot);

    //     $startTime = Carbon::parse($slot->start_time)->format('H:i:s');
    //     $endTime = Carbon::parse($slot->end_time)->format('H:i:s');

    //     $startDateTime = $this->selectedDate . ' ' . $startTime;
    //     $endDateTime = $this->selectedDate . ' ' . $endTime;

    //     if (!Appointment::isTimeAvailable($startDateTime, $endDateTime)) {
    //         $this->addError('booking', 'This time slot is no longer available. Please choose another slot.');
    //         return;
    //     }

    //     try {
    //         Appointment::create([
    //             'user_id' => Auth::id(),
    //             'start_time' => $startDateTime,
    //             'end_time' => $endDateTime,
    //             'notes' => $this->notes,
    //             'is_sure_investor' => $this->isSureInvestor,
    //             'status' => 'pending',
    //         ]);

    //         $this->reset(['selectedSlot', 'notes', 'showBookingModal', 'isSureInvestor', 'showTimeSlots', 'showInvestorPrompt', 'agreedToTerms']);
    //         $this->loadUserAppointments();
    //         $this->generateCalendar();

    //         session()->flash('message', 'Appointment booked successfully!');
    //     } catch (\Exception $e) {
    //         $this->addError('booking', 'An error occurred while booking the appointment. Please try again.');
    //     }
    // }

    public function bookAppointment()
    {
        if (!$this->selectedDate || !$this->selectedSlot || is_null($this->isSureInvestor) || $this->venue == "") {
            $this->addError('booking', 'Please complete all required steps.');
            return;
        }

        $slot = AvailabilitySlot::find($this->selectedSlot);

        $startTime = Carbon::parse($slot->start_time)->format('H:i:s');
        $endTime = Carbon::parse($slot->end_time)->format('H:i:s');

        $startDateTime = $this->selectedDate . ' ' . $startTime;
        $endDateTime = $this->selectedDate . ' ' . $endTime;

        if (!Appointment::isTimeAvailable($startDateTime, $endDateTime)) {
            $this->addError('booking', 'This time slot is no longer available. Please choose another slot.');
            return;
        }

        try {
            $appointment = Appointment::create([
                'user_id' => Auth::id(),
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'notes' => $this->notes,
                'venue' => $this->venue,
                'is_sure_investor' => $this->isSureInvestor,
                'status' => 'pending',
            ]);

            // Send confirmation email to user
            Mail::to(Auth::user()->email)->send(new UserAppointmentConfirmation($appointment));

            // Send notifications to all admin receivers (linked to users)
            $adminReceivers = EmailReceiver::getAppointmentReceivers();

            foreach ($adminReceivers as $receiverData) {
                Mail::to($receiverData['email'])->send(new AdminAppointmentNotification($appointment, $receiverData));
            }

            // Reset form
            $this->reset(['selectedSlot', 'notes', 'showBookingModal', 'isSureInvestor', 'showTimeSlots', 'showInvestorPrompt', 'agreedToTerms']);
            $this->loadUserAppointments();
            $this->generateCalendar();

            session()->flash('message', 'Appointment booked successfully! Confirmation email has been sent.');
        } catch (\Exception $e) {
            \Log::error('Appointment booking failed: ' . $e->getMessage());
            $this->addError('booking', 'An error occurred while booking the appointment. Please try again.');
        }
    }

    public function loadUserAppointments()
    {
        $this->appointments = Auth::user()
            ->appointments()
            ->with('user')
            // ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->start_time->format('Y-m-d'),
                    'formatted_date' => $appointment->start_time->format('M j, Y'),
                    'start_time' => $appointment->start_time->format('g:i A'),
                    'end_time' => $appointment->end_time->format('g:i A'),
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'venue' => $appointment->venue,
                    'booked_by' => $appointment->user->name,
                ];
            })
            ->toArray();
    }

    public function navigateMonth($direction)
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonths($direction)->format('Y-m-d');
        $this->generateCalendar();
    }

    public function goToToday()
    {
        $this->currentDate = now()->format('Y-m-d');
        $this->generateCalendar();
    }
}; ?>

<div class="max-w-12xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Book an Appointment</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Select a date and time for your appointment</p>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div
            class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Calendar Section -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                        {{ \Carbon\Carbon::parse($currentDate)->format('F Y') }}
                    </h2>
                    <div class="flex space-x-2">
                        <button wire:click="goToToday"
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                            Today
                        </button>
                        <div class="flex space-x-1">
                            <button wire:click="navigateMonth(-1)"
                                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5 dark:text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>
                            <button wire:click="navigateMonth(1)"
                                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-5 h-5 dark:text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="p-6">
                    <!-- Week Days Header -->
                    <div class="grid grid-cols-7 gap-1 mb-4">
                        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                            <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">
                                {{ $day }}
                            </div>
                        @endforeach
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7 gap-1">
                        @foreach ($calendarDays as $day)
                            <div class="relative">
                                <button wire:click="selectDate('{{ $day['date'] }}')" @disabled($day['is_past'] || !$day['has_available_slots'])
                                    class="w-full h-20 p-2 text-left border rounded-lg transition-all duration-200
                                        {{ $day['is_today'] ? 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700' : 'border-gray-200 dark:border-gray-700' }}
                                        {{ !$day['is_current_month'] ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100' }}
                                        {{ $day['is_past'] ? 'bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-50' : '' }}
                                        {{ !$day['is_past'] && $day['has_available_slots'] ? 'hover:bg-green-50 dark:hover:bg-green-900 hover:border-green-200 dark:hover:border-green-700 cursor-pointer' : '' }}
                                        {{ !$day['is_past'] && !$day['has_available_slots'] ? 'bg-red-50 dark:bg-red-900 border-red-200 dark:border-red-700 cursor-not-allowed' : '' }}">
                                    <span class="text-sm font-medium">{{ $day['day'] }}</span>

                                    <!-- Availability Indicator -->
                                    @if (!$day['is_past'])
                                        <div class="absolute bottom-1 right-1">
                                            @if ($day['has_available_slots'])
                                                <div class="w-2 h-2 bg-green-500 dark:bg-green-400 rounded-full"
                                                    title="Available slots">
                                                </div>
                                            @else
                                                <div class="w-2 h-2 bg-red-500 dark:bg-red-400 rounded-full"
                                                    title="No available slots">
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Booked Appointments</h3>

                @if (count($appointments) > 0)
                    <div class="space-y-4">
                        @foreach ($appointments as $appointment)
                            <div class="border-l-4 border-blue-500 dark:border-blue-400 pl-4 py-2">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $appointment['formatted_date'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $appointment['start_time'] }} - {{ $appointment['end_time'] }}
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Booked by: {{ $appointment['booked_by'] }}
                                        </p>
                                         <p class="text-sm text-gray-600 dark:text-gray-400">
                                            Venue: {{ $appointment['venue'] }}
                                        </p>
                                        @if ($appointment['notes'])
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $appointment['notes'] }}
                                            </p>
                                        @endif
                                    </div>
                                    <span
                                        class="px-2 py-1 text-xs rounded-full
                                        {{ $appointment['status'] === 'confirmed'
                                            ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
                                            : ($appointment['status'] === 'pending'
                                                ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'
                                                : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200') }}">
                                        {{ ucfirst($appointment['status']) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No book appointments</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    @if ($showBookingModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Book Appointment - {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}
                        </h3>
                        <button wire:click="$set('showBookingModal', false)"
                            class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Error Message -->
                    @error('booking')
                        <div
                            class="mb-4 p-3 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded">
                            {{ $message }}
                        </div>
                    @enderror

                    <!-- Step 1: Sure Investor Question -->
                    @if (!$showTimeSlots && !$showInvestorPrompt)
                        <div class="text-center py-4">
                            <div class="mb-6">
                                <svg class="w-16 h-16 text-blue-500 dark:text-blue-400 mx-auto mb-4" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    What is the purpose of your appointment?
                                </h4>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">
                                    This will determine the available time slots for your appointment.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <button wire:click="setSureInvestor(true)"
                                    class="p-4 border-2 border-green-500 dark:border-green-400 rounded-lg text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-900 hover:bg-green-100 dark:hover:bg-green-800 transition-colors">
                                    <span class="font-semibold">Ready to Invest</span>
                                    <p class="text-sm mt-1 text-green-600 dark:text-green-400">Show all available slots
                                    </p>
                                </button>

                                <button wire:click="setSureInvestor(false)"
                                    class="p-4 border-2 border-blue-500 dark:border-blue-400 rounded-lg text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors">
                                    <span class="font-semibold">For Orientation</span>
                                    <p class="text-sm mt-1 text-blue-600 dark:text-blue-400">Show slots from 5:00 PM
                                        only</p>
                                </button>
                            </div>
                        </div>
                    @elseif($showInvestorPrompt)
                        <!-- Step 1.5: Investor Requirements Prompt -->
                        <div class="py-4">
                            <!-- Back Button -->
                            <button wire:click="goBackToQuestion"
                                class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-4">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to question
                            </button>

                            <div class="mb-6">
                                <div class="flex items-center mb-4">
                                    <svg class="w-8 h-8 text-yellow-500 dark:text-yellow-400 mr-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <h4 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        Important Reminders for Sure Investor Appointments
                                    </h4>
                                </div>

                                <div
                                    class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-4">
                                        Please make sure the following requirements are met before scheduling a Sure
                                        Investor appointment:
                                    </p>

                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-start">
                                            <span
                                                class="text-gray-600 dark:text-gray-400 font-bold mr-2 mt-0.5">1.</span>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>Age Requirement:</strong> Investors must be between 22 to 70
                                                years old only.
                                            </span>
                                        </div>

                                        <div class="flex items-start">
                                            <span
                                                class="text-gray-600 dark:text-gray-400 font-bold mr-2 mt-0.5">2.</span>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>Personal Account Requirement:</strong> The investor must have
                                                their own active bank account or e-wallet under their name (e.g., GCash,
                                                Maya, or any bank).
                                            </span>
                                        </div>

                                        <div class="flex items-start">
                                            <span
                                                class="text-gray-600 dark:text-gray-400 font-bold mr-2 mt-0.5">3.</span>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>E-Cash Availability:</strong> The investment amount must be
                                                available in electronic cash form (e-cash).
                                                <span
                                                    class="block text-red-600 dark:text-red-400 font-semibold mt-1">⚠️
                                                    No e-cash, no investment transaction.</span>
                                            </span>
                                        </div>

                                        <div class="flex items-start">
                                            <span
                                                class="text-gray-600 dark:text-gray-400 font-bold mr-2 mt-0.5">4.</span>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>Valid Identification:</strong> The investor must present at
                                                least one valid government-issued ID (e.g., Driver's License, UMID,
                                                National ID, etc.).
                                            </span>
                                        </div>

                                        <div class="flex items-start">
                                            <span
                                                class="text-gray-600 dark:text-gray-400 font-bold mr-2 mt-0.5">5.</span>
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <strong>Investor Mindset:</strong> Must be open-minded and willing to
                                                take calculated risks in line with investment opportunities.
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Agreement Checkbox -->
                                <div class="mb-6">
                                    <label class="flex items-start space-x-3">
                                        <input type="checkbox" wire:model.live="agreedToTerms"
                                            class="mt-1 rounded border-gray-300 dark:border-gray-600 text-blue-600 dark:text-blue-500 focus:ring-blue-500 dark:focus:ring-blue-400 dark:bg-gray-700">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            I have read and understood all the requirements above, and I confirm that I
                                            meet all the criteria for a Sure Investor appointment.
                                        </span>
                                    </label>
                                    @error('agreement')
                                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Continue Button -->
                                <button wire:click="confirmInvestorAgreement" wire:loading.attr="disabled"
                                    class="w-full bg-green-600 dark:bg-green-500 text-white py-3 px-4 rounded-md hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors disabled:opacity-50"
                                    @if (!$agreedToTerms) disabled @endif>
                                    <span wire:loading.remove>Continue to Time Slots</span>
                                    <span wire:loading>Processing...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Step 2: Time Slots Selection -->
                        @if (count($availableSlots) > 0)
                            <div class="mb-6">
                                <!-- Back Button -->
                                <button wire:click="goBackToQuestion"
                                    class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-4">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back to question
                                </button>

                                <!-- Selection Info -->
                                <div class="mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-semibold">Selection:</span>
                                        {{ $isSureInvestor ? 'Ready to Invest - Showing all available time slots' : 'For Orientation - Showing time slots from 5:00 PM onwards' }}
                                    </p>
                                </div>

                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Select Time Slot
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($availableSlots as $slot)
                                        <button wire:click="selectSlot({{ $slot['id'] }})"
                                            class="p-3 border rounded text-center transition-all duration-200
                                                {{ $selectedSlot == $slot['id']
                                                    ? 'bg-blue-600 dark:bg-blue-500 text-white border-blue-600 dark:border-blue-500'
                                                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                                            {{ $slot['formatted_time'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Venue --}}
                            <div class="mb-6">
                                <label for="venue"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Venue / Location
                                </label>
                                <input type="text" wire:model="venue" id="venue"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    placeholder="Enter venue details...">
                            </div>

                            <!-- Notes -->
                            <div class="mb-6">
                                <label for="notes"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Notes (Optional)
                                </label>
                                <textarea wire:model="notes" id="notes" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    placeholder="Any additional information..."></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button wire:click="bookAppointment" wire:loading.attr="disabled"
                                class="w-full bg-blue-600 dark:bg-blue-500 text-white py-3 px-4 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors disabled:opacity-50">
                                <span wire:loading.remove>Book Appointment</span>
                                <span wire:loading>Booking...</span>
                            </button>
                        @else
                            <div class="text-center py-8">
                                <!-- Back Button -->
                                <button wire:click="goBackToQuestion"
                                    class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-6 mx-auto">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Back to question
                                </button>

                                <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 mb-2">
                                    @if ($isSureInvestor)
                                        No available slots for this date
                                    @else
                                        No available slots from 5:00 PM onwards for this date
                                    @endif
                                </p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    Please select another date or try a different option
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
