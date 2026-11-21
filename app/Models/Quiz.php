<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = ['course_id', 'topic_id', 'title', 'description', 'pass_mark', 'time_limit', 'max_attempts', 'show_answers', 'sort'];

    protected $casts = ['show_answers' => 'boolean'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topic()
    {
        return $this->belongsTo(CourseTopic::class, 'topic_id');
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort')->orderBy('id');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attemptsLeftFor(User $user): ?int
    {
        if (! $this->max_attempts) {
            return null;
        }

        return max(0, $this->max_attempts - $this->attempts()->where('user_id', $user->id)->count());
    }

    /** تصحيح إجابات الطالب: [question_id => [option indexes]] */
    public function grade(array $answers): array
    {
        $earned = 0;
        $total = 0;
        $details = [];
        foreach ($this->questions as $q) {
            $correct = $q->correctIndexes();
            $given = array_values(array_unique(array_map('intval', (array) ($answers[$q->id] ?? []))));
            sort($given);
            $isRight = $given === $correct;
            $total += $q->points;
            $earned += $isRight ? $q->points : 0;
            $details[$q->id] = ['given' => $given, 'correct' => $isRight];
        }
        $score = $total ? (int) round($earned * 100 / $total) : 0;

        return [
            'score' => $score,
            'earned_points' => $earned,
            'total_points' => $total,
            'passed' => $score >= $this->pass_mark,
            'answers' => $details,
        ];
    }
}
