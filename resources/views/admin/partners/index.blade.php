@extends('layouts.admin')

@section('title', 'الشركاء')

@section('actions')
    <a href="{{ route('admin.partners.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> شريك جديد</a>
@endsection

@section('content')
    <div class="card">
        @if ($partners->isEmpty())
            <div class="empty">لا يوجد شركاء بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الشعار</th><th>الاسم</th><th>الرابط</th><th>الترتيب</th><th></th></tr>
                    @foreach ($partners as $partner)
                        <tr>
                            <td>@if ($partner->logo)<img class="thumb" style="object-fit:contain" src="{{ media_url($partner->logo) }}" alt="">@else<span class="sub">بدون شعار</span>@endif</td>
                            <td><b>{{ $partner->name }}</b></td>
                            <td class="sub">{{ $partner->url }}</td>
                            <td>{{ $partner->sort }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.partners.edit', $partner) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.partners.destroy', $partner), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
