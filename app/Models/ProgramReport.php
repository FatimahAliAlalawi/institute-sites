<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** ملف أو ملاحظة يرفعها المدرب للدورة */
class ProgramReport extends Model
{
    /** النوع => [الاسم، أسبوعي؟] */
    public const KINDS = [
        'weekly_plan' => ['الخطة الأسبوعية', true],
        'weekly_report' => ['التقرير الأسبوعي', true],
        'pre_report' => ['تقرير الاختبار القبلي', false],
        'post_report' => ['تقرير الاختبار البعدي', false],
    ];

    protected $fillable = ['program_id', 'kind', 'week', 'path', 'original_name', 'note', 'admin_id', 'admin_name'];

    protected $casts = ['week' => 'date'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind][0] ?? $this->kind;
    }
}
