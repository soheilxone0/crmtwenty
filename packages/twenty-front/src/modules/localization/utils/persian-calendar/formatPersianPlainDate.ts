import { type Temporal } from 'temporal-polyfill';

import { toUtcDate } from '@/localization/utils/persian-calendar/toUtcDate';
import { PERSIAN_INTL_LOCALE } from '@/localization/utils/persian-calendar/PersianCalendar';

// Formats an ISO plain date in the Jalali calendar, e.g. "۲۳ آبان ۱۴۰۵".
export const formatPersianPlainDate = (
  date: Temporal.PlainDate,
  options: Intl.DateTimeFormatOptions = {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  },
): string =>
  new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
    ...options,
    timeZone: 'UTC',
  }).format(toUtcDate(date));
