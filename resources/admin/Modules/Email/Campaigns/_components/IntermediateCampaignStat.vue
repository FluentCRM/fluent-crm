<template>
    <el-row class="fc_campaign_archived_wrapper" :gutter="30">
        <el-col :md="12" :sm="12" :xs="24">
            <div class="fc_campaign_report">
                <h3>{{ $t('Current Status') }}</h3>
                <ul v-if="!loading">
                    <li v-for="statItem in stat" :key="statItem.status">
                        <i class="el-icon el-icon-message"/>
                        <span class="fc_report_title">{{ statItem.status | ucFirst }} {{
                                $t('Emails')
                            }}</span>
                        <span class="fc_report_value">
                                            {{ statItem.total }}
                                        </span>
                    </li>
                    <li v-for="analytic in analytics" :key="analytic.type"
                        :class="'fc_camp_data_' + analytic.type">
                        <i :class="analytic.icon_class"/>
                        <span class="fc_report_title">{{ analytic.label }}</span>
                        <template v-if="analytic.type == 'open'">
                            <el-tooltip class="item" effect="dark"
                                        :content="$t('open_rate_info')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                        <template v-else-if="analytic.type == 'click'">
                            <el-tooltip class="item" effect="dark"
                                        :content="$t('click_rate_info')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                        <span class="fc_report_value" v-if="analytic.is_percent">
                                            {{ getPercent(analytic.total) }}
                                        </span>
                        <span class="fc_report_value" v-else>
                                            {{ analytic.total }}
                                        </span>
                    </li>
                </ul>
                <div style="padding: 20px;" v-else>
                    <el-skeleton :rows="5" :animated="true"/>
                </div>
            </div>
        </el-col>
        <el-col :md="12" :sm="12" :xs="24">
            <div class="fc_campaign_report">
                <h3>{{ $t('Link activity') }}</h3>
                <div class="">
                    <link-metrics :hide_title="true" :campaign_id="campaign_id"/>
                </div>
            </div>
        </el-col>
    </el-row>
</template>

<script type="text/babel">
import LinkMetrics from './_LinkMetrics.vue';

export default {
    name: 'IntermediateCampaignStat',
    components: {LinkMetrics},
    props: ['campaign_id'],
    data() {
        return {
            sent_count: 0,
            stat: [],
            analytics: [],
            loading: false
        }
    },
    methods: {
        getPercent(number) {
            if (!this.sent_count) {
                return '--';
            }
            return parseFloat(number / this.sent_count * 100).toFixed(2) + '%';
        },
        getCampaignAllStats() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/overview_stats`)
                .then(response => {
                    this.sent_count = response.sent_count;
                    this.stat = response.stat;
                    this.analytics = response.analytics;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.getCampaignAllStats();
    }
}
</script>
