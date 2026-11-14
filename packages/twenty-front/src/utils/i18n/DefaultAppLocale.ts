import { APP_LOCALES } from 'twenty-shared/translations';

// crmtwenty customization: the interface is Persian (right-to-left) by
// default. A locale from the URL (?locale=en) or a previously chosen one
// (stored in localStorage / the user profile) still takes precedence.
export const DEFAULT_APP_LOCALE: keyof typeof APP_LOCALES =
  APP_LOCALES['fa-IR'];
