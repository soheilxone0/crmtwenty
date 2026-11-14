/**
 * crmtwenty: a Jalali (شمسی) date / date-time / range picker that is a drop-in
 * replacement for the parts of the Flatpickr API used by the admin panel.
 *
 * It is installed as `window.Flatpickr` when the panel runs in Persian, so the
 * existing `v-date-picker`, `v-datetime-picker` and `v-date-range-picker`
 * components keep working unchanged:
 *
 *   - the user sees and types Jalali dates (۱۴۰۵/۰۸/۲۳ — Persian or Latin digits),
 *   - the bound <input> and every `onChange` callback still receive Gregorian
 *     values in `dateFormat` ("Y-m-d" / "Y-m-d H:i:S"), so validation rules,
 *     the API and the database are untouched.
 *
 * Supported options: mode ("single" | "range"), inline, showMonths, appendTo,
 * enableTime, dateFormat, defaultDate, minDate, maxDate, disable, allowInput,
 * defaultHour, defaultMinute, onChange, onOpen, onClose, onReady.
 */
import {
    JALALI_MONTHS,
    JALALI_WEEKDAYS_NARROW,
    dateToJalali,
    jalaliToDate,
    jalaliMonthLength,
    formatJalaliInput,
    parseJalaliInput,
    parseServerDate,
    toPersianDigits,
} from "./jalali";

const pad = (value) => String(value).padStart(2, "0");

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());

const sameDay = (a, b) =>
    !! a && !! b
    && a.getFullYear() === b.getFullYear()
    && a.getMonth() === b.getMonth()
    && a.getDate() === b.getDate();

const asArray = (value) => (Array.isArray(value) ? value : value === undefined || value === null ? [] : [value]);

const hooks = (value) => asArray(value).filter((hook) => typeof hook === "function");

/**
 * Flatpickr-style token formatting for the Gregorian value handed to the server.
 */
const formatGregorian = (date, format) =>
    format.replace(/\\?[YmdHiSyjnGhKsU]/g, (token) => {
        if (token.startsWith("\\")) {
            return token.slice(1);
        }

        switch (token) {
            case "Y": return String(date.getFullYear());
            case "y": return String(date.getFullYear()).slice(-2);
            case "m": return pad(date.getMonth() + 1);
            case "n": return String(date.getMonth() + 1);
            case "d": return pad(date.getDate());
            case "j": return String(date.getDate());
            case "H": return pad(date.getHours());
            case "G": return String(date.getHours());
            case "h": return pad(date.getHours() % 12 || 12);
            case "i": return pad(date.getMinutes());
            case "S": return pad(date.getSeconds());
            case "s": return String(date.getSeconds());
            case "K": return date.getHours() < 12 ? "AM" : "PM";
            case "U": return String(Math.floor(date.getTime() / 1000));
            default: return token;
        }
    });

const toDate = (value) => {
    if (value === "today") {
        return startOfDay(new Date());
    }

    return parseServerDate(value);
};

const NATIVE_VALUE = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value");

export default class JalaliPicker {
    constructor(element, options = {}) {
        if (typeof element === "string") {
            element = document.querySelector(element);
        }

        this.element = element;
        this.input = element;
        this.isOpen = false;
        this.selectedDates = [];
        this.hoverDate = null;
        this.isSyncing = false;

        this.config = {
            mode: "single",
            inline: false,
            showMonths: 1,
            appendTo: null,
            enableTime: false,
            dateFormat: options.enableTime ? "Y-m-d H:i:S" : "Y-m-d",
            defaultDate: null,
            minDate: null,
            maxDate: null,
            disable: [],
            allowInput: true,
            defaultHour: 12,
            defaultMinute: 0,
            ...options,
        };

        this.config.minDate = this.config.minDate ? toDate(this.config.minDate) : null;
        this.config.maxDate = this.config.maxDate ? toDate(this.config.maxDate) : null;
        this.config.showMonths = Math.max(1, parseInt(this.config.showMonths, 10) || 1);

        this.onDocumentMouseDown = this.onDocumentMouseDown.bind(this);
        this.onKeyDown = this.onKeyDown.bind(this);
        this.reposition = this.reposition.bind(this);

        this.setupInput();
        this.setupCalendar();

        const initial = asArray(this.config.defaultDate).filter((value) => value !== "" && value !== null);

        if (initial.length) {
            this.setDate(initial, false);
        } else if (this.input.value) {
            this.setDate(this.input.value, false);
        } else {
            this.jumpToDate(new Date());
        }

        this.element._flatpickr = this;

        hooks(this.config.onReady).forEach((hook) => hook(this.selectedDates, this.input.value, this));
    }

    /* ------------------------------------------------------------------ */
    /* Input wiring                                                        */
    /* ------------------------------------------------------------------ */

    setupInput() {
        const input = this.input;
        const picker = this;

        /**
         * Vue (v-model / vee-validate) writes Gregorian values straight into the
         * input's `value` property. Intercepting the setter keeps the Jalali
         * display in step without any change to the Blade components.
         */
        Object.defineProperty(input, "value", {
            configurable: true,
            get() {
                return NATIVE_VALUE.get.call(this);
            },
            set(value) {
                NATIVE_VALUE.set.call(this, value);

                if (! picker.isSyncing) {
                    picker.syncFromInput();
                }
            },
        });

        if (this.config.inline) {
            return;
        }

        const altInput = document.createElement("input");

        altInput.type = "text";
        altInput.className = `${input.className} jdp-alt-input`;
        altInput.placeholder = input.getAttribute("placeholder") || (this.config.enableTime ? "۱۴۰۵/۰۱/۰۱ ۱۲:۰۰" : "۱۴۰۵/۰۱/۰۱");
        altInput.autocomplete = "off";
        altInput.dir = "ltr";
        altInput.disabled = input.disabled;
        altInput.readOnly = ! this.config.allowInput || input.readOnly;

        if (input.getAttribute("aria-label")) {
            altInput.setAttribute("aria-label", input.getAttribute("aria-label"));
        }

        input.type = "hidden";
        input.classList.add("jdp-input");
        input.insertAdjacentElement("afterend", altInput);

        altInput.addEventListener("focus", () => this.open());
        altInput.addEventListener("click", () => this.open());
        altInput.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                this.commitTypedValue();
                this.close();
            } else if (event.key === "Tab") {
                this.commitTypedValue();
                this.close();
            }
        });
        altInput.addEventListener("change", () => this.commitTypedValue());

        /**
         * Validation styling (red border) and disabled state are applied to the
         * original input by Vue; mirror them onto the visible one.
         */
        this.observer = new MutationObserver(() => {
            altInput.className = `${input.className.replace(/\bjdp-input\b/, "").trim()} jdp-alt-input`;
            altInput.disabled = input.disabled;
        });

        this.observer.observe(input, { attributes: true, attributeFilter: ["class", "disabled"] });

        this.altInput = altInput;
    }

    syncFromInput() {
        const value = NATIVE_VALUE.get.call(this.input);

        const dates = this.config.mode === "range"
            ? value.split(/\s+(?:to|تا)\s+/).map(parseServerDate).filter(Boolean)
            : [parseServerDate(value)].filter(Boolean);

        this.selectedDates = dates;

        if (dates.length) {
            this.jumpToDate(dates[0]);
        } else {
            this.render();
        }

        this.updateAltInput();
    }

    commitTypedValue() {
        if (! this.altInput || this.altInput.readOnly) {
            return;
        }

        const typed = this.altInput.value.trim();

        if (! typed) {
            if (this.selectedDates.length) {
                this.clear();
            }

            return;
        }

        const date = parseJalaliInput(typed);

        if (! date || this.isDisabled(date)) {
            this.updateAltInput();

            return;
        }

        if (this.selectedDates[0] && date.getTime() === this.selectedDates[0].getTime()) {
            this.updateAltInput();

            return;
        }

        if (this.config.enableTime && ! /\d{1,2}:\d{1,2}/.test(typed) && this.selectedDates[0]) {
            date.setHours(this.selectedDates[0].getHours(), this.selectedDates[0].getMinutes(), 0);
        }

        this.selectedDates = [date];
        this.jumpToDate(date);
        this.updateValue(true);
    }

    /* ------------------------------------------------------------------ */
    /* Public Flatpickr-compatible API                                     */
    /* ------------------------------------------------------------------ */

    setDate(dates, triggerChange = false) {
        const parsed = asArray(dates)
            .flatMap((value) => (typeof value === "string" && this.config.mode === "range"
                ? value.split(/\s+(?:to|تا)\s+/)
                : [value]))
            .map(toDate)
            .filter(Boolean);

        this.selectedDates = this.config.mode === "range"
            ? parsed.slice(0, 2).sort((a, b) => a - b)
            : parsed.slice(0, 1);

        this.jumpToDate(this.selectedDates[0] || new Date());
        this.updateValue(triggerChange);
    }

    clear(triggerChange = true) {
        this.selectedDates = [];
        this.hoverDate = null;
        this.render();
        this.updateValue(triggerChange);
    }

    jumpToDate(date) {
        const { jy, jm } = dateToJalali(toDate(date) || new Date());

        this.viewYear = jy;
        this.viewMonth = jm;
        this.render();
    }

    changeMonth(offset) {
        let month = this.viewMonth + offset;
        let year = this.viewYear;

        while (month < 1) {
            month += 12;
            year -= 1;
        }

        while (month > 12) {
            month -= 12;
            year += 1;
        }

        this.viewYear = year;
        this.viewMonth = month;
        this.render();
    }

    set(option, value) {
        if (option === "minDate" || option === "maxDate") {
            value = value ? toDate(value) : null;
        }

        this.config[option] = value;
        this.render();
    }

    redraw() {
        this.render();
    }

    formatDate(date, format) {
        return formatGregorian(date, format);
    }

    open() {
        if (this.config.inline || this.isOpen || this.altInput?.disabled) {
            return;
        }

        this.isOpen = true;

        if (this.selectedDates[0]) {
            this.jumpToDate(this.selectedDates[0]);
        }

        this.calendarContainer.classList.add("jdp-open");
        this.reposition();

        document.addEventListener("mousedown", this.onDocumentMouseDown, true);
        document.addEventListener("keydown", this.onKeyDown, true);
        window.addEventListener("resize", this.reposition);
        window.addEventListener("scroll", this.reposition, true);

        hooks(this.config.onOpen).forEach((hook) => hook(this.selectedDates, this.input.value, this));
    }

    close() {
        if (this.config.inline || ! this.isOpen) {
            return;
        }

        this.isOpen = false;
        this.calendarContainer.classList.remove("jdp-open");

        document.removeEventListener("mousedown", this.onDocumentMouseDown, true);
        document.removeEventListener("keydown", this.onKeyDown, true);
        window.removeEventListener("resize", this.reposition);
        window.removeEventListener("scroll", this.reposition, true);

        hooks(this.config.onClose).forEach((hook) => hook(this.selectedDates, this.input.value, this));
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    destroy() {
        this.close();
        this.observer?.disconnect();
        this.calendarContainer?.remove();

        if (this.altInput) {
            this.altInput.remove();
            this.input.type = "text";
            this.input.classList.remove("jdp-input");
        }

        delete this.input.value;
        delete this.element._flatpickr;
    }

    /* ------------------------------------------------------------------ */
    /* Value handling                                                      */
    /* ------------------------------------------------------------------ */

    get valueString() {
        return this.selectedDates.map((date) => formatGregorian(date, this.config.dateFormat)).join(" to ");
    }

    updateAltInput() {
        if (! this.altInput) {
            return;
        }

        this.altInput.value = this.selectedDates[0]
            ? toPersianDigits(formatJalaliInput(this.selectedDates[0], this.config.enableTime))
            : "";
    }

    updateValue(triggerChange) {
        const value = this.valueString;

        this.isSyncing = true;
        NATIVE_VALUE.set.call(this.input, value);
        this.isSyncing = false;

        this.updateAltInput();

        if (! triggerChange) {
            return;
        }

        this.input.dispatchEvent(new Event("input", { bubbles: true }));
        this.input.dispatchEvent(new Event("change", { bubbles: true }));

        hooks(this.config.onChange).forEach((hook) => hook([...this.selectedDates], value, this));
    }

    isDisabled(date) {
        const day = startOfDay(date);

        if (this.config.minDate && day < startOfDay(this.config.minDate)) {
            return true;
        }

        if (this.config.maxDate && day > startOfDay(this.config.maxDate)) {
            return true;
        }

        return asArray(this.config.disable).some((rule) => {
            if (typeof rule === "function") {
                return rule(day);
            }

            if (rule && typeof rule === "object" && ! (rule instanceof Date) && rule.from && rule.to) {
                return day >= startOfDay(toDate(rule.from)) && day <= startOfDay(toDate(rule.to));
            }

            const disabled = toDate(rule);

            return disabled && sameDay(disabled, day);
        });
    }

    selectDay(date) {
        if (this.isDisabled(date)) {
            return;
        }

        if (this.config.mode === "range") {
            if (this.selectedDates.length !== 1) {
                this.selectedDates = [date];
            } else {
                this.selectedDates = [this.selectedDates[0], date].sort((a, b) => a - b);
            }

            this.hoverDate = null;
            this.render();
            this.updateValue(true);

            return;
        }

        if (this.config.enableTime) {
            const previous = this.selectedDates[0];

            date.setHours(
                previous ? previous.getHours() : this.config.defaultHour,
                previous ? previous.getMinutes() : this.config.defaultMinute,
                0
            );
        }

        this.selectedDates = [date];
        this.render();
        this.updateValue(true);

        if (! this.config.enableTime) {
            this.close();
        }
    }

    setTime(hours, minutes) {
        const base = this.selectedDates[0] ? new Date(this.selectedDates[0]) : startOfDay(new Date());

        base.setHours(
            Math.min(23, Math.max(0, hours || 0)),
            Math.min(59, Math.max(0, minutes || 0)),
            0
        );

        this.selectedDates = [base];
        this.render();
        this.updateValue(true);
    }

    /* ------------------------------------------------------------------ */
    /* Rendering                                                           */
    /* ------------------------------------------------------------------ */

    setupCalendar() {
        const container = document.createElement("div");

        container.className = "jdp-calendar";
        container.dir = "rtl";
        container.classList.toggle("jdp-inline", !! this.config.inline);
        container.classList.toggle("jdp-multi", this.config.showMonths > 1);

        container.addEventListener("mousedown", (event) => {
            // Keep focus on the text input while clicking inside the calendar.
            if (! ["INPUT", "SELECT"].includes(event.target.tagName)) {
                event.preventDefault();
            }
        });

        this.calendarContainer = container;

        if (this.config.inline) {
            const host = this.config.appendTo || this.input.parentNode;

            if (this.config.appendTo) {
                host.appendChild(container);
            } else {
                this.input.insertAdjacentElement("afterend", container);
            }
        } else {
            (this.config.appendTo || document.body).appendChild(container);
        }
    }

    render() {
        if (! this.calendarContainer || ! this.viewYear) {
            return;
        }

        const container = this.calendarContainer;

        container.innerHTML = "";

        const months = document.createElement("div");

        months.className = "jdp-months";

        for (let index = 0; index < this.config.showMonths; index++) {
            let month = this.viewMonth + index;
            let year = this.viewYear;

            if (month > 12) {
                month -= 12;
                year += 1;
            }

            months.appendChild(this.renderMonth(year, month, index));
        }

        container.appendChild(months);

        if (! this.config.inline || this.config.enableTime) {
            container.appendChild(this.renderFooter());
        }
    }

    renderMonth(year, month, index) {
        const wrapper = document.createElement("div");

        wrapper.className = "jdp-month";

        /* Header */
        const header = document.createElement("div");

        header.className = "jdp-header";

        const prev = this.button("jdp-nav", "›", "ماه قبل", () => this.changeMonth(-1));
        const next = this.button("jdp-nav", "‹", "ماه بعد", () => this.changeMonth(1));

        if (index !== 0) {
            prev.style.visibility = "hidden";
        }

        if (index !== this.config.showMonths - 1) {
            next.style.visibility = "hidden";
        }

        const title = document.createElement("div");

        title.className = "jdp-title";

        if (this.config.showMonths === 1) {
            const monthSelect = document.createElement("select");

            monthSelect.className = "jdp-select";
            monthSelect.setAttribute("aria-label", "ماه");

            JALALI_MONTHS.forEach((name, i) => monthSelect.add(new Option(name, i + 1, false, i + 1 === month)));

            monthSelect.addEventListener("change", () => {
                this.viewMonth = parseInt(monthSelect.value, 10);
                this.render();
            });

            const yearSelect = document.createElement("select");

            yearSelect.className = "jdp-select";
            yearSelect.setAttribute("aria-label", "سال");

            const currentYear = dateToJalali(new Date()).jy;
            const fromYear = Math.min(year, this.config.minDate ? dateToJalali(this.config.minDate).jy : currentYear - 100);
            const toYear = Math.max(year, this.config.maxDate ? dateToJalali(this.config.maxDate).jy : currentYear + 20);

            for (let y = toYear; y >= fromYear; y--) {
                yearSelect.add(new Option(toPersianDigits(y), y, false, y === year));
            }

            yearSelect.addEventListener("change", () => {
                this.viewYear = parseInt(yearSelect.value, 10);
                this.render();
            });

            title.append(monthSelect, yearSelect);
        } else {
            title.textContent = `${JALALI_MONTHS[month - 1]} ${toPersianDigits(year)}`;
        }

        header.append(prev, title, next);
        wrapper.appendChild(header);

        /* Weekdays (Saturday first) */
        const weekdays = document.createElement("div");

        weekdays.className = "jdp-weekdays";

        JALALI_WEEKDAYS_NARROW.forEach((label, i) => {
            const cell = document.createElement("span");

            cell.textContent = label;

            if (i === 6) {
                cell.className = "jdp-friday";
            }

            weekdays.appendChild(cell);
        });

        wrapper.appendChild(weekdays);

        /* Days */
        const grid = document.createElement("div");

        grid.className = "jdp-days";

        const first = jalaliToDate(year, month, 1);
        const offset = (first.getDay() + 1) % 7;
        const length = jalaliMonthLength(year, month);
        const showOutside = this.config.showMonths === 1;
        const today = new Date();

        const cells = showOutside ? (offset + length > 35 ? 42 : 35) : offset + length;

        for (let i = 0; i < cells; i++) {
            const dayNumber = i - offset + 1;
            const date = new Date(first.getFullYear(), first.getMonth(), first.getDate() + (i - offset));
            const outside = dayNumber < 1 || dayNumber > length;

            if (outside && ! showOutside) {
                grid.appendChild(document.createElement("span"));

                continue;
            }

            const cell = this.button("jdp-day", toPersianDigits(dateToJalali(date).jd), null, () => this.selectDay(date));

            const classes = [];

            if (outside) classes.push("jdp-outside");
            if (sameDay(date, today)) classes.push("jdp-today");
            if (date.getDay() === 5) classes.push("jdp-friday");
            if (this.isDisabled(date)) {
                classes.push("jdp-disabled");
                cell.disabled = true;
            }

            const [start, end] = this.selectedDates;
            const rangeEnd = end || (this.config.mode === "range" && start ? this.hoverDate : null);

            if (sameDay(date, start) || sameDay(date, end)) {
                classes.push("jdp-selected");
            }

            if (this.config.mode === "range" && start && rangeEnd) {
                const [low, high] = [start, rangeEnd].sort((a, b) => a - b);
                const day = startOfDay(date);

                if (day > startOfDay(low) && day < startOfDay(high)) classes.push("jdp-in-range");
                if (sameDay(date, low)) classes.push("jdp-range-start");
                if (sameDay(date, high)) classes.push("jdp-range-end");
            }

            cell.className = ["jdp-day", ...classes].join(" ");
            cell.setAttribute("aria-label", formatJalaliInput(date));

            if (this.config.mode === "range") {
                cell.addEventListener("mouseenter", () => {
                    if (this.selectedDates.length === 1 && ! sameDay(this.hoverDate, date)) {
                        this.hoverDate = date;
                        this.render();
                    }
                });
            }

            grid.appendChild(cell);
        }

        wrapper.appendChild(grid);

        return wrapper;
    }

    renderFooter() {
        const footer = document.createElement("div");

        footer.className = "jdp-footer";

        if (this.config.enableTime) {
            const time = document.createElement("div");

            time.className = "jdp-time";
            time.dir = "ltr";

            const selected = this.selectedDates[0];

            const hours = this.timeInput(selected ? selected.getHours() : this.config.defaultHour, 23, "ساعت");
            const minutes = this.timeInput(selected ? selected.getMinutes() : this.config.defaultMinute, 59, "دقیقه");

            const apply = () => this.setTime(parseInt(hours.value, 10), parseInt(minutes.value, 10));

            hours.addEventListener("change", apply);
            minutes.addEventListener("change", apply);

            const separator = document.createElement("span");

            separator.textContent = ":";

            time.append(hours, separator, minutes);
            footer.appendChild(time);
        }

        if (! this.config.inline) {
            const actions = document.createElement("div");

            actions.className = "jdp-actions";

            actions.appendChild(this.button("jdp-action", "امروز", null, () => {
                const now = new Date();

                if (this.config.enableTime) {
                    this.selectedDates = [now];
                    this.jumpToDate(now);
                    this.updateValue(true);
                } else {
                    this.jumpToDate(now);
                    this.selectDay(startOfDay(now));
                }
            }));

            actions.appendChild(this.button("jdp-action", "پاک کردن", null, () => {
                this.clear();
                this.close();
            }));

            if (this.config.enableTime) {
                actions.appendChild(this.button("jdp-action jdp-primary", "تأیید", null, () => this.close()));
            }

            footer.appendChild(actions);
        }

        return footer;
    }

    button(className, text, label, handler) {
        const button = document.createElement("button");

        button.type = "button";
        button.className = className;
        button.textContent = text;

        if (label) {
            button.setAttribute("aria-label", label);
            button.title = label;
        }

        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            handler();
        });

        return button;
    }

    timeInput(value, max, label) {
        const input = document.createElement("input");

        input.type = "number";
        input.min = "0";
        input.max = String(max);
        input.value = pad(value);
        input.className = "jdp-time-input";
        input.setAttribute("aria-label", label);

        return input;
    }

    /* ------------------------------------------------------------------ */
    /* Popup behaviour                                                     */
    /* ------------------------------------------------------------------ */

    reposition() {
        if (! this.isOpen || ! this.altInput) {
            return;
        }

        const container = this.calendarContainer;
        const rect = this.altInput.getBoundingClientRect();
        const height = container.offsetHeight;
        const width = container.offsetWidth;
        const below = window.innerHeight - rect.bottom;
        const top = below < height + 8 && rect.top > height + 8
            ? rect.top + window.scrollY - height - 4
            : rect.bottom + window.scrollY + 4;

        let left = document.documentElement.dir === "rtl"
            ? rect.right + window.scrollX - width
            : rect.left + window.scrollX;

        left = Math.max(window.scrollX + 4, Math.min(left, window.scrollX + document.documentElement.clientWidth - width - 4));

        container.style.top = `${top}px`;
        container.style.left = `${left}px`;
    }

    onDocumentMouseDown(event) {
        if (
            this.calendarContainer.contains(event.target)
            || event.target === this.altInput
        ) {
            return;
        }

        this.commitTypedValue();
        this.close();
    }

    onKeyDown(event) {
        if (event.key === "Escape") {
            this.close();
        }
    }
}

/**
 * Flatpickr can be called with or without `new`; keep both working.
 */
export function JalaliFlatpickr(element, options) {
    return new JalaliPicker(element, options);
}

JalaliFlatpickr.localize = () => {};
JalaliFlatpickr.l10ns = {};
JalaliFlatpickr.defaultConfig = {};
