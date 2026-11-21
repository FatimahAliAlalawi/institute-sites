<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use Illuminate\Http\Request;

class CourseCategoryController extends Controller
{
    public function index()
    {
        return view('admin.course-categories.index', ['categories' => CourseCategory::withCount('courses')->orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        CourseCategory::create($this->validated($request));

        return back()->with('status', 'تمت إضافة التصنيف.');
    }

    public function update(Request $request, CourseCategory $courseCategory)
    {
        $courseCategory->update($this->validated($request));

        return back()->with('status', 'تم حفظ التصنيف.');
    }

    public function destroy(CourseCategory $courseCategory)
    {
        $courseCategory->delete();

        return back()->with('status', 'تم حذف التصنيف (الدورات بقيت بدون تصنيف).');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'sort' => 'nullable|integer'], [], ['name' => 'اسم التصنيف']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
