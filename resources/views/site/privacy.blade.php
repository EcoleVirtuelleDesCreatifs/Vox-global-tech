@extends('layouts.site', ['page' => 'confidentialite'])
@section('content')
<div class="wrap narrow"><header class="page-head"><h1>Politique de confidentialité</h1><p class="lead-muted">Comment {{ setting('name') }} traite vos données personnelles.</p></header>
  <div class="prose"><h2>Données collectées</h2><p>Adresse e-mail (newsletter, avec double confirmation), nom et e-mail (commentaires, propositions, contact, demandes annonceurs) et données de mesure d’audience anonymisées.</p>
  <h2>Mesure d’audience sans cookie</h2><p>Notre mesure d’audience est réalisée sur nos propres serveurs, sans cookie publicitaire ni outil tiers. Votre adresse IP n’est jamais enregistrée : elle est transformée en empreinte irréversible renouvelée chaque jour. Nous mesurons les pages vues, la durée de lecture, le type d’appareil et le pays approximatif.</p>
  <h2>Utilisation</h2><p>Envoi de la Newsletter VOX, réponse à vos demandes, modération des commentaires, amélioration du site. Vos données ne sont jamais vendues.</p>
  <h2>Durée de conservation</h2><p>Les abonnés sont conservés jusqu’à leur désinscription ; les messages et propositions trois ans maximum ; les statistiques détaillées treize mois.</p>
  <h2>Vos droits</h2><p>Vous pouvez accéder à vos données, les corriger ou les supprimer en écrivant à <a href="mailto:{{ setting('email_redaction') }}">{{ setting('email_redaction') }}</a>. Chaque newsletter contient un lien de désinscription en un clic.</p>
  <h2>Cookies</h2><p>Le site n’utilise que des cookies techniques indispensables (session, sécurité des formulaires) et une préférence d’affichage clair/sombre stockée sur votre appareil.</p></div></div>
@endsection
