@extends('layouts.admin')
@section('title', 'Boîte de réception')
@section('content')
<div class="adm-head"><div><h1>Boîte de réception</h1><p class="muted mb-0">Propositions de talents, demandes d’annonceurs et messages envoyés depuis le site. Chaque envoi déclenche un accusé de réception au lecteur et une alerte à la rédaction.</p></div></div>
<div class="tabs" role="group" aria-label="Filtrer">
  <a href="{{ route('admin.inbox') }}" aria-pressed="{{ $tab === 'talents' ? 'true' : 'false' }}">Propositions de talents ({{ $subsCount }})</a>
  <a href="{{ route('admin.inbox', ['onglet' => 'messages']) }}" aria-pressed="{{ $tab === 'messages' ? 'true' : 'false' }}">Annonceurs et contact ({{ $leadsCount }})@if ($unread) · {{ $unread }} non lu(s)@endif</a>
</div>

@if ($tab === 'talents')
<div class="inbox">
  @forelse ($subs as $s)
  <article class="panel msg {{ $s->status === 'Nouvelle' ? 'unread' : '' }}">
    <div class="msg-head"><div><h2>{{ $s->project }} <span class="meta fw-normal">· {{ $s->kind }}</span></h2><p class="meta mb-0">{{ $s->name }} · <a href="mailto:{{ $s->email }}">{{ $s->email }}</a>@if ($s->country) · {{ $s->country }}@endif @if ($s->category) · {{ $s->category }}@endif · {{ $s->created_at?->format('d/m/Y H:i') }}</p></div>
      <form method="POST" action="{{ route('admin.inbox.status', $s) }}" class="d-flex gap-2 align-items-center" data-autosubmit>@csrf
        <label class="visually-hidden" for="ss-{{ $s->id }}">Statut</label>
        <select id="ss-{{ $s->id }}" name="status" class="form-select form-select-sm w-auto">@foreach (\App\Models\Submission::STATUSES as $st)<option @selected($s->status === $st)>{{ $st }}</option>@endforeach</select>
        <input type="hidden" name="notify" value="1"><noscript><button class="mini-btn" type="submit">OK</button></noscript>
      </form></div>
    <p class="mb-2" style="white-space:pre-line">{{ $s->description }}</p>
    <div class="d-flex flex-wrap gap-1">
      <a class="mini-btn" href="mailto:{{ $s->email }}?subject={{ rawurlencode('Votre proposition : '.$s->project) }}">Répondre par e-mail</a>
      @can('draft')<a class="mini-btn" href="{{ route('admin.articles.create', ['titre' => $s->project]) }}">Créer un article</a>@endcan
      <form method="POST" action="{{ route('admin.inbox.submission.destroy', $s) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer la proposition « {{ $s->project }} » ?">Supprimer</button></form>
    </div>
  </article>
  @empty
  <div class="empty"><p>Aucune proposition pour l’instant. Le formulaire « Proposer un talent » du site arrive ici.</p></div>
  @endforelse
</div>
{{ $subs->links() }}
<p class="note mt-2">Passer une proposition en « En étude », « Retenue » ou « Refusée » envoie automatiquement un e-mail au proposant.</p>
@else
<div class="inbox">
  @forelse ($leads as $l)
  <article class="panel msg {{ $l->read_at ? '' : 'unread' }}">
    <div class="msg-head"><div><h2>{{ $l->kind }} · {{ $l->subject ?: ($l->format ?: 'Message') }}</h2><p class="meta mb-0">{{ $l->name }}@if ($l->company) ({{ $l->company }})@endif · <a href="mailto:{{ $l->email }}">{{ $l->email }}</a> · {{ $l->created_at?->format('d/m/Y H:i') }}@if ($l->format) · {{ $l->format }}@endif @if ($l->budget) · {{ $l->budget }}@endif</p></div>
      <span class="status {{ $l->read_at ? 'draft' : 'ok' }}">{{ $l->read_at ? 'Lu' : 'Nouveau' }}</span></div>
    <p class="mb-2" style="white-space:pre-line">{{ $l->message }}</p>
    <div class="d-flex flex-wrap gap-1">
      <a class="mini-btn" href="mailto:{{ $l->email }}?subject={{ rawurlencode('Re : '.($l->subject ?: $l->kind)) }}">Répondre</a>
      <form method="POST" action="{{ route('admin.inbox.read', $l) }}" class="inline-form">@csrf<button class="mini-btn" type="submit">{{ $l->read_at ? 'Marquer non lu' : 'Marquer comme lu' }}</button></form>
      <form method="POST" action="{{ route('admin.inbox.lead.destroy', $l) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer ce message ?">Supprimer</button></form>
    </div>
  </article>
  @empty
  <div class="empty"><p>Aucun message pour l’instant. Les formulaires « Annoncer » et « Contact » du site arrivent ici.</p></div>
  @endforelse
</div>
{{ $leads->links() }}
@endif
@endsection
