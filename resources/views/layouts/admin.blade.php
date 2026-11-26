@php($u = auth()->user())
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Administration') · {{ setting('name') }}</title>
<link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link rel="preload" href="{{ asset('assets/fonts/outfit-var.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">
<link rel="stylesheet" href="{{ asset('assets/css/vox.css') }}?v={{ filemtime(public_path('assets/css/vox.css')) }}">
<script>try{var t=localStorage.getItem('vx-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script>
</head>
<body>
@include('partials.sprite')
<div class="admin">
 <div class="adm-shell">
  <aside class="adm-nav" id="adm-nav">
    <a href="{{ route('admin.dashboard') }}" class="brand" aria-label="VOX TECH GLOBAL, tableau de bord">@include('partials.logo')</a>
    <nav aria-label="Administration">
      @php($is = fn (...$r) => request()->routeIs(...$r) ? 'on' : '')
      <a href="{{ route('admin.dashboard') }}" class="{{ $is('admin.dashboard') }}">{!! svg_icon('dash') !!}Tableau de bord</a>
      @can('stats')<a href="{{ route('admin.stats') }}" class="{{ $is('admin.stats') }}">{!! svg_icon('chart') !!}Statistiques</a>@endcan
      <span class="nav-grp">Contenus</span>
      <a href="{{ route('admin.articles') }}" class="{{ $is('admin.articles*') }}">{!! svg_icon('doc') !!}Articles</a>
      <a href="{{ route('admin.calendar') }}" class="{{ $is('admin.calendar') }}">{!! svg_icon('cal') !!}Calendrier éditorial</a>
      @can('moderate')<a href="{{ route('admin.comments') }}" class="{{ $is('admin.comments*') }}">{!! svg_icon('chat') !!}Commentaires @if ($pendingComments)<span class="badge-n">{{ $pendingComments }}</span>@endif</a>@endcan
      <a href="{{ route('admin.media') }}" class="{{ $is('admin.media*') }}">{!! svg_icon('img') !!}Médiathèque</a>
      @can('edit-others')<a href="{{ route('admin.rubriques') }}" class="{{ $is('admin.rubriques*') }}">{!! svg_icon('folder') !!}Rubriques et dossiers</a>@endcan
      @can('library')<a href="{{ route('admin.library') }}" class="{{ $is('admin.library*') }}">{!! svg_icon('book') !!}Bibliothèque</a>@endcan
      <span class="nav-grp">Audience et revenus</span>
      @can('newsletter')<a href="{{ route('admin.newsletter') }}" class="{{ $is('admin.newsletter*') }}">{!! svg_icon('send') !!}Newsletter</a>
      <a href="{{ route('admin.subscribers') }}" class="{{ $is('admin.subscribers*') }}">{!! svg_icon('mail') !!}Abonnés</a>@endcan
      @can('ads')<a href="{{ route('admin.ads') }}" class="{{ $is('admin.ads*') }}">{!! svg_icon('ad') !!}Publicités</a>@endcan
      <a href="{{ route('admin.inbox') }}" class="{{ $is('admin.inbox*') }}">{!! svg_icon('inbox') !!}Boîte de réception @if ($inboxCount)<span class="badge-n">{{ $inboxCount }}</span>@endif</a>
      <span class="nav-grp">Administration</span>
      @can('users')<a href="{{ route('admin.users') }}" class="{{ $is('admin.users*') }}">{!! svg_icon('users') !!}Utilisateurs et rôles</a>@endcan
      <a href="{{ route('admin.journal') }}" class="{{ $is('admin.journal') }}">{!! svg_icon('list') !!}Journal d’activité</a>
      @can('settings')<a href="{{ route('admin.settings') }}" class="{{ $is('admin.settings*') }}">{!! svg_icon('gear') !!}Réglages</a>
      <a href="{{ route('admin.diagnostic') }}" class="{{ $is('admin.diagnostic*') }}">{!! svg_icon('pulse') !!}Diagnostic & SEO</a>@endcan
    </nav>
    <div class="adm-nav-foot">
      <a href="{{ route('home') }}" target="_blank">{!! svg_icon('eye') !!}Voir le site</a>
      <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit">{!! svg_icon('out') !!}Se déconnecter</button></form>
    </div>
  </aside>
  <div class="adm-main">
    <header class="adm-top">
      <button class="icon-btn d-lg-none" type="button" data-adm-menu aria-label="Menu d’administration">{!! svg_icon('menu') !!}</button>
      <div class="adm-search">
        <label for="adm-s" class="visually-hidden">Rechercher dans l’admin</label>{!! svg_icon('search') !!}
        <input id="adm-s" class="form-control" type="search" placeholder="Rechercher un article, un utilisateur, une campagne…" autocomplete="off" data-api="{{ route('admin.search') }}">
        <div class="adm-hits" hidden></div>
      </div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="notif-wrap">
          <button class="icon-btn" type="button" data-notif aria-expanded="false" aria-label="Notifications : {{ count($notifs) }}">{!! svg_icon('bell') !!}@if (count($notifs))<span class="dot-n">{{ count($notifs) }}</span>@endif</button>
          <div class="notif-pop" hidden><p class="side-title mb-2">Notifications</p>@forelse ($notifs as $n)<a href="{{ $n[1] }}">{{ $n[0] }}</a>@empty<p class="muted mb-0">Tout est à jour.</p>@endforelse</div>
        </div>
        <button class="icon-btn mh-icon" type="button" data-theme-toggle aria-label="Changer de thème"><svg class="svg-i i-dark"><use href="#i-moon"/></svg><svg class="svg-i i-light"><use href="#i-sun"/></svg></button>
        <a href="{{ route('admin.profile') }}" class="d-flex align-items-center gap-2" title="Mon profil"><span class="av" aria-hidden="true">{{ $u->initials() }}</span><span class="d-none d-sm-inline">{{ $u->name }}<small class="d-block meta">{{ $u->role }}</small></span></a>
      </div>
    </header>
    <div class="adm-content">
      @if (session('ok'))<div class="flash-msg" role="status">{{ session('ok') }}<button type="button" data-dismiss aria-label="Fermer">×</button></div>@endif
      @if (session('err'))<div class="flash-msg err" role="alert">{{ session('err') }}<button type="button" data-dismiss aria-label="Fermer">×</button></div>@endif
      @if ($errors->any())<div class="flash-msg err" role="alert"><span>{{ $errors->first() }}@if ($errors->count() > 1) (+{{ $errors->count() - 1 }} autre(s) erreur(s))@endif</span><button type="button" data-dismiss aria-label="Fermer">×</button></div>@endif
      @yield('content')
    </div>
  </div>
 </div>
</div>
<div class="confirm-back" hidden data-confirm-box>
  <div class="confirm" role="alertdialog" aria-modal="true" aria-labelledby="cf-t">
    <h2 id="cf-t">Supprimer cet élément ?</h2><p class="muted" data-confirm-text>Cette action est définitive.</p>
    <div class="d-flex gap-2 justify-content-end"><button class="btn btn-ghost" type="button" data-confirm-no>Annuler</button><button class="btn btn-red" type="button" data-confirm-yes>Confirmer</button></div>
  </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div class="vx-toast" role="status" aria-live="polite" hidden></div></div>
<script src="{{ asset('assets/js/admin.js') }}?v={{ filemtime(public_path('assets/js/admin.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
