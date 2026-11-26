<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Seo;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function show(Request $request, string $slug, Seo $seo)
    {
        $article = Article::with(['rubrique', 'author', 'theme', 'cover'])->where('slug', $slug)->firstOrFail();
        $preview = ! $article->isLive();
        if ($preview && ! $request->user()) {
            abort(404);
        }
        $request->attributes->set('vox.article', $preview ? null : $article);

        $seo->article($article);
        if ($preview) {
            $seo->noindex();
        }
        $crumbs = [['Accueil', route('home')]];
        if ($article->rubrique) {
            $crumbs[] = [$article->rubrique->name, route('rubrique', $article->rubrique)];
        }
        $crumbs[] = [$article->title, $article->url()];
        $seo->crumbs($crumbs);

        $comments = setting('comments_on')
            ? $article->comments()->approved()->whereNull('parent_id')->with(['replies' => fn ($q) => $q->approved()->oldest()])->oldest()->get()
            : collect();

        $related = Article::published()->with(['rubrique', 'author', 'theme'])->where('id', '!=', $article->id)
            ->where(fn ($q) => $q->where('rubrique_id', $article->rubrique_id)->orWhere('theme_id', $article->theme_id))
            ->latestFirst()->limit(3)->get();
        $next = Article::published()->with('rubrique')->where('id', '!=', $article->id)
            ->where('published_at', '<', $article->published_at ?? now())->latestFirst()->first()
            ?? Article::published()->with('rubrique')->where('id', '!=', $article->id)->latestFirst()->first();

        return view('site.article', compact('article', 'comments', 'related', 'next', 'preview'));
    }

    /** Version Markdown de l'article (pour les assistants IA et agents). */
    public function markdown(Article $article)
    {
        abort_unless($article->isLive(), 404);

        return response($article->toMarkdown(), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Link' => '<'.$article->url().'>; rel="canonical"',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
