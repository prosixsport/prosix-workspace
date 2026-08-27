<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientAccountStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $client,
        public string $state
    ) {
    }

    public function build(): self
    {
        $subjects = [
            'added' => 'You have been added to Prosix CRM',
            'pending' => 'Prosix CRM account request received',
            'approved' => 'Your Prosix CRM account is approved',
            'rejected' => 'Prosix CRM account status update',
        ];

        return $this
            ->subject($subjects[$this->state] ?? 'Prosix CRM')
            ->view('emails.client-account-status');
    }
}
