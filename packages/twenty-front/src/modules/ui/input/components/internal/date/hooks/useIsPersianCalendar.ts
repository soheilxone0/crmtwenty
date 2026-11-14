import { useLingui } from '@lingui/react';

import { isPersianLocale } from '@/localization/utils/persian-calendar/isPersianLocale';

// crmtwenty customization: true when dates must be shown / picked in the
// Solar Hijri (Jalali) calendar, i.e. when the interface language is Persian.
export const useIsPersianCalendar = (): boolean => {
  const { i18n } = useLingui();

  return isPersianLocale(i18n.locale);
};
