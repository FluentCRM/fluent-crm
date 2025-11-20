<template>
    <div class="fcrm-abandon-reports-wrapper fluentcrm-view-wrapper fluentcrm_view">
        <el-alert
            v-if="!haveAutomation"
            class="fcrm-alert-warning"
            type="warning"
            :show-icon="false"
        >
            <template slot="title">
                <p>{{ $t('No active Abandoned Cart automation.') }} <a href="#" @click="goToFunnel">{{
                        $t('Set up an automation')
                    }}</a> to recover lost sales. To Learn More <a
                    href="https://fluentcrm.com/docs/abandon-cart-automation/" target="_blank">{{
                        $t('Click here')
                    }}</a>.
                </p>
            </template>
        </el-alert>
        <div class="fcrm-abandon-reports-header">
            <div class="title">{{ $t('Abandon Carts - Reports') }}</div>
            <div class="action">
                <el-date-picker
                    v-model="dateRange"
                    :end-placeholder="$t('End date')"
                    :picker-options="pickerOptions"
                    :range-separator="$t('To')"
                    :start-placeholder="$t('Start date')"
                    align="right"
                    type="daterange"
                    @change="getWidgetAndCartsReports"
                >
                </el-date-picker>
                <el-button @click="goToAbandonedSettings"><i class="el-icon-setting"></i></el-button>
            </div>
        </div>

        <div v-if="loading" class="fc_block_white">
            <el-skeleton :rows="6" animated/>
        </div>
        <abandon-report-widgets v-else :widgets="reportSummary"/>

        <div class="fcrm-abandon-report-carts-wrap">
            <div class="fcrm-abandon-reports-header">
                <div class="left">
                    <el-select @change="getCartsReports()" size="small" v-model="query.status">
                        <el-option
                            v-for="(label, value) in cartStatuses"
                            :key="value"
                            :label="label"
                            :value="value"
                        >
                        </el-option>
                    </el-select>
                </div>

                <div class="action">
                    <el-input
                        v-model="query.search"
                        clearable
                        :placeholder="$t('Search by Name/ Email')"
                        size="small"
                        @keydown.native.enter="getCartsReports()"
                        @clear="getCartsReports()"
                    >
                        <el-button @click="getCartsReports()" slot="append" icon="el-icon-search"></el-button>
                    </el-input>

                </div>
            </div>

            <div v-if="loading || cartsLoading" class="fc_block_white">
                <el-skeleton :rows="6" animated/>
            </div>
            <abandon-report-carts @refetch="getCartsReports" v-else :carts="carts">
                <template v-slot:pagination_block>
                    <pagination :pagination="pagination" @fetch="getCartsReports"/>
                </template>
            </abandon-report-carts>
        </div>

    </div>
</template>

<script type="text/babel">
import AbandonReportWidgets from './_AbandonReportWidgets.vue'
import AbandonReportCarts from './_AbandonReportCarts';
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'AbandonReports',
    components: {
        AbandonReportCarts,
        AbandonReportWidgets,
        Pagination
    },
    data() {
        return {
            reportSummary: [],
            loading: false,
            cartsLoading: false,
            dateRange: '',
            pickerOptions: {
                disabledDate(time) {
                    return time.getTime() > Date.now();
                },
                shortcuts: [{
                    text: this.$t('Last week'),
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: this.$t('Last month'),
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
                        picker.$emit('pick', [start, end]);
                    }
                }, {
                    text: this.$t('Last 3 months'),
                    onClick(picker) {
                        const end = new Date();
                        const start = new Date();
                        start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
                        picker.$emit('pick', [start, end]);
                    }
                }]
            },
            carts: [],
            query: {
                status: 'all',
                search: ''
            },
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            cartStatuses: {
                all: this.$t('All carts'),
                draft: this.$t('Draft Carts'),
                processing: this.$t('In Progress'),
                recovered: this.$t('Recovered Carts'),
                lost: this.$t('Lost Carts'),
                opt_out: this.$t('Opt-Out Carts'),
                skipped: this.$t('Skipped Carts')
            },
            haveAutomation: true
        }
    },
    methods: {
        getWidgetAndCartsReports() {
            this.getReports();
            this.getCartsReports();
        },
        getReports() {
            this.loading = true;
            this.$get('abandon-carts/report-summary', {
                date_range: this.dateRange
            })
                .then(response => {
                    this.reportSummary = response.widgets;
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getCartsReports() {
            this.cartsLoading = true;
            this.$get('abandon-carts', {
                date_range: this.dateRange,
                query: this.query,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    this.carts = response.carts.data;
                    this.haveAutomation = response.haveAutomation || false;
                    this.pagination.total = response.carts.total;
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.cartsLoading = false;
                });
        },
        goToAbandonedSettings() {
            this.$router.push({
                name: 'abandon_cart_settings'
            });
        },
        goToFunnel() {
            this.$router.push({
                name: 'funnels'
            });
        }
    },
    mounted() {
        this.changeTitle(this.$t('Abandoned Carts'));
        this.getReports();
        this.getCartsReports();
    }
}
</script>

<style scoped>

</style>
