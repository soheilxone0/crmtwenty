import { type Temporal } from 'temporal-polyfill';

import { PERSIAN_INTL_LOCALE } from '@/localization/utils/persian-calendar/PersianCalendar';
import { toUtcDate } from '@/localization/utils/persian-calendar/toUtcDate';

// e.g. "آبان ۱۴۰۵" (ICU orders it "۱۴۰۵ آبان", so the parts are joined here).
export const formatPersianMonthYear = (date: Temporal.PlainDate): string => {
  const parts = new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
  }).formatToParts(toUtcDate(date));

  const month = parts.find((part) => part.type === 'month')?.value ?? '';
  const year = parts.find((part) => part.type === 'year')?.value ?? '';

  return `${month} ${year}`.trim();
};
