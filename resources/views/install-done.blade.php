<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>اكتمل التثبيت</title>
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=1">
</head>
<body>
<div class="auth">
    <div class="box wide">
        <h1>اكتمل التثبيت ✅ — خطوة أخيرة</h1>
        <p>تعذّر حفظ ملف <code>.env</code> تلقائيًا بسبب صلاحيات الاستضافة. افتح مدير الملفات، وأنشئ/عدّل الملف <code>.env</code> في جذر الموقع، والصق فيه المحتوى التالي:</p>
        <textarea readonly style="min-height:300px;direction:ltr;text-align:left;font-family:monospace" onclick="this.select()">{{ $envContent }}</textarea>
        <p style="margin-top:16px"><a class="btn btn-primary" href="{{ url('/admin/login') }}">الانتقال لتسجيل الدخول</a></p>
    </div>
</div>
</body>
</html>
