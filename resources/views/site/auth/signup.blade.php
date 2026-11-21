@extends('layouts.site')

@section('title', \App\Support\FormBuilder::text('signup', 'title'))

@section('content')
    @include('partials.page-head', ['title' => \App\Support\FormBuilder::text('signup', 'title'), 'subtitle' => \App\Support\FormBuilder::text('signup', 'intro')])

    <section class="section">
        <div class="container auth-wrap" style="max-width:640px">
            <div class="form-card">
                <form class="form" method="post" action="{{ route('signup.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                    <div class="fields-grid">
                        @foreach ($fields as $field)
                            @include('partials.form-field', ['field' => $field])
                        @endforeach
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">{{ \App\Support\FormBuilder::text('signup', 'submit') }}</button>
                </form>
                <div class="switch">لديك حساب؟ <a href="{{ route('login') }}">سجّل دخولك</a></div>
            </div>
        </div>
    </section>
@endsection
