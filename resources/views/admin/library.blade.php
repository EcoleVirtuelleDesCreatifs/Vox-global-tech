@extends('layouts.admin')
@section('title', 'Bibliothèque')
@section('content')
@php($types = \App\Models\Publication::TYPES)
@php($colors = ['ink' => 'Noir', 'red' => 'Rouge', 'stone' => 'Pierre'])
<div class="adm-head"><div><h1>Bibliothèque</h1><p class="muted mb-0">{{ $pubs->count() }} publication(s) · {{ nf($pubs->sum('downloads')) }} téléchargement(s). Magazines, livres, e-books, rapports et documentations ; les fichiers gratuits se téléchargent par un lien signé temporaire (10 min) ; les publications payantes génèrent une commande et un e-mail de confirmation.</p></div>
  <button class="btn btn-red" type="button" data-toggle="new-pub">Nouvelle publication</button></div>

<div class="panel mb-4" id="new-pub" @if (! $errors->any()) hidden @endif><h2>Nouvelle publication</h2>
  <form class="row g-3" method="POST" action="{{ route('admin.library.store') }}" enctype="multipart/form-data">@csrf
    <div class="col-md-6"><label class="form-label" for="np-t">Titre</label><input id="np-t" name="title" class="form-control" value="{{ old('title') }}" required maxlength="150"></div>
    <div class="col-md-6"><label class="form-label" for="np-s">Sous-titre</label><input id="np-s" name="subtitle" class="form-control" value="{{ old('subtitle') }}" maxlength="200"></div>
    <div class="col-md-3"><label class="form-label" for="np-ty">Type</label><select id="np-ty" name="type" class="form-select">@foreach ($types as $t)<option @selected(old('type') === $t)>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-2"><label class="form-label" for="np-p">Pages</label><input id="np-p" name="pages" type="number" min="0" class="form-control" value="{{ old('pages', 0) }}"></div>
    <div class="col-md-2"><label class="form-label" for="np-pr">Prix (FCFA)</label><input id="np-pr" name="price" type="number" min="0" step="500" class="form-control" value="{{ old('price', 0) }}"><span class="note">0 = gratuit</span></div>
    <div class="col-md-2"><label class="form-label" for="np-c">Couverture</label><select id="np-c" name="color" class="form-select">@foreach ($colors as $k => $l)<option value="{{ $k }}" @selected(old('color') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label" for="np-d">Parution</label><input id="np-d" name="published_on" type="date" class="form-control" value="{{ old('published_on', now()->format('Y-m-d')) }}"></div>
    <div class="col-12"><label class="form-label" for="np-de">Présentation</label><textarea id="np-de" name="description" rows="3" class="form-control" maxlength="1200">{{ old('description') }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="np-toc">Sommaire (une ligne par chapitre)</label><textarea id="np-toc" name="toc" rows="4" class="form-control">{{ old('toc') }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="np-f">Fichier (PDF, EPUB ou ZIP, 50 Mo max.)</label><input id="np-f" name="file" type="file" accept=".pdf,.epub,.zip" class="form-control"><p class="note mt-1">Le fichier est stocké hors du dossier public : il n’est accessible que par lien signé.</p></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-red" type="submit">Ajouter</button><button class="btn btn-ghost" type="button" data-toggle="new-pub">Annuler</button></div>
  </form></div>

<div class="panel p-0"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Couverture</th><th>Titre</th><th>Type</th><th>Parution</th><th class="num">Pages</th><th>Fichier</th><th class="num">Téléch.</th><th class="num">Prix (FCFA)</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
  <tbody>
  @forelse ($pubs as $p)
    <tr>
      <td style="width:72px">@include('partials.cover', ['p' => $p])</td>
      <td><a href="{{ $p->url() }}" target="_blank"><strong>{{ $p->title }}</strong></a><div class="meta">{{ $p->subtitle }}</div></td>
      <td>{{ $p->type }}</td><td class="nowrap">{{ $p->published_on?->format('d/m/Y') }}</td><td class="num">{{ $p->pages }}</td>
      <td>@if ($p->file_path)<span class="status ok">{{ $p->format }}</span>@else<span class="status draft" title="Aucun fichier : le lecteur recevra un e-mail de confirmation sans lien">Aucun</span>@endif</td>
      <td class="num">{{ nf($p->downloads) }}</td>
      <td class="num"><form method="POST" action="{{ route('admin.library.update', $p->id) }}" class="d-flex gap-1 justify-content-end">@csrf @method('PUT')<input type="hidden" name="price_only" value="1"><label class="visually-hidden" for="pp-{{ $p->id }}">Prix</label><input id="pp-{{ $p->id }}" name="price" type="number" min="0" step="500" class="form-control form-control-sm num-in" value="{{ $p->price }}"><button class="mini-btn" type="submit">OK</button></form></td>
      <td class="nowrap"><button class="mini-btn" type="button" data-toggle="ed-{{ $p->id }}">Modifier</button>
        <form method="POST" action="{{ route('admin.library.destroy', $p->id) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer « {{ $p->title }} » et son fichier ?">Supprimer</button></form></td>
    </tr>
    <tr id="ed-{{ $p->id }}" hidden><td colspan="9">
      <form class="row g-2" method="POST" action="{{ route('admin.library.update', $p->id) }}" enctype="multipart/form-data">@csrf @method('PUT')
        <div class="col-md-5"><label class="form-label">Titre</label><input name="title" class="form-control form-control-sm" value="{{ $p->title }}" required></div>
        <div class="col-md-5"><label class="form-label">Sous-titre</label><input name="subtitle" class="form-control form-control-sm" value="{{ $p->subtitle }}"></div>
        <div class="col-md-2"><label class="form-label">Type</label><select name="type" class="form-select form-select-sm">@foreach ($types as $t)<option @selected($p->type === $t)>{{ $t }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Pages</label><input name="pages" type="number" min="0" class="form-control form-control-sm" value="{{ $p->pages }}"></div>
        <div class="col-md-2"><label class="form-label">Prix</label><input name="price" type="number" min="0" class="form-control form-control-sm" value="{{ $p->price }}"></div>
        <div class="col-md-2"><label class="form-label">Couverture</label><select name="color" class="form-select form-select-sm">@foreach ($colors as $k => $l)<option value="{{ $k }}" @selected($p->color === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Parution</label><input name="published_on" type="date" class="form-control form-control-sm" value="{{ $p->published_on?->format('Y-m-d') }}"></div>
        <div class="col-md-4"><label class="form-label">Remplacer le fichier</label><input name="file" type="file" accept=".pdf,.epub,.zip" class="form-control form-control-sm"></div>
        <div class="col-md-6"><label class="form-label">Présentation</label><textarea name="description" rows="3" class="form-control form-control-sm">{{ $p->description }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Sommaire (une ligne par chapitre)</label><textarea name="toc" rows="3" class="form-control form-control-sm">{{ implode("\n", $p->toc ?? []) }}</textarea></div>
        <div class="col-12"><button class="btn btn-ink btn-sm" type="submit">Enregistrer</button></div>
      </form></td></tr>
  @empty
    <tr><td colspan="9" class="text-center muted py-4">La bibliothèque est vide.</td></tr>
  @endforelse
  </tbody></table></div></div>
@endsection
