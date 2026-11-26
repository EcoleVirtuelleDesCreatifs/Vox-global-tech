<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;
    protected $fillable = ['path', 'article_id', 'visitor', 'session', 'source', 'referrer_host', 'utm_source', 'utm_campaign', 'device', 'country', 'duration', 'scroll', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
