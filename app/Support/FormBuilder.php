<?php

namespace App\Support;

use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** التحقق من النماذج الديناميكية وفصل الحقول الأساسية عن الحقول الإضافية */
class FormBuilder
{
    /** نصوص النموذج الافتراضية (قابلة للتعديل من صفحة النماذج) */
    public const TEXTS = [
        'registration' => [
            'title' => 'التسجيل في البرامج',
            'intro' => 'أدخل بياناتك وسيتواصل معك فريق المعهد لإكمال إجراءات القبول',
            'submit' => 'إرسال الطلب',
            'success' => 'شكرًا لك، سيتواصل معك فريق المعهد قريبًا على رقم الجوال المسجّل.',
        ],
        'signup' => [
            'title' => 'إنشاء حساب جديد',
            'intro' => 'حساب واحد للاشتراك في الدورات ومتابعة تقدمك والحصول على الشهادات',
            'submit' => 'إنشاء الحساب',
            'success' => 'تم إنشاء حسابك بنجاح.',
        ],
        'contact' => [
            'title' => 'تواصل معنا',
            'intro' => 'يسعدنا الرد على استفساراتكم وطلبات الشركات والجهات',
            'submit' => 'إرسال الرسالة',
            'success' => 'تم إرسال رسالتك بنجاح، سنتواصل معك قريبًا. شكرًا لك.',
        ],
    ];

    public static function text(string $form, string $key): string
    {
        return (string) setting('form_'.$form.'_'.$key, self::TEXTS[$form][$key] ?? '');
    }

    /**
     * يتحقق من النموذج ويعيد [الحقول الأساسية، الحقول الإضافية].
     *
     * @param  array<string,array>  $systemRules  قيود إضافية لحقول النظام (مثل: البريد غير مكرر)
     */
    public static function validate(Request $request, string $form, array $systemRules = [], array $messages = []): array
    {
        $fields = FormField::for($form);
        $rules = [];
        $attributes = [];

        foreach ($fields as $field) {
            $base = [($field->required || $field->is_locked) ? 'required' : 'nullable'];
            $rules[$field->key] = in_array($field->type, ['program', 'password'], true)
                ? array_merge($base, $systemRules[$field->key] ?? [])
                : array_merge($base, static::typeRules($field), $systemRules[$field->key] ?? []);
            $attributes[$field->key] = $field->label;
            if ($field->type === 'checkbox' && $field->option_list) {
                $rules[$field->key.'.*'] = [Rule::in($field->option_list)];
            }
        }

        // تحويل الأرقام العربية (٠١٢...) إلى إنجليزية في حقول الأرقام قبل التحقق
        foreach ($fields as $field) {
            if (in_array($field->type, ['national_id', 'tel', 'number'], true) && is_string($request->input($field->key))) {
                $request->merge([$field->key => static::latinDigits($request->input($field->key))]);
            }
        }

        $data = $request->validate($rules, $messages, $attributes);

        $system = [];
        $extra = [];
        foreach ($fields as $field) {
            if ($field->type === 'file') {
                if (! $request->hasFile($field->key)) {
                    continue;
                }
                $file = $request->file($field->key);
                $value = ['file' => Uploader::storePrivate($file, 'forms'), 'name' => $file->getClientOriginalName()];
            } else {
                $value = $data[$field->key] ?? null;
            }

            if ($field->is_system) {
                $system[$field->key] = $value;
            } elseif ($value !== null && $value !== '' && $value !== []) {
                $extra[$field->key] = $value;
            }
        }

        return [$system, $extra];
    }

    public static function typeRules(FormField $field): array
    {
        $options = $field->option_list;

        return match ($field->type) {
            'textarea' => ['string', 'max:5000'],
            'email' => ['email', 'max:190'],
            'tel' => ['string', 'max:20', 'regex:/^[0-9+\s\-]{9,20}$/'],
            'national_id' => ['string', 'regex:/^[12][0-9]{9}$/'],
            'number' => ['numeric', 'max:9999999999'],
            'date' => ['date'],
            'select', 'radio' => $options ? ['string', Rule::in($options)] : ['string', 'max:500'],
            'checkbox' => ['array'],
            'file' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:5120'],
            default => ['string', 'max:500'],
        };
    }

    /**
     * قائمة الإجابات الإضافية للعرض في لوحة التحكم.
     *
     * @return array<int,array{key:string,label:string,value:string,file:?array}>
     */
    public static function display(string $form, ?array $extra): array
    {
        if (! $extra) {
            return [];
        }
        $labels = FormField::where('form', $form)->pluck('label', 'key');
        $result = [];
        foreach ($extra as $key => $value) {
            $isFile = is_array($value) && isset($value['file']);
            $result[] = [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'value' => $isFile ? ($value['name'] ?? 'ملف') : (is_array($value) ? implode('، ', $value) : (string) $value),
                'file' => $isFile ? $value : null,
            ];
        }

        return $result;
    }

    /** الحقول الإضافية (غير الأساسية) لنموذج — لأعمدة ملف Excel */
    public static function extraColumns(string $form): array
    {
        return FormField::where('form', $form)->where('is_system', false)->orderBy('sort')->orderBy('id')->pluck('label', 'key')->all();
    }

    public static function latinDigits(string $value): string
    {
        return trim(strtr($value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));
    }

    /** الاسم الرباعي: 4 مقاطع على الأقل */
    public static function fourPartName(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (count(preg_split('/\s+/u', trim((string) $value))) < 4) {
                $fail('اكتب الاسم الرباعي كاملًا (الاسم، اسم الأب، اسم الجد، اسم العائلة).');
            }
        };
    }

    public static function plain($value): string
    {
        if (is_array($value)) {
            return isset($value['file']) ? ($value['name'] ?? 'ملف') : implode('، ', $value);
        }

        return (string) $value;
    }
}
