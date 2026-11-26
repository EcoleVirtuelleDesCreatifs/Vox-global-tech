@extends('layouts.site', ['page' => $charte ? 'charte' : 'a-propos'])
@section('content')
<section class="hero-page"><div class="wrap"><span class="eyebrow light">{{ $charte ? 'Charte éditoriale' : 'À propos' }}</span>
  <h1>{{ $charte ? 'Une information utile, vérifiée et contextualisée.' : 'Chaque talent mérite d’être découvert.' }}</h1>
  <p>Chaque innovation mérite d’être comprise. Chaque avancée technologique mérite d’être racontée avec rigueur.</p></div></section>
<div class="wrap about">
  <nav class="about-nav" aria-label="Sections">
    @foreach ([['ab-qui', 'Qui sommes-nous'], ['ab-vision', 'Vision et mission'], ['ab-valeurs', 'Valeurs'], ['ab-public', 'Public'], ['ab-ligne', 'Ligne éditoriale'], ['ab-charte', 'Charte et vérification'], ['ab-equipe', 'Équipe'], ['ab-contact', 'Contact']] as $t)
      <a href="#{{ $t[0] }}">{{ $t[1] }}</a>
    @endforeach
  </nav>
  <div class="about-body">
    <section id="ab-qui"><h2>Qui sommes-nous ?</h2>
      <p class="lead-muted">VOX TECH GLOBAL (VTG) est un média numérique international dédié à l’information technologique, à la découverte et à la valorisation des talents, des startups, des innovations et des initiatives qui contribuent à la transformation numérique du monde.</p>
      <p>Initié par l’École Virtuelle des Créatifs (EVC), le média accorde une attention particulière aux talents émergents, aux inventeurs, aux développeurs, aux chercheurs, aux startups et aux initiatives technologiques encore peu médiatisées. À travers des articles, des reportages, des interviews et des analyses, VOX TECH GLOBAL rend l’information technologique accessible à tous.</p></section>
    <section id="ab-vision"><h2>Vision et mission</h2>
      <p class="lead-muted">Devenir un média international de référence dans la découverte, l’information et la valorisation des talents et des innovations technologiques, avec une attention particulière aux écosystèmes émergents.</p>
      <div class="missions">@foreach ([['Découvrir', 'les talents technologiques et les initiatives innovantes encore peu médiatisés.'], ['Informer', 'sur l’actualité technologique nationale, africaine et internationale.'], ['Valoriser', 'les réalisations des inventeurs, développeurs, entrepreneurs, chercheurs et créateurs.'], ['Décrypter', 'les tendances, les transformations et les enjeux technologiques.'], ['Connecter', 'les talents, les innovations et les opportunités à travers une information accessible et crédible.']] as $m)<div class="mission"><b>{{ $m[0] }}</b><span>{{ $m[1] }}</span></div>@endforeach</div></section>
    <section id="ab-valeurs"><h2>Nos valeurs</h2>
      <div class="values">@foreach ([['Innovation', 'Une place importante aux idées nouvelles, aux découvertes et aux solutions technologiques.'], ['Crédibilité', 'Des informations vérifiées, fiables et documentées.'], ['Indépendance', 'L’autonomie des décisions journalistiques.'], ['Diversité', 'De la visibilité aux talents de tous pays, parcours et horizons.'], ['Accessibilité', 'Des sujets technologiques compréhensibles par le plus grand nombre.'], ['Responsabilité', 'Le respect de l’éthique journalistique, des personnes et de la vérité des faits.']] as $i => $v)<div class="value"><span class="n">0{{ $i + 1 }}</span><h3>{{ $v[0] }}</h3><p>{{ $v[1] }}</p></div>@endforeach</div></section>
    <section id="ab-public"><h2>Notre public</h2>
      <ul class="pub-list"><li>Développeurs, ingénieurs, designers et professionnels du numérique</li><li>Inventeurs, chercheurs et innovateurs</li><li>Startups et entrepreneurs technologiques</li><li>Étudiants et jeunes talents</li><li>Entreprises, investisseurs et incubateurs</li><li>Institutions engagées dans la transformation numérique</li><li>Grand public passionné par la technologie</li></ul>
      <p class="muted">Couverture internationale, avec une attention particulière à l’Afrique et aux écosystèmes émergents. Langue principale : français, extension prévue vers l’anglais.</p></section>
    <section id="ab-ligne"><h2>Ligne éditoriale</h2>
      <div class="man-cols mt-3">@foreach ([['Révéler les talents', 'Identifier et présenter les talents dont les réalisations manquent encore de visibilité.'], ['Explorer les innovations', 'Présenter les inventions, startups et solutions qui répondent à des besoins réels.'], ['Décrypter la Tech', 'Informer, analyser et expliquer les transformations technologiques.']] as $i => $p)<div class="man-col"><span class="man-n">0{{ $i + 1 }}</span><h3>{{ $p[0] }}</h3><p>{{ $p[1] }}</p></div>@endforeach</div>
      <p class="mt-4">VOX TECH GLOBAL ne se contente pas de relayer l’actualité technologique : le média recherche les talents, explore les innovations et explique les transformations du monde numérique. La rédaction privilégie les contenus originaux, les découvertes, les portraits, les interviews, les reportages et les analyses. La valorisation d’un talent ou d’une innovation ne se substitue jamais à la vérification journalistique.</p>
      <h3 class="h-sm mt-4">Notre style</h3>
      <div class="chips">@foreach (['Professionnel', 'Clair', 'Factuel', 'Humain', 'Dynamique', 'Accessible', 'International'] as $s)<span class="chip static">{{ $s }}</span>@endforeach</div></section>
    <section id="ab-charte"><h2>Charte éditoriale et vérification</h2>
      <div class="two-col">
        <div><h3 class="h-sm">Règles de rédaction</h3><ul class="check"><li>Un titre informatif, un chapeau, un développement structuré.</li><li>Des sources identifiables lorsque c’est possible.</li><li>Le nom de l’auteur et la date de publication.</li><li>Pas de titres sensationnalistes, d’informations non vérifiées, de citations inventées ni d’affirmations exagérées.</li><li>Les faits, opinions, annonces et hypothèses sont clairement distingués.</li></ul></div>
        <div><h3 class="h-sm">Avant publication, la rédaction</h3><ul class="check"><li>identifie l’origine de l’information ;</li><li>vérifie l’identité et la crédibilité des sources ;</li><li>contrôle dates, chiffres, documents et déclarations ;</li><li>recoupe les faits essentiels si nécessaire ;</li><li>vérifie l’authenticité et le contexte des images ;</li><li>sollicite les parties concernées en cas d’allégations sensibles ;</li><li>signale les incertitudes qui subsistent ;</li><li>fait valider le contenu selon le circuit éditorial interne.</li></ul></div>
      </div>
      <p class="callout">Les outils d’intelligence artificielle peuvent assister le travail rédactionnel, mais leurs réponses ne constituent pas des preuves suffisantes. Les erreurs significatives publiées sont corrigées de manière transparente.</p></section>
    <section id="ab-equipe"><h2>La rédaction</h2>
      <div class="team">@foreach ($authors as $a)<a href="{{ route('auteur', $a) }}" class="member"><span class="av lg" aria-hidden="true">{{ $a->initials() }}</span><span class="by-name">{{ $a->name }}</span><span class="meta">{{ $a->role }}</span></a>@endforeach</div></section>
    <section id="ab-contact"><h2>Contact</h2>
      <dl class="spec"><div><dt>Rédaction</dt><dd><a href="mailto:{{ setting('email_redaction') }}">{{ setting('email_redaction') }}</a></dd></div><div><dt>Partenariats et publicité</dt><dd><a href="mailto:{{ setting('email_partners') }}">{{ setting('email_partners') }}</a></dd></div>@if (setting('phone'))<div><dt>Téléphone</dt><dd>{{ setting('phone') }}</dd></div>@endif<div><dt>Projet initié par</dt><dd>École Virtuelle des Créatifs (EVC)</dd></div></dl>
      <a class="btn btn-ink" href="{{ route('contact') }}">Écrire à la rédaction</a></section>
  </div>
</div>
@endsection
