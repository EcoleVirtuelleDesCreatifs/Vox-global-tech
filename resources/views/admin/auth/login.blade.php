@extends('layouts.auth')
@section('title', 'Connexion')
@section('action', route('admin.login.post'))
@section('form')
<h1>Espace admin</h1><p class="muted">Connectez-vous pour gérer le média.</p>
@if (session('ok'))<p class="ok-t">{{ session('ok') }}</p>@endif
<div><label class="form-label" for="lg-e">E-mail</label><input id="lg-e" name="email" class="form-control {{ $errors->has('email') ? 'is-bad' : '' }}" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
<div><label class="form-label" for="lg-p">Mot de passe</label><input id="lg-p" name="password" class="form-control" type="password" autocomplete="current-password" required></div>
<div class="form-check"><input class="form-check-input" type="checkbox" id="lg-r" name="remember" value="1"><label class="form-check-label" for="lg-r">Rester connecté·e</label></div>
@if ($errors->any())<div class="field-err">{{ $errors->first() }}</div>@endif
<button class="btn btn-red w-100 justify-content-center" type="submit">Se connecter</button>
<p class="note mb-0"><a href="{{ route('admin.password.request') }}">Mot de passe oublié ?</a></p>
@endsection
