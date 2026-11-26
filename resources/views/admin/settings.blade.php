@extends('layouts.admin')
@section('title', 'Réglages')
@section('content')
@php($sw = fn ($k, $label, $hint = null) => '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="st-'.$k.'" name="'.$k.'" value="1" '.(! empty($s[$k]) ? 'checked' : '').'><label class="form-check-label" for="st-'.$k.'">'.e($label).'</label>'.($hint ? '<p class="note mb-0">'.e($hint).'</p>' : '').'</div>')
<div class="adm-head"><div><h1>Réglages</h1><p class="muted mb-0">Identité du média, référencement, réseaux sociaux, modération, notifications et données.</p></div>
  <a class="btn btn-ghost" href="{{ route('admin.diagnostic') }}">Lancer le diagnostic</a></div>
<div class="tabs" role="group" aria-label="Sections">
  @foreach ($tabs as $k => $l)<a href="{{ route('admin.settings', ['onglet' => $k]) }}" aria-pressed="{{ $tab === $k ? 'true' : 'false' }}">{{ $l }}</a>@endforeach
</div>

@if ($tab !== 'data')
<form method="POST" action="{{ route('admin.settings.update') }}" class="panel d-grid gap-3" style="max-width:780px">@csrf
  <input type="hidden" name="tab" value="{{ $tab }}">
  @switch($tab)
    @case('general')
      <div><label class="form-label" for="st-n">Nom du média</label><input id="st-n" name="name" class="form-control" value="{{ old('name', $s['name']) }}" required maxlength="80"></div>
      <div><label class="form-label" for="st-t">Signature</label><input id="st-t" name="tagline" class="form-control" value="{{ old('tagline', $s['tagline']) }}" required maxlength="200"></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="st-er">E-mail de la rédaction</label><input id="st-er" name="email_redaction" type="email" class="form-control" value="{{ old('email_redaction', $s['email_redaction']) }}" required><p class="note mb-0">Reçoit les propositions, messages et alertes.</p></div>
        <div class="col-md-6"><label class="form-label" for="st-ep">E-mail partenariats / régie</label><input id="st-ep" name="email_partners" type="email" class="form-control" value="{{ old('email_partners', $s['email_partners']) }}" required><p class="note mb-0">Reçoit les demandes d’annonceurs et les alertes de fin de campagne.</p></div>
        <div class="col-md-6"><label class="form-label" for="st-ph">Téléphone</label><input id="st-ph" name="phone" class="form-control" value="{{ old('phone', $s['phone']) }}" maxlength="40" placeholder="+225 …"></div>
        <div class="col-md-6"><label class="form-label" for="st-ad">Adresse</label><input id="st-ad" name="address" class="form-control" value="{{ old('address', $s['address']) }}" maxlength="200"></div>
      </div>
      <div><label class="form-label" for="st-p">Articles par page dans les rubriques</label><select id="st-p" name="per_page" class="form-select w-auto">@foreach ([4, 6, 9, 12] as $n)<option value="{{ $n }}" @selected((int) old('per_page', $s['per_page']) === $n)>{{ $n }}</option>@endforeach</select></div>
      {!! $sw('maintenance', 'Mode maintenance', 'Les lecteurs voient une page « Maintenance en cours » (code 503, conservée par Google). La rédaction connectée continue de voir le site.') !!}
      @break
    @case('seo')
      <div><label class="form-label" for="st-st">Titre par défaut (page d’accueil)</label><input id="st-st" name="seo_title" class="form-control" value="{{ old('seo_title', $s['seo_title']) }}" required maxlength="80" data-counter="60"><div class="char-c" data-for="st-st"></div></div>
      <div><label class="form-label" for="st-sd">Description par défaut</label><textarea id="st-sd" name="seo_description" rows="3" class="form-control" required maxlength="200" data-counter="155">{{ old('seo_description', $s['seo_description']) }}</textarea><div class="char-c" data-for="st-sd"></div></div>
      <div class="serp" aria-label="Aperçu dans Google"><span class="serp-url">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span><span class="serp-t" data-mirror="st-st">{{ $s['seo_title'] }}</span><span class="serp-d" data-mirror="st-sd">{{ $s['seo_description'] }}</span></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="st-gv">Google Search Console (code de vérification)</label><input id="st-gv" name="google_verification" class="form-control" value="{{ old('google_verification', $s['google_verification']) }}" placeholder="contenu de la balise google-site-verification"></div>
        <div class="col-md-6"><label class="form-label" for="st-bv">Bing Webmaster Tools (code de vérification)</label><input id="st-bv" name="bing_verification" class="form-control" value="{{ old('bing_verification', $s['bing_verification']) }}" placeholder="contenu de la balise msvalidate.01"></div>
      </div>
      <h2 class="h-sm mb-0">GEO : visibilité dans les moteurs de réponse IA</h2>
      {!! $sw('ai_crawlers', 'Autoriser les robots des IA (GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Google-Extended, Applebot…)', 'Indispensable pour être cité par ChatGPT, Claude, Perplexity, Gemini et Copilot. Les passages sont mesurés dans Statistiques › GEO.') !!}
      {!! $sw('indexnow', 'Notifier IndexNow (Bing, Yandex, Seznam, Naver) à chaque publication', 'Indexation en quelques minutes ; Bing alimente aussi ChatGPT Search et Copilot.') !!}
      <div class="note">Clé IndexNow : <code>{{ $indexnowKey }}</code> · fichier de vérification <a href="{{ url($indexnowKey.'.txt') }}" target="_blank">{{ $indexnowKey }}.txt</a></div>
      <div class="d-flex flex-wrap gap-2">
        @foreach (['robots' => 'robots.txt', 'sitemap' => 'sitemap.xml', 'llms' => 'llms.txt', 'llms.full' => 'llms-full.txt', 'feed' => 'Flux RSS', 'feed.json' => 'JSON Feed'] as $r => $l)<a class="mini-btn" href="{{ route($r) }}" target="_blank">{{ $l }}</a>@endforeach
      </div>
      @break
    @case('social')
      <p class="muted mb-0">Les liens renseignés apparaissent dans le pied de page et dans les données structurées <code>Organization.sameAs</code> (reconnaissance de la marque par Google et les IA).</p>
      @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'x' => 'X (Twitter)'] as $k => $l)
        <div><label class="form-label" for="so-{{ $k }}">{{ $l }}</label><input id="so-{{ $k }}" name="social[{{ $k }}]" type="url" class="form-control" value="{{ old('social.'.$k, $s['social'][$k] ?? '') }}" placeholder="https://"></div>
      @endforeach
      @break
    @case('moder')
      {!! $sw('comments_on', 'Autoriser les commentaires sous les articles') !!}
      {!! $sw('moderate', 'Relire les commentaires avant publication', 'Protection anti-spam active dans tous les cas : pot de miel, délai minimal de saisie, limitation de débit, filtre de liens.') !!}
      {!! $sw('ads_on', 'Afficher les espaces publicitaires sur le site') !!}
      @break
    @case('mail')
      <p class="muted mb-0">Pilote d’envoi actuel : <strong>{{ config('mail.default') }}</strong> · expéditeur <strong>{{ config('mail.from.address') }}</strong>. Le serveur SMTP se règle dans le fichier <code>.env</code> (voir le README). @if (config('mail.default') === 'log')<span class="text-danger">Les e-mails sont seulement écrits dans les journaux : configurez un SMTP avant la mise en ligne.</span>@endif</p>
      {!! $sw('notify_comments', 'Prévenir la rédaction à chaque nouveau commentaire à modérer') !!}
      {!! $sw('notify_forms', 'Prévenir la rédaction à chaque proposition de talent, demande d’annonceur ou message de contact', 'Les lecteurs reçoivent dans tous les cas un accusé de réception.') !!}
      {!! $sw('weekly_report', 'Envoyer un rapport d’audience hebdomadaire aux administrateurs (lundi 8 h)') !!}
      <div class="note">E-mails automatiques du site : confirmation d’inscription (double opt-in), bienvenue, newsletter, accusés de réception des formulaires, publication et réponse aux commentaires, suivi des propositions, téléchargement de publication, invitation et réinitialisation de mot de passe, alerte de connexion admin, fin de campagne publicitaire, rapport hebdomadaire.</div>
      @break
  @endswitch
  <div><button class="btn btn-red" type="submit">Enregistrer</button></div>
</form>
@if ($tab === 'mail')
  <form method="POST" action="{{ route('admin.diagnostic.mail') }}" class="mt-3">@csrf<button class="btn btn-ghost" type="submit">M’envoyer un e-mail de test</button></form>
@endif
@else
<div class="adm-grid">
  <section class="panel span-2"><h2>Statistiques</h2>
    <p class="muted">Si des statistiques de démonstration ont été générées à l’installation, remettez les compteurs à zéro avant la mise en ligne : la mesure repartira des vraies visites.</p>
    <div class="d-flex flex-wrap gap-2">
      <form method="POST" action="{{ route('admin.settings.purge') }}">@csrf<button class="btn btn-ghost danger-t" type="submit" data-confirm="Effacer toutes les statistiques d’audience (pages vues, passages de robots, recherches) ?" data-confirm-label="Remettre à zéro">Remettre les statistiques à zéro</button></form>
      <form method="POST" action="{{ route('admin.settings.purge') }}">@csrf<input type="hidden" name="do" value="regen"><button class="btn btn-ghost" type="submit" data-confirm="Remplacer les statistiques par 90 jours de données de démonstration ?" data-confirm-label="Générer">Régénérer des données de démonstration</button></form>
    </div></section>
  <section class="panel"><h2>Exports</h2>
    <ul class="plain"><li><a href="{{ route('admin.subscribers.export') }}">Abonnés (CSV)</a></li><li><a href="{{ route('admin.stats.export') }}">Statistiques (CSV)</a></li></ul>
    <p class="note mb-0">Sauvegarde : planifiez un export de la base de données chez votre hébergeur (voir README).</p></section>
</div>
@endif
@endsection
