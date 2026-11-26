<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi centralisé des e-mails : mis en file d'attente si une file est configurée,
 * et jamais bloquant pour le visiteur (une erreur SMTP est journalisée, pas affichée).
 */
class Notifier
{
    public static function send(string|array|null $to, Mailable $mail): bool
    {
        $to = array_values(array_filter((array) $to));
        if (! $to) {
            return false;
        }
        try {
            if (config('queue.default') !== 'sync') {
                Mail::to($to)->queue($mail);
            } else {
                Mail::to($to)->send($mail);
            }

            return true;
        } catch (Throwable $e) {
            Log::error('Envoi e-mail impossible : '.$e->getMessage(), ['to' => $to, 'mail' => $mail::class]);

            return false;
        }
    }

    /** Adresses de la rédaction : réglage + administrateurs actifs. */
    public static function staff(string $setting = 'email_redaction', array $roles = ['Administrateur', 'Rédacteur en chef']): array
    {
        $emails = \App\Models\User::where('active', true)->whereIn('role', $roles)->pluck('email')->all();
        array_unshift($emails, setting($setting));

        return array_values(array_unique(array_filter($emails)));
    }
}
