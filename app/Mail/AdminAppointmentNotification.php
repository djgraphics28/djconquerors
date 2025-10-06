<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminAppointmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;
    public $receiver;
    public $adminName;

    public function __construct(Appointment $appointment, $receiverData = null)
    {
        $this->appointment = $appointment;

        if ($receiverData && isset($receiverData['user_id'])) {
            // If we have a user_id, we can fetch the full user data
            $this->receiver = User::find($receiverData['user_id']);
            $this->adminName = $this->receiver ? $this->receiver->name : 'Admin';
        } else {
            $this->receiver = null;
            $this->adminName = $receiverData['name'] ?? 'Admin';
        }
    }

    public function build()
    {
        return $this->subject('📅 New Appointment Booking - ' . $this->appointment->user->name)
                    ->markdown('emails.admin-appointment-notification')
                    ->with([
                        'appointment' => $this->appointment,
                        'adminName' => $this->adminName,
                        'appointmentType' => $this->appointment->is_sure_investor ? 'Ready to Invest' : 'Orientation',
                        'receiver' => $this->receiver,
                    ]);
    }
}
