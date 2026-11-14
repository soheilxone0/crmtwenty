import Flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";

/**
 * crmtwenty: public web forms always use the light theme, so no theme stylesheet is loaded
 * from a CDN.
 */
export default {
    install: () => {
        window.Flatpickr = Flatpickr;
    },
};
