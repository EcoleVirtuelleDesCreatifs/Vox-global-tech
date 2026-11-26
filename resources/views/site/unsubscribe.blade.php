@extends('layouts.site', ['page' => 'newsletter'])
@section('content')
<div class="wrap narrow"><header class="page-head"><h1>Se désabonner</h1><p class="lead-muted">Voulez-vous vraiment ne plus recevoir la Newsletter VOX à l’adresse {{ $sub->email }} ?</p>
  <form method="POST" action="{{ route('newsletter.unsubscribe', $sub->token) }}" class="d-flex gap-2 flex-wrap">@csrf<button class="btn btn-red" type="submit">Confirmer la désinscription</button><a class="btn btn-ghost" href="{{ route('home') }}">Rester inscrit·e</a></form></header></div>
@endsection
