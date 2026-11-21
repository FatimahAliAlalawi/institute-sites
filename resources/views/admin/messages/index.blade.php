@extends('layouts.admin')

@section('title', 'رسائل التواصل')

@section('content')
    <div class="card">
        @if ($messages->isEmpty())
            <div class="empty">لا توجد رسائل بعد.</div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th>المرسل</th><th>الموضوع</th><th>التاريخ</th><th></th></tr>
                    @foreach ($messages as $m)
                        <tr @unless ($m->is_read) style="font-weight:700" @endunless>
                            <td>
                                <a href="{{ route('admin.messages.show', $m) }}">{{ $m->name }}</a>
                                @unless ($m->is_read)<span class="badge b-new">جديدة</span>@endunless
                                <div class="sub">{{ $m->phone ?: $m->email }}</div>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($m->subject ?: $m->body, 70) }}</td>
                            <td class="sub">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.messages.show', $m) }}" class="btn btn-sm">قراءة</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.messages.destroy', $m), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $messages->links() }}
        @endif
    </div>
@endsection
