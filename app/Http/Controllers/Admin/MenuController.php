<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** القوائم: العلوية والتذييل */
class MenuController extends Controller
{
    public function index()
    {
        return view('admin.menus.index', [
            'items' => MenuItem::orderBy('sort')->orderBy('id')->get()->groupBy('location'),
            'pages' => Page::orderBy('title')->get(['id', 'title', 'is_published']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort'] = (int) MenuItem::where('location', $data['location'])->max('sort') + 1;
        MenuItem::create($data);

        return back()->with('status', 'تمت إضافة الرابط «'.$data['label'].'».');
    }

    public function update(Request $request, MenuItem $menu)
    {
        $menu->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'تم حفظ الرابط.');
    }

    public function move(Request $request, MenuItem $menu)
    {
        $items = MenuItem::where('location', $menu->location)->orderBy('sort')->orderBy('id')->get()->values();
        $index = $items->search(fn ($i) => $i->id === $menu->id);
        $swap = $request->input('dir') === 'up' ? $index - 1 : $index + 1;
        if ($index !== false && isset($items[$swap])) {
            $ordered = $items->all();
            [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];
            foreach (array_values($ordered) as $i => $item) {
                $item->update(['sort' => $i + 1]);
            }
        }

        return back();
    }

    public function destroy(MenuItem $menu)
    {
        $menu->delete();

        return back()->with('status', 'تم حذف الرابط من القائمة.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'location' => ['required', Rule::in(array_keys(MenuItem::LOCATIONS))],
            'label' => 'required|string|max:60',
            'type' => 'required|in:route,page,url',
            'route' => ['nullable', 'required_if:type,route', Rule::in(array_keys(MenuItem::ROUTES))],
            'page_id' => 'nullable|required_if:type,page|exists:pages,id',
            'url' => ['nullable', 'required_if:type,url', 'max:500', 'regex:#^(https?://|/|mailto:|tel:)#i'],
        ], ['url.regex' => 'الرابط يجب أن يبدأ بـ https:// أو / أو mailto: أو tel:'], ['label' => 'النص', 'url' => 'الرابط', 'page_id' => 'الصفحة', 'route' => 'صفحة الموقع']);

        return [
            'location' => $data['location'],
            'label' => $data['label'],
            'type' => $data['type'],
            'target' => (string) match ($data['type']) {
                'route' => $data['route'],
                'page' => $data['page_id'],
                default => $data['url'],
            },
            'new_tab' => $request->boolean('new_tab'),
        ];
    }
}
