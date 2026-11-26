<?php

namespace App\Support;

use App\Models\ProgramAttendance;
use App\Models\ProgramTestResult;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * دمج الطلبات التي كررها السحب: طلب «تسجيل عام» قديم + طلب لنفس الشخص أُنشئ بعده لما أُضيفت الدورة.
 * يبقى الطلب القديم (بتاريخه ومتابعته) وينتقل للدورة، ويُحذف الأحدث بعد حفظ نسخة منه.
 */
class RegistrationMerger
{
    public const SOURCES = ['نموذج Google', 'ملف Excel'];

    /** @return array{pairs: Collection, review: Collection} */
    public static function scan(): array
    {
        $rows = Registration::with('program:id,title')->whereIn('source', self::SOURCES)->orderBy('id')->get();
        $worked = ProgramAttendance::distinct()->pluck('registration_id')
            ->merge(ProgramTestResult::distinct()->pluck('registration_id'))->flip();
        $pairs = collect();
        $review = collect();

        foreach ($rows->groupBy('phone') as $group) {
            $general = $group->firstWhere('program_id', null);
            if (! $general || trim((string) $general->phone) === '') {
                continue;
            }
            $newer = $group->first(fn ($r) => $r->program_id && $r->id > $general->id);
            if (! $newer) {
                continue;
            }
            $reason = match (true) {
                static::norm($general->name) !== static::norm($newer->name) => 'اسمان مختلفان بنفس الجوال',
                $general->import_key !== null && $newer->import_key !== null && $general->import_key !== $newer->import_key => 'طلبان لدورتين مختلفتين في النموذج',
                in_array($newer->status, Registration::SEAT_STATUSES, true) || in_array($general->status, Registration::SEAT_STATUSES, true) => 'فيه طلب مقبول أو مكتمل',
                filled($newer->invoice_no) || $worked->has($newer->id) || ! empty($newer->steps) => 'الطلب الأحدث عليه فاتورة أو حضور أو خطوات',
                default => null,
            };
            ($reason ? $review : $pairs)->push(['keep' => $general, 'drop' => $newer, 'reason' => $reason]);
        }

        return compact('pairs', 'review');
    }

    /** @return int عدد الطلبات المدموجة */
    public static function run(): int
    {
        // يوقف السحب التلقائي أثناء الدمج
        Cache::put('import_sync_tick', 1, now()->addMinutes(15));
        @set_time_limit(300);

        return DB::transaction(function () {
            $pairs = static::scan()['pairs'];
            if ($pairs->isEmpty()) {
                return 0;
            }
            static::backup($pairs);
            foreach ($pairs as $p) {
                static::merge($p['keep'], $p['drop']);
            }

            return $pairs->count();
        });
    }

    protected static function merge(Registration $keep, Registration $drop): void
    {
        $keep->program_id = $drop->program_id;
        foreach (['email', 'city', 'gender', 'qualification', 'employment_status', 'attendance', 'coupon_code', 'import_key', 'test_token'] as $f) {
            if (blank($keep->{$f}) && filled($drop->{$f})) {
                $keep->{$f} = $drop->{$f};
            }
        }
        $keep->extra = array_replace((array) $drop->extra, (array) $keep->extra) ?: null;
        foreach (['notes', 'admin_notes'] as $f) {
            $a = trim((string) $keep->{$f});
            $b = trim((string) $drop->{$f});
            if ($b !== '' && ! str_contains($a, $b)) {
                $keep->{$f} = $a === '' ? $b : $a."\n".$b;
            }
        }
        // حالة الأحدث تُؤخذ فقط إذا القديم ما زال «جديد»
        if ($keep->status === 'new' && $drop->status !== 'new') {
            $keep->status = $drop->status;
            $keep->follow_up_at = $drop->follow_up_at;
        }
        $drop->delete(); // أولًا: رمز الاختبار لا يتكرر
        $keep->save();
    }

    /** نسخة من الطلبين قبل الدمج في مجلد خاص غير متاح للزوار */
    protected static function backup(Collection $pairs): void
    {
        $dir = storage_path('app/private/merges');
        if (! is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $json = json_encode($pairs->map(fn ($p) => ['keep' => $p['keep']->getAttributes(), 'drop' => $p['drop']->getAttributes()])->values(), JSON_UNESCAPED_UNICODE);
        if ($json === false || @file_put_contents($dir.'/merge-'.now()->format('Ymd-His').'.json', $json) === false) {
            throw new \RuntimeException('تعذّر حفظ نسخة احتياطية قبل الدمج.');
        }
    }

    protected static function norm(?string $name): string
    {
        $name = mb_strtolower((string) $name);
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $name) ?? $name;
        $name = strtr($name, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }
}
