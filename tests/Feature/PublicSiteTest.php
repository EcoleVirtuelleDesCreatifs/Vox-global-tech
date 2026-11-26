<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Dossier;
use App\Models\Publication;
use App\Models\Rubrique;
use App\Models\Theme;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_home_page_renders_with_structured_data(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('NewsMediaOrganization', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:image"', false)
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_every_listing_page_renders(): void
    {
        foreach (Rubrique::all() as $r) {
            $this->get(route('rubrique', $r))->assertOk()->assertSee($r->name);
        }
        foreach (Theme::all() as $t) {
            $this->get(route('theme', $t))->assertOk();
        }
        foreach (Author::all() as $a) {
            $this->get(route('auteur', $a))->assertOk()->assertSee('ProfilePage', false);
        }
        $this->get(route('dossiers'))->assertOk();
        foreach (Dossier::all() as $d) {
            $this->get(route('dossier', $d))->assertOk();
        }
    }

    public function test_static_pages_render(): void
    {
        foreach (['bibliotheque', 'newsletter', 'proposer', 'apropos', 'charte', 'annoncer', 'contact', 'mentions', 'confidentialite'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (Publication::all() as $p) {
            $this->get($p->url())->assertOk()->assertSee($p->title);
        }
    }

    public function test_published_articles_render_with_news_schema_and_markdown(): void
    {
        $articles = Article::published()->get();
        $this->assertGreaterThan(10, $articles->count());
        foreach ($articles as $a) {
            $this->get($a->url())->assertOk()->assertSee('NewsArticle', false)->assertSee(e($a->title), false);
        }
        $a = $articles->first();
        $this->get(route('article.md', $a))->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')->assertSee('# '.$a->title, false);
    }

    public function test_drafts_and_unknown_articles_are_not_public(): void
    {
        $draft = Article::where('status', 'brouillon')->firstOrFail();
        $this->get($draft->url())->assertNotFound();
        $this->get('/article/cet-article-n-existe-pas')->assertNotFound();
        $this->actingAs($this->admin())->get($draft->url())->assertOk()->assertSee('Aperçu');
    }

    public function test_search_page_and_instant_search_api(): void
    {
        $this->get(route('recherche', ['q' => 'startup']))->assertOk();
        $this->getJson(route('api.search', ['q' => 'IA']))->assertOk()->assertJsonStructure(['results', 'all']);
    }

    public function test_maintenance_mode_hides_site_from_readers_only(): void
    {
        $this->setSetting(['maintenance' => true]);
        $this->get('/')->assertStatus(503)->assertHeader('Retry-After', '3600');
        $this->get('/robots.txt')->assertOk();
        $this->actingAs($this->admin())->get('/')->assertOk();
    }

    public function test_page_views_are_recorded_for_statistics(): void
    {
        $a = Article::published()->firstOrFail();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0) Chrome/130')->get($a->url())->assertOk();
        $this->assertDatabaseHas('page_views', ['article_id' => $a->id]);
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; GPTBot/1.2; +https://openai.com/gptbot)')->get($a->url())->assertOk();
        $this->assertDatabaseHas('bot_visits', ['bot' => 'GPTBot']);
    }

    public function test_custom_error_page(): void
    {
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('404');
    }
}
