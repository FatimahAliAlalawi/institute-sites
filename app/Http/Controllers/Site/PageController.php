<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Partner;

class PageController extends Controller
{
    /** صفحة مخصصة أنشأها المدير */
    public function show(\App\Models\Page $page)
    {
        abort_unless($page->is_published, 404);

        return view('site.page', compact('page'));
    }

    public function about()
    {
        return view('site.about', [
            'partners' => Partner::orderBy('sort')->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort')->get(),
        ]);
    }
}
