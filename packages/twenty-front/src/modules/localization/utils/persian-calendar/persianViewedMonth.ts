import { Temporal } from 'temporal-polyfill';

import { PERSIAN_CALENDAR } from '@/localization/utils/persian-calendar/PersianCalendar';
import { toIsoPlainDate } from '@/localization/utils/persian-calendar/toIsoPlainDate';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';

// The date pickers identify the displayed month with a Gregorian "YYYY-MM"
// string. Jalali months do not line up with Gregorian ones, so in Persian mode
// a Jalali month is represented by the Gregorian month that contains its 15th
// day. Jalali months always start between the 19th and the 23rd of a Gregorian
// month, so this mapping is a bijection:
//   Jalali month  -> Gregorian month of its 15th day
//   "YYYY-MM"     -> Jalali month containing the 10th of that Gregorian month
const VIEWED_MONTH_ANCHOR_DAY = 15;
const GREGORIAN_PROBE_DAY = '10';

const toViewedMonth = (persianDate: Temporal.PlainDate): string =>
  toIsoPlainDate(persianDate.with({ day: VIEWED_MONTH_ANCHOR_DAY }))
    .toString()
    .slice(0, 7);

const fromViewedMonth = (viewedMonth: string): Temporal.PlainDate =>
  toPersianPlainDate(`${viewedMonth}-${GREGORIAN_PROBE_DAY}`);

export const getPersianViewedMonthFromPlainDate = (
  date: Temporal.PlainDate | string,
): string => toViewedMonth(toPersianPlainDate(date));

export const getPersianYearMonthFromViewedMonth = (
  viewedMonth: string,
): { year: number; month: number; daysInMonth: number } => {
  const persianDate = fromViewedMonth(viewedMonth);

  return {
    year: persianDate.year,
    month: persianDate.month,
    daysInMonth: persianDate.daysInMonth,
  };
};

// First day (ISO calendar) of the Jalali month represented by viewedMonth.
export const getPersianMonthFirstDayFromViewedMonth = (
  viewedMonth: string,
): Temporal.PlainDate => toIsoPlainDate(fromViewedMonth(viewedMonth).with({ day: 1 }));

export const shiftPersianViewedMonth = (
  viewedMonth: string,
  months: number,
): string =>
  toViewedMonth(
    fromViewedMonth(viewedMonth)
      .with({ day: VIEWED_MONTH_ANCHOR_DAY })
      .add({ months }),
  );

export const setPersianViewedMonth = (
  viewedMonth: string,
  { year, month }: { year?: number; month?: number },
): string => {
  const current = fromViewedMonth(viewedMonth);

  return toViewedMonth(
    Temporal.PlainDate.from({
      calendar: PERSIAN_CALENDAR,
      year: year ?? current.year,
      month: month ?? current.month,
      day: VIEWED_MONTH_ANCHOR_DAY,
    }),
  );
};
