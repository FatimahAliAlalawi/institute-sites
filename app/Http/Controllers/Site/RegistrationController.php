<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Program;
use App\Models\Registration;
use App\Support\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    public function create(Request $request)
    {
        return view('site.register', [
            'fields' => FormField::for('registration'),
            'programs' => Program::active()->where('registration_open', true)->ordered()->get(),
            'selected' => (int) $request->query('program'),
        ]);
    }

    public function store(Request $request)
    {
        // حقل مخفي لصدّ البرامج الآلية (spam)
        if (filled($request->input('website'))) {
            return redirect()->route('register')->with('success', true);
        }

        [$data, $extra] = FormBuilder::validate($request, 'registration', [
            'program_id' => [Rule::exists('programs', 'id')->where('is_active', true)->where('registration_open', true)],
        ]);

        $columns = ['program_id', 'name', 'phone', 'email', 'city', 'gender', 'qualification', 'employment_status', 'notes'];
        Registration::create(array_intersect_key($data, array_flip($columns)) + [
            'extra' => $extra ?: null,
            'status' => 'new',
            'source' => 'الموقع',
            'follow_up_at' => now()->toDateString(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('register')->with('success', true);
    }
}
