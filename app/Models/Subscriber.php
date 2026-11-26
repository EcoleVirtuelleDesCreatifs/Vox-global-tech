<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    protected $fillable = ['email', 'source', 'token', 'confirmed_at', 'unsubscribed_at'];
    protected $casts = ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Subscriber $s) {
            $s->token = $s->token ?: Str::random(40);
        });
    }

    public function scopeActive($q) { return $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'); }

    public function isActive(): bool { return $this->confirmed_at && ! $this->unsubscribed_at; }
}
