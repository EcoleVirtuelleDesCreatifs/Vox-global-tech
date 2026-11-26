<?php

namespace App\Jobs;

use App\Mail\NewsletterIssue;
use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/** Envoie un numéro de la newsletter à tous les abonnés confirmés (par lots, reprise possible). */
class SendNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 3;

    public function __construct(public Newsletter $newsletter) {}

    public function handle(): void
    {
        $nl = $this->newsletter;
        $nl->update(['status' => 'En cours']);
        Subscriber::active()->orderBy('id')->chunkById(200, function ($subs) use ($nl) {
            foreach ($subs as $s) {
                $d = NewsletterDelivery::firstOrCreate(['newsletter_id' => $nl->id, 'subscriber_id' => $s->id], ['token' => Str::random(40)]);
                if ($d->wasRecentlyCreated) {
                    try {
                        Mail::to($s->email)->send(new NewsletterIssue($nl, $d, $s));
                    } catch (Throwable $e) {
                        Log::warning('Newsletter '.$nl->id.' → '.$s->email.' : '.$e->getMessage());
                    }
                }
            }
        });
        $nl->update(['status' => 'Envoyée', 'sent_at' => now(), 'recipients' => $nl->deliveries()->count()]);
    }
}
