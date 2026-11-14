import { styled } from '@linaria/react';
import { Temporal } from 'temporal-polyfill';
import { isDefined } from 'twenty-shared/utils';
import { type DatePickerProps } from 'twenty-ui/components/input';
import { themeCssVariables } from 'twenty-ui/theme';

import { formatPersianNumber } from '@/localization/utils/persian-calendar/formatPersianNumber';
import { PERSIAN_INTL_LOCALE } from '@/localization/utils/persian-calendar/PersianCalendar';
import {
  getPersianMonthFirstDayFromViewedMonth,
  getPersianYearMonthFromViewedMonth,
  setPersianViewedMonth,
  shiftPersianViewedMonth,
} from '@/localization/utils/persian-calendar/persianViewedMonth';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';

// crmtwenty customization: a Solar Hijri (Jalali) month grid used instead of
// react-datepicker (which only supports the Gregorian calendar) when the
// interface is in Persian. It speaks the same props as twenty-ui's DatePicker:
// values stay ISO plain dates ("2026-11-14") and the viewed month is the
// Gregorian "YYYY-MM" proxy described in persianViewedMonth.ts.

const DAYS_PER_WEEK = 7;
// 4 January 2026 is a Sunday (weekday index 0, same convention as weekStart).
const REFERENCE_SUNDAY_UTC = Date.UTC(2026, 0, 4);
const DAY_IN_MS = 24 * 60 * 60 * 1000;

const StyledCalendar = styled.div`
  box-sizing: border-box;
  font-family: ${themeCssVariables.font.family};
  inline-size: calc(7 * ${themeCssVariables.spacing[10]});
  max-inline-size: 100%;
`;

const StyledGrid = styled.div<{ $readOnly: boolean }>`
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  justify-items: center;
  opacity: ${({ $readOnly }) => ($readOnly ? 0.5 : 1)};
  padding: ${themeCssVariables.spacing[1]} ${themeCssVariables.spacing[1]}
    ${themeCssVariables.spacing[2]};
  pointer-events: ${({ $readOnly }) => ($readOnly ? 'none' : 'auto')};
`;

const StyledWeekday = styled.div`
  color: ${themeCssVariables.font.color.secondary};
  font-size: ${themeCssVariables.font.size.md};
  height: ${themeCssVariables.spacing[10]};
  line-height: ${themeCssVariables.spacing[10]};
  text-align: center;
  width: calc(${themeCssVariables.spacing[8]} + ${themeCssVariables.spacing[0.5]});
`;

const StyledDay = styled.button<{
  $isSelected: boolean;
  $isOutsideMonth: boolean;
  $isToday: boolean;
}>`
  background: ${({ $isSelected }) =>
    $isSelected ? themeCssVariables.color.blue : 'transparent'};
  border: none;
  border-radius: ${themeCssVariables.border.radius.sm};
  color: ${({ $isSelected, $isOutsideMonth }) =>
    $isSelected
      ? themeCssVariables.background.primary
      : $isOutsideMonth
        ? themeCssVariables.font.color.light
        : themeCssVariables.font.color.primary};
  cursor: pointer;
  font-family: inherit;
  font-size: ${themeCssVariables.font.size.md};
  font-weight: ${({ $isToday }) =>
    $isToday
      ? themeCssVariables.font.weight.semiBold
      : themeCssVariables.font.weight.regular};
  height: calc(${themeCssVariables.spacing[8]} + ${themeCssVariables.spacing[0.5]});
  margin: ${themeCssVariables.spacing[0.5]};
  padding: 0;
  width: calc(${themeCssVariables.spacing[8]} + ${themeCssVariables.spacing[0.5]});

  &:hover:not(:disabled) {
    background: ${({ $isSelected }) =>
      $isSelected
        ? themeCssVariables.color.blue
        : themeCssVariables.background.transparent.light};
  }

  &:disabled {
    color: ${themeCssVariables.font.color.light};
    cursor: default;
  }
`;

type PersianDatePickerGridProps = Pick<
  DatePickerProps,
  | 'value'
  | 'onValueChange'
  | 'onValueSelect'
  | 'viewedMonth'
  | 'onViewedMonthChange'
  | 'highlightedRange'
  | 'readOnly'
  | 'isGridHidden'
  | 'weekStart'
  | 'renderHeader'
  | 'isDateUnavailable'
  | 'disabled'
> & { ariaLabel?: string };

export const PersianDatePickerGrid = ({
  value,
  onValueChange,
  onValueSelect,
  viewedMonth,
  onViewedMonthChange,
  highlightedRange,
  readOnly = false,
  isGridHidden = false,
  weekStart,
  renderHeader,
  isDateUnavailable,
  disabled = false,
  ariaLabel,
}: PersianDatePickerGridProps) => {
  const { daysInMonth } = getPersianYearMonthFromViewedMonth(viewedMonth);
  const firstDayOfMonth = getPersianMonthFirstDayFromViewedMonth(viewedMonth);
  const leadingDays =
    ((firstDayOfMonth.dayOfWeek % DAYS_PER_WEEK) - weekStart + DAYS_PER_WEEK) %
    DAYS_PER_WEEK;
  const cellCount =
    Math.ceil((leadingDays + daysInMonth) / DAYS_PER_WEEK) * DAYS_PER_WEEK;
  const firstCell = firstDayOfMonth.subtract({ days: leadingDays });
  const today = Temporal.Now.plainDateISO().toString();

  const weekdayFormatter = new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
    weekday: 'narrow',
    timeZone: 'UTC',
  });
  const weekdayLongFormatter = new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
    weekday: 'long',
    timeZone: 'UTC',
  });
  const weekdays = Array.from({ length: DAYS_PER_WEEK }, (_, index) => {
    const date = new Date(
      REFERENCE_SUNDAY_UTC + ((weekStart + index) % DAYS_PER_WEEK) * DAY_IN_MS,
    );

    return {
      label: weekdayFormatter.format(date),
      title: weekdayLongFormatter.format(date),
    };
  });

  const navigationProps = {
    viewedMonth,
    onPreviousMonth: () =>
      onViewedMonthChange(shiftPersianViewedMonth(viewedMonth, -1)),
    onNextMonth: () =>
      onViewedMonthChange(shiftPersianViewedMonth(viewedMonth, 1)),
    onMonthChange: (month: number) =>
      onViewedMonthChange(setPersianViewedMonth(viewedMonth, { month })),
    onYearChange: (year: number) =>
      onViewedMonthChange(setPersianViewedMonth(viewedMonth, { year })),
    isPreviousMonthDisabled: disabled,
    isNextMonthDisabled: disabled,
  };

  const handleDayClick = (isoDate: string) => {
    if (readOnly || disabled) {
      return;
    }

    onValueChange(isoDate);
    onValueSelect?.(isoDate);
  };

  return (
    <StyledCalendar role="group" aria-label={ariaLabel}>
      {renderHeader?.(navigationProps)}
      {!isGridHidden && (
        <StyledGrid $readOnly={readOnly}>
          {weekdays.map((weekday) => (
            <StyledWeekday key={weekday.title} title={weekday.title}>
              {weekday.label}
            </StyledWeekday>
          ))}
          {Array.from({ length: cellCount }, (_, index) => {
            const day = firstCell.add({ days: index });
            const isoDate = day.toString();
            const persianDay = toPersianPlainDate(day);
            const isOutsideMonth = index < leadingDays || index >= leadingDays + daysInMonth;
            const isInHighlightedRange =
              isDefined(highlightedRange) &&
              isoDate >= highlightedRange.start &&
              isoDate <= highlightedRange.end;

            return (
              <StyledDay
                key={isoDate}
                type="button"
                $isSelected={isoDate === value || isInHighlightedRange}
                $isOutsideMonth={isOutsideMonth}
                $isToday={isoDate === today}
                disabled={disabled || isDateUnavailable?.(isoDate) === true}
                aria-pressed={isoDate === value}
                aria-label={new Intl.DateTimeFormat(PERSIAN_INTL_LOCALE, {
                  dateStyle: 'full',
                  timeZone: 'UTC',
                }).format(new Date(Date.UTC(day.year, day.month - 1, day.day)))}
                onClick={() => handleDayClick(isoDate)}
              >
                {formatPersianNumber(persianDay.day)}
              </StyledDay>
            );
          })}
        </StyledGrid>
      )}
    </StyledCalendar>
  );
};
