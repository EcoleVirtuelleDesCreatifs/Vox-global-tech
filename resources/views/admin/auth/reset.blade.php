@extends('layouts.auth')
@section('title', 'Nouveau mot de passe')
@section('action', route('admin.password.update'))
@section('form')
<h1>Choisir un mot de passe</h1><p class="muted">12 caractères minimum, avec lettres et chiffres.</p>
<input type="hidden" name="token" value="{{ $token }}">
<div><label class="form-label" for="rs-e">E-mail</label><input id="rs-e" name="email" class="form-control" type="email" value="{{ old('email', $email) }}" required></div>
<div><label class="form-label" for="rs-p">Nouveau mot de passe</label><input id="rs-p" name="password" class="form-control" type="password" autocomplete="new-password" required></div>
<div><label class="form-label" for="rs-c">Confirmation</label><input id="rs-c" name="password_confirmation" class="form-control" type="password" autocomplete="new-password" required></div>
@if ($errors->any())<div class="field-err">{{ $errors->first() }}</div>@endif
<button class="btn btn-red w-100 justify-content-center" type="submit">Enregistrer et me connecter</button>
@endsection
