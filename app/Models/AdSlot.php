<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdSlot extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'size', 'location', 'position'];
}
