@extends('layouts.site', ['page' => 'proposer'])
@section('content')
<section class="hero-page"><div class="wrap"><span class="eyebrow light">Espace participatif</span><h1>Proposer un talent ou une innovation</h1>
  <p>Lecteurs, chercheurs, inventeurs, développeurs et entrepreneurs : soumettez un talent, une invention ou un projet technologique à la rédaction.</p></div></section>
<div class="wrap form-shell" id="formulaire">
  <div><h2>Comment ça marche</h2><ol class="steps-list"><li><strong>Vous soumettez</strong> votre proposition.</li><li><strong>La rédaction l’examine</strong> selon ses critères éditoriaux et vérifie les informations.</li><li><strong>Si elle est retenue</strong>, un journaliste vous contacte pour préparer un portrait, une interview ou un reportage.</li></ol>
    <p class="callout">Une soumission ne garantit pas la publication. La sélection est éditoriale et gratuite.</p></div>
  <div class="form-card">
    @if (session('talent_sent'))
      <div class="success"><h3>Merci, proposition reçue.</h3><p class="muted">Un e-mail de confirmation vous a été envoyé. Elle sera examinée selon nos critères éditoriaux.</p><a class="btn btn-ink" href="{{ route('proposer') }}">Faire une autre proposition</a></div>
    @else
    @php($e = $errors->talent)
    <form method="POST" action="{{ route('proposer.store') }}" novalidate class="d-grid gap-3">
      @csrf @include('partials.hp')
      <div><span class="form-label d-block">Vous proposez</span><div class="chips" role="radiogroup" aria-label="Type de proposition">
        @foreach ($kinds as $k)<label class="chip chip-radio"><input type="radio" name="kind" value="{{ $k }}" @checked(old('kind', 'Un talent') === $k)> {{ $k }}</label>@endforeach</div></div>
      <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="t-n">Votre nom</label><input id="t-n" name="name" class="form-control {{ $e->has('name') ? 'is-bad' : '' }}" value="{{ old('name') }}" autocomplete="name" required>@if ($e->has('name'))<div class="field-err">{{ $e->first('name') }}</div>@endif</div>
        <div class="col-sm-6"><label class="form-label" for="t-e">E-mail</label><input id="t-e" name="email" type="email" class="form-control {{ $e->has('email') ? 'is-bad' : '' }}" value="{{ old('email') }}" autocomplete="email" required>@if ($e->has('email'))<div class="field-err">{{ $e->first('email') }}</div>@endif</div></div>
      <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="t-p">Nom du talent, de l’innovation ou du projet</label><input id="t-p" name="project" class="form-control {{ $e->has('project') ? 'is-bad' : '' }}" value="{{ old('project') }}" required>@if ($e->has('project'))<div class="field-err">{{ $e->first('project') }}</div>@endif</div>
        <div class="col-sm-6"><label class="form-label" for="t-c">Pays</label><input id="t-c" name="country" class="form-control" value="{{ old('country') }}" autocomplete="country-name"></div></div>
      <div><label class="form-label" for="t-t">Rubrique concernée</label><select id="t-t" name="category" class="form-select {{ $e->has('category') ? 'is-bad' : '' }}" required><option value="" disabled @selected(!old('category'))>Choisir</option>@foreach ($rubriques as $r)<option @selected(old('category') === $r->name)>{{ $r->name }}</option>@endforeach</select>@if ($e->has('category'))<div class="field-err">{{ $e->first('category') }}</div>@endif</div>
      <div><label class="form-label" for="t-d">Présentation</label><textarea id="t-d" name="description" rows="4" class="form-control {{ $e->has('description') ? 'is-bad' : '' }}" maxlength="800" data-count="t-d-c" placeholder="Quel problème est résolu ? Pour qui ? Quelles réalisations vérifiables (liens, documents, résultats) ?" required>{{ old('description') }}</textarea>
        <div class="d-flex">@if ($e->has('description'))<div class="field-err">{{ $e->first('description') }}</div>@endif<span class="meta ms-auto"><span id="t-d-c">{{ mb_strlen(old('description', '')) }}</span> / 800</span></div></div>
      <div class="form-check"><input class="form-check-input" type="checkbox" id="t-ok" name="ok" value="1" @checked(old('ok'))><label class="form-check-label" for="t-ok">J’accepte que la rédaction me contacte et vérifie les informations transmises.</label></div>
      @if ($e->has('ok'))<div class="field-err">{{ $e->first('ok') }}</div>@endif
      <button class="btn btn-red justify-self-start" type="submit">Soumettre à la rédaction</button>
    </form>
    @endif
  </div>
</div>
@endsection
