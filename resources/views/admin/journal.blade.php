@extends('layouts.admin')
@section('title', 'Journal d’activité')
@section('content')
<div class="adm-head"><div><h1>Journal d’activité</h1><p class="muted mb-0">Toutes les actions réalisées dans l’espace admin, des plus récentes aux plus anciennes.</p></div></div>
<div class="panel"><ul class="feed big">@foreach ($logs as $x)<li><span class="av sm" aria-hidden="true">{{ initials($x->who) }}</span><div><strong>{{ $x->who }}</strong> {{ $x->action }}<div class="meta">{{ $x->created_at?->format('d/m/Y H:i') }}</div></div></li>@endforeach</ul></div>
{{ $logs->links() }}
@endsection
