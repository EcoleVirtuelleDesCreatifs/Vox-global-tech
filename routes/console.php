<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\BotVisit;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\PageView;
use App\Models\SearchLog;
use App\Models\Submission;
use App\Models\Subscriber;
use App\Services\Analytics;
use App\Services\Diagnostic;
use App\Services\Notifier;
use App\Support\SiteCache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Commandes VOX TECH GLOBAL
|--------------------------------------------------------------------------
| Une seule tâche cron suffit en production :
|   * * * * * cd /chemin/du/site && php artisan schedule:run >> /dev/null 2>&1
*/

Artisan::command('vox:publish-scheduled', function () {
    $due = Article::where('status', 'programmé')->where('published_at', '<=', now())->get();
    foreach ($due as $a) {
        $a->update(['status' => 'publié']);
        ArticleController::afterPublish($a);
        ActivityLog::record('a mis en ligne l’article programmé « '.$a->title.' »');
    }
    if ($due->count()) {
        SiteCache::flush();
    }
    $this->info($due->count().' article(s) programmé(s) mis en ligne.');
})->purpose('Met en ligne les articles programmés arrivés à échéance (IndexNow + e-mail à l’auteur)');

Artisan::command('vox:campaigns', function () {
    $today = now()->startOfDay();
    // Alerte 3 jours avant la fin
    $ending = Campaign::with('adSlot')->where('active', true)->whereNull('expiry_notified_at')
        ->whereDate('ends_on', '>=', $today)->whereDate('ends_on', '<=', $today->copy()->addDays(3))->get();
    foreach ($ending as $c) {
        Notifier::send(Notifier::staff('email_partners', ['Administrateur', 'Commercial']), new VoxMail(
            'Fin de campagne le '.$c->ends_on->format('d/m') .' · '.$c->advertiser,
            'Une campagne publicitaire se termine bientôt',
            ['La campagne de **'.e($c->advertiser).'** sur l’emplacement « '.e($c->adSlot?->name ?? $c->slot).' » se termine le **'.$c->ends_on->format('d/m/Y').'**.', 'C’est le bon moment pour proposer un renouvellement à l’annonceur.'],
            ['label' => 'Gérer les publicités', 'url' => route('admin.ads')], [],
            [['Impressions', nf($c->impressions)], ['Clics', nf($c->clicks)], ['CTR', str_replace('.', ',', (string) $c->ctr()).' %'], ['Budget', fcfa($c->budget)]],
        ));
        $c->forceFill(['expiry_notified_at' => now()])->save();
    }
    // Désactivation des campagnes échues
    $expired = Campaign::where('active', true)->whereDate('ends_on', '<', $today)->update(['active' => false]);
    if ($expired) {
        SiteCache::flush();
        ActivityLog::record('a mis en pause '.$expired.' campagne(s) arrivée(s) à échéance');
    }
    $this->info($ending->count().' alerte(s), '.$expired.' campagne(s) désactivée(s).');
})->purpose('Alerte la régie avant la fin des campagnes et désactive les campagnes échues');

Artisan::command('vox:weekly-report {--force : Envoyer même si le réglage est désactivé}', function (Analytics $an) {
    if (! setting('weekly_report') && ! $this->option('force')) {
        return $this->info('Rapport hebdomadaire désactivé dans les réglages.');
    }
    $s = $an->summary(7);
    $top = $an->topArticles(7, 5);
    $ai = array_sum(array_column($an->bots(7, 'ai'), 'n'));
    $d = fn ($v) => $v === null ? '' : ' ('.($v >= 0 ? '+' : '').$v.' %)';
    $lines = ['Voici l’audience de '.setting('name').' sur les 7 derniers jours.'];
    if ($top->count()) {
        $lines[] = '**Articles les plus lus :**';
        foreach ($top as $i => $t) {
            $lines[] = ($i + 1).'. '.e($t['article']->title).' — '.nf($t['n']).' lectures';
        }
    }
    $sent = Notifier::send(Notifier::staff('email_redaction', ['Administrateur', 'Rédacteur en chef']), new VoxMail(
        'Rapport d’audience hebdomadaire · '.setting('name'), 'Votre semaine en chiffres', $lines,
        ['label' => 'Ouvrir les statistiques', 'url' => route('admin.stats')], [],
        [['Visites', nf($s['visits']).$d($s['delta_visits'])], ['Visiteurs uniques', nf($s['uniques']).$d($s['delta_uniques'])],
            ['Pages vues', nf($s['pages']).$d($s['delta_pages'])], ['Taux de rebond', $s['bounce'].' %'],
            ['Passages de robots IA', nf($ai)], ['Nouveaux abonnés', nf(Subscriber::active()->where('confirmed_at', '>=', now()->subDays(7))->count())],
            ['Commentaires à modérer', nf(Comment::where('status', 'en attente')->count())], ['Propositions à traiter', nf(Submission::where('status', 'Nouvelle')->count())]],
    ));
    $this->info($sent ? 'Rapport envoyé.' : 'Aucun destinataire ou envoi impossible.');
})->purpose('Envoie le rapport d’audience hebdomadaire à la rédaction');

Artisan::command('vox:prune', function () {
    $pv = PageView::where('created_at', '<', now()->subMonths(25))->delete();
    $bv = BotVisit::where('created_at', '<', now()->subMonths(13))->delete();
    $sl = SearchLog::where('created_at', '<', now()->subMonths(13))->delete();
    $ns = Subscriber::whereNull('confirmed_at')->where('created_at', '<', now()->subDays(30))->delete();
    $this->info("Purge : $pv pages vues, $bv passages de robots, $sl recherches, $ns inscriptions jamais confirmées.");
})->purpose('Purge RGPD : statistiques anciennes et inscriptions non confirmées');

Artisan::command('vox:diagnostic', function (Diagnostic $d) {
    $groups = $d->run();
    foreach ($groups as $title => $checks) {
        $this->line('');
        $this->line('<options=bold>'.$title.'</>');
        foreach ($checks as $c) {
            $icon = ['ok' => '<fg=green>✔</>', 'warn' => '<fg=yellow>!</>', 'fail' => '<fg=red>✘</>'][$c['status']] ?? '·';
            $this->line(" $icon {$c['label']} — {$c['detail']}");
        }
    }
    $this->line('');
    $this->info('Score global : '.$d->score($groups).' / 100');
})->purpose('Diagnostic technique, SEO et GEO du site en ligne de commande');

/*
|--------------------------------------------------------------------------
| Planification
|--------------------------------------------------------------------------
*/
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('vox:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('vox:campaigns')->dailyAt('07:30');
Schedule::command('vox:weekly-report')->weeklyOn(1, '08:00');
Schedule::command('vox:prune')->dailyAt('03:15');
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
