<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Site;
use Illuminate\Support\Facades\Route;

// معالج التثبيت (يُقفل تلقائيًا بعد اكتمال التثبيت)
Route::middleware('not.installed')->group(function () {
    Route::get('/install', [InstallController::class, 'show'])->name('install');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::middleware('installed')->group(function () {
    // ===== الموقع العام =====
    Route::get('/', Site\HomeController::class)->name('home');
    Route::get('/about', [Site\PageController::class, 'about'])->name('about');
    Route::get('/p/{page:slug}', [Site\PageController::class, 'show'])->name('pages.show');

    Route::get('/programs', [Site\ProgramController::class, 'index'])->name('programs.index');
    Route::get('/plan', [Site\ProgramController::class, 'plan'])->name('programs.plan');
    Route::get('/go/{id}', [Site\ProgramController::class, 'short'])->whereNumber('id')->name('programs.short');
    Route::get('/go/{id}/wa', [Site\ProgramController::class, 'whatsapp'])->whereNumber('id')->name('programs.whatsapp');
    Route::get('/programs/{program:slug}/calendar.ics', [Site\ProgramController::class, 'ics'])->name('programs.ics');
    Route::get('/programs/{program:slug}', [Site\ProgramController::class, 'show'])->name('programs.show');

    Route::get('/register', [Site\RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [Site\RegistrationController::class, 'store'])->middleware('throttle:forms')->name('register.store');

    // الاختبار القبلي/البعدي: رابط شخصي سري لكل متدرب
    Route::get('/t/{token}/{kind}', [Site\ProgramTestController::class, 'show'])->where('token', '[A-Za-z0-9]{40}')->whereIn('kind', ['pre', 'post'])->middleware('throttle:forms')->name('program-test');
    Route::post('/t/{token}/{kind}', [Site\ProgramTestController::class, 'store'])->where('token', '[A-Za-z0-9]{40}')->whereIn('kind', ['pre', 'post'])->middleware('throttle:forms')->name('program-test.store');

    Route::get('/news', [Site\NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{post:slug}', [Site\NewsController::class, 'show'])->name('news.show');

    Route::get('/contact', [Site\ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [Site\ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');

    // ===== المنصة التعليمية (LMS) =====
    Route::get('/courses', [Site\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course:slug}', [Site\CourseController::class, 'show'])->name('courses.show');
    Route::get('/instructors/{instructor}', [Site\CourseController::class, 'instructor'])->name('instructors.show');
    Route::get('/learn/{course:slug}/lessons/{lesson}', [Site\LearnController::class, 'lesson'])->name('learn.lesson');
    Route::get('/learn/{course:slug}/lessons/{lesson}/attachment', [Site\LearnController::class, 'attachment'])->name('learn.attachment');
    Route::post('/payments/moyasar/webhook', [Site\PaymentWebhookController::class, 'moyasar'])->name('payments.moyasar.webhook');
    Route::get('/tutor-sync/{token}', \App\Http\Controllers\TutorSyncController::class)->middleware('throttle:6,1')->name('tutor.sync.cron');
    Route::get('/certificates/verify', [Site\CertificateController::class, 'verify'])->name('certificates.verify');
    Route::get('/certificates/{code}', [Site\CertificateController::class, 'show'])->name('certificates.show');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [Site\StudentAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Site\StudentAuthController::class, 'login'])->middleware('throttle:logins')->name('login.attempt');
        Route::get('/signup', [Site\StudentAuthController::class, 'showSignup'])->name('signup');
        Route::post('/signup', [Site\StudentAuthController::class, 'signup'])->middleware('throttle:forms')->name('signup.store');
    });

    Route::middleware(['auth', 'profile.complete'])->group(function () {
        Route::post('/logout', [Site\StudentAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard/complete-profile', [Site\StudentDashboardController::class, 'completeProfile'])->name('profile.complete');
        Route::put('/dashboard/complete-profile', [Site\StudentDashboardController::class, 'saveCompleteProfile'])->name('profile.complete.save');

        Route::get('/dashboard', [Site\StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/certificates', [Site\StudentDashboardController::class, 'certificates'])->name('dashboard.certificates');
        Route::get('/dashboard/quiz-attempts', [Site\StudentDashboardController::class, 'attempts'])->name('dashboard.attempts');
        Route::get('/dashboard/profile', [Site\StudentDashboardController::class, 'profile'])->name('dashboard.profile');
        Route::put('/dashboard/profile', [Site\StudentDashboardController::class, 'updateProfile'])->name('dashboard.profile.update');

        Route::post('/courses/{course:slug}/enroll', [Site\EnrollController::class, 'enroll'])->name('courses.enroll');
        Route::get('/courses/{course:slug}/checkout', [Site\EnrollController::class, 'checkout'])->name('courses.checkout');
        Route::post('/courses/{course:slug}/checkout', [Site\EnrollController::class, 'submitPayment'])->middleware('throttle:forms')->name('courses.pay');
        Route::post('/courses/{course:slug}/review', [Site\EnrollController::class, 'review'])->name('courses.review');
        Route::post('/courses/{course:slug}/coupon', [Site\EnrollController::class, 'applyCoupon'])->middleware('throttle:forms')->name('courses.coupon');
        Route::post('/courses/{course:slug}/free', [Site\EnrollController::class, 'claimFree'])->name('courses.free');
        Route::get('/payments/moyasar/{order}/callback', [Site\EnrollController::class, 'moyasarCallback'])->name('payments.moyasar.callback');
        Route::get('/dashboard/orders', [Site\StudentDashboardController::class, 'orders'])->name('dashboard.orders');

        Route::get('/learn/{course:slug}', [Site\LearnController::class, 'start'])->name('learn.start');
        Route::post('/learn/{course:slug}/lessons/{lesson}/complete', [Site\LearnController::class, 'complete'])->name('learn.complete');
        Route::get('/learn/{course:slug}/quizzes/{quiz}', [Site\LearnController::class, 'quiz'])->name('learn.quiz');
        Route::post('/learn/{course:slug}/quizzes/{quiz}', [Site\LearnController::class, 'submitQuiz'])->name('learn.quiz.submit');
        Route::get('/learn/{course:slug}/live/{session}', [Site\LearnController::class, 'live'])->name('learn.live');
        Route::get('/learn/{course:slug}/live/{session}/join', [Site\LearnController::class, 'joinLive'])->name('learn.live.join');
        Route::get('/learn/{course:slug}/attempts/{attempt}', [Site\LearnController::class, 'attempt'])->name('learn.attempt');
    });

    // ===== لوحة التحكم =====
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:logins')->name('login.attempt');

        Route::middleware('admin')->group(function () {
            Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');
            Route::get('/', Admin\DashboardController::class)->name('dashboard');

            Route::get('programs/{program}/ops', [Admin\OpsController::class, 'show'])->name('programs.ops');
            Route::post('programs/{program}/ops', [Admin\OpsController::class, 'update'])->name('programs.ops.update');
            Route::post('programs/{program}/ops/email', [Admin\OpsController::class, 'email'])->name('programs.ops.email');
            Route::post('programs/{program}/ops/mark', [Admin\OpsController::class, 'mark'])->name('programs.ops.mark');
            Route::get('course-attendance', [Admin\CourseAttendanceController::class, 'index'])->name('course-attendance.index');
            Route::get('course-attendance/calendar.ics', [Admin\CourseAttendanceController::class, 'ics'])->name('course-attendance.ics');
            Route::get('course-attendance/{program}', [Admin\CourseAttendanceController::class, 'show'])->name('course-attendance.show');
            Route::post('course-attendance/{program}', [Admin\CourseAttendanceController::class, 'store'])->name('course-attendance.store');
            Route::prefix('course-attendance/{program}/tests')->name('course-attendance.tests')->controller(Admin\ProgramTestController::class)->group(function () {
                Route::get('/', 'tests');
                Route::get('export', 'exportTests')->name('.export');
                Route::post('questions', 'saveQuestions')->name('.questions');
                Route::post('copy', 'copyQuestions')->name('.copy');
                Route::post('toggle', 'toggle')->name('.toggle');
                Route::post('scores', 'scores')->name('.scores');
                Route::delete('results/{result}', 'reset')->name('.reset');
            });
            Route::prefix('course-attendance/{program}/files')->name('course-attendance.files')->controller(Admin\ProgramReportController::class)->group(function () {
                Route::get('/', 'files');
                Route::post('/', 'upload')->name('.store');
                Route::get('{report}', 'download')->name('.download');
                Route::delete('{report}', 'destroy')->name('.destroy');
            });
            Route::get('plan-follow', [Admin\PlanFollowController::class, 'index'])->name('plan-follow.index');
            Route::get('plan-follow/export', [Admin\PlanFollowController::class, 'export'])->name('plan-follow.export');
            Route::post('plan-follow/{program}', [Admin\PlanFollowController::class, 'update'])->name('plan-follow.update');
            Route::get('programs/{program}/ad-card', [Admin\ProgramController::class, 'adCard'])->name('programs.ad-card');
            Route::get('programs/{program}/qr/{type}', [Admin\ProgramController::class, 'qr'])->whereIn('type', ['page', 'wa'])->name('programs.qr');
            Route::resource('programs', Admin\ProgramController::class)->except('show');
            Route::get('approvals', [Admin\ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('my-tasks', [Admin\TaskController::class, 'mine'])->name('my-tasks');
            Route::get('team', [Admin\TaskController::class, 'team'])->name('team.index');
            Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
            Route::post('approvals/{program}', [Admin\ApprovalController::class, 'decide'])->name('approvals.decide');
            Route::resource('posts', Admin\PostController::class)->except('show');
            Route::resource('partners', Admin\PartnerController::class)->except('show');
            Route::resource('faqs', Admin\FaqController::class)->except('show');

            Route::get('registrations/export', [Admin\RegistrationController::class, 'export'])->name('registrations.export');
            Route::get('registrations/import', [Admin\RegistrationImportController::class, 'show'])->name('registrations.import');
            Route::post('registrations/import/link', [Admin\RegistrationImportController::class, 'link'])->name('registrations.import.link');
            Route::post('registrations/import/map', [Admin\RegistrationImportController::class, 'map'])->name('registrations.import.map');
            Route::post('registrations/import/sync', [Admin\RegistrationImportController::class, 'sync'])->name('registrations.import.sync');
            Route::post('registrations/import/merge', [Admin\RegistrationImportController::class, 'merge'])->name('registrations.import.merge');
            Route::post('registrations/import/upload', [Admin\RegistrationImportController::class, 'upload'])->name('registrations.import.upload');
            Route::resource('registrations', Admin\RegistrationController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
            Route::resource('messages', Admin\MessageController::class)->only(['index', 'show', 'destroy']);

            // المنصة التعليمية
            Route::resource('courses', Admin\CourseController::class)->except('show');
            Route::get('courses/{course}/curriculum', [Admin\CurriculumController::class, 'show'])->name('courses.curriculum');
            Route::post('courses/{course}/topics', [Admin\CurriculumController::class, 'storeTopic'])->name('topics.store');
            Route::put('topics/{topic}', [Admin\CurriculumController::class, 'updateTopic'])->name('topics.update');
            Route::delete('topics/{topic}', [Admin\CurriculumController::class, 'destroyTopic'])->name('topics.destroy');
            Route::get('topics/{topic}/lessons/create', [Admin\CurriculumController::class, 'createLesson'])->name('lessons.create');
            Route::post('topics/{topic}/lessons', [Admin\CurriculumController::class, 'storeLesson'])->name('lessons.store');
            Route::get('lessons/{lesson}/edit', [Admin\CurriculumController::class, 'editLesson'])->name('lessons.edit');
            Route::put('lessons/{lesson}', [Admin\CurriculumController::class, 'updateLesson'])->name('lessons.update');
            Route::delete('lessons/{lesson}', [Admin\CurriculumController::class, 'destroyLesson'])->name('lessons.destroy');
            Route::get('topics/{topic}/live/create', [Admin\CurriculumController::class, 'createLive'])->name('live.create');
            Route::post('topics/{topic}/live', [Admin\CurriculumController::class, 'storeLive'])->name('live.store');
            Route::get('live/{live}/edit', [Admin\CurriculumController::class, 'editLive'])->name('live.edit');
            Route::put('live/{live}', [Admin\CurriculumController::class, 'updateLive'])->name('live.update');
            Route::delete('live/{live}', [Admin\CurriculumController::class, 'destroyLive'])->name('live.destroy');
            Route::post('live/{live}/zoom', [Admin\CurriculumController::class, 'syncLive'])->name('live.zoom');
            Route::get('live/{live}/host', [Admin\CurriculumController::class, 'hostLive'])->name('live.host');
            Route::post('topics/{topic}/quizzes', [Admin\CurriculumController::class, 'storeQuiz'])->name('quizzes.store');
            Route::get('quizzes/{quiz}/edit', [Admin\CurriculumController::class, 'editQuiz'])->name('quizzes.edit');
            Route::put('quizzes/{quiz}', [Admin\CurriculumController::class, 'updateQuiz'])->name('quizzes.update');
            Route::delete('quizzes/{quiz}', [Admin\CurriculumController::class, 'destroyQuiz'])->name('quizzes.destroy');
            Route::post('quizzes/{quiz}/questions', [Admin\CurriculumController::class, 'storeQuestion'])->name('questions.store');
            Route::get('questions/{question}/edit', [Admin\CurriculumController::class, 'editQuestion'])->name('questions.edit');
            Route::put('questions/{question}', [Admin\CurriculumController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('questions/{question}', [Admin\CurriculumController::class, 'destroyQuestion'])->name('questions.destroy');

            Route::resource('course-categories', Admin\CourseCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('instructors', Admin\InstructorController::class)->except('show');
            Route::get('students-export', [Admin\StudentController::class, 'export'])->name('students.export');
            Route::resource('students', Admin\StudentController::class)->except('edit');
            Route::post('students/{student}/enroll', [Admin\StudentController::class, 'enroll'])->name('students.enroll');
            Route::post('students-bulk-days', [Admin\StudentController::class, 'bulkDays'])->name('students.bulk-days');
            Route::get('attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('enrollments/{enrollment}/receipt', [Admin\EnrollmentController::class, 'receipt'])->name('enrollments.receipt');
            Route::resource('enrollments', Admin\EnrollmentController::class)->only(['index', 'update', 'destroy']);
            Route::get('quiz-attempts', [Admin\LmsReportController::class, 'attempts'])->name('quiz-attempts.index');
            Route::get('certificates', [Admin\LmsReportController::class, 'certificates'])->name('certificates.index');
            Route::delete('certificates/{certificate}', [Admin\LmsReportController::class, 'destroyCertificate'])->name('certificates.destroy');
            Route::get('reviews', [Admin\LmsReportController::class, 'reviews'])->name('reviews.index');
            Route::put('reviews/{review}', [Admin\LmsReportController::class, 'updateReview'])->name('reviews.update');
            Route::delete('reviews/{review}', [Admin\LmsReportController::class, 'destroyReview'])->name('reviews.destroy');

            // منشئ النماذج
            Route::get('forms/{form?}', [Admin\FormController::class, 'show'])->whereIn('form', ['registration', 'signup', 'contact'])->name('forms.show');
            Route::post('forms/{form}/texts', [Admin\FormController::class, 'updateTexts'])->name('forms.texts');
            Route::post('forms/{form}/fields', [Admin\FormController::class, 'store'])->name('forms.fields.store');
            Route::get('form-fields/{field}/edit', [Admin\FormController::class, 'edit'])->name('form-fields.edit');
            Route::put('form-fields/{field}', [Admin\FormController::class, 'update'])->name('form-fields.update');
            Route::delete('form-fields/{field}', [Admin\FormController::class, 'destroy'])->name('form-fields.destroy');
            Route::post('form-fields/{field}/move', [Admin\FormController::class, 'move'])->name('form-fields.move');
            Route::post('form-fields/{field}/toggle', [Admin\FormController::class, 'toggle'])->name('form-fields.toggle');
            Route::get('form-file', [Admin\FormController::class, 'file'])->name('form-file');

            // المبيعات والتسويق
            Route::get('orders-export', [Admin\OrderController::class, 'export'])->name('orders.export');
            Route::resource('orders', Admin\OrderController::class)->only(['index', 'show', 'update']);
            Route::resource('coupons', Admin\CouponController::class)->except('show');

            // الصفحات والقوائم والصفحة الرئيسية
            Route::post('pages/upload-image', [Admin\PageController::class, 'uploadImage'])->name('pages.upload');
            Route::resource('pages', Admin\PageController::class)->except('show');
            Route::get('menus', [Admin\MenuController::class, 'index'])->name('menus.index');
            Route::post('menus', [Admin\MenuController::class, 'store'])->name('menus.store');
            Route::put('menus/{menu}', [Admin\MenuController::class, 'update'])->name('menus.update');
            Route::post('menus/{menu}/move', [Admin\MenuController::class, 'move'])->name('menus.move');
            Route::delete('menus/{menu}', [Admin\MenuController::class, 'destroy'])->name('menus.destroy');
            Route::get('home-page', [Admin\HomeSectionController::class, 'edit'])->name('home.edit');
            Route::put('home-page', [Admin\HomeSectionController::class, 'update'])->name('home.update');
            Route::post('home-page/move', [Admin\HomeSectionController::class, 'move'])->name('home.move');
            Route::post('home-page/toggle', [Admin\HomeSectionController::class, 'toggle'])->name('home.toggle');
            Route::post('home-page/add-page', [Admin\HomeSectionController::class, 'addPage'])->name('home.add-page');
            Route::post('home-page/remove', [Admin\HomeSectionController::class, 'removePage'])->name('home.remove');
            Route::get('marketing/announcement', [Admin\MarketingController::class, 'edit'])->name('marketing.edit');
            Route::put('marketing/announcement', [Admin\MarketingController::class, 'update'])->name('marketing.update');

            // المستخدمون والصلاحيات
            Route::resource('users', Admin\UserController::class)->except('show');
            Route::resource('roles', Admin\RoleController::class)->except(['index', 'show']);

            Route::get('import/tutor', [Admin\ImportController::class, 'show'])->name('import.tutor');
            Route::post('import/tutor', [Admin\ImportController::class, 'run'])->name('import.tutor.run');
            Route::post('import/sync', [Admin\ImportController::class, 'saveSync'])->name('import.sync.save');
            Route::post('import/sync/run', [Admin\ImportController::class, 'runSync'])->name('import.sync.run');

            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/test-mail', [Admin\SettingController::class, 'testMail'])->name('settings.test-mail');

            Route::get('account', [Admin\AccountController::class, 'edit'])->name('account.edit');
            Route::put('account', [Admin\AccountController::class, 'update'])->name('account.update');
        });
    });
});
