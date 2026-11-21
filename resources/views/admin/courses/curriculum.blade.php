@extends('layouts.admin')

@section('title', 'منهج: '.$course->title)

@section('actions')
    <a href="{{ route('admin.courses.edit', $course) }}" class="btn"><i class="fa-regular fa-pen-to-square"></i> بيانات الدورة</a>
    <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> عرض</a>
@endsection

@section('content')
    @unless ($course->is_published)
        <div class="alert alert-error">الدورة <b>مسودة</b> ولا تظهر للطلاب — فعّل "منشورة" من <a href="{{ route('admin.courses.edit', $course) }}">بيانات الدورة</a> عند الجاهزية.</div>
    @endunless

    <div class="card" style="background:#faf9fd">
        <b>طريقة البناء:</b> أضف <b>وحدة</b> (مثل: الأسبوع الأول)، ثم أضف داخلها <b>دروسًا</b> (فيديو + نص + مرفق) و<b>اختبارات</b>. الطالب يحصل على الشهادة بعد إكمال كل الدروس واجتياز كل الاختبارات.
    </div>

    @forelse ($curriculum as $topic)
        <div class="card">
            <div class="top" style="margin-bottom:12px">
                <form method="post" action="{{ route('admin.topics.update', $topic) }}" class="actions" style="flex:1">
                    @csrf @method('PUT')
                    <span class="badge b-new">وحدة {{ $loop->iteration }}</span>
                    <input type="text" name="title" value="{{ $topic->title }}" style="max-width:380px;font-weight:700" required>
                    <input type="number" name="sort" value="{{ $topic->sort }}" style="width:80px" title="الترتيب">
                    <button class="btn btn-sm" type="submit">حفظ</button>
                </form>
                @include('admin.partials.delete-button', ['action' => route('admin.topics.destroy', $topic), 'label' => 'حذف الوحدة', 'confirm' => 'سيتم حذف الوحدة وكل دروسها واختباراتها. متأكد؟'])
            </div>

            @if ($topic->items->isEmpty())
                <div class="empty" style="padding:16px">الوحدة فارغة — أضف درسًا أو اختبارًا.</div>
            @else
                <table>
                    @foreach ($topic->items as $item)
                        @php($m = $item['model'])
                        <tr>
                            <td style="width:40px"><i class="fa-regular {{ $item['type'] === 'live' ? 'fa-calendar-check' : ($item['type'] === 'quiz' ? 'fa-circle-question' : ($m->video_url ? 'fa-circle-play' : 'fa-file-lines')) }}" style="color:var(--accent);font-size:18px"></i></td>
                            <td>
                                <b>{{ $m->title }}</b>
                                <div class="sub">
                                    @if ($item['type'] === 'live')
                                        محاضرة مباشرة · {{ $m->starts_at->translatedFormat('D j M Y — g:i a') }} · {{ $m->duration }} د
                                        {!! $m->zoom_meeting_id ? '<span class="badge b-on">Zoom</span>' : ($m->join_url ? '<span class="badge b-off">رابط يدوي</span>' : '<span class="badge b-rejected">بدون رابط</span>') !!}
                                    @elseif ($item['type'] === 'quiz')
                                        اختبار · {{ $m->questions()->count() }} سؤال · النجاح {{ $m->pass_mark }}%
                                    @else
                                        درس {{ $m->video_url ? '· فيديو' : '' }} {{ $m->attachment ? '· مرفق' : '' }} {{ $m->duration ? '· '.$m->duration : '' }}
                                        @if ($m->is_preview)<span class="badge b-on">معاينة مجانية</span>@endif
                                    @endif
                                </div>
                            </td>
                            <td class="sub" style="width:60px">#{{ $m->sort }}</td>
                            <td style="width:220px">
                                <div class="actions">
                                    @if ($item['type'] === 'live')
                                        <a class="btn btn-sm" href="{{ route('admin.live.edit', $m) }}"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                        @include('admin.partials.delete-button', ['action' => route('admin.live.destroy', $m), 'label' => ''])
                                    @elseif ($item['type'] === 'quiz')
                                        <a class="btn btn-sm" href="{{ route('admin.quizzes.edit', $m) }}"><i class="fa-regular fa-pen-to-square"></i> الأسئلة</a>
                                        @include('admin.partials.delete-button', ['action' => route('admin.quizzes.destroy', $m), 'label' => ''])
                                    @else
                                        <a class="btn btn-sm" href="{{ route('admin.lessons.edit', $m) }}"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                        @include('admin.partials.delete-button', ['action' => route('admin.lessons.destroy', $m), 'label' => ''])
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <div class="actions" style="margin-top:14px">
                <a href="{{ route('admin.lessons.create', $topic) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> درس جديد</a>
                <a href="{{ route('admin.live.create', $topic) }}" class="btn btn-sm"><i class="fa-solid fa-video"></i> محاضرة مباشرة (Zoom)</a>
                <form method="post" action="{{ route('admin.quizzes.store', $topic) }}" class="actions">
                    @csrf
                    <input type="text" name="title" placeholder="عنوان الاختبار" required style="width:220px">
                    <button class="btn btn-sm" type="submit"><i class="fa-solid fa-plus"></i> اختبار جديد</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card empty">لا توجد وحدات بعد — ابدأ بإضافة أول وحدة.</div>
    @endforelse

    <form class="card" method="post" action="{{ route('admin.topics.store', $course) }}">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> إضافة وحدة</h2>
        <div class="form-grid">
            <div class="field"><label>عنوان الوحدة *</label><input type="text" name="title" required placeholder="مثال: الوحدة الأولى — المقدمة"></div>
            <div class="field"><label>وصف مختصر</label><input type="text" name="summary"></div>
        </div>
        <div class="form-foot"><button class="btn btn-primary" type="submit">إضافة الوحدة</button></div>
    </form>
@endsection
