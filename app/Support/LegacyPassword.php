<?php

namespace App\Support;

/**
 * التحقق من كلمات مرور ووردبريس المستوردة:
 * - الصيغة القديمة phpass ($P$ / $H$)
 * - صيغة ووردبريس 6.8+ ($wp$2y$...)
 * - bcrypt العادية ($2y$)
 */
class LegacyPassword
{
    protected const ITOA64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static function check(string $password, ?string $hash): bool
    {
        $hash = (string) $hash;
        if ($hash === '') {
            return false;
        }
        if (str_starts_with($hash, '$wp$')) {
            return password_verify(base64_encode(hash_hmac('sha384', $password, 'wp-sha384', true)), substr($hash, 3));
        }
        if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')) {
            return hash_equals($hash, static::phpass($password, $hash));
        }
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon2')) {
            return password_verify($password, $hash);
        }
        // كلمات مرور قديمة جدًا بصيغة MD5
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return hash_equals($hash, md5($password));
        }

        return false;
    }

    /** خوارزمية phpass المحمولة (المستخدمة في ووردبريس قبل 6.8) */
    public static function phpass(string $password, string $setting): string
    {
        $countLog2 = strpos(self::ITOA64, $setting[3] ?? '');
        if ($countLog2 === false || $countLog2 < 7 || $countLog2 > 30) {
            return '*';
        }
        $salt = substr($setting, 4, 8);
        if (strlen($salt) !== 8) {
            return '*';
        }

        $count = 1 << $countLog2;
        $hash = md5($salt.$password, true);
        do {
            $hash = md5($hash.$password, true);
        } while (--$count);

        return substr($setting, 0, 12).static::encode64($hash, 16);
    }

    protected static function encode64(string $input, int $count): string
    {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= self::ITOA64[$value & 0x3F];
            if ($i < $count) {
                $value |= ord($input[$i]) << 8;
            }
            $output .= self::ITOA64[($value >> 6) & 0x3F];
            if ($i++ >= $count) {
                break;
            }
            if ($i < $count) {
                $value |= ord($input[$i]) << 16;
            }
            $output .= self::ITOA64[($value >> 12) & 0x3F];
            if ($i++ >= $count) {
                break;
            }
            $output .= self::ITOA64[($value >> 18) & 0x3F];
        } while ($i < $count);

        return $output;
    }
}
