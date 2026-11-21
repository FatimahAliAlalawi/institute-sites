@extends('layouts.admin')

@section('title', 'تعديل حقل: '.$field->label)

@section('actions')
    <a href="{{ route('admin.forms.show', $field->form) }}" class="btn">← رجوع لنموذج {{ \App\Models\FormField::FORMS[$field->form] }}</a>
@endsection

@section('content')
    <form class="card" method="post" action="{{ route('admin.form-fields.update', $field) }}" data-type="{{ old('type', $field->type) }}">
        @csrf @method('PUT')
        @include('admin.forms.field-inputs', ['field' => $field])
        <div class="form-foot"><button class="btn btn-primary" type="submit">حفظ الحقل</button></div>
    </form>
@endsection
