<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\DailyRelease;
use App\Support\Uploader;
use Illuminate\Http\Request;

/** كل نصوص الموقع وبيانات التواصل والألوان — تُعدّل من صفحة واحدة بدون برمجة */
class SettingController extends Controller
{
    /** [القسم => [المفتاح => [النوع, العنوان, ملاحظة اختيارية]]] */
    public const GROUPS = [
        'عام' => [
            'site_name' => ['text', 'اسم المعهد'],
            'site_name_en' => ['text', 'الاسم بالإنجليزية'],
            'site_tagline' => ['text', 'الشعار النصي (يظهر تحت الاسم)'],
            'site_description' => ['textarea', 'وصف الموقع لمحركات البحث'],
            'logo' => ['image', 'الشعار', 'PNG بخلفية شفافة يفضّل'],
            'show_name_in_header' => ['bool', 'إظهار اسم المعهد نصًا بجانب الشعار في أعلى الموقع (أوقفه إن كان الشعار يحتوي الاسم)'],
            'footer_text' => ['textarea', 'نص أسفل الموقع'],
        ],
        'الصفحة الرئيسية' => [
            'hero_badge' => ['text', 'الشارة أعلى العنوان'],
            'hero_title' => ['text', 'العنوان الرئيسي'],
            'hero_subtitle' => ['textarea', 'النص التعريفي'],
            'hero_image' => ['image', 'صورة الواجهة', 'اختيارية — إن لم ترفع صورة يظهر الشعار'],
            'hero_cta_text' => ['text', 'نص الزر الأول'],
            'hero_cta_link' => ['text', 'رابط الزر الأول'],
            'hero_cta2_text' => ['text', 'نص الزر الثاني'],
            'hero_cta2_link' => ['text', 'رابط الزر الثاني'],
            'journey' => ['textarea', 'مراحل الرحلة', 'كل مرحلة في سطر'],
            'stat1_value' => ['text', 'الإحصائية 1 — الرقم'], 'stat1_label' => ['text', 'الإحصائية 1 — الوصف'],
            'stat2_value' => ['text', 'الإحصائية 2 — الرقم'], 'stat2_label' => ['text', 'الإحصائية 2 — الوصف'],
            'stat3_value' => ['text', 'الإحصائية 3 — الرقم'], 'stat3_label' => ['text', 'الإحصائية 3 — الوصف'],
            'stat4_value' => ['text', 'الإحصائية 4 — الرقم'], 'stat4_label' => ['text', 'الإحصائية 4 — الوصف'],
            'cta_title' => ['text', 'عنوان شريط الدعوة للتسجيل'],
            'cta_text' => ['textarea', 'نص شريط الدعوة للتسجيل'],
        ],
        'من نحن' => [
            'about_title' => ['text', 'العنوان'],
            'about_text' => ['textarea', 'النبذة', 'افصل الفقرات بسطر فارغ'],
            'about_image' => ['image', 'صورة الصفحة'],
            'vision' => ['textarea', 'الرؤية'],
            'mission' => ['textarea', 'الرسالة'],
            'values' => ['textarea', 'القيم', 'كل قيمة في سطر'],
            'license_no' => ['text', 'رقم الترخيص (اختياري)'],
        ],
        'التواصل' => [
            'phone' => ['text', 'رقم الهاتف'],
            'phone2' => ['text', 'رقم هاتف إضافي'],
            'whatsapp' => ['text', 'رقم واتساب', 'بالصيغة الدولية بدون + مثل 9665xxxxxxxx'],
            'email' => ['email', 'البريد الإلكتروني'],
            'address' => ['text', 'العنوان'],
            'working_hours' => ['text', 'أوقات العمل'],
            'map_url' => ['url', 'رابط الموقع على خرائط Google'],
            'map_embed' => ['url', 'رابط تضمين الخريطة', 'من Google Maps ← مشاركة ← تضمين خريطة، انسخ قيمة src فقط'],
        ],
        'التواصل الاجتماعي' => [
            'social_x' => ['url', 'X (تويتر)'],
            'social_instagram' => ['url', 'إنستقرام'],
            'social_snapchat' => ['url', 'سناب شات'],
            'social_tiktok' => ['url', 'تيك توك'],
            'social_linkedin' => ['url', 'لينكدإن'],
            'social_youtube' => ['url', 'يوتيوب'],
        ],
        'المنصة التعليمية والدفع' => [
            'study_days' => ['days', 'أيام الدراسة للدورات المسجلة (درس يوميًا)', 'الإعداد العام لكل المتدربين — ويمكن تخصيص أيام مختلفة لأي متدرب من صفحته في "الطلاب"'],
            'bank_name' => ['text', 'اسم البنك'],
            'bank_account_name' => ['text', 'اسم صاحب الحساب'],
            'bank_iban' => ['text', 'رقم الآيبان (IBAN)'],
            'bank_account_no' => ['text', 'رقم الحساب'],
            'payment_instructions' => ['textarea', 'تعليمات الدفع', 'تظهر للطالب في صفحة إتمام الاشتراك للدورات المدفوعة'],
            'certificate_title' => ['text', 'عنوان الشهادة', 'مثال: شهادة إتمام'],
            'certificate_text' => ['text', 'نص الشهادة قبل اسم المتدرب', 'استخدم :site ليُستبدل باسم المعهد'],
            'certificate_signer' => ['text', 'اسم الموقّع على الشهادة'],
            'certificate_signer_title' => ['text', 'صفة الموقّع', 'مثال: مدير المعهد'],
            'reviews_need_approval' => ['bool', 'التقييمات تحتاج موافقة قبل الظهور'],
        ],
        'الدفع الإلكتروني (ميسر)' => [
            'moyasar_enabled' => ['bool', 'تفعيل الدفع الإلكتروني عبر ميسر (مدى، فيزا، ماستركارد)'],
            'moyasar_publishable_key' => ['text', 'المفتاح المنشور (Publishable Key)', 'يبدأ بـ pk_live_ (أو pk_test_ للتجربة) — من لوحة ميسر ← الإعدادات ← مفاتيح API'],
            'moyasar_secret_key' => ['secret', 'المفتاح السري (Secret Key)', 'يبدأ بـ sk_live_ — يُحفظ ولا يُعرض مرة أخرى. اتركه فارغًا للإبقاء على المحفوظ.'],
            'moyasar_webhook_secret' => ['secret', 'الرمز السري للإشعارات (Webhook Secret)', 'اختياري لكن مستحسن: أضف في لوحة ميسر Webhook على الرابط /payments/moyasar/webhook بنفس الرمز.'],
            'moyasar_applepay' => ['bool', 'إظهار Apple Pay (يتطلب تفعيله وتوثيق الدومين في ميسر)'],
            'moyasar_stcpay' => ['bool', 'إظهار STC Pay (يتطلب تفعيله في ميسر)'],
            'bank_transfer_enabled' => ['bool', 'إظهار خيار التحويل البنكي ورفع الإيصال'],
        ],
        'Zoom (المحاضرات المباشرة)' => [
            'zoom_account_id' => ['text', 'Account ID', 'من Zoom Marketplace ← Develop ← Build App ← Server-to-Server OAuth'],
            'zoom_client_id' => ['text', 'Client ID'],
            'zoom_client_secret' => ['secret', 'Client Secret', 'يُحفظ ولا يُعرض مرة أخرى.'],
            'zoom_user' => ['text', 'بريد حساب المضيف في Zoom (اختياري)', 'اتركه فارغًا لاستخدام صاحب التطبيق، أو اكتب بريد مدرب في نفس حساب Zoom.'],
        ],
        'الألوان' => [
            'color_primary' => ['color', 'اللون الأساسي'],
            'color_secondary' => ['color', 'اللون الثانوي'],
            'color_accent' => ['color', 'لون التمييز'],
        ],
    ];

    public function edit()
    {
        return view('admin.settings.edit', ['groups' => self::GROUPS]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (self::GROUPS as $fields) {
            foreach ($fields as $key => [$type]) {
                $rules[$key] = match ($type) {
                    'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                    'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                    'url' => 'nullable|url|max:500',
                    'email' => 'nullable|email|max:190',
                    'textarea' => 'nullable|string|max:10000',
                    'bool' => 'nullable|boolean',
                    'days' => 'nullable|array',
                    'secret' => 'nullable|string|max:255',
                    default => 'nullable|string|max:500',
                };
            }
        }
        $request->validate($rules);

        $values = [];
        foreach (self::GROUPS as $fields) {
            foreach ($fields as $key => [$type]) {
                if ($type === 'image') {
                    $values[$key] = Uploader::replace($request->file($key), setting($key), 'settings', $request->boolean('remove_'.$key));
                } elseif ($type === 'secret') {
                    // الحقول السرية: لا تُستبدل إلا إذا كُتبت قيمة جديدة
                    $values[$key] = filled($request->input($key)) ? trim((string) $request->input($key)) : setting($key);
                } elseif ($type === 'days') {
                    $days = array_map('intval', array_filter((array) $request->input($key, []), fn ($d) => is_numeric($d) && $d >= 0 && $d <= 6));
                    sort($days);
                    $values[$key] = implode(',', array_unique($days)) ?: implode(',', DailyRelease::DEFAULT_DAYS);
                } elseif ($type === 'bool') {
                    $values[$key] = $request->boolean($key) ? '1' : '0';
                } else {
                    $values[$key] = $request->input($key);
                }
            }
        }
        Setting::put($values);

        return back()->with('status', 'تم حفظ الإعدادات.');
    }
}
