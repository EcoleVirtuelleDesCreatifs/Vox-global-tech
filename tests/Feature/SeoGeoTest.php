<?php

namespace Tests\Feature;

use App\Models\Article;
use Tests\TestCase;

class SeoGeoTest extends TestCase
{
    public function test_robots_txt_allows_ai_crawlers_and_declares_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false)
            ->assertSee('GPTBot')->assertSee('ClaudeBot')->assertSee('PerplexityBot')
            ->assertSee('Disallow: /admin', false);
    }

    public function test_ai_crawlers_can_be_blocked_from_settings(): void
    {
        $this->setSetting(['ai_crawlers' => false]);
        $body = $this->get('/robots.txt')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/User-agent: GPTBot\s+Disallow: \//', $body);
    }

    public function test_sitemaps_are_valid_xml(): void
    {
        $index = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $this->assertNotFalse($index);
        foreach (['pages', 'articles', 'news', 'taxonomies', 'publications'] as $part) {
            $xml = $this->get("/sitemaps/$part.xml")->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
            $this->assertNotFalse(simplexml_load_string($xml), "Sitemap $part invalide");
        }
        $articles = $this->get('/sitemaps/articles.xml')->getContent();
        $this->assertStringContainsString(Article::published()->first()->url(), $articles);
        $this->assertStringNotContainsString(Article::where('status', 'brouillon')->first()->slug, $articles);
    }

    public function test_feeds_are_valid(): void
    {
        $rss = simplexml_load_string($this->get('/feed')->assertOk()->getContent());
        $this->assertNotFalse($rss);
        $this->assertGreaterThan(5, count($rss->channel->item));
        $this->get('/feed.json')->assertOk()->assertJsonStructure(['version', 'title', 'items' => [['id', 'url', 'title']]]);
    }

    public function test_llms_txt_for_generative_engines(): void
    {
        $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')->assertSee('# '.setting('name'), false);
        $this->get('/llms-full.txt')->assertOk();
    }

    public function test_share_image_manifest_and_security_txt(): void
    {
        $a = Article::published()->firstOrFail();
        $res = $this->get(route('og.article', $a))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame([1200, 630], array_slice(getimagesize($res->baseResponse->getFile()->getPathname()), 0, 2));
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonStructure(['name', 'icons']);
        $this->get('/.well-known/security.txt')->assertOk()->assertSee('Contact:');
    }

    public function test_article_page_seo_signals(): void
    {
        $a = Article::published()->firstOrFail();
        $html = $this->get($a->url())->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<link rel="canonical" href="'.$a->url().'"', $html);
        $this->assertStringContainsString('type="text/markdown"', $html);
        $this->assertStringContainsString('BreadcrumbList', $html);
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $json) {
            $this->assertIsArray(json_decode($json, true), 'JSON-LD invalide');
        }
    }

    public function test_admin_and_private_pages_are_noindex(): void
    {
        $this->get('/admin/connexion')->assertOk()->assertSee('noindex', false);
    }
}
