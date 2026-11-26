<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = ['advertiser', 'slot', 'title', 'cta', 'url', 'starts_on', 'ends_on', 'budget', 'impressions', 'clicks', 'active', 'expiry_notified_at'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean', 'expiry_notified_at' => 'datetime'];

    public function adSlot(): BelongsTo { return $this->belongsTo(AdSlot::class, 'slot', 'code'); }

    /** Campagne active ET dans sa période de diffusion. */
    public function scopeRunning($q)
    {
        $today = now()->toDateString();

        return $q->where('active', true)
            ->where(fn ($w) => $w->whereNull('starts_on')->orWhere('starts_on', '<=', $today))
            ->where(fn ($w) => $w->whereNull('ends_on')->orWhere('ends_on', '>=', $today));
    }

    public function ctr(): float
    {
        return $this->impressions ? round($this->clicks / $this->impressions * 100, 2) : 0.0;
    }
}
