<?php

namespace App\Support;

/** صلاحيات لوحة التحكم: كل صلاحية تفتح قسمًا (مجموعة صفحات) */
class Permissions
{
    public const GROUPS = [
        'المحتوى' => [
            'programs' => 'البرامج التدريبية',
            'posts' => 'الأخبار',
            'partners' => 'الشركاء',
            'faqs' => 'الأسئلة الشائعة',
        ],
        'الطلبات' => [
            'registrations' => 'طلبات التسجيل',
            'messages' => 'رسائل التواصل',
        ],
        'المنصة التعليمية' => [
            'courses' => 'الدورات والمنهج والتصنيفات والمدربون',
            'students' => 'الطلاب',
            'enrollments' => 'الاشتراكات والمدفوعات',
            'attendance' => 'التحضير',
            'course_attendance' => 'تحضير الدورات الحضورية (للمدربين)',
            'lms_reports' => 'نتائج الاختبارات والشهادات والتقييمات',
        ],
        'المبيعات والتسويق' => [
            'orders' => 'الطلبات والمدفوعات',
            'marketing' => 'كوبونات الخصم وشريط الإعلان',
        ],
        'الإدارة' => [
            'approvals' => 'اعتماد الخطة التدريبية والإعلانات ومتابعة الفريق وتقارير الأداء',
            'pages' => 'الصفحات والقوائم والصفحة الرئيسية',
            'settings' => 'إعدادات الموقع',
            'forms' => 'النماذج والحقول',
            'import' => 'الاستيراد من Tutor LMS',
            'users' => 'المستخدمون والصلاحيات',
        ],
    ];

    /** بادئات أسماء المسارات لكل صلاحية */
    public const ROUTES = [
        'programs' => ['admin.programs.'],
        'approvals' => ['admin.approvals.', 'admin.team.', 'admin.reports.'],
        'posts' => ['admin.posts.'],
        'partners' => ['admin.partners.'],
        'faqs' => ['admin.faqs.'],
        'registrations' => ['admin.registrations.'],
        'messages' => ['admin.messages.'],
        'courses' => ['admin.courses.', 'admin.topics.', 'admin.lessons.', 'admin.live.', 'admin.quizzes.', 'admin.questions.', 'admin.course-categories.', 'admin.instructors.'],
        'students' => ['admin.students.'],
        'enrollments' => ['admin.enrollments.'],
        'attendance' => ['admin.attendance.'],
        'course_attendance' => ['admin.course-attendance.'],
        'lms_reports' => ['admin.quiz-attempts.', 'admin.certificates.', 'admin.reviews.'],
        'orders' => ['admin.orders.'],
        'marketing' => ['admin.coupons.', 'admin.marketing.'],
        'pages' => ['admin.pages.', 'admin.menus.', 'admin.home.'],
        'settings' => ['admin.settings.'],
        'forms' => ['admin.forms.', 'admin.form-fields.', 'admin.form-file'],
        'import' => ['admin.import.'],
        'users' => ['admin.users.', 'admin.roles.'],
    ];

    public static function all(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::GROUPS)));
    }

    public static function label(string $key): string
    {
        foreach (self::GROUPS as $items) {
            if (isset($items[$key])) {
                return $items[$key];
            }
        }

        return $key;
    }

    /** الصلاحية المطلوبة لمسار معيّن، أو null إن كان متاحًا لكل المستخدمين (الرئيسية، حسابي، الخروج) */
    public static function requiredFor(?string $routeName): ?string
    {
        foreach (self::ROUTES as $permission => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($routeName && str_starts_with($routeName, $prefix)) {
                    return $permission;
                }
            }
        }

        return null;
    }
}
