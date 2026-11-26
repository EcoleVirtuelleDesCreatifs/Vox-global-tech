<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VoxMail;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Author;
use App\Models\Media;
use App\Models\Rubrique;
use App\Models\Theme;
use App\Models\User;
use App\Services\IndexNow;
use App\Services\MediaUploader;
use App\Services\Notifier;
use App\Support\SiteCache;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $r)
    {
        $q = Article::with(['rubrique', 'author'])
            ->when($r->query('q'), fn ($w, $s) => $w->where(fn ($x) => $x->where('title', 'like', "%$s%")->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%$s%"))))
            ->when($r->query('rubrique'), fn ($w, $s) => $w->where('rubrique_id', $s))
            ->when($r->query('statut'), function ($w, $s) {
                return match ($s) {
                    'publié' => $w->published(),
                    'programmé' => $w->where('status', 'programmé')->where('published_at', '>', now()),
                    default => $w->where('status', $s),
                };
            });
        $q = match ($r->query('tri')) {
            'views' => $q->orderByDesc('views'),
            'title' => $q->orderBy('title'),
            default => $q->orderByDesc('published_at')->orderByDesc('id'),
        };

        return view('admin.articles.index', [
            'articles' => $q->paginate(25)->withQueryString(),
            'rubriques' => Rubrique::orderBy('position')->get(),
            'total' => Article::count(),
        ]);
    }

    public function create()
    {
        $a = new Article(['type' => 'article', 'nature' => 'Fait', 'region' => 'Afrique', 'status' => 'brouillon', 'published_at' => now()->addHour()->startOfHour()]);
        $a->author_id = auth()->user()->author_id ?? Author::value('id');
        $a->rubrique_id = Rubrique::orderBy('position')->value('id');
        $a->title = mb_substr((string) request('titre', ''), 0, 200) ?: null;

        return $this->form($a);
    }

    public function edit(Article $article)
    {
        $this->authorizeEdit($article);

        return $this->form($article);
    }

    protected function form(Article $a)
    {
        return view('admin.articles.form', [
            'a' => $a, 'rubriques' => Rubrique::orderBy('position')->get(), 'themes' => Theme::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(), 'media' => Media::where('type', 'Image')->whereNotNull('path')->latest()->limit(60)->get(),
            'comments' => $a->exists ? $a->comments()->count() : 0,
        ]);
    }

    public function store(Request $r)
    {
        $a = new Article(['created_by' => $r->user()->id, 'views' => 0]);

        return $this->save($r, $a);
    }

    public function update(Request $r, Article $article)
    {
        $this->authorizeEdit($article);

        return $this->save($r, $article);
    }

    protected function save(Request $r, Article $a)
    {
        $data = $r->validate([
            'title' => 'required|string|max:200',
            'chapo' => 'required|string|max:600',
            'text' => 'required|string|min:20',
            'slug' => ['nullable', 'string', 'max:90', 'regex:/^[a-z0-9-]*$/'],
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:180',
            'sources_text' => 'nullable|string|max:3000',
            'type' => ['required', Rule::in(array_keys(Article::TYPES))],
            'nature' => ['nullable', Rule::in(Article::NATURES)],
            'rubrique_id' => 'required|exists:rubriques,id',
            'sub' => 'nullable|string|max:80',
            'theme_id' => 'nullable|exists:themes,id',
            'region' => ['nullable', Rule::in(Article::REGIONS)],
            'country' => 'nullable|string|max:80',
            'author_id' => 'nullable|exists:authors,id',
            'date' => 'required|date',
            'time' => 'nullable|date_format:H:i',
            'tags' => 'nullable|string|max:300',
            'is_featured' => 'nullable|boolean',
            'is_sponsored' => 'nullable|boolean',
            'fiche' => 'nullable|array', 'startup' => 'nullable|array', 'verdict' => 'nullable|array',
            'cover_id' => 'nullable|exists:media,id',
            'cover_file' => 'nullable|image|max:6144',
            'remove_cover' => 'nullable|boolean',
        ], [
            'title.required' => 'Le titre est obligatoire.', 'chapo.required' => 'Le chapeau est obligatoire.',
            'text.required' => 'Le contenu est obligatoire.', 'text.min' => 'Le contenu est trop court.',
            'slug.regex' => 'L’adresse ne peut contenir que des minuscules, chiffres et tirets.', 'cover_file.image' => 'La couverture doit être une image.',
        ]);

        $user = $r->user();
        $publish = $r->input('action') === 'publish';
        $unpublish = $r->input('action') === 'unpublish';
        if (($publish || $a->isLive()) && ! $user->canDo('publish')) {
            $publish = false;
            if ($a->isLive()) abort(403, 'Vous ne pouvez pas modifier un article publié.');
        }
        $wasLive = $a->exists && $a->isLive();
        $when = Carbon::parse($data['date'].' '.($data['time'] ?? '08:00'));

        $a->fill([
            'title' => trim($data['title']), 'chapo' => trim($data['chapo']), 'body' => Article::textToBody($data['text']),
            'type' => $data['type'], 'nature' => $data['nature'] ?? null, 'rubrique_id' => $data['rubrique_id'], 'sub' => ($data['sub'] ?? null) ?: null,
            'theme_id' => $data['theme_id'] ?? null, 'region' => $data['region'] ?? null, 'country' => $data['country'] ?? null,
            'author_id' => $data['author_id'] ?? null, 'published_at' => $when,
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? ''))))),
            'sources' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['sources_text'] ?? ''))))) ?: null,
            'seo_title' => ($data['seo_title'] ?? null) ?: null, 'seo_description' => ($data['seo_description'] ?? null) ?: null,
            'is_featured' => $r->boolean('is_featured'), 'is_sponsored' => $r->boolean('is_sponsored'),
            'fiche' => $data['type'] === 'projet' ? array_filter($data['fiche'] ?? []) ?: null : null,
            'startup' => array_filter($data['startup'] ?? []) ?: null,
            'verdict' => $data['type'] === 'factcheck' && ! empty($data['verdict']['label']) ? ['label' => $data['verdict']['label'], 'text' => $data['verdict']['text'] ?? ''] : null,
        ]);
        $a->read_time = Article::estimateReadTime($a->chapo.' '.$a->plainText());
        $a->slug = ! empty($data['slug']) ? Article::uniqueSlug($data['slug'], $a->id) : ($a->slug ?: Article::uniqueSlug($a->title, $a->id));

        if ($r->hasFile('cover_file')) {
            $a->cover_id = app(MediaUploader::class)->store($r->file('cover_file'), $user, $a->title)->id;
        } elseif ($r->boolean('remove_cover')) {
            $a->cover_id = null;
        } elseif (! empty($data['cover_id'])) {
            $a->cover_id = $data['cover_id'];
        }

        if ($publish) {
            $a->status = $when->isFuture() ? 'programmé' : 'publié';
        } elseif ($unpublish) {
            $a->status = 'brouillon';
        } elseif (! $a->exists) {
            $a->status = 'brouillon';
        }
        $a->save();
        SiteCache::flush();

        $verb = $a->wasRecentlyCreated ? 'a créé' : 'a modifié';
        if ($publish) $verb = $a->status === 'programmé' ? 'a programmé' : 'a publié';
        if ($unpublish) $verb = 'a repassé en brouillon';
        ActivityLog::record($verb.' « '.$a->title.' »'.($a->status === 'programmé' ? ' pour le '.$a->published_at->format('d/m/Y à H:i') : ''));

        if ($a->isLive() && ! $wasLive) {
            self::afterPublish($a);
        } elseif ($a->isLive()) {
            app(IndexNow::class)->submit([$a->url()]);
        }

        $msg = match (true) {
            $publish && $a->status === 'programmé' => 'Article programmé : il paraîtra le '.$a->published_at->format('d/m/Y à H:i').'.',
            $publish => 'Article publié et notifié aux moteurs de recherche.',
            $unpublish => 'Article repassé en brouillon.',
            default => 'Article enregistré.',
        };

        return redirect()->route('admin.articles.edit', $a->id)->with('ok', $msg);
    }

    /** Actions à la mise en ligne : IndexNow + e-mail à l'auteur. */
    public static function afterPublish(Article $a): void
    {
        app(IndexNow::class)->submit([$a->url(), route('home'), route('sitemap.part', 'news')]);
        $authorUser = User::where('author_id', $a->author_id)->where('active', true)->first();
        if ($authorUser && $authorUser->id !== auth()->id()) {
            Notifier::send($authorUser->email, new VoxMail('Votre article est en ligne : '.$a->title, 'Votre article est publié 🎉', [
                'Bonjour '.e($authorUser->name).',', '« **'.e($a->title).'** » est désormais en ligne sur '.setting('name').'.', 'Pensez à le partager sur vos réseaux !'],
                ['label' => 'Voir l’article', 'url' => $a->url()]));
        }
    }

    public function toggle(Article $article)
    {
        $this->authorizeEdit($article);
        $live = $article->isLive();
        if ($article->status === 'programmé' && ! $live) {
            $article->update(['status' => 'brouillon']);
        } else {
            $article->update(['status' => $live ? 'brouillon' : 'publié', 'published_at' => $live ? $article->published_at : ($article->published_at?->isFuture() ? now() : $article->published_at ?? now())]);
        }
        SiteCache::flush();
        ActivityLog::record(($article->isLive() ? 'a publié' : 'a repassé en brouillon').' « '.$article->title.' »');
        if ($article->isLive() && ! $live) self::afterPublish($article);

        return back()->with('ok', $article->isLive() ? 'Article publié.' : 'Article repassé en brouillon.');
    }

    public function bulk(Request $r)
    {
        $data = $r->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'do' => 'required|in:publish,unpublish,delete,feature,unfeature']);
        $user = $r->user();
        $need = ['publish' => 'publish', 'unpublish' => 'publish', 'delete' => 'edit-others', 'feature' => 'edit-others', 'unfeature' => 'edit-others'][$data['do']];
        abort_unless($user->canDo($need), 403);
        $items = Article::whereIn('id', $data['ids'])->get();
        foreach ($items as $a) {
            match ($data['do']) {
                'publish' => tap($a)->update(['status' => 'publié', 'published_at' => $a->published_at?->isFuture() ? now() : ($a->published_at ?? now())]),
                'unpublish' => $a->update(['status' => 'brouillon']),
                'feature' => $a->update(['is_featured' => true]),
                'unfeature' => $a->update(['is_featured' => false]),
                'delete' => $a->delete(),
            };
        }
        SiteCache::flush();
        $label = ['publish' => 'publié', 'unpublish' => 'repassé en brouillon', 'delete' => 'supprimé', 'feature' => 'mis à la une', 'unfeature' => 'retiré de la une'][$data['do']];
        ActivityLog::record('a '.$label.' '.$items->count().' article(s)');
        if ($data['do'] === 'publish') app(IndexNow::class)->submit($items->map->url()->all());

        return back()->with('ok', $items->count().' article(s) '.$label.(($items->count() > 1) ? 's' : '').'.');
    }

    public function destroy(Article $article)
    {
        abort_unless(auth()->user()->canDo('edit-others') || $this->isOwn($article), 403);
        $title = $article->title;
        $article->delete();
        SiteCache::flush();
        ActivityLog::record('a supprimé « '.$title.' »');

        return redirect()->route('admin.articles')->with('ok', 'Article supprimé.');
    }

    public function calendar(Request $r)
    {
        $month = $r->query('mois') && preg_match('/^\d{4}-\d{2}$/', $r->query('mois')) ? Carbon::createFromFormat('Y-m-d', $r->query('mois').'-01') : now()->startOfMonth();
        $month = $month->startOfMonth();
        $start = $month->copy()->startOfWeek(Carbon::MONDAY);
        $items = Article::whereBetween('published_at', [$start, $start->copy()->addDays(42)])->orderBy('published_at')->get()->groupBy(fn ($a) => $a->published_at->toDateString());
        $days = [];
        for ($i = 0; $i < 42; $i++) {
            $d = $start->copy()->addDays($i);
            $days[] = ['d' => $d, 'out' => $d->month !== $month->month, 'today' => $d->isToday(), 'items' => $items->get($d->toDateString(), collect())];
        }

        return view('admin.articles.calendar', compact('month', 'days'));
    }

    protected function isOwn(Article $a): bool
    {
        $u = auth()->user();

        return $a->created_by === $u->id || ($u->author_id && $a->author_id === $u->author_id);
    }

    protected function authorizeEdit(Article $a): void
    {
        abort_unless(auth()->user()->canDo('edit-others') || $this->isOwn($a), 403, 'Vous ne pouvez modifier que vos propres articles.');
    }
}
