@extends('layouts.admin')

@section('title', 'لا تملك صلاحية')

@section('content')
    <div class="card empty">
        <div style="font-size:48px;color:var(--danger)"><i class="fa-solid fa-lock"></i></div>
        <h2>لا تملك صلاحية الدخول لهذا القسم</h2>
        <p>صلاحياتك الحالية: <b>{{ $currentAdmin->role_label }}</b>. إن احتجت هذا القسم تواصل مع المدير العام.</p>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">العودة للرئيسية</a>
    </div>
@endsection
