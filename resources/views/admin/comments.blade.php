@extends('layouts.admin')
@section('title', 'Commentaires')
@section('content')
<div class="adm-head"><div><h1>Commentaires</h1><p class="muted mb-0">Modérez les réactions des lecteurs avant publication. Le lecteur est prévenu par e-mail de la publication et des réponses.</p></div></div>
<div class="tabs" role="group" aria-label="Filtrer">
  @foreach ([['en attente', 'En attente'], ['approuvé', 'Approuvés'], ['spam', 'Indésirables'], ['refusé', 'Refusés'], ['tous', 'Tous']] as [$k, $l])
    <a href="{{ route('admin.comments', ['statut' => $k]) }}" aria-pressed="{{ $f === $k ? 'true' : 'false' }}">{{ $l }} ({{ $k === 'tous' ? $total : ($counts[$k] ?? 0) }})</a>
  @endforeach
</div>
<div class="inbox">
  @forelse ($comments as $c)
  <article class="panel msg {{ $c->status === 'en attente' ? 'unread' : '' }}">
    <div class="msg-head"><div class="d-flex gap-2 align-items-start"><span class="av sm" aria-hidden="true">{{ initials($c->name) }}</span><div><h2>{{ $c->name }} <span class="meta fw-normal">{{ $c->email }}</span></h2><p class="meta mb-0">Sur <a href="{{ $c->article?->url() }}#commentaires" target="_blank">{{ $c->article?->title }}</a> · {{ $c->created_at?->format('d/m/Y H:i') }}@if ($c->parent_id) · réponse @endif</p></div></div>
      <span class="status {{ $c->status === 'approuvé' ? 'ok' : ($c->status === 'en attente' ? 'sched' : 'draft') }}">{{ $c->status }}</span></div>
    <p>{{ $c->text }}</p>
    <div class="d-flex flex-wrap gap-1">
      @foreach (['approuvé' => 'Approuver', 'spam' => 'Indésirable', 'refusé' => 'Refuser'] as $s => $l)
        @if ($c->status !== $s)<form method="POST" action="{{ route('admin.comments.moderate', $c) }}" class="inline-form">@csrf<input type="hidden" name="status" value="{{ $s }}"><button class="mini-btn" type="submit">{{ $l }}</button></form>@endif
      @endforeach
      <button class="mini-btn" type="button" data-toggle="rp-{{ $c->id }}">Répondre</button>
      <form method="POST" action="{{ route('admin.comments.destroy', $c) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer ce commentaire ?">Supprimer</button></form>
    </div>
    <form id="rp-{{ $c->id }}" method="POST" action="{{ route('admin.comments.reply', $c) }}" class="d-flex gap-2 mt-3" hidden>@csrf<label for="rpi-{{ $c->id }}" class="visually-hidden">Votre réponse</label><input id="rpi-{{ $c->id }}" name="text" class="form-control" placeholder="Réponse de la rédaction…" required><button class="btn btn-ink" type="submit">Publier</button></form>
  </article>
  @empty
  <div class="empty"><p>Aucun commentaire dans cette liste.</p></div>
  @endforelse
</div>
{{ $comments->links() }}
@endsection
