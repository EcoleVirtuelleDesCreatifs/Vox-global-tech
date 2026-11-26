@extends('layouts.admin')
@section('title', 'Publicités')
@section('content')
@php($open = $errors->any())
<div class="adm-head"><div><h1>Publicités</h1><p class="muted mb-0">{{ $slots->count() }} emplacements · {{ $running->count() }} campagne(s) en diffusion · une campagne active par emplacement. L’annonceur et la régie sont prévenus par e-mail 3 jours avant la fin.</p></div>
  <button class="btn btn-red" type="button" data-toggle="new-camp">Nouvelle campagne</button></div>

<div class="panel mb-4" id="new-camp" @if (! $open) hidden @endif>
  <h2>Nouvelle campagne</h2>
  <form class="row g-3" method="POST" action="{{ route('admin.ads.store') }}">@csrf
    <div class="col-md-4"><label class="form-label" for="nc-a">Annonceur</label><input id="nc-a" name="advertiser" class="form-control" value="{{ old('advertiser') }}" required maxlength="120"></div>
    <div class="col-md-4"><label class="form-label" for="nc-s">Emplacement</label><select id="nc-s" name="slot" class="form-select" required>@foreach ($slots as $s)<option value="{{ $s->code }}" @selected(old('slot') === $s->code)>{{ $s->name }} ({{ $s->size }})</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="nc-b">Budget (FCFA)</label><input id="nc-b" name="budget" type="number" min="0" step="1000" class="form-control" value="{{ old('budget', 0) }}"></div>
    <div class="col-md-6"><label class="form-label" for="nc-t">Message</label><input id="nc-t" name="title" class="form-control" value="{{ old('title') }}" required maxlength="160"></div>
    <div class="col-md-2"><label class="form-label" for="nc-c">Bouton</label><input id="nc-c" name="cta" class="form-control" value="{{ old('cta', 'Découvrir') }}" maxlength="40"></div>
    <div class="col-md-2"><label class="form-label" for="nc-d1">Début</label><input id="nc-d1" name="starts_on" type="date" class="form-control" value="{{ old('starts_on', now()->format('Y-m-d')) }}" required></div>
    <div class="col-md-2"><label class="form-label" for="nc-d2">Fin</label><input id="nc-d2" name="ends_on" type="date" class="form-control" value="{{ old('ends_on', now()->addDays(30)->format('Y-m-d')) }}" required></div>
    <div class="col-12"><label class="form-label" for="nc-u">Adresse de destination</label><input id="nc-u" name="url" type="url" class="form-control" value="{{ old('url') }}" placeholder="https://site-de-l-annonceur.com"><p class="note mt-1 mb-0">Les clics passent par un lien de suivi (<code>/pub/…/clic</code>) puis redirigent vers cette adresse, avec les paramètres UTM.</p></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-red" type="submit">Activer la campagne</button><button class="btn btn-ghost" type="button" data-toggle="new-camp">Annuler</button></div>
  </form>
</div>

<div class="slots">
  @foreach ($slots as $s)
    @php($c = $running->get($s->code))
    <div class="slot-card"><div class="slot-prev sp-{{ $s->code }}"><span>{{ $s->size }}</span></div><h3>{{ $s->name }}</h3><p class="meta">{{ $s->location }}</p>
      <p class="mb-0 {{ $c ? 'ok-t' : 'muted' }}">{{ $c ? 'Occupé : '.$c->advertiser.' (jusqu’au '.$c->ends_on?->format('d/m').')' : 'Libre : « Votre publicité ici »' }}</p></div>
  @endforeach
</div>

<div class="panel mt-4"><h2>Chiffre d’affaires par emplacement</h2>
  @php($mx = max(1, $revenue->max('v')))
  <ul class="bars">@foreach ($revenue as $r)<li><span class="bl">{{ $r['s']->name }}</span><span class="bt"><i style="width:{{ $r['v'] / $mx * 100 }}%"></i></span><span class="bv">{{ fcfa($r['v']) }}</span></li>@endforeach</ul>
  <p class="note mb-0">Total engagé : <strong>{{ fcfa($revenue->sum('v')) }}</strong></p></div>

<div class="panel p-0 mt-4"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Annonceur</th><th>Emplacement</th><th>Période</th><th class="num">Budget</th><th class="num">Impressions</th><th class="num">Clics</th><th class="num">CTR</th><th>État</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
  <tbody>
  @forelse ($campaigns as $c)
    @php($live = $running->has($c->slot) && $running->get($c->slot)->id === $c->id)
    <tr class="{{ $c->active ? '' : 'dim' }}">
      <td><strong>{{ $c->advertiser }}</strong><div class="meta">{{ $c->title }}@if ($c->url) · <a href="{{ $c->url }}" target="_blank" rel="noopener">destination</a>@endif</div></td>
      <td>{{ $c->adSlot?->name ?? $c->slot }}</td>
      <td class="nowrap">{{ $c->starts_on?->format('d/m/Y') }} → {{ $c->ends_on?->format('d/m/Y') }}</td>
      <td class="num">{{ nf($c->budget) }}</td><td class="num">{{ nf($c->impressions) }}</td><td class="num">{{ nf($c->clicks) }}</td>
      <td class="num">{{ str_replace('.', ',', number_format($c->ctr(), 2)) }} %</td>
      <td><form method="POST" action="{{ route('admin.ads.toggle', $c) }}" class="inline-form">@csrf<button type="submit" class="status {{ $c->active ? ($live ? 'ok' : 'sched') : 'draft' }}" title="{{ $c->active && ! $live ? 'Active mais hors période de diffusion' : 'Changer l’état' }}">{{ $c->active ? ($live ? 'En diffusion' : 'Active (hors période)') : 'En pause' }}</button></form></td>
      <td><form method="POST" action="{{ route('admin.ads.destroy', $c) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" data-confirm="Supprimer la campagne {{ $c->advertiser }} ?">Supprimer</button></form></td>
    </tr>
  @empty
    <tr><td colspan="9" class="text-center muted py-4">Aucune campagne. Les espaces affichent « Votre publicité ici » avec un lien vers la page Annoncer.</td></tr>
  @endforelse
  </tbody></table></div></div>
@endsection
