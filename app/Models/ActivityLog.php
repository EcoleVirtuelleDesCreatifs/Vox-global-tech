<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'who', 'action', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public static function record(string $action, ?User $user = null): void
    {
        $user ??= auth()->user();
        static::create(['user_id' => $user?->id, 'who' => $user?->name ?? 'Système', 'action' => $action, 'created_at' => now()]);
    }
}
