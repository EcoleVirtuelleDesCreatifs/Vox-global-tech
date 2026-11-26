<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Author;
use App\Models\Dossier;
use App\Models\Opportunity;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Theme;
use App\Services\Analytics;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ListingController extends Controller
{
    public function rubrique(Request $r, Rubrique $rubrique, Seo $seo)
    {
        $sub = (string) $r->query('sous', '');
        $themeSlug = (string) $r->query('theme', '');
        $sort = $r->query('tri') === 'populaires' ? 'populaires' : 'recents';

        $q = Article::published()->with(['rubrique', 'author', 'theme'])->where('rubrique_id', $rubrique->id)
            ->when($sub, fn ($q) => $q->where('sub', $sub))
            ->when($themeSlug, fn ($q) => $q->whereHas('theme', fn ($t) => $t->where('slug', $themeSlug)));
        $q = $sort === 'populaires' ? $q->orderByDesc('views') : $q->latestFirst();
        $articles = $q->paginate((int) setting('per_page', 6) + 1)->withQueryString();

        $subCounts = Article::published()->where('rubrique_id', $rubrique->id)->selectRaw('sub, COUNT(*) as n')->groupBy('sub')->pluck('n', 'sub');
        $opps = $rubrique->slug === 'campus'
            ? Opportunity::when($r->query('opportunite'), fn ($q, $t) => $q->where('type', $t))->orderBy('deadline')->get()
            : collect();

        $title = $rubrique->name.($sub ? ' · '.$sub : '');
        $seo->title($title.($articles->currentPage() > 1 ? ' — page '.$articles->currentPage() : ''))->description($rubrique->description ?: $rubrique->promise);
        $seo->canonical = $articles->currentPage() > 1 ? $articles->url($articles->currentPage()) : route('rubrique', $rubrique).($sub ? '?sous='.urlencode($sub) : '');
        if ($themeSlug || $sort !== 'recents') {
            $seo->robots = 'noindex, follow';
        }
        $seo->rss = route('feed.rubrique', $rubrique);
        $seo->crumbs([['Accueil', route('home')], [$rubrique->name, route('rubrique', $rubrique)]]);
        $seo->add($this->itemList($articles->getCollection(), $rubrique->name));

        return view('site.listing', [
            'mode' => 'rubrique', 'rub' => $rubrique, 'theme' => null, 'articles' => $articles, 'sub' => $sub,
            'filter' => $themeSlug, 'sort' => $sort, 'subCounts' => $subCounts, 'opps' => $opps, 'themes' => Theme::orderBy('name')->get(),
            'rubriques' => collect(), 'oppType' => (string) $r->query('opportunite', ''),
        ]);
    }

    public function theme(Request $r, Theme $theme, Seo $seo)
    {
        $rubSlug = (string) $r->query('rubrique', '');
        $sort = $r->query('tri') === 'populaires' ? 'populaires' : 'recents';
        $q = Article::published()->with(['rubrique', 'author', 'theme'])->where('theme_id', $theme->id)
            ->when($rubSlug, fn ($q) => $q->whereHas('rubrique', fn ($t) => $t->where('slug', $rubSlug)));
        $q = $sort === 'populaires' ? $q->orderByDesc('views') : $q->latestFirst();
        $articles = $q->paginate((int) setting('per_page', 6) + 1)->withQueryString();

        $seo->title($theme->name.' : talents, innovations et actualités')->description('Tous les contenus de '.setting('name').' sur la thématique '.$theme->name.' : portraits, innovations documentées, startups et décryptages.');
        $seo->canonical = route('theme', $theme);
        if ($rubSlug || $sort !== 'recents') {
            $seo->robots = 'noindex, follow';
        }
        $seo->crumbs([['Accueil', route('home')], ['Thématiques', null], [$theme->name, route('theme', $theme)]]);
        $seo->add($this->itemList($articles->getCollection(), $theme->name));

        return view('site.listing', [
            'mode' => 'theme', 'rub' => null, 'theme' => $theme, 'articles' => $articles, 'sub' => '', 'filter' => $rubSlug, 'sort' => $sort,
            'subCounts' => collect(), 'opps' => collect(), 'themes' => collect(), 'rubriques' => Rubrique::orderBy('position')->get(), 'oppType' => '',
        ]);
    }

    public function author(Author $author, Seo $seo)
    {
        $articles = $author->articles()->published()->with(['rubrique', 'author', 'theme'])->latestFirst()->paginate(12);
        $seo->title($author->name.', '.mb_strtolower((string) $author->role))->description($author->bio);
        $seo->canonical = route('auteur', $author);
        $seo->crumbs([['Accueil', route('home')], ['La rédaction', route('apropos').'#ab-equipe'], [$author->name, route('auteur', $author)]]);
        $seo->add(['@type' => 'ProfilePage', '@id' => route('auteur', $author).'#profile', 'url' => route('auteur', $author), 'mainEntity' => $seo->person($author)]);

        return view('site.author', compact('author', 'articles'));
    }

    public function dossiers(Seo $seo)
    {
        $dossiers = Dossier::with(['articles' => fn ($q) => $q->published()])->orderBy('position')->get();
        $seo->title('Dossiers spéciaux : enquêtes et grandes thématiques')->description('Pour comprendre en profondeur les sujets qui transforment la technologie et la société : IA, startups, innovation frugale, femmes dans la Tech…');
        $seo->canonical = route('dossiers');
        $seo->crumbs([['Accueil', route('home')], ['Dossiers spéciaux', route('dossiers')]]);

        return view('site.dossiers', compact('dossiers'));
    }

    public function dossier(Dossier $dossier, Seo $seo)
    {
        $articles = $dossier->articles()->published()->with(['rubrique', 'author', 'theme'])->get();
        $seo->title('Dossier : '.$dossier->title)->description($dossier->chapo);
        $seo->canonical = route('dossier', $dossier);
        $seo->crumbs([['Accueil', route('home')], ['Dossiers spéciaux', route('dossiers')], [$dossier->title, route('dossier', $dossier)]]);
        $seo->add($this->itemList($articles, $dossier->title));

        return view('site.dossier', compact('dossier', 'articles'));
    }

    public function search(Request $r, Seo $seo, Analytics $analytics)
    {
        $q = trim((string) $r->query('q', ''));
        $type = (string) $r->query('type', '');
        $articles = collect();
        $pubs = collect();
        if (mb_strlen($q) >= 2) {
            $articles = $this->searchQuery($q)->when(array_key_exists($type, Article::TYPES), fn ($x) => $x->where('type', $type))->limit(40)->get();
            $like = '%'.$q.'%';
            $pubs = Publication::where(fn ($w) => $w->where('title', 'like', $like)->orWhere('subtitle', 'like', $like)->orWhere('description', 'like', $like))->get();
            if (! $r->user() && ! $r->attributes->get('vox.bot')) {
                $analytics->logSearch($q, $articles->count() + $pubs->count());
            }
        }
        $seo->title($q ? 'Recherche : '.$q : 'Recherche')->description('Rechercher un talent, une startup, une innovation ou un pays dans '.setting('name').'.')->noindex();
        $seo->canonical = route('recherche');

        return view('site.search', compact('q', 'type', 'articles', 'pubs'));
    }

    public function quickSearch(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }
        $items = $this->searchQuery($q)->limit(6)->get()->map(fn (Article $a) => [
            'title' => $a->title, 'url' => $a->url(), 'type' => $a->label().' · '.($a->rubrique?->name ?? ''),
            'meta' => trim(($a->country ? $a->country.' · ' : '').fr_date($a->published_at)),
        ]);

        return response()->json(['results' => $items, 'all' => route('recherche', ['q' => $q])]);
    }

    /** Recherche plein texte insensible aux accents (titre, chapo, mots-clés, pays, auteur, rubrique, thématique). */
    protected function searchQuery(string $q)
    {
        $terms = array_filter(preg_split('/\s+/u', Str::lower($q)), fn ($t) => mb_strlen($t) >= 2);

        return Article::published()->with(['rubrique', 'author', 'theme'])
            ->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $like = '%'.$t.'%';
                    $ascii = '%'.Str::ascii($t).'%';
                    $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('chapo', 'like', $like)->orWhere('tags', 'like', $like)
                        ->orWhere('country', 'like', $like)->orWhere('title', 'like', $ascii)->orWhere('chapo', 'like', $ascii)->orWhere('body', 'like', $like)
                        ->orWhereHas('author', fn ($a) => $a->where('name', 'like', $like))
                        ->orWhereHas('rubrique', fn ($a) => $a->where('name', 'like', $like))
                        ->orWhereHas('theme', fn ($a) => $a->where('name', 'like', $like)->orWhere('slug', 'like', $ascii)));
                }
            })
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', ['%'.$q.'%'])->latestFirst();
    }

    protected function itemList($articles, string $name): array
    {
        return ['@type' => 'ItemList', 'name' => $name, 'itemListElement' => $articles->values()->map(fn ($a, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'url' => $a->url(), 'name' => $a->title,
        ])->all()];
    }
}
