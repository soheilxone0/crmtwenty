import { Temporal } from 'temporal-polyfill';

import { PERSIAN_CALENDAR } from '@/localization/utils/persian-calendar/PersianCalendar';
import { toIsoPlainDate } from '@/localization/utils/persian-calendar/toIsoPlainDate';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';

// Text representation used by the Persian date inputs: "1405/08/23" (Latin
// digits so it can be typed on any keyboard; Persian digits are accepted too).
export const PERSIAN_DATE_INPUT_MASK = '0000/00/00';

const pad = (value: number, length = 2) => value.toString().padStart(length, '0');

export const formatPlainDateToPersianInputString = (
  date: Temporal.PlainDate | string,
): string => {
  const persianDate = toPersianPlainDate(date);

  return `${pad(persianDate.year, 4)}/${pad(persianDate.month)}/${pad(persianDate.day)}`;
};

export const normalizePersianDigits = (value: string): string =>
  value
    .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x06f0))
    .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660));

// Returns the ISO plain date or null when the string is not a valid Jalali date.
export const parsePersianInputStringToPlainDate = (
  value: string,
): Temporal.PlainDate | null => {
  const match = normalizePersianDigits(value).match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);

  if (match === null) {
    return null;
  }

  const [, year, month, day] = match;

  try {
    return toIsoPlainDate(
      Temporal.PlainDate.from(
        {
          calendar: PERSIAN_CALENDAR,
          year: Number(year),
          month: Number(month),
          day: Number(day),
        },
        { overflow: 'reject' },
      ),
    );
  } catch {
    return null;
  }
};
