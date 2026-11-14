import { currentWorkspaceMemberState } from '@/auth/states/currentWorkspaceMemberState';
import { getDatePickerWeekStartOrThrow } from '@/ui/input/components/internal/date/utils/getDatePickerWeekStartOrThrow';
import { useAtomStateValue } from '@/ui/utilities/state/jotai/hooks/useAtomStateValue';
import { useState } from 'react';
import { Temporal } from 'temporal-polyfill';
import { SOURCE_LOCALE } from 'twenty-shared/translations';
import { isDefined } from 'twenty-shared/utils';
import { DatePicker, type DatePickerProps } from 'twenty-ui/components/input';
import { useLingui } from '@lingui/react/macro';
import { getPersianViewedMonthFromPlainDate } from '@/localization/utils/persian-calendar/persianViewedMonth';
import { PersianDatePickerGrid } from '@/ui/input/components/internal/date/components/PersianDatePickerGrid';
import { useIsPersianCalendar } from '@/ui/input/components/internal/date/hooks/useIsPersianCalendar';

type DatePickerCalendarProps = {
  value: string | null;
  onChange?: (date: string) => void;
  onSelect?: (date: string) => void;
  highlightedRange?: { start: string; end: string };
  readOnly?: boolean;
  isGridHidden?: boolean;
  today?: string;
  weekStart?: DatePickerProps['weekStart'];
  onViewedMonthChange?: (month: string) => void;
  renderHeader?: DatePickerProps['renderHeader'];
  loadingFallback?: DatePickerProps['loadingFallback'];
};

export const DatePickerCalendar = ({
  value,
  onChange,
  onSelect,
  highlightedRange,
  readOnly,
  isGridHidden,
  today,
  weekStart,
  onViewedMonthChange,
  renderHeader,
  loadingFallback,
}: DatePickerCalendarProps) => {
  const { t } = useLingui();
  const isPersianCalendar = useIsPersianCalendar();
  const getViewedMonth = (date: string) =>
    isPersianCalendar
      ? getPersianViewedMonthFromPlainDate(date)
      : date.slice(0, 7);
  const currentWorkspaceMember = useAtomStateValue(currentWorkspaceMemberState);
  const currentToday = today ?? Temporal.Now.plainDateISO().toString();
  const referenceDate = highlightedRange?.start ?? value ?? currentToday;
  const referenceKey = isDefined(highlightedRange)
    ? `${highlightedRange.start}-${highlightedRange.end}`
    : referenceDate;
  const calendarReferenceKey = `${isPersianCalendar ? 'fa' : 'iso'}:${referenceKey}`;
  const [calendarState, setCalendarState] = useState({
    referenceKey: calendarReferenceKey,
    viewedMonth: getViewedMonth(referenceDate),
  });
  if (calendarState.referenceKey !== calendarReferenceKey) {
    setCalendarState({
      referenceKey: calendarReferenceKey,
      viewedMonth: getViewedMonth(referenceDate),
    });
  }

  const resolvedWeekStart =
    weekStart ??
    getDatePickerWeekStartOrThrow(currentWorkspaceMember?.calendarStartDay);

  const handleViewedMonthChange = (month: string) => {
    setCalendarState({ referenceKey: calendarReferenceKey, viewedMonth: month });
    onViewedMonthChange?.(month);
  };

  if (isPersianCalendar) {
    return (
      <PersianDatePickerGrid
        ariaLabel={t`Choose a date`}
        value={value}
        onValueChange={(date) => onChange?.(date)}
        onValueSelect={onSelect}
        viewedMonth={calendarState.viewedMonth}
        onViewedMonthChange={handleViewedMonthChange}
        weekStart={resolvedWeekStart}
        highlightedRange={highlightedRange}
        readOnly={readOnly}
        isGridHidden={isGridHidden}
        renderHeader={renderHeader}
      />
    );
  }

  return (
    <DatePicker
      aria-label={t`Choose a date`}
      value={value}
      onValueChange={(date) => onChange?.(date)}
      onValueSelect={onSelect}
      viewedMonth={calendarState.viewedMonth}
      onViewedMonthChange={handleViewedMonthChange}
      weekStart={resolvedWeekStart}
      locale={SOURCE_LOCALE}
      previousMonthLabel={t`Previous`}
      nextMonthLabel={t`Next`}
      monthLabel={t`Month`}
      yearLabel={t`Year`}
      highlightedRange={highlightedRange}
      readOnly={readOnly}
      isGridHidden={isGridHidden}
      isKeyboardNavigationDisabled
      renderHeader={renderHeader}
      loadingFallback={loadingFallback}
    />
  );
};
