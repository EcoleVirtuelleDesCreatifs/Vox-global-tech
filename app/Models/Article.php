<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Article extends Model
{
    public const TYPES = [
        'article' => 'Article', 'interview' => 'Interview', 'portrait' => 'Portrait',
        'projet' => 'Innovation documentée', 'dossier' => 'Dossier', 'factcheck' => 'Fact-check',
    ];

    public const NATURES = ['Fait', 'Annonce', 'Analyse', 'Enquête', 'Opinion', 'Portrait', 'Entretien', 'Reportage', 'Guide', 'Fact-check'];

    public const REGIONS = ['Afrique', 'Amériques', 'Asie', 'Europe', 'Moyen-Orient', 'Monde'];

    public const STATUSES = ['publié' => 'Publié', 'programmé' => 'Programmé', 'brouillon' => 'Brouillon'];

    protected $fillable = [
        'slug', 'type', 'nature', 'rubrique_id', 'sub', 'theme_id', 'region', 'country', 'author_id', 'cover_id',
        'title', 'chapo', 'body', 'tags', 'sources', 'fiche', 'startup', 'verdict', 'status', 'published_at',
        'read_time', 'is_featured', 'is_sponsored', 'views', 'seo_title', 'seo_description', 'created_by',
    ];

    protected $casts = [
        'body' => 'array', 'tags' => 'array', 'sources' => 'array', 'fiche' => 'array', 'startup' => 'array',
        'verdict' => 'array', 'published_at' => 'datetime', 'is_featured' => 'boolean', 'is_sponsored' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /* ---------------- Relations ---------------- */
    public function rubrique(): BelongsTo { return $this->belongsTo(Rubrique::class); }
    public function theme(): BelongsTo { return $this->belongsTo(Theme::class); }
    public function author(): BelongsTo { return $this->belongsTo(Author::class); }
    public function cover(): BelongsTo { return $this->belongsTo(Media::class, 'cover_id'); }
    public function comments(): HasMany { return $this->hasMany(Comment::class); }
    public function dossiers(): BelongsToMany { return $this->belongsToMany(Dossier::class); }

    /* ---------------- Portées ---------------- */

    /** Visible des lecteurs : publié, ou programmé dont la date est passée (fonctionne même sans tâche cron). */
    public function scopePublished(Builder $q): Builder
    {
        return $q->whereIn('status', ['publié', 'programmé'])->where('published_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('published_at')->orderByDesc('id');
    }

    /* ---------------- Aides ---------------- */
    public function isLive(): bool
    {
        return in_array($this->status, ['publié', 'programmé'], true) && $this->published_at && $this->published_at->lte(now());
    }

    public function effectiveStatus(): string
    {
        return $this->status === 'programmé' && $this->isLive() ? 'publié' : $this->status;
    }

    public function label(): string
    {
        if ($this->nature && in_array($this->nature, ['Opinion', 'Fact-check', 'Analyse', 'Enquête', 'Reportage', 'Guide'], true)) {
            return $this->nature;
        }

        return self::TYPES[$this->type] ?? 'Article';
    }

    public function url(): string
    {
        return route('article', $this);
    }

    public function seoTitle(): string
    {
        return (string) ($this->seo_title ?: $this->title);
    }

    public function seoDescription(): string
    {
        return Str::limit($this->seo_description ?: (string) $this->chapo, 158, '…');
    }

    public function hue(): int
    {
        return ($this->id * 37) % 4;
    }

    public function blocks(): array
    {
        return is_array($this->body) ? $this->body : [];
    }

    /** Intertitres pour le sommaire. */
    public function toc(): array
    {
        $out = [];
        foreach ($this->blocks() as $i => $b) {
            if (! empty($b['h'])) {
                $out[] = ['i' => $i, 'h' => $b['h']];
            }
        }

        return $out;
    }

    public function quote(): ?array
    {
        foreach ($this->blocks() as $b) {
            if (! empty($b['q'])) {
                return $b;
            }
        }

        return null;
    }

    /** Paires question/réponse (interviews) — exploitées en FAQPage JSON-LD. */
    public function qaPairs(): array
    {
        return array_values(array_map(fn ($b) => $b['qa'], array_filter($this->blocks(), fn ($b) => ! empty($b['qa']))));
    }

    public function plainText(): string
    {
        $parts = [];
        foreach ($this->blocks() as $b) {
            if (! empty($b['p'])) $parts[] = $b['p'];
            elseif (! empty($b['h'])) $parts[] = $b['h'];
            elseif (! empty($b['q'])) $parts[] = '« '.$b['q'].' »'.(! empty($b['who']) ? ' — '.$b['who'] : '');
            elseif (! empty($b['qa'])) $parts[] = $b['qa'][0]."\n".$b['qa'][1];
        }

        return implode("\n\n", $parts);
    }

    public function wordCount(): int
    {
        return str_word_count(Str::ascii($this->chapo.' '.$this->plainText()));
    }

    public static function estimateReadTime(string $text): int
    {
        return max(1, (int) ceil(str_word_count(Str::ascii($text)) / 220));
    }

    /** Version Markdown (GEO : consommée par les moteurs de réponse IA). */
    public function toMarkdown(): string
    {
        $md = '# '.$this->title."\n\n";
        $md .= '> '.$this->chapo."\n\n";
        $md .= '- Rubrique : '.($this->rubrique?->name ?? '—')."\n";
        $md .= '- Thématique : '.($this->theme?->name ?? '—')."\n";
        $md .= '- Auteur : '.($this->author?->name ?? 'La rédaction')."\n";
        $md .= '- Publié le : '.optional($this->published_at)->toDateString()."\n";
        $md .= '- Pays : '.($this->country ?? '—')."\n";
        $md .= '- URL : '.$this->url()."\n\n";
        if ($this->verdict) {
            $md .= '**Verdict : '.$this->verdict['label'].'** — '.$this->verdict['text']."\n\n";
        }
        foreach ($this->blocks() as $b) {
            if (! empty($b['p'])) $md .= $b['p']."\n\n";
            elseif (! empty($b['h'])) $md .= '## '.$b['h']."\n\n";
            elseif (! empty($b['q'])) $md .= '> « '.$b['q'].' »'.(! empty($b['who']) ? ' — '.$b['who'] : '')."\n\n";
            elseif (! empty($b['qa'])) $md .= '**'.$b['qa'][0]."**\n\n".$b['qa'][1]."\n\n";
        }
        if ($this->sources) {
            $md .= "## Sources\n\n".collect($this->sources)->map(fn ($s) => '- '.$s)->implode("\n")."\n";
        }

        return $md;
    }

    /* -------- Conversion éditeur texte <-> blocs (syntaxe de la maquette) -------- */
    public function bodyToText(): string
    {
        $out = [];
        foreach ($this->blocks() as $b) {
            if (! empty($b['p'])) $out[] = $b['p'];
            elseif (! empty($b['h'])) $out[] = '## '.$b['h'];
            elseif (! empty($b['q'])) $out[] = '> '.$b['q'].(! empty($b['who']) ? ' — '.$b['who'] : '');
            elseif (! empty($b['qa'])) $out[] = 'Q : '.$b['qa'][0]."\nR : ".$b['qa'][1];
        }

        return implode("\n\n", $out);
    }

    public static function textToBody(string $text): array
    {
        $chunks = preg_split("/\R\s*\R/u", trim(str_replace("\r", '', $text))) ?: [];
        $blocks = [];
        foreach ($chunks as $c) {
            $c = trim($c);
            if ($c === '') continue;
            if (str_starts_with($c, '## ')) {
                $blocks[] = ['h' => trim(substr($c, 3))];
            } elseif (str_starts_with($c, '>')) {
                $q = trim(ltrim($c, '> '));
                $who = null;
                if (preg_match('/^(.*)\s+[—–-]\s+([^—–]+)$/u', $q, $m)) {
                    [$q, $who] = [trim($m[1]), trim($m[2])];
                }
                $blocks[] = array_filter(['q' => trim($q, '«» '), 'who' => $who]);
            } elseif (preg_match('/^Q\s*:\s*(.+?)\nR\s*:\s*(.+)$/su', $c, $m)) {
                $blocks[] = ['qa' => [trim($m[1]), trim($m[2])]];
            } else {
                $blocks[] = ['p' => preg_replace('/\s*\n\s*/', ' ', $c)];
            }
        }

        return $blocks;
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($title), 70, '') ?: 'article';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
