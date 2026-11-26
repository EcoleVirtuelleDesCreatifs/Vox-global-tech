<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Services\Diagnostic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DiagnosticController extends Controller
{
    public function index(Diagnostic $d)
    {
        $groups = $d->run();

        return view('admin.diagnostic', ['groups' => $groups, 'score' => $d->score($groups)]);
    }

    public function mail(Request $r)
    {
        try {
            Mail::to($r->user()->email)->send(new VoxMail('Test d’envoi · '.setting('name'), 'Les e-mails fonctionnent !',
                ['Ce message confirme que la configuration d’envoi de '.setting('name').' est opérationnelle.'], null, [], [['Pilote', config('mail.default')], ['Expéditeur', config('mail.from.address')], ['Date', now()->format('d/m/Y H:i')]]));
            ActivityLog::record('a envoyé un e-mail de test');

            return back()->with('ok', 'E-mail de test envoyé à '.$r->user()->email.(config('mail.default') === 'log' ? ' (pilote « log » : consultez storage/logs).' : '.'));
        } catch (Throwable $e) {
            return back()->with('err', 'Échec de l’envoi : '.$e->getMessage());
        }
    }

    public function cache()
    {
        Artisan::call('optimize:clear');
        app(\App\Support\Settings::class)->flush();
        ActivityLog::record('a vidé les caches');

        return back()->with('ok', 'Caches vidés : le site affiche les données les plus récentes.');
    }
}
