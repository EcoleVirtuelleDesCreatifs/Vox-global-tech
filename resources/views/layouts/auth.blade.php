<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>@yield('title') · {{ setting('name') }}</title><link rel="icon" href="{{ asset('assets/img/favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/style.css') }}"><link rel="stylesheet" href="{{ asset('assets/css/vox.css') }}">
<script>try{var t=localStorage.getItem('vx-theme');if(t)document.documentElement.setAttribute('data-theme',t);}catch(e){}</script></head>
<body><div class="admin"><div class="login-wrap">
  <form class="login-card" method="POST" action="@yield('action')" novalidate>@csrf
    <a href="{{ route('home') }}" class="brand" aria-label="Retour au site">@include('partials.logo')</a>
    @yield('form')
  </form>
</div></div></body></html>
