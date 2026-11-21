@php
    $syncConfig = \App\Support\TutorSync::config() ?? [];
    $syncOn = \App\Support\TutorSync::enabled();
    $lastReport = \App\Support\TutorSync::lastReport();
@endphp

<div class="card" style="border-color:var(--accent)">
    <h2><i class="fa-solid fa-rotate"></i> المزامنة التلقائية مع الموقع القديم
        @if ($syncOn)
            <span class="badge" style="margin-inline-start:8px;background:#e3f6ec;color:var(--success)">تعمل</span>
        @else
            <span class="badge" style="margin-inline-start:8px;background:#f1f1f4;color:#666">متوقفة</span>
        @endif
    </h2>
    <p class="sub" style="margin-top:0">
        بعد التفعيل يُنقل تلقائيًا من الموقع القديم إلى هذا الموقع: <b>كل من يسجّل حسابًا</b>، <b>كل اشتراك جديد</b> (وتفعيل المدفوع)،
        و<b>الدورات والدروس والفيديوهات الجديدة</b>. الطالب يدخل هنا بنفس بريده وكلمة مروره القديمة، وتظهر له دوراته وفيديوهاتها <b>فورًا</b> عند دخوله.
        الموقع القديم يُقرأ فقط ولا يُعدَّل عليه شيء.
    </p>

    @if (\App\Support\TutorSync::config())
        <div class="stats" style="margin-bottom:12px">
            <div class="stat"><b style="font-size:15px">{{ setting('tutor_sync_last_at') ? \Illuminate\Support\Carbon::parse(setting('tutor_sync_last_at'))->diffForHumans() : '—' }}</b><span>آخر مزامنة</span></div>
            <div class="stat"><b>{{ \App\Support\TutorSync::interval() }}</b><span>دقائق بين كل مزامنة</span></div>
            @if ($lastReport)
                <div class="stat"><b style="font-size:15px;color:{{ ($lastReport['status'] ?? '') === 'ok' ? 'var(--success)' : 'var(--danger, #c0392b)' }}">{{ ($lastReport['status'] ?? '') === 'ok' ? 'ناجحة' : 'فشلت' }}</b><span>نتيجة آخر مزامنة</span></div>
            @endif
        </div>
        @if ($lastReport && ! empty($lastReport['counts']))
            <p class="sub" style="margin:0 0 8px">آخر ما نُقل: {{ collect($lastReport['counts'])->map(fn ($n, $l) => $l.': '.$n)->implode('، ') }}</p>
        @endif
        @if ($lastReport && ! empty($lastReport['warnings']))
            <details style="margin-bottom:12px">
                <summary style="cursor:pointer;font-weight:700">ملاحظات ({{ count($lastReport['warnings']) }})</summary>
                <ul style="margin:8px 0 0;padding-inline-start:20px">@foreach (array_slice($lastReport['warnings'], 0, 50) as $w)<li>{{ $w }}</li>@endforeach</ul>
            </details>
        @endif
        <form method="post" action="{{ route('admin.import.sync.run') }}" style="margin-bottom:14px">
            @csrf
            <button class="btn btn-primary" type="submit" onclick="this.disabled=true;this.form.submit()"><i class="fa-solid fa-rotate"></i> مزامنة الآن</button>
        </form>
        @if ($syncOn)
            <details style="margin-bottom:14px">
                <summary style="cursor:pointer;font-weight:700">اختياري: مهمة مجدولة (Cron) لمزامنة منتظمة حتى بدون زوار</summary>
                <p class="sub" style="margin:8px 0">cPanel ← <b>Cron Jobs</b> ← كل 5 دقائق، والأمر:</p>
                <code dir="ltr" style="display:block;padding:8px;background:#f4f4f8;border-radius:6px;word-break:break-all">wget -q -O /dev/null "{{ route('tutor.sync.cron', \App\Support\TutorSync::token()) }}"</code>
                <p class="sub" style="margin:8px 0 0">هذا الرابط سري — لا تنشره.</p>
            </details>
        @endif
    @endif

    <form method="post" action="{{ route('admin.import.sync.save') }}">
        @csrf
        <div class="form-grid">
            <div class="field"><label>خادم قاعدة البيانات (DB_HOST)</label><input type="text" name="db_host" value="{{ old('db_host', $syncConfig['host'] ?? 'localhost') }}" dir="ltr" required></div>
            <div class="field"><label>المنفذ</label><input type="number" name="db_port" value="{{ old('db_port', $syncConfig['port'] ?? 3306) }}" dir="ltr" required></div>
            <div class="field"><label>اسم قاعدة البيانات (DB_NAME)</label><input type="text" name="db_database" value="{{ old('db_database', $syncConfig['database'] ?? '') }}" dir="ltr" required></div>
            <div class="field"><label>المستخدم (DB_USER)</label><input type="text" name="db_username" value="{{ old('db_username', $syncConfig['username'] ?? '') }}" dir="ltr" required></div>
            <div class="field"><label>كلمة المرور (DB_PASSWORD)</label><input type="password" name="db_password" dir="ltr" autocomplete="new-password" placeholder="{{ ! empty($syncConfig['password']) ? 'محفوظة — اتركها فارغة للإبقاء عليها' : '' }}"></div>
            <div class="field"><label>بادئة الجداول ($table_prefix)</label><input type="text" name="prefix" value="{{ old('prefix', $syncConfig['prefix'] ?? 'wp_') }}" dir="ltr" required></div>
            <div class="field"><label>مسار مجلد uploads على السيرفر (اختياري)</label><input type="text" name="uploads_path" value="{{ old('uploads_path', $syncConfig['uploads_path'] ?? '') }}" dir="ltr" placeholder="/home/USERNAME/public_html/wp-content/uploads"></div>
            <div class="field"><label>رابط الموقع القديم (اختياري)</label><input type="url" name="site_url" value="{{ old('site_url', $syncConfig['site_url'] ?? '') }}" dir="ltr" placeholder="https://albsaer.com"></div>
            <div class="field"><label>المزامنة كل (دقائق)</label><input type="number" name="interval" min="1" max="1440" value="{{ old('interval', \App\Support\TutorSync::interval()) }}" dir="ltr" required></div>
            <div class="field full checks">
                <label><input type="checkbox" name="enabled" value="1" @checked(old('enabled', setting('tutor_sync_enabled') === '1' || ! \App\Support\TutorSync::config()))> تفعيل المزامنة التلقائية</label>
            </div>
        </div>
        <div class="form-foot">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plug-circle-check"></i> فحص الاتصال وحفظ</button>
        </div>
    </form>
</div>
