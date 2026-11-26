<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Lead;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Models\User;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->scoped(Seo::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr');
        Paginator::defaultView('partials.pagination');

        if (str_starts_with((string) config('app.url'), 'https://') && ! $this->app->runningUnitTests()) {
            URL::forceScheme('https');
        }

        // Droits par rôle (matrice de la maquette)
        foreach (array_keys(User::PERMISSIONS) as $perm) {
            Gate::define($perm, fn (User $u) => $u->canDo($perm));
        }

        RateLimiter::for('forms', fn (Request $r) => [Limit::perMinute(6)->by($r->ip()), Limit::perDay(60)->by($r->ip())]);

        ResetPassword::toMailUsing(function (User $user, string $token) {
            $url = route('admin.password.reset', ['token' => $token, 'email' => $user->email]);
            $invite = ! $user->last_login_at;

            return (new MailMessage)
                ->subject($invite ? 'Votre invitation à l’espace rédaction de '.setting('name') : 'Réinitialisation de votre mot de passe')
                ->greeting('Bonjour '.$user->name.',')
                ->line($invite ? 'Vous êtes invité·e à rejoindre l’espace d’administration de '.setting('name').' avec le rôle « '.$user->role.' ».' : 'Vous avez demandé à réinitialiser le mot de passe de votre compte.')
                ->action($invite ? 'Choisir mon mot de passe' : 'Réinitialiser le mot de passe', $url)
                ->line('Ce lien expire dans '.config('auth.passwords.users.expire').' minutes.')
                ->salutation('La rédaction de '.setting('name'));
        });

        View::composer('layouts.site', function ($view) {
            $view->with('navRubriques', Cache::remember('vox.nav', 600, fn () => Rubrique::orderBy('position')->get()));
        });
        View::composer(['partials.ad', 'layouts.site', 'site.home', 'site.listing', 'site.article'], function ($view) {
            $view->with('runningAds', Cache::remember('vox.ads', 120, fn () => setting('ads_on') ? Campaign::running()->get()->keyBy('slot') : collect()));
        });
        View::composer('layouts.admin', function ($view) {
            $pending = Comment::where('status', 'en attente')->count();
            $newSubs = Submission::where('status', 'Nouvelle')->count();
            $unread = Lead::whereNull('read_at')->count();
            $sched = \App\Models\Article::where('status', 'programmé')->where('published_at', '>', now())->count();
            $expired = Campaign::where('active', true)->whereDate('ends_on', '<', now())->count();
            $notifs = array_values(array_filter([
                $pending ? [$pending.' commentaire(s) à modérer', route('admin.comments')] : null,
                $newSubs ? [$newSubs.' nouvelle(s) proposition(s) de talent', route('admin.inbox')] : null,
                $unread ? [$unread.' message(s) non lu(s)', route('admin.inbox', ['onglet' => 'messages'])] : null,
                $sched ? [$sched.' article(s) programmé(s)', route('admin.calendar')] : null,
                $expired ? [$expired.' campagne(s) arrivée(s) à échéance', route('admin.ads')] : null,
            ]));
            $view->with(['pendingComments' => $pending, 'inboxCount' => $newSubs + $unread, 'notifs' => $notifs]);
        });
    }
}
