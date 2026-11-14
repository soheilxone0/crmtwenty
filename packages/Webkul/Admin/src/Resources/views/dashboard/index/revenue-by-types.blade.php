{!! view_render_event('admin.dashboard.index.revenue_by_types.before') !!}

<v-dashboard-revenue-by-types>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.revenue-by-types />
</v-dashboard-revenue-by-types>

{!! view_render_event('admin.dashboard.index.revenue_by_types.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-revenue-by-types-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue-by-types />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 7"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-settings-type"
                            style="--tone: var(--ct-violet)"
                        ></span>

                        @lang('admin::app.dashboard.index.revenue-by-types.title')
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
                    <span class="ct-chip icon-settings-type"></span>

                    <b>@lang('admin::app.dashboard.index.revenue-by-types.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.revenue-by-types.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-revenue-by-types', {
            template: '#v-dashboard-revenue-by-types-template',

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

                    filters.type = 'revenue-by-types';

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
