@extends('layouts.site')

@section('title', \App\Support\FormBuilder::text('registration', 'title'))

@section('content')
    @include('partials.page-head', ['title' => \App\Support\FormBuilder::text('registration', 'title'), 'subtitle' => \App\Support\FormBuilder::text('registration', 'intro')])

    <section class="section">
        <div class="container" style="max-width:820px">
            @if (session('success'))
                <div class="form-card center">
                    <div style="font-size:60px;color:#16794a"><i class="fa-regular fa-circle-check"></i></div>
                    <h2 style="color:var(--primary)">تم استلام طلبك بنجاح</h2>
                    <p class="muted prose">{{ \App\Support\FormBuilder::text('registration', 'success') }}</p>
                    <a href="{{ route('programs.index') }}" class="btn btn-outline">تصفّح البرامج</a>
                </div>
            @else
                <div class="form-card">
                    @if ($errors->any())
                        <div class="alert alert-error">يرجى تصحيح الحقول المظللة ثم الإرسال مرة أخرى.</div>
                    @endif
                    <form class="form" method="post" action="{{ route('register.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                        <div class="fields-grid">
                            @foreach ($fields as $field)
                                @include('partials.form-field', ['field' => $field, 'default' => $field->key === 'city' ? 'الأحساء' : null])
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{{ \App\Support\FormBuilder::text('registration', 'submit') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </section>
@endsection
