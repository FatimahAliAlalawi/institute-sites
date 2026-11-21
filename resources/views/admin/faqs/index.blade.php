@extends('layouts.admin')

@section('title', 'الأسئلة الشائعة')

@section('actions')
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> سؤال جديد</a>
@endsection

@section('content')
    <div class="card">
        @if ($faqs->isEmpty())
            <div class="empty">لا توجد أسئلة بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>السؤال</th><th>الحالة</th><th>الترتيب</th><th></th></tr>
                    @foreach ($faqs as $faq)
                        <tr>
                            <td><b>{{ $faq->question }}</b><div class="sub">{{ \Illuminate\Support\Str::limit($faq->answer, 110) }}</div></td>
                            <td><span class="badge {{ $faq->is_active ? 'b-on' : 'b-off' }}">{{ $faq->is_active ? 'ظاهر' : 'مخفي' }}</span></td>
                            <td>{{ $faq->sort }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.faqs.destroy', $faq), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    </div>
@endsection
