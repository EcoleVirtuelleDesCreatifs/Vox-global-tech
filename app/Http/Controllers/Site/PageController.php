<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AdSlot;
use App\Models\Article;
use App\Models\Author;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Support\Seo;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function library(Request $r, Seo $seo)
    {
        $type = (string) $r->query('type', '');
        $pubs = Publication::when($type, fn ($q) => $q->where('type', $type))->orderByDesc('published_on')->get();
        $types = Publication::select('type')->distinct()->pluck('type');
        $seo->title('Bibliothèque : magazines, e-books, rapports')->description('Magazines, livres, e-books, rapports et documentations techniques des inventions suivies par '.setting('name').'.');
        $seo->canonical = route('bibliotheque');
        $seo->crumbs([['Accueil', route('home')], ['Bibliothèque', route('bibliotheque')]]);

        return view('site.library', compact('pubs', 'types', 'type'));
    }

    public function publication(Publication $publication, Seo $seo)
    {
        $seo->publication($publication);
        $seo->crumbs([['Accueil', route('home')], ['Bibliothèque', route('bibliotheque')], [$publication->title, $publication->url()]]);

        return view('site.publication', ['pub' => $publication]);
    }

    public function newsletter(Seo $seo)
    {
        $seo->title('Newsletter VOX : les talents et innovations de la semaine')->description('Chaque semaine, une sélection des principales actualités, découvertes, innovations et publications de '.setting('name').'. Gratuite.');
        $seo->canonical = route('newsletter');
        $preview = Article::published()->with('rubrique')->latestFirst()->limit(3)->get();

        return view('site.newsletter', compact('preview'));
    }

    public function proposer(Seo $seo)
    {
        $seo->title('Proposer un talent ou une innovation')->description('Lecteurs, chercheurs, inventeurs, développeurs et entrepreneurs : soumettez un talent, une invention ou un projet technologique à la rédaction.');
        $seo->canonical = route('proposer');

        return view('site.proposer', ['rubriques' => Rubrique::orderBy('position')->get(), 'kinds' => Submission::KINDS]);
    }

    public function about(Request $r, Seo $seo)
    {
        $charte = $r->routeIs('charte');
        $seo->title($charte ? 'Charte éditoriale et vérification' : 'À propos : qui sommes-nous ?')
            ->description($charte ? 'Notre charte éditoriale : règles de rédaction, vérification des faits, distinction faits/opinions, corrections transparentes et usage encadré de l’IA.'
                : 'VOX TECH GLOBAL (VTG) est un média numérique international dédié aux talents, startups, innovations et initiatives technologiques, initié par l’École Virtuelle des Créatifs.');
        $seo->canonical = $charte ? route('charte') : route('apropos');
        $seo->add(['@type' => $charte ? 'WebPage' : 'AboutPage', '@id' => $seo->canonical.'#about', 'url' => $seo->canonical, 'about' => ['@id' => $seo->siteUrl().'/#organization']]);

        return view('site.about', ['charte' => $charte, 'authors' => Author::orderBy('id')->get()]);
    }

    public function advertise(Seo $seo)
    {
        $seo->title('Annoncer : publicité et partenariats')->description('Bandeaux, pavés, articles sponsorisés et partenariats : touchez une audience jeune, qualifiée et tournée vers la technologie.');
        $seo->canonical = route('annoncer');

        return view('site.advertise', ['slots' => AdSlot::orderBy('position')->get()]);
    }

    public function contact(Seo $seo)
    {
        $seo->title('Contact')->description('Une question, une correction, une idée de sujet ? Écrivez à la rédaction de '.setting('name').'.');
        $seo->canonical = route('contact');
        $seo->add(['@type' => 'ContactPage', '@id' => route('contact').'#contact', 'url' => route('contact')]);

        return view('site.contact');
    }

    public function legal(Seo $seo)
    {
        $seo->title('Mentions légales')->description('Mentions légales de '.setting('name').'.');
        $seo->canonical = route('mentions');

        return view('site.legal');
    }

    public function privacy(Seo $seo)
    {
        $seo->title('Politique de confidentialité')->description('Comment '.setting('name').' traite vos données personnelles.');
        $seo->canonical = route('confidentialite');

        return view('site.privacy');
    }
}
