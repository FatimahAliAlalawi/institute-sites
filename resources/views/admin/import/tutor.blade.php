@extends('layouts.admin')

@section('title', 'استيراد من Tutor LMS')

@section('content')
    @isset($error)
        <div class="alert alert-error">{{ $error }}</div>
    @endisset

    @if (\App\Support\TutorSync::available())
        @include('admin.import.sync')
    @endif

    @if (! empty($report))
        <div class="card" style="border-color:#bfe6d1;background:#f3fbf7">
            <h2 style="color:var(--success)"><i class="fa-solid fa-circle-check"></i> اكتمل الاستيراد</h2>
            <div class="stats" style="margin-bottom:10px">
                @forelse ($report['counts'] as $label => $count)
                    <div class="stat"><b>{{ $count }}</b><span>{{ $label }}</span></div>
                @empty
                    <div class="empty">لم يُستورد شيء جديد (كل العناصر موجودة مسبقًا).</div>
                @endforelse
            </div>
            <div class="actions">
                <a href="{{ route('admin.courses.index') }}" class="btn btn-primary">عرض الدورات</a>
                <a href="{{ route('admin.students.index') }}" class="btn">عرض الطلاب</a>
            </div>
            @if (! empty($report['warnings']))
                <details style="margin-top:16px">
                    <summary style="cursor:pointer;font-weight:700">ملاحظات تحتاج مراجعة ({{ count($report['warnings']) }})</summary>
                    <ul style="margin:10px 0 0;padding-inline-start:20px">
                        @foreach ($report['warnings'] as $w)<li>{{ $w }}</li>@endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    @if (! empty($preview))
        <div class="card" style="border-color:var(--accent)">
            <h2><i class="fa-solid fa-plug-circle-check"></i> تم الاتصال بنجاح — هذا ما سيتم استيراده:</h2>
            <div class="stats" style="margin-bottom:0">
                @foreach ($preview as $label => $count)
                    <div class="stat"><b>{{ $count }}</b><span>{{ $label }}</span></div>
                @endforeach
            </div>
            <p class="sub" style="margin:12px 0 0">إن كانت الأرقام صحيحة اضغط <b>بدء الاستيراد</b> بالأسفل.</p>
        </div>
    @endif

    <div class="card" style="background:#faf9fd">
        <h2>كيف أحصل على البيانات المطلوبة؟</h2>
        <ol style="margin:0;padding-inline-start:20px;line-height:2">
            <li>ادخل cPanel الخاص بالموقع القديم ← <b>File Manager</b> ← افتح مجلد الموقع (غالبًا <code>public_html</code>).</li>
            <li>افتح الملف <code>wp-config.php</code> (زر أيمن ← View) وانسخ القيم: <code>DB_NAME</code> و<code>DB_USER</code> و<code>DB_PASSWORD</code> و<code>DB_HOST</code>، والبادئة <code>$table_prefix</code>.</li>
            <li>إن كان الموقعان على نفس الاستضافة: الخادم <code>localhost</code>. وإن كانا مختلفين: صدّر قاعدة البيانات القديمة من <b>phpMyAdmin ← Export</b>، واستوردها في قاعدة بيانات جديدة على الاستضافة الجديدة، ثم أدخل بياناتها هنا.</li>
            <li>اختياري لكن مهم: <b>مسار مجلد الصور والملفات</b> على السيرفر، مثل <code>/home/USERNAME/public_html/wp-content/uploads</code> — ليتم نسخ صور الدورات والمرفقات بدل الاعتماد على الموقع القديم.</li>
        </ol>
        <p class="sub" style="margin:10px 0 0">الاستيراد <b>يقرأ فقط</b> من الموقع القديم ولا يعدّل عليه شيئًا، ويمكن تشغيله أكثر من مرة بدون تكرار (العناصر المستوردة سابقًا تُتخطى).</p>
    </div>

    <form class="card" method="post" action="{{ route('admin.import.tutor.run') }}">
        @csrf
        <h2>بيانات قاعدة بيانات الموقع القديم</h2>
        <div class="form-grid">
            <div class="field"><label>خادم قاعدة البيانات (DB_HOST)</label><input type="text" name="db_host" value="{{ old('db_host', $input['db_host'] ?? 'localhost') }}" dir="ltr" required></div>
            <div class="field"><label>المنفذ</label><input type="number" name="db_port" value="{{ old('db_port', $input['db_port'] ?? 3306) }}" dir="ltr" required></div>
            <div class="field"><label>اسم قاعدة البيانات (DB_NAME)</label><input type="text" name="db_database" value="{{ old('db_database', $input['db_database'] ?? '') }}" dir="ltr" required></div>
            <div class="field"><label>المستخدم (DB_USER)</label><input type="text" name="db_username" value="{{ old('db_username', $input['db_username'] ?? '') }}" dir="ltr" required></div>
            <div class="field"><label>كلمة المرور (DB_PASSWORD)</label><input type="password" name="db_password" value="{{ $input['db_password'] ?? '' }}" dir="ltr" autocomplete="off"></div>
            <div class="field"><label>بادئة الجداول ($table_prefix)</label><input type="text" name="prefix" value="{{ old('prefix', $input['prefix'] ?? 'wp_') }}" dir="ltr" required></div>
            <div class="field"><label>مسار مجلد uploads على السيرفر (اختياري)</label><input type="text" name="uploads_path" value="{{ old('uploads_path', $input['uploads_path'] ?? '') }}" dir="ltr" placeholder="/home/USERNAME/public_html/wp-content/uploads"></div>
            <div class="field"><label>رابط الموقع القديم (اختياري)</label><input type="url" name="site_url" value="{{ old('site_url', $input['site_url'] ?? '') }}" dir="ltr" placeholder="https://albsaer.com"><div class="hint">يُستخدم لتنزيل الصور إن لم يُحدَّد المسار.</div></div>
            <div class="field full checks">
                <label><input type="checkbox" name="with_students" value="1" @checked($input['with_students'] ?? true)> الطلاب واشتراكاتهم</label>
                <label><input type="checkbox" name="with_progress" value="1" @checked($input['with_progress'] ?? true)> تقدّم الطلاب (الدروس المكتملة، نتائج الاختبارات، الشهادات)</label>
                <label><input type="checkbox" name="with_reviews" value="1" @checked($input['with_reviews'] ?? true)> التقييمات</label>
            </div>
        </div>
        <div class="form-foot">
            <button class="btn" type="submit" name="action" value="preview"><i class="fa-solid fa-plug"></i> فحص الاتصال ومعاينة الأعداد</button>
            <button class="btn btn-primary" type="submit" name="action" value="import" onclick="this.form.dataset.busy=1;return confirm('بدء الاستيراد الآن؟ قد يستغرق عدة دقائق حسب حجم البيانات.')"><i class="fa-solid fa-file-import"></i> بدء الاستيراد</button>
        </div>
    </form>
@endsection
