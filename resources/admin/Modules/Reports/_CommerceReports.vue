<template>
    <div>
        <el-row v-if="loading" v-loading="loading" :gutter="30">
            <el-col :span="18">
                <el-skeleton class="fc_skeleton_loader" :rows="3" animated/>
                <el-skeleton class="fc_skeleton_loader" :rows="6" animated/>
            </el-col>
            <el-col :span="6">
                <el-skeleton class="fc_skeleton_loader" animated>
                    <template slot="template">
                        <el-skeleton-item variant="rect" style="width: 100%; height: 40px;"/>
                        <el-skeleton-item variant="rect" style="width: 100%; height: 40px;"/>
                        <el-skeleton-item variant="button" style="width: 50%;"/>
                    </template>
                </el-skeleton>
                <el-skeleton class="fc_skeleton_loader" :rows="3" animated/>
            </el-col>
        </el-row>

        <div v-else-if="overview && overview.enabled">
            <el-row :class="'fc_advanced_report fc_report_' + provider" :gutter="30">
                <h3 style="padding: 0 20px 10px;">
                    {{ overview.title }}
                    <el-tooltip v-if="overview.title_info" class="item" effect="dark"
                                :content="overview.title_info"
                                placement="top-start">
                        <i class="el-icon el-icon-info"></i>
                    </el-tooltip>
                </h3>
                <el-col class="fc_report_body" :sm="24" :md="16" :lg="18">
                    <div class="fc_card_widgets">
                        <div v-for="(stat,stat_name) in overview.widgets" :key="stat_name" class="fc_card_widget">
                            <div class="fluentcrm_body">
                                <template v-if="stat.is_money">
                                    <span v-html="overview.currency_sign"></span><span
                                    v-html="formatMoney(stat.value)"></span>
                                </template>
                                <span v-else v-html="stat.value"></span>
                            </div>
                            <div class="stat_title" v-html="stat.label"></div>
                        </div>
                        <template v-if="overview.store_average">
                            <div class="fc_card_widget">
                                <div class="fluentcrm_body"><span
                                    v-html="overview.currency_sign"></span>{{ overview.store_average.aov|formatMoney }}
                                </div>
                                <div class="stat_title">{{ $t('Average Order Value (AOV)') }}</div>
                            </div>
                            <div class="fc_card_widget">
                                <div class="fluentcrm_body">{{ overview.store_average.aoc|formatMoney }}</div>
                                <div class="stat_title">{{ $t('Average Order/Customer (AOC)') }}</div>
                            </div>
                        </template>
                    </div>
                    <div class="ns_subscribers_chart">
                        <commerce-growth :overview="overview" :provider="provider"/>
                    </div>
                </el-col>
                <el-col v-if="overview.top_products" :sm="24" :md="8" :lg="6">
                    <div class="fc_m_20 fc_quick_links fc_onboarding">
                        <div style="justify-content: space-between;" class="fluentcrm_header">
                            <div class="fluentcrm_header_title">
                                {{ $t('Top Selling Products') }}
                            </div>
                            <div v-if="overview.has_top_products_filter" class="fluentcrm-actions">
                                <el-popover
                                    placement="left"
                                    width="400"
                                    trigger="click">
                                    <el-date-picker
                                        @change="getTopProducts()"
                                        v-model="date_range"
                                        type="daterange"
                                        value-format="yyyy-MM-dd"
                                        :picker-options="pickerOptions"
                                        range-separator="To"
                                        start-placeholder="Start date"
                                        end-placeholder="End date">
                                    </el-date-picker>
                                    <el-button style="padding: 0;" size="mini" type="text" slot="reference" icon="el-icon-date"></el-button>
                                </el-popover>
                            </div>
                        </div>
                        <div v-loading="loading_top_products" class="fluentcrm_body">
                            <ul class="fc_lined_items fc_top_products">
                                <li v-for="product in overview.top_products" :key="product.item_id">
                                    {{ product.post_title }}
                                    x <span class="fc_count_total">{{ product.count }}</span>
                                    <template v-if="product.revenue"> = <span
                                        class="fc_revenue_total"><em
                                        v-html="overview.currency_sign"></em>{{ product.revenue | formatMoney }}</span>
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <div v-else class="fc_m_20 fc_onboarding">
            <div class="fluentcrm_body fc_narrow_box">
                <h3>{{ $t('Data Sync is required for') }} {{ overview.title }}</h3>
                <p v-html="overview.enable_instruction"></p>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import CommerceGrowth from './_CommerceGrowth'
import {dateConfig} from '@/Bits/data_config';

export default {
    name: 'CommerceReports',
    components: {
        CommerceGrowth
    },
    props: ['provider'],
    data() {
        return {
            loading: false,
            overview: false,
            loading_top_products: false,
            date_range: ['', ''],
            pickerOptions: {
                shortcuts: dateConfig
            }
        }
    },
    methods: {
        getOverview() {
            this.loading = true;
            this.$get('commerce-reports/' + this.provider, {
                with: ['top_products']
            })
                .then(response => {
                    this.overview = response.report
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getTopProducts() {
            this.loading_top_products = true;
            this.$get('commerce-reports/' + this.provider, {
                top_products_only: 'yes',
                date_range: this.date_range
            })
                .then(response => {
                    this.overview.top_products = response.report.top_products
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading_top_products = false;
                });
        }
    },
    mounted() {
        this.getOverview();
    }
}
</script>
