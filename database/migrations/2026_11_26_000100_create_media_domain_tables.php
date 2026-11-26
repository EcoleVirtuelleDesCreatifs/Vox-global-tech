<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });

        Schema::create('rubriques', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('pillar')->nullable();
            $t->string('promise')->nullable();
            $t->text('description')->nullable();
            $t->json('subs')->nullable();
            $t->unsignedSmallInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('themes', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('authors', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('role')->nullable();
            $t->text('bio')->nullable();
            $t->string('email')->nullable();
            $t->json('same_as')->nullable(); // profils sociaux (E-E-A-T)
            $t->timestamps();
        });

        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('path')->nullable();
            $t->string('type', 20)->default('Image');
            $t->string('mime', 80)->nullable();
            $t->unsignedInteger('size')->default(0); // Ko
            $t->unsignedInteger('width')->default(0);
            $t->unsignedInteger('height')->default(0);
            $t->string('alt')->nullable();
            $t->unsignedTinyInteger('tone')->default(0);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('type', 20)->default('article')->index();
            $t->string('nature', 30)->nullable();
            $t->foreignId('rubrique_id')->nullable()->constrained()->nullOnDelete();
            $t->string('sub')->nullable();
            $t->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $t->string('region', 40)->nullable();
            $t->string('country', 80)->nullable();
            $t->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
            $t->string('title');
            $t->text('chapo')->nullable();
            $t->json('body')->nullable();
            $t->json('tags')->nullable();
            $t->json('sources')->nullable();
            $t->json('fiche')->nullable();
            $t->json('startup')->nullable();
            $t->json('verdict')->nullable();
            $t->string('status', 20)->default('brouillon')->index();
            $t->timestamp('published_at')->nullable()->index();
            $t->unsignedSmallInteger('read_time')->default(3);
            $t->boolean('is_featured')->default(false)->index();
            $t->boolean('is_sponsored')->default(false);
            $t->unsignedInteger('views')->default(0);
            $t->string('seo_title')->nullable();
            $t->string('seo_description', 300)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'published_at']);
        });

        Schema::create('comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email');
            $t->text('text');
            $t->string('status', 20)->default('en attente')->index();
            $t->string('ip', 64)->nullable();
            $t->timestamps();
        });

        Schema::create('dossiers', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('chapo')->nullable();
            $t->unsignedTinyInteger('tone')->default(0);
            $t->unsignedSmallInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('article_dossier', function (Blueprint $t) {
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('position')->default(0);
            $t->primary(['article_id', 'dossier_id']);
        });

        Schema::create('publications', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('type', 30);
            $t->string('title');
            $t->string('subtitle')->nullable();
            $t->date('published_on')->nullable();
            $t->unsignedSmallInteger('pages')->default(0);
            $t->string('format', 20)->default('PDF');
            $t->unsignedInteger('price')->default(0);
            $t->string('color', 20)->default('ink');
            $t->text('description')->nullable();
            $t->json('toc')->nullable();
            $t->string('file_path')->nullable();
            $t->unsignedInteger('downloads')->default(0);
            $t->timestamps();
        });

        Schema::create('ad_slots', function (Blueprint $t) {
            $t->string('code', 30)->primary();
            $t->string('name');
            $t->string('size');
            $t->string('location');
            $t->unsignedSmallInteger('position')->default(0);
        });

        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('advertiser');
            $t->string('slot', 30)->index();
            $t->string('title');
            $t->string('cta', 60)->default('Découvrir');
            $t->string('url')->nullable();
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->unsignedBigInteger('budget')->default(0);
            $t->unsignedBigInteger('impressions')->default(0);
            $t->unsignedBigInteger('clicks')->default(0);
            $t->boolean('active')->default(false);
            $t->timestamp('expiry_notified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('submissions', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 40)->default('Un talent');
            $t->string('name');
            $t->string('email');
            $t->string('project');
            $t->string('category')->nullable();
            $t->string('country')->nullable();
            $t->text('description');
            $t->string('status', 20)->default('Nouvelle')->index();
            $t->timestamps();
        });

        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20); // Annonceur | Contact
            $t->string('name');
            $t->string('company')->nullable();
            $t->string('email');
            $t->string('subject')->nullable();
            $t->string('format')->nullable();
            $t->string('budget')->nullable();
            $t->text('message');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->string('source', 40)->nullable();
            $t->string('token', 64)->unique();
            $t->timestamp('confirmed_at')->nullable();
            $t->timestamp('unsubscribed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('newsletters', function (Blueprint $t) {
            $t->id();
            $t->string('subject');
            $t->text('intro')->nullable();
            $t->json('article_ids')->nullable();
            $t->string('status', 20)->default('Brouillon');
            $t->timestamp('sent_at')->nullable();
            $t->unsignedInteger('recipients')->default(0);
            $t->unsignedInteger('opens')->default(0);
            $t->unsignedInteger('clicks')->default(0);
            $t->timestamps();
        });

        Schema::create('newsletter_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $t->string('token', 40)->unique();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('clicked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30);
            $t->string('title');
            $t->string('organization')->nullable();
            $t->string('location')->nullable();
            $t->date('deadline')->nullable();
            $t->string('url')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('who');
            $t->string('action', 500);
            $t->timestamp('created_at')->useCurrent()->index();
        });

        // ---- Mesure d'audience maison (sans cookie, IP hachée) ----
        Schema::create('page_views', function (Blueprint $t) {
            $t->id();
            $t->string('path', 500);
            $t->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $t->string('visitor', 64)->index();   // hash quotidien IP + UA
            $t->string('session', 64)->index();   // hash visiteur + tranche 30 min
            $t->string('source', 20)->default('direct')->index(); // direct|search|social|newsletter|referral|ai
            $t->string('referrer_host')->nullable();
            $t->string('utm_source')->nullable();
            $t->string('utm_campaign')->nullable();
            $t->string('device', 10)->default('desktop');
            $t->string('country', 2)->nullable();
            $t->unsignedSmallInteger('duration')->default(0); // secondes de lecture
            $t->unsignedTinyInteger('scroll')->default(0);    // % de défilement max
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('bot_visits', function (Blueprint $t) {
            $t->id();
            $t->string('bot', 40)->index();
            $t->string('kind', 20)->index(); // ai | search | seo | other
            $t->string('path', 500);
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('search_logs', function (Blueprint $t) {
            $t->id();
            $t->string('query', 120)->index();
            $t->unsignedInteger('results')->default(0);
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['search_logs', 'bot_visits', 'page_views', 'activity_logs', 'opportunities', 'newsletter_deliveries', 'newsletters',
            'subscribers', 'leads', 'submissions', 'campaigns', 'ad_slots', 'publications', 'article_dossier', 'dossiers', 'comments',
            'articles', 'media', 'authors', 'themes', 'rubriques', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
