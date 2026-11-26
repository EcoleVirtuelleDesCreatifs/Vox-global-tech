<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterDelivery extends Model
{
    protected $fillable = ['newsletter_id', 'subscriber_id', 'token', 'opened_at', 'clicked_at'];
    protected $casts = ['opened_at' => 'datetime', 'clicked_at' => 'datetime'];

    public function newsletter(): BelongsTo { return $this->belongsTo(Newsletter::class); }
    public function subscriber(): BelongsTo { return $this->belongsTo(Subscriber::class); }
}
