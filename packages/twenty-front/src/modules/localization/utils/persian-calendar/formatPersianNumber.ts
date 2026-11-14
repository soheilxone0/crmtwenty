const PERSIAN_NUMBER_FORMAT = new Intl.NumberFormat('fa-IR', {
  useGrouping: false,
});

export const formatPersianNumber = (value: number): string =>
  PERSIAN_NUMBER_FORMAT.format(value);
