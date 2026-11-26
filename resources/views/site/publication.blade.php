@extends('layouts.site', ['page' => 'publication'])
@section('content')
<div class="wrap">
  <nav class="crumbs mt-4" aria-label="Fil d’Ariane"><a href="{{ route('home') }}">Accueil</a><span>›</span><a href="{{ route('bibliotheque') }}">Bibliothèque</a><span>›</span><span>{{ $pub->type }}</span></nav>
  <div class="pub-page">
    @include('partials.cover', ['p' => $pub, 'big' => true])
    <div>
      <span class="eyebrow">{{ $pub->type }}</span>
      <h1>{{ $pub->title }}</h1><p class="lead-muted">{{ $pub->subtitle }}</p>
      <p>{{ $pub->description }}</p>
      <dl class="spec"><div><dt>Parution</dt><dd>{{ fr_date($pub->published_on) }}</dd></div><div><dt>Pages</dt><dd>{{ $pub->pages }}</dd></div><div><dt>Format</dt><dd>{{ $pub->format }}</dd></div><div><dt>Prix</dt><dd>{{ fcfa($pub->price) }}</dd></div></dl>
      <div id="obtenir">
        @if (session('pub_ok'))<p class="ok-t" role="status">{{ session('pub_ok') }}</p>@endif
        <form method="POST" action="{{ route('publication.get', $pub) }}" class="d-grid gap-2" style="max-width:520px" novalidate>
          @csrf
          @if ($pub->price > 0)
            @php($e = $errors->order)
            <div class="row g-2"><div class="col-sm-6"><label class="form-label" for="o-n">Nom</label><input id="o-n" name="name" class="form-control {{ $e->has('name') ? 'is-bad' : '' }}" value="{{ old('name') }}" autocomplete="name">@if ($e->has('name'))<div class="field-err">{{ $e->first('name') }}</div>@endif</div>
              <div class="col-sm-6"><label class="form-label" for="o-e">E-mail</label><input id="o-e" name="email" type="email" class="form-control {{ $e->has('email') ? 'is-bad' : '' }}" value="{{ old('email') }}" autocomplete="email">@if ($e->has('email'))<div class="field-err">{{ $e->first('email') }}</div>@endif</div></div>
            <div><label class="form-label" for="o-p">Téléphone (Mobile Money, facultatif)</label><input id="o-p" name="phone" class="form-control" value="{{ old('phone') }}" autocomplete="tel"></div>
          @endif
          <button class="btn btn-red justify-self-start" type="submit">{!! svg_icon('dl') !!}{{ $pub->price ? 'Commander · '.fcfa($pub->price) : 'Télécharger gratuitement' }}</button>
        </form>
      </div>
      @if ($pub->toc)<h2 class="h-sm mt-5">Sommaire</h2><ol class="toc">@foreach ($pub->toc as $t)<li>{{ $t }}</li>@endforeach</ol>@endif
    </div>
  </div>
</div>
@endsection
