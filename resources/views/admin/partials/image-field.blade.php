{{-- حقل رفع صورة مع معاينة وخيار حذف: $name, $label, $value, $hint (اختياري) --}}
<div class="field">
    <label for="{{ $name }}">{{ $label }}</label>
    <input type="file" id="{{ $name }}" name="{{ $name }}" accept="image/png,image/jpeg,image/webp">
    <div class="hint">{{ $hint ?? 'JPG أو PNG أو WEBP — بحد أقصى 4 ميجابايت' }}</div>
    @if ($value)
        <div class="preview">
            <img src="{{ media_url($value) }}" alt="">
            <label class="checks" style="font-weight:500"><input type="checkbox" name="remove_{{ $name }}" value="1"> حذف الصورة</label>
        </div>
    @endif
</div>
