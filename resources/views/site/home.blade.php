@extends('layouts.site')

@section('content')
    {{-- أقسام الصفحة الرئيسية بالترتيب المحدد من لوحة التحكم ← الصفحة الرئيسية --}}
    @foreach (\App\Support\HomeSections::visible() as $section)
        @include('site.home.'.$section['key'], ['section' => $section])
    @endforeach
@endsection
