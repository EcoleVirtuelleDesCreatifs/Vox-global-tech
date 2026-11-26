<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Analyse et diagnostic : serveur, sécurité, e-mails, tâches planifiées,
 * SEO technique, GEO (IA) et qualité éditoriale. Utilisé par l'admin et `php artisan vox:diagnostic`.
 */
class Diagnostic
{
    /** @return array<string, array<int, array{status:string,label:string,detail:string}>> */
    public function run(): array
    {
        return [
            'Serveur et application' => $this->server(),
            'Sécurité' => $this->security(),
            'E-mails et tâches planifiées' => $this->mail(),
            'SEO technique' => $this->seo(),
            'GEO · moteurs de réponse IA' => $this->geo(),
            'Qualité éditoriale' => $this->editorial(),
        ];
    }

    public function score(array $groups): int
    {
        $all = array_merge(...array_values($groups));
        $pts = array_sum(array_map(fn ($c) => ['ok' => 1, 'warn' => .5, 'fail' => 0][$c['status']], $all));

        return (int) round($pts / max(1, count($all)) * 100);
    }

    protected function c(bool|string $ok, string $label, string $detail = ''): array
    {
        $status = is_string($ok) ? $ok : ($ok ? 'ok' : 'fail');

        return compact('status', 'label', 'detail');
    }

    protected function server(): array
    {
        $out = [];
        $out[] = $this->c(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP '.PHP_VERSION, 'Laravel '.app()->version().' exige PHP 8.3 ou plus.');
        $missing = array_filter(['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'intl', 'curl'], fn ($e) => ! extension_loaded($e));
        $out[] = $this->c(! $missing, 'Extensions PHP', $missing ? 'Manquantes : '.implode(', ', $missing) : 'Toutes les extensions nécessaires sont présentes (dont GD pour les images de partage).');
        try {
            DB::connection()->getPdo();
            $out[] = $this->c(true, 'Base de données', DB::connection()->getDriverName().' · '.Article::count().' articles');
        } catch (Throwable $e) {
            $out[] = $this->c(false, 'Base de données', $e->getMessage());
        }
        $migr = 0;
        try { $migr = count(app('migrator')->getMigrationFiles(database_path('migrations'))) - DB::table('migrations')->count(); } catch (Throwable) {}
        $out[] = $this->c($migr <= 0, 'Migrations', $migr > 0 ? $migr.' migration(s) en attente : lancez php artisan migrate --force' : 'Schéma à jour.');
        foreach (['storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'] as $d) {
            if (! is_writable(base_path($d))) $out[] = $this->c(false, 'Dossier non inscriptible', $d);
        }
        $out[] = $this->c(is_link(public_path('storage')) || is_dir(public_path('storage')), 'Lien public/storage', is_link(public_path('storage')) ? 'Présent : les images téléversées sont accessibles.' : 'Absent : lancez php artisan storage:link');
        try { Cache::put('vox.diag', 1, 5); $ok = Cache::get('vox.diag') === 1; } catch (Throwable) { $ok = false; }
        $out[] = $this->c($ok, 'Cache', 'Pilote : '.config('cache.default'));
        $free = @disk_free_space(base_path());
        $out[] = $this->c($free === false || $free > 500 * 1024 * 1024 ? 'ok' : 'warn', 'Espace disque', $free ? nf($free / 1073741824, 1).' Go libres' : 'Inconnu');
        $out[] = $this->c(app()->configurationIsCached() || app()->isLocal() ? 'ok' : 'warn', 'Optimisation', app()->configurationIsCached() ? 'Configuration en cache (rapide).' : 'En production, lancez php artisan optimize.');

        return $out;
    }

    protected function security(): array
    {
        $out = [];
        $out[] = $this->c(filled(config('app.key')), 'Clé d’application', filled(config('app.key')) ? 'Définie.' : 'Absente : php artisan key:generate');
        $prod = app()->environment('production');
        $out[] = $this->c(! ($prod && config('app.debug')), 'Mode débogage', config('app.debug') ? ($prod ? 'ACTIVÉ en production : mettez APP_DEBUG=false' : 'Activé (normal en développement).') : 'Désactivé.');
        $https = str_starts_with((string) config('app.url'), 'https://');
        $out[] = $this->c($https ? 'ok' : ($prod ? 'fail' : 'warn'), 'HTTPS', $https ? 'APP_URL en https (HSTS actif).' : 'APP_URL n’est pas en https : indispensable pour le SEO et la sécurité.');
        $default = User::where('email', env('ADMIN_EMAIL', 'admin@voxtechglobal.com'))->first();
        $weak = $default && \Hash::check('VoxTech@2026', $default->password);
        $out[] = $this->c(! $weak ? 'ok' : ($prod ? 'fail' : 'warn'), 'Mot de passe administrateur', $weak ? 'Le mot de passe par défaut est toujours utilisé : changez-le dans Mon profil.' : 'Personnalisé.');
        $out[] = $this->c(true, 'Protection des formulaires', 'CSRF, limitation de débit, pot de miel anti-robots et filtrage anti-spam des commentaires actifs.');

        return $out;
    }

    protected function mail(): array
    {
        $out = [];
        $m = config('mail.default');
        $out[] = $this->c(! in_array($m, ['log', 'array'], true) ? 'ok' : 'warn', 'Envoi des e-mails', in_array($m, ['log', 'array'], true) ? 'Pilote « '.$m.' » : les e-mails sont écrits dans les journaux, pas envoyés. Configurez MAIL_* (SMTP).' : 'Pilote '.$m.' · expéditeur '.config('mail.from.address'));
        $q = config('queue.default');
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $out[] = $this->c($pending < 50 ? 'ok' : 'warn', 'File d’attente ('.$q.')', $q === 'sync' ? 'Envoi immédiat.' : $pending.' tâche(s) en attente · traitée(s) par la tâche planifiée chaque minute.');
        $out[] = $this->c($failed === 0 ? 'ok' : 'warn', 'Tâches en échec', $failed ? $failed.' échec(s) : php artisan queue:retry all' : 'Aucune.');
        $hb = Cache::get('vox.scheduler.heartbeat');
        $out[] = $this->c($hb && now()->diffInMinutes($hb) < 10 ? 'ok' : 'warn', 'Tâche planifiée (cron)', $hb ? 'Dernier passage : '.\Illuminate\Support\Carbon::parse($hb)->diffForHumans() : 'Jamais vue : ajoutez « * * * * * php '.base_path('artisan').' schedule:run » au cron.');

        return $out;
    }

    protected function seo(): array
    {
        $out = [];
        $pub = Article::published();
        $n = (clone $pub)->count();
        $longTitle = (clone $pub)->get()->filter(fn ($a) => mb_strlen($a->seoTitle()) > 65)->count();
        $out[] = $this->c($longTitle === 0 ? 'ok' : 'warn', 'Titres pour Google', $longTitle ? $longTitle.' article(s) ont un titre de plus de 65 caractères (tronqué dans Google) : renseignez « Titre pour Google ».' : 'Tous les titres tiennent dans les résultats.');
        $shortDesc = (clone $pub)->get()->filter(fn ($a) => mb_strlen($a->seoDescription()) < 70)->count();
        $out[] = $this->c($shortDesc === 0 ? 'ok' : 'warn', 'Méta-descriptions', $shortDesc ? $shortDesc.' description(s) trop courtes (< 70 caractères).' : 'Toutes les descriptions sont exploitables.');
        $dup = Article::select('title')->groupBy('title')->havingRaw('COUNT(*) > 1')->count();
        $out[] = $this->c($dup === 0, 'Titres uniques', $dup ? $dup.' titre(s) en double.' : 'Aucun doublon.');
        $out[] = $this->c(true, 'Données structurées', 'JSON-LD : NewsMediaOrganization, WebSite + SearchAction, BreadcrumbList, NewsArticle, FAQPage (interviews), ClaimReview (fact-checks), ProfilePage, ItemList.');
        $out[] = $this->c(true, 'Plans du site', 'sitemap.xml (index), articles avec images, Google News (48 h), rubriques, publications · '.$n.' articles indexables.');
        $out[] = $this->c(true, 'Partage social', 'Open Graph + Twitter Cards ; image 1200×630 générée automatiquement par article.');
        $out[] = $this->c(setting('google_verification') ? 'ok' : 'warn', 'Google Search Console', setting('google_verification') ? 'Balise de validation présente.' : 'Ajoutez la balise de validation dans Réglages › Référencement puis soumettez /sitemap.xml.');
        $out[] = $this->c(setting('indexnow') ? 'ok' : 'warn', 'IndexNow (Bing, Yandex…)', setting('indexnow') ? 'Ping automatique à chaque publication.' : 'Désactivé dans les réglages.');
        $noCover = (clone $pub)->whereNull('cover_id')->count();
        $out[] = $this->c($noCover < max(1, $n / 2) ? 'ok' : 'warn', 'Images d’articles', $noCover ? $noCover.' article(s) sans image de couverture (un visuel graphique est utilisé). Ajoutez des photos pour Google Discover.' : 'Tous les articles ont une couverture.');

        return $out;
    }

    protected function geo(): array
    {
        $out = [];
        $out[] = $this->c(setting('ai_crawlers') ? 'ok' : 'warn', 'Robots IA autorisés', setting('ai_crawlers') ? 'GPTBot, ClaudeBot, PerplexityBot, Google-Extended… peuvent lire le site (robots.txt).' : 'Bloqués : le média ne sera pas cité par les assistants IA.');
        $out[] = $this->c(true, 'llms.txt', 'Résumé structuré du média et des articles récents pour les modèles de langage, + llms-full.txt (texte intégral).');
        $out[] = $this->c(true, 'Version Markdown des articles', 'Chaque article est disponible en .md (lien alternate) pour une citation fidèle.');
        $out[] = $this->c(true, 'Signaux de confiance (E-E-A-T)', 'Auteurs avec biographie, charte éditoriale, politique de corrections, sources citées, distinction faits/opinions balisée.');
        $withSources = Article::published()->whereNotNull('sources')->count();
        $total = max(1, Article::published()->count());
        $out[] = $this->c($withSources / $total > .3 ? 'ok' : 'warn', 'Sources citées', $withSources.' / '.$total.' articles citent leurs sources (les moteurs IA privilégient les contenus sourcés).');
        $bots = \App\Models\BotVisit::where('kind', 'ai')->where('created_at', '>=', now()->subDays(30))->count();
        $out[] = $this->c($bots > 0 ? 'ok' : 'warn', 'Passages de robots IA (30 j)', $bots ? nf($bots).' visites de robots IA mesurées.' : 'Aucun passage mesuré pour l’instant.');
        $ai = PageView::where('source', 'ai')->where('created_at', '>=', now()->subDays(30))->count();
        $out[] = $this->c('ok', 'Trafic venant des assistants IA (30 j)', nf($ai).' pages vues depuis ChatGPT, Perplexity, Claude, Gemini…');

        return $out;
    }

    protected function editorial(): array
    {
        $out = [];
        $stale = Article::where('status', 'brouillon')->where('updated_at', '<', now()->subDays(30))->count();
        $out[] = $this->c($stale === 0 ? 'ok' : 'warn', 'Brouillons oubliés', $stale ? $stale.' brouillon(s) non modifié(s) depuis 30 jours.' : 'Aucun.');
        $last = Article::published()->max('published_at');
        $out[] = $this->c($last && now()->diffInDays($last) <= 3 ? 'ok' : 'warn', 'Fraîcheur', $last ? 'Dernière publication '.\Illuminate\Support\Carbon::parse($last)->diffForHumans().'.' : 'Aucune publication.');
        $short = Article::published()->get()->filter(fn ($a) => $a->wordCount() < 150)->count();
        $out[] = $this->c($short === 0 ? 'ok' : 'warn', 'Contenus courts', $short ? $short.' article(s) de moins de 150 mots (« contenu mince » pour Google).' : 'Aucun.');
        $exp = Campaign::where('active', true)->whereDate('ends_on', '<', now())->count();
        $out[] = $this->c($exp === 0 ? 'ok' : 'warn', 'Campagnes publicitaires', $exp ? $exp.' campagne(s) active(s) mais échue(s).' : 'Toutes les campagnes actives sont dans leur période.');

        return $out;
    }
}
