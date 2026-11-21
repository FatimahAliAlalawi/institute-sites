<span class="stars" title="{{ number_format((float) $rating, 1) }} من 5">@for ($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= round((float) $rating) ? '' : 'off' }}"></i>@endfor</span>
