<?php

namespace Tests\Feature;

use App\Mail\VoxMail;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Lead;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Submission;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FormsAndMailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_newsletter_double_opt_in_welcome_and_one_click_unsubscribe(): void
    {
        $this->from(route('newsletter'))->post(route('newsletter.subscribe'), ['email' => 'Lectrice@Exemple.ci', 'source' => 'Test'])
            ->assertRedirect(route('newsletter'))->assertSessionHas('nl_ok');
        $sub = Subscriber::where('email', 'lectrice@exemple.ci')->firstOrFail();
        $this->assertNull($sub->confirmed_at);
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('lectrice@exemple.ci') && str_contains($m->mailSubject, 'Confirmez'));

        $this->get(route('newsletter.confirm', $sub->token))->assertRedirect(route('newsletter'));
        $this->assertTrue($sub->fresh()->isActive());
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('lectrice@exemple.ci') && str_contains($m->mailSubject, 'Bienvenue'));

        // Désinscription en un clic (RFC 8058 : POST sans session ni CSRF)
        $this->post(route('newsletter.unsubscribe', $sub->token), ['List-Unsubscribe' => 'One-Click'])->assertOk();
        $this->assertFalse($sub->fresh()->isActive());
    }

    public function test_newsletter_rejects_invalid_email(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'pas-un-email'])->assertSessionHasErrors('email', null, 'newsletter');
        Mail::assertNothingSent();
    }

    public function test_honeypot_silently_drops_bots(): void
    {
        $this->post(route('contact.store'), ['name' => 'Bot', 'email' => 'bot@spam.io', 'subject' => 'Promo', 'message' => 'Achetez maintenant !!!', 'website' => 'http://spam.io'])
            ->assertRedirect();
        $this->assertDatabaseMissing('leads', ['email' => 'bot@spam.io']);
        Mail::assertNothingSent();
    }

    public function test_contact_form_stores_message_and_notifies_both_sides(): void
    {
        $this->post(route('contact.store'), ['name' => 'Awa Traoré', 'email' => 'awa@exemple.ci', 'subject' => 'Partenariat école', 'message' => 'Bonjour, nous souhaitons présenter nos étudiants.'])
            ->assertRedirect(route('contact').'#formulaire')->assertSessionHas('contact_sent');
        $this->assertDatabaseHas('leads', ['email' => 'awa@exemple.ci', 'kind' => 'Contact']);
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('awa@exemple.ci'));
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo(setting('email_redaction')) && $m->replyToEmail === 'awa@exemple.ci');
    }

    public function test_talent_proposal_and_advertiser_request(): void
    {
        $this->post(route('proposer.store'), [
            'kind' => Submission::KINDS[0], 'name' => 'Koffi Yao', 'email' => 'koffi@exemple.ci', 'project' => 'Drone agricole solaire',
            'country' => 'Côte d’Ivoire', 'category' => Rubrique::first()->name, 'description' => str_repeat('Un drone qui cartographie les parcelles. ', 3), 'ok' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('talent_sent');
        $this->assertDatabaseHas('submissions', ['project' => 'Drone agricole solaire', 'status' => 'Nouvelle']);

        $this->post(route('annoncer.store'), ['company' => 'Orange CI', 'name' => 'Marie', 'email' => 'marie@orange.ci', 'format' => 'Billboard', 'budget' => '1 M FCFA', 'message' => 'Campagne de rentrée en septembre.'])
            ->assertSessionHasNoErrors()->assertSessionHas('ads_sent');
        $this->assertDatabaseHas('leads', ['company' => 'Orange CI', 'kind' => 'Annonceur']);
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo(setting('email_partners')));
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('koffi@exemple.ci'));
    }

    public function test_proposal_requires_consent_and_description(): void
    {
        $this->post(route('proposer.store'), ['kind' => Submission::KINDS[0], 'name' => 'X', 'email' => 'x@exemple.ci', 'project' => 'P', 'category' => Rubrique::first()->name, 'description' => 'court'])
            ->assertSessionHasErrors(['description', 'ok'], null, 'talent');
    }

    public function test_comment_is_moderated_and_spam_is_filtered(): void
    {
        $a = Article::published()->firstOrFail();
        $this->post(route('comment.store', $a), ['name' => 'Ibrahim', 'email' => 'ibra@exemple.ci', 'text' => 'Bravo pour ce portrait inspirant !'])
            ->assertRedirect($a->url().'#commentaires');
        $this->assertSame('en attente', Comment::where('email', 'ibra@exemple.ci')->value('status'));
        Mail::assertSent(VoxMail::class, fn ($m) => str_contains($m->mailSubject, 'Nouveau commentaire'));

        $this->post(route('comment.store', $a), ['name' => 'Spam', 'email' => 's@spam.io', 'text' => 'Gagnez de l’argent https://a.io https://b.io https://c.io']);
        $this->assertSame('spam', Comment::where('email', 's@spam.io')->value('status'));
    }

    public function test_comments_can_be_disabled(): void
    {
        $this->setSetting(['comments_on' => false]);
        $this->post(route('comment.store', Article::published()->first()), ['name' => 'A', 'email' => 'a@b.ci', 'text' => 'Bonjour'])->assertNotFound();
    }

    public function test_paid_publication_creates_an_order(): void
    {
        $p = Publication::where('price', '>', 0)->first() ?? tap(Publication::first())->update(['price' => 5000]);
        $this->post(route('publication.get', $p), ['name' => 'Client', 'email' => 'client@exemple.ci', 'phone' => '0700000000'])->assertSessionHas('pub_ok');
        $this->assertDatabaseHas('leads', ['kind' => 'Commande', 'email' => 'client@exemple.ci']);
        Mail::assertSent(VoxMail::class, fn ($m) => $m->hasTo('client@exemple.ci'));
    }

    public function test_forms_are_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('newsletter.subscribe'), ['email' => "l$i@exemple.ci"]);
        }
        $this->post(route('newsletter.subscribe'), ['email' => 'l7@exemple.ci'])->assertStatus(429);
    }
}
