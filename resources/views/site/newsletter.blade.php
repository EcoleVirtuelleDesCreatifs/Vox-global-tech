@extends('layouts.site', ['page' => 'newsletter'])
@section('content')
<header class="rh"><div class="wrap rh-in"><span class="pillar-tag light">Hebdomadaire · gratuite</span><h1>Newsletter VOX</h1><p class="rh-promise">Chaque semaine, une sélection des principales actualités, découvertes, innovations et publications du média.</p></div></header>
<div class="wrap form-shell pt-5">
  <div><h2>Ce que vous recevez</h2>
    <ul class="nl-what"><li><b>Le talent de la semaine</b> : un portrait à découvrir.</li><li><b>Les innovations</b> repérées par la rédaction.</li><li><b>Un décryptage</b> pour comprendre l’actualité tech.</li><li><b>Campus & Carrières</b> : formations et opportunités.</li></ul>
    <div class="form-card mt-4">
      @if (session('nl_page') === 'confirmed')
        <div class="success"><h3>Inscription confirmée.</h3><p class="muted">Bienvenue ! Prochain envoi : lundi matin.</p></div>
      @elseif (session('nl_page') === 'unsubscribed')
        <div class="success"><h3>Désinscription effectuée.</h3><p class="muted">Vous ne recevrez plus la Newsletter VOX. Vous pouvez vous réinscrire à tout moment.</p></div>
      @else
        @include('partials.nl-form', ['source' => 'Page newsletter', 'variant' => 'page', 'class' => 'd-grid gap-3'])
      @endif
    </div>
  </div>
  <div class="mail">
    <div class="mail-top">@include('partials.logo')</div>
    <p class="mail-subj">Newsletter VOX · Aperçu</p><p>Cette semaine : des talents à découvrir, une invention documentée et un décryptage.</p>
    @foreach ($preview as $a)<div class="mail-art"><span class="kicker"><span class="type">{{ $a->rubrique?->name }}</span></span><strong>{{ $a->title }}</strong><span class="meta">{{ $a->chapo }}</span></div>@endforeach
    @include('partials.ad', ['pos' => 'newsletter'])
    <p class="meta mail-foot">{{ setting('name') }} · Se désabonner</p>
  </div>
</div>
@endsection
