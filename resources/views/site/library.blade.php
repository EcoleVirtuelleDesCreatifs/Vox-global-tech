@extends('layouts.site', ['page' => 'bibliotheque'])
@section('content')
<div class="wrap">
  <header class="page-head"><nav class="crumbs" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>›</span><span>Bibliothèque</span></nav>
    <h1>Bibliothèque</h1><p class="lead-muted">Magazines, livres, e-books, rapports et documentations techniques des inventions que nous suivons.</p></header>
  <div class="chips mb-4" role="group" aria-label="Type de publication">
    <a class="chip" href="{{ route('bibliotheque') }}" aria-pressed="{{ $type ? 'false' : 'true' }}">Tout</a>
    @foreach ($types as $t)<a class="chip" href="{{ route('bibliotheque', ['type' => $t]) }}" aria-pressed="{{ $type === $t ? 'true' : 'false' }}">{{ $t }}</a>@endforeach
  </div>
  <div class="shelf big">
    @forelse ($pubs as $p)
      <a href="{{ $p->url() }}" class="shelf-item rv">@include('partials.cover', ['p' => $p])<span class="shelf-t">{{ $p->title }}</span><span class="meta">{{ $p->type }} · {{ $p->pages }} pages · {{ fcfa($p->price) }}</span></a>
    @empty
      <div class="empty"><p>Aucune publication de ce type pour l’instant.</p></div>
    @endforelse
  </div>
</div>
@endsection
