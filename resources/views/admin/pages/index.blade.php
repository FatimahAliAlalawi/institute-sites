@extends('layouts.admin')

@section('title', 'الصفحات')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> صفحة جديدة</a>
@endsection

@section('content')
    <div class="card">
        <p class="sub" style="margin-top:0">أنشئ صفحات جديدة (سياسة الخصوصية، الشروط والأحكام، الاعتمادات، التوظيف...) بمحرر مرئي، ثم أضفها للقائمة من <a href="{{ route('admin.menus.index') }}">القوائم</a> أو كقسم في <a href="{{ route('admin.home.edit') }}">الصفحة الرئيسية</a>.</p>
        @if ($pages->isEmpty())
            <div class="empty">لا توجد صفحات مخصصة بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الصفحة</th><th>الرابط</th><th>الحالة</th><th>آخر تعديل</th><th></th></tr>
                    @foreach ($pages as $p)
                        <tr>
                            <td><a href="{{ route('admin.pages.edit', $p) }}"><b>{{ $p->title }}</b></a></td>
                            <td class="sub" dir="ltr" style="text-align:right">/p/{{ $p->slug }}</td>
                            <td><span class="badge {{ $p->is_published ? 'b-on' : 'b-off' }}">{{ $p->is_published ? 'منشورة' : 'مسودة' }}</span></td>
                            <td class="sub">{{ $p->updated_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('pages.show', $p) }}" target="_blank" class="btn btn-sm"><i class="fa-regular fa-eye"></i></a>
                                    <a href="{{ route('admin.pages.edit', $p) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.pages.destroy', $p), 'label' => '', 'confirm' => 'حذف الصفحة؟ ستُزال من القوائم أيضًا.'])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
