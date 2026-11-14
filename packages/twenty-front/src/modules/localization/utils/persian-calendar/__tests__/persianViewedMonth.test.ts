import {
  getPersianMonthFirstDayFromViewedMonth,
  getPersianViewedMonthFromPlainDate,
  getPersianYearMonthFromViewedMonth,
  setPersianViewedMonth,
  shiftPersianViewedMonth,
} from '@/localization/utils/persian-calendar/persianViewedMonth';

describe('persianViewedMonth', () => {
  it('maps a date to the viewed month of its Jalali month', () => {
    // 2026-11-14 = 1405/08/23 (Aban), Aban 15 = 2026-11-06
    expect(getPersianViewedMonthFromPlainDate('2026-11-14')).toBe('2026-11');
    // 2026-10-23 = 1405/08/01 (first day of Aban)
    expect(getPersianViewedMonthFromPlainDate('2026-10-23')).toBe('2026-11');
    // 2026-10-22 = 1405/07/30 (last day of Mehr), Mehr 15 = 2026-10-07
    expect(getPersianViewedMonthFromPlainDate('2026-10-22')).toBe('2026-10');
  });

  it('round-trips every Jalali month over several years', () => {
    let viewedMonth = getPersianViewedMonthFromPlainDate('2020-03-20');
    let previous = getPersianYearMonthFromViewedMonth(viewedMonth);

    for (let index = 0; index < 120; index++) {
      viewedMonth = shiftPersianViewedMonth(viewedMonth, 1);
      const current = getPersianYearMonthFromViewedMonth(viewedMonth);
      const expectedMonth = previous.month === 12 ? 1 : previous.month + 1;
      const expectedYear =
        previous.month === 12 ? previous.year + 1 : previous.year;

      expect(current.month).toBe(expectedMonth);
      expect(current.year).toBe(expectedYear);

      const firstDay = getPersianMonthFirstDayFromViewedMonth(viewedMonth);

      expect(getPersianViewedMonthFromPlainDate(firstDay)).toBe(viewedMonth);
      previous = current;
    }
  });

  it('returns the first day of the Jalali month', () => {
    expect(getPersianMonthFirstDayFromViewedMonth('2026-11').toString()).toBe(
      '2026-10-23',
    );
    // Farvardin 1405 starts on Nowruz, 2026-03-21
    expect(getPersianMonthFirstDayFromViewedMonth('2026-04').toString()).toBe(
      '2026-03-21',
    );
  });

  it('sets the Jalali month and year', () => {
    const viewedMonth = setPersianViewedMonth('2026-11', {
      year: 1403,
      month: 12,
    });

    expect(getPersianYearMonthFromViewedMonth(viewedMonth).year).toBe(1403);
    expect(getPersianYearMonthFromViewedMonth(viewedMonth).month).toBe(12);
    // 1403 is a leap year: Esfand has 30 days
    expect(getPersianYearMonthFromViewedMonth(viewedMonth).daysInMonth).toBe(
      30,
    );
  });
});
