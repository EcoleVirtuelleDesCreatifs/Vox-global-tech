@extends('layouts.site', ['page' => 'recherche'])
@section('content')
<div class="wrap">
  <header class="page-head"><h1>Recherche</h1>
    <form class="search-big" role="search" method="GET" action="{{ route('recherche') }}" data-autosubmit-select>
      <label for="sq" class="visually-hidden">Rechercher</label>
      <input id="sq" name="q" class="form-control" type="search" value="{{ $q }}" placeholder="Un talent, un pays, une innovation…" @if (!$q) autofocus @endif>
      <label for="st" class="visually-hidden">Type de contenu</label>
      <select id="st" name="type" class="form-select"><option value="">Tous les types</option>@foreach (['article' => 'Articles', 'interview' => 'Interviews', 'portrait' => 'Portraits', 'projet' => 'Projets documentés', 'dossier' => 'Dossiers', 'factcheck' => 'Fact-checks'] as $k => $l)<option value="{{ $k }}" @selected($type === $k)>{{ $l }}</option>@endforeach</select>
      <button class="btn btn-ink" type="submit">Rechercher</button>
    </form>
  </header>
  @if ($q)
    @php($n = $articles->count() + $pubs->count())
    <p class="count-line">{{ $n }} résultat{{ $n > 1 ? 's' : '' }} pour « {{ $q }} »</p>
    @if ($articles->count())<div class="list-rows">@foreach ($articles as $a)@include('partials.card', ['a' => $a, 'variant' => 'row'])@endforeach</div>@endif
    @if ($pubs->count())<div class="shelf mt-4">@foreach ($pubs as $p)<a href="{{ $p->url() }}" class="shelf-item">@include('partials.cover', ['p' => $p])<span class="shelf-t">{{ $p->title }}</span><span class="meta">{{ $p->type }}</span></a>@endforeach</div>@endif
    @if (!$n)<div class="empty"><p>Aucun résultat. Essayez un autre mot, par exemple « santé », « Ghana » ou « open source ».</p></div>@endif
  @else
    <div><p class="muted">Suggestions :</p><div class="theme-cloud">@foreach (['IA', 'Santé', 'Open source', 'Sénégal', 'Interview', 'Recyclage'] as $s)<a class="chip" href="{{ route('recherche', ['q' => $s]) }}">{{ $s }}</a>@endforeach</div></div>
  @endif
</div>
@endsection
