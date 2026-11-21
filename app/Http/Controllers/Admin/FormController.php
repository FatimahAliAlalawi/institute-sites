<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\Setting;
use App\Support\FormBuilder;
use App\Support\Uploader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** منشئ النماذج: حقول ونصوص نماذج التسجيل وإنشاء الحساب والتواصل */
class FormController extends Controller
{
    public function show(string $form = 'registration')
    {
        abort_unless(isset(FormField::FORMS[$form]), 404);

        return view('admin.forms.index', [
            'form' => $form,
            'fields' => FormField::where('form', $form)->orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function updateTexts(Request $request, string $form)
    {
        abort_unless(isset(FormField::FORMS[$form]), 404);
        $data = $request->validate([
            'title' => 'nullable|string|max:190',
            'intro' => 'nullable|string|max:1000',
            'submit' => 'nullable|string|max:60',
            'success' => 'nullable|string|max:1000',
        ]);
        Setting::put(collect($data)->mapWithKeys(fn ($v, $k) => ['form_'.$form.'_'.$k => $v])->all());

        return back()->with('status', 'تم حفظ نصوص النموذج.');
    }

    public function store(Request $request, string $form)
    {
        abort_unless(isset(FormField::FORMS[$form]), 404);
        $data = $this->validated($request, null);
        $data += [
            'form' => $form,
            'key' => 'f_'.Str::lower(Str::random(8)),
            'is_system' => false,
            'is_active' => true,
            'sort' => (int) FormField::where('form', $form)->max('sort') + 1,
        ];
        FormField::create($data);

        return back()->with('status', 'تمت إضافة الحقل «'.$data['label'].'».');
    }

    public function edit(FormField $field)
    {
        return view('admin.forms.edit', compact('field'));
    }

    public function update(Request $request, FormField $field)
    {
        $data = $this->validated($request, $field);
        $field->update($data);

        return redirect()->route('admin.forms.show', $field->form)->with('status', 'تم حفظ الحقل «'.$field->label.'».');
    }

    public function destroy(FormField $field)
    {
        abort_if($field->is_system, 403, 'لا يمكن حذف حقل أساسي — يمكنك إخفاؤه بدلًا من ذلك.');
        $form = $field->form;
        $field->delete();

        return redirect()->route('admin.forms.show', $form)->with('status', 'تم حذف الحقل (الإجابات السابقة تبقى محفوظة في الطلبات).');
    }

    /** نقل الحقل لأعلى/لأسفل */
    public function move(Request $request, FormField $field)
    {
        $fields = FormField::where('form', $field->form)->orderBy('sort')->orderBy('id')->get()->values();
        $index = $fields->search(fn ($f) => $f->id === $field->id);
        $swap = $request->input('dir') === 'up' ? $index - 1 : $index + 1;
        if ($index !== false && isset($fields[$swap])) {
            $ordered = $fields->all();
            [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];
            foreach (array_values($ordered) as $i => $f) {
                $f->update(['sort' => $i + 1]);
            }
        }

        return redirect()->to(route('admin.forms.show', $field->form).'#field-'.$field->id);
    }

    /** إظهار/إخفاء سريع */
    public function toggle(FormField $field)
    {
        abort_if($field->is_locked, 403, 'هذا الحقل أساسي ولا يمكن إخفاؤه.');
        $field->update(['is_active' => ! $field->is_active]);

        return redirect()->to(route('admin.forms.show', $field->form).'#field-'.$field->id);
    }

    /** تنزيل ملف مرفوع عبر أحد النماذج (للمدير فقط) */
    public function file(Request $request)
    {
        $path = (string) $request->query('path');
        abort_unless(str_starts_with($path, 'private/forms/'), 404);
        $full = Uploader::privatePath($path);
        abort_unless($full, 404);

        return response()->download($full, $request->query('name') ?: basename($full));
    }

    protected function validated(Request $request, ?FormField $field): array
    {
        $isSystem = $field?->is_system ?? false;
        $types = array_keys(FormField::TYPES);

        $data = $request->validate([
            'label' => 'required|string|max:190',
            'type' => $isSystem ? 'nullable' : ['required', Rule::in($types)],
            'options' => 'nullable|string|max:5000',
            'placeholder' => 'nullable|string|max:190',
            'help' => 'nullable|string|max:500',
            'width' => 'required|in:half,full',
        ], [], ['label' => 'عنوان الحقل', 'type' => 'نوع الحقل', 'options' => 'الخيارات']);

        $type = $isSystem ? $field->type : $data['type'];
        if (in_array($type, FormField::OPTION_TYPES, true) && count(lines($data['options'] ?? '')) < 1) {
            abort(back()->withInput()->withErrors(['options' => 'أضف خيارًا واحدًا على الأقل (كل خيار في سطر).']));
        }

        $result = [
            'label' => $data['label'],
            'placeholder' => $data['placeholder'] ?? null,
            'help' => $data['help'] ?? null,
            'width' => $data['width'],
            'options' => in_array($type, FormField::OPTION_TYPES, true) ? implode("\n", lines($data['options'] ?? '')) : null,
        ];
        if (! $isSystem) {
            $result['type'] = $type;
        }
        // الحقول الأساسية المقفلة: تبقى إلزامية وظاهرة دائمًا
        if (! ($field?->is_locked)) {
            $result['required'] = $request->boolean('required');
            if ($field) {
                $result['is_active'] = $request->boolean('is_active');
            }
        }

        return $result;
    }
}
