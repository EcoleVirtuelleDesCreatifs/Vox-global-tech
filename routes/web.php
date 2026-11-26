<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO / GEO : sitemaps, robots, llms.txt, flux, images de partage
|--------------------------------------------------------------------------
*/
Route::controller(Site\SeoController::class)->group(function () {
    Route::get('/robots.txt', 'robots')->name('robots');
    Route::get('/sitemap.xml', 'sitemapIndex')->name('sitemap');
    Route::get('/sitemaps/{part}.xml', 'sitemap')->whereIn('part', ['pages', 'articles', 'news', 'taxonomies', 'publications'])->name('sitemap.part');
    Route::get('/llms.txt', 'llms')->name('llms');
    Route::get('/llms-full.txt', 'llmsFull')->name('llms.full');
    Route::get('/feed', 'rss')->name('feed');
    Route::get('/feed.json', 'jsonFeed')->name('feed.json');
    Route::get('/feed/{rubrique}', 'rss')->name('feed.rubrique');
    Route::get('/og/article/{article}.png', 'ogArticle')->name('og.article');
    Route::get('/manifest.webmanifest', 'manifest')->name('manifest');
    Route::get('/.well-known/security.txt', 'security')->name('security.txt');
    Route::get('/{key}.txt', 'indexNowKey')->where('key', '[a-z0-9]{32}')->name('indexnow.key');
});

/*
|--------------------------------------------------------------------------
| Mesure d'audience, suivi newsletter et publicités
|--------------------------------------------------------------------------
*/
Route::controller(Site\TrackingController::class)->group(function () {
    Route::post('/collect', 'collect')->middleware('throttle:120,1')->name('collect');
    Route::get('/nl/o/{token}.gif', 'open')->name('nl.open');
    Route::get('/nl/c/{token}', 'click')->name('nl.click');
    Route::get('/pub/{campaign}/clic', 'campaign')->name('campaign.click');
});

/*
|--------------------------------------------------------------------------
| Site public
|--------------------------------------------------------------------------
*/
Route::middleware(['maintenance', 'track'])->group(function () {
    Route::get('/', [Site\HomeController::class, 'index'])->name('home');

    Route::controller(Site\ListingController::class)->group(function () {
        Route::get('/rubrique/{rubrique}', 'rubrique')->name('rubrique');
        Route::get('/theme/{theme}', 'theme')->name('theme');
        Route::get('/auteur/{author}', 'author')->name('auteur');
        Route::get('/dossiers', 'dossiers')->name('dossiers');
        Route::get('/dossier/{dossier}', 'dossier')->name('dossier');
        Route::get('/recherche', 'search')->name('recherche');
    });

    Route::get('/article/{slug}', [Site\ArticleController::class, 'show'])->where('slug', '[A-Za-z0-9\-]+')->name('article');

    Route::controller(Site\PageController::class)->group(function () {
        Route::get('/bibliotheque', 'library')->name('bibliotheque');
        Route::get('/publication/{publication}', 'publication')->name('publication');
        Route::get('/newsletter', 'newsletter')->name('newsletter');
        Route::get('/proposer', 'proposer')->name('proposer');
        Route::get('/a-propos', 'about')->name('apropos');
        Route::get('/charte-editoriale', 'about')->name('charte');
        Route::get('/annoncer', 'advertise')->name('annoncer');
        Route::get('/contact', 'contact')->name('contact');
        Route::get('/mentions-legales', 'legal')->name('mentions');
        Route::get('/confidentialite', 'privacy')->name('confidentialite');
    });
});

Route::get('/article/{article}.md', [Site\ArticleController::class, 'markdown'])->name('article.md');
Route::get('/api/recherche', [Site\ListingController::class, 'quickSearch'])->middleware('throttle:60,1')->name('api.search');

Route::middleware('throttle:forms')->controller(Site\FormController::class)->group(function () {
    Route::post('/article/{article}/commentaires', 'comment')->name('comment.store');
    Route::post('/newsletter', 'subscribe')->name('newsletter.subscribe');
    Route::post('/proposer', 'proposer')->name('proposer.store');
    Route::post('/annoncer', 'advertise')->name('annoncer.store');
    Route::post('/contact', 'contact')->name('contact.store');
    Route::post('/publication/{publication}/obtenir', 'publication')->name('publication.get');
});
Route::get('/newsletter/confirmer/{token}', [Site\FormController::class, 'confirm'])->name('newsletter.confirm');
Route::match(['get', 'post'], '/newsletter/desabonner/{token}', [Site\FormController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/publication/{publication}/telecharger', [Site\FormController::class, 'download'])->middleware('signed')->name('publication.download');

/*
|--------------------------------------------------------------------------
| Espace d'administration
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->controller(Admin\AuthController::class)->group(function () {
        Route::get('/connexion', 'login')->name('login');
        Route::post('/connexion', 'authenticate')->middleware('throttle:6,1')->name('login.post');
        Route::get('/mot-de-passe-oublie', 'forgot')->name('password.request');
        Route::post('/mot-de-passe-oublie', 'sendReset')->middleware('throttle:4,1')->name('password.email');
        Route::get('/reinitialiser/{token}', 'reset')->name('password.reset');
        Route::post('/reinitialiser', 'updatePassword')->name('password.update');
    });
    Route::post('/deconnexion', [Admin\AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'staff'])->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/statistiques', [Admin\DashboardController::class, 'stats'])->middleware('can:stats')->name('stats');
        Route::get('/statistiques/export', [Admin\DashboardController::class, 'export'])->middleware('can:stats')->name('stats.export');
        Route::get('/journal', [Admin\DashboardController::class, 'journal'])->name('journal');
        Route::get('/recherche', [Admin\DashboardController::class, 'search'])->name('search');
        Route::get('/diagnostic', [Admin\DiagnosticController::class, 'index'])->middleware('can:settings')->name('diagnostic');
        Route::post('/diagnostic/mail', [Admin\DiagnosticController::class, 'mail'])->middleware('can:settings')->name('diagnostic.mail');
        Route::post('/diagnostic/cache', [Admin\DiagnosticController::class, 'cache'])->middleware('can:settings')->name('diagnostic.cache');

        Route::get('/articles', [Admin\ArticleController::class, 'index'])->name('articles');
        Route::get('/articles/nouveau', [Admin\ArticleController::class, 'create'])->middleware('can:draft')->name('articles.create');
        Route::post('/articles', [Admin\ArticleController::class, 'store'])->middleware('can:draft')->name('articles.store');
        Route::get('/articles/{article:id}', [Admin\ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('/articles/{article:id}', [Admin\ArticleController::class, 'update'])->name('articles.update');
        Route::delete('/articles/{article:id}', [Admin\ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::post('/articles/{article:id}/statut', [Admin\ArticleController::class, 'toggle'])->middleware('can:publish')->name('articles.toggle');
        Route::post('/articles-groupes', [Admin\ArticleController::class, 'bulk'])->name('articles.bulk');
        Route::get('/calendrier', [Admin\ArticleController::class, 'calendar'])->name('calendar');

        Route::middleware('can:moderate')->group(function () {
            Route::get('/commentaires', [Admin\CommentController::class, 'index'])->name('comments');
            Route::post('/commentaires/{comment}/statut', [Admin\CommentController::class, 'moderate'])->name('comments.moderate');
            Route::post('/commentaires/{comment}/reponse', [Admin\CommentController::class, 'reply'])->name('comments.reply');
            Route::delete('/commentaires/{comment}', [Admin\CommentController::class, 'destroy'])->name('comments.destroy');
        });

        Route::get('/mediatheque', [Admin\MediaController::class, 'index'])->name('media');
        Route::post('/mediatheque', [Admin\MediaController::class, 'store'])->middleware('can:draft')->name('media.store');
        Route::put('/mediatheque/{media}', [Admin\MediaController::class, 'update'])->middleware('can:draft')->name('media.update');
        Route::delete('/mediatheque/{media}', [Admin\MediaController::class, 'destroy'])->middleware('can:edit-others')->name('media.destroy');

        Route::middleware('can:edit-others')->controller(Admin\TaxonomyController::class)->group(function () {
            Route::get('/rubriques', 'index')->name('rubriques');
            Route::post('/rubriques', 'store')->name('rubriques.store');
            Route::put('/rubriques/{rubrique:id}', 'update')->name('rubriques.update');
            Route::delete('/rubriques/{rubrique:id}', 'destroy')->name('rubriques.destroy');
            Route::post('/thematiques', 'storeTheme')->name('themes.store');
            Route::delete('/thematiques/{theme:id}', 'destroyTheme')->name('themes.destroy');
            Route::post('/dossiers', 'storeDossier')->name('dossiers.store');
            Route::put('/dossiers/{dossier:id}', 'updateDossier')->name('dossiers.update');
            Route::delete('/dossiers/{dossier:id}', 'destroyDossier')->name('dossiers.destroy');
            Route::post('/opportunites', 'storeOpportunity')->name('opps.store');
            Route::delete('/opportunites/{opportunity}', 'destroyOpportunity')->name('opps.destroy');
        });

        Route::middleware('can:library')->controller(Admin\LibraryController::class)->group(function () {
            Route::get('/bibliotheque', 'index')->name('library');
            Route::post('/bibliotheque', 'store')->name('library.store');
            Route::put('/bibliotheque/{publication:id}', 'update')->name('library.update');
            Route::delete('/bibliotheque/{publication:id}', 'destroy')->name('library.destroy');
        });

        Route::middleware('can:newsletter')->controller(Admin\NewsletterController::class)->group(function () {
            Route::get('/newsletter', 'index')->name('newsletter');
            Route::post('/newsletter', 'send')->name('newsletter.send');
            Route::get('/abonnes', 'subscribers')->name('subscribers');
            Route::get('/abonnes/export', 'export')->name('subscribers.export');
            Route::post('/abonnes', 'addSubscriber')->name('subscribers.store');
            Route::delete('/abonnes/{subscriber}', 'destroySubscriber')->name('subscribers.destroy');
        });

        Route::middleware('can:ads')->controller(Admin\AdController::class)->group(function () {
            Route::get('/publicites', 'index')->name('ads');
            Route::post('/publicites', 'store')->name('ads.store');
            Route::post('/publicites/{campaign}/etat', 'toggle')->name('ads.toggle');
            Route::delete('/publicites/{campaign}', 'destroy')->name('ads.destroy');
        });

        Route::controller(Admin\InboxController::class)->group(function () {
            Route::get('/boite', 'index')->name('inbox');
            Route::post('/boite/propositions/{submission}', 'status')->name('inbox.status');
            Route::post('/boite/messages/{lead}/lu', 'read')->name('inbox.read');
            Route::delete('/boite/messages/{lead}', 'destroyLead')->name('inbox.lead.destroy');
            Route::delete('/boite/propositions/{submission}', 'destroySubmission')->name('inbox.submission.destroy');
        });

        Route::middleware('can:users')->controller(Admin\UserController::class)->group(function () {
            Route::get('/utilisateurs', 'index')->name('users');
            Route::post('/utilisateurs', 'invite')->name('users.invite');
            Route::put('/utilisateurs/{user}', 'update')->name('users.update');
            Route::delete('/utilisateurs/{user}', 'destroy')->name('users.destroy');
        });

        Route::get('/profil', [Admin\UserController::class, 'profile'])->name('profile');
        Route::put('/profil', [Admin\UserController::class, 'updateProfile'])->name('profile.update');

        Route::middleware('can:settings')->controller(Admin\SettingsController::class)->group(function () {
            Route::get('/reglages', 'index')->name('settings');
            Route::post('/reglages', 'update')->name('settings.update');
            Route::post('/reglages/statistiques-demo', 'purgeDemo')->name('settings.purge');
        });
    });
});
