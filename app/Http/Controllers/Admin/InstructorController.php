<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Support\Uploader;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function index()
    {
        return view('admin.instructors.index', ['instructors' => Instructor::withCount('courses')->orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.instructors.form', ['instructor' => new Instructor(['sort' => (int) Instructor::max('sort') + 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['photo'] = $request->hasFile('photo') ? Uploader::store($request->file('photo'), 'instructors') : null;
        Instructor::create($data);

        return redirect()->route('admin.instructors.index')->with('status', 'تمت إضافة المدرب.');
    }

    public function edit(Instructor $instructor)
    {
        return view('admin.instructors.form', compact('instructor'));
    }

    public function update(Request $request, Instructor $instructor)
    {
        $data = $this->validated($request);
        $data['photo'] = Uploader::replace($request->file('photo'), $instructor->photo, 'instructors', $request->boolean('remove_photo'));
        $instructor->update($data);

        return redirect()->route('admin.instructors.index')->with('status', 'تم حفظ بيانات المدرب.');
    }

    public function destroy(Instructor $instructor)
    {
        Uploader::delete($instructor->photo);
        $instructor->delete();

        return redirect()->route('admin.instructors.index')->with('status', 'تم حذف المدرب.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'title' => 'nullable|string|max:190',
            'bio' => 'nullable|string|max:5000',
            'sort' => 'nullable|integer',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [], ['photo' => 'الصورة', 'bio' => 'النبذة']);
        unset($data['photo']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
