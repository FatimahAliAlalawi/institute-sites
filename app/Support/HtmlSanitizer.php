<?php

namespace App\Support;

/**
 * تنظيف محتوى المحرر المرئي قبل حفظه: يسمح فقط بوسوم وتنسيقات آمنة،
 * ويمنع أي سكربت أو أحداث JavaScript أو روابط خطرة (javascript:, data:).
 */
class HtmlSanitizer
{
    protected const TAGS = [
        'p' => ['class'], 'br' => [], 'h2' => ['class'], 'h3' => ['class'], 'h4' => ['class'],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'ul' => ['class'], 'ol' => ['class'], 'li' => ['class', 'data-list'],
        'a' => ['href', 'target', 'rel'], 'img' => ['src', 'alt', 'width'], 'blockquote' => ['class'],
        'pre' => ['class', 'spellcheck'], 'code' => [], 'span' => ['class'], 'hr' => [],
        'iframe' => ['src', 'class', 'frameborder', 'allowfullscreen'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    protected const VIDEO_HOSTS = ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com', 'drive.google.com'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '' || $html === '<p><br></p>') {
            return '';
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root') ?? $doc->documentElement;
        static::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    protected static function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'link', 'meta', 'svg', 'math'], true)) {
                $node->removeChild($child);

                continue;
            }
            if (! array_key_exists($tag, self::TAGS)) {
                // وسم غير مسموح: نحتفظ بنصه ومحتواه فقط
                static::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                if (! in_array($name, self::TAGS[$tag], true)) {
                    $child->removeAttribute($attr->name);
                }
            }

            if ($tag === 'a') {
                if (! static::safeUrl($child->getAttribute('href'), true)) {
                    $child->removeAttribute('href');
                }
                if ($child->getAttribute('target') === '_blank') {
                    $child->setAttribute('rel', 'noopener noreferrer');
                } else {
                    $child->removeAttribute('target');
                }
            }
            if ($tag === 'img' && ! static::safeUrl($child->getAttribute('src'), false)) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'iframe') {
                $host = strtolower((string) parse_url($child->getAttribute('src'), PHP_URL_HOST));
                if (! str_starts_with($child->getAttribute('src'), 'https://') || ! in_array($host, self::VIDEO_HOSTS, true)) {
                    $node->removeChild($child);

                    continue;
                }
            }
            if ($child->hasAttribute('class')) {
                // فقط تنسيقات المحرر (المحاذاة والاتجاه والإزاحة والفيديو)
                $classes = array_filter(preg_split('/\s+/', $child->getAttribute('class')), fn ($c) => preg_match('/^ql-(align|direction|indent|size|video|syntax)[\w-]*$/', $c));
                $classes ? $child->setAttribute('class', implode(' ', $classes)) : $child->removeAttribute('class');
            }

            static::walk($child);
        }
    }

    protected static function safeUrl(string $url, bool $allowMailTel): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }
        if (preg_match('#^https?://#i', $url)) {
            return true;
        }

        return $allowMailTel && preg_match('#^(mailto:|tel:|\#)#i', $url);
    }
}
