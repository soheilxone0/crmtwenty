{!! view_render_event('admin.dashboard.index.sales_funnel.before') !!}

<!-- Sales Funnel Vue Component -->
<v-dashboard-sales-funnel>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.sales-funnel />
</v-dashboard-sales-funnel>

{!! view_render_event('admin.dashboard.index.sales_funnel.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-sales-funnel-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.sales-funnel />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 3"
            >
                <div class="ct-card-head">
                    <div class="grid gap-0.5">
                        <p class="ct-card-title">
                            <span
                                class="ct-chip sm icon-filter"
                                style="--tone: var(--ct-violet)"
                            ></span>

                            @lang('admin::app.dashboard.index.sales-funnel.title')
                        </p>

                        <span
                            class="ct-card-sub"
                            v-if="report.statistics.pipeline"
                        >
                            @{{ report.statistics.pipeline }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ct-funnel-pill">
                            @lang('admin::app.dashboard.index.sales-funnel.total'):
                            @{{ $ct.num(report.statistics.total) }}
                        </span>

                        <span class="ct-funnel-pill win">
                            @lang('admin::app.dashboard.index.sales-funnel.win-rate'):
                            @{{ $ct.pct(report.statistics.win_rate) }}
                        </span>

                        @if (bouncer()->hasPermission('leads'))
                            <a
                                href="{{ route('admin.leads.index') }}"
                                class="ct-funnel-link"
                            >
                                @lang('admin::app.dashboard.index.sales-funnel.open-board')
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Funnel: one centred band per stage, narrowing as leads drop out -->
                <div
                    class="ct-funnel"
                    v-if="report.statistics.total"
                >
                    <template
                        v-for="(stage, index) in report.statistics.stages"
                        :key="stage.id"
                    >
                        <!-- Step conversion between two stages -->
                        <div
                            class="ct-funnel-step"
                            v-if="index"
                        >
                            <span>
                                <i class="icon-down-arrow"></i>

                                @{{ $ct.pct(stage.step_conversion) }}
                            </span>
                        </div>

                        <div class="ct-funnel-row">
                            <div class="ct-funnel-name">
                                <b class="truncate">@{{ stage.name }}</b>

                                <small>
                                    @{{ $ct.num(stage.current) }}
                                    @lang('admin::app.dashboard.index.sales-funnel.in-stage')
                                </small>
                            </div>

                            <div class="ct-funnel-lane">
                                <div
                                    class="ct-funnel-band"
                                    :style="{
                                        width: Math.max(14, stage.total / max * 100) + '%',
                                        '--i': index,
                                        '--tone': tone(stage, index),
                                    }"
                                >
                                    @{{ $ct.num(stage.total) }}
                                </div>
                            </div>

                            <div class="ct-funnel-meta">
                                <em>@{{ $ct.pct(stage.conversion) }}</em>

                                <small>@{{ stage.formatted_value }}</small>
                            </div>
                        </div>
                    </template>

                    <!-- Lost leads leave the funnel -->
                    <div
                        class="ct-funnel-lost"
                        v-if="report.statistics.lost"
                    >
                        <span class="ct-dot" style="--tone: var(--ct-rose)"></span>

                        @lang('admin::app.dashboard.index.sales-funnel.lost'):

                        <b>@{{ $ct.num(report.statistics.lost) }}</b>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    class="ct-empty"
                    v-else
                >
                    <span class="ct-chip icon-filter"></span>

                    <b>@lang('admin::app.dashboard.index.sales-funnel.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.sales-funnel.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-sales-funnel', {
            template: '#v-dashboard-sales-funnel-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                max() {
                    return Math.max(1, ...this.report.statistics.stages.map(({ total }) => Number(total) || 0));
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

                    filters.type = 'sales-funnel';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                },

                /**
                 * Solid colour per stage; the won stage is always green.
                 */
                tone(stage, index) {
                    return stage.code == 'won'
                        ? 'var(--ct-emerald)'
                        : ['#4F46E5', '#6366F1', '#0EA5E9', '#8B5CF6', '#F59E0B', '#EC4899'][index % 6];
                },
            }
        });
    </script>
@endPushOnce
