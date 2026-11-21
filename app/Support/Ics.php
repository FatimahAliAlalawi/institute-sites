<?php

namespace App\Support;

use App\Models\Program;
use Illuminate\Support\Carbon;

/**
 * ملف تقويم (.ics) يفتح في تقويم الجوال (Apple / Google / Outlook) ويضيف أيام الدورة بأوقاتها.
 */
class Ics
{
    /** @param iterable<Program> $programs */
    public static function build(iterable $programs, string $calendarName): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'institute';
        $stamp = now()->utc()->format('Ymd\THis\Z');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.self::esc(setting('site_name', config('app.name'))).'//AR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::esc($calendarName),
        ];

        foreach ($programs as $program) {
            $times = $program->session_times;
            $dates = $program->session_dates ?: ($program->start_date ? [$program->start_date->toDateString()] : []);
            $total = count($dates);
            foreach ($dates as $i => $date) {
                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:program-'.$program->id.'-'.$date.'@'.$host;
                $lines[] = 'DTSTAMP:'.$stamp;
                if ($times) {
                    $start = Carbon::parse($date)->setTime($times[0][0], $times[0][1]);
                    $end = Carbon::parse($date)->setTime($times[1][0], $times[1][1]);
                    if ($end->lte($start)) {
                        $end = $start->copy()->addHours(3);
                    }
                    $lines[] = 'DTSTART:'.$start->utc()->format('Ymd\THis\Z');
                    $lines[] = 'DTEND:'.$end->utc()->format('Ymd\THis\Z');
                } else {
                    $lines[] = 'DTSTART;VALUE=DATE:'.Carbon::parse($date)->format('Ymd');
                    $lines[] = 'DTEND;VALUE=DATE:'.Carbon::parse($date)->addDay()->format('Ymd');
                }
                $lines[] = 'SUMMARY:'.self::esc($program->title.($total > 1 ? ' ('.($i + 1).'/'.$total.')' : ''));
                if ($program->location) {
                    $lines[] = 'LOCATION:'.self::esc($program->location);
                }
                $desc = trim(implode("\n", array_filter([
                    setting('site_name'),
                    $program->trainer_name ? 'المدرب: '.$program->trainer_name : null,
                    $program->schedule_time ? 'الوقت: '.$program->schedule_time : null,
                    route('programs.show', $program),
                ])));
                $lines[] = 'DESCRIPTION:'.self::esc($desc);
                $lines[] = 'BEGIN:VALARM';
                $lines[] = 'TRIGGER:-PT1H';
                $lines[] = 'ACTION:DISPLAY';
                $lines[] = 'DESCRIPTION:'.self::esc($program->title);
                $lines[] = 'END:VALARM';
                $lines[] = 'END:VEVENT';
            }
        }
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'fold'], $lines))."\r\n";
    }

    public static function response(string $ics, string $filename)
    {
        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    protected static function esc(string $s): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $s);
    }

    /** طي الأسطر الطويلة حسب المواصفة (75 بايت) دون كسر حرف عربي */
    protected static function fold(string $line): string
    {
        $out = '';
        $cur = '';
        foreach (mb_str_split($line) as $ch) {
            if (strlen($cur.$ch) > 74) {
                $out .= $cur."\r\n ";
                $cur = '';
            }
            $cur .= $ch;
        }

        return $out.$cur;
    }
}
