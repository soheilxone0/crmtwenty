import { type Temporal } from 'temporal-polyfill';
import { toIsoPlainDate } from '@/localization/utils/persian-calendar/toIsoPlainDate';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';
import { ViewCalendarLayout } from '~/generated-metadata/graphql';

export const getRecordCalendarDaysRange = ({
  selectedDate,
  calendarLayout,
  weekStartsOnDayIndex,
  isPersianCalendar = false,
}: {
  selectedDate: Temporal.PlainDate;
  calendarLayout: ViewCalendarLayout;
  weekStartsOnDayIndex: number;
  // crmtwenty customization: month layout follows Jalali months.
  isPersianCalendar?: boolean;
}) => {
  const isDayLayout = calendarLayout === ViewCalendarLayout.DAY;
  const isMonthLayout = calendarLayout === ViewCalendarLayout.MONTH;
  const monthDate = isPersianCalendar
    ? toPersianPlainDate(selectedDate)
    : selectedDate;
  const periodStart = isMonthLayout
    ? toIsoPlainDate(monthDate.with({ day: 1 }))
    : selectedDate;
  const daysSinceStartOfWeek =
    ((periodStart.dayOfWeek % 7) - weekStartsOnDayIndex + 7) % 7;
  const firstDay = isDayLayout
    ? selectedDate
    : periodStart.subtract({ days: daysSinceStartOfWeek });
  const daysPerRow = isDayLayout ? 1 : 7;
  const rowCount = isMonthLayout
    ? Math.ceil((daysSinceStartOfWeek + monthDate.daysInMonth) / 7)
    : 1;
  const days = Array.from({ length: rowCount }, (_, rowIndex) =>
    Array.from({ length: daysPerRow }, (_, dayIndex) =>
      firstDay.add({ days: rowIndex * daysPerRow + dayIndex }),
    ),
  );

  return {
    firstDay,
    lastDay: firstDay.add({ days: rowCount * daysPerRow - 1 }),
    days,
  };
};
