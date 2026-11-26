<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Publication;
use App\Support\SiteCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LibraryController extends Controller
{
    public function index()
    {
        return view('admin.library', ['pubs' => Publication::orderByDesc('published_on')->get()]);
    }

    protected function rules(): array
    {
        return ['title' => 'required|string|max:150', 'subtitle' => 'nullable|string|max:200', 'type' => ['required', Rule::in(Publication::TYPES)],
            'pages' => 'nullable|integer|min:0|max:5000', 'price' => 'nullable|integer|min:0', 'color' => 'required|in:ink,red,stone',
            'description' => 'nullable|string|max:1200', 'published_on' => 'nullable|date', 'toc' => 'nullable|string|max:2000',
            'file' => 'nullable|file|max:51200|mimes:pdf,epub,zip'];
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules(), ['title.required' => 'Le titre est obligatoire.']);
        $p = new Publication();
        $this->fill($p, $d, $r);
        $p->slug = $this->slug($d['title']);
        $p->save();
        SiteCache::flush();
        ActivityLog::record('a ajouté la publication « '.$p->title.' »');

        return back()->with('ok', 'Publication ajoutée.');
    }

    public function update(Request $r, Publication $publication)
    {
        if ($r->has('price_only')) {
            $publication->update($r->validate(['price' => 'required|integer|min:0']));

            return back()->with('ok', 'Prix mis à jour.');
        }
        $d = $r->validate($this->rules());
        $this->fill($publication, $d, $r);
        $publication->save();
        SiteCache::flush();
        ActivityLog::record('a modifié la publication « '.$publication->title.' »');

        return back()->with('ok', 'Publication enregistrée.');
    }

    protected function fill(Publication $p, array $d, Request $r): void
    {
        $p->fill(collect($d)->except(['file', 'toc'])->all());
        $p->pages = $d['pages'] ?? 0; $p->price = $d['price'] ?? 0;
        $p->published_on = $d['published_on'] ?? now();
        $p->format = $r->hasFile('file') ? strtoupper($r->file('file')->getClientOriginalExtension()) : ($p->format ?: 'PDF');
        $p->toc = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($d['toc'] ?? ''))))) ?: ($p->toc ?? []);
        if ($r->hasFile('file')) {
            if ($p->file_path) Storage::disk('local')->delete($p->file_path);
            $p->file_path = $r->file('file')->store('publications', 'local');
        }
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path) Storage::disk('local')->delete($publication->file_path);
        $publication->delete();
        SiteCache::flush();
        ActivityLog::record('a supprimé la publication « '.$publication->title.' »');

        return back()->with('ok', 'Publication supprimée.');
    }

    protected function slug(string $t): string
    {
        $b = Str::slug($t) ?: 'publication'; $s = $b; $i = 2;
        while (Publication::where('slug', $s)->exists()) $s = $b.'-'.$i++;

        return $s;
    }
}
