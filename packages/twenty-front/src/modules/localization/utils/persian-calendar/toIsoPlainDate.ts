import { type Temporal } from 'temporal-polyfill';

export const toIsoPlainDate = (date: Temporal.PlainDate): Temporal.PlainDate =>
  date.withCalendar('iso8601');
