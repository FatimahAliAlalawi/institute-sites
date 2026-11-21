@php
    $menu = [
        ['admin.dashboard', 'fa-solid fa-gauge', 'الرئيسية', 'admin.dashboard'],
        'المنصة التعليمية',
        ['admin.courses.index', 'fa-solid fa-laptop-file', 'الدورات', 'admin.courses.*|admin.topics.*|admin.lessons.*|admin.live.*|admin.quizzes.*|admin.questions.*'],
        ['admin.course-categories.index', 'fa-solid fa-tags', 'تصنيفات الدورات', 'admin.course-categories.*'],
        ['admin.instructors.index', 'fa-solid fa-chalkboard-user', 'المدربون', 'admin.instructors.*'],
        ['admin.students.index', 'fa-solid fa-user-graduate', 'الطلاب', 'admin.students.*'],
        ['admin.attendance.index', 'fa-solid fa-clipboard-user', 'التحضير (الدورات المسجلة)', 'admin.attendance.*'],
        ['admin.enrollments.index', 'fa-solid fa-receipt', 'الاشتراكات والمدفوعات', 'admin.enrollments.*', $badges['enrollments'] ?? 0],
        ['admin.quiz-attempts.index', 'fa-solid fa-square-poll-vertical', 'نتائج الاختبارات', 'admin.quiz-attempts.*'],
        ['admin.certificates.index', 'fa-solid fa-award', 'الشهادات', 'admin.certificates.*'],
        ['admin.reviews.index', 'fa-regular fa-star', 'التقييمات', 'admin.reviews.*'],
        'المبيعات والتسويق',
        ['admin.orders.index', 'fa-solid fa-cart-shopping', 'الطلبات والمدفوعات', 'admin.orders.*'],
        ['admin.coupons.index', 'fa-solid fa-ticket', 'كوبونات الخصم', 'admin.coupons.*'],
        ['admin.marketing.edit', 'fa-solid fa-bullhorn', 'شريط الإعلان', 'admin.marketing.*'],
        'محتوى الموقع',
        ['admin.home.edit', 'fa-solid fa-house', 'الصفحة الرئيسية', 'admin.home.*'],
        ['admin.pages.index', 'fa-regular fa-file-lines', 'الصفحات', 'admin.pages.*'],
        ['admin.menus.index', 'fa-solid fa-bars-staggered', 'القوائم', 'admin.menus.*'],
        ['admin.my-tasks', 'fa-solid fa-list-check', 'مهامي', 'admin.my-tasks', $badges['tasks'] ?? 0],
        ['admin.team.index', 'fa-solid fa-people-group', 'متابعة الفريق', 'admin.team.*'],
        ['admin.programs.index', 'fa-solid fa-graduation-cap', 'البرامج التدريبية', 'admin.programs.*'],
        ['admin.course-attendance.index', 'fa-solid fa-clipboard-check', 'تحضير الدورات الحضورية', 'admin.course-attendance.*'],
        ['admin.approvals.index', 'fa-solid fa-stamp', 'اعتماد الخطة والإعلانات', 'admin.approvals.*', $badges['approvals'] ?? 0],
        ['admin.posts.index', 'fa-regular fa-newspaper', 'الأخبار', 'admin.posts.*'],
        ['admin.partners.index', 'fa-regular fa-handshake', 'الشركاء', 'admin.partners.*'],
        ['admin.faqs.index', 'fa-regular fa-circle-question', 'الأسئلة الشائعة', 'admin.faqs.*'],
        'الطلبات',
        ['admin.registrations.index', 'fa-solid fa-user-plus', 'طلبات التسجيل', 'admin.registrations.*', $badges['registrations'] ?? 0],
        ['admin.messages.index', 'fa-regular fa-envelope', 'رسائل التواصل', 'admin.messages.*', $badges['messages'] ?? 0],
        'الإعدادات',
        ['admin.settings.edit', 'fa-solid fa-sliders', 'إعدادات الموقع', 'admin.settings.*'],
        ['admin.forms.show', 'fa-solid fa-list-check', 'النماذج والحقول', 'admin.forms.*|admin.form-fields.*'],
        ['admin.import.tutor', 'fa-solid fa-file-import', 'استيراد من Tutor LMS', 'admin.import.*'],
        ['admin.users.index', 'fa-solid fa-users-gear', 'المستخدمون والصلاحيات', 'admin.users.*|admin.roles.*'],
        ['admin.account.edit', 'fa-solid fa-user-shield', 'حسابي', 'admin.account.*'],
    ];

    // إظهار الأقسام المسموحة فقط (وحذف العناوين الفارغة)
    $visible = [];
    $pendingSep = null;
    foreach ($menu as $item) {
        if (is_string($item)) {
            $pendingSep = $item;
            continue;
        }
        if (isset($currentAdmin) && ! $currentAdmin->canOpenRoute($item[0])) {
            continue;
        }
        if ($pendingSep !== null) {
            $visible[] = $pendingSep;
            $pendingSep = null;
        }
        $visible[] = $item;
    }
    $menu = $visible;
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — لوحة التحكم</title>
    <link rel="icon" type="image/png" href="{{ media_url(setting('logo', 'assets/img/logo.png')) }}">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}?v=3">
</head>
<body>
<div class="layout">
    <aside class="side" id="side">
        <a href="{{ route('admin.dashboard') }}" class="logo"><img src="{{ media_url(setting('logo', 'assets/img/logo.png')) }}" alt=""></a>
        <nav>
            @foreach ($menu as $item)
                @if (is_string($item))
                    <div class="sep">{{ $item }}</div>
                @else
                    <a href="{{ route($item[0]) }}" @class(['active' => request()->routeIs(...explode('|', $item[3]))])>
                        <i class="{{ $item[1] }}"></i> {{ $item[2] }}
                        @if (! empty($item[4]))<span class="count">{{ $item[4] }}</span>@endif
                    </a>
                @endif
            @endforeach
            <div class="sep"></div>
            <a href="{{ route('home') }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> عرض الموقع</a>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <a href="#" onclick="this.closest('form').submit();return false;"><i class="fa-solid fa-right-from-bracket"></i> تسجيل الخروج</a>
            </form>
        </nav>
    </aside>

    <div class="main">
        <div class="top">
            <div class="actions">
                <button class="btn menu-btn" type="button" onclick="document.getElementById('side').classList.toggle('open')"><i class="fa-solid fa-bars"></i></button>
                <h1>@yield('title')</h1>
            </div>
            <div class="actions">
                @yield('actions')
                <span class="user"><i class="fa-regular fa-user"></i> {{ $currentAdmin->name ?? '' }} <span class="sub">({{ $currentAdmin->role_label ?? '' }})</span></span>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </div>
</div>
<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
</script>
</body>
</html>
