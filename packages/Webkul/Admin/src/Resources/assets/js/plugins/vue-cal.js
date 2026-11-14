
import VueCal from 'vue-cal';

import 'vue-cal/dist/vuecal.css';

import 'vue-cal/dist/i18n/ar.es.js';
import 'vue-cal/dist/i18n/en.es.js';
import 'vue-cal/dist/i18n/es.es.js';
import 'vue-cal/dist/i18n/fa.es.js';
import 'vue-cal/dist/i18n/tr.es.js';
import 'vue-cal/dist/i18n/pt-br.es.js';
import 'vue-cal/dist/i18n/vi.es.js';

import {
    JALALI_MONTHS,
    dateToJalali,
    isPersianLocale,
    toPersianDigits,
} from '../jalali/jalali';

/**
 * Saturday-first weekday names (Iranian week).
 */
const PERSIAN_WEEK = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

/**
 * crmtwenty: vue-cal with an Iranian week (Saturday → Friday) and Jalali
 * titles when the panel runs in Persian. Other locales get vue-cal untouched.
 *
 * The day numbers in the weekday headings are rendered through the
 * `weekday-heading` slot in the activities view, using `$admin.jalali`.
 */
const JalaliVueCal = {
    name: 'vue-cal',

    extends: VueCal,

    created() {
        if (! isPersianLocale()) {
            return;
        }

        const dateUtils = Object.create(this.utils.date);

        dateUtils.getPreviousFirstDayOfWeek = (date = null) => {
            const first = (date && new Date(date.valueOf())) || new Date();

            first.setDate(first.getDate() - ((first.getDay() + 1) % 7));

            return first;
        };

        this.utils.date = dateUtils;

        /**
         * vue-cal computed its first view in its own `created` hook with a
         * Monday week; rebuild it now that weeks start on Saturday. It is not
         * ready yet, so no `view-change` is emitted.
         */
        this.switchView(this.view.id, this.view.selectedDate);
    },

    computed: {
        weekDays() {
            const weekDays = VueCal.computed.weekDays.call(this);

            if (! isPersianLocale() || this.startWeekOnSunday) {
                return weekDays;
            }

            // vue-cal lists Monday first; the Iranian week starts on Saturday.
            return [...weekDays.slice(5), ...weekDays.slice(0, 5)].map((day, i) => ({
                ...day,
                label: PERSIAN_WEEK[i],
                short: PERSIAN_WEEK[i],
            }));
        },

        viewTitle() {
            if (! isPersianLocale() || ! ['week', 'day', 'month'].includes(this.view.id)) {
                return VueCal.computed.viewTitle.call(this);
            }

            const start = dateToJalali(this.view.startDate);

            if (this.view.id === 'day') {
                return toPersianDigits(`${PERSIAN_WEEK[(this.view.startDate.getDay() + 1) % 7]} ${start.jd} ${JALALI_MONTHS[start.jm - 1]} ${start.jy}`);
            }

            const end = dateToJalali(this.view.id === 'week' ? this.view.endDate : this.view.startDate);

            if (start.jy !== end.jy) {
                return toPersianDigits(`${JALALI_MONTHS[start.jm - 1]} ${start.jy} - ${JALALI_MONTHS[end.jm - 1]} ${end.jy}`);
            }

            if (start.jm !== end.jm) {
                return toPersianDigits(`${JALALI_MONTHS[start.jm - 1]} - ${JALALI_MONTHS[end.jm - 1]} ${start.jy}`);
            }

            return toPersianDigits(`${JALALI_MONTHS[start.jm - 1]} ${start.jy}`);
        },
    },
};

export default {
    install: (app, options) => app.component('v-vue-cal', JalaliVueCal),
};
