<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** نتيجة متدرب في الاختبار القبلي أو البعدي لدورته */
class ProgramTestResult extends Model
{
    public const KINDS = ['pre' => 'الاختبار القبلي', 'post' => 'الاختبار البعدي'];

    protected $fillable = ['program_id', 'registration_id', 'kind', 'score', 'earned', 'total', 'answers', 'recorded_by'];

    protected $casts = ['answers' => 'array', 'score' => 'integer'];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * ملخص مجموعة نتائج: المتوسطات، ونسبة التحسن لمن أدّى الاختبارين فقط (حتى تكون المقارنة عادلة).
     *
     * @param  Collection<int, self>  $results
     */
    public static function summarize(Collection $results): array
    {
        $pre = $results->where('kind', 'pre')->keyBy('registration_id');
        $post = $results->where('kind', 'post')->keyBy('registration_id');
        $both = $pre->keys()->intersect($post->keys());
        $avg = fn (Collection $scores) => $scores->isEmpty() ? null : (int) round($scores->avg());

        return [
            'pre_count' => $pre->count(),
            'post_count' => $post->count(),
            'both' => $both->count(),
            'pre_avg' => $avg($pre->pluck('score')),
            'post_avg' => $avg($post->pluck('score')),
            'gain' => $both->isEmpty() ? null : (int) round($both->avg(fn ($id) => $post[$id]->score - $pre[$id]->score)),
            'improved' => $both->filter(fn ($id) => $post[$id]->score > $pre[$id]->score)->count(),
        ];
    }
}
