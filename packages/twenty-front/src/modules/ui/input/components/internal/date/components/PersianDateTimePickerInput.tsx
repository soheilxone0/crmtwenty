import { styled } from '@linaria/react';
import { useLingui } from '@lingui/react/macro';
import { useEffect, useState } from 'react';
import { useIMask } from 'react-imask';
import { Temporal } from 'temporal-polyfill';
import { isDefined } from 'twenty-shared/utils';
import { themeCssVariables } from 'twenty-ui/theme';

import {
  formatPlainDateToPersianInputString,
  normalizePersianDigits,
  parsePersianInputStringToPlainDate,
  PERSIAN_DATE_INPUT_MASK,
} from '@/localization/utils/persian-calendar/persianDateInputString';
import { TimeZoneAbbreviation } from '@/ui/input/components/internal/date/components/TimeZoneAbbreviation';
import { useUserTimezone } from '@/ui/input/components/internal/date/hooks/useUserTimezone';
import { type FormFieldInputVariant } from '@/ui/input/types/FormFieldInputVariant';
import { isDifferentZonedDateTime } from '~/utils/dates/isDifferentZonedDateTime';

// crmtwenty customization: Jalali date + 24h time input ("1405/08/23 14:30")
// used instead of the Gregorian masked input when the interface is in Persian.

const PERSIAN_DATE_TIME_INPUT_MASK = `${PERSIAN_DATE_INPUT_MASK} 00:00`;

const StyledInputContainer = styled.div<{
  $variant: FormFieldInputVariant;
}>`
  align-items: center;
  display: flex;
  height: ${({ $variant }) =>
    $variant === 'transparent'
      ? themeCssVariables.spacing[6]
      : themeCssVariables.spacing[8]};
  width: 100%;

  &:focus-within {
    box-shadow: inset 0 -1px 0 ${themeCssVariables.color.blue};
  }
`;

const StyledInput = styled.input<{
  $variant: FormFieldInputVariant;
}>`
  background: transparent;
  border: none;
  color: ${themeCssVariables.font.color.primary};
  direction: ltr;
  font-size: ${themeCssVariables.font.size.md};
  font-weight: ${({ $variant }) =>
    $variant === 'transparent' ? themeCssVariables.font.weight.regular : 500};
  outline: none;
  padding-inline: ${({ $variant }) =>
    $variant === 'transparent' ? '0' : themeCssVariables.spacing[2]};
  text-align: end;
  width: 140px;
`;

const pad = (value: number) => value.toString().padStart(2, '0');

const formatZonedDateTimeToPersianInputString = (
  date: Temporal.ZonedDateTime,
  timeZone: string,
): string => {
  const zonedDate = date.withTimeZone(timeZone);

  return `${formatPlainDateToPersianInputString(zonedDate.toPlainDate())} ${pad(
    zonedDate.hour,
  )}:${pad(zonedDate.minute)}`;
};

const parsePersianInputStringToZonedDateTime = (
  value: string,
  timeZone: string,
): Temporal.ZonedDateTime | null => {
  const [datePart, timePart] = value.split(' ');
  const plainDate = parsePersianInputStringToPlainDate(datePart ?? '');
  const timeMatch = (timePart ?? '').match(/^(\d{2}):(\d{2})$/);

  if (!isDefined(plainDate) || timeMatch === null) {
    return null;
  }

  const hour = Number(timeMatch[1]);
  const minute = Number(timeMatch[2]);

  if (hour > 23 || minute > 59) {
    return null;
  }

  return plainDate
    .toPlainDateTime({ hour, minute })
    .toZonedDateTime(timeZone);
};

type PersianDateTimePickerInputProps = {
  onChange?: (date: Temporal.ZonedDateTime | null) => void;
  date: Temporal.ZonedDateTime | null;
  onFocus?: () => void;
  readonly?: boolean;
  timeZone?: string;
  variant?: FormFieldInputVariant;
};

export const PersianDateTimePickerInput = ({
  date,
  onChange,
  onFocus,
  readonly,
  timeZone,
  variant = 'default',
}: PersianDateTimePickerInputProps) => {
  const { t } = useLingui();
  const { userTimezone } = useUserTimezone();
  const resolvedTimeZone = timeZone ?? userTimezone;
  const [internalDate, setInternalDate] = useState(date);

  const { ref, setValue } = useIMask<HTMLInputElement>(
    {
      mask: PERSIAN_DATE_TIME_INPUT_MASK,
      lazy: false,
      placeholderChar: '_',
      // Accept Persian / Arabic-Indic digits typed on a Persian keyboard.
      prepareChar: normalizePersianDigits,
    },
    {
      defaultValue: isDefined(internalDate)
        ? formatZonedDateTimeToPersianInputString(internalDate, resolvedTimeZone)
        : undefined,
      onComplete: (value) => {
        const zonedDateTime = parsePersianInputStringToZonedDateTime(
          value,
          resolvedTimeZone,
        );

        if (!isDefined(zonedDateTime)) {
          return;
        }

        setInternalDate(zonedDateTime);
        onChange?.(zonedDateTime);
      },
    },
  );

  useEffect(() => {
    if (isDifferentZonedDateTime(internalDate, date)) {
      setInternalDate(date);
      setValue(
        isDefined(date)
          ? formatZonedDateTimeToPersianInputString(date, resolvedTimeZone)
          : '',
      );
    }
  }, [date, internalDate, resolvedTimeZone, setValue]);

  const shouldDisplayReadOnly = readonly === true;

  return (
    <StyledInputContainer $variant={variant}>
      <StyledInput
        $variant={variant}
        disabled={shouldDisplayReadOnly}
        type="text"
        inputMode="numeric"
        aria-label={t`Date and time`}
        ref={ref}
        onFocus={!shouldDisplayReadOnly ? onFocus : undefined}
      />
      <TimeZoneAbbreviation
        instant={internalDate?.toInstant() ?? Temporal.Now.instant()}
        timeZone={resolvedTimeZone}
      />
    </StyledInputContainer>
  );
};
