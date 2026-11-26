@extends('layouts.admin')
@section('title', 'Mon profil')
@section('content')
<div class="adm-head"><div><h1>Mon profil</h1><p class="muted mb-0">{{ $u->role }} · dernière connexion {{ $u->last_login_at?->format('d/m/Y H:i') ?? '—' }}</p></div>
  @if ($u->author)<a class="btn btn-ghost" href="{{ $u->author->url() }}" target="_blank">Voir ma page auteur</a>@endif</div>
<form method="POST" action="{{ route('admin.profile.update') }}" class="adm-grid">@csrf @method('PUT')
  <section class="panel span-2 d-grid gap-3">
    <h2>Identité</h2>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="p-n">Nom complet</label><input id="p-n" name="name" class="form-control" value="{{ old('name', $u->name) }}" required maxlength="100" autocomplete="name"></div>
      <div class="col-md-6"><label class="form-label" for="p-e">E-mail</label><input id="p-e" name="email" type="email" class="form-control" value="{{ old('email', $u->email) }}" required autocomplete="email"></div>
    </div>
    @if ($u->author)
      <div><label class="form-label" for="p-b">Biographie publique</label><textarea id="p-b" name="bio" rows="4" class="form-control" maxlength="600" data-counter="600">{{ old('bio', $u->author->bio) }}</textarea><div class="char-c" data-for="p-b"></div>
        <p class="note mb-0">Affichée sous vos articles et sur votre page auteur. Une bio précise (expertise, parcours) renforce la crédibilité E-E-A-T auprès de Google et des IA.</p></div>
      <div><label class="form-label" for="p-s">Profils officiels (une adresse par ligne : LinkedIn, X, site personnel…)</label><textarea id="p-s" name="same_as" rows="3" class="form-control" placeholder="https://www.linkedin.com/in/…">{{ old('same_as', implode("\n", $u->author->same_as ?? [])) }}</textarea>
        <p class="note mb-0">Repris dans les données structurées <code>Person.sameAs</code> pour relier votre identité dans le Knowledge Graph.</p></div>
    @else
      <p class="note mb-0">Votre compte n’est pas relié à une fiche auteur. Un administrateur peut l’associer depuis « Utilisateurs et rôles ».</p>
    @endif
  </section>
  <section class="panel d-grid gap-3 align-content-start">
    <h2>Mot de passe</h2>
    <div><label class="form-label" for="p-c">Mot de passe actuel</label><input id="p-c" name="current_password" type="password" class="form-control" autocomplete="current-password"></div>
    <div><label class="form-label" for="p-p">Nouveau mot de passe</label><input id="p-p" name="password" type="password" class="form-control" autocomplete="new-password" minlength="12"><p class="note mb-0">12 caractères minimum, lettres et chiffres.</p></div>
    <div><label class="form-label" for="p-pc">Confirmation</label><input id="p-pc" name="password_confirmation" type="password" class="form-control" autocomplete="new-password"></div>
    <p class="note mb-0">Laissez vide pour conserver le mot de passe actuel. Un e-mail de sécurité vous est envoyé à chaque changement.</p>
  </section>
  <div class="span-3"><button class="btn btn-red" type="submit">Enregistrer</button></div>
</form>
@endsection
