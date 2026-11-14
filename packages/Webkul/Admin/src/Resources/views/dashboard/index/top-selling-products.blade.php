{!! view_render_event('admin.dashboard.index.top_selling_proudcts.before') !!}

<v-dashboard-top-selling-products>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.index.top-selling-products />
</v-dashboard-top-selling-products>

{!! view_render_event('admin.dashboard.index.top_selling_proudcts.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-selling-products-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <x-admin::shimmer.dashboard.index.top-selling-products />
        </template>

        <template v-else>
            <div
                class="ct-card ct-rise w-full"
                style="--i: 3"
            >
                <div class="ct-card-head">
                    <p class="ct-card-title">
                        <span
                            class="ct-chip sm icon-product"
                            style="--tone: var(--ct-amber)"
                        ></span>

                        @lang('admin::app.dashboard.index.top-selling-products.title')
                    </p>
                </div>

                <!-- Ranked list -->
                <div
                    class="ct-rank -mx-2"
                    v-if="report.statistics.length"
                >
                    <a
                        :href="'{{ route('admin.products.view', ':id') }}'.replace(':id', item.id)"
                        class="ct-rank-row"
                        target="_blank"
                        v-for="(item, index) in report.statistics"
                    >
                        <span class="ct-rank-no">@{{ $ct.num(index + 1) }}</span>

                        <div class="ct-rank-body gap-1.5">
                            <div class="flex items-baseline justify-between gap-3">
                                <b v-text="item.name"></b>

                                <span class="ct-rank-value">@{{ item.formatted_revenue }}</span>
                            </div>

                            <div class="ct-bar-track !h-1.5">
                                <div
                                    class="ct-bar-fill"
                                    :style="{ width: Math.max(4, (Number(item.revenue) || 0) / max * 100) + '%', '--i': index, '--tone': $ct.color(index) }"
                                ></div>
                            </div>

                            <span>@{{ item.formatted_price }}</span>
                        </div>
                    </a>
                </div>

                <!-- Empty state -->
                <div
                    class="ct-empty"
                    v-else
                >
                    <span class="ct-chip icon-product"></span>

                    <b>@lang('admin::app.dashboard.index.top-selling-products.empty-title')</b>

                    <span>@lang('admin::app.dashboard.index.top-selling-products.empty-info')</span>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-selling-products', {
            template: '#v-dashboard-top-selling-products-template',

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

                    filters.type = 'top-selling-products';

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
