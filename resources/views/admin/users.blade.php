@extends('layouts.admin')
@section('title', 'Utilisateurs et rôles')
@section('content')
@php($roles = \App\Models\User::ROLES)
<div class="adm-head"><div><h1>Utilisateurs et rôles</h1><p class="muted mb-0">{{ $users->where('active', true)->count() }} compte(s) actif(s). Une invitation par e-mail permet à chaque membre de choisir son mot de passe.</p></div>
  <button class="btn btn-red" type="button" data-toggle="new-user">Inviter un utilisateur</button></div>

<div class="panel mb-4" id="new-user" @if (! $errors->any()) hidden @endif><h2>Inviter un utilisateur</h2>
  <form class="row g-3" method="POST" action="{{ route('admin.users.invite') }}">@csrf
    <div class="col-md-4"><label class="form-label" for="nu-n">Nom</label><input id="nu-n" name="name" class="form-control" value="{{ old('name') }}" required maxlength="100"></div>
    <div class="col-md-4"><label class="form-label" for="nu-e">E-mail</label><input id="nu-e" name="email" type="email" class="form-control" value="{{ old('email') }}" required></div>
    <div class="col-md-4"><label class="form-label" for="nu-r">Rôle</label><select id="nu-r" name="role" class="form-select">@foreach ($roles as $r)<option @selected(old('role', 'Journaliste') === $r)>{{ $r }}</option>@endforeach</select></div>
    <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="author" value="1" id="nu-a" checked><label class="form-check-label" for="nu-a">Créer une fiche auteur publique (signature des articles, page auteur, données E-E-A-T pour le SEO)</label></div></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-red" type="submit">Envoyer l’invitation</button><button class="btn btn-ghost" type="button" data-toggle="new-user">Annuler</button></div>
  </form></div>

<div class="panel p-0"><div class="table-wrap"><table class="tbl">
  <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Fiche auteur</th><th class="num">Articles</th><th>Dernière connexion</th><th>État</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
  <tbody>
  @foreach ($users as $u)
    @php($me = $u->is(auth()->user()))
    <tr class="{{ $u->active ? '' : 'dim' }}">
      <td><div class="d-flex align-items-center gap-2"><span class="av sm" aria-hidden="true">{{ $u->initials() }}</span><div><strong>{{ $u->name }}</strong>@if ($me) <span class="meta">(vous)</span>@endif<div class="meta">{{ $u->email }}</div></div></div></td>
      <td><form method="POST" action="{{ route('admin.users.update', $u) }}" data-autosubmit>@csrf @method('PUT')<label class="visually-hidden" for="ur-{{ $u->id }}">Rôle</label><select id="ur-{{ $u->id }}" name="role" class="form-select form-select-sm w-auto" @disabled($me)>@foreach ($roles as $r)<option @selected($u->role === $r)>{{ $r }}</option>@endforeach</select></form></td>
      <td><form method="POST" action="{{ route('admin.users.update', $u) }}" data-autosubmit>@csrf @method('PUT')<label class="visually-hidden" for="ua-{{ $u->id }}">Fiche auteur</label><select id="ua-{{ $u->id }}" name="author_id" class="form-select form-select-sm w-auto"><option value="">—</option>@foreach ($authors as $a)<option value="{{ $a->id }}" @selected($u->author_id === $a->id)>{{ $a->name }}</option>@endforeach</select></form></td>
      <td class="num">{{ $u->author_id ? nf($counts[$u->author_id] ?? 0) : '—' }}</td>
      <td class="nowrap">{{ $u->last_login_at?->format('d/m/Y H:i') ?? 'Jamais' }}</td>
      <td><form method="POST" action="{{ route('admin.users.update', $u) }}" class="inline-form">@csrf @method('PUT')<input type="hidden" name="active" value="{{ $u->active ? 0 : 1 }}"><button type="submit" class="status {{ $u->active ? 'ok' : 'draft' }}" @disabled($me)>{{ $u->active ? 'Actif' : 'Désactivé' }}</button></form></td>
      <td class="nowrap">
        @unless ($u->last_login_at)<form method="POST" action="{{ route('admin.users.update', $u) }}" class="inline-form">@csrf @method('PUT')<input type="hidden" name="resend" value="1"><button class="mini-btn" type="submit">Renvoyer l’invitation</button></form>@endunless
        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline-form">@csrf @method('DELETE')<button class="mini-btn danger" type="submit" @disabled($me) data-confirm="Supprimer le compte de {{ $u->name }} ? Ses articles sont conservés.">Supprimer</button></form></td>
    </tr>
  @endforeach
  </tbody></table></div></div>

<section class="panel mt-4"><h2>Droits par rôle</h2>
  <div class="table-wrap"><table class="tbl perms"><thead><tr><th>Action</th>@foreach ($roles as $r)<th class="text-center">{{ $r }}</th>@endforeach</tr></thead><tbody>
    @foreach (\App\Models\User::PERMISSIONS as [$label, $vals])<tr><td>{{ $label }}</td>@foreach ($vals as $v)<td class="text-center">@if ($v)<span class="yes" aria-label="Oui">✓</span>@else<span class="no" aria-label="Non">–</span>@endif</td>@endforeach</tr>@endforeach
  </tbody></table></div></section>
@endsection
