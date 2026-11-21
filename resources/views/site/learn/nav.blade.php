{{-- أزرار السابق / التالي في المشغّل --}}
<div class="lesson-nav">
    @if ($prev)
        <a class="btn btn-outline" href="{{ route(\App\Models\Course::itemRoute($prev['type']), [$course, $prev['model']]) }}"><i class="fa-solid fa-arrow-right"></i> السابق</a>
    @else
        <span></span>
    @endif
    {{ $slot ?? '' }}
    @if ($next && ($enrolled ?? true))
        <a class="btn btn-outline" href="{{ route(\App\Models\Course::itemRoute($next['type']), [$course, $next['model']]) }}">التالي <i class="fa-solid fa-arrow-left"></i></a>
    @else
        <span></span>
    @endif
</div>
