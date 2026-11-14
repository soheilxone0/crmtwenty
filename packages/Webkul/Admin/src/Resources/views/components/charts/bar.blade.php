<v-charts-bar {{ $attributes }}></v-charts-bar>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-charts-bar-template"
    >
        <div
            class="relative w-full"
            :style="{ height: height + 'px' }"
        >
            <canvas :id="$.uid + '_chart'"></canvas>
        </div>
    </script>

    <script type="module">
        app.component('v-charts-bar', {
            template: '#v-charts-bar-template',

            props: {
                labels: {
                    type: Array,
                    default: () => [],
                },

                datasets: {
                    type: Array,
                    default: () => [],
                },

                height: {
                    type: Number,
                    default: 260,
                },

                stacked: {
                    type: Boolean,
                    default: false,
                },

                /**
                 * Kept for templates that still pass it; the chart now fills a fixed height.
                 */
                aspectRatio: {
                    type: Number,
                    default: 3.23,
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

                    const count = Math.max(1, this.datasets.length);
                    const points = Math.max(1, this.labels.length);

                    const datasets = this.datasets.map((dataset) => ({
                        borderRadius: this.stacked ? 4 : 999,
                        borderSkipped: false,
                        maxBarThickness: this.stacked ? 28 : Math.max(6, 22 - count * 2),
                        categoryPercentage: points > 20 ? 0.8 : 0.62,
                        barPercentage: 0.9,
                        hoverBackgroundColor: dataset.backgroundColor,
                        ...dataset,
                    }));

                    this.chart = new Chart(document.getElementById(this.$.uid + '_chart'), {
                        type: 'bar',

                        data: {
                            labels: this.labels,

                            datasets,
                        },

                        options: {
                            maintainAspectRatio: false,

                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },

                            animation: {
                                delay: (context) => context.type === 'data' && context.mode === 'default'
                                    ? context.dataIndex * 35 + context.datasetIndex * 90
                                    : 0,
                            },

                            plugins: {
                                legend: {
                                    display: false,
                                },
                            },

                            scales: {
                                x: {
                                    stacked: this.stacked,

                                    grid: {
                                        display: false,
                                    },

                                    ticks: {
                                        maxRotation: 0,
                                        autoSkipPadding: 14,
                                    },
                                },

                                y: {
                                    stacked: this.stacked,

                                    beginAtZero: true,

                                    grace: '8%',

                                    grid: {
                                        drawTicks: false,
                                    },

                                    border: {
                                        display: false,
                                        dash: [4, 4],
                                    },

                                    ticks: {
                                        precision: 0,
                                        maxTicksLimit: 5,
                                        callback: (value) => window.ct ? window.ct.compact(value) : value,
                                    },
                                },
                            },
                        },
                    });
                }
            }
        });
    </script>
@endPushOnce
