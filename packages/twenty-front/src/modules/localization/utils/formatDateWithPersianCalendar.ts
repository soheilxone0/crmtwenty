import { type Locale } from 'date-fns';

// crmtwenty customization: when the interface language is Persian, dates are
// displayed in the Solar Hijri (Jalali / شمسی) calendar with Persian digits,
// e.g. "۲۳ آبان ۱۴۰۵". Storage, filters and the API keep using ISO
// (Gregorian) dates, so this only affects how values are rendered.
export const isPersianCalendarLocale = (localeCatalog?: Locale): boolean =>
  localeCatalog?.code?.startsWith('fa') === true;

export const formatDateWithPersianCalendar = ({
  date,
  timeZone,
  withTime = false,
  hour12 = false,
}: {
  date: Date;
  timeZone: string;
  withTime?: boolean;
  hour12?: boolean;
}): string => {
  const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit', hour12 } : {}),
  };

  try {
    return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
      ...options,
      timeZone,
    }).format(date);
  } catch {
    // Unknown time zone identifier: fall back to the browser time zone.
    return new Intl.DateTimeFormat('fa-IR-u-ca-persian', options).format(date);
  }
};
