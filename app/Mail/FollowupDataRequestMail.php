<?php

namespace App\Mail;

use App\Models\DataRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FollowupDataRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DataRequest $dataRequest,
        public string $clientName,
        public string $kapName,
        public int $daysOverdue,
        public int $followupLevel,
    ) {}

    public function envelope(): Envelope
    {
        $levelLabel = $this->followupLevel === 2 ? 'Follow-up Kedua (15 Hari)' : 'Follow-up Pertama (7 Hari)';

        return new Envelope(
            subject: $levelLabel . ' - Data Audit Belum Diterima — ' . $this->dataRequest->section,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.followup-reminder',
        );
    }
}
