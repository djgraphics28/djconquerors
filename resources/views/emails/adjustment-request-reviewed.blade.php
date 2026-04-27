@component('mail::message')
# {{ $adjustmentRequest->isApproved() ? '✅ Adjustment Request Approved' : '❌ Adjustment Request Rejected' }}

Hello {{ $userName }},

Your adjustment request has been **{{ $status }}** by our admin team.

@component('mail::panel')
## Request Details

@if ($adjustmentRequest->correct_name)
- **Name Change:** {{ $adjustmentRequest->correct_name }}
@endif
@if ($adjustmentRequest->correct_riscoin_id)
- **Riscoin ID Change:** {{ $adjustmentRequest->correct_riscoin_id }}
@endif
@if ($adjustmentRequest->correct_inviters_code)
- **Inviter Code Change:** {{ $adjustmentRequest->correct_inviters_code }}
@endif
@if ($adjustmentRequest->correctAssistant)
- **Assister Change:** {{ $adjustmentRequest->correctAssistant->name }}
@endif
@if ($adjustmentRequest->notes)
- **Your Notes:** {{ $adjustmentRequest->notes }}
@endif
@endcomponent

@if ($adjustmentRequest->isApproved())
@component('mail::panel')
## What Was Updated

Your account information has been updated according to your request. The changes are now live on your profile.
@endcomponent

@component('mail::button', ['url' => url('/settings/profile'), 'color' => 'success'])
View My Profile
@endcomponent
@else
@component('mail::panel')
## Reason for Rejection

{{ $adjustmentRequest->admin_notes ?? 'No reason provided. Please contact support for more information.' }}
@endcomponent

@component('mail::button', ['url' => url('/settings/adjustment-request'), 'color' => 'error'])
Submit a New Request
@endcomponent
@endif

If you have any questions, please don't hesitate to reach out to our support team.

Best regards,

**The {{ config('app.name') }} Team**
@endcomponent
