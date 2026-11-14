<?php

use Crmtwenty\Persian\Support\Jalali;
use Illuminate\Support\Carbon;

/**
 * crmtwenty: the Jalali (شمسی) calendar used everywhere in the Persian panel.
 */
afterEach(function () {
    app()->setLocale(config('app.locale'));
});

it('converts between the gregorian and jalali calendars', function () {
    expect(Jalali::toJalali(2026, 11, 14))->toBe([1405, 8, 23])
        ->and(Jalali::toJalali(2025, 3, 21))->toBe([1404, 1, 1])
        ->and(Jalali::toGregorian(1405, 8, 23))->toBe([2026, 11, 14])
        ->and(Jalali::isLeapJalaliYear(1403))->toBeTrue()
        ->and(Jalali::isLeapJalaliYear(1404))->toBeFalse()
        ->and(Jalali::monthLength(1403, 12))->toBe(30)
        ->and(Jalali::monthLength(1404, 12))->toBe(29);
});

it('round-trips every day of several years', function () {
    $date = Carbon::create(2020, 1, 1);

    while ($date->year < 2031) {
        [$jy, $jm, $jd] = Jalali::toJalali($date->year, $date->month, $date->day);

        expect(Jalali::toGregorian($jy, $jm, $jd))->toBe([$date->year, $date->month, $date->day]);

        $date->addDay();
    }
});

it('parses jalali input with persian digits', function () {
    $date = Jalali::parse('۱۴۰۵/۰۸/۲۳ 14:30');

    expect($date)->not->toBeNull()
        ->and($date->format('Y-m-d H:i'))->toBe('2026-11-14 14:30')
        ->and(Jalali::parse('1404/12/30'))->toBeNull();
});

it('formats dates in the jalali calendar when the panel is in persian', function () {
    app()->setLocale('fa');

    expect(core()->formatDate('2026-11-14 14:30:00', 'Y/m/d'))->toBe('۱۴۰۵/۰۸/۲۳')
        ->and(core()->formatDate('2026-11-14', 'j F Y'))->toBe('۲۳ آبان ۱۴۰۵')
        ->and(core()->formatDate('2026-11-14', 'l'))->toBe('شنبه');
});

it('keeps gregorian dates in english', function () {
    app()->setLocale('en');

    expect(core()->formatDate('2026-11-14 14:30:00'))->toBe('14 Nov 2026 02:30PM');
});

it('ships complete persian translations', function () {
    $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($lines as $key => $value) {
            $keys = is_array($value)
                ? array_merge($keys, $flatten($value, $prefix.$key.'.'))
                : array_merge($keys, [$prefix.$key]);
        }

        return $keys;
    };

    foreach ([
        base_path('packages/Webkul/Admin/src/Resources/lang'),
        base_path('packages/Crmtwenty/Persian/src/Resources/lang'),
    ] as $directory) {
        $missing = array_diff(
            $flatten(require $directory.'/en/app.php'),
            $flatten(require $directory.'/fa/app.php')
        );

        expect($missing)->toBe([]);
    }
});
