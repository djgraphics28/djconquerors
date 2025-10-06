@component('mail::message')
# ✅ Appointment Confirmed

Hello {{ $userName }},

Your appointment has been successfully booked! Here are your appointment details:

@component('mail::panel')
## Appointment Summary
- **Appointment Type:** {{ $appointmentType }}
- **Date:** {{ $appointment->start_time->format('l, F j, Y') }}
- **Time:** {{ $appointment->start_time->format('g:i A') }} - {{ $appointment->end_time->format('g:i A') }}
- **Status:** {{ ucfirst($appointment->status) }}

@if($appointment->notes)
- **Your Notes:** {{ $appointment->notes }}
@endif
@endcomponent

## Important Reminders
@if($appointment->is_sure_investor)
🔔 **For Ready to Invest Appointments:**
- Please bring valid government-issued ID
- Ensure you have your e-cash ready for investment
- Be prepared with your personal bank account details
@else
🔔 **For Orientation Appointments:**
- This session is for information and consultation
- No investment required during orientation
- Come prepared with any questions you may have
@endif

@component('mail::button', ['url' => url('/book-appointment'), 'color' => 'success'])
View My Appointments
@endcomponent

## Need to Reschedule?
If you need to reschedule or cancel your appointment, please contact us at least 24 hours in advance.

Thank you for choosing us!

Best regards,

**The {{ config('app.name') }} Team**
[{{ config('app.url') }}]
@endcomponent
