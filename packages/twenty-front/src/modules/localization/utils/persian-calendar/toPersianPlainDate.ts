import { Temporal } from 'temporal-polyfill';

import { PERSIAN_CALENDAR } from '@/localization/utils/persian-calendar/PersianCalendar';

export const toPersianPlainDate = (
  date: Temporal.PlainDate | string,
): Temporal.PlainDate =>
  (typeof date === 'string' ? Temporal.PlainDate.from(date) : date).withCalendar(
    PERSIAN_CALENDAR,
  );
