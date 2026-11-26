<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';
    protected $fillable = ['name', 'path', 'type', 'mime', 'size', 'width', 'height', 'alt', 'tone', 'user_id'];

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function isImage(): bool { return $this->type === 'Image'; }
}
