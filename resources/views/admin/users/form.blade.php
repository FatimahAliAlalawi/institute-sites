@extends('layouts.admin')

@section('title', $user->exists ? 'تعديل مستخدم: '.$user->name : 'مستخدم جديد')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn">رجوع</a>
@endsection

@section('content')
    @php $isMe = $user->exists && $user->id === $currentAdmin->id; @endphp
    <form class="card" method="post" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" style="max-width:760px">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="form-grid">
            <div class="field"><label>الاسم *</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="field"><label>البريد الإلكتروني</label><input type="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr"></div>
            <div class="field">
                <label>اسم الدخول *</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" dir="ltr" required autocomplete="off">
                <div class="hint">بالإنجليزي بدون مسافات، مثل: sara.hr</div>
            </div>
            <div class="field">
                <label>كلمة المرور {{ $user->exists ? '' : '*' }}</label>
                <input type="text" name="password" dir="ltr" minlength="8" autocomplete="new-password" @required(! $user->exists) placeholder="{{ $user->exists ? 'اتركها فارغة لعدم التغيير' : '8 أحرف على الأقل' }}">
                <div class="hint">أرسلها للموظف، ويغيّرها من "حسابي".</div>
            </div>
            <div class="field full">
                <label>الدور (الصلاحيات)</label>
                <select name="role_id">
                    <option value="">— اختر —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }} — {{ implode('، ', array_map([\App\Support\Permissions::class, 'label'], (array) $role->permissions)) }}</option>
                    @endforeach
                </select>
                <div class="hint">تحتاج دورًا بصلاحيات مختلفة؟ <a href="{{ route('admin.roles.create') }}">أنشئ دورًا جديدًا</a>.</div>
            </div>
            <div class="field full checks">
                @if ($currentAdmin->is_super)
                    <label><input type="checkbox" name="is_super" value="1" @checked(old('is_super', $user->is_super)) @disabled($isMe)> مدير عام (كل الصلاحيات بما فيها إدارة المستخدمين)</label>
                @endif
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) @disabled($isMe)> الحساب فعّال</label>
                @if ($isMe)<input type="hidden" name="is_super" value="{{ $user->is_super ? 1 : 0 }}"><input type="hidden" name="is_active" value="1"><span class="sub">(لا يمكنك إيقاف حسابك أو تغيير صلاحيتك)</span>@endif
            </div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ</button></div>
    </form>
@endsection
