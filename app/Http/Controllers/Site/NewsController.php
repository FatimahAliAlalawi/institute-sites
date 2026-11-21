<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;

class NewsController extends Controller
{
    public function index()
    {
        return view('site.news.index', ['posts' => Post::published()->paginate(9)]);
    }

    public function show(Post $post)
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at->isPast()), 404);

        return view('site.news.show', [
            'post' => $post,
            'latest' => Post::published()->whereKeyNot($post->id)->take(3)->get(),
        ]);
    }
}
