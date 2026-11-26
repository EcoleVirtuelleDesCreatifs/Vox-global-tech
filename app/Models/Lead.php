<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['kind', 'name', 'company', 'email', 'subject', 'format', 'budget', 'message', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
