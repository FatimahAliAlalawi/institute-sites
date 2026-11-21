@extends('layouts.admin')

@section('title', 'مهامي')

@php
    $open = $tasks->where('done', false);
@endphp

@section('content')
    <div class="card" style="display:flex;gap:24px;flex-wrap:wrap">
        <div><div class="sub">مهام مفتوحة</div><b style="font-size:24px">{{ $open->count() }}</b></div>
        <div><div class="sub">متأخرة</div><b style="font-size:24px;color:#b42318">{{ $open->where('overdue', true)->count() }}</b></div>
        <div><div class="sub">اليوم</div><b style="font-size:24px;color:#b54708">{{ $open->where('today', true)->count() }}</b></div>
        <div><div class="sub">تمت</div><b style="font-size:24px;color:#16794a">{{ $tasks->where('done', true)->count() }}</b></div>
        @if ($dueFollowUps !== null)
            <div><div class="sub">متابعات مهتمين اليوم</div><a href="{{ route('admin.registrations.index', ['view' => 'due']) }}"><b style="font-size:24px">{{ $dueFollowUps }}</b></a></div>
        @endif
    </div>

    <div class="card">
        <h2>مهامي في آلية التسجيل</h2>
        <p class="sub" style="margin-top:0">المهام المسندة لك باسمك في «تشغيل الدورة» لكل دورة جارية أو قادمة{{ $admin->isTrainerOnly() ? '، وتحضير أيام دوراتك' : '' }}. إذا ما ظهرت مهامك، تأكد إن اسمك في خانة «المسؤول» مكتوب مثل اسم حسابك: <b>{{ $admin->name }}</b>.</p>
        @include('admin.tasks._list', ['tasks' => $tasks])
    </div>
@endsection
