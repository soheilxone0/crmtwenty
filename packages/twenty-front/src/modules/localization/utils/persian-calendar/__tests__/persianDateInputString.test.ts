import {
  formatPlainDateToPersianInputString,
  parsePersianInputStringToPlainDate,
} from '@/localization/utils/persian-calendar/persianDateInputString';
import { formatPersianPlainDate } from '@/localization/utils/persian-calendar/formatPersianPlainDate';
import { formatPersianMonthYear } from '@/localization/utils/persian-calendar/formatPersianMonthYear';
import { Temporal } from 'temporal-polyfill';

describe('persianDateInputString', () => {
  it('formats an ISO date as a Jalali input string', () => {
    expect(formatPlainDateToPersianInputString('2026-11-14')).toBe(
      '1405/08/23',
    );
    expect(formatPlainDateToPersianInputString('2026-03-21')).toBe(
      '1405/01/01',
    );
  });

  it('parses a Jalali input string to an ISO date', () => {
    expect(
      parsePersianInputStringToPlainDate('1405/08/23')?.toString(),
    ).toBe('2026-11-14');
    expect(
      parsePersianInputStringToPlainDate('۱۴۰۳/۱۲/۳۰')?.toString(),
    ).toBe('2025-03-20');
  });

  it('rejects invalid Jalali dates', () => {
    // 1404 is not a leap year: Esfand has 29 days
    expect(parsePersianInputStringToPlainDate('1404/12/30')).toBe(null);
    expect(parsePersianInputStringToPlainDate('1405/13/01')).toBe(null);
    expect(parsePersianInputStringToPlainDate('1405/08/__')).toBe(null);
  });

  it('formats dates with Persian month names', () => {
    const date = Temporal.PlainDate.from('2026-11-14');

    expect(formatPersianPlainDate(date)).toBe('۲۳ آبان ۱۴۰۵');
    expect(formatPersianMonthYear(date)).toBe('آبان ۱۴۰۵');
    expect(formatPersianMonthYear(date.withCalendar('persian'))).toBe(
      'آبان ۱۴۰۵',
    );
  });
});
