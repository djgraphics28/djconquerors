<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ManagerCongratulations extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public int $newLevel;
    public bool $isUpgrade;

    public function __construct(User $user, int $newLevel, bool $isUpgrade)
    {
        $this->user = $user;
        $this->newLevel = $newLevel;
        $this->isUpgrade = $isUpgrade;
    }

    public function envelope(): Envelope
    {
        $subject = $this->isUpgrade
            ? "🎉 Congratulations! You've been promoted to Manager Level {$this->newLevel}"
            : "🎉 Congratulations! You are now a Manager Level {$this->newLevel}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.manager-congratulations',
            with: [
                'user'     => $this->user,
                'newLevel' => $this->newLevel,
                'isUpgrade' => $this->isUpgrade,
            ],
        );
    }
}
