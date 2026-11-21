@extends('layouts.admin')

@section('title', 'تصنيفات الدورات')

@section('content')
    <form class="card" method="post" action="{{ route('admin.course-categories.store') }}">
        @csrf
        <h2><i class="fa-solid fa-plus"></i> تصنيف جديد</h2>
        <div class="actions">
            <input type="text" name="name" placeholder="اسم التصنيف (مثال: مهارات التوظيف)" required style="max-width:360px">
            <input type="number" name="sort" placeholder="الترتيب" style="width:100px">
            <button class="btn btn-primary" type="submit">إضافة</button>
        </div>
    </form>

    <div class="card">
        @if ($categories->isEmpty())
            <div class="empty">لا توجد تصنيفات.</div>
        @else
            <table>
                <tr><th>الاسم</th><th>الترتيب</th><th>الدورات</th><th></th></tr>
                @foreach ($categories as $cat)
                    <tr>
                        <td colspan="2">
                            <form method="post" action="{{ route('admin.course-categories.update', $cat) }}" class="actions" id="cat{{ $cat->id }}">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $cat->name }}" required style="max-width:320px">
                                <input type="number" name="sort" value="{{ $cat->sort }}" style="width:90px">
                                <button class="btn btn-sm" type="submit">حفظ</button>
                            </form>
                        </td>
                        <td><a href="{{ route('admin.courses.index', ['category' => $cat->id]) }}">{{ $cat->courses_count }}</a></td>
                        <td>@include('admin.partials.delete-button', ['action' => route('admin.course-categories.destroy', $cat), 'label' => ''])</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
