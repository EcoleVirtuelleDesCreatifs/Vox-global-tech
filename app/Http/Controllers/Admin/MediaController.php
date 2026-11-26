<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Media;
use App\Services\MediaUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $r)
    {
        $media = Media::when($r->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('alt', 'like', "%$s%")))
            ->when($r->query('type'), fn ($q, $t) => $q->where('type', $t))->latest()->paginate(48)->withQueryString();
        $sel = $r->query('m') ? Media::find($r->query('m')) : null;

        return view('admin.media', ['media' => $media, 'sel' => $sel, 'total' => Media::sum('size'), 'count' => Media::count(),
            'usedBy' => $sel ? Article::where('cover_id', $sel->id)->get(['id', 'title']) : collect()]);
    }

    public function store(Request $r, MediaUploader $up)
    {
        $r->validate(['files' => 'required|array|max:20', 'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,gif,avif,pdf,svg'],
            ['files.*.mimes' => 'Formats acceptés : images (jpg, png, webp, gif, avif, svg) et PDF.', 'files.*.max' => '10 Mo maximum par fichier.']);
        $n = 0;
        foreach ($r->file('files') as $f) { $up->store($f, $r->user()); $n++; }
        ActivityLog::record('a ajouté '.$n.' fichier(s) à la médiathèque');

        return back()->with('ok', $n.' fichier(s) ajouté(s).');
    }

    public function update(Request $r, Media $media)
    {
        $media->update($r->validate(['alt' => 'nullable|string|max:250']));

        return back()->with('ok', 'Texte alternatif enregistré.');
    }

    public function destroy(Media $media)
    {
        if ($media->path) Storage::disk('public')->delete($media->path);
        Article::where('cover_id', $media->id)->update(['cover_id' => null]);
        $media->delete();
        ActivityLog::record('a supprimé le fichier '.$media->name);

        return redirect()->route('admin.media')->with('ok', 'Fichier supprimé.');
    }
}
