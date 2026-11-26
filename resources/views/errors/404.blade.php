@php(app(\App\Support\Seo::class)->title('Page introuvable')->noindex())
@extends('layouts.site', ['page' => '404'])
@section('content')
<div class="wrap narrow"><header class="page-head"><h1>Page introuvable</h1><p class="lead-muted">Ce contenu n’existe pas ou a été retiré.</p>
  <form class="search-big mb-4" role="search" action="{{ route('recherche') }}" method="GET"><label for="e-q" class="visually-hidden">Rechercher</label><input id="e-q" name="q" class="form-control" type="search" placeholder="Rechercher un talent, une innovation…"><button class="btn btn-ink" type="submit">Rechercher</button></form>
  <a class="btn btn-ink" href="{{ route('home') }}">Retour à l’accueil</a></header></div>
@endsection
