import { enUS } from 'date-fns/locale';
import { faIR } from 'date-fns/locale/fa-IR';

import { formatDateISOStringToDate } from '@/localization/utils/formatDateISOStringToDate';
import { formatDateISOStringToDateTime } from '@/localization/utils/formatDateISOStringToDateTime';
import {
  formatDateWithPersianCalendar,
  isPersianCalendarLocale,
} from '@/localization/utils/formatDateWithPersianCalendar';
import { DateFormat } from '@/localization/constants/DateFormat';
import { TimeFormat } from '@/localization/constants/TimeFormat';

describe('formatDateWithPersianCalendar', () => {
  it('should detect Persian locales only', () => {
    expect(isPersianCalendarLocale(faIR)).toBe(true);
    expect(isPersianCalendarLocale(enUS)).toBe(false);
    expect(isPersianCalendarLocale(undefined)).toBe(false);
  });

  it('should format a date in the Solar Hijri calendar', () => {
    expect(
      formatDateWithPersianCalendar({
        date: new Date(Date.UTC(2026, 2, 21)),
        timeZone: 'UTC',
      }),
    ).toBe('۱ فروردین ۱۴۰۵');
  });

  it('should respect the time zone when formatting date times', () => {
    expect(
      formatDateISOStringToDateTime({
        date: '2026-11-14T10:30:00Z',
        timeZone: 'Asia/Tehran',
        dateFormat: DateFormat.DAY_FIRST,
        timeFormat: TimeFormat.HOUR_24,
        localeCatalog: faIR,
      }),
    ).toContain('۲۳ آبان ۱۴۰۵');
  });

  it('should format plain dates without time zone shifts', () => {
    expect(
      formatDateISOStringToDate({
        date: '2026-03-21',
        timeZone: 'America/Los_Angeles',
        dateFormat: DateFormat.DAY_FIRST,
        localeCatalog: faIR,
      }),
    ).toBe('۱ فروردین ۱۴۰۵');
  });

  it('should keep the Gregorian format for other locales', () => {
    expect(
      formatDateISOStringToDate({
        date: '2026-03-21',
        timeZone: 'UTC',
        dateFormat: DateFormat.DAY_FIRST,
        localeCatalog: enUS,
      }),
    ).toBe('21 Mar, 2026');
  });
});
