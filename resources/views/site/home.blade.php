@extends('layouts.site', ['page' => 'home'])
@section('content')
<h1 class="visually-hidden">{{ setting('name') }} : talents, innovations et actualités de la Tech</h1>

{{-- À LA UNE --}}
@if ($hero->count())
<section class="hs" aria-roledescription="carrousel" aria-label="À la une" tabindex="-1" data-slider>
  <div class="hs-stage">
    @foreach ($hero as $i => $a)
    <article class="hs-slide {{ $i === 0 ? 'on' : '' }}" role="group" aria-roledescription="diapositive" aria-label="{{ $i + 1 }} sur {{ $hero->count() }}" aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" data-slide>
      <a class="hs-link" href="{{ $a->url() }}" tabindex="-1" aria-hidden="true"></a>
      <div class="hs-bg v{{ $a->hue() }}" aria-hidden="true"><span class="hs-ring"></span><span class="hs-ring sm"></span><span class="hs-grain"></span></div>
      <div class="wrap hs-in">
        <div class="hs-txt">
          <p class="hs-kicker"><span class="dot"></span>À la une <span class="sep">/</span> {{ $a->rubrique?->name }} <span class="sep">/</span> {{ $a->label() }}</p>
          <h2 class="hs-title"><a href="{{ $a->url() }}" tabindex="{{ $i === 0 ? 0 : -1 }}">{{ $a->title }}</a></h2>
          <p class="hs-chapo">{{ $a->chapo }}</p>
          <div class="hs-meta"><span class="av sm" aria-hidden="true">{{ initials($a->author?->name) }}</span><span>{{ $a->author?->name }}</span><span class="sep">·</span><span>{{ $a->read_time }} min de lecture</span></div>
          <a class="hs-cta" href="{{ $a->url() }}" tabindex="{{ $i === 0 ? 0 : -1 }}">Lire l’article {!! svg_icon('arrow') !!}</a>
        </div>
      </div>
    </article>
    @endforeach
  </div>
  <div class="wrap hs-ctrl">
    <ol class="hs-steps">
      @foreach ($hero as $i => $a)
      <li><button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-go="{{ $i }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Article {{ $i + 1 }} : {{ $a->title }}">
        <span class="hs-bar"><i></i></span><span class="hs-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><span class="hs-st">{{ $a->title }}</span></button></li>
      @endforeach
    </ol>
    <div class="hs-arrows">
      <button type="button" class="hs-btn" data-pause aria-label="Mettre en pause"><svg class="svg-i i-pause"><use href="#i-pause"/></svg><svg class="svg-i i-play"><use href="#i-play"/></svg></button>
      <button type="button" class="hs-btn" data-prev aria-label="Article précédent"><svg class="svg-i" style="transform:rotate(180deg)"><use href="#i-arrow"/></svg></button>
      <button type="button" class="hs-btn" data-next aria-label="Article suivant">{!! svg_icon('arrow') !!}</button>
    </div>
  </div>
</section>
@endif

{{-- FLASH TECH --}}
<section class="flash" aria-label="Flash Tech">
  <div class="wrap flash-in">
    <h2 class="flash-lbl"><span class="live"></span>Flash Tech</h2>
    <div class="flash-tk"><div class="flash-track">
      @foreach ([1, 2] as $n)
        @foreach ($flash as $a)
          <a href="{{ $a->url() }}" @if($n === 2) tabindex="-1" aria-hidden="true" @endif><time datetime="{{ $a->published_at?->toDateString() }}">{{ fr_date($a->published_at, true) }}</time><b>{{ $a->rubrique?->name }}</b>{{ $a->title }}</a>
        @endforeach
      @endforeach
    </div></div>
    @if ($rubs->has('actualites'))<a href="{{ route('rubrique', $rubs['actualites']) }}" class="flash-all d-none d-md-inline-flex">Tout le fil {!! svg_icon('arrow') !!}</a>@endif
  </div>
</section>

<div class="wrap ad-band">@include('partials.ad', ['pos' => 'leaderboard'])</div>

{{-- DERNIÈRES ACTUALITÉS --}}
<section class="block pt-4">
  <div class="wrap with-side">
    <section>
      <header class="bh rv"><div><span class="pillar-tag">Publications récentes</span><h2>Dernières actualités</h2></div>@if ($rubs->has('actualites'))<a href="{{ route('rubrique', $rubs['actualites']) }}" class="arrow-link">{{ $rubs['actualites']->name }} {!! svg_icon('arrow') !!}</a>@endif</header>
      <div class="list-rows">
        @foreach ($latest as $i => $a)
          @include('partials.card', ['a' => $a, 'variant' => 'row'])
          @if ($i === 2)@include('partials.native')@endif
        @endforeach
      </div>
    </section>
    @include('partials.sidebar')
  </div>
</section>

{{-- TALENTS À DÉCOUVRIR --}}
@php($talents = $by->get('talents', collect()))
@if ($talents->count() && $rubs->has('talents'))
<section class="block">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Révéler les talents</span><h2>Talents à découvrir</h2><p class="bh-p">{{ $rubs['talents']->promise }}</p></div><a href="{{ route('rubrique', $rubs['talents']) }}" class="arrow-link">{{ $rubs['talents']->name }} {!! svg_icon('arrow') !!}</a></header></div>
  <div class="wrap talents-grid">
    @include('partials.card', ['a' => $talents[0], 'variant' => 'big'])
    <div class="tg-side">@foreach ($talents->slice(1, 3) as $a)@include('partials.card', ['a' => $a, 'variant' => 'row-sm'])@endforeach</div>
  </div>
</section>
@endif

@if ($spotlight)
@php($q = $spotlight->quote())
<section class="spot">
  <span class="spot-ring" data-parallax=".8" aria-hidden="true"></span>
  <div class="wrap spot-in rv">
    <span class="eyebrow light">Parcours inspirant</span>
    <blockquote class="spot-q">« {{ $q['q'] ?? $spotlight->chapo }} »</blockquote>
    <div class="spot-who"><span class="spot-name">{{ $q['who'] ?? $spotlight->title }}</span><span class="spot-where">{{ $spotlight->country }} · {{ $spotlight->sub }}</span></div>
    <a class="btn btn-light-c" href="{{ $spotlight->url() }}">Lire le portrait {!! svg_icon('arrow') !!}</a>
  </div>
</section>
@endif

{{-- INNOVATIONS DU MONDE --}}
@if ($rubs->has('innovations') && $by->get('innovations'))
<section class="block">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Explorer les innovations</span><h2>Innovations du monde</h2><p class="bh-p">{{ $rubs['innovations']->promise }}</p></div><a href="{{ route('rubrique', $rubs['innovations']) }}" class="arrow-link">Toutes les innovations {!! svg_icon('arrow') !!}</a></header></div>
  <div class="wrap">
    <div class="rail" data-rail>
      <div class="rail-nav"><button type="button" class="r-btn" data-rail-prev disabled aria-label="Faire défiler vers la gauche : Innovations du monde"><svg class="svg-i" style="transform:rotate(180deg)"><use href="#i-arrow"/></svg></button>
        <button type="button" class="r-btn" data-rail-next aria-label="Faire défiler vers la droite : Innovations du monde">{!! svg_icon('arrow') !!}</button></div>
      <div class="rail-track" tabindex="0" aria-label="Innovations du monde">
        @foreach ($by['innovations'] as $a)
          <a href="{{ $a->url() }}" class="rcard">@include('partials.visual', ['a' => $a])
            <span class="rc-status">{{ $a->fiche['statut'] ?? $a->sub }}</span><span class="rc-t">{{ $a->title }}</span>
            <span class="meta">{{ $a->fiche ? ($a->fiche['porteur'] ?? '').' · '.($a->fiche['pays'] ?? '') : $a->country }}</span></a>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endif

{{-- STARTUPS À SUIVRE --}}
@if ($rubs->has('startups'))
@php($sponsor = ($runningAds ?? collect())->get('sponsor'))
<section class="block">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Startups & Business</span><h2>Startups à suivre</h2><p class="bh-p">{{ $rubs['startups']->promise }}</p></div>
    <div class="bh-right">@if ($sponsor)<span class="sponsor-mini">Rubrique présentée par <b>{{ $sponsor->advertiser }}</b></span>@endif<a href="{{ route('rubrique', $rubs['startups']) }}" class="arrow-link">{{ $rubs['startups']->name }} {!! svg_icon('arrow') !!}</a></div></header></div>
  <div class="wrap startups">
    @foreach ($by->get('startups', collect())->take(4) as $a)
      <a href="{{ $a->url() }}" class="su-card rv">
        <span class="su-top"><span class="su-sub">{{ $a->sub }}</span><span class="su-ini" aria-hidden="true">{{ initials(str_replace(['«', '»', ':'], '', $a->title)) }}</span></span>
        <span class="su-t">{{ $a->title }}</span>
        @if ($a->startup)<span class="su-facts"><span>{{ $a->startup['secteur'] ?? '' }}</span><span>{{ $a->startup['pays'] ?? '' }}</span><span>{{ $a->startup['stade'] ?? '' }}</span></span>
        @else<span class="su-facts"><span>{{ $a->label() }}</span><span>{{ $a->read_time }} min</span></span>@endif
      </a>
    @endforeach
  </div>
</section>
@endif

<div class="wrap">@include('partials.ad', ['pos' => 'billboard'])</div>

{{-- DÉCRYPTAGES --}}
@if ($rubs->has('decryptages'))
<section class="block pt-0">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Décrypter la Tech</span><h2>Décryptages</h2><p class="bh-p">{{ $rubs['decryptages']->promise }}</p></div><a href="{{ route('rubrique', $rubs['decryptages']) }}" class="arrow-link">Tous les décryptages {!! svg_icon('arrow') !!}</a></header></div>
  <div class="wrap reveler">
    @if ($decryptFeature)@include('partials.card', ['a' => $decryptFeature, 'variant' => 'big'])@endif
    <div class="rev-side">
      @if ($factcheck)
        <a href="{{ $factcheck->url() }}" class="fc-card rv"><span class="fc-lbl">Fact-checking</span><span class="fc-verdict v-{{ \Illuminate\Support\Str::slug($factcheck->verdict['label']) }}">{{ $factcheck->verdict['label'] }}</span>
          <span class="fc-t">{{ $factcheck->title }}</span><span class="meta">{{ $factcheck->verdict['text'] }}</span></a>
      @endif
      @foreach ($decryptOthers as $a)@include('partials.card', ['a' => $a, 'variant' => 'text'])@endforeach
    </div>
  </div>
</section>
@endif

{{-- CAMPUS & CARRIÈRES --}}
@if ($rubs->has('campus'))
<section class="block">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Campus & Carrières</span><h2>Métiers, formations et opportunités</h2><p class="bh-p">{{ $rubs['campus']->promise }}</p></div><a href="{{ route('rubrique', $rubs['campus']) }}" class="arrow-link">{{ $rubs['campus']->name }} {!! svg_icon('arrow') !!}</a></header></div>
  <div class="wrap campus">
    <div class="campus-arts">@foreach ($by->get('campus', collect())->take(3) as $a)@include('partials.card', ['a' => $a])@endforeach</div>
    <aside class="opps rv">
      <p class="itw-lbl">Opportunités</p>
      <ul>@foreach ($opportunities as $o)<li><span class="opp-type">{{ $o->type }}</span><span class="opp-t">{{ $o->title }}</span><span class="meta">{{ $o->organization }} · {{ $o->location }}@if ($o->deadline) · jusqu’au {{ fr_date($o->deadline, true) }}@endif</span></li>@endforeach
        @if ($opportunities->isEmpty())<li class="muted">De nouvelles opportunités arrivent bientôt.</li>@endif</ul>
      <a href="{{ route('rubrique', $rubs['campus']) }}#opportunites" class="arrow-link">Toutes les opportunités {!! svg_icon('arrow') !!}</a>
    </aside>
  </div>
</section>
@endif

{{-- DOSSIERS SPÉCIAUX --}}
@if ($dossiers->count())
<section class="block">
  <div class="wrap"><header class="bh rv"><div><span class="pillar-tag">Enquêtes et grandes thématiques</span><h2>Dossiers spéciaux</h2></div><a href="{{ route('dossiers') }}" class="arrow-link">Tous les dossiers {!! svg_icon('arrow') !!}</a></header></div>
  <div class="wrap dossiers">
    @foreach ($dossiers as $i => $d)
      <a href="{{ route('dossier', $d) }}" class="dos t{{ $d->tone }} {{ $i === 0 ? 'wide' : '' }} rv"><span class="dos-ring" aria-hidden="true"></span>
        <span class="dos-n">Dossier · {{ $d->articles->count() }} articles</span><span class="dos-t">{{ $d->title }}</span>@if ($i === 0)<span class="dos-c">{{ $d->chapo }}</span>@endif</a>
    @endforeach
  </div>
</section>
@endif

{{-- NEWSLETTER --}}
<section class="nl-band" id="newsletter">
  <div class="wrap nl-in rv">
    <div><span class="eyebrow light">Newsletter VOX · chaque semaine</span><h2>Les talents, innovations et décryptages de la semaine, dans votre boîte mail.</h2></div>
    @include('partials.nl-form', ['source' => 'Accueil', 'variant' => 'home', 'class' => 'nl-form'])
  </div>
</section>

{{-- PROPOSER --}}
<section class="cta-band"><span class="cta-ring" data-parallax="1.2" aria-hidden="true"></span>
  <div class="wrap cta-in rv"><div><span class="eyebrow light">Proposer un talent ou une innovation</span><h2>Vous connaissez un talent, une invention ou une startup à faire découvrir ?</h2><p>Chaque proposition est examinée selon nos critères éditoriaux.</p></div>
    <a class="btn btn-light-c" href="{{ route('proposer') }}">Soumettre à la rédaction {!! svg_icon('arrow') !!}</a></div>
</section>
@endsection
