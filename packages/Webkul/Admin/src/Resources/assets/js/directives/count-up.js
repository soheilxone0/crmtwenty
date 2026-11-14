/**
 * v-count-up="'۱۲٬۳۴۵ ریال'"
 *
 * Animates the first number found in an already formatted string from zero to its value,
 * keeping the original digits (Latin or Persian), separators, decimals and surrounding text.
 */
const PERSIAN = "۰۱۲۳۴۵۶۷۸۹";
const ARABIC = "٠١٢٣٤٥٦٧٨٩";
const NUMBER = /[0-9۰-۹٠-٩](?:[0-9۰-۹٠-٩,٬.٫\u066C\u2009 ]*[0-9۰-۹٠-٩])?/;

const reduceMotion = () =>
    window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

function toLatin(text) {
    return text.replace(/[۰-۹٠-٩]/g, (d) => {
        const p = PERSIAN.indexOf(d);

        return String(p > -1 ? p : ARABIC.indexOf(d));
    });
}

function parse(text) {
    const match = String(text ?? "").match(NUMBER);

    if (! match) {
        return null;
    }

    const token = match[0];
    const latin = toLatin(token);
    const digits = /[۰-۹]/.test(token) ? PERSIAN : /[٠-٩]/.test(token) ? ARABIC : null;

    const separators = latin.replace(/\d/g, "");
    const kinds = [...new Set(separators)];
    let decimalMark = null;

    if (kinds.length > 1) {
        decimalMark = separators[separators.length - 1];
    } else if (kinds.length === 1) {
        const mark = kinds[0];
        const tail = latin.slice(latin.lastIndexOf(mark) + 1);

        if (mark === "٫" || (mark !== "٬" && mark !== " " && mark !== "\u2009" && separators.length === 1 && tail.length !== 3)) {
            decimalMark = mark;
        }
    }

    const cut = decimalMark ? latin.lastIndexOf(decimalMark) : latin.length;
    const integerPart = latin.slice(0, cut);
    const fraction = decimalMark ? latin.slice(cut + 1) : "";
    const group = integerPart.replace(/\d/g, "")[0] || "";
    const decimals = fraction.length;
    const value = parseFloat(integerPart.replace(/\D/g, "") + (fraction ? "." + fraction : "")) || 0;

    return {
        before: text.slice(0, match.index),
        after: text.slice(match.index + token.length),
        value,
        decimals,
        decimalMark,
        group,
        digits,
    };
}

function format(n, spec) {
    let [int, frac] = n.toFixed(spec.decimals).split(".");

    if (spec.group) {
        int = int.replace(/\B(?=(\d{3})+(?!\d))/g, spec.group);
    }

    let out = frac ? int + spec.decimalMark + frac : int;

    if (spec.digits) {
        out = out.replace(/\d/g, (d) => spec.digits[d]);
    }

    return spec.before + out + spec.after;
}

function run(el, text) {
    cancelAnimationFrame(el.__countUpFrame);

    const spec = parse(text);

    if (! spec || reduceMotion() || spec.value === 0) {
        el.textContent = text ?? "";

        return;
    }

    const duration = Math.min(1600, 700 + Math.log10(spec.value + 1) * 120);
    const start = performance.now();

    const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 4);

        el.textContent = t < 1 ? format(spec.value * eased, spec) : text;

        if (t < 1) {
            el.__countUpFrame = requestAnimationFrame(step);
        }
    };

    el.__countUpFrame = requestAnimationFrame(step);
}

export default {
    mounted(el, binding) {
        run(el, binding.value);
    },

    updated(el, binding) {
        if (binding.value !== binding.oldValue) {
            run(el, binding.value);
        }
    },

    unmounted(el) {
        cancelAnimationFrame(el.__countUpFrame);
    },
};
