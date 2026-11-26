@extends('layouts.admin')
@section('title', 'Diagnostic & SEO')
@section('content')
<div class="adm-head"><div><h1>Diagnostic & SEO / GEO</h1><p class="muted mb-0">Analyse automatique du serveur, de la sécurité, des e-mails, du référencement et de la visibilité dans les moteurs IA.</p></div>
  <div class="d-flex gap-2 flex-wrap"><form method="POST" action="{{ route('admin.diagnostic.mail') }}">@csrf<button class="btn btn-ghost" type="submit">{!! svg_icon('mail') !!} Envoyer un e-mail de test</button></form>
  <form method="POST" action="{{ route('admin.diagnostic.cache') }}">@csrf<button class="btn btn-ink" type="submit">Vider les caches</button></form></div></div>
<div class="adm-grid">
  <section class="panel d-flex gap-4 align-items-center span-3">
    <div class="score-ring" style="--p: {{ $score }}"><span>{{ $score }}</span></div>
    <div><h2 class="mb-1">Score de santé : {{ $score }} / 100</h2><p class="muted mb-2">{{ $score >= 85 ? 'Excellent : le site est prêt pour la production.' : ($score >= 65 ? 'Bon : quelques réglages recommandés ci-dessous.' : 'À corriger avant la mise en ligne.') }}</p>
      <p class="note mb-0">Liens utiles : <a href="{{ route('sitemap') }}" target="_blank">sitemap.xml</a> · <a href="{{ route('robots') }}" target="_blank">robots.txt</a> · <a href="{{ route('llms') }}" target="_blank">llms.txt</a> · <a href="{{ route('feed') }}" target="_blank">RSS</a> · <a href="https://search.google.com/test/rich-results?url={{ urlencode(route('home')) }}" target="_blank" rel="noopener">Test des résultats enrichis</a> · <a href="https://pagespeed.web.dev/analysis?url={{ urlencode(route('home')) }}" target="_blank" rel="noopener">PageSpeed</a></p></div>
  </section>
  @foreach ($groups as $title => $checks)
  <section class="panel {{ $loop->index < 2 ? '' : '' }} span-{{ in_array($title, ['SEO technique', 'GEO · moteurs de réponse IA'], true) ? '3' : '3' }}"><h2>{{ $title }}</h2>
    <ul class="diag">@foreach ($checks as $c)<li class="{{ $c['status'] }}"><span class="ic">{{ ['ok' => '✓', 'warn' => '!', 'fail' => '×'][$c['status']] }}</span><div><strong>{{ $c['label'] }}</strong><div class="d">{{ $c['detail'] }}</div></div><span class="status {{ ['ok' => 'ok', 'warn' => 'sched', 'fail' => 'draft'][$c['status']] }}">{{ ['ok' => 'OK', 'warn' => 'À améliorer', 'fail' => 'À corriger'][$c['status']] }}</span></li>@endforeach</ul>
  </section>
  @endforeach
</div>
<p class="note">Le même diagnostic est disponible en ligne de commande : <code>php artisan vox:diagnostic</code>.</p>
@endsection
