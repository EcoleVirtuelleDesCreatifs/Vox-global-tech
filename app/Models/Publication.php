<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    public const TYPES = ['Magazine', 'Livre', 'E-book', 'Rapport', 'Documentation'];

    protected $fillable = ['slug', 'type', 'title', 'subtitle', 'published_on', 'pages', 'format', 'price', 'color', 'description', 'toc', 'file_path', 'downloads'];
    protected $casts = ['toc' => 'array', 'published_on' => 'date'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function url(): string { return route('publication', $this); }
}
