import { DatePickerCalendar } from '@/ui/input/components/internal/date/components/DatePickerCalendar';
import { DatePickerLoadingFallback } from '@/ui/input/components/internal/date/components/DatePickerLoadingFallback';
import { DatePickerHeader } from '@/ui/input/components/internal/date/components/DatePickerHeader';
import { StyledDatePickerContainer } from '@/ui/input/components/internal/date/components/StyledDatePickerContainer';
import { styled } from '@linaria/react';
import { Temporal } from 'temporal-polyfill';
import { type Nullable } from 'twenty-shared/types';
import { isDefined } from 'twenty-shared/utils';
import { themeCssVariables } from 'twenty-ui/theme';
import { toIsoPlainDate } from '@/localization/utils/persian-calendar/toIsoPlainDate';
import { toPersianPlainDate } from '@/localization/utils/persian-calendar/toPersianPlainDate';
import { useIsPersianCalendar } from '@/ui/input/components/internal/date/hooks/useIsPersianCalendar';

const StyledSpacer = styled.div`
  height: ${themeCssVariables.spacing[2]};
  width: auto;
`;

type DatePickerWithoutCalendarProps = {
  instanceId: string;
  date: Nullable<string>;
  onClose?: (date: string | null) => void;
  onChange?: (date: string | null) => void;
  onEnter?: (date: string | null) => void;
  onEscape?: (date: string | null) => void;
  keyboardEventsDisabled?: boolean;
};

export const DatePickerWithoutCalendar = ({
  instanceId,
  date,
  onChange,
  onClose,
}: DatePickerWithoutCalendarProps) => {
  const isPersianCalendar = useIsPersianCalendar();
  const plainDate = isDefined(date) ? Temporal.PlainDate.from(date) : null;

  // In Persian mode month/year values are Jalali, so the arithmetic is done in
  // the Persian calendar and converted back to an ISO date.
  const updatePlainDate = (
    update: (date: Temporal.PlainDate) => Temporal.PlainDate,
  ) => {
    if (!isDefined(plainDate)) {
      onChange?.(null);
      return;
    }

    const updatedDate = isPersianCalendar
      ? toIsoPlainDate(update(toPersianPlainDate(plainDate)))
      : update(plainDate);

    onChange?.(updatedDate.toString());
  };

  const handleChangeMonth = (month: number) => {
    updatePlainDate((date) => date.with({ month }));
  };
  const handleChangeYear = (year: number) => {
    updatePlainDate((date) => date.with({ year }));
  };
  const handleAddMonth = () => {
    updatePlainDate((date) => date.add({ months: 1 }));
  };
  const handleSubtractMonth = () => {
    updatePlainDate((date) => date.subtract({ months: 1 }));
  };

  return (
    <StyledDatePickerContainer>
      <DatePickerCalendar
        loadingFallback={<DatePickerLoadingFallback />}
        value={plainDate?.toString() ?? null}
        isGridHidden
        onChange={onChange}
        onSelect={onClose}
        renderHeader={(header) => (
          <>
            <DatePickerHeader
              instanceId={instanceId}
              date={plainDate?.toString() ?? null}
              onChange={onChange}
              onChangeMonth={handleChangeMonth}
              onChangeYear={handleChangeYear}
              onAddMonth={handleAddMonth}
              onSubtractMonth={handleSubtractMonth}
              prevMonthButtonDisabled={header.isPreviousMonthDisabled}
              nextMonthButtonDisabled={header.isNextMonthDisabled}
              hideInput
            />
            <StyledSpacer />
          </>
        )}
      />
    </StyledDatePickerContainer>
  );
};
