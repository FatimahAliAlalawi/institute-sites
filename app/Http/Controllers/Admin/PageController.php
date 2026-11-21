<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** الصفحات المخصصة (محرر مرئي) */
class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::latest('id')->get()]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new Page(['is_published' => true, 'show_header' => true])]);
    }

    public function store(Request $request)
    {
        $page = Page::create($this->validated($request));
        if ($request->boolean('add_to_menu')) {
            MenuItem::create(['location' => 'header', 'label' => $page->title, 'type' => 'page', 'target' => (string) $page->id, 'sort' => (int) MenuItem::where('location', 'header')->max('sort') + 1]);
        }

        return redirect()->route('admin.pages.edit', $page)->with('status', 'تم إنشاء الصفحة ✅'.($request->boolean('add_to_menu') ? ' وأُضيفت للقائمة العلوية.' : ''));
    }

    public function edit(Page $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validated($request, $page);
        $data['cover'] = Uploader::replace($request->file('cover'), $page->cover, 'pages', $request->boolean('remove_cover'));
        $page->update($data);

        return back()->with('status', 'تم حفظ الصفحة.');
    }

    public function destroy(Page $page)
    {
        Uploader::delete($page->cover);
        MenuItem::where('type', 'page')->where('target', (string) $page->id)->delete();
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'تم حذف الصفحة (وأُزيلت من القوائم).');
    }

    /** رفع صورة من داخل المحرر */
    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096'], [], ['image' => 'الصورة']);

        return response()->json(['url' => asset(Uploader::store($request->file('image'), 'pages'))]);
    }

    protected function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'subtitle' => 'nullable|string|max:500',
            'content' => 'nullable|string|max:500000',
            'meta_description' => 'nullable|string|max:500',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [], ['title' => 'عنوان الصفحة', 'slug' => 'الرابط']);
        unset($data['cover']);
        $data['content'] = HtmlSanitizer::clean($data['content'] ?? '');
        $data['is_published'] = $request->boolean('is_published');
        $data['show_header'] = $request->boolean('show_header');

        return $data;
    }
}
