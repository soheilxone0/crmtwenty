import { i18n } from '@lingui/core';
import { detectDateFormat } from '@/localization/utils/detection/detectDateFormat';
import { formatPersianPlainDate } from '@/localization/utils/persian-calendar/formatPersianPlainDate';
import { isPersianLocale } from '@/localization/utils/persian-calendar/isPersianLocale';
import { type Temporal } from 'temporal-polyfill';
import { WorkspaceMemberDateFormatEnum } from '~/generated-metadata/graphql';

export const formatZonedDateTimeDatePart = (
  zonedDateTime: Temporal.ZonedDateTime,
  dateFormat: WorkspaceMemberDateFormatEnum,
): string => {
  // crmtwenty customization: Jalali date for Persian users.
  if (isPersianLocale(i18n.locale)) {
    return formatPersianPlainDate(zonedDateTime.toPlainDate());
  }

  const MMM = zonedDateTime.toLocaleString('en-US', { month: 'short' });
  const d = zonedDateTime.day;
  const yyyy = zonedDateTime.year;

  switch (dateFormat) {
    case WorkspaceMemberDateFormatEnum.SYSTEM: {
      const detectedFormat = WorkspaceMemberDateFormatEnum[detectDateFormat()];

      return formatZonedDateTimeDatePart(zonedDateTime, detectedFormat);
    }
    case WorkspaceMemberDateFormatEnum.MONTH_FIRST:
      return `${MMM} ${d}, ${yyyy}`;
    case WorkspaceMemberDateFormatEnum.DAY_FIRST:
      return `${d} ${MMM}, ${yyyy}`;
    case WorkspaceMemberDateFormatEnum.YEAR_FIRST:
      return `${yyyy} ${MMM} ${d}`;
  }
};
