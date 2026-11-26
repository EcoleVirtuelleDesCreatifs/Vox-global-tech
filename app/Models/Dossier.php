<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dossier extends Model
{
    protected $fillable = ['slug', 'title', 'chapo', 'tone', 'position'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot('position')->orderBy('article_dossier.position');
    }

    public function url(): string { return route('dossier', $this); }
}
