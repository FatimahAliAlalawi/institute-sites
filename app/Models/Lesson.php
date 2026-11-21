<?php

namespace App\Models;

use App\Support\Video;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['course_id', 'topic_id', 'title', 'video_url', 'content', 'duration', 'attachment', 'attachment_name', 'is_preview', 'sort'];

    protected $casts = ['is_preview' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topic()
    {
        return $this->belongsTo(CourseTopic::class, 'topic_id');
    }

    public function getVideoAttribute(): ?array
    {
        return Video::parse($this->video_url);
    }
}
