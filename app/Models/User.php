<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** الطالب (حساب المتدرب في المنصة التعليمية) */
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'phone', 'national_id', 'birth_date', 'gender', 'qualification', 'password', 'avatar', 'is_active', 'last_login_at'];

    public const GENDERS = ['ذكر', 'أنثى'];

    protected $hidden = ['password', 'legacy_password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'birth_date' => 'date',
        'extra' => 'array',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function enrollmentFor(Course $course): ?Enrollment
    {
        return $this->enrollments()->where('course_id', $course->id)->first();
    }

    public function isEnrolled(Course $course): bool
    {
        return $this->enrollments()->where('course_id', $course->id)->where('status', 'active')->exists();
    }

    public function getInitialsAttribute(): string
    {
        return mb_substr(trim($this->name), 0, 1);
    }
}
