@extends('layouts.admin')
@section('title', 'Tableau de bord')
@section('content')
@php
  use App\Support\Chart;
  $delta = fn ($v, $suffix = ' %') => $v === null ? '<em>Première période</em>' : '<em class="'.($v >= 0 ? 'up' : 'down').'">'.($v >= 0 ? '+' : '').$v.$suffix.' vs période précédente</em>';
  $srcLabels = ['social' => 'Réseaux sociaux', 'search' => 'Moteurs de recherche', 'direct' => 'Accès direct', 'newsletter' => 'Newsletter', 'referral' => 'Sites référents', 'ai' => 'Assistants IA'];
@endphp
<div class="adm-head"><div><h1>Tableau de bord</h1><p class="muted mb-0"><span class="live-dot"></span>{{ $realtime }} visiteur(s) en ce moment · bonjour {{ explode(' ', auth()->user()->name)[0] }} !</p></div>
  @can('draft')<a class="btn btn-red" href="{{ route('admin.articles.create') }}">Nouvel article</a>@endcan</div>
<div class="kpis">
  <div class="kpi"><span>Visites (30 jours)</span><strong>{{ nf($s['visits']) }}</strong>{!! $delta($s['delta_visits']) !!}</div>
  <div class="kpi"><span>Articles publiés</span><strong>{{ $published }}</strong><em>{{ $drafts }} brouillon{{ $drafts > 1 ? 's' : '' }}</em></div>
  <div class="kpi"><span>Abonnés newsletter</span><strong>{{ nf($subscribers) }}</strong><em>Abonnés confirmés</em></div>
  <div class="kpi"><span>Propositions à traiter</span><strong>{{ $pendingSubs }}</strong><em><a href="{{ route('admin.inbox') }}">Ouvrir la boîte</a></em></div>
  <div class="kpi"><span>Revenus pub (actifs)</span><strong>{{ nf($revenue) }}</strong><em>FCFA · CTR {{ str_replace('.', ',', $ctr) }} %</em></div>
</div>
<div class="adm-grid">
  <section class="panel span-2"><h2>Visites par jour</h2><div class="chart-box">{!! Chart::line(array_column($daily, 'label'), [['label' => 'Visites', 'values' => array_column($daily, 'v')]]) !!}</div></section>
  <section class="panel"><h2>Sources de trafic</h2><div class="chart-box">{!! Chart::donut(array_map(fn ($x) => ['k' => $srcLabels[$x['k']] ?? $x['k'], 'n' => $x['n']], $sources)) !!}</div></section>
  <section class="panel"><h2>Lectures par rubrique</h2><ul class="bars">@php($mx = max(1, ...array_column($byRub, 'n') ?: [1]))@foreach ($byRub as $b)<li><span class="bl">{{ $b['k'] }}</span><span class="bt"><i style="width:{{ $b['n'] / $mx * 100 }}%"></i></span><span class="bv">{{ nf($b['n']) }}</span></li>@endforeach</ul></section>
  <section class="panel span-2"><h2>Campagnes publicitaires</h2><div class="chart-box">{!! Chart::bars($campaigns->pluck('advertiser')->all(), $campaigns->pluck('impressions')->all(), 220) !!}</div>
    <p class="note mb-0">Impressions cumulées par annonceur · <a href="{{ route('admin.ads') }}">détail et CTR</a></p></section>
  <section class="panel span-3"><h2>Articles les plus lus</h2>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Titre</th><th>Rubrique</th><th>Auteur</th><th class="num">Lectures</th></tr></thead>
    <tbody>@foreach ($top as $a)<tr><td><a href="{{ route('admin.articles.edit', $a->id) }}">{{ $a->title }}</a></td><td>{{ $a->rubrique?->name }}</td><td>{{ $a->author?->name }}</td><td class="num">{{ nf($a->views) }}</td></tr>@endforeach</tbody></table></div></section>
  <section class="panel"><h2>À venir</h2>
    <ul class="feed">@forelse ($upcoming as $a)<li><span class="f-date">{{ $a->published_at?->format('d/m') }}</span><div><a href="{{ route('admin.articles.edit', $a->id) }}">{{ $a->title }}</a><div class="meta">{{ $a->status === 'programmé' ? 'Programmé' : 'Brouillon' }} · {{ $a->author?->name }}</div></div></li>@empty<li class="muted">Rien de programmé.</li>@endforelse</ul>
    <a href="{{ route('admin.calendar') }}" class="small-link">Ouvrir le calendrier</a></section>
  <section class="panel"><h2>À traiter</h2>
    <ul class="todo"><li><a href="{{ route('admin.comments') }}">Commentaires à modérer</a><span class="badge-c">{{ $pendingComments }}</span></li><li><a href="{{ route('admin.inbox') }}">Propositions de talents</a><span class="badge-c">{{ $pendingSubs }}</span></li><li><a href="{{ route('admin.inbox', ['onglet' => 'messages']) }}">Messages non lus</a><span class="badge-c">{{ $unread }}</span></li><li><a href="{{ route('admin.articles', ['statut' => 'brouillon']) }}">Brouillons</a><span class="badge-c">{{ $drafts }}</span></li><li><a href="{{ route('admin.stats') }}#geo">Passages de robots IA (30 j)</a><span class="badge-c">{{ nf($aiBots) }}</span></li></ul></section>
  <section class="panel"><h2>Activité récente</h2>
    <ul class="feed">@foreach ($activity as $x)<li><span class="av sm" aria-hidden="true">{{ initials($x->who) }}</span><div><strong>{{ $x->who }}</strong> {{ $x->action }}<div class="meta">{{ $x->created_at?->format('d/m/Y H:i') }}</div></div></li>@endforeach</ul>
    <a href="{{ route('admin.journal') }}" class="small-link">Tout le journal</a></section>
</div>
@endsection
