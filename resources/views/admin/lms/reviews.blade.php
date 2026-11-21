@extends('layouts.admin')

@section('title', 'تقييمات الدورات')

@section('content')
    <div class="card">
        @if ($reviews->isEmpty())
            <div class="empty">لا توجد تقييمات بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>الطالب</th><th>الدورة</th><th>التقييم</th><th>التعليق</th><th>الحالة</th><th></th></tr>
                    @foreach ($reviews as $r)
                        <tr>
                            <td>{{ $r->user?->name }}<div class="sub">{{ $r->created_at->format('Y-m-d') }}</div></td>
                            <td class="sub">{{ $r->course?->title }}</td>
                            <td style="color:#f5a524;white-space:nowrap">{{ str_repeat('★', $r->rating) }}<span style="color:#ddd">{{ str_repeat('★', 5 - $r->rating) }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit((string) $r->comment, 120) }}</td>
                            <td><span class="badge {{ $r->is_approved ? 'b-on' : 'b-off' }}">{{ $r->is_approved ? 'ظاهر' : 'مخفي' }}</span></td>
                            <td>
                                <div class="actions">
                                    <form method="post" action="{{ route('admin.reviews.update', $r) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="is_approved" value="{{ $r->is_approved ? 0 : 1 }}">
                                        <button class="btn btn-sm" type="submit">{{ $r->is_approved ? 'إخفاء' : 'إظهار' }}</button>
                                    </form>
                                    @include('admin.partials.delete-button', ['action' => route('admin.reviews.destroy', $r), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $reviews->links() }}
        @endif
    </div>
@endsection
