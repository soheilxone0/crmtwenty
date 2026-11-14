import { type Temporal } from 'temporal-polyfill';

// JS Date at UTC midnight of the given plain date (any calendar).
export const toUtcDate = (date: Temporal.PlainDate): Date => {
  const isoDate = date.withCalendar('iso8601');

  return new Date(Date.UTC(isoDate.year, isoDate.month - 1, isoDate.day));
};
