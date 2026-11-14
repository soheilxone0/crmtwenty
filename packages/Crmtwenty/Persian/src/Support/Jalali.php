<?php

namespace Crmtwenty\Persian\Support;

use Carbon\Carbon;
use DateTimeInterface;

/**
 * Solar Hijri (Jalali / شمسی) calendar helpers.
 *
 * Conversion algorithm ported from jalaali-js (MIT), which is exact for the
 * years 1 to 3177 of the Jalali calendar (it follows the 2820-year cycle
 * breaks used by the Iranian calendar authority).
 */
class Jalali
{
    public const MONTHS = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    /** Indexed by PHP's date('w'): 0 = Sunday. */
    public const WEEKDAYS = [
        0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه',
    ];

    private const BREAKS = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
    ];

    /**
     * Gregorian to Jalali: returns [jy, jm, jd].
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    /**
     * Jalali to Gregorian: returns [gy, gm, gd].
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    public static function isValidJalaliDate(int $jy, int $jm, int $jd): bool
    {
        return $jy >= -61 && $jy <= 3177
            && $jm >= 1 && $jm <= 12
            && $jd >= 1 && $jd <= self::monthLength($jy, $jm);
    }

    public static function isLeapJalaliYear(int $jy): bool
    {
        return self::jalCal($jy, true)['leap'] === 0;
    }

    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        return self::isLeapJalaliYear($jy) ? 30 : 29;
    }

    /**
     * Formats a date with a PHP date()-like format string in the Jalali calendar.
     *
     * Supported: d j D l N w m n M F y Y H G h g i s A a, plus any character
     * escaped with a backslash. Digits are converted to Persian digits unless
     * $persianDigits is false.
     */
    public static function format(DateTimeInterface|string|int|null $date, string $format = 'j F Y', bool $persianDigits = true, ?string $timezone = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = $date instanceof DateTimeInterface
            ? Carbon::instance($date)
            : (is_int($date) ? Carbon::createFromTimestamp($date) : Carbon::parse($date));

        if ($timezone) {
            $carbon = $carbon->copy()->setTimezone($timezone);
        }

        [$jy, $jm, $jd] = self::toJalali((int) $carbon->format('Y'), (int) $carbon->format('n'), (int) $carbon->format('j'));

        $weekday = (int) $carbon->format('w');

        $out = '';
        $length = mb_strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($format, $i, 1);

            if ($char === '\\') {
                $i++;
                $out .= $i < $length ? mb_substr($format, $i, 1) : '';

                continue;
            }

            $out .= match ($char) {
                'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
                'j' => (string) $jd,
                'D', 'l' => self::WEEKDAYS[$weekday],
                'N' => (string) ((($weekday + 1) % 7) + 1), // 1 = Saturday … 7 = Friday
                'w' => (string) (($weekday + 1) % 7),       // 0 = Saturday … 6 = Friday
                'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
                'n' => (string) $jm,
                'M', 'F' => self::MONTHS[$jm],
                'y' => str_pad((string) ($jy % 100), 2, '0', STR_PAD_LEFT),
                'Y', 'o' => (string) $jy,
                't' => (string) self::monthLength($jy, $jm),
                'L' => self::isLeapJalaliYear($jy) ? '1' : '0',
                'A' => (int) $carbon->format('G') < 12 ? 'ق.ظ' : 'ب.ظ',
                'a' => (int) $carbon->format('G') < 12 ? 'ق.ظ' : 'ب.ظ',
                'H', 'G', 'h', 'g', 'i', 's', 'u', 'v', 'e', 'T', 'P', 'O', 'Z', 'U' => $carbon->format($char),
                default => $char,
            };
        }

        return $persianDigits ? self::toPersianDigits($out) : $out;
    }

    /**
     * Parses "1405/08/23" (or with "-", optional " 14:30[:00]", Persian or
     * Arabic digits) into a Carbon instance; returns null when invalid.
     */
    public static function parse(?string $value, ?string $timezone = null): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $value = trim(self::toLatinDigits($value));

        if (! preg_match('#^(\d{3,4})[/\-](\d{1,2})[/\-](\d{1,2})(?:[ T](\d{1,2}):(\d{2})(?::(\d{2}))?)?$#', $value, $m)) {
            return null;
        }

        [$jy, $jm, $jd] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        if (! self::isValidJalaliDate($jy, $jm, $jd)) {
            return null;
        }

        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);

        $hour = isset($m[4]) ? (int) $m[4] : 0;
        $minute = isset($m[5]) ? (int) $m[5] : 0;
        $second = isset($m[6]) ? (int) $m[6] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        return Carbon::create($gy, $gm, $gd, $hour, $minute, $second, $timezone);
    }

    public static function toPersianDigits(string $value): string
    {
        return strtr($value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    public static function toLatinDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    private static function jalCal(int $jy, bool $withoutLeap = false): array
    {
        $bl = count(self::BREAKS);
        $gy = $jy + 621;
        $leapJ = -14;
        $jp = self::BREAKS[0];
        $jump = 0;

        if ($jy < $jp || $jy >= self::BREAKS[$bl - 1]) {
            throw new \InvalidArgumentException("Invalid Jalali year $jy");
        }

        for ($i = 1; $i < $bl; $i++) {
            $jm = self::BREAKS[$i];
            $jump = $jm - $jp;

            if ($jy < $jm) {
                break;
            }

            $leapJ += self::div($jump, 33) * 8 + self::div(self::mod($jump, 33), 4);
            $jp = $jm;
        }

        $n = $jy - $jp;
        $leapJ += self::div($n, 33) * 8 + self::div(self::mod($n, 33) + 3, 4);

        if (self::mod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ++;
        }

        $leapG = self::div($gy, 4) - self::div((self::div($gy, 100) + 1) * 3, 4) - 150;
        $march = 20 + $leapJ - $leapG;

        if ($withoutLeap) {
            return ['gy' => $gy, 'march' => $march, 'leap' => self::leapOf($jump, $n)];
        }

        return ['leap' => self::leapOf($jump, $n), 'gy' => $gy, 'march' => $march];
    }

    private static function leapOf(int $jump, int $n): int
    {
        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }

        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);

        if ($leap === -1) {
            $leap = 4;
        }

        return $leap;
    }

    private static function j2d(int $jy, int $jm, int $jd): int
    {
        $r = self::jalCal($jy, true);

        return self::g2d($r['gy'], 3, $r['march']) + ($jm - 1) * 31 - self::div($jm, 7) * ($jm - 7) + $jd - 1;
    }

    private static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)[0];
        $jy = $gy - 621;
        $r = self::jalCal($jy, false);
        $jdn1f = self::g2d($gy, 3, $r['march']);
        $k = $jdn - $jdn1f;

        if ($k >= 0) {
            if ($k <= 185) {
                return [$jy, 1 + self::div($k, 31), self::mod($k, 31) + 1];
            }

            $k -= 186;
        } else {
            $jy -= 1;
            $k += 179;

            if ($r['leap'] === 1) {
                $k += 1;
            }
        }

        return [$jy, 7 + self::div($k, 30), self::mod($k, 30) + 1];
    }

    private static function g2d(int $gy, int $gm, int $gd): int
    {
        $d = self::div(($gy + self::div($gm - 8, 6) + 100100) * 1461, 4)
            + self::div(153 * self::mod($gm + 9, 12) + 2, 5)
            + $gd - 34840408;

        return $d - self::div(self::div($gy + 100100 + self::div($gm - 8, 6), 100) * 3, 4) + 752;
    }

    private static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j = $j + self::div(self::div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::div(self::mod($j, 1461), 4) * 5 + 308;
        $gd = self::div(self::mod($i, 153), 5) + 1;
        $gm = self::mod(self::div($i, 153), 12) + 1;
        $gy = self::div($j, 1461) - 100100 + self::div(8 - $gm, 6);

        return [$gy, $gm, $gd];
    }

    private static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    private static function mod(int $a, int $b): int
    {
        return $a - intdiv($a, $b) * $b;
    }
}
