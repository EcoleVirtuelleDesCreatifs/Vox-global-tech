@extends('layouts.admin')
@section('title', 'Calendrier éditorial')
@section('content')
<div class="adm-head"><div><h1>Calendrier éditorial</h1><p class="muted mb-0">Publications passées, programmées et brouillons, jour par jour.</p></div>
  <div class="d-flex gap-2 align-items-center"><a class="icon-btn" href="{{ route('admin.calendar', ['mois' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Mois précédent"><svg class="svg-i" style="transform:rotate(180deg)"><use href="#i-arrow"/></svg></a><strong class="cal-title">{{ $month->locale('fr')->translatedFormat('F Y') }}</strong><a class="icon-btn" href="{{ route('admin.calendar', ['mois' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Mois suivant">{!! svg_icon('arrow') !!}</a><a class="btn btn-ghost" href="{{ route('admin.calendar') }}">Aujourd’hui</a>@can('draft')<a class="btn btn-red" href="{{ route('admin.articles.create') }}">Nouvel article</a>@endcan</div></div>
<div class="cal-legend"><span><i class="lg pub"></i>Publié</span><span><i class="lg sched"></i>Programmé</span><span><i class="lg draft"></i>Brouillon</span></div>
<div class="cal-wrap"><div class="cal">
  @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $d)<div class="cal-h">{{ $d }}</div>@endforeach
  @foreach ($days as $d)
    <div class="cal-d {{ $d['out'] ? 'out' : '' }} {{ $d['today'] ? 'today' : '' }}"><span class="cal-n">{{ $d['d']->day }}</span>
      @foreach ($d['items'] as $a)@php($st = $a->effectiveStatus())<a href="{{ route('admin.articles.edit', $a->id) }}" class="cal-item {{ $st === 'publié' ? 'pub' : ($st === 'programmé' ? 'sched' : 'draft') }}" title="{{ $a->published_at->format('H:i') }} · {{ $a->title }}">{{ $a->title }}</a>@endforeach
    </div>
  @endforeach
</div></div>
@endsection
