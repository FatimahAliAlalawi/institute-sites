<?php

namespace App\Support;

/** يحوّل رابط فيديو (يوتيوب، فيميو، Google Drive، أو ملف mp4 مباشر) إلى رابط تضمين */
class Video
{
    /** @return array{type:string,src:string}|null */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return ['type' => 'iframe', 'src' => 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0&modestbranding=1'];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return ['type' => 'iframe', 'src' => 'https://player.vimeo.com/video/'.$m[1]];
        }
        if (preg_match('~drive\.google\.com/file/d/([A-Za-z0-9_-]+)~', $url, $m)) {
            return ['type' => 'iframe', 'src' => 'https://drive.google.com/file/d/'.$m[1].'/preview'];
        }
        if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url)) {
            return ['type' => 'file', 'src' => $url];
        }

        return ['type' => 'iframe', 'src' => $url];
    }
}
