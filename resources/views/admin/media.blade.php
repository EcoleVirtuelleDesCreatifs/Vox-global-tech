@extends('layouts.admin')
@section('title', 'Médiathèque')
@section('content')
<div class="adm-head"><div><h1>Médiathèque</h1><p class="muted mb-0">{{ $count }} fichiers · {{ ko($total) }} · les images sont redimensionnées (2000 px) et converties en WebP automatiquement.</p></div>
  @can('draft')<form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" data-upload>@csrf<label class="btn btn-red mb-0" for="up">Ajouter des fichiers</label><input id="up" name="files[]" class="visually-hidden" type="file" multiple accept="image/*,application/pdf"></form>@endcan</div>
<form class="adm-filters two" method="GET" data-autosubmit>
  <label for="mq" class="visually-hidden">Rechercher un fichier</label><input id="mq" name="q" class="form-control" type="search" value="{{ request('q') }}" placeholder="Nom ou texte alternatif…">
  <label for="mt" class="visually-hidden">Type</label><select id="mt" name="type" class="form-select"><option value="">Tous les types</option>@foreach (['Image', 'Document'] as $t)<option @selected(request('type') === $t)>{{ $t }}</option>@endforeach</select>
</form>
<div class="media-layout">
  <div class="media-grid">
    @forelse ($media as $m)
      <a href="{{ route('admin.media', array_merge(request()->query(), ['m' => $m->id])) }}" class="media-item {{ $sel?->id === $m->id ? 'on' : '' }}" aria-label="{{ $m->name }}">
        @if ($m->isImage() && $m->url())<img src="{{ $m->url() }}" alt="" loading="lazy">@else<span class="media-ph t{{ $m->tone }} {{ $m->type === 'Document' ? 'doc' : '' }}">{!! svg_icon($m->type === 'Document' ? 'doc' : 'img') !!}</span>@endif
        <span class="media-n">{{ $m->name }}</span></a>
    @empty
      <div class="empty" style="grid-column:1/-1"><p>Aucun fichier.</p></div>
    @endforelse
  </div>
  @if ($sel)
  <aside class="panel media-side">
    <h2>Détails du fichier</h2>
    <div class="media-prev">@if ($sel->isImage() && $sel->url())<img src="{{ $sel->url() }}" alt="{{ $sel->alt }}">@else<span class="media-ph t{{ $sel->tone }} {{ $sel->type === 'Document' ? 'doc' : '' }}">{!! svg_icon($sel->type === 'Document' ? 'doc' : 'img') !!}</span>@endif</div>
    <dl class="spec one"><div><dt>Nom</dt><dd>{{ $sel->name }}</dd></div><div><dt>Type</dt><dd>{{ $sel->type }}</dd></div><div><dt>Poids</dt><dd>{{ ko($sel->size) }}</dd></div>@if ($sel->width)<div><dt>Dimensions</dt><dd>{{ $sel->width }} × {{ $sel->height }} px</dd></div>@endif<div><dt>Ajouté le</dt><dd>{{ $sel->created_at?->format('d/m/Y') }}</dd></div>@if ($usedBy->count())<div><dt>Utilisé par</dt><dd>@foreach ($usedBy as $a)<a href="{{ route('admin.articles.edit', $a->id) }}">{{ $a->title }}</a><br>@endforeach</dd></div>@endif</dl>
    <form method="POST" action="{{ route('admin.media.update', $sel) }}">@csrf @method('PUT')
      <label class="form-label" for="m-alt">Texte alternatif (accessibilité et SEO images)</label><input id="m-alt" name="alt" class="form-control" value="{{ $sel->alt }}" placeholder="Décrivez l’image en une phrase">
      <button class="btn btn-ink mt-2" type="submit">Enregistrer</button></form>
    @if (!$sel->path)<p class="note mt-2">Fichier de démonstration (sans contenu réel).</p>@endif
    <div class="d-flex gap-2 mt-3">@if ($sel->url())<button class="btn btn-ghost" type="button" data-copy="{{ $sel->url() }}">Copier l’adresse</button>@endif
      @can('edit-others')<form method="POST" action="{{ route('admin.media.destroy', $sel) }}">@csrf @method('DELETE')<button class="btn btn-ghost danger-t" type="submit" data-confirm="Supprimer ce fichier ?">Supprimer</button></form>@endcan</div>
  </aside>
  @endif
</div>
{{ $media->links() }}
@endsection
