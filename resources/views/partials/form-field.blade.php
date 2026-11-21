{{-- حقل نموذج ديناميكي: $field (FormField)، وللبرامج: $programs و $selected --}}
@php
    $name = $field->key;
    $value = old($name, $default ?? null);
    $required = $field->required || $field->is_locked;
    $ltr = in_array($field->type, ['email', 'tel', 'number', 'national_id'], true);
@endphp
<div @class(['w-full' => $field->width === 'full' || in_array($field->type, ['textarea', 'checkbox'], true)])>
    @if ($field->type !== 'password')
        <label for="f-{{ $name }}">{{ $field->label }} @if ($required)<span class="req">*</span>@endif</label>
    @endif

    @switch($field->type)
        @case('program')
            <select id="f-{{ $name }}" name="{{ $name }}" @required($required)>
                <option value="">تسجيل اهتمام عام (بدون تحديد برنامج)</option>
                @foreach ($programs->groupBy('category') as $cat => $items)
                    <optgroup label="{{ \App\Models\Program::CATEGORIES[$cat] ?? $cat }}">
                        @foreach ($items as $p)
                            <option value="{{ $p->id }}" @selected((int) old($name, $selected ?? 0) === $p->id)>{{ $p->title }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @break

        @case('textarea')
            <textarea id="f-{{ $name }}" name="{{ $name }}" placeholder="{{ $field->placeholder }}" maxlength="5000" @required($required)>{{ $value }}</textarea>
            @break

        @case('select')
            <select id="f-{{ $name }}" name="{{ $name }}" @required($required)>
                <option value="">{{ $field->placeholder ?: '— اختر —' }}</option>
                @foreach ($field->option_list as $option)
                    <option @selected($value === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @break

        @case('radio')
            <div class="radio-row">
                @foreach ($field->option_list as $option)
                    <label><input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($value === $option) @required($required && $loop->first)> {{ $option }}</label>
                @endforeach
            </div>
            @break

        @case('checkbox')
            <div class="radio-row" style="flex-wrap:wrap">
                @foreach ($field->option_list as $option)
                    <label><input type="checkbox" name="{{ $name }}[]" value="{{ $option }}" @checked(in_array($option, (array) $value, true))> {{ $option }}</label>
                @endforeach
            </div>
            @break

        @case('file')
            <input type="file" id="f-{{ $name }}" name="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" @required($required)>
            @break

        @case('password')
            <div class="row-2">
                <div>
                    <label for="f-{{ $name }}">{{ $field->label }} <span class="req">*</span></label>
                    <input type="password" id="f-{{ $name }}" name="{{ $name }}" required minlength="8" autocomplete="new-password">
                </div>
                <div>
                    <label for="f-{{ $name }}-c">تأكيد {{ $field->label }} <span class="req">*</span></label>
                    <input type="password" id="f-{{ $name }}-c" name="{{ $name }}_confirmation" required minlength="8" autocomplete="new-password">
                </div>
            </div>
            @break

        @default
            <input type="{{ ['email' => 'email', 'tel' => 'tel', 'number' => 'number', 'date' => 'date'][$field->type] ?? 'text' }}"
                   id="f-{{ $name }}" name="{{ $name }}" value="{{ is_array($value) ? '' : $value }}" placeholder="{{ $field->placeholder }}"
                   @if ($ltr) dir="ltr" style="text-align:right" @endif @if ($field->type === 'national_id') inputmode="numeric" maxlength="10" @endif
                   @if ($field->type === 'date' && $field->form === 'signup') max="{{ now()->subDay()->format('Y-m-d') }}" @endif @required($required)>
    @endswitch

    @if ($field->help)<div class="muted" style="font-size:13px;margin-top:4px">{{ $field->help }}</div>@endif
    @error($name)<div class="error">{{ $message }}</div>@enderror
    @if ($field->type === 'checkbox')@error($name.'.*')<div class="error">{{ $message }}</div>@enderror @endif
</div>
