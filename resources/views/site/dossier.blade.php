@extends('layouts.site', ['page' => 'dossier'])
@section('content')
<header class="rh"><span class="rh-ring" aria-hidden="true"></span><div class="wrap rh-in"><nav class="crumbs light" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>/</span><a href="{{ route('dossiers') }}">Dossiers spéciaux</a></nav>
  <span class="pillar-tag light">Dossier spécial · {{ $articles->count() }} articles</span><h1>{{ $dossier->title }}</h1><p class="rh-promise">{{ $dossier->chapo }}</p></div></header>
<div class="wrap with-side pt-5">
  <section><ol class="dos-list">@foreach ($articles as $i => $a)<li class="rv"><span class="dl-n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>@include('partials.card', ['a' => $a, 'variant' => 'row'])</li>@endforeach</ol>
    @if ($articles->isEmpty())<div class="empty"><p>Les articles de ce dossier arrivent bientôt.</p></div>@endif</section>
  @include('partials.sidebar')
</div>
@endsection
