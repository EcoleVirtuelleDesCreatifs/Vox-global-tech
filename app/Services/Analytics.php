<?php

namespace App\Services;

use App\Models\Article;
use App\Models\BotVisit;
use App\Models\PageView;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Mesure d'audience intégrée, respectueuse de la vie privée :
 * pas de cookie, IP jamais stockée (hachage quotidien salé), robots IA et moteurs suivis à part (GEO).
 */
class Analytics
{
    /** Robots connus : nom => type (ai = moteurs de réponse / LLM, search = moteurs classiques). */
    public const BOTS = [
        'GPTBot' => 'ai', 'ChatGPT-User' => 'ai', 'OAI-SearchBot' => 'ai', 'ClaudeBot' => 'ai', 'Claude-User' => 'ai',
        'Claude-SearchBot' => 'ai', 'anthropic-ai' => 'ai', 'PerplexityBot' => 'ai', 'Perplexity-User' => 'ai',
        'Google-Extended' => 'ai', 'Google-CloudVertexBot' => 'ai', 'Gemini' => 'ai', 'Applebot-Extended' => 'ai', 'CCBot' => 'ai',
        'cohere-ai' => 'ai', 'MistralAI-User' => 'ai', 'meta-externalagent' => 'ai', 'Meta-ExternalFetcher' => 'ai', 'Bytespider' => 'ai',
        'Amazonbot' => 'ai', 'YouBot' => 'ai', 'DuckAssistBot' => 'ai', 'Diffbot' => 'ai', 'PetalBot' => 'ai',
        'Googlebot' => 'search', 'bingbot' => 'search', 'Applebot' => 'search', 'YandexBot' => 'search', 'DuckDuckBot' => 'search',
        'Baiduspider' => 'search', 'Qwantbot' => 'search', 'SeznamBot' => 'search', 'Ecosia' => 'search',
        'facebookexternalhit' => 'social', 'Twitterbot' => 'social', 'LinkedInBot' => 'social', 'WhatsApp' => 'social',
        'TelegramBot' => 'social', 'Slackbot' => 'social', 'Discordbot' => 'social', 'Pinterest' => 'social',
        'AhrefsBot' => 'seo', 'SemrushBot' => 'seo', 'MJ12bot' => 'seo', 'DotBot' => 'seo', 'Screaming Frog' => 'seo',
    ];

    public const SEARCH_ENGINES = ['google.', 'bing.', 'yahoo.', 'duckduckgo.', 'qwant.', 'ecosia.', 'yandex.', 'baidu.', 'brave.com', 'startpage.'];
    public const AI_REFERRERS = ['chatgpt.com', 'chat.openai.com', 'perplexity.ai', 'claude.ai', 'gemini.google.com', 'copilot.microsoft.com', 'bard.google.com', 'you.com', 'phind.com', 'meta.ai', 'mistral.ai', 'chat.mistral.ai', 'deepseek.com'];
    public const SOCIAL = ['facebook.', 'fb.', 'instagram.', 'linkedin.', 'lnkd.in', 't.co', 'twitter.', 'x.com', 'tiktok.', 'youtube.', 'whatsapp.', 'wa.me', 'telegram.', 'pinterest.', 'reddit.', 'threads.net', 'snapchat.'];

    public function detectBot(?string $ua): ?array
    {
        $ua = (string) $ua;
        if ($ua === '') {
            return ['unknown', 'other'];
        }
        foreach (self::BOTS as $name => $kind) {
            if (stripos($ua, $name) !== false) {
                return [$name, $kind];
            }
        }
        if (preg_match('/bot|crawl|spider|slurp|fetch|curl|wget|python-requests|httpclient|headless|lighthouse/i', $ua)) {
            return ['autre robot', 'other'];
        }

        return null;
    }

    public function device(?string $ua): string
    {
        $ua = (string) $ua;
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) return 'tablet';
        if (preg_match('/Mobi|iPhone|Android|Opera Mini|IEMobile/i', $ua)) return 'mobile';

        return 'desktop';
    }

    public function source(Request $r): array
    {
        $utm = (string) $r->query('utm_source', '');
        $medium = strtolower((string) $r->query('utm_medium', ''));
        $ref = (string) $r->headers->get('referer', '');
        $host = $ref ? strtolower((string) parse_url($ref, PHP_URL_HOST)) : null;
        $own = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($host && ($host === $own || $host === $r->getHost())) {
            $host = null; // navigation interne
        }
        $src = 'direct';
        if ($medium === 'email' || stripos($utm, 'newsletter') !== false) $src = 'newsletter';
        elseif ($host && $this->match($host, self::AI_REFERRERS)) $src = 'ai';
        elseif ($utm && preg_match('/chatgpt|perplexity|claude|gemini|copilot/i', $utm)) $src = 'ai';
        elseif ($host && $this->match($host, self::SEARCH_ENGINES)) $src = 'search';
        elseif (($host && $this->match($host, self::SOCIAL)) || in_array($medium, ['social', 'social-media'], true)) $src = 'social';
        elseif ($host) $src = 'referral';

        return [$src, $host];
    }

    protected function match(string $host, array $needles): bool
    {
        foreach ($needles as $n) {
            if (str_contains($host, $n)) {
                return true;
            }
        }

        return false;
    }

    public function country(Request $r): ?string
    {
        foreach (['CF-IPCountry', 'X-Country-Code', 'CloudFront-Viewer-Country'] as $h) {
            $c = strtoupper((string) $r->headers->get($h));
            if (preg_match('/^[A-Z]{2}$/', $c) && $c !== 'XX') return $c;
        }
        // Repli : région de la langue du navigateur (fr-CI, fr-SN…)
        if (preg_match('/^[a-z]{2}-([A-Z]{2})/', (string) $r->headers->get('accept-language'), $m)) {
            return $m[1];
        }

        return null;
    }

    /** Enregistre la visite ; renvoie l'identifiant de la vue (pour la balise de lecture). */
    public function record(Request $r, ?Article $article = null): ?int
    {
        $ua = (string) $r->userAgent();
        if ($bot = $this->detectBot($ua)) {
            BotVisit::create(['bot' => $bot[0], 'kind' => $bot[1], 'path' => mb_substr('/'.ltrim($r->path(), '/'), 0, 500), 'created_at' => now()]);

            return null;
        }
        $salt = config('app.key').now()->toDateString();
        $visitor = hash('sha256', $salt.$r->ip().$ua);
        $session = hash('sha256', $visitor.floor(time() / 1800));
        [$source, $host] = $this->source($r);
        $pv = PageView::create([
            'path' => mb_substr('/'.ltrim($r->path(), '/'), 0, 500),
            'article_id' => $article?->id,
            'visitor' => $visitor,
            'session' => $session,
            'source' => $source,
            'referrer_host' => $host ? mb_substr($host, 0, 190) : null,
            'utm_source' => $r->query('utm_source') ? mb_substr((string) $r->query('utm_source'), 0, 190) : null,
            'utm_campaign' => $r->query('utm_campaign') ? mb_substr((string) $r->query('utm_campaign'), 0, 190) : null,
            'device' => $this->device($ua),
            'country' => $this->country($r),
            'created_at' => now(),
        ]);
        if ($article && Cache::add('vox.read.'.$article->id.'.'.$visitor, 1, now()->endOfDay())) {
            Article::whereKey($article->id)->increment('views');
        }

        return $pv->id;
    }

    public function logSearch(string $q, int $results): void
    {
        $q = mb_strtolower(trim($q));
        if (mb_strlen($q) >= 2) {
            SearchLog::create(['query' => mb_substr($q, 0, 120), 'results' => $results, 'created_at' => now()]);
        }
    }

    /* ======================= Requêtes de statistiques ======================= */

    protected function dayExpr(): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
    }

    protected function hourExpr(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(HOUR FROM created_at)',
            default => 'HOUR(created_at)',
        };
    }

    public function summary(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $prevFrom = (clone $from)->subDays($days);
        $cur = $this->period($from, now());
        $prev = $this->period($prevFrom, (clone $from)->subSecond());

        $delta = fn ($a, $b) => $b ? (int) round(($a - $b) / $b * 100) : null;

        return $cur + [
            'delta_visits' => $delta($cur['visits'], $prev['visits']),
            'delta_uniques' => $delta($cur['uniques'], $prev['uniques']),
            'delta_pages' => $delta($cur['pages'], $prev['pages']),
            'delta_duration' => $cur['avg_duration'] - $prev['avg_duration'],
            'delta_bounce' => $cur['bounce'] - $prev['bounce'],
        ];
    }

    protected function period(Carbon $from, Carbon $to): array
    {
        $q = PageView::whereBetween('created_at', [$from, $to]);
        $pages = (clone $q)->count();
        $visits = (clone $q)->distinct()->count('session');
        $uniques = (clone $q)->distinct()->count('visitor');
        $avg = (int) round((clone $q)->where('duration', '>', 0)->avg('duration') ?? 0);
        $single = DB::query()->fromSub((clone $q)->select('session')->groupBy('session')->havingRaw('COUNT(*) = 1'), 's')->count();

        return [
            'pages' => $pages, 'visits' => $visits, 'uniques' => $uniques,
            'avg_duration' => $avg, 'pages_per_visit' => $visits ? round($pages / $visits, 1) : 0,
            'bounce' => $visits ? (int) round($single / $visits * 100) : 0,
        ];
    }

    /** Série quotidienne : [label, visites (sessions), visiteurs uniques, pages vues]. */
    public function daily(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = PageView::where('created_at', '>=', $from)
            ->selectRaw($this->dayExpr().' as d, COUNT(DISTINCT session) as v, COUNT(DISTINCT visitor) as u, COUNT(*) as p')
            ->groupBy('d')->get()->keyBy('d');
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = (clone $from)->addDays($i);
            $k = $d->toDateString();
            $out[] = ['label' => $d->format('j/n'), 'date' => $k, 'v' => (int) ($rows[$k]->v ?? 0), 'u' => (int) ($rows[$k]->u ?? 0), 'p' => (int) ($rows[$k]->p ?? 0)];
        }

        return $out;
    }

    public function breakdown(string $column, int $days, int $limit = 8): array
    {
        $rows = PageView::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw("$column as k, COUNT(*) as n")->groupBy('k')->orderByDesc('n')->limit($limit)->get();
        $total = max(1, $rows->sum('n'));

        return $rows->map(fn ($r) => ['k' => $r->k ?: 'Inconnu', 'n' => (int) $r->n, 'pct' => (int) round($r->n / $total * 100)])->all();
    }

    public function hours(int $days): array
    {
        $rows = PageView::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw($this->hourExpr().' as h, COUNT(*) as n')->groupBy('h')->pluck('n', 'h');
        $out = [];
        for ($h = 0; $h < 24; $h++) {
            $out[$h] = (int) ($rows[$h] ?? 0);
        }

        return $out;
    }

    public function topArticles(int $days, int $limit = 8)
    {
        $rows = PageView::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->whereNotNull('article_id')
            ->selectRaw('article_id, COUNT(*) as n, AVG(NULLIF(duration,0)) as d, AVG(NULLIF(scroll,0)) as s')
            ->groupBy('article_id')->orderByDesc('n')->limit($limit)->get();
        $articles = Article::with('rubrique')->whereIn('id', $rows->pluck('article_id'))->get()->keyBy('id');

        return $rows->filter(fn ($r) => isset($articles[$r->article_id]))->map(fn ($r) => [
            'article' => $articles[$r->article_id], 'n' => (int) $r->n, 'duration' => (int) round($r->d ?? 0), 'scroll' => (int) round($r->s ?? 0),
        ])->values();
    }

    public function topSearches(int $days, int $limit = 8): array
    {
        return SearchLog::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('query, COUNT(*) as n, MIN(results) as r')->groupBy('query')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['k' => $r->query, 'n' => (int) $r->n, 'zero' => (int) $r->r === 0])->all();
    }

    public function bots(int $days, ?string $kind = null): array
    {
        return BotVisit::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->selectRaw('bot, kind, COUNT(*) as n, MAX(created_at) as last')->groupBy('bot', 'kind')->orderByDesc('n')->get()
            ->map(fn ($r) => ['bot' => $r->bot, 'kind' => $r->kind, 'n' => (int) $r->n, 'last' => $r->last])->all();
    }

    public function referrers(int $days, int $limit = 8): array
    {
        return PageView::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())->whereNotNull('referrer_host')
            ->selectRaw('referrer_host as k, COUNT(*) as n')->groupBy('k')->orderByDesc('n')->limit($limit)->get()
            ->map(fn ($r) => ['k' => $r->k, 'n' => (int) $r->n])->all();
    }

    public function byRubrique(int $days): array
    {
        return PageView::query()->join('articles', 'articles.id', '=', 'page_views.article_id')
            ->join('rubriques', 'rubriques.id', '=', 'articles.rubrique_id')
            ->where('page_views.created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('rubriques.name as k, COUNT(*) as n')->groupBy('rubriques.name')->orderByDesc('n')->get()
            ->map(fn ($r) => ['k' => $r->k, 'n' => (int) $r->n])->all();
    }

    public function realtime(): int
    {
        return PageView::where('created_at', '>=', now()->subMinutes(5))->distinct()->count('visitor');
    }

    public static function countryName(?string $code): string
    {
        $names = ['CI' => 'Côte d’Ivoire', 'SN' => 'Sénégal', 'FR' => 'France', 'CM' => 'Cameroun', 'MA' => 'Maroc', 'NG' => 'Nigeria',
            'CA' => 'Canada', 'BE' => 'Belgique', 'CH' => 'Suisse', 'US' => 'États-Unis', 'GH' => 'Ghana', 'BF' => 'Burkina Faso',
            'ML' => 'Mali', 'BJ' => 'Bénin', 'TG' => 'Togo', 'GN' => 'Guinée', 'CD' => 'RD Congo', 'CG' => 'Congo', 'GA' => 'Gabon',
            'TN' => 'Tunisie', 'DZ' => 'Algérie', 'KE' => 'Kenya', 'RW' => 'Rwanda', 'ZA' => 'Afrique du Sud', 'NE' => 'Niger', 'MG' => 'Madagascar'];

        return $code ? ($names[$code] ?? $code) : 'Inconnu';
    }
}
