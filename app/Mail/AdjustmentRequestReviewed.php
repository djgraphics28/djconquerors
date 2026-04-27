<?php

namespace App\Mail;

use App\Models\AdjustmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdjustmentRequestReviewed extends Mailable
{
    use Queueable, SerializesModels;

    public AdjustmentRequest $adjustmentRequest;

    public function __construct(AdjustmentRequest $adjustmentRequest)
    {
        $this->adjustmentRequest = $adjustmentRequest;
    }

    public function build()
    {
        $status = ucfirst($this->adjustmentRequest->status);
        $subject = $this->adjustmentRequest->isApproved()
            ? '✅ Your Adjustment Request Has Been Approved'
            : '❌ Your Adjustment Request Has Been Rejected';

        return $this->subject($subject)
            ->markdown('emails.adjustment-request-reviewed')
            ->with([
                'adjustmentRequest' => $this->adjustmentRequest,
                'userName' => $this->adjustmentRequest->user->name,
                'status' => $status,
            ]);
    }
}
