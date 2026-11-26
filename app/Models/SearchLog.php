<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['query', 'results', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
}
