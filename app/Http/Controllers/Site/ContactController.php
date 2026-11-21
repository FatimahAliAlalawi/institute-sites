<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Message;
use App\Support\FormBuilder;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('site.contact', ['fields' => FormField::for('contact')]);
    }

    public function store(Request $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('contact')->with('success', true);
        }

        // إن كان الجوال والبريد ظاهرين واختياريين: يلزم أحدهما على الأقل للرد
        $fields = FormField::for('contact')->keyBy('key');
        $rules = [];
        if (isset($fields['phone'], $fields['email']) && ! $fields['phone']->required && ! $fields['email']->required) {
            $rules = ['phone' => ['required_without:email'], 'email' => ['required_without:phone']];
        }

        [$data, $extra] = FormBuilder::validate($request, 'contact', $rules);

        Message::create(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'subject', 'body'])) + [
            'body' => $data['body'] ?? '',
            'extra' => $extra ?: null,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('contact')->with('success', true);
    }
}
