<?php

namespace App\Support;

use App\Models\FormField;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * بيانات الطالب الإلزامية (حسب نموذج إنشاء الحساب): من ينقصه شيء منها
 * — كالطلاب المنقولين من الموقع القديم — يُطلب منه إكماله قبل متابعة الدورات.
 */
class ProfileCompletion
{
    public const KEYS = ['name', 'phone', 'national_id', 'birth_date', 'gender', 'qualification'];

    /** حقول نموذج إنشاء الحساب الإلزامية الناقصة عند هذا الطالب */
    public static function missing(User $user): Collection
    {
        return FormField::for('signup')
            ->filter(fn (FormField $f) => in_array($f->key, self::KEYS, true) && ($f->required || $f->is_locked))
            ->filter(fn (FormField $f) => $f->key === 'name'
                ? self::wantsFourPart($f) && count(preg_split('/\s+/u', trim((string) $user->name))) < 4
                : blank($user->{$f->key}))
            ->values();
    }

    public static function wantsFourPart(FormField $field): bool
    {
        return str_contains($field->label, 'رباعي');
    }
}
