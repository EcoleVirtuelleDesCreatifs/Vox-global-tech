@extends('layouts.site', ['page' => 'auteur'])
@section('content')
<div class="wrap">
  <header class="page-head author-head">
    <span class="av xl" aria-hidden="true">{{ $author->initials() }}</span>
    <div><nav class="crumbs" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>›</span><a href="{{ route('apropos') }}#ab-equipe">La rédaction</a></nav>
      <h1>{{ $author->name }}</h1><p class="eyebrow mb-2">{{ $author->role }}</p><p class="lead-muted">{{ $author->bio }}</p></div>
  </header>
  <div class="with-side"><section><p class="count-line">{{ $articles->total() }} article{{ $articles->total() > 1 ? 's' : '' }}</p>
    <div class="list-rows">@foreach ($articles as $a)@include('partials.card', ['a' => $a, 'variant' => 'row'])@endforeach</div>{{ $articles->links() }}</section>
    @include('partials.sidebar')</div>
</div>
@endsection
