@extends('layouts.admin')

@section('title', 'إعدادات الموقع')

@section('content')
    <div class="tabs">
        @foreach ($groups as $group => $fields)
            <a href="#g{{ $loop->index }}">{{ $group }}</a>
        @endforeach
    </div>

    <form method="post" enctype="multipart/form-data" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $fields)
            <div class="card settings-group" id="g{{ $loop->index }}">
                <h2>{{ $group }}</h2>
                <div class="form-grid">
                    @foreach ($fields as $key => $field)
                        @php
                            [$type, $label] = $field;
                            $hint = $field[2] ?? null;
                        @endphp
                        @if ($type === 'days')
                            @php $selectedDays = \App\Support\DailyRelease::globalDays(); @endphp
                            <div class="field full" id="study">
                                <label>{{ $label }}</label>
                                <div class="checks">
                                    @foreach (\App\Support\DailyRelease::DAYS as $num => $dayName)
                                        <label><input type="checkbox" name="{{ $key }}[]" value="{{ $num }}" @checked(in_array($num, $selectedDays, true))> {{ $dayName }}</label>
                                    @endforeach
                                </div>
                                @if ($hint)<div class="hint">{{ $hint }}</div>@endif
                            </div>
                        @elseif ($type === 'secret')
                            <div class="field">
                                <label for="{{ $key }}">{{ $label }}</label>
                                <input type="password" id="{{ $key }}" name="{{ $key }}" value="" dir="ltr" autocomplete="new-password" placeholder="{{ setting($key) ? '•••••••• محفوظ ('.substr((string) setting($key), 0, 8).'…)' : 'غير مضبوط' }}">
                                @if ($hint)<div class="hint">{{ $hint }}</div>@endif
                            </div>
                        @elseif ($type === 'bool')
                            <div class="field full checks">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <label><input type="checkbox" name="{{ $key }}" value="1" @checked(setting($key) === '1')> {{ $label }}</label>
                            </div>
                        @elseif ($type === 'image')
                            @include('admin.partials.image-field', ['name' => $key, 'label' => $label, 'value' => setting($key), 'hint' => $hint])
                        @else
                            <div @class(['field', 'full' => $type === 'textarea'])>
                                <label for="{{ $key }}">{{ $label }}</label>
                                @if ($type === 'textarea')
                                    <textarea id="{{ $key }}" name="{{ $key }}" @if (str_starts_with($key, 'msg_')) rows="6" @endif>{{ old($key, setting($key, str_starts_with($key, 'msg_') ? (\App\Support\TraineeMessages::DEFAULTS[substr($key, 4)] ?? '') : null)) }}</textarea>
                                @elseif ($type === 'color')
                                    <div class="actions">
                                        <input type="color" id="{{ $key }}" value="{{ old($key, setting($key, '#3F3592')) }}" oninput="this.nextElementSibling.value=this.value">
                                        <input type="text" name="{{ $key }}" value="{{ old($key, setting($key, '#3F3592')) }}" style="width:120px" dir="ltr" oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value))this.previousElementSibling.value=this.value">
                                    </div>
                                @else
                                    <input type="{{ in_array($type, ['url', 'email']) ? $type : 'text' }}" id="{{ $key }}" name="{{ $key }}" value="{{ old($key, setting($key)) }}" @if (in_array($type, ['url', 'email'])) dir="ltr" @endif>
                                @endif
                                @if ($hint)<div class="hint">{{ $hint }}</div>@endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="form-foot sticky-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ كل الإعدادات</button>
        </div>
    </form>

    <form class="card" method="post" action="{{ route('admin.settings.test-mail') }}" id="mail">
        @csrf
        <h2>تجربة البريد</h2>
        <p class="sub" style="margin-top:0">بعد حفظ إعدادات البريد، أرسل رسالة تجربة لبريدك للتأكد إنها تشتغل.</p>
        <div class="actions">
            <input type="email" name="to" value="{{ old('to', setting('email')) }}" dir="ltr" placeholder="your@email.com" required style="min-width:240px">
            <button class="btn" type="submit"><i class="fa-regular fa-paper-plane"></i> إرسال تجربة</button>
        </div>
    </form>
@endsection
