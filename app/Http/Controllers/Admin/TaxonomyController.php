<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Dossier;
use App\Models\Opportunity;
use App\Models\Rubrique;
use App\Models\Theme;
use App\Support\SiteCache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    public function index()
    {
        return view('admin.taxonomies', [
            'rubriques' => Rubrique::withCount('articles')->orderBy('position')->get(),
            'themes' => Theme::withCount('articles')->orderBy('name')->get(),
            'dossiers' => Dossier::withCount('articles')->with('articles:id,title')->orderBy('position')->get(),
            'opportunities' => Opportunity::orderBy('deadline')->get(),
            'articles' => Article::published()->latestFirst()->get(['id', 'title']),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:60|unique:rubriques,name']);
        Rubrique::create(['name' => $d['name'], 'slug' => $this->slug(Rubrique::class, $d['name']), 'pillar' => 'Rubrique', 'promise' => '', 'subs' => [], 'position' => Rubrique::max('position') + 1]);
        $this->done('a créé la rubrique '.$d['name']);

        return back()->with('ok', 'Rubrique créée.');
    }

    public function update(Request $r, Rubrique $rubrique)
    {
        $d = $r->validate(['name' => 'required|string|max:60', 'pillar' => 'nullable|string|max:60', 'promise' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:400', 'subs' => 'nullable|string|max:600', 'position' => 'nullable|integer|min:0|max:99']);
        $d['subs'] = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($d['subs'] ?? ''))))));
        $rubrique->update($d);
        $this->done('a modifié la rubrique '.$rubrique->name);

        return back()->with('ok', 'Rubrique « '.$rubrique->name.' » enregistrée.');
    }

    public function destroy(Rubrique $rubrique)
    {
        if ($rubrique->articles()->exists()) return back()->with('err', 'Videz la rubrique avant de la supprimer.');
        $rubrique->delete();
        $this->done('a supprimé la rubrique '.$rubrique->name);

        return back()->with('ok', 'Rubrique supprimée.');
    }

    public function storeTheme(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:60|unique:themes,name']);
        Theme::create(['name' => $d['name'], 'slug' => $this->slug(Theme::class, $d['name'])]);
        $this->done('a créé la thématique '.$d['name']);

        return back()->with('ok', 'Thématique ajoutée.');
    }

    public function destroyTheme(Theme $theme)
    {
        Article::where('theme_id', $theme->id)->update(['theme_id' => null]);
        $theme->delete();
        $this->done('a supprimé la thématique '.$theme->name);

        return back()->with('ok', 'Thématique supprimée.');
    }

    public function storeDossier(Request $r)
    {
        $d = $r->validate(['title' => 'required|string|max:120', 'chapo' => 'nullable|string|max:400']);
        Dossier::create($d + ['slug' => $this->slug(Dossier::class, $d['title']), 'tone' => random_int(0, 3), 'position' => Dossier::max('position') + 1]);
        $this->done('a créé le dossier '.$d['title']);

        return back()->with('ok', 'Dossier créé.');
    }

    public function updateDossier(Request $r, Dossier $dossier)
    {
        $d = $r->validate(['title' => 'required|string|max:120', 'chapo' => 'nullable|string|max:400', 'tone' => 'nullable|integer|min:0|max:3',
            'articles' => 'nullable|array', 'articles.*' => 'integer|exists:articles,id']);
        $dossier->update(collect($d)->except('articles')->all());
        $dossier->articles()->sync(collect($d['articles'] ?? [])->values()->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
        $this->done('a modifié le dossier '.$dossier->title);

        return back()->with('ok', 'Dossier enregistré.');
    }

    public function destroyDossier(Dossier $dossier)
    {
        $dossier->delete();
        $this->done('a supprimé le dossier '.$dossier->title);

        return back()->with('ok', 'Dossier supprimé.');
    }

    public function storeOpportunity(Request $r)
    {
        $d = $r->validate(['type' => ['required', Rule::in(Opportunity::TYPES)], 'title' => 'required|string|max:160', 'organization' => 'nullable|string|max:120',
            'location' => 'nullable|string|max:80', 'deadline' => 'nullable|date', 'url' => 'nullable|url|max:250']);
        Opportunity::create($d);
        $this->done('a ajouté l’opportunité '.$d['title']);

        return back()->with('ok', 'Opportunité ajoutée.');
    }

    public function destroyOpportunity(Opportunity $opportunity)
    {
        $opportunity->delete();
        $this->done('a supprimé l’opportunité '.$opportunity->title);

        return back()->with('ok', 'Opportunité supprimée.');
    }

    protected function slug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'element'; $s = $base; $i = 2;
        while ($model::where('slug', $s)->exists()) $s = $base.'-'.$i++;

        return $s;
    }

    protected function done(string $msg): void
    {
        SiteCache::flush();
        ActivityLog::record($msg);
    }
}
