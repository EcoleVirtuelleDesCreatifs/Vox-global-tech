@extends('layouts.admin')
@section('title', 'Abonnés')
@section('content')
@php($etat = request('etat'))
<div class="adm-head"><div><h1>Abonnés à la newsletter</h1><p class="muted mb-0">{{ nf($counts['actifs']) }} abonné(s) confirmé(s). Double opt-in : un e-mail de confirmation est envoyé à chaque inscription (conformité RGPD et loi ivoirienne n° 2013-450).</p></div>
  <a class="btn btn-ink" href="{{ route('admin.subscribers.export') }}">Exporter (CSV)</a></div>
<div class="tabs" role="group" aria-label="Filtrer">
  <a href="{{ route('admin.subscribers') }}" aria-pressed="{{ ! $etat ? 'true' : 'false' }}">Confirmés ({{ nf($counts['actifs']) }})</a>
  <a href="{{ route('admin.subscribers', ['etat' => 'attente']) }}" aria-pressed="{{ $etat === 'attente' ? 'true' : 'false' }}">En attente de confirmation ({{ nf($counts['attente']) }})</a>
  <a href="{{ route('admin.subscribers', ['etat' => 'desinscrits']) }}" aria-pressed="{{ $etat === 'desinscrits' ? 'true' : 'false' }}">Désinscrits ({{ nf($counts['desinscrits']) }})</a>
</div>
<div class="adm-filters two">
  <form method="GET" class="d-flex gap-2">@if ($etat)<input type="hidden" name="etat" value="{{ $etat }}">@endif<label for="sq" class="visually-hidden">Rechercher</label><input id="sq" name="q" class="form-control" type="search" value="{{ request('q') }}" placeholder="Rechercher une adresse…"><button class="btn btn-ghost" type="submit">Rechercher</button></form>
  <form method="POST" action="{{ route('admin.subscribers.store') }}" class="d-flex gap-2">@csrf<label for="se" class="visually-hidden">Ajouter un abonné</label><input id="se" name="email" type="email" class="form-control" placeholder="Ajouter une adresse (déjà consentante)" required><button class="btn btn-ink" type="submit">Ajouter</button></form>
</div>
<div class="panel p-0"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>E-mail</th><th>Inscription</th><th>Confirmation</th><th>Source</th><th>État</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
  <tbody>
  @forelse ($subs as $s)
    <tr><td>{{ $s->email }}</td><td class="nowrap">{{ $s->created_at?->format('d/m/Y') }}</td><td class="nowrap">{{ $s->confirmed_at?->format('d/m/Y') ?? '—' }}</td><td>{{ $s->source }}</td>
      <td>@if ($s->unsubscribed_at)<span class="status draft">Désinscrit le {{ $s->unsubscribed_at->format('d/m/Y') }}</span>@elseif ($s->confirmed_at)<span class="status ok">Confirmé</span>@else<span class="status sched">En attente</span>@endif</td>
      <td><form method="POST" action="{{ route('admin.subscribers.destroy', $s) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Retirer {{ $s->email }} de la liste ? Ses données seront effacées.">Retirer</button></form></td></tr>
  @empty
    <tr><td colspan="6" class="text-center muted py-4">Aucun abonné dans cette liste.</td></tr>
  @endforelse
  </tbody></table></div></div>
{{ $subs->links() }}
@endsection
