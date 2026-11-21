<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/** عنصر في قائمة الرأس أو التذييل */
class MenuItem extends Model
{
    public const LOCATIONS = ['header' => 'القائمة العلوية', 'footer' => 'روابط التذييل'];

    /** صفحات الموقع الثابتة المتاحة للإضافة للقائمة */
    public const ROUTES = [
        'home' => 'الرئيسية',
        'about' => 'من نحن',
        'courses.index' => 'الدورات',
        'programs.index' => 'البرامج',
        'news.index' => 'الأخبار',
        'contact' => 'تواصل معنا',
        'register' => 'التسجيل في البرامج',
        'signup' => 'إنشاء حساب',
        'login' => 'دخول الطلاب',
        'certificates.verify' => 'التحقق من شهادة',
    ];

    protected $fillable = ['location', 'label', 'type', 'target', 'new_tab', 'is_active', 'sort'];

    protected $casts = ['new_tab' => 'boolean', 'is_active' => 'boolean'];

    protected static array $cache = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$cache = []);
        static::deleted(fn () => static::$cache = []);
    }

    public static function for(string $location): Collection
    {
        if (! \App\Support\Installer::installed()) {
            return collect();
        }

        return static::$cache[$location] ??= static::where('location', $location)->where('is_active', true)->orderBy('sort')->orderBy('id')->get()
            ->filter(fn ($item) => $item->url !== null)->values();
    }

    public function getUrlAttribute(): ?string
    {
        return match ($this->type) {
            'route' => Route::has($this->target) ? route($this->target) : null,
            'page' => ($page = Page::where('is_published', true)->find((int) $this->target)) ? route('pages.show', $page) : null,
            default => $this->target,
        };
    }

    /** هل الرابط هو الصفحة الحالية؟ */
    public function isActive(): bool
    {
        if ($this->type === 'route') {
            return $this->target === 'home' ? request()->routeIs('home') : request()->routeIs(explode('.', $this->target)[0].'*');
        }
        if ($this->type === 'page') {
            return request()->routeIs('pages.show') && (string) optional(request()->route('page'))->id === (string) $this->target;
        }

        return false;
    }

    public function getTargetLabelAttribute(): string
    {
        return match ($this->type) {
            'route' => 'صفحة الموقع: '.(self::ROUTES[$this->target] ?? $this->target),
            'page' => 'صفحة مخصصة: '.(Page::find((int) $this->target)?->title ?? '(محذوفة)'),
            default => 'رابط: '.$this->target,
        };
    }
}
