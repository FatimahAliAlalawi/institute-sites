{{-- مدخلات إعداد حقل (إضافة/تعديل): $field --}}
@php
    $editingSystem = $field->exists && $field->is_system;
    $type = old('type', $field->type);
@endphp
<div class="form-grid">
    <div class="field">
        <label>عنوان الحقل (يظهر للزائر) *</label>
        <input type="text" name="label" value="{{ old('label', $field->label) }}" required placeholder="مثال: رقم الهوية الوطنية">
    </div>
    <div class="field">
        <label>نوع الحقل</label>
        @if ($editingSystem)
            <input type="text" value="{{ $field->type_label }}" disabled>
            <div class="hint">نوع الحقول الأساسية ثابت.</div>
        @else
            <select name="type" onchange="this.closest('form').dataset.type = this.value">
                @foreach (\App\Models\FormField::TYPES as $key => $label)
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </div>
    @if (! $editingSystem || in_array($field->type, \App\Models\FormField::OPTION_TYPES, true))
        <div class="field full opt-only">
            <label>الخيارات (كل خيار في سطر) *</label>
            <textarea name="options" placeholder="الخيار الأول&#10;الخيار الثاني">{{ old('options', $field->options) }}</textarea>
            @error('options')<div class="error">{{ $message }}</div>@enderror
        </div>
    @endif
    <div class="field">
        <label>نص توضيحي داخل الحقل (اختياري)</label>
        <input type="text" name="placeholder" value="{{ old('placeholder', $field->placeholder) }}">
    </div>
    <div class="field">
        <label>ملاحظة تحت الحقل (اختياري)</label>
        <input type="text" name="help" value="{{ old('help', $field->help) }}">
    </div>
    <div class="field">
        <label>العرض</label>
        <select name="width">
            <option value="half" @selected(old('width', $field->width) === 'half')>نصف السطر</option>
            <option value="full" @selected(old('width', $field->width) === 'full')>سطر كامل</option>
        </select>
    </div>
    <div class="field checks" style="align-self:end">
        @if ($field->is_locked)
            <span class="sub">🔒 حقل أساسي: إلزامي وظاهر دائمًا.</span>
        @else
            <label><input type="checkbox" name="required" value="1" @checked(old('required', $field->required))> إلزامي</label>
            @if ($field->exists)
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $field->is_active))> ظاهر في النموذج</label>
            @endif
        @endif
    </div>
</div>
@unless ($editingSystem)
    <style>
        form:not([data-type="select"]):not([data-type="radio"]):not([data-type="checkbox"]) .opt-only { display: none; }
    </style>
@endunless
