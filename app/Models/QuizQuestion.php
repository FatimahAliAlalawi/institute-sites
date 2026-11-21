<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    public const TYPES = [
        'single' => 'اختيار من متعدد (إجابة واحدة)',
        'multiple' => 'اختيار من متعدد (أكثر من إجابة)',
        'true_false' => 'صح أو خطأ',
    ];

    protected $fillable = ['quiz_id', 'question', 'type', 'options', 'explanation', 'points', 'sort'];

    /** options: [['text' => '...', 'correct' => bool], ...] */
    protected $casts = ['options' => 'array'];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function correctIndexes(): array
    {
        $indexes = [];
        foreach ((array) $this->options as $i => $option) {
            if (! empty($option['correct'])) {
                $indexes[] = $i;
            }
        }

        return $indexes;
    }
}
