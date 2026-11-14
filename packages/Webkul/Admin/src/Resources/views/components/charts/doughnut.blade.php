<v-charts-doughnut {{ $attributes }}></v-charts-doughnut>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-charts-doughnut-template"
    >
        <canvas
            :id="$.uid + '_chart'"
            class="flex w-full max-w-full items-end"
        ></canvas>
    </script>

    <script type="module">
        app.component('v-charts-doughnut', {
            template: '#v-charts-doughnut-template',

            props: {
                labels: {
                    type: Array,
                    default: () => [],
                },

                datasets: {
                    type: Array,
                    default: () => [],
                },

                cutout: {
                    type: String,
                    default: '76%',
                },

                /**
                 * Formats the value in the tooltip.
                 */
                format: {
                    type: Function,
                    default: null,
                },
            },

            data() {
                return {
                    chart: undefined,
                }
            },

            mounted() {
                this.prepare();
            },

            beforeUnmount() {
                this.chart?.destroy();
            },

            methods: {
                prepare() {
                    if (this.chart) {
                        this.chart.destroy();
                    }

                    const surface = window.ct ? window.ct.tokens().surface : '#fff';

                    const datasets = this.datasets.map((dataset) => ({
                        borderRadius: 8,
                        spacing: 3,
                        borderWidth: 0,
                        hoverOffset: 8,
                        hoverBorderColor: surface,
                        ...dataset,
                    }));

                    this.chart = new Chart(document.getElementById(this.$.uid + '_chart'), {
                        type: 'doughnut',

                        data: {
                            labels: this.labels,

                            datasets,
                        },

                        options: {
                            cutout: this.cutout,

                            layout: {
                                padding: 8,
                            },

                            animation: {
                                animateRotate: true,
                                animateScale: true,
                                duration: 1100,
                            },

                            plugins: {
                                legend: {
                                    display: false,
                                },

                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const value = this.format
                                                ? this.format(context.raw)
                                                : (window.ct ? window.ct.num(context.raw) : context.raw);

                                            const total = context.dataset.data.reduce((sum, item) => sum + (Number(item) || 0), 0);

                                            const share = window.ct
                                                ? window.ct.pct(window.ct.share(context.raw, total))
                                                : '';

                                            return ' ' + context.label + ': ' + value + (share ? '  (' + share + ')' : '');
                                        },
                                    },
                                },
                            },
                        }
                    });
                }
            }
        });
    </script>
@endPushOnce
