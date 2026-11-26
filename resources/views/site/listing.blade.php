@extends('layouts.site', ['page' => $mode, 'activeRub' => $rub?->slug])
@section('content')
@php
  $baseRoute = $mode === 'rubrique' ? route('rubrique', $rub) : route('theme', $theme);
  $qs = fn (array $over) => $baseRoute.'?'.http_build_query(array_filter(array_merge(['sous' => $sub, $mode === 'rubrique' ? 'theme' : 'rubrique' => $filter, 'tri' => $sort === 'populaires' ? 'populaires' : null], $over)));
  $items = $articles->getCollection();
  $sponsor = ($runningAds ?? collect())->get('sponsor');
@endphp
<div class="wrap ad-band pt-4">@include('partials.ad', ['pos' => 'leaderboard'])</div>
<header class="rh mt-4">
  <span class="rh-ring" data-parallax="-.6" aria-hidden="true"></span>
  <div class="wrap rh-in">
    <nav class="crumbs light" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>/</span><span>{{ $mode === 'rubrique' ? 'Rubriques' : 'Thématiques' }}</span></nav>
    @if ($mode === 'rubrique')<span class="pillar-tag light">{{ $rub->pillar }}</span>@endif
    <h1>{{ $mode === 'rubrique' ? $rub->name : $theme->name }}@if ($sub)<span class="visually-hidden"> · {{ $sub }}</span>@endif</h1>
    <p class="rh-promise">{{ $mode === 'rubrique' ? $rub->promise : 'Tous nos contenus sur cette thématique, toutes rubriques confondues.' }}</p>
    @if ($mode === 'rubrique')<p class="rh-obj"><b>Objectif.</b> {{ $rub->description }}</p>@endif
    <p class="rh-count">{{ $articles->total() }} contenu{{ $articles->total() > 1 ? 's' : '' }}</p>
    @if ($mode === 'rubrique' && $sponsor && $rub->slug === 'startups')<span class="rh-sponsor">Rubrique présentée par <b>{{ $sponsor->advertiser }}</b> · Partenariat</span>@endif
  </div>
</header>
<div class="filters-bar"><div class="wrap fb-in">
  @if ($mode === 'rubrique' && $rub->subs)
  <div class="chips" role="group" aria-label="Sous-rubriques">
    <a class="chip" href="{{ $qs(['sous' => null]) }}" aria-pressed="{{ $sub ? 'false' : 'true' }}">Tout</a>
    @foreach ($rub->subs as $s)
      <a class="chip" href="{{ $qs(['sous' => $s]) }}" aria-pressed="{{ $sub === $s ? 'true' : 'false' }}">{{ $s }} <span class="c">{{ $subCounts[$s] ?? 0 }}</span></a>
    @endforeach
  </div>
  @endif
  <form class="filter-sel" method="GET" action="{{ $baseRoute }}" data-autosubmit>
    @if ($sub)<input type="hidden" name="sous" value="{{ $sub }}">@endif
    @if ($mode === 'rubrique')
      <label for="f-theme" class="visually-hidden">Thématique</label>
      <select id="f-theme" name="theme" class="form-select"><option value="">Toutes les thématiques</option>@foreach ($themes as $t)<option value="{{ $t->slug }}" @selected($filter === $t->slug)>{{ $t->name }}</option>@endforeach</select>
    @else
      <label for="f-rub" class="visually-hidden">Rubrique</label>
      <select id="f-rub" name="rubrique" class="form-select"><option value="">Toutes les rubriques</option>@foreach ($rubriques as $r)<option value="{{ $r->slug }}" @selected($filter === $r->slug)>{{ $r->name }}</option>@endforeach</select>
    @endif
    <label for="f-sort" class="visually-hidden">Trier</label>
    <select id="f-sort" name="tri" class="form-select"><option value="">Plus récents</option><option value="populaires" @selected($sort === 'populaires')>Plus lus</option></select>
    <noscript><button class="btn btn-ink" type="submit">Filtrer</button></noscript>
  </form>
</div></div>
<div class="wrap with-side pt-5">
  <section>
    @if ($items->count())
      @include('partials.card', ['a' => $items->first(), 'variant' => 'big'])
      <div class="grid-2 mt-5">
        @foreach ($items->slice(1)->values() as $i => $a)
          @include('partials.card', ['a' => $a])
          @if ($i === 1)@include('partials.native', ['class' => 'card-native'])@endif
        @endforeach
      </div>
      {{ $articles->links() }}
    @else
      <div class="empty"><p>Aucun contenu ne correspond à ces filtres pour l’instant.</p><a class="btn btn-ink" href="{{ $baseRoute }}">Tout afficher</a></div>
    @endif

    @if ($mode === 'rubrique' && $rub->slug === 'campus')
    <section class="opps-page" id="opportunites">
      <header class="bh small mt-5"><div><h2>Emploi & opportunités</h2></div></header>
      <div class="chips mb-3" role="group" aria-label="Type d’opportunité">
        <a class="chip" href="{{ route('rubrique', $rub) }}#opportunites" aria-pressed="{{ $oppType ? 'false' : 'true' }}">Toutes</a>
        @foreach (\App\Models\Opportunity::TYPES as $t)<a class="chip" href="{{ route('rubrique', [$rub, 'opportunite' => $t]) }}#opportunites" aria-pressed="{{ $oppType === $t ? 'true' : 'false' }}">{{ $t }}</a>@endforeach
      </div>
      <ul class="opp-list">
        @forelse ($opps as $o)
          <li><span class="opp-type">{{ $o->type }}</span><div><span class="opp-t">@if ($o->url)<a href="{{ $o->url }}" target="_blank" rel="noopener">{{ $o->title }}</a>@else{{ $o->title }}@endif</span><span class="meta">{{ $o->organization }} · {{ $o->location }}</span></div><span class="opp-d">@if ($o->deadline){{ $o->deadline->isPast() ? 'Clôturé le' : 'Jusqu’au' }} {{ fr_date($o->deadline) }}@endif</span></li>
        @empty
          <li class="muted">Aucune opportunité de ce type pour le moment.</li>
        @endforelse
      </ul>
      <p class="note">Une opportunité à partager ? <a href="{{ route('contact') }}">Écrivez à la rédaction</a>.</p>
    </section>
    @endif
  </section>
  @include('partials.sidebar')
</div>
@endsection
