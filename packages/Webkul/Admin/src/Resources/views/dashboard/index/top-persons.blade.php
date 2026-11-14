{!! view_render_event('admin.dashboard.index.top_persons.before') !!}

<v-dashboard-top-persons>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.top-persons />
</v-dashboard-top-persons>

{!! view_render_event('admin.dashboard.index.top_persons.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-persons-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-persons />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise w-full"
                style="--i: 4"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-perosnal"
                            style="--tone: var(--ct-emerald)"
                        ></span>

                        @lang('admin::app.dashboard.index.top-persons.title')
                    </p>
                </div>

                <!-- Ranked list -->
                <div
                    class="ct-rank -mx-2"
                    v-if="report.statistics.length"
                >
                    <a
                        :href="'{{ route('admin.contacts.persons.view', ':id') }}'.replace(':id', item.id)"
                        class="ct-rank-row"
                        target="_blank"
                        v-for="(item, index) in report.statistics"
                    >
                        <!-- Person Initials -->
                        <x-admin::avatar ::name="item.name" />

                        <!-- Person Details -->
                        <div class="ct-rank-body">
                            <b>@{{ item.name }}</b>

                            <span>@{{ (item.emails || []).map(item => item.value).join(', ') }}</span>
                        </div>

                        <span
                            class="ct-rank-value"
                            v-if="item.formatted_revenue"
                        >@{{ item.formatted_revenue }}</span>
                    </a>
                </div>

                <!-- Empty state -->
                <div
                    class="ct-empty"
                    v-else
                >
                    <span class="ct-chip icon-perosnal"></span>

                    <b>@lang('admin::app.dashboard.index.top-persons.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.top-persons.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-persons', {
            template: '#v-dashboard-top-persons-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                max() {
                    return Math.max(1, ...this.report.statistics.map((item) => Number(item.revenue) || 0));
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

                    filters.type = 'top-persons';

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
