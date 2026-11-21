@extends('layouts.admin')

@section('title', $role->exists ? 'تعديل دور: '.$role->name : 'دور جديد')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn">رجوع</a>
@endsection

@section('content')
    @php $selected = old('permissions', (array) $role->permissions); @endphp
    <form class="card" method="post" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
        @csrf
        @if ($role->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field"><label>اسم الدور *</label><input type="text" name="name" value="{{ old('name', $role->name) }}" required placeholder="مثال: مشرف التدريب"></div>
            <div class="field"><label>وصف مختصر</label><input type="text" name="description" value="{{ old('description', $role->description) }}"></div>
        </div>

        <h2 style="margin-top:20px">الصلاحيات — الأقسام التي يراها هذا الدور</h2>
        @foreach (\App\Support\Permissions::GROUPS as $group => $items)
            <div style="border:1px solid var(--line);border-radius:12px;padding:12px 16px;margin-bottom:10px">
                <b>{{ $group }}</b>
                <div class="checks" style="margin-top:8px">
                    @foreach ($items as $key => $label)
                        <label style="font-weight:500"><input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selected, true))> {{ $label }}</label>
                    @endforeach
                </div>
            </div>
        @endforeach
        <p class="sub">الرئيسية و"حسابي" متاحة لكل المستخدمين. صلاحية "المستخدمون والصلاحيات" تسمح بإدارة الحسابات (عدا حسابات المدير العام).</p>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ الدور</button></div>
    </form>
@endsection
