@extends('layouts.admin')

@section('title', 'المدربون')

@section('actions')
    <a href="{{ route('admin.instructors.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> مدرب جديد</a>
@endsection

@section('content')
    <div class="card">
        @if ($instructors->isEmpty())
            <div class="empty">لا يوجد مدربون بعد.</div>
        @else
            <table>
                <tr><th></th><th>الاسم</th><th>المسمى</th><th>الدورات</th><th></th></tr>
                @foreach ($instructors as $ins)
                    <tr>
                        <td>@if ($ins->photo)<img class="thumb" style="width:46px;height:46px;border-radius:50%" src="{{ media_url($ins->photo) }}" alt="">@endif</td>
                        <td><b>{{ $ins->name }}</b></td>
                        <td class="sub">{{ $ins->title }}</td>
                        <td>{{ $ins->courses_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.instructors.edit', $ins) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                <a href="{{ route('instructors.show', $ins) }}" target="_blank" class="btn btn-sm"><i class="fa-regular fa-eye"></i></a>
                                @include('admin.partials.delete-button', ['action' => route('admin.instructors.destroy', $ins), 'label' => ''])
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection
