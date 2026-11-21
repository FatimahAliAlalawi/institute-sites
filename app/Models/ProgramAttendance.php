<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramAttendance extends Model
{
    public const STATUSES = [
        'present' => 'حاضر',
        'late' => 'متأخر',
        'absent' => 'غائب',
        'excused' => 'غائب بعذر',
    ];

    /** حالات تُحسب حضورًا */
    public const ATTENDED = ['present', 'late'];

    protected $fillable = ['program_id', 'registration_id', 'date', 'status', 'note', 'recorded_by'];

    protected $casts = ['date' => 'date'];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /** أقصى نسبة غياب مسموحة للشهادة (إعدادات الموقع، الافتراضي 25%) */
    public static function maxAbsencePercent(): int
    {
        $v = setting('cert_max_absence');

        return is_numeric($v) ? max(0, min(100, (int) $v)) : 25;
    }

    /** هل الغياب بعذر يُحسب ضمن نسبة الغياب؟ (يُحسب ما لم تُفعَّل «مستثنى» في الإعدادات) */
    public static function excusedCounts(): bool
    {
        return setting('cert_excused_exempt') !== '1';
    }

    /**
     * ملخص متدرب: أيام التحضير، وحضوره، وغيابه، واستحقاقه للشهادة.
     * total = أيام الدورة في التقويم (أو أيام التحضير إن لم يُولَّد التقويم).
     * cert = ok (مستحق) | near (على الحد: يوم غياب آخر يُسقط الشهادة) | over (تجاوز الحد).
     */
    public static function summaryFor(Registration $r, ?Program $program = null): array
    {
        $program ??= $r->program;
        $days = $r->program_id ? static::where('program_id', $r->program_id)->distinct()->count('date') : 0;
        $rows = static::where('registration_id', $r->id)->get();

        $absent = $rows->where('status', 'absent')->count();
        $excused = $rows->where('status', 'excused')->count();
        $total = max($program ? count($program->session_dates) : 0, $days);
        $max = static::maxAbsencePercent();
        $missed = $absent + (static::excusedCounts() ? $excused : 0);
        $allowed = (int) floor($total * $max / 100);
        $remaining = $allowed - $missed;
        $cert = $missed > $allowed ? 'over' : ($missed > 0 && $remaining <= 1 ? 'near' : 'ok');

        return [
            'days' => $days,
            'attended' => $rows->whereIn('status', self::ATTENDED)->count(),
            'late' => $rows->where('status', 'late')->count(),
            'absent' => $absent,
            'excused' => $excused,
            'total' => $total,
            'missed' => $missed,
            'allowed' => $allowed,
            'remaining' => max(0, $remaining),
            'absence_pct' => $total ? (int) round($missed / $total * 100) : 0,
            'max_pct' => $max,
            'cert' => $total ? $cert : 'ok',
        ];
    }

    public const CERT_LABELS = [
        'ok' => ['مستحق للشهادة', 'b-on'],
        'near' => ['على حد الغياب', 'b-contacted'],
        'over' => ['تجاوز الغياب: لا يستحق الشهادة', 'b-rejected'],
    ];
}
