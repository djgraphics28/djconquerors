<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $newLabel = ucwords(str_replace('_', ' ', $this->ticket->status));
        $oldLabel = ucwords(str_replace('_', ' ', $this->oldStatus));

        return (new MailMessage)
            ->subject("Ticket Update: {$this->ticket->ticket_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your support ticket status has been updated.")
            ->line("**Ticket:** {$this->ticket->ticket_number}")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Status:** {$oldLabel} → {$newLabel}")
            ->action('View Ticket', route('tickets.show', $this->ticket->id))
            ->line('Thank you for your patience.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject'       => $this->ticket->subject,
            'old_status'    => $this->oldStatus,
            'new_status'    => $this->ticket->status,
            'message'       => "Ticket {$this->ticket->ticket_number} status changed to " . ucwords(str_replace('_', ' ', $this->ticket->status)) . '.',
            'type'          => 'ticket_status_updated',
            'url'           => route('tickets.show', $this->ticket->id),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
