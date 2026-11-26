<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    public const STATUSES = ['Nouvelle', 'En étude', 'Retenue', 'Refusée'];
    public const KINDS = ['Un talent', 'Une innovation', 'Une startup', 'Un projet technologique'];

    protected $fillable = ['kind', 'name', 'email', 'project', 'category', 'country', 'description', 'status'];
}
