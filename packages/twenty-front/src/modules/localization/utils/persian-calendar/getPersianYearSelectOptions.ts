import { Temporal } from 'temporal-polyfill';

import { DATE_PICKER_YEAR_SELECT_OPTIONS_CONFIG } from '@/ui/input/components/internal/date/constants/DatePickerYearSelectOptionsConfig';
import { formatPersianNumber } from '@/localization/utils/persian-calendar/formatPersianNumber';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';

export const getPersianYearSelectOptions = (): {
  label: string;
  value: number;
}[] => {
  const currentPersianYear = toPersianPlainDate(Temporal.Now.plainDateISO()).year;

  return Array.from(
    { length: DATE_PICKER_YEAR_SELECT_OPTIONS_CONFIG.optionCount },
    (_, index) =>
      currentPersianYear +
      DATE_PICKER_YEAR_SELECT_OPTIONS_CONFIG.yearsAfterCurrentYear -
      index,
  ).map((year) => ({ label: formatPersianNumber(year), value: year }));
};
