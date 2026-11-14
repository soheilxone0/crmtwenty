import {
  formatDateWithPersianCalendar,
  isPersianCalendarLocale,
} from '@/localization/utils/formatDateWithPersianCalendar';
import { format, type Locale } from 'date-fns';
import { Temporal } from 'temporal-polyfill';

export const formatPlainDateISOString = ({
  date,
  dateFormat,
  localeCatalog,
}: {
  date: string;
  dateFormat: string;
  localeCatalog?: Locale;
}) => {
  const plainDate = Temporal.PlainDate.from(date);

  if (isPersianCalendarLocale(localeCatalog)) {
    return formatDateWithPersianCalendar({
      date: new Date(
        Date.UTC(plainDate.year, plainDate.month - 1, plainDate.day),
      ),
      timeZone: 'UTC',
    });
  }

  return format(
    new Date(plainDate.year, plainDate.month - 1, plainDate.day),
    dateFormat,
    { locale: localeCatalog },
  );
};
