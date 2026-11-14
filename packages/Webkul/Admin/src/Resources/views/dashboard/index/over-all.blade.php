{!! view_render_event('admin.dashboard.index.over_all.before') !!}

<!-- Over Details Vue Component -->
<v-dashboard-over-all-stats>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.over-all />
</v-dashboard-over-all-stats>

{!! view_render_event('admin.dashboard.index.over_all.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-over-all-stats-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.over-all />
        </template>

        <!-- KPI tiles -->
        <template v-else>
            <div class="ct-kpis">
                <div
                    class="ct-card ct-kpi ct-rise"
                    :style="{ '--i': index + 1, '--tone': card.tone }"
                    v-for="(card, index) in cards"
                    :key="card.key"
                >
                    <div class="ct-kpi-top">
                        <span class="ct-kpi-label">@{{ card.label }}</span>

                        <span
                            class="ct-chip"
                            :class="card.icon"
                        ></span>
                    </div>

                    <p
                        class="ct-kpi-value md"
                        v-count-up="card.value"
                    ></p>

                    <div class="ct-kpi-foot">
                        <span
                            class="ct-delta"
                            :class="$ct.delta(card.progress)"
                        >
                            <span :class="$ct.deltaIcon(card.progress)"></span>

                            @{{ $ct.pct(card.progress) }}
                        </span>

                        <span>@lang('admin::app.dashboard.index.vs-previous')</span>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-over-all-stats', {
            template: '#v-dashboard-over-all-stats-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                cards() {
                    const stats = this.report.statistics;

                    return [
                        {
                            key: 'average_lead_value',
                            label: @json(trans('admin::app.dashboard.index.over-all.average-lead-value')),
                            icon: 'icon-dollar',
                            tone: 'var(--ct-indigo)',
                            value: stats.average_lead_value.formatted_total,
                            progress: stats.average_lead_value.progress,
                        }, {
                            key: 'total_leads',
                            label: @json(trans('admin::app.dashboard.index.over-all.total-leads')),
                            icon: 'icon-leads',
                            tone: 'var(--ct-sky)',
                            value: this.$ct.num(stats.total_leads.current),
                            progress: stats.total_leads.progress,
                        }, {
                            key: 'average_leads_per_day',
                            label: @json(trans('admin::app.dashboard.index.over-all.average-leads-per-day')),
                            icon: 'icon-activity',
                            tone: 'var(--ct-violet)',
                            value: this.$ct.num(Number(stats.average_leads_per_day.current || 0).toFixed(2)),
                            progress: stats.average_leads_per_day.progress,
                        }, {
                            key: 'total_quotations',
                            label: @json(trans('admin::app.dashboard.index.over-all.total-quotations')),
                            icon: 'icon-quote',
                            tone: 'var(--ct-amber)',
                            value: this.$ct.num(stats.total_quotations.current),
                            progress: stats.total_quotations.progress,
                        }, {
                            key: 'total_persons',
                            label: @json(trans('admin::app.dashboard.index.over-all.total-persons')),
                            icon: 'icon-perosnal',
                            tone: 'var(--ct-emerald)',
                            value: this.$ct.num(stats.total_persons.current),
                            progress: stats.total_persons.progress,
                        }, {
                            key: 'total_organizations',
                            label: @json(trans('admin::app.dashboard.index.over-all.total-organizations')),
                            icon: 'icon-organization',
                            tone: 'var(--ct-rose)',
                            value: this.$ct.num(stats.total_organizations.current),
                            progress: stats.total_organizations.progress,
                        },
                    ];
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

                    filters.type = 'over-all';

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
