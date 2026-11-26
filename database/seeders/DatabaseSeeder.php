<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AdSlot;
use App\Models\Article;
use App\Models\Author;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Dossier;
use App\Models\Media;
use App\Models\Newsletter;
use App\Models\Opportunity;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Models\Subscriber;
use App\Models\Theme;
use App\Models\User;
use App\Services\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Importe les contenus de la maquette VOX TECH GLOBAL (database/data/*.json).
 * Les dates sont recalées pour que le dernier article soit daté d'hier.
 */
class DatabaseSeeder extends Seeder
{
    protected int $offset = 0;

    protected function d(?string $date, string $time = '08:00'): ?Carbon
    {
        return $date ? Carbon::parse($date.' '.$time)->addDays($this->offset) : null;
    }

    public function run(): void
    {
        $seed = json_decode(file_get_contents(database_path('data/seed.json')), true);
        $extra = json_decode(file_get_contents(database_path('data/extra.json')), true);
        $latest = collect($seed['ARTICLES'])->max('date');
        $this->offset = (int) Carbon::parse($latest)->diffInDays(now()->subDay()->startOfDay());

        $rubIds = [];
        foreach ($seed['RUBRIQUES'] as $i => $r) {
            $rubIds[$r['id']] = Rubrique::create(['slug' => $r['id'], 'name' => $r['name'], 'pillar' => $r['pillar'], 'promise' => $r['promise'],
                'description' => $r['desc'], 'subs' => $r['subs'] ?? [], 'position' => $i])->id;
        }
        $themeIds = [];
        foreach ($seed['THEMES'] as $t) {
            $themeIds[$t['id']] = Theme::create(['slug' => $t['id'], 'name' => $t['name']])->id;
        }
        $authorIds = [];
        foreach ($seed['AUTHORS'] as $a) {
            $authorIds[$a['id']] = Author::create(['slug' => $a['id'], 'name' => $a['name'], 'role' => $a['role'], 'bio' => $a['bio']])->id;
        }

        // ---- Comptes : l'administrateur principal vient du .env ----
        $password = env('ADMIN_PASSWORD', 'VoxTech@2026');
        foreach ($extra['users'] as $u) {
            $email = $u['id'] === 1 ? env('ADMIN_EMAIL', 'admin@voxtechglobal.com') : str_replace('@exemple.com', '@voxtechglobal.com', $u['email']);
            $author = Author::where('name', $u['name'])->first();
            User::create(['name' => $u['name'], 'email' => $email, 'password' => $password, 'role' => $u['role'], 'active' => $u['active'],
                'author_id' => $author?->id, 'last_login_at' => $this->d($u['last'], '09:00'), 'email_verified_at' => now()]);
        }

        $articles = $seed['ARTICLES'];
        $sched = $extra['scheduled'];
        $articles[] = $sched;
        foreach ($articles as $a) {
            $isSched = ($a['status'] ?? '') === 'programmé';
            $published = $isSched ? now()->addDays(5)->setTime(7, 0) : $this->d($a['date'], sprintf('%02d:%02d', 7 + $a['id'] % 10, ($a['id'] * 7) % 60));
            Article::create([
                'id' => $a['id'], 'slug' => $a['slug'], 'type' => $a['type'], 'nature' => $a['nature'] ?? null,
                'rubrique_id' => $rubIds[$a['rubrique']] ?? null, 'sub' => $a['sub'] ?? null, 'theme_id' => $themeIds[$a['theme']] ?? null,
                'region' => $a['region'] ?? null, 'country' => $a['country'] ?? null, 'author_id' => $authorIds[$a['author']] ?? null,
                'title' => $a['title'], 'chapo' => $a['chapo'], 'body' => $a['body'] ?? [], 'tags' => $a['tags'] ?? [],
                'sources' => $a['sources'] ?? null, 'fiche' => $a['fiche'] ?? null, 'startup' => $a['startup'] ?? null, 'verdict' => $a['verdict'] ?? null,
                'status' => $a['status'] ?? 'publié' ?: 'publié', 'published_at' => $published, 'read_time' => $a['read'] ?? 4,
                'is_featured' => (bool) ($a['une'] ?? false), 'views' => $a['views'] ?? 0,
                'created_at' => $published, 'updated_at' => $published,
            ]);
        }
        Article::where('status', '')->update(['status' => 'publié']);

        foreach ($extra['comments'] as $c) {
            $art = Article::where('slug', $c['article'])->first();
            if ($art) {
                Comment::create(['article_id' => $art->id, 'name' => $c['name'], 'email' => $c['email'], 'text' => $c['text'], 'status' => $c['status'],
                    'created_at' => $this->d($c['date'], '12:00'), 'updated_at' => $this->d($c['date'], '12:00')]);
            }
        }

        foreach ($seed['DOSSIERS'] as $i => $d) {
            $dos = Dossier::create(['slug' => $d['id'], 'title' => $d['title'], 'chapo' => $d['chapo'], 'tone' => $d['tone'] ?? 0, 'position' => $i]);
            foreach (array_values($d['articles']) as $pos => $aid) {
                if (Article::whereKey($aid)->exists()) {
                    $dos->articles()->attach($aid, ['position' => $pos]);
                }
            }
        }

        foreach ($seed['LIBRARY'] as $p) {
            Publication::create(['slug' => $p['slug'], 'type' => $p['type'], 'title' => $p['title'], 'subtitle' => $p['subtitle'] ?? null,
                'published_on' => $p['date'], 'pages' => $p['pages'], 'format' => $p['format'], 'price' => $p['price'], 'color' => $p['color'],
                'description' => $p['desc'], 'toc' => $p['toc'] ?? [], 'downloads' => 40 + $p['id'] * 23]);
        }

        foreach ($seed['AD_SLOTS'] as $i => $s) {
            AdSlot::create(['code' => $s['id'], 'name' => $s['name'], 'size' => $s['size'], 'location' => $s['where'], 'position' => $i]);
        }
        foreach ($seed['CAMPAIGNS'] as $c) {
            Campaign::create(['advertiser' => $c['advertiser'], 'slot' => $c['slot'], 'title' => $c['title'], 'cta' => $c['cta'],
                'url' => 'https://www.exemple.com/'.Str::slug($c['advertiser']), 'starts_on' => $this->d($c['start']), 'ends_on' => $this->d($c['end']),
                'budget' => $c['budget'], 'impressions' => $c['impressions'], 'clicks' => $c['clicks'], 'active' => $c['active']]);
        }

        foreach ($seed['SUBMISSIONS'] as $s) {
            Submission::create(['name' => $s['name'], 'email' => $s['email'], 'project' => $s['project'], 'category' => $s['cat'],
                'country' => $s['country'], 'description' => $s['desc'], 'status' => $s['status'],
                'created_at' => $this->d($s['date'], '10:00'), 'updated_at' => $this->d($s['date'], '10:00')]);
        }
        foreach ($seed['SUBSCRIBERS'] as $s) {
            $at = $this->d($s['date'], '11:00');
            Subscriber::create(['email' => $s['email'], 'source' => $s['source'], 'confirmed_at' => $at, 'created_at' => $at, 'updated_at' => $at]);
        }
        foreach ($seed['OPPORTUNITES'] as $o) {
            Opportunity::create(['type' => $o['type'], 'title' => $o['title'], 'organization' => $o['org'], 'location' => $o['lieu'], 'deadline' => $this->d($o['limite'])]);
        }
        foreach ($extra['media'] as $m) {
            Media::create(['name' => $m['name'], 'type' => $m['type'], 'size' => $m['size'], 'width' => $m['w'], 'height' => $m['h'], 'alt' => $m['alt'],
                'tone' => $m['tone'], 'created_at' => $this->d($m['date']), 'updated_at' => $this->d($m['date'])]);
        }
        foreach ($extra['newsletters'] as $n) {
            $ids = Article::whereIn('slug', $n['articles'])->pluck('id')->all();
            Newsletter::create(['subject' => $n['subject'], 'intro' => 'Cette semaine : des talents à découvrir, une invention documentée et un décryptage.',
                'article_ids' => $ids, 'status' => 'Envoyée', 'sent_at' => $this->d($n['date'], '07:30'), 'recipients' => $n['sent'],
                'opens' => (int) round($n['sent'] * $n['opens'] / 100), 'clicks' => (int) round($n['sent'] * $n['clicks'] / 100)]);
        }
        foreach (array_reverse($extra['activity']) as $x) {
            [$date, $time] = explode(' ', $x['date']);
            ActivityLog::create(['who' => $x['who'], 'action' => $x['what'], 'created_at' => $this->d($date, $time),
                'user_id' => User::where('name', $x['who'])->value('id')]);
        }

        // Statistiques de démonstration (désactivables : SEED_DEMO_STATS=false)
        if (filter_var(env('SEED_DEMO_STATS', true), FILTER_VALIDATE_BOOL)) {
            app(DemoData::class)->analytics(90);
        }
    }
}
