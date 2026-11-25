<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** سؤال في اختبار الدورة (نفس الأسئلة للقبلي والبعدي حتى تصح المقارنة) */
class ProgramQuestion extends Model
{
    protected $fillable = ['program_id', 'question', 'options', 'correct', 'sort'];

    protected $casts = ['options' => 'array', 'correct' => 'integer'];
}
