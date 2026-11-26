<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    public const TYPES = ['Emploi', 'Stage', 'Formation', 'Bourse', 'Concours'];

    protected $fillable = ['type', 'title', 'organization', 'location', 'deadline', 'url'];
    protected $casts = ['deadline' => 'date'];
}
