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

    /** ملخص متدرب: أيام الدورة المسجّل فيها تحضير، وحضوره، وغيابه */
    public static function summaryFor(Registration $r): array
    {
        $days = $r->program_id ? static::where('program_id', $r->program_id)->distinct()->count('date') : 0;
        $rows = static::where('registration_id', $r->id)->get();

        return [
            'days' => $days,
            'attended' => $rows->whereIn('status', self::ATTENDED)->count(),
            'late' => $rows->where('status', 'late')->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'excused' => $rows->where('status', 'excused')->count(),
        ];
    }
}
