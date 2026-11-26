@extends('layouts.site', ['page' => 'dossiers'])
@section('content')
<header class="rh"><div class="wrap rh-in"><nav class="crumbs light" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>/</span><span>Dossiers spéciaux</span></nav>
  <span class="pillar-tag light">Enquêtes et grandes thématiques</span><h1>Dossiers spéciaux</h1><p class="rh-promise">Pour comprendre en profondeur les sujets qui transforment la technologie et la société.</p></div></header>
<div class="wrap block pt-5"><div class="dossiers full">
  @foreach ($dossiers as $d)
    <a href="{{ route('dossier', $d) }}" class="dos t{{ $d->tone }} rv"><span class="dos-ring" aria-hidden="true"></span><span class="dos-n">{{ $d->articles->count() }} articles</span><span class="dos-t">{{ $d->title }}</span><span class="dos-c">{{ $d->chapo }}</span></a>
  @endforeach
</div></div>
@endsection
