<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/** شريط الإعلان أعلى الموقع (عروض، تسجيل مفتوح، كوبونات...) */
class MarketingController extends Controller
{
    public function edit()
    {
        return view('admin.marketing.announcement');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'announcement_text' => 'nullable|string|max:300',
            'announcement_button' => 'nullable|string|max:40',
            'announcement_link' => ['nullable', 'max:500', 'regex:#^(https?://|/)#i'],
            'announcement_bg' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'announcement_ends_at' => 'nullable|date',
        ], ['announcement_link.regex' => 'الرابط يجب أن يبدأ بـ https:// أو /'], ['announcement_text' => 'نص الإعلان']);
        $data['announcement_enabled'] = $request->boolean('announcement_enabled') ? '1' : '0';
        Setting::put($data);

        return back()->with('status', 'تم حفظ شريط الإعلان.');
    }
}
