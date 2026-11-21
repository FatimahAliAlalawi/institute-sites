@extends('layouts.admin')

@section('title', 'البرامج التدريبية')

@section('actions')
    <a href="{{ route('admin.programs.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> برنامج جديد</a>
@endsection

@section('content')
    <div class="tabs">
        <a href="{{ route('admin.programs.index') }}" @class(['active' => ! request('category')])>الكل</a>
        @foreach (\App\Models\Program::CATEGORIES as $key => $label)
            <a href="{{ route('admin.programs.index', ['category' => $key]) }}" @class(['active' => request('category') === $key])>{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.programs.index', ['plan' => 'approved']) }}" @class(['active' => request('plan') === 'approved'])>ضمن الخطة المعتمدة</a>
    </div>

    <div class="card">
        @if ($programs->isEmpty())
            <div class="empty">لا توجد برامج بعد. <a href="{{ route('admin.programs.create') }}">أضف أول برنامج</a></div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th></th><th>البرنامج</th><th>التصنيف</th><th>المدة</th><th>الطلبات / المقاعد</th><th>الحالة</th><th>الخطة / الإعلان</th><th>الترتيب</th><th></th></tr>
                    @foreach ($programs as $program)
                        <tr>
                            <td>@if ($program->image)<img class="thumb" src="{{ media_url($program->image) }}" alt="">@else<div class="thumb"></div>@endif</td>
                            <td>
                                <a href="{{ route('admin.programs.edit', $program) }}"><b>{{ $program->title }}</b></a>
                                @if ($program->is_featured)<span class="badge b-new">مميز</span>@endif
                                <div class="sub">{{ $program->price_label }}</div>
                            </td>
                            <td>{{ $program->category_label }}</td>
                            <td class="sub">{{ $program->duration }}</td>
                            <td>
                                <a href="{{ route('admin.registrations.index', ['program' => $program->id]) }}">{{ $program->registrations_count }} طلب</a>
                                <div class="sub">مسجّل {{ $program->seat_registrations_count }}{{ $program->seats ? ' من '.$program->seats : '' }}</div>
                                @if ($program->seats && $program->seat_registrations_count >= $program->seats)<span class="badge b-rejected">مكتملة</span>@endif
                            </td>
                            <td>
                                <span class="badge {{ $program->is_active ? 'b-on' : 'b-off' }}">{{ $program->is_active ? 'منشور' : 'مخفي' }}</span>
                                <span class="badge {{ $program->registration_open ? 'b-on' : 'b-off' }}">{{ $program->registration_open ? 'التسجيل مفتوح' : 'التسجيل مغلق' }}</span>
                            </td>
                            <td>
                                @foreach (['plan' => 'الخطة', 'ad' => 'الإعلان'] as $kind => $kindLabel)
                                    @php($st = $program->{$kind.'_status'} ?: 'none')
                                    @if ($st !== 'none')
                                        <span class="badge {{ $st === 'approved' ? 'b-on' : ($st === 'rejected' ? 'b-off' : 'b-new') }}">{{ $kindLabel }}: {{ $st === 'approved' ? 'معتمد' : ($st === 'rejected' ? 'مرفوض' : 'بانتظار الاعتماد') }}</span>
                                    @endif
                                @endforeach
                                @if ($program->start_date)<div class="sub">يبدأ {{ $program->start_date->format('Y-m-d') }}</div>@endif
                            </td>
                            <td>{{ $program->sort }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('programs.show', $program) }}" target="_blank" class="btn btn-sm" title="عرض"><i class="fa-regular fa-eye"></i></a>
                                    <a href="{{ route('admin.programs.ops', $program) }}" class="btn btn-sm" title="تشغيل الدورة (آلية التسجيل)"><i class="fa-solid fa-list-check"></i></a>
                                    <a href="{{ route('admin.programs.ad-card', $program) }}" target="_blank" class="btn btn-sm" title="بطاقة الإعلان و QR"><i class="fa-solid fa-bullhorn"></i></a>
                                    <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.programs.destroy', $program), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $programs->links() }}
        @endif
    </div>
@endsection
