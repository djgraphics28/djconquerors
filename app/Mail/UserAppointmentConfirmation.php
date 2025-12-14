<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserAppointmentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $userName;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
        $this->userName = $appointment->user->name;
    }

    public function build()
    {
        $appointmentType = $this->appointment->is_sure_investor ? 'Ready to Invest' : 'Orientation';

        return $this->subject('✅ Appointment Confirmed - ' . $this->appointment->start_time->format('M j, Y'))
                    ->markdown('emails.user-appointment-confirmation')
                    ->with([
                        'appointment' => $this->appointment,
                        'userName' => $this->userName,
                        'appointmentType' => $appointmentType,
                    ]);
    }
}
