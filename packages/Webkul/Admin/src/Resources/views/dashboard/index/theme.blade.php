{{--
    Shared look and helpers for every dashboard widget: Chart.js defaults (font, colours,
    tooltips, motion), a flat palette, number formatting and theme switching.
--}}
<script type="module">
    (() => {
        const root = document.documentElement;
        const locale = root.lang || 'en';
        const rtl = root.dir === 'rtl';

        const palette = [
            '#6366F1', '#10B981', '#F59E0B', '#0EA5E9', '#F43F5E',
            '#8B5CF6', '#14B8A6', '#EC4899', '#84CC16', '#F97316',
        ];

        const isDark = () => root.classList.contains('dark');

        const tokens = () => isDark()
            ? { text: '#94a3b8', strong: '#e2e8f0', grid: 'rgba(148, 163, 184, .12)', tip: '#f8fafc', tipText: '#0f172a', surface: '#0f1522' }
            : { text: '#64748b', strong: '#0f172a', grid: 'rgba(15, 23, 42, .06)', tip: '#0f172a', tipText: '#f8fafc', surface: '#ffffff' };

        const number = new Intl.NumberFormat(locale, { maximumFractionDigits: 2 });
        const compact = new Intl.NumberFormat(locale, { notation: 'compact', maximumFractionDigits: 1 });
        const percent = new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 1 });

        const ct = {
            palette,
            rtl,
            isDark,
            tokens,

            color(index) {
                return palette[index % palette.length];
            },

            num(value) {
                return number.format(Number(value) || 0);
            },

            compact(value) {
                return compact.format(Number(value) || 0);
            },

            pct(value) {
                return percent.format(Math.abs(Number(value) || 0) / 100);
            },

            share(part, whole) {
                return whole > 0 ? (Number(part) || 0) / whole * 100 : 0;
            },

            /**
             * Badge class for a change. `inverse` is for figures where growth is bad (outflow).
             */
            delta(value, inverse = false) {
                value = Number(value) || 0;

                if (Math.abs(value) < 0.005) {
                    return 'flat';
                }

                return (value > 0) !== inverse ? 'up' : 'down';
            },

            deltaIcon(value) {
                value = Number(value) || 0;

                return value < 0 ? 'icon-down-arrow' : 'icon-up-arrow';
            },
        };

        window.ct = ct;

        if (window.app?.config) {
            window.app.config.globalProperties.$ct = ct;
        }

        const Chart = window.Chart;

        if (! Chart) {
            return;
        }

        const apply = () => {
            const t = tokens();
            const d = Chart.defaults;

            d.font.family = "'Vazirmatn', Tahoma, sans-serif";
            d.font.size = 12;
            d.color = t.text;
            d.borderColor = t.grid;
            d.responsive = true;

            d.animation.duration = 900;
            d.animation.easing = 'easeOutQuart';

            d.layout.padding = 0;

            d.elements.bar.borderRadius = 8;
            d.elements.bar.borderSkipped = false;
            d.elements.arc.borderWidth = 0;
            d.elements.line.tension = 0.4;
            d.elements.line.borderWidth = 2.5;
            d.elements.point.radius = 0;
            d.elements.point.hoverRadius = 5;
            d.elements.point.hoverBorderWidth = 3;

            d.scale.grid.color = t.grid;
            d.scale.border.display = false;
            d.scale.ticks.padding = 8;

            d.plugins.legend.display = false;
            d.plugins.legend.rtl = rtl;

            Object.assign(d.plugins.tooltip, {
                backgroundColor: t.tip,
                titleColor: t.tipText,
                bodyColor: t.tipText,
                footerColor: t.tipText,
                padding: { x: 12, y: 10 },
                cornerRadius: 12,
                caretSize: 6,
                caretPadding: 8,
                boxWidth: 8,
                boxHeight: 8,
                boxPadding: 6,
                usePointStyle: true,
                rtl,
                textDirection: rtl ? 'rtl' : 'ltr',
                titleFont: { weight: '700', size: 12 },
                bodyFont: { size: 12 },
                titleMarginBottom: 6,
                displayColors: true,
                borderWidth: 0,
            });

            d.plugins.tooltip.callbacks.labelPointStyle = () => ({ pointStyle: 'circle', rotation: 0 });
        };

        apply();

        /**
         * Dark mode is switched without a reload, so the charts follow it.
         */
        let current = isDark();

        new MutationObserver(() => {
            if (current === isDark()) {
                return;
            }

            current = isDark();

            apply();

            Object.values(Chart.instances || {}).forEach((chart) => {
                const t = tokens();

                Object.values(chart.options.scales || {}).forEach((scale) => {
                    if (scale.grid) {
                        scale.grid.color = t.grid;
                    }

                    if (scale.ticks) {
                        scale.ticks.color = t.text;
                    }
                });

                Object.assign(chart.options.plugins.tooltip, {
                    backgroundColor: t.tip,
                    titleColor: t.tipText,
                    bodyColor: t.tipText,
                });

                chart.update('none');
            });
        }).observe(root, { attributes: true, attributeFilter: ['class'] });
    })();
</script>
