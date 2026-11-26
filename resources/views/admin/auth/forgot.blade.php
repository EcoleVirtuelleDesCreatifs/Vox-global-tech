@extends('layouts.auth')
@section('title', 'Mot de passe oublié')
@section('action', route('admin.password.email'))
@section('form')
<h1>Mot de passe oublié</h1><p class="muted">Saisissez votre e-mail : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
@if (session('ok'))<p class="ok-t">{{ session('ok') }}</p>@endif
<div><label class="form-label" for="fg-e">E-mail</label><input id="fg-e" name="email" class="form-control" type="email" value="{{ old('email') }}" required autofocus></div>
@if ($errors->any())<div class="field-err">{{ $errors->first() }}</div>@endif
<button class="btn btn-red w-100 justify-content-center" type="submit">Envoyer le lien</button>
<p class="note mb-0"><a href="{{ route('admin.login') }}">← Retour à la connexion</a></p>
@endsection
