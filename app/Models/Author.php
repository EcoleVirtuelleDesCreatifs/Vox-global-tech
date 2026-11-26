<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $fillable = ['slug', 'name', 'role', 'bio', 'email', 'same_as'];
    protected $casts = ['same_as' => 'array'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function articles(): HasMany { return $this->hasMany(Article::class); }

    public function url(): string { return route('auteur', $this); }

    public function initials(): string { return initials($this->name); }
}
