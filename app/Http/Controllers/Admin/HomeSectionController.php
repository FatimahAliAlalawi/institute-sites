<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use App\Support\HomeSections;
use Illuminate\Http\Request;

/** الصفحة الرئيسية: ترتيب الأقسام، إظهارها، عناوينها، وإضافة أقسام من صفحات مخصصة */
class HomeSectionController extends Controller
{
    public function edit()
    {
        return view('admin.home.edit', [
            'sections' => HomeSections::all(),
            'pages' => Page::where('is_published', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    /** حفظ عناوين الأقسام (الترتيب والإظهار لهما أزرار مستقلة) */
    public function update(Request $request)
    {
        $texts = [];
        foreach (HomeSections::TITLED as $key) {
            foreach (['eyebrow', 'title'] as $field) {
                if ($request->has("text.$key.$field")) {
                    $texts["home_{$key}_{$field}"] = mb_substr(trim((string) $request->input("text.$key.$field")), 0, 190) ?: null;
                }
            }
        }
        Setting::put($texts);

        return back()->with('status', 'تم حفظ عناوين الأقسام.');
    }

    public function move(Request $request)
    {
        $sections = HomeSections::all();
        $index = (int) $request->input('index');
        $swap = $request->input('dir') === 'up' ? $index - 1 : $index + 1;
        if (isset($sections[$index], $sections[$swap])) {
            [$sections[$index], $sections[$swap]] = [$sections[$swap], $sections[$index]];
            HomeSections::save($sections);
        }

        return back();
    }

    public function toggle(Request $request)
    {
        $sections = HomeSections::all();
        $index = (int) $request->input('index');
        if (isset($sections[$index])) {
            $sections[$index]['on'] = empty($sections[$index]['on']);
            HomeSections::save($sections);
        }

        return back();
    }

    public function addPage(Request $request)
    {
        $data = $request->validate(['page_id' => 'required|exists:pages,id', 'style' => 'nullable|in:plain,alt'], [], ['page_id' => 'الصفحة']);
        $sections = HomeSections::all();
        $sections[] = ['key' => 'page', 'on' => true, 'page_id' => (int) $data['page_id'], 'style' => $data['style'] ?? 'plain', 'show_title' => $request->boolean('show_title')];
        HomeSections::save($sections);

        return back()->with('status', 'تمت إضافة القسم في آخر الصفحة الرئيسية — رتّبه بالأسهم.');
    }

    public function removePage(Request $request)
    {
        $sections = HomeSections::all();
        $index = (int) $request->input('index');
        if (($sections[$index]['key'] ?? null) === 'page') {
            array_splice($sections, $index, 1);
            HomeSections::save($sections);
        }

        return back()->with('status', 'تم حذف القسم من الصفحة الرئيسية.');
    }
}
