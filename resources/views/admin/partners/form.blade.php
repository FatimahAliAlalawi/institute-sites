@extends('layouts.admin')

@section('title', $partner->exists ? 'تعديل شريك' : 'شريك جديد')

@section('content')
    <form method="post" enctype="multipart/form-data" action="{{ $partner->exists ? route('admin.partners.update', $partner) : route('admin.partners.store') }}">
        @csrf
        @if ($partner->exists) @method('PUT') @endif
        <div class="card">
            <div class="form-grid">
                <div class="field">
                    <label for="name">اسم الجهة *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $partner->name) }}" required>
                </div>
                <div class="field">
                    <label for="url">رابط موقع الجهة</label>
                    <input type="url" id="url" name="url" value="{{ old('url', $partner->url) }}" placeholder="https://">
                </div>
                @include('admin.partials.image-field', ['name' => 'logo', 'label' => 'الشعار', 'value' => $partner->logo, 'hint' => 'يفضّل PNG بخلفية شفافة — بحد أقصى 2 ميجابايت. إن لم يُرفع شعار يظهر اسم الجهة.'])
                <div class="field">
                    <label for="sort">الترتيب</label>
                    <input type="number" id="sort" name="sort" value="{{ old('sort', $partner->sort ?? 0) }}">
                </div>
            </div>
        </div>
        <div class="form-foot">
            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> حفظ</button>
            <a href="{{ route('admin.partners.index') }}" class="btn">رجوع</a>
        </div>
    </form>
@endsection
