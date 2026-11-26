<?php

namespace Tests;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Chaque test démarre avec le jeu de données éditorial complet (sans statistiques de démonstration). */
    protected bool $seed = true;

    protected function admin(): User
    {
        return User::where('role', 'Administrateur')->firstOrFail();
    }

    protected function userWithRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    protected function setSetting(array $values): void
    {
        app(Settings::class)->set($values);
    }
}
