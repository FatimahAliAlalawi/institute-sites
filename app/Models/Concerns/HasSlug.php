<?php

namespace App\Models\Concerns;

/** يولّد رابطًا مختصرًا (يدعم العربية) من العنوان تلقائيًا ويضمن عدم تكراره */
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $base = static::makeSlug($model->slug ?: $model->title) ?: 'item';
            $slug = $base;
            $i = 2;
            while (static::where('slug', $slug)->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))->exists()) {
                $slug = $base.'-'.$i++;
            }
            $model->slug = $slug;
        });
    }

    public static function makeSlug(?string $text): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim((string) $text)));

        return mb_substr(trim($slug, '-'), 0, 120);
    }
}
