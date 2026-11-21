<section class="stats" @if ($loop->first) style="margin-top:30px" @endif>
    <div class="container">
        <div class="grid">
            @foreach ([1, 2, 3, 4] as $i)
                @if (setting("stat{$i}_value"))
                    <div class="stat"><b>{{ setting("stat{$i}_value") }}</b><span>{{ setting("stat{$i}_label") }}</span></div>
                @endif
            @endforeach
        </div>
    </div>
</section>
