<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Support\Uploader;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        return view('admin.partners.index', ['partners' => Partner::orderBy('sort')->get()]);
    }

    public function create()
    {
        return view('admin.partners.form', ['partner' => new Partner(['sort' => Partner::max('sort') + 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['logo'] = $request->hasFile('logo') ? Uploader::store($request->file('logo'), 'partners') : null;
        Partner::create($data);

        return redirect()->route('admin.partners.index')->with('status', 'تمت إضافة الشريك.');
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.form', compact('partner'));
    }

    public function update(Request $request, Partner $partner)
    {
        $data = $this->validated($request);
        $data['logo'] = Uploader::replace($request->file('logo'), $partner->logo, 'partners', $request->boolean('remove_logo'));
        $partner->update($data);

        return redirect()->route('admin.partners.index')->with('status', 'تم حفظ التعديلات.');
    }

    public function destroy(Partner $partner)
    {
        Uploader::delete($partner->logo);
        $partner->delete();

        return redirect()->route('admin.partners.index')->with('status', 'تم حذف الشريك.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'url' => 'nullable|url|max:255',
            'sort' => 'nullable|integer',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        unset($data['logo']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
