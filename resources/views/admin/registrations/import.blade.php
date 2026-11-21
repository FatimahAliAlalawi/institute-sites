@extends('layouts.admin')

@section('title', 'نقل المتقدمين من نموذج Google أو Excel')

@section('actions')
    <a href="{{ route('admin.registrations.index', ['source' => 'نموذج Google']) }}" class="btn">المنقولون من النموذج</a>
    <a href="{{ route('admin.registrations.index') }}" class="btn">رجوع للقائمة</a>
@endsection

@section('content')
    @if (! empty($last))
        <div class="card" style="display:flex;gap:18px;flex-wrap:wrap;align-items:center">
            <b>آخر نقل:</b>
            <span class="sub">{{ $last['at'] ?? '' }} — {{ $last['from'] ?? '' }}</span>
            @if (isset($last['error']))
                <span class="badge b-rejected">خطأ: {{ $last['error'] }}</span>
            @else
                <span class="badge b-on">{{ $last['added'] ?? 0 }} جديد</span>
                <span class="badge b-new">{{ $last['updated'] ?? 0 }} تحديث</span>
                @if (($last['skipped'] ?? 0) > 0)<span class="badge b-off">{{ $last['skipped'] }} تجاهل</span>@endif
            @endif
        </div>
    @endif

    <div class="cols">
        <form class="card" method="post" action="{{ route('admin.registrations.import.link') }}">
            @csrf
            <h2><i class="fa-brands fa-google"></i> 1. ربط نموذج Google المعتمد</h2>
            <ol class="sub" style="margin-top:0;padding-inline-start:18px">
                <li>افتح النموذج ← تبويب «الردود» ← «الربط بجداول البيانات» (Link to Sheets).</li>
                <li>افتح جدول الردود ← «مشاركة» ← «أي شخص لديه الرابط» (عارض).</li>
                <li>انسخ رابط الجدول من شريط المتصفح وضعه هنا.</li>
            </ol>
            <div class="field" style="margin-bottom:10px">
                <label for="import_url">رابط جدول الردود (Google Sheets)</label>
                <input type="url" id="import_url" name="import_url" value="{{ old('import_url', setting('import_url')) }}" dir="ltr" placeholder="https://docs.google.com/spreadsheets/d/...">
            </div>
            <label class="checks" style="display:block;margin-bottom:10px"><input type="checkbox" name="import_auto" value="1" @checked(setting('import_auto') === '1')> سحب تلقائي كل 15 دقيقة (أي رد جديد أو تحديث ينتقل للموقع)</label>
            <div class="alert alert-error" style="margin:0 0 10px">جدول الردود فيه بيانات شخصية: شاركه «بالرابط» فقط ولا تنشره، وألغِ المشاركة لما تنتهي فترة الربط المؤقت.</div>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-link"></i> حفظ الرابط</button>
                @if (setting('import_url'))
                    <button class="btn" type="submit" formaction="{{ route('admin.registrations.import.sync') }}"><i class="fa-solid fa-rotate"></i> سحب الآن</button>
                @endif
            </div>
        </form>

        <form class="card" method="post" enctype="multipart/form-data" action="{{ route('admin.registrations.import.upload') }}">
            @csrf
            <h2><i class="fa-regular fa-file-excel"></i> 2. أو رفع ملف Excel</h2>
            <p class="sub" style="margin-top:0">ملف xlsx أو CSV، الصف الأول فيه عناوين الأعمدة (مثل تنزيل ردود النموذج). نفس ربط الأعمدة يُستخدم للملف والنموذج.</p>
            <div class="field" style="margin-bottom:10px">
                <input type="file" name="file" accept=".xlsx,.csv" required>
            </div>
            <label class="checks" style="display:block;margin-bottom:10px"><input type="checkbox" name="review" value="1"> راجع ربط الأعمدة قبل الاستيراد</label>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-upload"></i> رفع واستيراد</button>
        </form>
    </div>

    <form class="card" method="post" action="{{ route('admin.registrations.import.map') }}">
        @csrf
        @if ($fileToken)<input type="hidden" name="file_token" value="{{ $fileToken }}">@endif
        <h2>3. ربط الأعمدة {{ $pending ? '— ملف: '.$pending['name'] : '' }}</h2>
        @if (! $headers)
            <div class="empty">اربط نموذج Google أو ارفع ملفًا حتى تظهر عناوين الأعمدة هنا.</div>
        @else
            <div class="form-grid">
                @foreach (\App\Support\RegistrationImporter::FIELDS as $field => $label)
                    <div class="field">
                        <label>{{ $label }}</label>
                        <select name="map[{{ $field }}]">
                            <option value="">— لا يوجد —</option>
                            @foreach ($headers as $h)
                                @continue($h === '')
                                @php
                                    $guess = $map[$field] ?? null;
                                    $auto = ! $guess && match ($field) {
                                        'name' => str_contains($h, 'اسم') || stripos($h, 'name') !== false,
                                        'phone' => str_contains($h, 'جوال') || str_contains($h, 'هاتف') || str_contains($h, 'رقم الت') || stripos($h, 'phone') !== false || stripos($h, 'mobile') !== false,
                                        'email' => str_contains($h, 'بريد') || str_contains($h, 'ايميل') || str_contains($h, 'إيميل') || stripos($h, 'mail') !== false,
                                        'city' => str_contains($h, 'مدينة') || stripos($h, 'city') !== false,
                                        'program' => str_contains($h, 'دورة') || str_contains($h, 'برنامج'),
                                        default => false,
                                    };
                                @endphp
                                <option value="{{ $h }}" @selected($guess === $h || $auto)>{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="field">
                    <label>الدورة (إذا ما في عمود دورة أو ما تطابق الاسم)</label>
                    <select name="import_program">
                        <option value="">تسجيل عام</option>
                        @foreach ($programs as $p)
                            <option value="{{ $p->id }}" @selected((string) setting('import_program') === (string) $p->id)>{{ $p->title }}{{ $p->start_date ? ' — '.$p->start_date->format('Y-m-d') : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>حالة المتقدمين الجدد</label>
                    <select name="import_status">
                        @foreach (\App\Models\Registration::OPEN_STATUSES as $key)
                            <option value="{{ $key }}" @selected(setting('import_status', 'new') === $key)>{{ \App\Models\Registration::STATUSES[$key] }}</option>
                        @endforeach
                    </select>
                    <div class="hint">القبول في الدورة يكون باعتماد الفاتورة من صفحة المتقدم، وليس من الاستيراد.</div>
                </div>
            </div>
            <p class="sub">المتقدم المكرر (نفس الجوال ونفس الدورة) لا يتكرر: تُكمَّل بياناته الناقصة فقط. عمود «الحالة» اختياري، وإذا ربطته تتحدث الحالة مع كل سحب (ما عدا المقبولين).</p>
            <div class="form-foot"><button class="btn btn-primary" type="submit"><i class="fa-regular fa-floppy-disk"></i> {{ $fileToken ? 'حفظ واستيراد الملف' : 'حفظ ربط الأعمدة' }}</button></div>
        @endif
    </form>
@endsection
