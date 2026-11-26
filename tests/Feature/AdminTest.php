<?php

namespace Tests\Feature;

use App\Jobs\SendNewsletter;
use App\Mail\NewsletterIssue;
use App\Mail\VoxMail;
use App\Models\Article;
use App\Models\Campaign;
use App\Models\Comment;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminTest extends TestCase
{
    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/reglages')->assertRedirect(route('admin.login'));
    }

    public function test_login_logout_and_bad_credentials(): void
    {
        $this->post(route('admin.login.post'), ['email' => 'admin@voxtechglobal.com', 'password' => 'mauvais'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('admin.login.post'), ['email' => 'admin@voxtechglobal.com', 'password' => 'VoxTech@2026'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin());
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_deactivated_accounts_cannot_log_in(): void
    {
        $u = $this->userWithRole('Journaliste');
        $u->update(['active' => false, 'password' => 'MotDePasse2026']);
        $this->post(route('admin.login.post'), ['email' => $u->email, 'password' => 'MotDePasse2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_from_new_device_sends_security_alert(): void
    {
        Mail::fake();
        $cred = ['email' => 'admin@voxtechglobal.com', 'password' => 'VoxTech@2026'];
        $this->withHeader('User-Agent', 'Navigateur-A')->post(route('admin.login.post'), $cred);
        $this->post(route('admin.logout'));
        Mail::fake(); // on repart d'une boîte vide : l'appareil A est désormais connu
        $this->withHeader('User-Agent', 'Navigateur-A')->post(route('admin.login.post'), $cred);
        $this->post(route('admin.logout'));
        Mail::assertNothingSent();
        $this->withHeader('User-Agent', 'Navigateur-B')->post(route('admin.login.post'), $cred);
        Mail::assertSent(VoxMail::class, fn ($m) => str_contains($m->mailSubject, 'Nouvelle connexion'));
    }

    public function test_every_admin_page_renders_for_administrator(): void
    {
        $this->actingAs($this->admin());
        $pages = ['/admin', '/admin/statistiques', '/admin/journal', '/admin/diagnostic', '/admin/articles', '/admin/articles/nouveau', '/admin/calendrier',
            '/admin/commentaires', '/admin/mediatheque', '/admin/rubriques', '/admin/bibliotheque', '/admin/newsletter', '/admin/newsletter?nouvelle=1',
            '/admin/abonnes', '/admin/publicites', '/admin/boite', '/admin/boite?onglet=messages', '/admin/utilisateurs', '/admin/profil', '/admin/reglages'];
        foreach (['seo', 'social', 'moder', 'mail', 'data'] as $t) {
            $pages[] = '/admin/reglages?onglet='.$t;
        }
        foreach ($pages as $p) {
            $this->get($p)->assertOk();
        }
        $this->get(route('admin.articles.edit', Article::first()->id))->assertOk();
        $this->getJson('/admin/recherche?q=ia')->assertOk()->assertJsonIsArray();
        $this->get('/admin/statistiques/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->get('/admin/abonnes/export')->assertOk();
    }

    public function test_deactivated_session_is_logged_out(): void
    {
        $this->actingAs($this->userWithRole('Contributeur'))->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_role_permissions_are_enforced(): void
    {
        $this->actingAs(tap($this->userWithRole('Contributeur'))->update(['active' => true]));
        $this->get('/admin')->assertOk();
        foreach (['/admin/reglages', '/admin/utilisateurs', '/admin/publicites', '/admin/newsletter', '/admin/statistiques', '/admin/commentaires'] as $p) {
            $this->get($p)->assertForbidden();
        }
        $this->actingAs($this->userWithRole('Commercial'));
        $this->get('/admin/publicites')->assertOk();
        $this->get('/admin/reglages')->assertForbidden();
    }

    public function test_contributor_cannot_publish(): void
    {
        $this->actingAs(tap($this->userWithRole('Contributeur'))->update(['active' => true]));
        $this->post(route('admin.articles.store'), $this->articlePayload(['action' => 'publish']))->assertRedirect();
        $this->assertSame('brouillon', Article::where('title', 'Un nouveau talent ivoirien de la robotique')->value('status'));
    }

    public function test_create_publish_and_view_article(): void
    {
        $this->actingAs($this->admin());
        $res = $this->post(route('admin.articles.store'), $this->articlePayload(['action' => 'publish', 'date' => now()->subMinute()->format('Y-m-d'), 'time' => now()->subMinute()->format('H:i')]));
        $a = Article::where('title', 'Un nouveau talent ivoirien de la robotique')->firstOrFail();
        $res->assertRedirect(route('admin.articles.edit', $a->id));
        $this->assertTrue($a->isLive());
        $this->assertSame('un-nouveau-talent-ivoirien-de-la-robotique', $a->slug);
        auth()->logout();
        $this->get($a->url())->assertOk()->assertSee('Un nouveau talent ivoirien de la robotique');
        $this->assertDatabaseHas('activity_logs', ['action' => 'a publié « Un nouveau talent ivoirien de la robotique »']);
    }

    public function test_scheduled_article_goes_live_with_the_scheduler(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $when = now()->addDay();
        $this->post(route('admin.articles.store'), $this->articlePayload(['action' => 'publish', 'date' => $when->format('Y-m-d'), 'time' => $when->format('H:i'), 'author_id' => 3]));
        $a = Article::where('title', 'Un nouveau talent ivoirien de la robotique')->firstOrFail();
        $this->assertSame('programmé', $a->status);
        auth()->logout();
        $this->get($a->url())->assertNotFound();

        $this->travelTo($when->copy()->addMinutes(2));
        $this->artisan('vox:publish-scheduled')->assertSuccessful();
        $this->assertSame('publié', $a->fresh()->status);
        $this->get($a->url())->assertOk();
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo(User::where('author_id', 3)->value('email')) && str_contains($m->mailSubject, 'en ligne'));
    }

    public function test_article_validation_errors(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.articles.store'), ['title' => '', 'type' => 'article'])->assertSessionHasErrors(['title', 'chapo', 'text']);
    }

    public function test_comment_moderation_notifies_reader(): void
    {
        Mail::fake();
        $c = Comment::create(['article_id' => Article::published()->first()->id, 'name' => 'Lecteur', 'email' => 'lecteur@exemple.ci', 'text' => 'Super article', 'status' => 'en attente']);
        $this->actingAs($this->admin())->post(route('admin.comments.moderate', $c), ['status' => 'approuvé'])->assertRedirect();
        $this->assertSame('approuvé', $c->fresh()->status);
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('lecteur@exemple.ci'));
    }

    public function test_submission_status_change_notifies_proposer(): void
    {
        Mail::fake();
        $s = Submission::firstOrFail();
        $s->update(['status' => 'Nouvelle']);
        $this->actingAs($this->admin())->post(route('admin.inbox.status', $s), ['status' => 'Retenue'])->assertRedirect();
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo($s->email) && str_contains($m->mailSubject, 'Retenue'));
    }

    public function test_newsletter_send_reaches_confirmed_subscribers_only(): void
    {
        Mail::fake();
        Subscriber::create(['email' => 'pending@exemple.ci', 'source' => 'Test']);
        $active = Subscriber::active()->count();
        $ids = Article::published()->limit(3)->pluck('id')->all();
        $this->actingAs($this->admin())->post(route('admin.newsletter.send'), ['subject' => 'La lettre des talents', 'intro' => 'Bonjour', 'articles' => $ids, 'mode' => 'send'])
            ->assertRedirect(route('admin.newsletter'));
        Mail::assertSent(NewsletterIssue::class, $active);
        Mail::assertNotSent(NewsletterIssue::class, fn ($m) => $m->hasTo('pending@exemple.ci'));
        $this->assertDatabaseHas('newsletters', ['subject' => 'La lettre des talents', 'status' => 'Envoyée', 'recipients' => $active]);
    }

    public function test_newsletter_test_send(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->post(route('admin.newsletter.send'), ['subject' => 'Test', 'articles' => [Article::published()->first()->id], 'mode' => 'test'])->assertSessionHas('ok');
        Mail::assertSent(NewsletterIssue::class, fn ($m) => $m->hasTo('admin@voxtechglobal.com'));
    }

    public function test_invite_user_sends_invitation(): void
    {
        Notification::fake();
        $this->actingAs($this->admin())->post(route('admin.users.invite'), ['name' => 'Nouvelle Journaliste', 'email' => 'nj@voxtechglobal.com', 'role' => 'Journaliste', 'author' => '1'])->assertSessionHas('ok');
        $u = User::where('email', 'nj@voxtechglobal.com')->firstOrFail();
        $this->assertNotNull($u->author_id);
        Notification::assertSentTo($u, ResetPassword::class);
    }

    public function test_admin_cannot_demote_or_delete_self(): void
    {
        $me = $this->admin();
        $this->actingAs($me)->put(route('admin.users.update', $me), ['role' => 'Journaliste'])->assertSessionHas('err');
        $this->delete(route('admin.users.destroy', $me))->assertSessionHas('err');
        $this->assertSame('Administrateur', $me->fresh()->role);
    }

    public function test_password_change_requires_current_password_and_sends_alert(): void
    {
        Mail::fake();
        $me = $this->admin();
        $this->actingAs($me)->put(route('admin.profile.update'), ['name' => $me->name, 'email' => $me->email, 'current_password' => 'faux', 'password' => 'NouveauMotDePasse2026', 'password_confirmation' => 'NouveauMotDePasse2026'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.profile.update'), ['name' => $me->name, 'email' => $me->email, 'current_password' => 'VoxTech@2026', 'password' => 'NouveauMotDePasse2026', 'password_confirmation' => 'NouveauMotDePasse2026'])
            ->assertSessionHas('ok');
        Mail::assertSent(VoxMail::class, fn ($m) => str_contains($m->mailSubject, 'mot de passe'));
    }

    public function test_settings_are_saved_and_applied(): void
    {
        $this->actingAs($this->admin())->post(route('admin.settings.update'), ['tab' => 'seo', 'seo_title' => 'VOX TECH GLOBAL, média des talents', 'seo_description' => 'Le média des talents tech africains.', 'ai_crawlers' => '1', 'indexnow' => '1', 'google_verification' => 'abc123'])
            ->assertRedirect(route('admin.settings', ['onglet' => 'seo']));
        auth()->logout();
        $this->get('/')->assertSee('google-site-verification', false)->assertSee('abc123', false);
    }

    public function test_campaign_lifecycle_and_alerts(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $slot = \App\Models\AdSlot::first();
        $this->post(route('admin.ads.store'), ['advertiser' => 'Wave', 'slot' => $slot->code, 'title' => 'Envoyez de l’argent sans frais', 'cta' => 'Télécharger', 'url' => 'https://wave.com',
            'budget' => 500000, 'starts_on' => now()->format('Y-m-d'), 'ends_on' => now()->addDays(2)->format('Y-m-d')])->assertSessionHas('ok');
        $c = Campaign::where('advertiser', 'Wave')->firstOrFail();
        $this->assertSame(1, Campaign::where('slot', $slot->code)->where('active', true)->count());

        $this->get(route('campaign.click', $c))->assertRedirect();
        $this->assertSame(1, $c->fresh()->clicks);

        $this->artisan('vox:campaigns')->assertSuccessful();
        Mail::assertSent(VoxMail::class, fn ($m) => str_contains($m->mailSubject, 'Wave'));
        $this->assertNotNull($c->fresh()->expiry_notified_at);

        $this->travel(4)->days();
        $this->artisan('vox:campaigns')->assertSuccessful();
        $this->assertFalse($c->fresh()->active);
    }

    public function test_weekly_report_and_maintenance_commands(): void
    {
        Mail::fake();
        $this->artisan('vox:weekly-report')->assertSuccessful();
        Mail::assertSent(VoxMail::class, fn ($m) => str_contains($m->mailSubject, 'hebdomadaire'));
        $this->artisan('vox:prune')->assertSuccessful();
        $this->artisan('vox:diagnostic')->assertSuccessful();
    }

    public function test_diagnostic_page_and_test_mail(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->get(route('admin.diagnostic'))->assertOk()->assertSee('GEO');
        $this->post(route('admin.diagnostic.mail'))->assertSessionHas('ok');
        Mail::assertSent(VoxMail::class);
    }

    protected function articlePayload(array $over = []): array
    {
        return $over + [
            'title' => 'Un nouveau talent ivoirien de la robotique',
            'chapo' => 'À 22 ans, elle construit des robots éducatifs à partir de matériaux recyclés.',
            'text' => "## Une passion précoce\n\nTout a commencé dans un atelier de Yopougon, avec des moteurs récupérés.\n\n> La technologie doit être accessible — Aminata",
            'type' => 'portrait', 'nature' => 'Fait', 'rubrique_id' => Rubrique::first()->id, 'region' => 'Afrique', 'country' => 'Côte d’Ivoire',
            'date' => now()->format('Y-m-d'), 'time' => '08:00', 'tags' => 'robotique, éducation', 'action' => 'save',
        ];
    }
}
