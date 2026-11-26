<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Dossier;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Theme;
use App\Services\Analytics;
use App\Services\IndexNow;
use App\Services\OgImage;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots()
    {
        $ai = (bool) setting('ai_crawlers');
        $lines = ['# '.setting('name').' — robots.txt', 'User-agent: *', 'Allow: /', 'Disallow: /admin', 'Disallow: /recherche', 'Disallow: /api/', 'Disallow: /nl/', 'Disallow: /pub/', 'Disallow: /*?*tri=', ''];
        $lines[] = '# Moteurs de réponse et assistants IA (GEO) : '.($ai ? 'autorisés' : 'bloqués');
        foreach (array_keys(array_filter(Analytics::BOTS, fn ($k) => $k === 'ai')) as $bot) {
            $lines[] = 'User-agent: '.$bot;
            $lines[] = $ai ? 'Allow: /' : 'Disallow: /';
            if ($ai) {
                $lines[] = 'Disallow: /admin';
            }
            $lines[] = '';
        }
        $lines[] = 'Sitemap: '.route('sitemap');
        $lines[] = '# Résumé du média pour les modèles de langage : '.route('llms');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemapIndex()
    {
        $parts = ['pages', 'articles', 'news', 'taxonomies', 'publications'];
        $last = Article::published()->max('updated_at');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($parts as $p) {
            $xml .= '  <sitemap><loc>'.e(route('sitemap.part', $p)).'</loc>'.($last ? '<lastmod>'.\Illuminate\Support\Carbon::parse($last)->toAtomString().'</lastmod>' : '')."</sitemap>\n";
        }

        return response($xml.'</sitemapindex>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function sitemap(string $part)
    {
        $xml = Cache::remember('vox.sitemap.'.$part, 900, fn () => $this->buildSitemap($part));

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    protected function buildSitemap(string $part): string
    {
        $urls = [];
        $news = false;
        switch ($part) {
            case 'pages':
                $urls[] = [route('home'), now(), 'hourly', '1.0'];
                foreach (['dossiers', 'bibliotheque', 'newsletter', 'proposer', 'apropos', 'charte', 'annoncer', 'contact', 'mentions', 'confidentialite'] as $r) {
                    $urls[] = [route($r), null, 'monthly', in_array($r, ['dossiers', 'bibliotheque'], true) ? '0.7' : '0.4'];
                }
                break;
            case 'articles':
                Article::published()->latestFirst()->select(['id', 'slug', 'updated_at', 'published_at', 'title'])->chunk(500, function ($chunk) use (&$urls) {
                    foreach ($chunk as $a) {
                        $urls[] = [$a->url(), $a->updated_at ?? $a->published_at, 'weekly', '0.8', route('og.article', $a), $a->title];
                    }
                });
                break;
            case 'news': // Google News : articles des 48 dernières heures
                $news = true;
                foreach (Article::published()->where('published_at', '>=', now()->subHours(48))->latestFirst()->limit(1000)->get() as $a) {
                    $urls[] = [$a->url(), $a->published_at, null, null, null, $a->title, $a->published_at];
                }
                break;
            case 'taxonomies':
                foreach (Rubrique::orderBy('position')->get() as $r) $urls[] = [route('rubrique', $r), null, 'daily', '0.8'];
                foreach (Theme::all() as $t) $urls[] = [route('theme', $t), null, 'daily', '0.6'];
                foreach (Dossier::all() as $d) $urls[] = [route('dossier', $d), $d->updated_at, 'weekly', '0.7'];
                foreach (Author::all() as $au) $urls[] = [route('auteur', $au), null, 'weekly', '0.5'];
                break;
            case 'publications':
                foreach (Publication::all() as $p) $urls[] = [$p->url(), $p->updated_at, 'monthly', '0.6'];
                break;
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
            .($news ? ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"' : '').'>'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u[0]).'</loc>';
            if (! empty($u[1])) $xml .= '<lastmod>'.\Illuminate\Support\Carbon::parse($u[1])->toAtomString().'</lastmod>';
            if (! empty($u[2])) $xml .= '<changefreq>'.$u[2].'</changefreq>';
            if (! empty($u[3])) $xml .= '<priority>'.$u[3].'</priority>';
            if (! empty($u[4])) $xml .= '<image:image><image:loc>'.e($u[4]).'</image:loc><image:title>'.e($u[5]).'</image:title></image:image>';
            if ($news) {
                $xml .= '<news:news><news:publication><news:name>'.e(setting('name')).'</news:name><news:language>fr</news:language></news:publication>'
                    .'<news:publication_date>'.$u[6]->toAtomString().'</news:publication_date><news:title>'.e($u[5]).'</news:title></news:news>';
            }
            $xml .= "</url>\n";
        }

        return $xml.'</urlset>';
    }

    /** llms.txt : présentation structurée du média pour les moteurs de réponse IA (GEO). */
    public function llms()
    {
        $txt = Cache::remember('vox.llms', 900, function () {
            $out = '# '.setting('name')."\n\n> ".setting('seo_description')."\n\n";
            $out .= setting('name').' (VTG) est un média numérique international francophone, initié par l’École Virtuelle des Créatifs (EVC), basé à Abidjan (Côte d’Ivoire). '
                ."Il découvre et valorise les talents, startups, inventions et initiatives technologiques, avec une attention particulière à l’Afrique et aux écosystèmes émergents. "
                ."Ligne éditoriale : Révéler les talents · Explorer les innovations · Décrypter la Tech. Les contenus sont vérifiés selon une charte publique ; faits, opinions et contenus sponsorisés sont clairement distingués.\n\n";
            $out .= "## Rubriques\n\n";
            foreach (Rubrique::orderBy('position')->get() as $r) {
                $out .= '- ['.$r->name.']('.route('rubrique', $r).'): '.$r->description."\n";
            }
            $out .= "\n## Articles récents\n\n";
            foreach (Article::published()->with('rubrique')->latestFirst()->limit(60)->get() as $a) {
                $out .= '- ['.$a->title.']('.route('article.md', $a).'): '.$a->chapo.' ('.$a->label().', '.optional($a->published_at)->toDateString().")\n";
            }
            $out .= "\n## Dossiers spéciaux\n\n";
            foreach (Dossier::orderBy('position')->get() as $d) {
                $out .= '- ['.$d->title.']('.route('dossier', $d).'): '.$d->chapo."\n";
            }
            $out .= "\n## À propos et confiance\n\n- [À propos]( ".route('apropos')."): mission, valeurs, rédaction\n- [Charte éditoriale](".route('charte')."): règles de vérification et corrections\n- [Contact](".route('contact')."): rédaction et partenariats\n\n";
            $out .= "## Optional\n\n- [Texte intégral des articles récents](".route('llms.full').")\n- [Flux RSS](".route('feed').")\n- [Plan du site](".route('sitemap').")\n";

            return str_replace('( ', '(', $out);
        });

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llmsFull()
    {
        $txt = Cache::remember('vox.llms.full', 900, function () {
            $out = '# '.setting('name')." — texte intégral des articles récents\n\n> ".setting('seo_description')."\n\n";
            foreach (Article::published()->with(['rubrique', 'author', 'theme'])->latestFirst()->limit(40)->get() as $a) {
                $out .= "\n---\n\n".$a->toMarkdown()."\n";
            }

            return $out;
        });

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function rss(?Rubrique $rubrique = null)
    {
        $articles = Article::published()->with(['rubrique', 'author'])->when($rubrique, fn ($q) => $q->where('rubrique_id', $rubrique->id))->latestFirst()->limit(30)->get();
        $title = setting('name').($rubrique ? ' · '.$rubrique->name : '');
        $link = $rubrique ? route('rubrique', $rubrique) : route('home');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:media="http://search.yahoo.com/mrss/"><channel>';
        $xml .= '<title>'.e($title).'</title><link>'.e($link).'</link><description>'.e(setting('tagline')).'</description><language>fr-FR</language>';
        $xml .= '<atom:link href="'.e(url()->current()).'" rel="self" type="application/rss+xml"/>';
        $xml .= '<image><url>'.e(asset('assets/img/logo-512.png')).'</url><title>'.e($title).'</title><link>'.e($link).'</link></image>';
        if ($articles->first()) $xml .= '<lastBuildDate>'.$articles->first()->published_at->toRssString().'</lastBuildDate>';
        foreach ($articles as $a) {
            $xml .= '<item><title>'.e($a->title).'</title><link>'.e($a->url()).'</link><guid isPermaLink="true">'.e($a->url()).'</guid>'
                .'<pubDate>'.$a->published_at->toRssString().'</pubDate><dc:creator>'.e($a->author?->name ?? setting('name')).'</dc:creator>'
                .'<category>'.e($a->rubrique?->name).'</category><description>'.e($a->chapo).'</description>'
                .'<content:encoded><![CDATA['.nl2br(e($a->plainText())).']]></content:encoded>'
                .'<media:content url="'.e(route('og.article', $a)).'" medium="image" type="image/png" width="1200" height="630"/></item>';
        }

        return response($xml.'</channel></rss>', 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function jsonFeed()
    {
        $items = Article::published()->with(['author', 'rubrique'])->latestFirst()->limit(30)->get()->map(fn ($a) => [
            'id' => $a->url(), 'url' => $a->url(), 'title' => $a->title, 'summary' => $a->chapo, 'content_text' => $a->plainText(),
            'image' => route('og.article', $a), 'date_published' => $a->published_at->toAtomString(), 'date_modified' => optional($a->updated_at)->toAtomString(),
            'authors' => [['name' => $a->author?->name, 'url' => $a->author?->url()]], 'tags' => array_values(array_filter(array_merge([$a->rubrique?->name], $a->tags ?? []))),
        ]);

        return response()->json(['version' => 'https://jsonfeed.org/version/1.1', 'title' => setting('name'), 'home_page_url' => route('home'),
            'feed_url' => route('feed.json'), 'description' => setting('tagline'), 'language' => 'fr', 'icon' => asset('assets/img/logo-512.png'), 'items' => $items],
            200, ['Content-Type' => 'application/feed+json; charset=UTF-8'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function ogArticle(Article $article, OgImage $og)
    {
        abort_unless($article->isLive() || auth()->check(), 404);

        return response()->file($og->forArticle($article->loadMissing(['rubrique', 'author'])), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=604800']);
    }

    public function manifest()
    {
        return response()->json([
            'name' => setting('name'), 'short_name' => 'VOX TECH', 'description' => setting('tagline'), 'lang' => 'fr', 'start_url' => '/',
            'display' => 'standalone', 'background_color' => '#F6F5F2', 'theme_color' => '#111214',
            'icons' => [['src' => asset('assets/img/favicon.svg'), 'sizes' => 'any', 'type' => 'image/svg+xml'], ['src' => asset('assets/img/logo-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function security()
    {
        return response("Contact: mailto:".setting('email_redaction')."\nPreferred-Languages: fr, en\nCanonical: ".route('security.txt')."\nExpires: ".now()->addYear()->toAtomString()."\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function indexNowKey(string $key)
    {
        abort_unless(hash_equals((string) setting('indexnow_key'), $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain']);
    }
}
