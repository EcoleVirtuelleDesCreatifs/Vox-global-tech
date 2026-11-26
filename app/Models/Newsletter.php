<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Newsletter extends Model
{
    protected $fillable = ['subject', 'intro', 'article_ids', 'status', 'sent_at', 'recipients', 'opens', 'clicks'];
    protected $casts = ['article_ids' => 'array', 'sent_at' => 'datetime'];

    public function deliveries(): HasMany { return $this->hasMany(NewsletterDelivery::class); }

    public function articles()
    {
        $ids = $this->article_ids ?: [];

        return Article::whereIn('id', $ids)->get()->sortBy(fn ($a) => array_search($a->id, $ids))->values();
    }

    public function openRate(): float { return $this->recipients ? round($this->opens / $this->recipients * 100, 1) : 0.0; }
    public function clickRate(): float { return $this->recipients ? round($this->clicks / $this->recipients * 100, 1) : 0.0; }
}
