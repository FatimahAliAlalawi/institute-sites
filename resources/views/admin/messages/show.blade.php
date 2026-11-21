@extends('layouts.admin')

@section('title', 'رسالة من '.$message->name)

@section('actions')
    <a href="{{ route('admin.messages.index') }}" class="btn">رجوع للرسائل</a>
@endsection

@section('content')
    <div class="card">
        <dl class="info">
            <dt>الاسم</dt><dd>{{ $message->name }}</dd>
            <dt>الجوال</dt><dd dir="ltr" style="text-align:right">{{ $message->phone ?: '—' }}</dd>
            <dt>البريد الإلكتروني</dt><dd>{{ $message->email ?: '—' }}</dd>
            <dt>الموضوع</dt><dd>{{ $message->subject ?: '—' }}</dd>
            <dt>التاريخ</dt><dd>{{ $message->created_at->format('Y-m-d H:i') }}</dd>
            @include('admin.partials.extra-answers', ['form' => 'contact', 'extra' => $message->extra])
            <dt>الرسالة</dt><dd style="font-weight:400">{{ $message->body }}</dd>
        </dl>
        <div class="actions" style="margin-top:20px">
            @if ($message->email)
                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('رد: '.($message->subject ?: 'رسالتك')) }}" class="btn btn-primary"><i class="fa-regular fa-envelope"></i> رد بالبريد</a>
            @endif
            @if ($message->phone)
                <a href="https://wa.me/{{ preg_replace('/^0/', '966', preg_replace('/\D/', '', $message->phone)) }}" target="_blank" rel="noopener" class="btn"><i class="fa-brands fa-whatsapp"></i> واتساب</a>
            @endif
            @include('admin.partials.delete-button', ['action' => route('admin.messages.destroy', $message)])
        </div>
    </div>
@endsection
