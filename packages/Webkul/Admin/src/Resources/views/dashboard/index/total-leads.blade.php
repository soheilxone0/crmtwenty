{!! view_render_event('admin.dashboard.index.total_leads.before') !!}

<!-- Total Leads Vue Component -->
<v-dashboard-total-leads>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.total-leads />
</v-dashboard-total-leads>

{!! view_render_event('admin.dashboard.index.total_leads.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-leads-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.total-leads />
        </template>

        <!-- Leads over time -->
        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 2"
            >
                <div class="ct-card-head max-sm:flex-wrap">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-leads"
                            style="--tone: var(--ct-sky)"
                        ></span>

                        @lang('admin::app.dashboard.index.total-leads.title')
                    </p>

                    <!-- Series switches, which double as the legend and the totals -->
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="ct-series"
                            :class="{ 'is-off': hidden.includes(index) }"
                            :style="{ '--tone': serie.color }"
                            v-for="(serie, index) in series"
                            :key="serie.key"
                            @click="toggle(index)"
                        >
                            <i class="ct-dot"></i>

                            <span>@{{ serie.label }}</span>

                            <b>@{{ $ct.num(serie.sum) }}</b>
                        </button>
                    </div>
                </div>

                <x-admin::charts.bar
                    ref="chart"
                    ::labels="chartLabels"
                    ::datasets="chartDatasets"
                    ::height="270"
                />
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-leads', {
            template: '#v-dashboard-total-leads-template',

            data() {
                return {
                    report: [],

                    isLoading: true,

                    hidden: [],
                }
            },

            computed: {
                series() {
                    const stats = this.report.statistics;

                    const sum = (items) => items.reduce((total, { count }) => total + (Number(count) || 0), 0);

                    return [
                        {
                            key: 'all',
                            label: @json(trans('admin::app.dashboard.index.total-leads.total')),
                            color: '#6366F1',
                            items: stats.all.over_time,
                            sum: sum(stats.all.over_time),
                        }, {
                            key: 'won',
                            label: @json(trans('admin::app.dashboard.index.total-leads.won')),
                            color: '#10B981',
                            items: stats.won.over_time,
                            sum: sum(stats.won.over_time),
                        }, {
                            key: 'lost',
                            label: @json(trans('admin::app.dashboard.index.total-leads.lost')),
                            color: '#F43F5E',
                            items: stats.lost.over_time,
                            sum: sum(stats.lost.over_time),
                        },
                    ];
                },

                chartLabels() {
                    return this.report.statistics.all.over_time.map(({ label }) => label);
                },

                chartDatasets() {
                    return this.series.map((serie) => ({
                        label: serie.label,
                        data: serie.items.map(({ count }) => count),
                        backgroundColor: serie.color,
                    }));
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'total-leads';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.hidden = [];

                            this.isLoading = false;
                        })
                        .catch(error => {});
                },

                toggle(index) {
                    const canvas = this.$refs.chart?.$el?.querySelector?.('canvas');

                    const chart = canvas ? Chart.getChart(canvas) : null;

                    if (this.hidden.includes(index)) {
                        this.hidden = this.hidden.filter((item) => item !== index);
                    } else if (this.hidden.length < this.series.length - 1) {
                        this.hidden = [...this.hidden, index];
                    }

                    if (chart) {
                        this.series.forEach((serie, i) => chart.setDatasetVisibility(i, ! this.hidden.includes(i)));

                        chart.update();
                    }
                },
            }
        });
    </script>
@endPushOnce
