import { fromStorage, fromUrl } from '@lingui/detect-locale';
import { APP_LOCALES } from 'twenty-shared/translations';
import { isDefined, isValidLocale, normalizeLocale } from 'twenty-shared/utils';
import { DEFAULT_APP_LOCALE } from '~/utils/i18n/DefaultAppLocale';
import { dynamicActivate } from '~/utils/i18n/dynamicActivate';

export const initialI18nActivate = () => {
  const urlLocale = fromUrl('locale');
  const storageLocale = fromStorage('locale');

  // The browser language is intentionally ignored: most Persian speakers run
  // an English browser, and this build ships with Persian as the default.
  let locale: keyof typeof APP_LOCALES = DEFAULT_APP_LOCALE;

  const normalizedUrlLocale = isDefined(urlLocale)
    ? normalizeLocale(urlLocale)
    : null;
  const normalizedStorageLocale = isDefined(storageLocale)
    ? normalizeLocale(storageLocale)
    : null;

  if (isDefined(normalizedUrlLocale) && isValidLocale(normalizedUrlLocale)) {
    locale = normalizedUrlLocale;
    try {
      localStorage.setItem('locale', normalizedUrlLocale);
    } catch (error) {
      // oxlint-disable-next-line no-console
      console.log('Failed to save locale to localStorage:', error);
    }
  } else if (
    isDefined(normalizedStorageLocale) &&
    isValidLocale(normalizedStorageLocale)
  ) {
    locale = normalizedStorageLocale;
  }

  dynamicActivate(locale);
};
