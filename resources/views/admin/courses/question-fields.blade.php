{{-- حقول السؤال (إضافة/تعديل): $question --}}
@php
    $opts = old('options', collect($question->options ?? [])->pluck('text')->all());
    $correct = array_map('intval', (array) old('correct', array_keys(array_filter(collect($question->options ?? [])->pluck('correct')->all()))));
    $opts = array_pad((array) $opts, 6, '');
    $type = old('type', $question->type ?? 'single');
@endphp
<div class="form-grid">
    <div class="field full">
        <label>نص السؤال *</label>
        <textarea name="question" required style="min-height:80px">{{ old('question', $question->question) }}</textarea>
    </div>
    <div class="field">
        <label>نوع السؤال</label>
        <select name="type" onchange="this.closest('form').dataset.type=this.value" class="q-type">
            @foreach (\App\Models\QuizQuestion::TYPES as $key => $label)
                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label>الدرجة</label>
        <input type="number" name="points" min="1" value="{{ old('points', $question->points ?? 1) }}">
    </div>
    <div class="field full q-options">
        <label>الخيارات — علّم الإجابة/الإجابات الصحيحة ✔</label>
        @foreach ($opts as $i => $text)
            @if ($i < 6)
                <div class="actions" style="margin-bottom:6px;flex-wrap:nowrap">
                    <label class="checks" style="margin:0;white-space:nowrap"><input type="checkbox" name="correct[]" value="{{ $i }}" @checked(in_array($i, $correct, true))> صحيحة</label>
                    <input type="text" name="options[{{ $i }}]" value="{{ $text }}" placeholder="الخيار {{ $i + 1 }}{{ $i > 1 ? ' (اختياري)' : '' }}">
                </div>
            @endif
        @endforeach
    </div>
    <div class="field full q-tf">
        <label>الإجابة الصحيحة</label>
        @php $tf = (int) old('tf_answer', ($question->type === 'true_false' && ! empty($question->options[1]['correct'] ?? false)) ? 1 : 0); @endphp
        <div class="checks">
            <label><input type="radio" name="tf_answer" value="0" @checked($tf === 0)> صح</label>
            <label><input type="radio" name="tf_answer" value="1" @checked($tf === 1)> خطأ</label>
        </div>
    </div>
    <div class="field full">
        <label>توضيح يظهر بعد الإجابة (اختياري)</label>
        <input type="text" name="explanation" value="{{ old('explanation', $question->explanation) }}">
    </div>
</div>
<style>
    form[data-type="true_false"] .q-options { display: none; }
    form:not([data-type="true_false"]) .q-tf { display: none; }
</style>
