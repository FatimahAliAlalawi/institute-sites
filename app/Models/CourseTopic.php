<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** وحدة / قسم داخل الدورة */
class CourseTopic extends Model
{
    protected $fillable = ['course_id', 'title', 'summary', 'sort'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'topic_id')->orderBy('sort')->orderBy('id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'topic_id')->orderBy('sort')->orderBy('id');
    }

    public function liveSessions()
    {
        return $this->hasMany(LiveSession::class, 'topic_id')->orderBy('starts_at');
    }
}
