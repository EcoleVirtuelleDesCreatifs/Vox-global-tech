<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Author;
use App\Models\Publication;
use Illuminate\Support\Str;

/**
 * Méta-données SEO / GEO d'une page : titre, description, canonical, Open Graph,
 * Twitter, robots et graphe JSON-LD schema.org.
 */
class Seo
{
    public string $title = '';
    public string $description = '';
    public ?string $canonical = null;
    public string $type = 'website';
    public ?string $image = null;
    public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    public array $breadcrumbs = [];
    public array $graph = [];
    public array $meta = [];
    public ?string $markdown = null;
    public ?string $rss = null;

    public function title(string $title, bool $suffix = true): static
    {
        $this->title = $suffix ? Str::limit($title, 62, '…').' | '.setting('name') : $title;

        return $this;
    }

    public function description(?string $text): static
    {
        $this->description = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text))), 158, '…');

        return $this;
    }

    public function noindex(): static
    {
        $this->robots = 'noindex, follow';

        return $this;
    }

    public function crumbs(array $items): static
    {
        $this->breadcrumbs = $items; // [[label, url|null], ...]

        return $this;
    }

    public function add(array $node): static
    {
        $this->graph[] = $node;

        return $this;
    }

    public function siteUrl(): string
    {
        return rtrim(config('app.url'), '/');
    }

    public function organization(): array
    {
        $social = array_values(array_filter((array) setting('social', [])));

        return array_filter([
            '@type' => 'NewsMediaOrganization',
            '@id' => $this->siteUrl().'/#organization',
            'name' => setting('name'),
            'alternateName' => ['VTG', 'VOXTECH GLOBAL'],
            'url' => $this->siteUrl().'/',
            'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/img/logo-512.png'), 'width' => 512, 'height' => 512],
            'slogan' => 'Révéler les talents. Explorer les innovations. Décrypter la Tech.',
            'description' => setting('seo_description'),
            'foundingDate' => '2026',
            'parentOrganization' => ['@type' => 'EducationalOrganization', 'name' => 'École Virtuelle des Créatifs (EVC)'],
            'areaServed' => ['Afrique', 'Monde'],
            'inLanguage' => 'fr',
            'knowsAbout' => ['Intelligence artificielle', 'Startups', 'Innovation', 'Fintech', 'Agritech', 'Santé numérique', 'Éducation', 'Énergie', 'Talents tech'],
            'publishingPrinciples' => route('charte'),
            'ethicsPolicy' => route('charte'),
            'correctionsPolicy' => route('charte').'#ab-charte',
            'verificationFactCheckingPolicy' => route('charte').'#ab-charte',
            'masthead' => route('apropos').'#ab-equipe',
            'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'Rédaction', 'email' => setting('email_redaction'), 'url' => route('contact')],
                ['@type' => 'ContactPoint', 'contactType' => 'Publicité', 'email' => setting('email_partners'), 'url' => route('annoncer')]],
            'sameAs' => $social ?: null,
        ]);
    }

    public function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => $this->siteUrl().'/#website',
            'url' => $this->siteUrl().'/',
            'name' => setting('name'),
            'description' => setting('tagline'),
            'inLanguage' => 'fr-FR',
            'publisher' => ['@id' => $this->siteUrl().'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('recherche').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function person(?Author $a): array
    {
        if (! $a) {
            return ['@type' => 'Organization', 'name' => setting('name')];
        }

        return array_filter([
            '@type' => 'Person', '@id' => $a->url().'#person', 'name' => $a->name, 'url' => $a->url(),
            'jobTitle' => $a->role, 'description' => $a->bio,
            'worksFor' => ['@id' => $this->siteUrl().'/#organization'],
            'sameAs' => $a->same_as ?: null,
        ]);
    }

    /** Article / NewsArticle + FAQ (interviews) + ClaimReview (fact-checks). */
    public function article(Article $a): static
    {
        $this->type = 'article';
        $this->title($a->seoTitle());
        $this->description($a->seoDescription());
        $this->canonical = $a->url();
        $this->image = route('og.article', $a);
        $this->markdown = route('article.md', $a);
        $schemaType = match ($a->nature ?: $a->type) {
            'Opinion' => 'OpinionNewsArticle',
            'Analyse', 'dossier', 'Enquête' => 'AnalysisNewsArticle',
            'Reportage' => 'ReportageNewsArticle',
            default => 'NewsArticle',
        };
        $node = array_filter([
            '@type' => $schemaType,
            '@id' => $a->url().'#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $a->url()],
            'headline' => Str::limit($a->title, 110, '…'),
            'alternativeHeadline' => $a->seo_title,
            'description' => $a->chapo,
            'abstract' => $a->chapo,
            'image' => [['@type' => 'ImageObject', 'url' => route('og.article', $a), 'width' => 1200, 'height' => 630]],
            'datePublished' => optional($a->published_at)->toAtomString(),
            'dateModified' => optional($a->updated_at ?? $a->published_at)->toAtomString(),
            'author' => [$this->person($a->author)],
            'publisher' => ['@id' => $this->siteUrl().'/#organization'],
            'isPartOf' => ['@id' => $this->siteUrl().'/#website'],
            'articleSection' => $a->rubrique?->name,
            'genre' => $a->label(),
            'keywords' => implode(', ', array_merge($a->tags ?? [], array_filter([$a->theme?->name, $a->country]))),
            'about' => array_values(array_filter([$a->theme ? ['@type' => 'Thing', 'name' => $a->theme->name] : null])),
            'contentLocation' => $a->country && $a->country !== 'International' ? ['@type' => 'Place', 'name' => $a->country] : null,
            'wordCount' => $a->wordCount(),
            'timeRequired' => 'PT'.$a->read_time.'M',
            'inLanguage' => 'fr-FR',
            'isAccessibleForFree' => true,
            'citation' => $a->sources ?: null,
            'speakable' => ['@type' => 'SpeakableSpecification', 'cssSelector' => ['h1', '.ap-chapo', '.verdict p']],
        ]);
        $this->add($node);

        if ($pairs = $a->qaPairs()) {
            $this->add([
                '@type' => 'FAQPage', '@id' => $a->url().'#faq',
                'mainEntity' => array_map(fn ($qa) => ['@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]]], $pairs),
            ]);
        }
        if ($a->verdict) {
            $rating = ['Faux' => 1, 'Trompeur' => 2, 'Exagéré' => 2, 'Imprécis' => 3, 'Plutôt vrai' => 4, 'Vrai' => 5][$a->verdict['label']] ?? 3;
            $this->add([
                '@type' => 'ClaimReview', '@id' => $a->url().'#claimreview',
                'url' => $a->url(), 'datePublished' => optional($a->published_at)->toDateString(),
                'author' => ['@id' => $this->siteUrl().'/#organization'],
                'claimReviewed' => $a->chapo,
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5, 'worstRating' => 1, 'alternateName' => $a->verdict['label']],
                'itemReviewed' => ['@type' => 'Claim', 'appearance' => ['@type' => 'CreativeWork', 'name' => 'Rumeur diffusée en ligne']],
            ]);
        }
        $this->meta['article:published_time'] = optional($a->published_at)->toAtomString();
        $this->meta['article:modified_time'] = optional($a->updated_at)->toAtomString();
        $this->meta['article:section'] = $a->rubrique?->name;
        $this->meta['article:author'] = $a->author?->name;

        return $this;
    }

    public function publication(Publication $p): static
    {
        $this->title($p->title);
        $this->description($p->description);
        $this->canonical = $p->url();
        $type = $p->type === 'Magazine' ? 'PublicationIssue' : ($p->type === 'Rapport' ? 'Report' : 'Book');
        $this->add(array_filter([
            '@type' => $type, '@id' => $p->url().'#pub', 'name' => $p->title, 'alternativeHeadline' => $p->subtitle,
            'description' => $p->description, 'url' => $p->url(), 'numberOfPages' => $p->pages ?: null,
            'datePublished' => optional($p->published_on)->toDateString(), 'inLanguage' => 'fr',
            'publisher' => ['@id' => $this->siteUrl().'/#organization'], 'bookFormat' => $type === 'Book' ? 'https://schema.org/EBook' : null,
            'offers' => ['@type' => 'Offer', 'price' => $p->price, 'priceCurrency' => 'XOF', 'availability' => 'https://schema.org/InStock', 'url' => $p->url()],
        ]));

        return $this;
    }

    /** Graphe JSON-LD final (Organization + WebSite + Breadcrumb + nœuds de la page). */
    public function jsonLd(): string
    {
        $graph = [$this->organization(), $this->website()];
        $page = [
            '@type' => $this->type === 'article' ? 'WebPage' : 'CollectionPage',
            '@id' => ($this->canonical ?: url()->current()).'#webpage',
            'url' => $this->canonical ?: url()->current(),
            'name' => $this->title,
            'description' => $this->description,
            'isPartOf' => ['@id' => $this->siteUrl().'/#website'],
            'inLanguage' => 'fr-FR',
        ];
        if ($this->breadcrumbs) {
            $items = [];
            foreach (array_values($this->breadcrumbs) as $i => [$label, $url]) {
                $items[] = array_filter(['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => $url ?: ($this->canonical ?: url()->current())]);
            }
            $graph[] = ['@type' => 'BreadcrumbList', '@id' => ($this->canonical ?: url()->current()).'#breadcrumb', 'itemListElement' => $items];
            $page['breadcrumb'] = ['@id' => ($this->canonical ?: url()->current()).'#breadcrumb'];
        }
        $graph[] = $page;

        return json_encode(['@context' => 'https://schema.org', '@graph' => array_merge($graph, $this->graph)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    public function finalTitle(): string
    {
        return $this->title ?: setting('seo_title');
    }

    public function finalDescription(): string
    {
        return $this->description ?: setting('seo_description');
    }
}
