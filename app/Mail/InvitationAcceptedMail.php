<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public User $acceptedUser,
        public string $recipientType = 'accepted_user',
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->recipientType) {
            'inviter' => 'Undangan WebAudit Telah Diterima',
            'super_admin' => 'Update Akses User dari Invitation WebAudit',
            default => 'Akses WebAudit Anda Sudah Aktif',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation-accepted');
    }
}
