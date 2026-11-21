<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('site.home', [
            'courses' => Course::published()->where('is_featured', true)->with(['instructor', 'category'])->withCount('lessons')
                ->withAvg(['reviews' => fn ($r) => $r->where('is_approved', true)], 'rating')->ordered()->take(6)->get(),
            'programs' => Program::active()->where('is_featured', true)->ordered()->take(6)->get(),
            'posts' => Post::published()->take(3)->get(),
            'partners' => Partner::orderBy('sort')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort')->take(6)->get(),
        ]);
    }
}
