<?php

namespace App\Support;

/**
 * مولّد رمز QR بسيط بدون مكتبات خارجية (وضع البايت، تصحيح أخطاء M، الإصدارات 1–10).
 * يكفي للروابط القصيرة مثل /go/12 و /go/12/wa. المخرج: SVG.
 */
class QrCode
{
    /** [عدد كلمات التصحيح لكل كتلة, عدد الكتل] لمستوى M، للإصدارات 1..10 */
    private const ECC_M = [
        1 => [10, 1], 2 => [16, 1], 3 => [26, 1], 4 => [18, 2], 5 => [24, 2],
        6 => [16, 4], 7 => [18, 4], 8 => [22, 4], 9 => [22, 5], 10 => [26, 5],
    ];

    private int $version;
    private int $size;
    /** @var array<int, array<int, bool>> */
    private array $modules = [];
    /** @var array<int, array<int, bool>> */
    private array $isFunction = [];

    public static function svg(string $text, int $scale = 8, string $dark = '#000', string $light = '#fff'): string
    {
        $qr = new self($text);
        $border = 4;
        $dim = ($qr->size + $border * 2) * $scale;
        $path = '';
        for ($y = 0; $y < $qr->size; $y++) {
            for ($x = 0; $x < $qr->size; $x++) {
                if ($qr->modules[$y][$x]) {
                    $path .= 'M'.(($x + $border) * $scale).','.(($y + $border) * $scale).'h'.$scale.'v'.$scale.'h-'.$scale.'z';
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$dim.' '.$dim.'" width="'.$dim.'" height="'.$dim.'" shape-rendering="crispEdges">'
            .'<rect width="100%" height="100%" fill="'.htmlspecialchars($light).'"/><path d="'.$path.'" fill="'.htmlspecialchars($dark).'"/></svg>';
    }

    /** @return array<int, array<int, bool>> */
    public static function matrix(string $text): array
    {
        return (new self($text))->modules;
    }

    public function __construct(string $text)
    {
        $bytes = array_values(unpack('C*', $text) ?: []);

        $this->version = 0;
        for ($v = 1; $v <= 10; $v++) {
            $countBits = $v < 10 ? 8 : 16;
            if (4 + $countBits + 8 * count($bytes) <= self::numDataCodewords($v) * 8) {
                $this->version = $v;
                break;
            }
        }
        if ($this->version === 0) {
            throw new \InvalidArgumentException('النص أطول من المسموح لرمز QR.');
        }

        $this->size = $this->version * 4 + 17;
        $row = array_fill(0, $this->size, false);
        $this->modules = array_fill(0, $this->size, $row);
        $this->isFunction = array_fill(0, $this->size, $row);

        $this->drawFunctionPatterns();
        $this->drawCodewords($this->addEccAndInterleave($this->dataCodewords($bytes)));

        $best = 0;
        $minPenalty = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $this->applyMask($mask);
            $this->drawFormatBits($mask);
            $penalty = $this->penalty();
            if ($penalty < $minPenalty) {
                $best = $mask;
                $minPenalty = $penalty;
            }
            $this->applyMask($mask); // تراجع (XOR)
        }
        $this->applyMask($best);
        $this->drawFormatBits($best);
    }

    // ---------------------------------------------------------------- البيانات

    private function dataCodewords(array $bytes): array
    {
        $bits = [];
        $append = function (int $val, int $len) use (&$bits) {
            for ($i = $len - 1; $i >= 0; $i--) {
                $bits[] = ($val >> $i) & 1;
            }
        };
        $append(0x4, 4);
        $append(count($bytes), $this->version < 10 ? 8 : 16);
        foreach ($bytes as $b) {
            $append($b, 8);
        }

        $capacity = self::numDataCodewords($this->version) * 8;
        $append(0, min(4, $capacity - count($bits)));
        $append(0, (8 - count($bits) % 8) % 8);
        for ($pad = 0xEC; count($bits) < $capacity; $pad ^= 0xEC ^ 0x11) {
            $append($pad, 8);
        }

        $result = [];
        foreach (array_chunk($bits, 8) as $chunk) {
            $result[] = array_reduce($chunk, fn ($acc, $bit) => ($acc << 1) | $bit, 0);
        }

        return $result;
    }

    private static function numRawDataModules(int $ver): int
    {
        $result = (16 * $ver + 128) * $ver + 64;
        if ($ver >= 2) {
            $numAlign = intdiv($ver, 7) + 2;
            $result -= (25 * $numAlign - 10) * $numAlign - 55;
            if ($ver >= 7) {
                $result -= 36;
            }
        }

        return $result;
    }

    private static function numDataCodewords(int $ver): int
    {
        [$ecc, $blocks] = self::ECC_M[$ver];

        return intdiv(self::numRawDataModules($ver), 8) - $ecc * $blocks;
    }

    private function addEccAndInterleave(array $data): array
    {
        [$blockEccLen, $numBlocks] = self::ECC_M[$this->version];
        $rawCodewords = intdiv(self::numRawDataModules($this->version), 8);
        $numShortBlocks = $numBlocks - $rawCodewords % $numBlocks;
        $shortBlockLen = intdiv($rawCodewords, $numBlocks);

        $divisor = self::rsDivisor($blockEccLen);
        $blocks = [];
        $k = 0;
        for ($i = 0; $i < $numBlocks; $i++) {
            $len = $shortBlockLen - $blockEccLen + ($i < $numShortBlocks ? 0 : 1);
            $dat = array_slice($data, $k, $len);
            $k += $len;
            $ecc = self::rsRemainder($dat, $divisor);
            if ($i < $numShortBlocks) {
                $dat[] = 0;
            }
            $blocks[] = array_merge($dat, $ecc);
        }

        $result = [];
        $blockLen = count($blocks[0]);
        for ($i = 0; $i < $blockLen; $i++) {
            foreach ($blocks as $j => $block) {
                if ($i !== $shortBlockLen - $blockEccLen || $j >= $numShortBlocks) {
                    $result[] = $block[$i];
                }
            }
        }

        return $result;
    }

    private static function rsDivisor(int $degree): array
    {
        $result = array_fill(0, $degree, 0);
        $result[$degree - 1] = 1;
        $root = 1;
        for ($i = 0; $i < $degree; $i++) {
            for ($j = 0; $j < $degree; $j++) {
                $result[$j] = self::gfMul($result[$j], $root);
                if ($j + 1 < $degree) {
                    $result[$j] ^= $result[$j + 1];
                }
            }
            $root = self::gfMul($root, 0x02);
        }

        return $result;
    }

    private static function rsRemainder(array $data, array $divisor): array
    {
        $result = array_fill(0, count($divisor), 0);
        foreach ($data as $b) {
            $factor = $b ^ array_shift($result);
            $result[] = 0;
            foreach ($divisor as $i => $coef) {
                $result[$i] ^= self::gfMul($coef, $factor);
            }
        }

        return $result;
    }

    private static function gfMul(int $x, int $y): int
    {
        $z = 0;
        for ($i = 7; $i >= 0; $i--) {
            $z = (($z << 1) ^ (($z >> 7) * 0x11D)) & 0xFF;
            $z ^= (($y >> $i) & 1) * $x;
        }

        return $z;
    }

    // ---------------------------------------------------------------- الرسم

    private function setFunction(int $x, int $y, bool $dark): void
    {
        $this->modules[$y][$x] = $dark;
        $this->isFunction[$y][$x] = true;
    }

    private function drawFunctionPatterns(): void
    {
        for ($i = 0; $i < $this->size; $i++) {
            $this->setFunction(6, $i, $i % 2 === 0);
            $this->setFunction($i, 6, $i % 2 === 0);
        }
        $this->drawFinder(3, 3);
        $this->drawFinder($this->size - 4, 3);
        $this->drawFinder(3, $this->size - 4);

        $pos = $this->alignmentPositions();
        $n = count($pos);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $n - 1) || ($i === $n - 1 && $j === 0)) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $this->setFunction($pos[$i] + $dx, $pos[$j] + $dy, max(abs($dx), abs($dy)) !== 1);
                    }
                }
            }
        }

        $this->drawFormatBits(0); // حجز مكان معلومات التنسيق
        $this->drawVersion();
    }

    private function drawFinder(int $x, int $y): void
    {
        for ($dy = -4; $dy <= 4; $dy++) {
            for ($dx = -4; $dx <= 4; $dx++) {
                $dist = max(abs($dx), abs($dy));
                $xx = $x + $dx;
                $yy = $y + $dy;
                if ($xx >= 0 && $xx < $this->size && $yy >= 0 && $yy < $this->size) {
                    $this->setFunction($xx, $yy, $dist !== 2 && $dist !== 4);
                }
            }
        }
    }

    private function alignmentPositions(): array
    {
        if ($this->version === 1) {
            return [];
        }
        $numAlign = intdiv($this->version, 7) + 2;
        $step = intdiv($this->version * 8 + $numAlign * 3 + 5, $numAlign * 4 - 4) * 2;
        $result = [6];
        for ($pos = $this->size - 7; count($result) < $numAlign; $pos -= $step) {
            array_splice($result, 1, 0, [$pos]);
        }

        return $result;
    }

    private function drawFormatBits(int $mask): void
    {
        $data = (0 << 3) | $mask; // مستوى M = 00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $bit = fn (int $i) => (($bits >> $i) & 1) !== 0;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunction(8, $i, $bit($i));
        }
        $this->setFunction(8, 7, $bit(6));
        $this->setFunction(8, 8, $bit(7));
        $this->setFunction(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunction(14 - $i, 8, $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->setFunction($this->size - 1 - $i, 8, $bit($i));
        }
        for ($i = 8; $i < 15; $i++) {
            $this->setFunction(8, $this->size - 15 + $i, $bit($i));
        }
        $this->setFunction(8, $this->size - 8, true);
    }

    private function drawVersion(): void
    {
        if ($this->version < 7) {
            return;
        }
        $rem = $this->version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = ($this->version << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) !== 0;
            $a = $this->size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $this->setFunction($a, $b, $dark);
            $this->setFunction($b, $a, $dark);
        }
    }

    private function drawCodewords(array $data): void
    {
        $i = 0;
        $total = count($data) * 8;
        for ($right = $this->size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $this->size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $upward = (($right + 1) & 2) === 0;
                    $y = $upward ? $this->size - 1 - $vert : $vert;
                    if (! $this->isFunction[$y][$x] && $i < $total) {
                        $this->modules[$y][$x] = (($data[$i >> 3] >> (7 - ($i & 7))) & 1) !== 0;
                        $i++;
                    }
                }
            }
        }
    }

    private function applyMask(int $mask): void
    {
        for ($y = 0; $y < $this->size; $y++) {
            for ($x = 0; $x < $this->size; $x++) {
                $invert = match ($mask) {
                    0 => ($x + $y) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($x + $y) % 3 === 0,
                    4 => (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0,
                    5 => ($x * $y) % 2 + ($x * $y) % 3 === 0,
                    6 => (($x * $y) % 2 + ($x * $y) % 3) % 2 === 0,
                    default => ((($x + $y) % 2) + ($x * $y) % 3) % 2 === 0,
                };
                if ($invert && ! $this->isFunction[$y][$x]) {
                    $this->modules[$y][$x] = ! $this->modules[$y][$x];
                }
            }
        }
    }

    /** تقدير عقوبة القناع وفق قواعد المواصفة الأربع (لاختيار القناع الأوضح للقراءة) */
    private function penalty(): int
    {
        $n = $this->size;
        $score = 0;
        $lines = [];
        for ($y = 0; $y < $n; $y++) {
            $lines[] = implode('', array_map(fn ($m) => $m ? '1' : '0', $this->modules[$y]));
        }
        for ($x = 0; $x < $n; $x++) {
            $col = '';
            for ($y = 0; $y < $n; $y++) {
                $col .= $this->modules[$y][$x] ? '1' : '0';
            }
            $lines[] = $col;
        }

        foreach ($lines as $line) {
            if (preg_match_all('/0{5,}|1{5,}/', $line, $m)) {
                foreach ($m[0] as $run) {
                    $score += 3 + strlen($run) - 5;
                }
            }
            $score += 40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101'));
        }

        $dark = 0;
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $c = $this->modules[$y][$x];
                $dark += $c ? 1 : 0;
                if ($x < $n - 1 && $y < $n - 1 && $c === $this->modules[$y][$x + 1] && $c === $this->modules[$y + 1][$x] && $c === $this->modules[$y + 1][$x + 1]) {
                    $score += 3;
                }
            }
        }
        $total = $n * $n;
        $k = intdiv(abs($dark * 20 - $total * 10) + $total - 1, $total) - 1;
        $score += max(0, $k) * 10;

        return $score;
    }
}
