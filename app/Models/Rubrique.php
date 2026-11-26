<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubrique extends Model
{
    protected $fillable = ['slug', 'name', 'pillar', 'promise', 'description', 'subs', 'position'];
    protected $casts = ['subs' => 'array'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function articles(): HasMany { return $this->hasMany(Article::class); }

    public function url(): string { return route('rubrique', $this); }
}
