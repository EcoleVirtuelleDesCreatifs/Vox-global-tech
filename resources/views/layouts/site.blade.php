@php($seo = app(\App\Support\Seo::class))
<!doctype html>
<html lang="fr" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $seo->finalTitle() }}</title>
<meta name="description" content="{{ $seo->finalDescription() }}">
<meta name="robots" content="{{ $seo->robots }}">
<link rel="canonical" href="{{ $seo->canonical ?: url()->current() }}">
<link rel="alternate" hreflang="fr" href="{{ $seo->canonical ?: url()->current() }}">
<link rel="alternate" hreflang="x-default" href="{{ $seo->canonical ?: url()->current() }}">
<meta name="theme-color" content="#111214" media="(prefers-color-scheme: dark)"><meta name="theme-color" content="#F6F5F2" media="(prefers-color-scheme: light)">
<meta name="author" content="{{ setting('name') }}">
<meta name="generator" content="VOX TECH GLOBAL · Laravel">
@if (setting('google_verification'))<meta name="google-site-verification" content="{{ setting('google_verification') }}">@endif
@if (setting('bing_verification'))<meta name="msvalidate.01" content="{{ setting('bing_verification') }}">@endif
{{-- Open Graph / réseaux sociaux --}}
<meta property="og:site_name" content="{{ setting('name') }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:title" content="{{ $seo->finalTitle() }}">
<meta property="og:description" content="{{ $seo->finalDescription() }}">
<meta property="og:url" content="{{ $seo->canonical ?: url()->current() }}">
<meta property="og:image" content="{{ $seo->image ?: asset('assets/img/og-default.png') }}">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seo->finalTitle() }}">
@foreach (array_filter($seo->meta) as $k => $v)<meta property="{{ $k }}" content="{{ $v }}">
@endforeach
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->finalTitle() }}">
<meta name="twitter:description" content="{{ $seo->finalDescription() }}">
<meta name="twitter:image" content="{{ $seo->image ?: asset('assets/img/og-default.png') }}">
{{-- Découverte : flux, version Markdown pour les IA, manifeste --}}
<link rel="alternate" type="application/rss+xml" title="{{ setting('name') }}" href="{{ $seo->rss ?: route('feed') }}">
<link rel="alternate" type="application/feed+json" title="{{ setting('name') }} (JSON Feed)" href="{{ route('feed.json') }}">
@if ($seo->markdown)<link rel="alternate" type="text/markdown" title="Version Markdown" href="{{ $seo->markdown }}">@endif
<link rel="help" type="text/plain" href="{{ route('llms') }}" title="llms.txt">
<link rel="manifest" href="{{ route('manifest') }}">
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('assets/img/logo-512.png') }}">
<link rel="preload" href="{{ asset('assets/fonts/outfit-var.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/vox.css') }}?v={{ filemtime(public_path('assets/css/vox.css')) }}">
<script>(function(){try{var t=localStorage.getItem('vx-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}
/* Anciennes adresses de la maquette (#article-…, #rubrique-…) → nouvelles URL */
var h=location.hash.slice(1),m=h.match(/^(article|rubrique|theme|auteur|publication|dossier)-(.+)$/),s={'a-propos':'a-propos','newsletter':'newsletter','proposer':'proposer','contact':'contact','annoncer':'annoncer','bibliotheque':'bibliotheque','dossiers-speciaux':'dossiers','charte-editoriale':'charte-editoriale','mentions':'mentions-legales','confidentialite':'confidentialite','recherche':'recherche','admin':'admin'};
if(m)location.replace('/'+m[1]+'/'+m[2]);else if(s[h]&&location.pathname==='/')location.replace('/'+s[h]);})();</script>
<script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
@stack('head')
</head>
<body>
@include('partials.sprite')
<a class="skip-link" href="#contenu">Aller au contenu</a>
<div class="site v4 v5 r-{{ $page ?? 'page' }}" data-pv="0" data-collect="{{ route('collect') }}">
  @if (($page ?? '') === 'article')<div class="progress-read" aria-hidden="true"></div>@endif
  @if (setting('maintenance'))<div class="maint" role="status">Le site est en maintenance : certaines fonctions peuvent être indisponibles.</div>@endif

  <div class="ub"><div class="wrap ub-in">
    <span class="ub-date">{{ ucfirst(now()->locale('fr')->translatedFormat('l j F Y')) }}</span>
    <span class="ub-sig d-none d-xl-inline">Révéler les talents · Explorer les innovations · Décrypter la Tech</span>
    <nav class="ub-links" aria-label="Accès rapides"><a href="{{ route('newsletter') }}">Newsletter VOX</a><a href="{{ route('dossiers') }}" class="d-none d-sm-inline">Dossiers spéciaux</a><a href="{{ route('annoncer') }}" class="d-none d-md-inline">Annonceurs</a><a href="{{ route('admin.dashboard') }}" rel="nofollow">Espace admin</a></nav>
  </div></div>

  <header class="mh" id="mh">
    <div class="wrap mh-in">
      <button class="mh-burger d-lg-none" type="button" data-drawer aria-expanded="false" aria-controls="drawer" aria-label="Menu"><span></span><span></span></button>
      <a href="{{ route('home') }}" class="brand" aria-label="VOX TECH GLOBAL, accueil">@include('partials.logo')</a>
      <nav class="mh-nav d-none d-lg-flex" aria-label="Rubriques">
        @foreach ($navRubriques as $r)
          <a href="{{ route('rubrique', $r) }}" data-mega="{{ $r->slug }}" class="{{ ($activeRub ?? null) === $r->slug ? 'on' : '' }}" aria-expanded="false">{{ $r->name }}</a>
        @endforeach
        <a href="{{ route('dossiers') }}" class="{{ in_array($page ?? '', ['dossiers', 'dossier'], true) ? 'on' : '' }}" data-mega-close>Dossiers</a>
      </nav>
      <div class="mh-tools">
        <button class="mh-search" type="button" data-search-open aria-label="Rechercher (raccourci : /)">{!! svg_icon('search') !!}<span>Rechercher</span><kbd>/</kbd></button>
        <button class="mh-icon" type="button" data-theme-toggle aria-label="Changer de thème (clair / sombre)"><svg class="svg-i i-dark"><use href="#i-moon"/></svg><svg class="svg-i i-light"><use href="#i-sun"/></svg></button>
        <a class="mh-cta d-none d-md-inline-flex" href="{{ route('proposer') }}">Proposer un talent</a>
      </div>
    </div>
    @php($megaLatest = \Illuminate\Support\Facades\Cache::remember('vox.mega', 600, fn () => \App\Models\Article::published()->with('theme')->latestFirst()->get(['id', 'slug', 'title', 'rubrique_id', 'theme_id', 'published_at', 'cover_id'])->groupBy('rubrique_id')->map->take(2)))
    @foreach ($navRubriques as $r)
    <div class="mega d-none d-lg-block" data-mega-panel="{{ $r->slug }}" hidden>
      <div class="wrap mega-in">
        <div class="mega-intro"><span class="pillar-tag">{{ $r->pillar }}</span><p class="mega-name">{{ $r->name }}</p><p class="mega-promise">{{ $r->promise }}</p>
          <a href="{{ route('rubrique', $r) }}" class="arrow-link">Toute la rubrique {!! svg_icon('arrow') !!}</a></div>
        <ul class="mega-subs">@foreach ($r->subs ?? [] as $s)<li><a href="{{ route('rubrique', [$r, 'sous' => $s]) }}">{{ $s }}</a></li>@endforeach</ul>
        <div class="mega-latest">
          @foreach ($megaLatest->get($r->id, collect()) as $a)
            <a href="{{ $a->url() }}" class="mega-art">@include('partials.visual', ['a' => $a])<span class="t">{{ $a->title }}</span><span class="meta">{{ fr_date($a->published_at) }}</span></a>
          @endforeach
        </div>
      </div>
    </div>
    @endforeach
  </header>

  <nav id="drawer" class="drawer d-lg-none" aria-label="Menu principal" hidden>
    <a href="{{ route('home') }}" class="drawer-home">À la une</a>
    @foreach ($navRubriques as $r)
      <a href="{{ route('rubrique', $r) }}" class="drawer-rub"><span class="pillar-tag">{{ $r->pillar }}</span><span class="n">{{ $r->name }}</span><span class="p">{{ $r->promise }}</span></a>
    @endforeach
    <a href="{{ route('dossiers') }}" class="drawer-rub"><span class="n">Dossiers spéciaux</span><span class="p">Les grandes thématiques et enquêtes.</span></a>
    <div class="drawer-links"><a href="{{ route('newsletter') }}">Newsletter VOX</a><a href="{{ route('proposer') }}">Proposer un talent</a><a href="{{ route('bibliotheque') }}">Bibliothèque</a><a href="{{ route('apropos') }}">À propos</a><a href="{{ route('annoncer') }}">Annoncer</a><a href="{{ route('contact') }}">Contact</a></div>
  </nav>

  <main id="contenu" class="page" tabindex="-1">
    @yield('content')
  </main>

  <footer class="footer v4f">
    <div class="wrap">
      <div class="f-top">
        <div class="f-brand">@include('partials.logo')<p class="sig">{{ setting('tagline') }}</p><p class="f-evc">Un média initié par l’École Virtuelle des Créatifs (EVC).</p></div>
        @include('partials.nl-form', ['source' => 'Pied de page', 'variant' => 'footer', 'class' => 'f-news'])
      </div>
      <div class="foot-grid five">
        <div><h2>Rubriques</h2>@foreach ($navRubriques as $r)<a href="{{ route('rubrique', $r) }}">{{ $r->name }}</a>@endforeach</div>
        <div><h2>À découvrir</h2><a href="{{ route('dossiers') }}">Dossiers spéciaux</a><a href="{{ route('bibliotheque') }}">Bibliothèque</a><a href="{{ route('newsletter') }}">Newsletter VOX</a><a href="{{ route('proposer') }}">Proposer un talent</a><a href="{{ route('feed') }}">Flux RSS</a></div>
        <div><h2>Le média</h2><a href="{{ route('apropos') }}">À propos</a><a href="{{ route('charte') }}">Charte éditoriale</a><a href="{{ route('apropos') }}#ab-equipe">La rédaction</a><a href="{{ route('annoncer') }}">Annoncer</a></div>
        <div><h2>Informations</h2><a href="{{ route('contact') }}">Contact</a><a href="{{ route('mentions') }}">Mentions légales</a><a href="{{ route('confidentialite') }}">Politique de confidentialité</a></div>
        <div><h2>Suivre VTG</h2>
          @php($social = array_filter((array) setting('social')))
          @foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X (Twitter)'] as $k => $lbl)
            @if (!empty($social[$k]))<a href="{{ $social[$k] }}" target="_blank" rel="noopener me">{{ $lbl }}</a>@endif
          @endforeach
          @if (!$social)<a href="{{ route('contact') }}">Nous contacter</a>@endif
        </div>
      </div>
      <div class="foot-bottom"><span>© {{ date('Y') }} {{ setting('name') }} · Révéler les talents. Explorer les innovations. Décrypter la Tech.</span><span><a href="{{ route('sitemap') }}">Plan du site</a> · <a href="{{ route('llms') }}">llms.txt</a></span></div>
    </div>
  </footer>

  <div class="so" id="search-overlay" role="dialog" aria-modal="true" aria-label="Recherche" hidden data-api="{{ route('api.search') }}">
    <div class="so-box">
      <form class="so-form" role="search" action="{{ route('recherche') }}" method="GET">
        {!! svg_icon('search') !!}
        <label for="so-in" class="visually-hidden">Rechercher</label>
        <input id="so-in" name="q" type="search" placeholder="Un talent, une startup, une innovation…" autocomplete="off">
        <button type="button" class="so-x" data-search-close aria-label="Fermer la recherche">{!! svg_icon('x') !!}</button>
      </form>
      <div class="so-res" hidden></div>
      <div class="so-sugg"><p class="toc-lbl">Rubriques</p><div class="chips mb-3">@foreach ($navRubriques as $r)<a class="chip" href="{{ route('rubrique', $r) }}">{{ $r->name }}</a>@endforeach</div>
        <p class="toc-lbl">Suggestions</p><div class="chips">@foreach (['Cybersécurité', 'IA', 'Startups', 'Fact-check', 'Formation', 'Femmes dans la Tech'] as $s)<button class="chip" type="button" data-suggest="{{ $s }}">{{ $s }}</button>@endforeach</div></div>
    </div>
  </div>

  @if (setting('ads_on'))
  <div class="ad-mobile-wrap d-md-none" data-ad-mobile>@include('partials.ad', ['pos' => 'mobile'])<button type="button" class="ad-close" data-ad-close aria-label="Fermer la publicité">×</button></div>
  @endif
  <button class="icon-btn back-top" type="button" data-top aria-label="Revenir en haut" hidden>{!! svg_icon('up') !!}</button>
  <div class="toast-container position-fixed bottom-0 end-0 p-3"><div class="vx-toast" role="status" aria-live="polite" hidden></div></div>
</div>
<script src="{{ asset('assets/js/site.js') }}?v={{ filemtime(public_path('assets/js/site.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
