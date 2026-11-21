@extends('layouts.admin')

@section('title', 'الصفحة الرئيسية')

@section('actions')
    <a href="{{ route('home') }}" target="_blank" class="btn"><i class="fa-regular fa-eye"></i> معاينة</a>
@endsection

@section('content')
    <div class="card">
        <h2>الأقسام وترتيبها</h2>
        <p class="sub" style="margin-top:-6px">رتّب الأقسام بالأسهم، وأخفِ ما لا تحتاجه. نصوص الواجهة والإحصائيات والصور تُعدّل من <a href="{{ route('admin.settings.edit') }}">إعدادات الموقع</a>.</p>
        <table>
            @foreach ($sections as $i => $section)
                <tr @if (empty($section['on'])) style="opacity:.5" @endif>
                    <td style="width:80px">
                        <div class="actions" style="flex-wrap:nowrap">
                            <form method="post" action="{{ route('admin.home.move') }}">@csrf<input type="hidden" name="index" value="{{ $i }}"><input type="hidden" name="dir" value="up"><button class="btn btn-sm" @disabled($loop->first)>▲</button></form>
                            <form method="post" action="{{ route('admin.home.move') }}">@csrf<input type="hidden" name="index" value="{{ $i }}"><input type="hidden" name="dir" value="down"><button class="btn btn-sm" @disabled($loop->last)>▼</button></form>
                        </div>
                    </td>
                    <td><b>{{ \App\Support\HomeSections::label($section) }}</b> @if ($section['key'] === 'page')<span class="badge b-new">قسم مخصص</span>@endif</td>
                    <td style="width:240px">
                        <div class="actions">
                            <form method="post" action="{{ route('admin.home.toggle') }}">@csrf<input type="hidden" name="index" value="{{ $i }}">
                                <button class="btn btn-sm" type="submit">{!! ! empty($section['on']) ? '<i class="fa-regular fa-eye"></i> ظاهر' : '<i class="fa-regular fa-eye-slash"></i> مخفي' !!}</button>
                            </form>
                            @if ($section['key'] === 'page')
                                <a class="btn btn-sm" href="{{ route('admin.pages.edit', $section['page_id'] ?? 0) }}">تعديل المحتوى</a>
                                <form method="post" action="{{ route('admin.home.remove') }}" data-confirm="حذف هذا القسم من الرئيسية؟ (الصفحة نفسها لا تُحذف)">@csrf<input type="hidden" name="index" value="{{ $i }}"><button class="btn btn-sm btn-danger" type="submit"><i class="fa-regular fa-trash-can"></i></button></form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <form class="card" method="post" action="{{ route('admin.home.add-page') }}">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> إضافة قسم جديد للرئيسية</h2>
        <p class="sub" style="margin-top:-6px">أنشئ <a href="{{ route('admin.pages.create') }}">صفحة</a> بالمحرر المرئي (نص، صور، فيديو، أزرار روابط)، ثم اعرض محتواها كقسم هنا.</p>
        <div class="actions">
            <select name="page_id" required style="max-width:300px">
                <option value="">— اختر صفحة —</option>
                @foreach ($pages as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
            </select>
            <select name="style" style="max-width:180px"><option value="plain">خلفية بيضاء</option><option value="alt">خلفية رمادية فاتحة</option></select>
            <label class="checks" style="font-weight:500"><input type="checkbox" name="show_title" value="1" checked> إظهار عنوان الصفحة</label>
            <button class="btn btn-primary" type="submit">إضافة القسم</button>
        </div>
    </form>

    <form class="card" method="post" action="{{ route('admin.home.update') }}">
        @csrf @method('PUT')
        <h2>عناوين الأقسام</h2>
        <div class="form-grid">
            @foreach (\App\Support\HomeSections::TITLED as $key)
                <div class="field full" style="border-bottom:1px dashed var(--line);padding-bottom:10px">
                    <label>{{ \App\Support\HomeSections::SECTIONS[$key] }}</label>
                    <div class="actions" style="flex-wrap:nowrap">
                        <input type="text" name="text[{{ $key }}][eyebrow]" value="{{ setting('home_'.$key.'_eyebrow') }}" placeholder="العنوان الصغير (اختياري)" style="max-width:260px">
                        @if ($key !== 'about')<input type="text" name="text[{{ $key }}][title]" value="{{ setting('home_'.$key.'_title') }}" placeholder="العنوان الرئيسي للقسم">@endif
                    </div>
                </div>
            @endforeach
        </div>
        <p class="sub">اترك الحقل فارغًا لاستخدام العنوان الافتراضي.</p>
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ العناوين</button></div>
    </form>
@endsection
