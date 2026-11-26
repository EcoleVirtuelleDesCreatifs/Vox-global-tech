<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Dossier;
use App\Models\Opportunity;
use App\Models\Rubrique;
use App\Support\Seo;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Seo $seo)
    {
        $seo->title(setting('seo_title'), false)->description(setting('seo_description'));
        $seo->canonical = route('home');
        $seo->rss = route('feed');

        $data = Cache::remember('vox.home', 300, function () {
            $pub = Article::published()->with(['rubrique', 'author', 'theme'])->latestFirst()->limit(150)->get();
            $hero = $pub->where('is_featured', true)->concat($pub->where('is_featured', false))->take(5)->values();
            $heroIds = $hero->pluck('id');
            $by = $pub->groupBy(fn ($a) => $a->rubrique?->slug);
            $talents = $by->get('talents', collect());
            $spot = $talents->first(fn ($a) => ! $heroIds->contains($a->id) && $a->quote()) ?? $talents->first();
            $decr = $by->get('decryptages', collect());
            $feature = $decr->first(fn ($a) => ! in_array($a->nature, ['Fact-check', 'Opinion'], true));
            $fc = $decr->first(fn ($a) => (bool) $a->verdict);
            $others = $decr->reject(fn ($a) => $a->is($feature) || $a->is($fc))->take(3)->values();

            return [
                'hero' => $hero,
                'flash' => $pub->take(8),
                'latest' => $pub->reject(fn ($a) => $heroIds->contains($a->id))->take(6)->values(),
                'by' => $by,
                'spotlight' => $spot,
                'decryptFeature' => $feature,
                'factcheck' => $fc,
                'decryptOthers' => $others,
                'opportunities' => Opportunity::where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()))->orderBy('deadline')->limit(4)->get(),
                'dossiers' => Dossier::with(['articles' => fn ($q) => $q->published()])->orderBy('position')->get(),
                'rubs' => Rubrique::orderBy('position')->get()->keyBy('slug'),
            ];
        });

        return view('site.home', $data);
    }
}
