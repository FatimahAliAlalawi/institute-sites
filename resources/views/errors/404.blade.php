@extends('layouts.site')

@section('title', 'الصفحة غير موجودة')

@section('content')
    <section class="section">
        <div class="container center">
            <div style="font-size:90px;font-weight:800;color:var(--primary);line-height:1">404</div>
            <h1>الصفحة غير موجودة</h1>
            <p class="muted">ربما تم نقل الصفحة أو حذفها.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">العودة للرئيسية</a>
        </div>
    </section>
@endsection
