/**
 * crmtwenty: Solar Hijri (Jalali / شمسی) calendar helpers shared by the
 * admin panel (date formatting, date pickers, activity calendar).
 *
 * Conversions use jalaali-js, which implements the official Iranian calendar
 * algorithm. Values exchanged with the server always stay Gregorian.
 */
import { toJalaali, toGregorian, isValidJalaaliDate, jalaaliMonthLength } from "jalaali-js";

export const JALALI_MONTHS = [
    "فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور",
    "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند",
];

/** Indexed by Date#getDay(): 0 = Sunday. */
export const JALALI_WEEKDAYS = ["یکشنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنجشنبه", "جمعه", "شنبه"];

/** Narrow labels, Saturday first. */
export const JALALI_WEEKDAYS_NARROW = ["ش", "ی", "د", "س", "چ", "پ", "ج"];

const PERSIAN_DIGITS = "۰۱۲۳۴۵۶۷۸۹";

export const isPersianLocale = () =>
    (document.documentElement.lang || "").toLowerCase().startsWith("fa");

export const toPersianDigits = (value) =>
    String(value ?? "").replace(/[0-9]/g, (digit) => PERSIAN_DIGITS[digit]);

export const toLatinDigits = (value) =>
    String(value ?? "")
        .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x06f0))
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660));

const pad = (value, length = 2) => String(value).padStart(length, "0");

/** Local Date -> { jy, jm, jd }. */
export const dateToJalali = (date) =>
    toJalaali(date.getFullYear(), date.getMonth() + 1, date.getDate());

/** Jalali parts -> local Date at midnight (or the given time). */
export const jalaliToDate = (jy, jm, jd, hours = 0, minutes = 0, seconds = 0) => {
    const { gy, gm, gd } = toGregorian(jy, jm, jd);

    return new Date(gy, gm - 1, gd, hours, minutes, seconds);
};

export const jalaliMonthLength = (jy, jm) => jalaaliMonthLength(jy, jm);

export const isValidJalaliDate = (jy, jm, jd) => isValidJalaaliDate(jy, jm, jd);

/**
 * Parses server values ("2026-11-14", "2026-11-14 14:30:00", ISO strings,
 * Date objects) into a local Date. Returns null when empty or invalid.
 */
export const parseServerDate = (value) => {
    if (value instanceof Date) {
        return isNaN(value) ? null : new Date(value.getTime());
    }

    if (value === null || value === undefined || value === "") {
        return null;
    }

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);

    if (match) {
        const [, y, m, d, h = 0, i = 0, s = 0] = match;

        return new Date(+y, +m - 1, +d, +h, +i, +s);
    }

    const date = new Date(value);

    return isNaN(date) ? null : date;
};

/** Local Date -> "Y-m-d" or "Y-m-d H:i:S" (Gregorian, for the server). */
export const formatServerDate = (date, withTime = false) => {
    const base = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

    return withTime
        ? `${base} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`
        : base;
};

/** "1405/08/23" or "1405/08/23 14:30" with Latin digits (easy to type). */
export const formatJalaliInput = (date, withTime = false) => {
    const { jy, jm, jd } = dateToJalali(date);
    const base = `${jy}/${pad(jm)}/${pad(jd)}`;

    return withTime ? `${base} ${pad(date.getHours())}:${pad(date.getMinutes())}` : base;
};

/**
 * Parses "1405/08/23", "1405-8-23 14:30", with Persian or Latin digits.
 * Returns a local Date or null.
 */
export const parseJalaliInput = (value) => {
    const match = toLatinDigits(value)
        .trim()
        .match(/^(\d{3,4})[/\-.](\d{1,2})[/\-.](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/);

    if (! match) {
        return null;
    }

    const [jy, jm, jd, h = 0, i = 0, s = 0] = match.slice(1).map((part) => (part === undefined ? 0 : +part));

    if (! isValidJalaaliDate(jy, jm, jd) || h > 23 || i > 59 || s > 59) {
        return null;
    }

    return jalaliToDate(jy, jm, jd, h, i, s);
};

/**
 * Date parts in a time zone (falls back to the browser time zone).
 */
const zonedParts = (date, timeZone) => {
    if (! timeZone) {
        return {
            year: date.getFullYear(),
            month: date.getMonth() + 1,
            day: date.getDate(),
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds(),
            weekday: date.getDay(),
        };
    }

    try {
        const parts = Object.fromEntries(
            new Intl.DateTimeFormat("en-US", {
                timeZone,
                hourCycle: "h23",
                year: "numeric",
                month: "numeric",
                day: "numeric",
                hour: "numeric",
                minute: "numeric",
                second: "numeric",
            })
                .formatToParts(date)
                .filter((part) => part.type !== "literal")
                .map((part) => [part.type, parseInt(part.value, 10)])
        );

        const weekday = new Date(parts.year, parts.month - 1, parts.day).getDay();

        return { ...parts, hour: parts.hour % 24, weekday };
    } catch (error) {
        return zonedParts(date, null);
    }
};

/**
 * Formats a date in the Jalali calendar with the token syntax used by the
 * admin panel ($admin.formatDate): d DD M MM MMM MMMM yy yyyy H HH h hh m mm A,
 * plus "dddd" (weekday name). Digits are Persian.
 */
export const formatJalali = (value, format = "d MMMM yyyy", timeZone = null) => {
    const date = parseServerDate(value);

    if (! date) {
        return "";
    }

    const parts = zonedParts(date, timeZone);
    const { jy, jm, jd } = toJalaali(parts.year, parts.month, parts.day);

    const tokens = {
        dddd: JALALI_WEEKDAYS[parts.weekday],
        d: jd,
        DD: pad(jd),
        M: jm,
        MM: pad(jm),
        MMM: JALALI_MONTHS[jm - 1],
        MMMM: JALALI_MONTHS[jm - 1],
        yy: String(jy).slice(-2),
        yyyy: jy,
        H: parts.hour,
        HH: pad(parts.hour),
        h: parts.hour % 12 || 12,
        hh: pad(parts.hour % 12 || 12),
        m: parts.minute,
        mm: pad(parts.minute),
        A: parts.hour < 12 ? "ق.ظ" : "ب.ظ",
    };

    return toPersianDigits(
        format.replace(/\b(?:dddd|d|DD|M|MM|MMM|MMMM|yy|yyyy|H|HH|h|hh|m|mm|A)\b/g, (token) => tokens[token])
    );
};

/** "آبان ۱۴۰۵" */
export const formatJalaliMonthYear = (jy, jm) => toPersianDigits(`${JALALI_MONTHS[jm - 1]} ${jy}`);

/** First and last local Date of the Jalali month containing `date`. */
export const jalaliMonthRange = (date, offset = 0) => {
    let { jy, jm } = dateToJalali(date);

    jm += offset;

    while (jm < 1) {
        jm += 12;
        jy -= 1;
    }

    while (jm > 12) {
        jm -= 12;
        jy += 1;
    }

    return [jalaliToDate(jy, jm, 1), jalaliToDate(jy, jm, jalaaliMonthLength(jy, jm))];
};

/** First and last local Date of the Jalali year containing `date`. */
export const jalaliYearRange = (date) => {
    const { jy } = dateToJalali(date);

    return [jalaliToDate(jy, 1, 1), jalaliToDate(jy, 12, jalaaliMonthLength(jy, 12))];
};

/**
 * Gregorian server value ("Y-m-d" / "Y-m-d H:i:s") -> "۱۴۰۵/۰۸/۲۳" / "۱۴۰۵/۰۸/۲۳ ۱۴:۳۰".
 * Anything that is not a date is returned unchanged.
 */
export const formatJalaliValue = (value) => {
    const match = String(value ?? "").match(/^\d{4}-\d{2}-\d{2}(?:[ T](\d{2}:\d{2}))?/);

    if (! match) {
        return value;
    }

    const date = parseServerDate(value);

    return date ? toPersianDigits(formatJalaliInput(date, !! match[1])) : value;
};

/** ["2026-11-01", "2026-11-14"] -> "۱۴۰۵/۰۸/۱۰ تا ۱۴۰۵/۰۸/۲۳" */
export const formatJalaliRange = (values) =>
    (values || []).filter((value) => value !== "" && value !== null && value !== undefined)
        .map(formatJalaliValue)
        .join(" تا ");

const helpers = {
    formatJalaliValue,
    formatJalaliRange,
    JALALI_MONTHS,
    JALALI_WEEKDAYS,
    JALALI_WEEKDAYS_NARROW,
    isPersianLocale,
    toPersianDigits,
    toLatinDigits,
    dateToJalali,
    jalaliToDate,
    jalaliMonthLength,
    isValidJalaliDate,
    parseServerDate,
    formatServerDate,
    formatJalaliInput,
    parseJalaliInput,
    formatJalali,
    formatJalaliMonthYear,
    jalaliMonthRange,
    jalaliYearRange,
};

export default helpers;
