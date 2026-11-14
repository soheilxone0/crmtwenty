import { type DateFormat } from '@/localization/constants/DateFormat';
import {
  formatDateWithPersianCalendar,
  isPersianCalendarLocale,
} from '@/localization/utils/formatDateWithPersianCalendar';
import { formatPlainDateISOString } from '@/localization/utils/formatPlainDateISOString';
import { type Locale } from 'date-fns';
import { formatInTimeZone } from 'date-fns-tz';
import { isDateWithoutTime } from 'twenty-shared/utils';

export const formatDateISOStringToDate = ({
  date,
  timeZone,
  dateFormat,
  localeCatalog,
}: {
  date: string;
  timeZone: string;
  dateFormat: DateFormat;
  localeCatalog?: Locale;
}) => {
  if (isDateWithoutTime(date)) {
    return formatPlainDateISOString({ date, dateFormat, localeCatalog });
  }

  if (isPersianCalendarLocale(localeCatalog)) {
    return formatDateWithPersianCalendar({ date: new Date(date), timeZone });
  }

  return formatInTimeZone(new Date(date), timeZone, dateFormat, {
    locale: localeCatalog,
  });
};
