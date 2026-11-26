<?php

namespace App\Services;

use App\Models\Article;
use App\Models\BotVisit;
use App\Models\PageView;
use App\Models\SearchLog;
use Illuminate\Support\Facades\DB;

/** Génère un historique d'audience réaliste pour la démonstration (supprimable depuis l'admin). */
class DemoData
{
    public function analytics(int $days = 90): int
    {
        mt_srand(2026);
        $articles = Article::published()->pluck('views', 'id')->all();
        $slugs = Article::published()->pluck('slug', 'id')->all();
        $weights = [];
        foreach ($articles as $id => $v) {
            $weights[$id] = max(1, (int) round($v / 400));
        }
        $pool = [];
        foreach ($weights as $id => $w) {
            $pool = array_merge($pool, array_fill(0, $w, $id));
        }
        $countries = array_merge(array_fill(0, 24, 'CI'), array_fill(0, 14, 'SN'), array_fill(0, 12, 'FR'), array_fill(0, 9, 'CM'),
            array_fill(0, 7, 'MA'), array_fill(0, 6, 'NG'), array_fill(0, 5, 'CA'), array_fill(0, 4, 'BF'), array_fill(0, 4, 'BJ'), array_fill(0, 3, 'GH'), array_fill(0, 3, 'BE'));
        $sources = array_merge(array_fill(0, 40, 'social'), array_fill(0, 26, 'search'), array_fill(0, 17, 'direct'), array_fill(0, 10, 'newsletter'), array_fill(0, 4, 'referral'), array_fill(0, 3, 'ai'));
        $refs = ['social' => ['facebook.com', 'l.facebook.com', 'linkedin.com', 't.co', 'instagram.com', 'wa.me'], 'search' => ['google.com', 'bing.com', 'duckduckgo.com'],
            'referral' => ['afriquinfos.com', 'evc-registre.com', 'techcabal.com'], 'ai' => ['chatgpt.com', 'perplexity.ai', 'claude.ai', 'gemini.google.com']];
        $devices = array_merge(array_fill(0, 71, 'mobile'), array_fill(0, 24, 'desktop'), array_fill(0, 5, 'tablet'));
        $rows = [];
        $total = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $wk = in_array($day->dayOfWeek, [0, 6], true);
            $sessions = (int) max(60, 180 + 70 * sin($i / 3.2) + ($wk ? -45 : 25) + ($days - $i) * 3.2 + mt_rand(-25, 25));
            for ($s = 0; $s < $sessions; $s++) {
                $h = $this->hour();
                $visitor = hash('sha256', 'demo'.$day->toDateString().mt_rand(1, (int) ($sessions * 0.72)));
                $session = hash('sha256', $visitor.$h.$s);
                $src = $sources[array_rand($sources)];
                $ref = $refs[$src][array_rand($refs[$src] ?? [null])] ?? null;
                $device = $devices[array_rand($devices)];
                $country = $countries[array_rand($countries)];
                $n = mt_rand(1, 100) <= 46 ? 1 : mt_rand(2, 5);
                for ($p = 0; $p < $n; $p++) {
                    $aid = mt_rand(1, 100) <= 78 && $pool ? $pool[array_rand($pool)] : null;
                    $rows[] = ['path' => $aid ? '/article/'.$slugs[$aid] : ['/', '/rubrique/talents', '/rubrique/innovations', '/recherche', '/dossiers'][mt_rand(0, 4)],
                        'article_id' => $aid, 'visitor' => $visitor, 'session' => $session, 'source' => $p === 0 ? $src : 'direct',
                        'referrer_host' => $p === 0 ? $ref : null, 'utm_source' => $src === 'newsletter' ? 'newsletter' : null, 'utm_campaign' => null,
                        'device' => $device, 'country' => $country, 'duration' => $aid ? mt_rand(35, 420) : mt_rand(5, 60), 'scroll' => $aid ? mt_rand(25, 100) : mt_rand(10, 80),
                        'created_at' => $day->copy()->setTime($h, mt_rand(0, 59), mt_rand(0, 59))];
                    $total++;
                }
                if (count($rows) >= 500) {
                    PageView::insert($rows);
                    $rows = [];
                }
            }
            // Robots (moteurs classiques et IA)
            $bots = [];
            foreach (['Googlebot' => ['search', 40], 'bingbot' => ['search', 14], 'GPTBot' => ['ai', 9], 'ClaudeBot' => ['ai', 6], 'PerplexityBot' => ['ai', 5],
                'OAI-SearchBot' => ['ai', 4], 'Google-Extended' => ['ai', 3], 'Applebot' => ['search', 3], 'facebookexternalhit' => ['social', 7], 'AhrefsBot' => ['seo', 4]] as $bot => [$kind, $avg]) {
                for ($k = 0; $k < max(0, $avg + mt_rand(-3, 3)); $k++) {
                    $aid = $pool ? $pool[array_rand($pool)] : null;
                    $bots[] = ['bot' => $bot, 'kind' => $kind, 'path' => $aid ? '/article/'.$slugs[$aid] : '/sitemap.xml', 'created_at' => $day->copy()->setTime(mt_rand(0, 23), mt_rand(0, 59))];
                }
            }
            BotVisit::insert($bots);
            $terms = ['intelligence artificielle' => 6, 'startup' => 4, 'santé' => 3, 'financement' => 3, 'imprimante 3d' => 2, 'mobile money' => 2, 'cybersécurité' => 1, 'bourse' => 1];
            $logs = [];
            foreach ($terms as $q => $w) {
                for ($k = 0; $k < mt_rand(0, $w); $k++) {
                    $logs[] = ['query' => $q, 'results' => $q === 'bourse' ? 0 : mt_rand(2, 9), 'created_at' => $day->copy()->setTime(mt_rand(7, 23), mt_rand(0, 59))];
                }
            }
            if ($logs) {
                SearchLog::insert($logs);
            }
        }
        if ($rows) {
            PageView::insert($rows);
        }

        return $total;
    }

    protected function hour(): int
    {
        $w = [];
        for ($h = 0; $h < 24; $h++) {
            $w[$h] = (int) round(30 + 70 * max(0, sin(($h - 6) / 24 * M_PI * 2)) + ($h >= 19 && $h <= 22 ? 35 : 0));
        }
        $r = mt_rand(1, array_sum($w));
        foreach ($w as $h => $v) {
            if (($r -= $v) <= 0) {
                return $h;
            }
        }

        return 20;
    }

    public function purge(): void
    {
        PageView::query()->delete();
        BotVisit::query()->delete();
        SearchLog::query()->delete();
    }
}
