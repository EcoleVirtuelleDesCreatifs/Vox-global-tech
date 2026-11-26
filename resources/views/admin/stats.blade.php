@extends('layouts.admin')
@section('title', 'Statistiques')
@section('content')
@php
  use App\Support\Chart; use App\Services\Analytics;
  $delta = fn ($v, $suffix = ' %', $inv = false) => $v === null ? '<em>—</em>' : '<em class="'.((($v >= 0) xor $inv) ? 'up' : 'down').'">'.($v >= 0 ? '+' : '').$v.$suffix.'</em>';
  $dur = fn ($s) => intdiv($s, 60).' min '.str_pad($s % 60, 2, '0', STR_PAD_LEFT);
  $srcLabels = ['social' => 'Réseaux sociaux', 'search' => 'Moteurs de recherche', 'direct' => 'Accès direct', 'newsletter' => 'Newsletter', 'referral' => 'Sites référents', 'ai' => 'Assistants IA (ChatGPT, Perplexity…)'];
  $devLabels = ['mobile' => 'Mobile', 'desktop' => 'Ordinateur', 'tablet' => 'Tablette'];
  $hoursMax = max(1, ...array_values($hours));
@endphp
<div class="adm-head"><div><h1>Statistiques</h1><p class="muted mb-0"><span class="live-dot"></span>{{ $realtime }} visiteur(s) actif(s) · mesure intégrée sans cookie, IP jamais stockée, robots exclus.</p></div>
  <div class="d-flex gap-2 align-items-center flex-wrap"><div class="seg" role="group" aria-label="Période">@foreach ([7, 30, 90, 365] as $x)<a href="{{ route('admin.stats', ['periode' => $x]) }}" class="btn-seg" aria-pressed="{{ $p === $x ? 'true' : 'false' }}">{{ $x === 365 ? '1 an' : $x.' jours' }}</a>@endforeach</div>
  <a class="btn btn-ghost" href="{{ route('admin.stats.export', ['periode' => $p]) }}">{!! svg_icon('dl') !!} Export CSV</a></div></div>
<div class="kpis">
  <div class="kpi"><span>Visites</span><strong>{{ nf($s['visits']) }}</strong>{!! $delta($s['delta_visits']) !!}</div>
  <div class="kpi"><span>Visiteurs uniques</span><strong>{{ nf($s['uniques']) }}</strong>{!! $delta($s['delta_uniques']) !!}</div>
  <div class="kpi"><span>Pages vues</span><strong>{{ nf($s['pages']) }}</strong><em>{{ str_replace('.', ',', $s['pages_per_visit']) }} pages par visite</em></div>
  <div class="kpi"><span>Temps de lecture moyen</span><strong>{{ $dur($s['avg_duration']) }}</strong>{!! $delta($s['delta_duration'], ' s') !!}</div>
  <div class="kpi"><span>Taux de rebond</span><strong>{{ $s['bounce'] }} %</strong>{!! $delta($s['delta_bounce'], ' pt', true) !!}</div>
</div>
<div class="adm-grid">
  <section class="panel span-3"><h2>Visites sur {{ $p === 365 ? 'un an' : $p.' jours' }}</h2><div class="chart-box tall">{!! Chart::line(array_column($daily, 'label'), [['label' => 'Visites', 'values' => array_column($daily, 'v')], ['label' => 'Visiteurs uniques', 'values' => array_column($daily, 'u'), 'class' => 'line2']], 300) !!}</div>
    <div class="vx-legend"><span><i style="background:var(--red)"></i>Visites</span><span><i style="background:var(--ink)"></i>Visiteurs uniques</span></div></section>
  <section class="panel"><h2>Appareils</h2><div class="chart-box">{!! Chart::donut(array_map(fn ($x) => ['k' => $devLabels[$x['k']] ?? $x['k'], 'n' => $x['n']], $devices)) !!}</div></section>
  <section class="panel"><h2>Sources de trafic</h2><div class="chart-box">{!! Chart::donut(array_map(fn ($x) => ['k' => $srcLabels[$x['k']] ?? $x['k'], 'n' => $x['n']], $sources)) !!}</div></section>
  <section class="panel"><h2>Pays des lecteurs</h2><ul class="bars">@php($cm = max(1, ...array_column($countries, 'pct') ?: [1]))@foreach ($countries as $c)<li><span class="bl">{{ Analytics::countryName($c['k'] === 'Inconnu' ? null : $c['k']) }}</span><span class="bt"><i style="width:{{ $c['pct'] / $cm * 100 }}%"></i></span><span class="bv">{{ $c['pct'] }} %</span></li>@endforeach</ul></section>
  <section class="panel"><h2>Heures de lecture</h2><div class="chart-box">{!! Chart::bars(array_map(fn ($h) => $h.'h', array_keys($hours)), array_values($hours), 200, $hoursMax * .8) !!}</div>
    @php($peak = array_search(max($hours), $hours))<p class="note mb-0">Pic de lecture vers {{ $peak }} h : le meilleur moment pour publier et partager.</p></section>
  <section class="panel span-2"><h2>Articles les plus lus</h2>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Titre</th><th>Rubrique</th><th class="num">Lectures</th><th class="num">Lecture moy.</th><th class="num">Défilement</th></tr></thead><tbody>
      @forelse ($top as $t)<tr><td><a href="{{ $t['article']->url() }}" target="_blank">{{ $t['article']->title }}</a></td><td>{{ $t['article']->rubrique?->name }}</td><td class="num">{{ nf($t['n']) }}</td><td class="num">{{ $dur($t['duration']) }}</td><td class="num">{{ $t['scroll'] }} %</td></tr>@empty<tr><td colspan="5" class="muted">Pas encore de données.</td></tr>@endforelse
    </tbody></table></div></section>
  <section class="panel"><h2>Lectures par rubrique</h2><ul class="bars">@php($mx = max(1, ...array_column($byRub, 'n') ?: [1]))@foreach ($byRub as $b)<li><span class="bl">{{ $b['k'] }}</span><span class="bt"><i style="width:{{ $b['n'] / $mx * 100 }}%"></i></span><span class="bv">{{ nf($b['n']) }}</span></li>@endforeach</ul></section>
  <section class="panel"><h2>Recherches sur le site</h2><ul class="bars">@php($sm = max(1, ...array_column($searches, 'n') ?: [1]))@forelse ($searches as $q)<li><span class="bl">{{ $q['k'] }} @if ($q['zero'])<span class="pill-k ai" title="Aucun résultat : sujet à couvrir !">0 résultat</span>@endif</span><span class="bt"><i style="width:{{ $q['n'] / $sm * 100 }}%"></i></span><span class="bv">{{ $q['n'] }}</span></li>@empty<li class="muted">Aucune recherche.</li>@endforelse</ul>
    <p class="note mb-0">Les recherches sans résultat sont des idées de sujets.</p></section>
  <section class="panel"><h2>Sites référents</h2><ul class="bars">@php($rm = max(1, ...array_column($referrers, 'n') ?: [1]))@forelse ($referrers as $q)<li><span class="bl">{{ $q['k'] }}</span><span class="bt"><i style="width:{{ $q['n'] / $rm * 100 }}%"></i></span><span class="bv">{{ nf($q['n']) }}</span></li>@empty<li class="muted">Aucun référent.</li>@endforelse</ul></section>
  <section class="panel span-3" id="geo"><h2>{!! svg_icon('ai') !!} GEO · Robots d’indexation et moteurs de réponse IA</h2>
    <p class="muted">Passages des robots sur le site : moteurs de recherche classiques (SEO) et assistants IA qui lisent et citent vos articles (GEO). Ces visites sont exclues de l’audience.</p>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Robot</th><th>Type</th><th class="num">Passages</th><th>Dernier passage</th></tr></thead><tbody>
      @forelse ($bots as $b)<tr><td><strong>{{ $b['bot'] }}</strong></td><td><span class="pill-k {{ $b['kind'] === 'ai' ? 'ai' : '' }}">{{ ['ai' => 'IA / LLM', 'search' => 'Moteur', 'social' => 'Réseau social', 'seo' => 'Outil SEO', 'other' => 'Autre'][$b['kind']] ?? $b['kind'] }}</span></td><td class="num">{{ nf($b['n']) }}</td><td class="nowrap">{{ \Illuminate\Support\Carbon::parse($b['last'])->diffForHumans() }}</td></tr>@empty<tr><td colspan="4" class="muted">Aucun passage de robot pour l’instant.</td></tr>@endforelse
    </tbody></table></div></section>
</div>
@endsection
