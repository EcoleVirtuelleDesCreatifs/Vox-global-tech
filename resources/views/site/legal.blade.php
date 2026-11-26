@extends('layouts.site', ['page' => 'mentions'])
@section('content')
<div class="wrap narrow"><header class="page-head"><h1>Mentions légales</h1></header>
  <div class="prose"><h2>Éditeur</h2><p>{{ setting('name') }}, média numérique initié par l’École Virtuelle des Créatifs (EVC). Adresse : {{ setting('address') }}. Contact : {{ setting('email_redaction') }}. [Forme juridique, numéro d’immatriculation et directeur·rice de la publication à compléter.]</p>
  <h2>Hébergement</h2><p>[Nom, adresse et téléphone de l’hébergeur à compléter.]</p>
  <h2>Propriété intellectuelle</h2><p>L’ensemble des contenus (textes, visuels, logo) est protégé. Toute reproduction sans autorisation est interdite, à l’exception des courtes citations accompagnées d’un lien vers l’article d’origine.</p>
  <h2>Contenus sponsorisés</h2><p>Tout contenu payant est signalé par la mention « Partenariat » ou « Contenu sponsorisé ». Les talents sont présentés gratuitement.</p>
  <h2>Données personnelles</h2><p>Voir notre <a href="{{ route('confidentialite') }}">politique de confidentialité</a>.</p></div></div>
@endsection
