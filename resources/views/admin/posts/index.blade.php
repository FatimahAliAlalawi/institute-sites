@extends('layouts.admin')

@section('title', 'الأخبار')

@section('actions')
    <a href="{{ route('admin.posts.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> خبر جديد</a>
@endsection

@section('content')
    <div class="card">
        @if ($posts->isEmpty())
            <div class="empty">لا توجد أخبار بعد. <a href="{{ route('admin.posts.create') }}">أضف أول خبر</a></div>
        @else
            <div class="table-wrap">
                <table>
                    <tr><th></th><th>العنوان</th><th>تاريخ النشر</th><th>الحالة</th><th></th></tr>
                    @foreach ($posts as $post)
                        <tr>
                            <td>@if ($post->image)<img class="thumb" src="{{ media_url($post->image) }}" alt="">@else<div class="thumb"></div>@endif</td>
                            <td><a href="{{ route('admin.posts.edit', $post) }}"><b>{{ $post->title }}</b></a></td>
                            <td class="sub">{{ $post->date?->format('Y-m-d') }}</td>
                            <td><span class="badge {{ $post->is_published ? 'b-on' : 'b-off' }}">{{ $post->is_published ? 'منشور' : 'مسودة' }}</span></td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('news.show', $post) }}" target="_blank" class="btn btn-sm"><i class="fa-regular fa-eye"></i></a>
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm"><i class="fa-regular fa-pen-to-square"></i> تعديل</a>
                                    @include('admin.partials.delete-button', ['action' => route('admin.posts.destroy', $post), 'label' => ''])
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            {{ $posts->links() }}
        @endif
    </div>
@endsection
