// crmtwenty customization: Persian (fa / fa-IR) users get the Solar Hijri
// (Jalali, شمسی) calendar everywhere dates are displayed or picked.
export const isPersianLocale = (locale?: string | null): boolean =>
  typeof locale === 'string' && locale.toLowerCase().startsWith('fa');
