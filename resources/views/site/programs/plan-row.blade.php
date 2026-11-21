<div class="plan-row">
    <div class="plan-date">
        @if ($program->start_date)
            <b>{{ $program->start_date->format('j') }}</b>
            <small>{{ $program->start_date->translatedFormat('F') }}</small>
        @else
            <i class="fa-regular fa-clock"></i>
            <small>قريبًا</small>
        @endif
    </div>
    <div class="plan-info">
        <h3><a href="{{ route('programs.show', $program) }}">{{ $program->title }}</a></h3>
        @if ($program->schedule_days || $program->schedule_time || $program->trainer_name)
            <div class="sched">
                @if ($program->schedule_days)<span><i class="fa-regular fa-calendar"></i> {{ $program->schedule_days }}</span>@endif
                @if ($program->schedule_time)<span><i class="fa-regular fa-clock"></i> {{ $program->schedule_time }}</span>@endif
                @if ($program->trainer_name)<span><i class="fa-solid fa-chalkboard-user"></i> {{ $program->trainer_name }}</span>@endif
            </div>
        @endif
        <div class="meta">
            @if ($program->accreditation_no)<span class="chip"><i class="fa-solid fa-certificate"></i> رقم الاعتماد <span dir="ltr">{{ $program->accreditation_no }}</span></span>@endif
            @if ($program->duration)<span class="chip"><i class="fa-regular fa-clock"></i> {{ $program->duration }}</span>@endif
            @if ($program->hours)<span class="chip">{{ $program->hours }}</span>@endif
            <span class="chip">{{ $program->mode }}</span>
            @if ($program->seats)
                @if ($program->is_full)
                    <span class="chip" style="background:#fdecec;color:#b42318"><i class="fa-solid fa-chair"></i> المقاعد مكتملة</span>
                @elseif ($program->seats_left <= 5)
                    <span class="chip" style="background:#fff4e5;color:#b54708"><i class="fa-solid fa-chair"></i> باقي {{ $program->seats_left }} {{ $program->seats_left <= 2 ? 'مقعد' : 'مقاعد' }}</span>
                @else
                    <span class="chip"><i class="fa-solid fa-chair"></i> {{ $program->seats }} مقعدًا</span>
                @endif
            @endif
        </div>
        @if ($program->offer_ends_at && $program->offer_ends_at->copy()->endOfDay()->isFuture())
            <div class="plan-deadline"><i class="fa-regular fa-hourglass-half"></i> آخر موعد للتسجيل{{ $program->has_offer ? ' والخصم' : '' }}: {{ $program->offer_ends_at->translatedFormat('j F Y') }}</div>
        @endif
    </div>
    <div class="plan-price">
        @if ($program->has_offer)
            <b>{{ $program->offer_price_label }}</b>
            <s class="old-price">{{ $program->price_label }}</s>
            @if ($program->discount_percent)<span class="chip free">خصم {{ $program->discount_percent }}%</span>@endif
        @elseif ($program->price_label)
            <b>{{ $program->price_label }}</b>
        @endif
    </div>
    <div class="plan-actions">
        @if ($program->can_register)
            <a href="{{ $program->external_link ?: route('register', ['program' => $program->id]) }}" @if ($program->external_link) target="_blank" rel="noopener" @endif class="btn btn-primary btn-sm">سجّل الآن</a>
        @else
            <span class="chip">{{ $program->is_full ? 'مكتملة' : 'التسجيل مغلق' }}</span>
        @endif
        @if ($program->whatsapp_url)
            <a href="{{ $program->whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="fa-brands fa-whatsapp"></i> استفسر</a>
        @endif
        @if ($program->session_dates)
            <a href="{{ route('programs.ics', $program) }}" class="btn btn-outline btn-sm" title="أضف أيام الدورة لتقويم جوالك"><i class="fa-regular fa-calendar-plus"></i> للتقويم</a>
        @endif
    </div>
</div>
