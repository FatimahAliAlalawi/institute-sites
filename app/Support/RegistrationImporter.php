<?php

namespace App\Support;

use App\Models\Program;
use App\Models\Registration;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * نقل المتقدمين من نموذج Google المعتمد (أو ملف Excel) إلى «طلبات التسجيل».
 * الربط بأسماء الأعمدة (العناوين)، والتكرار يُمنع بالجوال + الدورة: الصف الموجود يُحدَّث ولا يتكرر.
 */
class RegistrationImporter
{
    public const FIELDS = [
        'name' => 'الاسم *',
        'phone' => 'الجوال *',
        'email' => 'البريد الإلكتروني',
        'city' => 'المدينة',
        'program' => 'الدورة (عمود)',
        'status' => 'الحالة (عمود)',
        'notes' => 'ملاحظات',
    ];

    public static function map(): array
    {
        return json_decode((string) setting('import_map', '{}'), true) ?: [];
    }

    public static function ready(): bool
    {
        $m = static::map();

        return filled($m['name'] ?? null) && filled($m['phone'] ?? null);
    }

    /** المزامنة التلقائية مع رابط نموذج Google (كل 15 دقيقة مع زيارات لوحة التحكم/الموقع) */
    public static function autoRun(): void
    {
        if (! Installer::installed() || setting('import_auto') !== '1' || ! filled(setting('import_url')) || ! static::ready()) {
            return;
        }
        if (! Cache::add('import_sync_tick', 1, now()->addMinutes(15))) {
            return;
        }
        static::syncGoogle();
    }

    public static function syncGoogle(): array
    {
        try {
            $rows = SheetReader::fromGoogle((string) setting('import_url'));
            $result = static::import($rows, 'نموذج Google');
        } catch (\Throwable $e) {
            $result = ['error' => $e->getMessage()];
        }
        Setting::put(['import_last' => json_encode($result + ['at' => now()->format('Y-m-d H:i'), 'from' => 'نموذج Google'], JSON_UNESCAPED_UNICODE)]);

        return $result;
    }

    /** @param array<int, array<int, string>> $rows الصف الأول عناوين */
    public static function import(array $rows, string $source): array
    {
        if (count($rows) < 2) {
            return ['added' => 0, 'updated' => 0, 'skipped' => 0, 'total' => 0];
        }
        $headers = array_map('trim', array_shift($rows));
        $map = static::map();
        $col = fn ($field) => filled($map[$field] ?? null) ? array_search($map[$field], $headers, true) : false;
        $idx = [];
        foreach (array_keys(self::FIELDS) as $f) {
            $idx[$f] = $col($f);
        }
        if ($idx['name'] === false || $idx['phone'] === false) {
            throw new \RuntimeException('أعمدة الاسم والجوال غير موجودة في الملف. راجع ربط الأعمدة.');
        }

        $defaultProgram = (int) (setting('import_program') ?: 0) ?: null;
        $defaultStatus = setting('import_status', 'new');
        $programs = Program::all(['id', 'title']);

        $added = $updated = $skipped = 0;
        foreach ($rows as $row) {
            $get = fn ($f) => $idx[$f] !== false ? trim((string) ($row[$idx[$f]] ?? '')) : '';
            $name = $get('name');
            $phone = Registration::normalizePhone($get('phone'));
            if ($name === '' || strlen(preg_replace('/\D/', '', $phone)) < 9) {
                $skipped++;
                continue;
            }

            $programId = $defaultProgram;
            if (($p = $get('program')) !== '') {
                $match = $programs->first(fn ($x) => $x->title === $p)
                    ?? $programs->first(fn ($x) => str_contains($p, $x->title) || str_contains($x->title, $p));
                $programId = $match?->id ?? $defaultProgram;
            }

            $status = $defaultStatus;
            if (($s = $get('status')) !== '') {
                foreach (Registration::STATUSES as $key => $label) {
                    if ($s === $key || str_contains($label, $s) || str_contains($s, explode(' ', $label)[0])) {
                        $status = $key;
                        break;
                    }
                }
            }

            $existing = Registration::where('phone', $phone)
                ->where(fn ($q) => $programId ? $q->where('program_id', $programId) : $q->whereNull('program_id'))
                ->first();

            if ($existing) {
                // تحديث البيانات الناقصة فقط، والحالة لا تُغيَّر إلا إذا كان في الملف عمود حالة
                $changes = array_filter([
                    'email' => $existing->email ?: ($get('email') ?: null),
                    'city' => $existing->city ?: ($get('city') ?: null),
                ]);
                if ($idx['status'] !== false && $get('status') !== '' && $existing->status !== $status && ! in_array($existing->status, Registration::SEAT_STATUSES, true)) {
                    $changes['status'] = $status;
                }
                $existing->fill($changes);
                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                }

                continue;
            }

            Registration::create([
                'program_id' => $programId,
                'name' => $name,
                'phone' => $phone,
                'email' => filter_var($get('email'), FILTER_VALIDATE_EMAIL) ? $get('email') : null,
                'city' => $get('city') ?: null,
                'notes' => $get('notes') ?: null,
                'status' => in_array($status, Registration::SEAT_STATUSES, true) ? 'new' : $status,
                'source' => $source,
                'follow_up_at' => in_array($status, Registration::OPEN_STATUSES, true) ? now()->toDateString() : null,
            ]);
            $added++;
        }

        return ['added' => $added, 'updated' => $updated, 'skipped' => $skipped, 'total' => count($rows)];
    }
}
