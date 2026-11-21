<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** محاضرة مباشرة عبر Zoom ضمن وحدة في الدورة */
class LiveSession extends Model
{
    /** يُفتح زر الانضمام قبل الموعد بـ 15 دقيقة */
    public const OPEN_BEFORE_MINUTES = 15;

    protected $fillable = ['course_id', 'topic_id', 'title', 'description', 'starts_at', 'duration', 'zoom_meeting_id', 'join_url', 'start_url', 'password', 'recording_url', 'sort'];

    protected $hidden = ['start_url'];

    protected $casts = ['starts_at' => 'datetime', 'duration' => 'integer', 'sort' => 'integer'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topic()
    {
        return $this->belongsTo(CourseTopic::class, 'topic_id');
    }

    public function attendances()
    {
        return $this->hasMany(LiveAttendance::class);
    }

    public function getEndsAtAttribute()
    {
        return $this->starts_at->copy()->addMinutes($this->duration);
    }

    /** upcoming | open | ended */
    public function getStateAttribute(): string
    {
        if (now()->gte($this->ends_at)) {
            return 'ended';
        }

        return now()->gte($this->starts_at->copy()->subMinutes(self::OPEN_BEFORE_MINUTES)) ? 'open' : 'upcoming';
    }
}
