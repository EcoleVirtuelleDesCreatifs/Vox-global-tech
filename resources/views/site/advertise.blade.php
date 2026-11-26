@extends('layouts.site', ['page' => 'annoncer'])
@section('content')
<section class="hero-page"><div class="wrap"><span class="eyebrow light">Annonceurs</span><h1>Associez votre marque aux talents qui innovent.</h1>
  <p>Bandeaux, pavés, articles sponsorisés et partenariats : touchez une audience jeune, qualifiée et tournée vers la technologie.</p></div></section>
<div class="wrap">
  <h2>Nos emplacements</h2>
  <div class="slots">@foreach ($slots as $s)<div class="slot-card"><div class="slot-prev sp-{{ $s->code }}"><span>{{ $s->size }}</span></div><h3>{{ $s->name }}</h3><p class="meta mb-0">{{ $s->location }}</p></div>@endforeach</div>
  <h2 class="mt-5">Tarifs de lancement</h2>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Format</th><th>Détail</th><th class="num">Prix HT</th></tr></thead><tbody>
    @foreach ([['Publication sponsorisée','Facebook, Instagram, LinkedIn',50000],['Article sponsorisé','Rédaction + relais réseaux',150000],['Bannière sur le site','Page d’accueil, 1 mois',100000],['Mention newsletter','1 envoi',40000],['Contenu sponsorisé','Format article dans les fils, signalé « Partenaire »',150000],['Partenaire de rubrique','« Rubrique présentée par », 1 mois',300000],['Sponsor de la newsletter','Encadré dans la Newsletter VOX, 1 envoi',40000],['Pack Découverte','1 article, 2 publications, 1 newsletter',240000],['Pack Visibilité','1 mois',690000],['Pack Partenaire officiel','3 mois',2200000]] as $r)
      <tr><td>{{ $r[0] }}</td><td>{{ $r[1] }}</td><td class="num">{{ fcfa($r[2]) }}</td></tr>
    @endforeach
  </tbody></table></div>
  <div class="form-shell mt-5" id="formulaire">
    <div><h2>Demander une proposition</h2><p class="lead-muted">Réponse sous 48 heures ouvrées. Chaque contenu payant est signalé comme partenariat.</p></div>
    <div class="form-card">
      @if (session('ads_sent'))
        <div class="success"><h3>Demande envoyée.</h3><p class="muted">Un e-mail de confirmation vous a été envoyé. Notre équipe partenariats revient vers vous sous 48 h.</p><a class="btn btn-ink" href="{{ route('annoncer') }}#formulaire">Nouvelle demande</a></div>
      @else
      @php($e = $errors->ads)
      <form method="POST" action="{{ route('annoncer.store') }}" novalidate class="d-grid gap-3">
        @csrf @include('partials.hp')
        <div><label class="form-label" for="ad-c">Structure</label><input id="ad-c" name="company" class="form-control {{ $e->has('company') ? 'is-bad' : '' }}" value="{{ old('company') }}" autocomplete="organization">@if ($e->has('company'))<div class="field-err">{{ $e->first('company') }}</div>@endif</div>
        <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="ad-n">Votre nom</label><input id="ad-n" name="name" class="form-control {{ $e->has('name') ? 'is-bad' : '' }}" value="{{ old('name') }}" autocomplete="name">@if ($e->has('name'))<div class="field-err">{{ $e->first('name') }}</div>@endif</div>
          <div class="col-sm-6"><label class="form-label" for="ad-e">E-mail</label><input id="ad-e" name="email" type="email" class="form-control {{ $e->has('email') ? 'is-bad' : '' }}" value="{{ old('email') }}" autocomplete="email">@if ($e->has('email'))<div class="field-err">{{ $e->first('email') }}</div>@endif</div></div>
        <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="ad-f">Format souhaité</label><select id="ad-f" name="format" class="form-select"><option value="">Je ne sais pas encore</option>@foreach ($slots as $s)<option @selected(old('format') === $s->name)>{{ $s->name }}</option>@endforeach<option>Article sponsorisé</option><option>Pack</option></select></div>
          <div class="col-sm-6"><label class="form-label" for="ad-b">Budget indicatif</label><select id="ad-b" name="budget" class="form-select"><option value="">Non défini</option>@foreach (['Moins de 250 000 FCFA', '250 000 à 1 000 000 FCFA', 'Plus de 1 000 000 FCFA'] as $b)<option @selected(old('budget') === $b)>{{ $b }}</option>@endforeach</select></div></div>
        <div><label class="form-label" for="ad-m">Votre projet</label><textarea id="ad-m" name="message" rows="4" class="form-control {{ $e->has('message') ? 'is-bad' : '' }}">{{ old('message') }}</textarea>@if ($e->has('message'))<div class="field-err">{{ $e->first('message') }}</div>@endif</div>
        <button class="btn btn-red justify-self-start" type="submit">Envoyer la demande</button>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection
