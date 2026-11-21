@extends('site.dashboard.layout')

@section('title', 'الملف الشخصي')

@section('dash')
    @php($me = auth()->user())
    <div class="form-card">
        <form class="form" method="post" enctype="multipart/form-data" action="{{ route('dashboard.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="row-2">
                <div>
                    <label for="name">الاسم الكامل (يظهر في الشهادة)</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $me->name) }}" required>
                    @error('name')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="phone">رقم الجوال</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $me->phone) }}" dir="ltr" style="text-align:right">
                    @error('phone')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label for="national_id">رقم الهوية / الإقامة</label>
                    <input type="text" id="national_id" name="national_id" value="{{ old('national_id', $me->national_id) }}" dir="ltr" style="text-align:right" maxlength="10" inputmode="numeric">
                    @error('national_id')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="birth_date">تاريخ الميلاد</label>
                    <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $me->birth_date?->format('Y-m-d')) }}">
                    @error('birth_date')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row-2">
                <div>
                    <label for="gender">الجنس</label>
                    <select id="gender" name="gender"><option value="">—</option>@foreach (\App\Models\User::GENDERS as $g)<option @selected(old('gender', $me->gender) === $g)>{{ $g }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="qualification">المؤهل الدراسي</label>
                    <input type="text" id="qualification" name="qualification" value="{{ old('qualification', $me->qualification) }}">
                </div>
            </div>
            <div>
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" value="{{ old('email', $me->email) }}" required dir="ltr" style="text-align:right">
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div>
                <label for="avatar">الصورة الشخصية</label>
                <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp">
                @error('avatar')<div class="error">{{ $message }}</div>@enderror
                @if ($me->avatar)
                    <label class="check-row" style="margin-top:8px"><img class="avatar" src="{{ media_url($me->avatar) }}" alt=""> <input type="checkbox" name="remove_avatar" value="1"> حذف الصورة</label>
                @endif
            </div>
            <hr style="border:0;border-top:1px solid var(--line);width:100%">
            <b>تغيير كلمة المرور (اختياري)</b>
            <div class="row-2">
                <div>
                    <label for="password">كلمة المرور الجديدة</label>
                    <input type="password" id="password" name="password" autocomplete="new-password">
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="password_confirmation">تأكيدها</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                </div>
            </div>
            <div>
                <label for="current_password">كلمة المرور الحالية (مطلوبة عند تغيير كلمة المرور)</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                @error('current_password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div><button class="btn btn-primary" type="submit">حفظ التغييرات</button></div>
        </form>
    </div>
@endsection
