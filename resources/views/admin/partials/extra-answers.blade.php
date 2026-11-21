{{-- إجابات الحقول الإضافية: $form, $extra --}}
@foreach (\App\Support\FormBuilder::display($form, $extra) as $answer)
    <dt>{{ $answer['label'] }}</dt>
    <dd>
        @if ($answer['file'])
            <a href="{{ route('admin.form-file', ['path' => $answer['file']['file'], 'name' => $answer['file']['name'] ?? null]) }}"><i class="fa-solid fa-paperclip"></i> {{ $answer['value'] }}</a>
        @else
            {{ $answer['value'] }}
        @endif
    </dd>
@endforeach
