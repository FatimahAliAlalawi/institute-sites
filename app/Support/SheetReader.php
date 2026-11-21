<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * قراءة جداول المتقدمين: ملف CSV أو Excel (xlsx) أو رابط Google Sheets (ردود نموذج Google).
 * يرجع صفوفًا: الصف الأول العناوين.
 */
class SheetReader
{
    /** يحوّل أي رابط Google Sheets إلى رابط تصدير CSV */
    public static function googleCsvUrl(string $url): ?string
    {
        $url = trim($url);
        if (str_contains($url, '/export?') || str_contains($url, 'output=csv') || str_contains($url, '/pub?')) {
            return str_contains($url, 'output=csv') || str_contains($url, 'format=csv') ? $url : $url.(str_contains($url, '?') ? '&' : '?').'output=csv';
        }
        if (! preg_match('#docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return null;
        }
        $gid = preg_match('/[#&?]gid=(\d+)/', $url, $g) ? $g[1] : '0';

        return 'https://docs.google.com/spreadsheets/d/'.$m[1].'/export?format=csv&gid='.$gid;
    }

    public static function fromGoogle(string $url): array
    {
        $csvUrl = static::googleCsvUrl($url);
        if (! $csvUrl) {
            throw new \RuntimeException('الرابط ليس رابط Google Sheets. افتح ملف ردود النموذج وانسخ الرابط من شريط المتصفح.');
        }
        $res = Http::timeout(25)->withOptions(['allow_redirects' => true])->get($csvUrl);
        $body = (string) $res->body();
        if (! $res->successful() || stripos($body, '<html') !== false) {
            throw new \RuntimeException('تعذّر قراءة الملف. تأكد من المشاركة: Share ← General access ← Anyone with the link (Viewer).');
        }

        return static::fromCsv($body);
    }

    public static function fromCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $rows = [];
        $h = fopen('php://temp', 'r+');
        fwrite($h, $csv);
        rewind($h);
        while (($row = fgetcsv($h, 0, ',', '"', '')) !== false) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = array_map(fn ($v) => trim((string) $v), $row);
        }
        fclose($h);

        return static::clean($rows);
    }

    /** أول ورقة في ملف xlsx (بدون مكتبات) */
    public static function fromXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('تعذّر فتح ملف Excel.');
        }
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = simplexml_load_string($xml);
            foreach ($sx->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                }
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $shared[] = $text;
            }
        }
        $sheetPath = 'xl/worksheets/sheet1.xml';
        if (($wb = $zip->getFromName('xl/workbook.xml')) !== false && ($rels = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false) {
            $wbx = simplexml_load_string($wb);
            $first = $wbx->sheets->sheet[0] ?? null;
            $rid = $first ? (string) $first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : null;
            foreach (simplexml_load_string($rels)->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $target = ltrim((string) $rel['Target'], '/');
                    $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }
        $xml = $zip->getFromName($sheetPath);
        $zip->close();
        if ($xml === false) {
            throw new \RuntimeException('ملف Excel لا يحتوي ورقة بيانات.');
        }

        $rows = [];
        $sx = simplexml_load_string($xml);
        foreach ($sx->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = static::colIndex(preg_replace('/\d+/', '', $ref));
                $type = (string) $c['t'];
                if ($type === 's') {
                    $val = $shared[(int) $c->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } else {
                    $val = (string) $c->v;
                    // أرقام الجوال الطويلة قد تُخزّن بصيغة علمية
                    if (is_numeric($val) && str_contains(strtolower($val), 'e')) {
                        $val = number_format((float) $val, 0, '', '');
                    }
                }
                $cells[$col] = trim($val);
            }
            if ($cells) {
                $max = max(array_keys($cells));
                $line = [];
                for ($i = 0; $i <= $max; $i++) {
                    $line[] = $cells[$i] ?? '';
                }
                $rows[] = $line;
            }
        }

        return static::clean($rows);
    }

    public static function fromUpload(\Illuminate\Http\UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match ($ext) {
            'xlsx' => static::fromXlsx($file->getRealPath()),
            'csv', 'txt' => static::fromCsv((string) file_get_contents($file->getRealPath())),
            default => throw new \RuntimeException('الملف لازم يكون Excel (xlsx) أو CSV. لو عندك xls قديم: افتحه واحفظه بصيغة xlsx.'),
        };
    }

    protected static function colIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n - 1;
    }

    /** حذف الصفوف الفارغة وتوحيد طول الصفوف */
    protected static function clean(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn ($r) => implode('', $r) !== ''));
        $width = $rows ? max(array_map('count', $rows)) : 0;

        return array_map(fn ($r) => array_pad($r, $width, ''), $rows);
    }
}
