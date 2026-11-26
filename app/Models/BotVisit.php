<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotVisit extends Model
{
    public $timestamps = false;
    protected $fillable = ['bot', 'kind', 'path', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
