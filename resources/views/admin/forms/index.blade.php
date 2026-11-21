@extends('layouts.admin')

@section('title', 'النماذج — '.\App\Models\FormField::FORMS[$form])

@section('actions')
    <a href="{{ route(['registration' => 'register', 'signup' => 'signup', 'contact' => 'contact'][$form]) }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> معاينة النموذج</a>
@endsection

@section('content')
    <div class="tabs">
        @foreach (\App\Models\FormField::FORMS as $key => $label)
            <a href="{{ route('admin.forms.show', $key) }}" @class(['active' => $form === $key])>{{ $label }}</a>
        @endforeach
    </div>

    <form class="card" method="post" action="{{ route('admin.forms.texts', $form) }}">
        @csrf
        <h2>نصوص النموذج</h2>
        <div class="form-grid">
            <div class="field"><label>عنوان الصفحة</label><input type="text" name="title" value="{{ \App\Support\FormBuilder::text($form, 'title') }}"></div>
            <div class="field"><label>نص زر الإرسال</label><input type="text" name="submit" value="{{ \App\Support\FormBuilder::text($form, 'submit') }}"></div>
            <div class="field full"><label>النص التعريفي تحت العنوان</label><input type="text" name="intro" value="{{ \App\Support\FormBuilder::text($form, 'intro') }}"></div>
            <div class="field full"><label>رسالة الشكر بعد الإرسال</label><textarea name="success" style="min-height:70px">{{ \App\Support\FormBuilder::text($form, 'success') }}</textarea></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ النصوص</button></div>
    </form>

    <div class="card">
        <h2>حقول النموذج</h2>
        <p class="sub" style="margin-top:-6px">رتّب الحقول بالأسهم، واضغط "تعديل" لتغيير العنوان أو الخيارات أو جعل الحقل إلزاميًا. الحقول التي عليها 🔒 أساسية ولا يمكن إخفاؤها.</p>
        <div class="table-wrap">
            <table>
                <tr><th style="width:70px">الترتيب</th><th>الحقل</th><th>النوع</th><th>إلزامي</th><th>الحالة</th><th></th></tr>
                @foreach ($fields as $f)
                    <tr id="field-{{ $f->id }}" @unless ($f->is_active) style="opacity:.55" @endunless>
                        <td>
                            <div class="actions" style="flex-wrap:nowrap">
                                <form method="post" action="{{ route('admin.form-fields.move', $f) }}">@csrf<input type="hidden" name="dir" value="up"><button class="btn btn-sm" type="submit" title="لأعلى" @disabled($loop->first)>▲</button></form>
                                <form method="post" action="{{ route('admin.form-fields.move', $f) }}">@csrf<input type="hidden" name="dir" value="down"><button class="btn btn-sm" type="submit" title="لأسفل" @disabled($loop->last)>▼</button></form>
                            </div>
                        </td>
                        <td>
                            <b>{{ $f->label }}</b> @if ($f->is_locked)🔒@endif
                            @if ($f->option_list)<div class="sub">{{ \Illuminate\Support\Str::limit(implode(' · ', $f->option_list), 90) }}</div>@endif
                            @if ($f->help)<div class="sub">{{ $f->help }}</div>@endif
                        </td>
                        <td class="sub">{{ $f->type_label }} @unless ($f->is_system)<span class="badge b-new">إضافي</span>@endunless</td>
                        <td>{!! ($f->required || $f->is_locked) ? '<span class="badge b-accepted">إلزامي</span>' : '<span class="sub">اختياري</span>' !!}</td>
                        <td>
                            @if ($f->is_locked)
                                <span class="badge b-on">ظاهر</span>
                            @else
                                <form method="post" action="{{ route('admin.form-fields.toggle', $f) }}">@csrf
                                    <button class="btn btn-sm" type="submit">{!! $f->is_active ? '<i class="fa-regular fa-eye"></i> ظاهر' : '<i class="fa-regular fa-eye-slash"></i> مخفي' !!}</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.form-fields.edit', $f) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                @unless ($f->is_system)
                                    @include('admin.partials.delete-button', ['action' => route('admin.form-fields.destroy', $f), 'label' => '', 'confirm' => 'حذف هذا الحقل؟ الإجابات السابقة تبقى محفوظة.'])
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <form class="card" method="post" action="{{ route('admin.forms.fields.store', $form) }}" data-type="{{ old('type', 'text') }}">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> إضافة حقل جديد</h2>
        @include('admin.forms.field-inputs', ['field' => new \App\Models\FormField(['type' => old('type', 'text'), 'width' => 'half'])])
        <div class="form-foot"><button class="btn btn-primary" type="submit">إضافة الحقل</button></div>
    </form>
@endsection
