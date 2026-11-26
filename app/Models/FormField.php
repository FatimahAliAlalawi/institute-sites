<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** حقل في أحد نماذج الموقع (التسجيل، إنشاء الحساب، التواصل) */
class FormField extends Model
{
    public const FORMS = [
        'registration' => 'التسجيل في البرامج',
        'signup' => 'إنشاء حساب طالب',
        'contact' => 'تواصل معنا',
    ];

    public const TYPES = [
        'text' => 'نص قصير',
        'textarea' => 'نص طويل',
        'email' => 'بريد إلكتروني',
        'tel' => 'رقم جوال',
        'national_id' => 'رقم هوية / إقامة',
        'number' => 'رقم',
        'date' => 'تاريخ',
        'select' => 'قائمة منسدلة',
        'radio' => 'اختيار واحد (أزرار)',
        'checkbox' => 'اختيار متعدد (مربعات)',
        'file' => 'رفع ملف',
    ];

    /** حقول أساسية لا يمكن إخفاؤها ولا جعلها اختيارية (يعتمد عليها النظام) */
    public const LOCKED = [
        'registration' => ['phone', 'national_id'],
        'signup' => ['name', 'email', 'password'],
        'contact' => ['name', 'body'],
    ];

    /** حقول أساسية يمكن تعديل خياراتها (القيم المسموحة) */
    public const OPTION_TYPES = ['select', 'radio', 'checkbox'];

    protected $fillable = ['form', 'key', 'label', 'type', 'options', 'placeholder', 'help', 'required', 'is_active', 'is_system', 'width', 'sort'];

    protected $casts = [
        'required' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static array $cache = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = []);
        static::deleted(fn () => static::$cache = []);
    }

    /** الحقول الظاهرة في نموذج مرتبة */
    public static function for(string $form): Collection
    {
        return static::$cache[$form] ??= static::where('form', $form)->where('is_active', true)->orderBy('sort')->orderBy('id')->get();
    }

    public function getOptionListAttribute(): array
    {
        return lines($this->options);
    }

    public function getIsLockedAttribute(): bool
    {
        return in_array($this->key, self::LOCKED[$this->form] ?? [], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ($this->type === 'program' ? 'اختيار البرنامج' : ($this->type === 'password' ? 'كلمة مرور' : $this->type));
    }
}
