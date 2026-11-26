<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail transactionnel générique aux couleurs de VOX TECH GLOBAL
 * (accusés de réception, alertes rédaction, invitations, rapports…).
 */
class VoxMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $heading,
        public array $lines = [],
        public ?array $button = null,      // ['label' => '', 'url' => '']
        public array $outro = [],
        public array $table = [],          // [['Libellé', 'Valeur'], …]
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: $this->replyToEmail ? [new Address($this->replyToEmail, $this->replyToName)] : [],
            tags: ['transactionnel'],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.vox');
    }
}
