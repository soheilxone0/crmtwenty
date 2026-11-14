{!! view_render_event('admin.dashboard.index.revenue_by_sources.before') !!}

<v-dashboard-revenue-by-sources>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.revenue-by-sources />
</v-dashboard-revenue-by-sources>

{!! view_render_event('admin.dashboard.index.revenue_by_sources.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-revenue-by-sources-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue-by-sources />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 6"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-settings-sources"
                            style="--tone: var(--ct-indigo)"
                        ></span>

                        @lang('admin::app.dashboard.index.revenue-by-sources.title')
                    </p>
                </div>

                <template v-if="report.statistics.length">
                    <!-- Doughnut with the total in the middle -->
                    <div class="ct-donut">
                        <x-admin::charts.doughnut
                            ::labels="chartLabels"
                            ::datasets="chartDatasets"
                        />

                        <div class="ct-donut-center">
                            <strong v-count-up="$ct.compact(total)"></strong>

                            <span>@lang('admin::app.dashboard.index.pdf.total')</span>
                        </div>
                    </div>

                    <!-- Legend with values and shares -->
                    <div class="ct-bars !gap-3">
                        <div
                            class="ct-bar-meta"
                            v-for="(stat, index) in report.statistics"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <i
                                    class="ct-dot"
                                    :style="{ '--tone': $ct.color(index) }"
                                ></i>

                                <b class="truncate">@{{ stat.name }}</b>
                            </span>

                            <span class="whitespace-nowrap">
                                <em>@{{ $ct.num(stat.total) }}</em>

                                <small>@{{ $ct.pct($ct.share(stat.total, total)) }}</small>
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Empty state -->
                <div
                    class="ct-empty"
                    v-else
                >
                    <span class="ct-chip icon-settings-sources"></span>

                    <b>@lang('admin::app.dashboard.index.revenue-by-sources.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.revenue-by-sources.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-revenue-by-sources', {
            template: '#v-dashboard-revenue-by-sources-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                total() {
                    return this.report.statistics.reduce((sum, { total }) => sum + (Number(total) || 0), 0);
                },

                chartLabels() {
                    return this.report.statistics.map(({ name }) => name);
                },

                chartDatasets() {
                    return [{
                        data: this.report.statistics.map(({ total }) => Number(total) || 0),
                        backgroundColor: this.report.statistics.map((stat, index) => this.$ct.color(index)),
                    }];
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

                    filters.type = 'revenue-by-sources';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                },
            }
        });
    </script>
@endPushOnce
