# VOX TECH GLOBAL

**La voix des talents, des innovations, des technologies et des actualités des small business émergents.**

Site média complet développé avec **Laravel 13** (PHP 8.3+) à partir de la maquette fonctionnelle *VOXTECH-GLOBAL_Media v6* : site public, espace de rédaction multi-rôles, newsletter, régie publicitaire, bibliothèque, statistiques d'audience, notifications e-mail et référencement SEO / GEO (moteurs de réponse IA).

---

## Sommaire

1. [Fonctionnalités](#fonctionnalités)
2. [Pile technique](#pile-technique)
3. [Installation locale](#installation-locale)
4. [Mise en production](#mise-en-production)
5. [E-mails et notifications](#e-mails-et-notifications)
6. [SEO et GEO 360°](#seo-et-geo-360)
7. [Statistiques](#statistiques)
8. [Tests et diagnostic](#tests-et-diagnostic)
9. [Rôles et droits](#rôles-et-droits)
10. [Structure du projet](#structure-du-projet)

---

## Fonctionnalités

### Site public
- **Accueil** : slider « À la une », Flash Tech, dernières actualités, Talents à découvrir, Innovations du monde, Startups à suivre, Décryptages, Campus & Carrières (opportunités), Dossiers spéciaux, newsletter, appel à talents.
- **6 rubriques** (5 sous-rubriques chacune) et **8 thématiques**, avec filtres et pagination.
- **Articles** : article, interview (questions-réponses), portrait, innovation documentée (fiche), dossier / enquête, fact-check (verdict), fiche startup, sources, sommaire automatique, temps de lecture, partage, commentaires modérés, articles liés.
- Dossiers spéciaux, pages auteur, recherche plein texte insensible aux accents + recherche instantanée.
- **Bibliothèque** : magazines, livres, e-books, rapports, documentations (téléchargement par lien signé, ou commande pour les titres payants).
- Newsletter (double opt-in), Proposer un talent, Annoncer, Contact, À propos / charte éditoriale, mentions légales, confidentialité.
- Mode clair / sombre, responsive, accessibilité (WCAG : contrastes, focus, ARIA, réduction des animations).
- **8 emplacements publicitaires** (728×90, 970×250, 300×250, in-article, contenu sponsorisé, partenaire de rubrique, sponsor newsletter, mobile 320×100) avec mesure des impressions et des clics.

### Espace d'administration (`/admin`)
Tableau de bord, statistiques détaillées (export CSV), articles (création, programmation, publication, actions groupées, aperçu Google, image de couverture), calendrier éditorial, modération des commentaires, médiathèque (conversion WebP automatique), rubriques / thématiques / dossiers / opportunités, bibliothèque, newsletter (composition, aperçu, test, envoi, taux d'ouverture et de clic), abonnés (export CSV), publicités (campagnes, CTR, chiffre d'affaires par emplacement), boîte de réception (propositions de talents, annonceurs, contact, commandes), utilisateurs et rôles (invitations), journal d'activité, réglages, **diagnostic technique + SEO + GEO** avec score.

---

## Pile technique

| Élément | Choix |
|---|---|
| Framework | Laravel 13 (PHP ≥ 8.3) |
| Base de données | SQLite (développement) · MySQL 8 / MariaDB 10.6+ (production) |
| Front | Blade, Bootstrap 5.3 (servi localement), CSS sur mesure, JavaScript natif sans dépendance |
| Graphiques | SVG générés côté serveur (rapides, sans librairie JS) |
| Images | GD : redimensionnement + WebP, images de partage 1200×630 générées par article |
| E-mails | Mailables Laravel, file d'attente `database`, SMTP au choix (Brevo, Mailgun, Postmark, SMTP de l'hébergeur…) |
| Tâches | Planificateur Laravel (une seule ligne de cron) |
| Tests | PHPUnit 12 — 49 tests, 424 assertions · intégration continue GitHub Actions |

Aucune étape de compilation front (pas de Node requis en production).

---

## Installation locale

Prérequis : PHP 8.3+ avec les extensions `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `gd`, `intl`, `fileinfo`, `openssl`, et Composer 2.

```bash
git clone https://github.com/EcoleVirtuelleDesCreatifs/Vox-global-tech.git
cd Vox-global-tech
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE vox_tech_global CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

- Site : <http://localhost:8000>
- Administration : <http://localhost:8000/admin> — `admin@voxtechglobal.com` / `VoxTech@2026` (modifiable via `ADMIN_EMAIL` / `ADMIN_PASSWORD` avant le seed). **Changez ce mot de passe dès la première connexion** (Mon profil).

La base par défaut est MySQL/MariaDB `vox_tech_global` (renseignez `DB_USERNAME` / `DB_PASSWORD` dans `.env`). Pour tester sans serveur MySQL : `DB_CONNECTION=sqlite` puis `touch database/database.sqlite`.

Le seeder importe les contenus éditoriaux réels de `database/data` : 21 articles publiés et 1 brouillon (Ivoire Tech Forum 2026, programmes Ivoire Tech Next 15 / Scale Up, financement des startups africaines, IA, cybersécurité, opportunités), tous sourcés, ainsi que les rubriques, dossiers et opportunités. Aucune donnée fictive (commentaires, abonnés, campagnes, audience) n'est créée ; `SEED_DEMO_STATS=true` génère, si besoin, 90 jours d'audience fictive pour une démonstration.

---

## Mise en production

### 1. Fichier `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.voxtechglobal.com      # en https : indispensable pour le SEO

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=vox_tech_global
DB_USERNAME=vox_tech_global
DB_PASSWORD=********

SESSION_SECURE_COOKIE=true
SEED_DEMO_STATS=false
LOG_LEVEL=warning
```

### 2. Déploiement

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate          # première installation uniquement
php artisan migrate --seed --force   # première installation (ensuite : migrate --force)
php artisan storage:link
php artisan optimize
```

La racine web du domaine doit pointer sur le dossier **`public/`**. Sur un hébergement mutualisé (LWS, o2switch…) qui ne permet pas de la changer, placez le projet hors de `public_html` et faites pointer `public_html` vers `public/` (lien symbolique), ou utilisez la redirection fournie par l'hébergeur.

### 3. Tâche planifiée (obligatoire)

Une seule ligne de cron, chaque minute :

```cron
* * * * * cd /chemin/vers/Vox-global-tech && php artisan schedule:run >> /dev/null 2>&1
```

Elle assure :

| Tâche | Fréquence |
|---|---|
| Envoi des e-mails en file d'attente (`queue:work --stop-when-empty`) | chaque minute |
| Mise en ligne des articles programmés + IndexNow + e-mail à l'auteur (`vox:publish-scheduled`) | chaque minute |
| Alertes de fin de campagne (J-3) et désactivation des campagnes échues (`vox:campaigns`) | tous les jours, 7 h 30 |
| Rapport d'audience hebdomadaire aux administrateurs (`vox:weekly-report`) | lundi, 8 h |
| Purge RGPD : statistiques > 25 mois, inscriptions non confirmées > 30 jours (`vox:prune`) | tous les jours, 3 h 15 |
| Nettoyage des jetons de réinitialisation et des tâches en échec | régulier |

Sans cron, le site reste fonctionnel (les articles programmés apparaissent à l'heure prévue), mais les e-mails restent en attente : dans ce cas, mettez `QUEUE_CONNECTION=sync`.

---

## E-mails et notifications

Configurez le SMTP dans `.env` (exemple Brevo) :

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre-identifiant
MAIL_PASSWORD=votre-cle-smtp
MAIL_FROM_ADDRESS="redaction@voxtechglobal.com"
MAIL_FROM_NAME="VOX TECH GLOBAL"
```

Puis **Admin › Diagnostic › Envoyer un e-mail de test**. Pensez aux enregistrements DNS **SPF, DKIM et DMARC** du domaine pour la délivrabilité.

E-mails envoyés automatiquement (modèle HTML aux couleurs du média + version texte) :

| Destinataire | Événement |
|---|---|
| Lecteur | Confirmation d'inscription newsletter (double opt-in), bienvenue, newsletter (désabonnement en un clic RFC 8058, mesure d'ouverture et de clics) |
| Lecteur | Accusé de réception : proposition de talent, demande annonceur, message de contact, commande en bibliothèque |
| Lecteur | Commentaire publié, réponse de la rédaction |
| Proposant | Suivi de sa proposition (en étude, retenue, refusée) |
| Rédaction | Nouveau commentaire à modérer, nouvelle proposition, nouveau message, nouvelle commande (avec *Répondre à* direct au lecteur) |
| Régie / commerciaux | Nouvelle demande annonceur, campagne arrivant à échéance |
| Auteur | Son article est en ligne |
| Équipe | Invitation, réinitialisation de mot de passe, alerte de connexion depuis un nouvel appareil, confirmation de changement de mot de passe |
| Administrateurs | Rapport d'audience hebdomadaire |

Les notifications à la rédaction se désactivent dans **Réglages › Notifications**. Un incident SMTP n'est jamais bloquant pour le visiteur (journalisé dans `storage/logs`).

---

## SEO et GEO 360°

### SEO technique
- Balises `title`, `description`, `canonical`, `robots`, `hreflang` (fr), pagination, fil d'Ariane.
- **Données structurées JSON-LD** : `NewsMediaOrganization`, `WebSite` + `SearchAction`, `BreadcrumbList`, `NewsArticle` (auteur, éditeur, dates, image, mots-clés, section), `FAQPage` pour les interviews, `ClaimReview` pour les fact-checks, `ProfilePage` + `Person` (E-E-A-T) pour les auteurs, `ItemList` pour les rubriques, `Book` / `PublicationIssue` / `Report` pour la bibliothèque.
- **Sitemaps** : index + pages, articles (avec images), **Google News** (48 h), rubriques / thématiques, publications.
- **Open Graph + Twitter Cards**, image de partage 1200×630 générée automatiquement pour chaque article.
- Flux **RSS** (global et par rubrique) et **JSON Feed**, manifeste PWA, `security.txt`.
- **IndexNow** (Bing, Yandex, Seznam, Naver) : notification à chaque publication — Bing alimente aussi ChatGPT Search et Copilot.
- Performance : aucune dépendance JS externe, police auto-hébergée préchargée, images WebP en `loading="lazy"`, cache applicatif, en-têtes de cache.
- Codes de vérification Google Search Console et Bing Webmaster Tools dans **Réglages › Référencement**.

### GEO — Generative Engine Optimization
- `robots.txt` qui autorise explicitement les robots des IA (GPTBot, OAI-SearchBot, ChatGPT-User, ClaudeBot, Claude-SearchBot, PerplexityBot, Perplexity-User, Google-Extended, Gemini, Applebot-Extended, Meta, Mistral, Amazon, DuckAssist, Cohere…) — réglable en un clic.
- **`/llms.txt`** (présentation structurée du média, rubriques et articles récents) et **`/llms-full.txt`** (texte intégral).
- **Version Markdown de chaque article** (`/article/{slug}.md`, déclarée en `<link rel="alternate" type="text/markdown">`) pour une citation fidèle.
- Signaux de confiance : biographies et profils `sameAs` des auteurs, charte éditoriale, distinction fait / opinion / annonce balisée, sources listées, dates de mise à jour.
- **Mesure** des passages des robots IA et du trafic venant de ChatGPT, Perplexity, Claude, Gemini, Copilot… (Statistiques › GEO).

---

## Statistiques

Mesure d'audience **interne, sans cookie tiers ni service externe** (respectueuse du RGPD : IP jamais stockée en clair, visiteurs anonymisés par empreinte quotidienne salée) : visites, visiteurs uniques, pages vues, durée de lecture, profondeur de défilement, taux de rebond, temps réel, sources (moteurs, réseaux sociaux, newsletter, assistants IA, accès direct), sites référents, campagnes UTM, appareils, pays, heures de lecture, articles les plus lus, lectures par rubrique, recherches internes (dont recherches sans résultat), passages des robots (moteurs et IA), performances newsletter et publicité. Export CSV.

Google Analytics 4 ou Matomo peuvent être ajoutés en complément si nécessaire.

---

## Tests et diagnostic

```bash
php artisan test          # 49 tests : site, SEO/GEO, formulaires, e-mails, admin, droits, tâches planifiées
php artisan vox:diagnostic   # diagnostic serveur, sécurité, e-mails, SEO, GEO, qualité éditoriale (score /100)
```

Le même diagnostic est disponible dans **Admin › Diagnostic & SEO**. Les tests sont exécutés automatiquement par GitHub Actions à chaque push (`.github/workflows/tests.yml`).

Sécurité : CSRF, limitation de débit (formulaires, connexion, recherche), pot de miel et délai minimal anti-robots, filtre anti-spam des commentaires, mots de passe robustes, comptes désactivables, alerte de nouvelle connexion, en-têtes de sécurité (HSTS en https, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy), fichiers de la bibliothèque hors du dossier public (liens signés), pages admin en `noindex`.

---

## Rôles et droits

| Action | Administrateur | Rédacteur en chef | Journaliste | Contributeur | Commercial |
|---|:-:|:-:|:-:|:-:|:-:|
| Publier un article | ✓ | ✓ | ✓ | – | – |
| Écrire un brouillon | ✓ | ✓ | ✓ | ✓ | – |
| Modifier les articles des autres | ✓ | ✓ | – | – | – |
| Modérer les commentaires | ✓ | ✓ | ✓ | – | – |
| Gérer la bibliothèque | ✓ | ✓ | – | – | – |
| Gérer les publicités | ✓ | – | – | – | ✓ |
| Envoyer la newsletter | ✓ | ✓ | – | – | – |
| Voir les statistiques | ✓ | ✓ | ✓ | – | ✓ |
| Gérer les utilisateurs | ✓ | – | – | – | – |
| Modifier les réglages | ✓ | – | – | – | – |

---

## Structure du projet

```
app/
  Http/Controllers/Site     Site public, formulaires, SEO (sitemaps, llms.txt, flux…), mesure d'audience
  Http/Controllers/Admin    Espace d'administration
  Http/Middleware           En-têtes de sécurité, mesure d'audience, maintenance, comptes actifs
  Mail, Jobs                E-mails transactionnels, newsletter
  Models                    Article, Rubrique, Theme, Author, Dossier, Publication, Campaign, AdSlot, Subscriber…
  Services                  Analytics, Diagnostic, IndexNow, MediaUploader, Notifier, OgImage, DemoData
  Support                   Seo (méta + JSON-LD), Settings, Chart (SVG), SiteCache
database/data               Contenus éditoriaux sourcés (seed.json, extra.json)
resources/views             Gabarits Blade : site, admin, e-mails, erreurs
routes/web.php              119 routes
routes/console.php          Commandes vox:* et planification
public/assets               CSS, JS, police Outfit, logos, Bootstrap
tests/Feature               Suite de tests
```

---

© VOX TECH GLOBAL — Abidjan, Côte d'Ivoire.
