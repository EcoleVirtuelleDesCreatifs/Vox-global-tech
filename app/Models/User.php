<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['Administrateur', 'Rédacteur en chef', 'Journaliste', 'Contributeur', 'Commercial'];

    /** Matrice des droits (ordre = ROLES), identique à la maquette. */
    public const PERMISSIONS = [
        'publish' => ['Publier un article', [1, 1, 1, 0, 0]],
        'draft' => ['Écrire un brouillon', [1, 1, 1, 1, 0]],
        'edit-others' => ['Modifier les articles des autres', [1, 1, 0, 0, 0]],
        'moderate' => ['Modérer les commentaires', [1, 1, 1, 0, 0]],
        'library' => ['Gérer la bibliothèque', [1, 1, 0, 0, 0]],
        'ads' => ['Gérer les publicités', [1, 0, 0, 0, 1]],
        'newsletter' => ['Envoyer la newsletter', [1, 1, 0, 0, 0]],
        'stats' => ['Voir les statistiques', [1, 1, 1, 0, 1]],
        'users' => ['Gérer les utilisateurs', [1, 0, 0, 0, 0]],
        'settings' => ['Modifier les réglages', [1, 0, 0, 0, 0]],
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'active', 'author_id', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function canDo(string $permission): bool
    {
        $index = array_search($this->role, self::ROLES, true);
        if ($index === false || ! isset(self::PERMISSIONS[$permission])) {
            return false;
        }

        return (bool) self::PERMISSIONS[$permission][1][$index];
    }

    public function initials(): string
    {
        return initials($this->name);
    }
}
