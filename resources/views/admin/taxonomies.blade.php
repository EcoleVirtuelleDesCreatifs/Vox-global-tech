@extends('layouts.admin')
@section('title', 'Rubriques et dossiers')
@section('content')
<div class="adm-head"><div><h1>Rubriques, thématiques et dossiers</h1><p class="muted mb-0">Les rubriques forment le menu du site ; les thématiques classent les contenus par domaine ; les dossiers regroupent des articles.</p></div></div>
<div class="adm-grid">
  <section class="panel span-2"><h2>Rubriques</h2>
    @foreach ($rubriques as $r)
      <details class="mb-2" @if ($loop->first) open @endif><summary>{{ $r->name }} <span class="meta">· {{ $r->articles_count }} article(s) · {{ count($r->subs ?? []) }} sous-rubrique(s)</span></summary>
        <form method="POST" action="{{ route('admin.rubriques.update', $r->id) }}" class="row g-2 mt-1">@csrf @method('PUT')
          <div class="col-md-5"><label class="form-label">Nom</label><input name="name" class="form-control form-control-sm" value="{{ $r->name }}" required></div>
          <div class="col-md-5"><label class="form-label">Pilier</label><input name="pillar" class="form-control form-control-sm" value="{{ $r->pillar }}"></div>
          <div class="col-md-2"><label class="form-label">Ordre</label><input name="position" type="number" min="0" class="form-control form-control-sm" value="{{ $r->position }}"></div>
          <div class="col-12"><label class="form-label">Promesse</label><input name="promise" class="form-control form-control-sm" value="{{ $r->promise }}"></div>
          <div class="col-12"><label class="form-label">Objectif (description SEO)</label><textarea name="description" rows="2" class="form-control form-control-sm">{{ $r->description }}</textarea></div>
          <div class="col-12"><label class="form-label">Sous-rubriques (séparées par des virgules)</label><input name="subs" class="form-control form-control-sm" value="{{ implode(', ', $r->subs ?? []) }}"></div>
          <div class="col-12 d-flex gap-2"><button class="btn btn-ink btn-sm" type="submit">Enregistrer</button><a class="btn btn-ghost btn-sm" href="{{ route('rubrique', $r) }}" target="_blank">Voir</a>
            <button class="mini-btn danger" type="submit" form="dr-{{ $r->id }}" @disabled($r->articles_count) title="{{ $r->articles_count ? 'Videz la rubrique avant de la supprimer' : '' }}" data-confirm="Supprimer la rubrique {{ $r->name }} ?">Supprimer</button></div>
        </form>
        <form id="dr-{{ $r->id }}" method="POST" action="{{ route('admin.rubriques.destroy', $r->id) }}" hidden>@csrf @method('DELETE')</form>
      </details>
    @endforeach
    <form class="d-flex gap-2 mt-3" method="POST" action="{{ route('admin.rubriques.store') }}">@csrf<label for="nr" class="visually-hidden">Nouvelle rubrique</label><input id="nr" name="name" class="form-control" placeholder="Nouvelle rubrique" required><button class="btn btn-ink" type="submit">Ajouter</button></form>
  </section>
  <section class="panel"><h2>Thématiques</h2><ul class="plain">@foreach ($themes as $t)<li><span><a href="{{ route('theme', $t) }}" target="_blank">{{ $t->name }}</a></span><span class="d-flex gap-2 align-items-center"><span class="num">{{ $t->articles_count }}</span><form method="POST" action="{{ route('admin.themes.destroy', $t->id) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer la thématique {{ $t->name }} ? Les articles seront conservés.">×</button></form></span></li>@endforeach</ul>
    <form class="d-flex gap-2 mt-3" method="POST" action="{{ route('admin.themes.store') }}">@csrf<label for="nt" class="visually-hidden">Nouvelle thématique</label><input id="nt" name="name" class="form-control" placeholder="Nouvelle thématique" required><button class="btn btn-ink" type="submit">Ajouter</button></form></section>
  <section class="panel span-2"><h2>Dossiers spéciaux</h2>
    @foreach ($dossiers as $d)
      <details class="mb-2"><summary>{{ $d->title }} <span class="meta">· {{ $d->articles_count }} article(s)</span></summary>
        <form method="POST" action="{{ route('admin.dossiers.update', $d->id) }}" class="d-grid gap-2 mt-2">@csrf @method('PUT')
          <input name="title" class="form-control form-control-sm" value="{{ $d->title }}" required aria-label="Titre">
          <textarea name="chapo" rows="2" class="form-control form-control-sm" aria-label="Chapeau">{{ $d->chapo }}</textarea>
          <label class="form-label mb-0">Articles du dossier (Ctrl/Cmd + clic pour sélectionner plusieurs)</label>
          <select name="articles[]" multiple size="8" class="form-select form-select-sm">@foreach ($articles as $a)<option value="{{ $a->id }}" @selected($d->articles->contains('id', $a->id))>{{ $a->title }}</option>@endforeach</select>
          <div class="d-flex gap-2"><select name="tone" class="form-select form-select-sm w-auto" aria-label="Couleur">@foreach ([0, 1, 2, 3] as $t)<option value="{{ $t }}" @selected($d->tone == $t)>Ton {{ $t + 1 }}</option>@endforeach</select><button class="btn btn-ink btn-sm" type="submit">Enregistrer</button>
            <button class="mini-btn danger" type="submit" form="dd-{{ $d->id }}" data-confirm="Supprimer le dossier {{ $d->title }} ?">Supprimer</button></div>
        </form>
        <form id="dd-{{ $d->id }}" method="POST" action="{{ route('admin.dossiers.destroy', $d->id) }}" hidden>@csrf @method('DELETE')</form>
      </details>
    @endforeach
    <form class="row g-2 mt-3" method="POST" action="{{ route('admin.dossiers.store') }}">@csrf<div class="col-md-5"><input name="title" class="form-control" placeholder="Titre du nouveau dossier" required aria-label="Titre"></div><div class="col-md-5"><input name="chapo" class="form-control" placeholder="Chapeau" aria-label="Chapeau"></div><div class="col-md-2"><button class="btn btn-ink w-100" type="submit">Créer</button></div></form>
  </section>
  <section class="panel"><h2>Opportunités (Campus & Carrières)</h2>
    <ul class="plain">@foreach ($opportunities as $o)<li><span><span class="opp-type">{{ $o->type }}</span> {{ $o->title }}<span class="meta d-block">{{ $o->organization }} · {{ $o->location }} · {{ $o->deadline?->format('d/m/Y') }}</span></span><form method="POST" action="{{ route('admin.opps.destroy', $o) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer cette opportunité ?">×</button></form></li>@endforeach</ul>
    <form class="d-grid gap-2 mt-3" method="POST" action="{{ route('admin.opps.store') }}">@csrf
      <select name="type" class="form-select" aria-label="Type">@foreach (\App\Models\Opportunity::TYPES as $t)<option>{{ $t }}</option>@endforeach</select>
      <input name="title" class="form-control" placeholder="Intitulé" required aria-label="Intitulé"><input name="organization" class="form-control" placeholder="Organisation" aria-label="Organisation">
      <div class="d-flex gap-2"><input name="location" class="form-control" placeholder="Lieu" aria-label="Lieu"><input name="deadline" type="date" class="form-control" aria-label="Date limite"></div>
      <input name="url" type="url" class="form-control" placeholder="https://lien-de-candidature" aria-label="Lien"><button class="btn btn-ink" type="submit">Ajouter l’opportunité</button></form></section>
</div>
@endsection
