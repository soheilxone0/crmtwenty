// Builds the Phosphor-based icon set that replaces the original icon font.
// Usage (from a folder with the packages of scripts/brand/package.json installed):
//   node icons.mjs <admin-assets-dir> <platform-public-dir>
// The admin icon CSS is written next to the assets dir, to be pasted into css/app.css.
import fs from "node:fs";
import path from "node:path";
import subsetFont from "subset-font";

const root = path.resolve("node_modules/@phosphor-icons/web/src");
const [adminAssets, platformDir] = process.argv.slice(2);

function codes(weight) {
    const css = fs.readFileSync(`${root}/${weight}/style.css`, "utf8");
    const map = {};
    const re = /\.ph(?:-fill)?\.ph-([a-z0-9-]+):before\s*{\s*content:\s*"\\([0-9a-f]+)"/g;
    let m;
    while ((m = re.exec(css))) map[m[1]] = parseInt(m[2], 16);
    return map;
}

const regular = codes("regular");
const fill = codes("fill");

// panel class => [phosphor name, weight]
const admin = {
    activity: ["pulse"], "add-2": ["plus-circle"], add: ["plus"], "attached-file": ["paperclip"],
    attachment: ["paperclip-horizontal"], attribute: ["sliders-horizontal"],
    "bookmark-active": ["bookmark-simple", "fill"], bookmark: ["bookmark-simple"],
    calendar: ["calendar-blank"], call: ["phone"], "checkbox-multiple": ["minus-square", "fill"],
    "checkbox-outline": ["square"], "checkbox-select": ["check-square", "fill"],
    configuration: ["gear-six"], contact: ["address-book"], "cross-large": ["x"], dark: ["moon-stars"],
    dashboard: ["squares-four"], delete: ["trash"], dollar: ["money"], "down-arrow": ["caret-down"],
    download: ["download-simple"], edit: ["pencil-simple-line"], enter: ["arrow-elbow-down-left"],
    error: ["x-circle", "fill"], "eye-hide": ["eye-slash"], eye: ["eye"], file: ["file-text"],
    filter: ["funnel-simple"], folder: ["folder-simple"], forward: ["arrow-bend-up-right"],
    image: ["image"], info: ["info", "fill"], kanban: ["kanban"], leads: ["target"],
    "left-arrow": ["caret-left"], light: ["sun"], list: ["list-bullets"], location: ["map-pin"],
    mail: ["envelope-simple"], meeting: ["users-three"], menu: ["list"], message: ["chat-circle-text"],
    more: ["dots-three-vertical"], move: ["dots-six-vertical"], note: ["note-pencil"],
    notification: ["bell"], organization: ["buildings"], percentage: ["percent"], perosnal: ["user-circle"],
    pin: ["push-pin"], print: ["printer"], product: ["package"], quote: ["receipt"],
    "radio-normal": ["circle"], "radio-selected": ["radio-button", "fill"],
    "reply-all": ["arrow-bend-double-up-left"], reply: ["arrow-bend-up-left"], "right-arrow": ["caret-right"],
    role: ["shield-check"], rotten: ["clock-countdown"], search: ["magnifying-glass"], sent: ["paper-plane-tilt"],
    setting: ["gear-six"], "settings-attributes": ["sliders-horizontal"], "settings-flow": ["flow-arrow"],
    "settings-group": ["users-three"], "settings-mail": ["envelope-open"], "settings-pipeline": ["funnel"],
    "settings-roles": ["shield-check"], "settings-sources": ["plugs-connected"], "settings-tag": ["tag"],
    "settings-type": ["shapes"], "settings-user": ["user-gear"], "settings-warehouse": ["warehouse"],
    "settings-webforms": ["browser"], "settings-webhooks": ["webhooks-logo"], "stats-down": ["trend-down"],
    "stats-up": ["trend-up"], success: ["check-circle", "fill"], "system-generate": ["sparkle"], tag: ["tag"],
    tick: ["check"], "up-arrow": ["caret-up"], user: ["user"], video: ["video-camera"], warning: ["warning", "fill"],
    help: ["question"], "arrow-down": ["caret-down"], "arrow-up": ["caret-up"],
    "cross-large-large": ["x"], "down-stat": ["trend-down"], "up-stat": ["trend-up"], export: ["export"],
    import: ["tray-arrow-down"], pause: ["pause"], play: ["play"], resetting: ["arrows-clockwise"],
    settings: ["gear-six"], "toast-done": ["check-circle", "fill"],
};

// Icons used by the platform (super admin) panel: class "pi pi-<name>"
const platform = [
    "squares-four", "buildings", "plus", "plus-circle", "gear-six", "sign-out", "user", "user-gear",
    "users-three", "envelope-simple", "lock-key", "key", "eye", "eye-slash", "pencil-simple-line", "trash",
    "power", "check-circle", "x-circle", "warning", "info", "arrow-left", "arrow-right", "caret-left",
    "caret-down", "magnifying-glass", "copy", "sparkle", "shield-check", "database", "paper-plane-tilt",
    "arrow-square-out", "arrows-clockwise", "dots-three-vertical", "list", "x", "image", "palette",
    "calendar-blank", "chart-line-up", "globe-hemisphere-east", "identification-card", "lightning",
    "rocket-launch", "sign-in", "user-plus", "user-switch", "clock", "hard-drives", "check",
    "phone", "target", "address-book", "note-pencil", "house", "question",
];

function glyphs(names, weight) {
    const map = weight === "fill" ? fill : regular;
    return names.map((n) => {
        if (!(n in map)) throw new Error(`Missing ${weight} icon ${n}`);
        return map[n];
    });
}

async function subset(weight, cps, out) {
    const src = fs.readFileSync(`${root}/${weight}/Phosphor${weight === "fill" ? "-Fill" : ""}.woff2`);
    const text = String.fromCodePoint(...new Set(cps));
    const buf = await subsetFont(src, text, { targetFormat: "woff2" });
    fs.writeFileSync(out, buf);
    console.log(out, buf.length);
}

// ---- admin ----
const reg = [], fil = [];
let css = "";
for (const [cls, [name, weight = "regular"]] of Object.entries(admin)) {
    const cp = (weight === "fill" ? fill : regular)[name];
    if (cp === undefined) throw new Error(`Missing ${weight} icon ${name}`);
    (weight === "fill" ? fil : reg).push(cp);
    css += `    .icon-${cls}:before {\n        content: "\\${cp.toString(16)}";${weight === "fill" ? "\n        font-family: 'PhosphorFill';" : ""}\n    }\n\n`;
}
fs.writeFileSync(path.join(adminAssets, "..", "admin-icons.generated.css"), css);
await subset("regular", reg, `${adminAssets}/fonts/phosphor.woff2`);
await subset("fill", fil, `${adminAssets}/fonts/phosphor-fill.woff2`);

// ---- platform ----
const pcps = glyphs(platform, "regular");
await subset("regular", pcps, `${platformDir}/fonts/phosphor.woff2`);
let pcss = "";
platform.forEach((n, i) => (pcss += `.pi-${n}:before{content:"\\${pcps[i].toString(16)}"}\n`));
fs.writeFileSync(`${platformDir}/icons.css`, pcss);
