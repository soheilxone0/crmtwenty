{!! view_render_event('admin.dashboard.index.revenue.after') !!}

<!-- Over Details Vue Component -->
<v-dashboard-revenue-stats>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.revenue />
</v-dashboard-revenue-stats>

{!! view_render_event('admin.dashboard.index.revenue.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-revenue-stats-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.revenue />
        </template>

        <!-- Inflow and outflow -->
        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 0"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-dollar"
                            style="--tone: var(--ct-brand)"
                        ></span>

                        @lang('admin::app.dashboard.index.pdf.revenue')
                    </p>

                    <div class="ct-legend max-sm:hidden">
                        <span>
                            <i
                                class="ct-dot live"
                                style="--tone: var(--ct-emerald)"
                            ></i>

                            @lang('admin::app.dashboard.index.revenue.won-revenue')
                        </span>

                        <span>
                            <i
                                class="ct-dot"
                                style="--tone: var(--ct-rose)"
                            ></i>

                            @lang('admin::app.dashboard.index.revenue.lost-revenue')
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                    <!-- Inflow (won revenue) -->
                    <div
                        class="ct-tile"
                        style="--tone: var(--ct-emerald)"
                    >
                        <div class="ct-kpi-top">
                            <span class="ct-kpi-label">
                                @lang('admin::app.dashboard.index.revenue.won-revenue')
                            </span>

                            <span class="ct-chip sm icon-stats-up"></span>
                        </div>

                        <p
                            class="ct-kpi-value"
                            v-count-up="won.formatted_total"
                        ></p>

                        <div class="ct-kpi-foot">
                            <span
                                class="ct-delta"
                                :class="$ct.delta(won.progress)"
                            >
                                <span :class="$ct.deltaIcon(won.progress)"></span>

                                @{{ $ct.pct(won.progress) }}
                            </span>

                            <span>@lang('admin::app.dashboard.index.vs-previous')</span>
                        </div>
                    </div>

                    <!-- Outflow (lost revenue) -->
                    <div
                        class="ct-tile"
                        style="--tone: var(--ct-rose)"
                    >
                        <div class="ct-kpi-top">
                            <span class="ct-kpi-label">
                                @lang('admin::app.dashboard.index.revenue.lost-revenue')
                            </span>

                            <span class="ct-chip sm icon-stats-down"></span>
                        </div>

                        <p
                            class="ct-kpi-value"
                            v-count-up="lost.formatted_total"
                        ></p>

                        <div class="ct-kpi-foot">
                            <span
                                class="ct-delta"
                                :class="$ct.delta(lost.progress, true)"
                            >
                                <span :class="$ct.deltaIcon(lost.progress)"></span>

                                @{{ $ct.pct(lost.progress) }}
                            </span>

                            <span>@lang('admin::app.dashboard.index.vs-previous')</span>
                        </div>
                    </div>
                </div>

                <!-- Share of each side -->
                <div class="grid gap-2.5">
                    <div class="ct-split">
                        <i
                            :style="{ '--tone': 'var(--ct-emerald)', flexGrow: wonShare || (lostShare ? 0 : 1) }"
                        ></i>

                        <i
                            :style="{ '--tone': 'var(--ct-rose)', flexGrow: lostShare || (wonShare ? 0 : 1), animationDelay: '120ms' }"
                        ></i>
                    </div>

                    <div class="ct-kpi-foot justify-between">
                        <span>@{{ $ct.pct(wonShare) }}</span>

                        <span>@{{ $ct.pct(lostShare) }}</span>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-revenue-stats', {
            template: '#v-dashboard-revenue-stats-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                won() {
                    return this.report.statistics.total_won_revenue;
                },

                lost() {
                    return this.report.statistics.total_lost_revenue;
                },

                wonShare() {
                    return this.$ct.share(this.won.current, (Number(this.won.current) || 0) + (Number(this.lost.current) || 0));
                },

                lostShare() {
                    return this.$ct.share(this.lost.current, (Number(this.won.current) || 0) + (Number(this.lost.current) || 0));
                },
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'revenue-stats';

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
