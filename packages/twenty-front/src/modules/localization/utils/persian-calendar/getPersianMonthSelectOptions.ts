import { PERSIAN_INTL_LOCALE } from '@/localization/utils/persian-calendar/PersianCalendar';

const MONTH_COUNT = 12;
// 1 Farvardin 1400 = 21 March 2021; each sample date lies inside Jalali month
// index + 1 (months 1-6 have 31 days, 7-11 have 30).
const FIRST_DAY_OF_YEAR_UTC = Date.UTC(2021, 2, 21);
const DAY_IN_MS = 24 * 60 * 60 * 1000;

export const getPersianMonthSelectOptions = (): {
  label: string;
  value: number;
}[] => {
  const formatter = new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
    month: 'long',
    timeZone: 'UTC',
  });

  return Array.from({ length: MONTH_COUNT }, (_, index) => ({
    label: formatter.format(
      new Date(FIRST_DAY_OF_YEAR_UTC + (index * 30 + 15) * DAY_IN_MS),
    ),
    value: index + 1,
  }));
};
