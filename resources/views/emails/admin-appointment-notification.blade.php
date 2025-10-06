@component('mail::message')
# 📅 New Appointment Booking

Hello {{ $adminName }},

A new appointment has been scheduled with the following details:

@component('mail::panel')
## Appointment Details
- **Client Name:** {{ $appointment->user->name }}
- **Client Email:** {{ $appointment->user->email }}
- **Appointment Type:** {{ $appointmentType }}
- **Date:** {{ $appointment->start_time->format('l, F j, Y') }}
- **Time:** {{ $appointment->start_time->format('g:i A') }} - {{ $appointment->end_time->format('g:i A') }}
- **Status:** {{ ucfirst($appointment->status) }}

@if($appointment->notes)
- **Notes:** {{ $appointment->notes }}
@endif
@endcomponent

## Client Information
- **Name:** {{ $appointment->user->name }}
- **Email:** {{ $appointment->user->email }}
- **Booking Date:** {{ $appointment->created_at->format('M j, Y \a\t g:i A') }}

@component('mail::button', ['url' => url('appointments/' . $appointment->id), 'color' => 'primary'])
View Appointment Details
@endcomponent

Thank you for using our booking system.

Regards,
{{ config('app.name') }}
@endcomponent
