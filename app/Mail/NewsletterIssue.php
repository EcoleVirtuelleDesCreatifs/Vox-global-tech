<?php

namespace App\Mail;

use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/** Numéro de la Newsletter VOX, avec suivi d'ouverture, de clics et désinscription en un clic (RFC 8058). */
class NewsletterIssue extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Newsletter $newsletter, public ?NewsletterDelivery $delivery = null, public ?Subscriber $subscriber = null, public bool $test = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->test ? '[TEST] ' : '').$this->newsletter->subject, tags: ['newsletter']);
    }

    public function headers(): Headers
    {
        if (! $this->subscriber) {
            return new Headers;
        }
        $url = route('newsletter.unsubscribe', $this->subscriber->token);

        return new Headers(text: ['List-Unsubscribe' => '<'.$url.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click']);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.newsletter', text: 'mail.newsletter-text', with: [
            'articles' => $this->newsletter->articles(),
            'track' => fn (string $url) => $this->delivery ? route('nl.click', [$this->delivery->token, 'u' => $url]) : $url,
            'pixel' => $this->delivery ? route('nl.open', $this->delivery->token) : null,
            'unsubscribe' => $this->subscriber ? route('newsletter.unsubscribe', $this->subscriber->token) : route('newsletter'),
            'sponsor' => setting('ads_on') ? \App\Models\Campaign::running()->where('slot', 'newsletter')->first() : null,
        ]);
    }
}
