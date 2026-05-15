<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $inviteLink;
    public string $memberName;
    public string $role;

    public function __construct(string $inviteLink, string $memberName, string $role)
    {
        $this->inviteLink  = $inviteLink;
        $this->memberName  = $memberName;
        $this->role        = $role;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to Monday Clone!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invite',
        );
    }
}
