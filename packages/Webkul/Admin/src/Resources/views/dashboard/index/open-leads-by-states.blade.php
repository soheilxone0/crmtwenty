{!! view_render_event('admin.dashboard.index.open_leads_by_states.before') !!}

<!-- Total Leads Vue Component -->
<v-dashboard-open-leads-by-states>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.open-leads-by-states />
</v-dashboard-open-leads-by-states>

{!! view_render_event('admin.dashboard.index.open_leads_by_states.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-open-leads--by-states-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.open-leads-by-states />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise"
                style="--i: 5"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-settings-pipeline"
                            style="--tone: var(--ct-sky)"
                        ></span>

                        @lang('admin::app.dashboard.index.open-leads-by-states.title')
                    </p>

                    <span
                        class="ct-delta flat"
                        v-if="report.statistics.length"
                    >
                        @{{ $ct.num(total) }}
                    </span>
                </div>

                <!-- Pipeline stages as ranked bars -->
                <div
                    class="ct-bars"
                    v-if="report.statistics.length"
                >
                    <div
                        class="ct-bar-row"
                        v-for="(stat, index) in report.statistics"
                    >
                        <div class="ct-bar-meta">
                            <b class="truncate">@{{ stat.name }}</b>

                            <span class="whitespace-nowrap">
                                <em>@{{ $ct.num(stat.total) }}</em>

                                <small>@{{ $ct.pct($ct.share(stat.total, total)) }}</small>
                            </span>
                        </div>

                        <div class="ct-bar-track">
                            <div
                                class="ct-bar-fill"
                                :style="{ width: Math.max(3, (Number(stat.total) || 0) / max * 100) + '%', '--i': index, '--tone': $ct.color(index) }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    class="ct-empty"
                    v-else
                >
                    <span class="ct-chip icon-settings-pipeline"></span>

                    <b>@lang('admin::app.dashboard.index.open-leads-by-states.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.open-leads-by-states.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-open-leads-by-states', {
            template: '#v-dashboard-open-leads--by-states-template',

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

                max() {
                    return Math.max(1, ...this.report.statistics.map(({ total }) => Number(total) || 0));
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

                    filters.type = 'open-leads-by-states';

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
