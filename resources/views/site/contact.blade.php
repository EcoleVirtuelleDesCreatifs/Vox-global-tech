@extends('layouts.site', ['page' => 'contact'])
@section('content')
<div class="wrap">
  <header class="page-head"><h1>Contact</h1><p class="lead-muted">Une question, une correction, une idée de sujet ? Écrivez à la rédaction.</p></header>
  <div class="form-shell" id="formulaire">
    <div><dl class="spec"><div><dt>Rédaction</dt><dd><a href="mailto:{{ setting('email_redaction') }}">{{ setting('email_redaction') }}</a></dd></div><div><dt>Partenariats</dt><dd><a href="mailto:{{ setting('email_partners') }}">{{ setting('email_partners') }}</a></dd></div>@if (setting('phone'))<div><dt>Téléphone</dt><dd>{{ setting('phone') }}</dd></div>@endif<div><dt>Adresse</dt><dd>{{ setting('address') }}</dd></div></dl></div>
    <div class="form-card">
      @if (session('contact_sent'))
        <div class="success"><h3>Message envoyé.</h3><p class="muted">Un accusé de réception vous a été envoyé par e-mail. Nous vous répondrons rapidement.</p><a class="btn btn-ink" href="{{ route('contact') }}">Nouveau message</a></div>
      @else
      @php($e = $errors->contact)
      <form method="POST" action="{{ route('contact.store') }}" novalidate class="d-grid gap-3">
        @csrf @include('partials.hp')
        <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="c-n">Nom</label><input id="c-n" name="name" class="form-control {{ $e->has('name') ? 'is-bad' : '' }}" value="{{ old('name') }}" autocomplete="name">@if ($e->has('name'))<div class="field-err">{{ $e->first('name') }}</div>@endif</div>
          <div class="col-sm-6"><label class="form-label" for="c-e">E-mail</label><input id="c-e" name="email" type="email" class="form-control {{ $e->has('email') ? 'is-bad' : '' }}" value="{{ old('email') }}" autocomplete="email">@if ($e->has('email'))<div class="field-err">{{ $e->first('email') }}</div>@endif</div></div>
        <div><label class="form-label" for="c-s">Objet</label><input id="c-s" name="subject" class="form-control {{ $e->has('subject') ? 'is-bad' : '' }}" value="{{ old('subject', request('objet')) }}">@if ($e->has('subject'))<div class="field-err">{{ $e->first('subject') }}</div>@endif</div>
        <div><label class="form-label" for="c-m">Message</label><textarea id="c-m" name="message" rows="5" class="form-control {{ $e->has('message') ? 'is-bad' : '' }}">{{ old('message') }}</textarea>@if ($e->has('message'))<div class="field-err">{{ $e->first('message') }}</div>@endif</div>
        <button class="btn btn-red justify-self-start" type="submit">Envoyer</button>
      </form>
      @endif
    </div>
  </div>
</div>
@endsection
