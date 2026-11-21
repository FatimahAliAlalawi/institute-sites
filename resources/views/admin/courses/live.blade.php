@extends('layouts.admin')

@section('title', $live->exists ? 'محاضرة مباشرة: '.$live->title : 'محاضرة مباشرة جديدة — '.$topic->title)

@section('actions')
    <a href="{{ route('admin.courses.curriculum', $course) }}" class="btn">← رجوع للمنهج</a>
@endsection

@section('content')
    @if (! \App\Support\Zoom::configured())
        <div class="alert alert-error">ربط Zoom غير مضبوط — يمكنك لصق رابط الاجتماع يدويًا، أو أضف بيانات تطبيق Zoom من <a href="{{ route('admin.settings.edit') }}">الإعدادات</a> ليُنشأ الاجتماع تلقائيًا.</div>
    @endif

    <div class="cols">
        <form class="card" method="post" action="{{ $live->exists ? route('admin.live.update', $live) : route('admin.live.store', $topic) }}">
            @csrf
            @if ($live->exists) @method('PUT') @endif
            <div class="form-grid">
                <div class="field full"><label>عنوان المحاضرة *</label><input type="text" name="title" value="{{ old('title', $live->title) }}" required></div>
                <div class="field"><label>الموعد (بتوقيت السعودية) *</label><input type="datetime-local" name="starts_at" value="{{ old('starts_at', $live->starts_at?->format('Y-m-d\TH:i')) }}" required></div>
                <div class="field"><label>المدة (دقيقة) *</label><input type="number" name="duration" min="15" max="600" value="{{ old('duration', $live->duration ?? 60) }}" required></div>
                <div class="field full"><label>وصف / ملاحظات للطلاب</label><textarea name="description" style="min-height:80px">{{ old('description', $live->description) }}</textarea></div>
                @if (\App\Support\Zoom::configured() && ! $live->zoom_meeting_id)
                    <div class="field full checks"><label><input type="checkbox" name="create_zoom" value="1" @checked(old('create_zoom', true))> إنشاء اجتماع Zoom تلقائيًا</label></div>
                @endif
                <div class="field full">
                    <label>رابط الانضمام {{ $live->zoom_meeting_id ? '(من Zoom)' : '(يدوي — إن لم تستخدم الإنشاء التلقائي)' }}</label>
                    <input type="url" name="join_url" value="{{ old('join_url', $live->join_url) }}" dir="ltr" placeholder="https://zoom.us/j/..." @readonly($live->zoom_meeting_id)>
                </div>
                <div class="field"><label>رمز الدخول (اختياري)</label><input type="text" name="password" value="{{ old('password', $live->password) }}" dir="ltr" @readonly($live->zoom_meeting_id)></div>
                <div class="field"><label>الترتيب داخل الوحدة</label><input type="number" name="sort" value="{{ old('sort', $live->sort ?? 0) }}"></div>
                <div class="field full"><label>رابط التسجيل بعد المحاضرة (اختياري)</label><input type="url" name="recording_url" value="{{ old('recording_url', $live->recording_url) }}" dir="ltr" placeholder="رابط يوتيوب غير مدرج أو تسجيل Zoom السحابي"></div>
            </div>
            <div class="form-foot"><button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> حفظ</button></div>
        </form>

        @if ($live->exists)
            <div>
                <div class="card">
                    <h2>Zoom</h2>
                    @if ($live->zoom_meeting_id)
                        <p class="sub" style="margin-top:0">رقم الاجتماع: <b dir="ltr">{{ $live->zoom_meeting_id }}</b></p>
                        <a href="{{ route('admin.live.host', $live) }}" target="_blank" class="btn btn-primary"><i class="fa-solid fa-video"></i> ابدأ المحاضرة كمضيف</a>
                        <p class="sub">رابط المضيف خاص بالمدرب — لا تشاركه مع الطلاب.</p>
                    @elseif (\App\Support\Zoom::configured())
                        <form method="post" action="{{ route('admin.live.zoom', $live) }}">@csrf<button class="btn" type="submit"><i class="fa-solid fa-rotate"></i> إنشاء اجتماع Zoom الآن</button></form>
                    @else
                        <p class="sub">الرابط يدوي.</p>
                    @endif
                </div>
                <div class="card">
                    <h2>الحضور ({{ $attendees->count() }})</h2>
                    @forelse ($attendees as $a)
                        <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid var(--line)">
                            <span>{{ $a->user?->name }}</span><span class="sub">{{ $a->joined_at->format('g:i a') }}{{ $a->join_count > 1 ? ' ×'.$a->join_count : '' }}</span>
                        </div>
                    @empty
                        <div class="empty" style="padding:14px">لم ينضم أحد بعد.</div>
                    @endforelse
                </div>
                <div class="card">@include('admin.partials.delete-button', ['action' => route('admin.live.destroy', $live), 'label' => 'حذف المحاضرة', 'confirm' => 'حذف المحاضرة؟ سيُحذف اجتماع Zoom أيضًا.'])</div>
            </div>
        @endif
    </div>
@endsection
