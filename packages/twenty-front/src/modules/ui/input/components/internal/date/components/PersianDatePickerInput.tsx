import { styled } from '@linaria/react';
import { useLingui } from '@lingui/react/macro';
import { useEffect, useState } from 'react';
import { useIMask } from 'react-imask';
import { isDefined } from 'twenty-shared/utils';
import { themeCssVariables } from 'twenty-ui/theme';

import {
  formatPlainDateToPersianInputString,
  normalizePersianDigits,
  parsePersianInputStringToPlainDate,
  PERSIAN_DATE_INPUT_MASK,
} from '@/localization/utils/persian-calendar/persianDateInputString';
import { type FormFieldInputVariant } from '@/ui/input/types/FormFieldInputVariant';

// crmtwenty customization: Jalali text input ("1405/08/23") used instead of
// the Gregorian masked input when the interface is in Persian.

const StyledInputContainer = styled.div<{
  $variant: FormFieldInputVariant;
}>`
  align-items: center;
  border-bottom: ${({ $variant }) =>
    $variant === 'transparent'
      ? 'none'
      : `1px solid ${themeCssVariables.border.color.light}`};
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
  padding: ${({ $variant }) => ($variant === 'transparent' ? '0' : '4px 8px')};
  text-align: end;
  width: 100%;
`;

type PersianDatePickerInputProps = {
  onChange?: (date: string | null) => void;
  date: string | null;
  readonly?: boolean;
  variant?: FormFieldInputVariant;
};

export const PersianDatePickerInput = ({
  date,
  onChange,
  readonly = false,
  variant = 'default',
}: PersianDatePickerInputProps) => {
  const { t } = useLingui();
  const [internalDate, setInternalDate] = useState(date);

  const { ref, setValue, value } = useIMask<HTMLInputElement>(
    {
      mask: PERSIAN_DATE_INPUT_MASK,
      lazy: false,
      placeholderChar: '_',
      // Accept Persian / Arabic-Indic digits typed on a Persian keyboard.
      prepareChar: normalizePersianDigits,
    },
    {
      defaultValue: isDefined(internalDate)
        ? formatPlainDateToPersianInputString(internalDate)
        : undefined,
      onComplete: (newValue) => {
        const parsedDate = parsePersianInputStringToPlainDate(newValue);

        if (isDefined(parsedDate)) {
          onChange?.(parsedDate.toString());
        }
      },
    },
  );

  useEffect(() => {
    if (internalDate !== date) {
      setInternalDate(date);
      setValue(isDefined(date) ? formatPlainDateToPersianInputString(date) : '');
    }
  }, [date, internalDate, setValue]);

  return (
    <StyledInputContainer $variant={variant}>
      <StyledInput
        $variant={variant}
        type="text"
        inputMode="numeric"
        aria-label={t`Date`}
        disabled={readonly}
        ref={ref}
        value={value}
        onChange={() => {}} // Prevent React warning
      />
    </StyledInputContainer>
  );
};
