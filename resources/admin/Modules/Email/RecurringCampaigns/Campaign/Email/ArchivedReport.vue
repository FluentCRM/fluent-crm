<template>
    <div v-loading="!app_loaded" class="email_archived_report">

        <div style="margin-bottom: 20px; text-align: center;" v-if="campaign.status == 'working'">
            <h3> {{ $t('Vie_Your_easrn') }}
                <span v-loading="campaign.status == 'working'">{{$t('Sending')}}</span>
            </h3>
            <el-progress :text-inside="true" :stroke-width="36" :percentage="getCampaignPercent()"
                         status="success"></el-progress>
        </div>

        <el-row class="fc_campaign_archived_wrapper" :gutter="30">
            <el-col :md="8" :sm="12" :xs="24">
                <div class="fc_campaign_report">
                    <h3>{{ $t('Campaign Performance') }}</h3>
                    <ul>
                        <li v-for="statItem in stat" :key="statItem.status">
                            <i class="el-icon el-icon-message"/>
                            <span class="fc_report_title">{{ statItem.status | ucFirst }} {{ $t('Emails') }}</span>
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
                </div>
            </el-col>
            <el-col :md="8" :sm="12" :xs="24">
                <div class="fc_campaign_report">
                    <h3>{{ $t('Emails Stats') }}</h3>
                    <div style="max-width: 270px; margin: 0 auto;">
                        <campaign-chart v-if="chartData && campaign" :chart-data="chartData"/>
                    </div>
                </div>
            </el-col>
            <el-col :md="8" :sm="12" :xs="24">
                <div class="fc_campaign_report">
                    <h3>{{ $t('Link activity') }}</h3>
                    <div class="">
                        <link-metrics v-if="campaign" :hide_title="true" :campaign_id="campaign_id"/>
                    </div>
                </div>
            </el-col>
        </el-row>

        <div v-if="campaign" class="fc_campaign_details">
            <el-tabs v-model="activeTab" type="border-card" tab-position="top" style="min-height:200px;">
                <el-tab-pane name="campaign_details" :label="$t('Campaign Details')">
                    <campaign-details :campaign="campaign"/>
                </el-tab-pane>
                <el-tab-pane :lazy="true" name="campaign_subscribers" :label="$t('Emails')">
                    <campaign-emails @fetchCampaign="getCampaignStatus()" :campaign_id="campaign_id"/>
                </el-tab-pane>
                <el-tab-pane v-if="analytics.unsubscribe && campaign" :lazy="true" name="campaign_unsubscribers"
                             :label="$t('Unsubscribers')">
                    <unsubscribers :campaign_id="campaign.id"/>
                </el-tab-pane>
                <el-tab-pane v-if="campaign" :lazy="true" name="campaign_selections" :label="$t('Contact Selections')">
                    <h2>Contact Selections</h2>
                    <readable-recipients :settings="campaign.settings"/>
                </el-tab-pane>
            </el-tabs>
        </div>
    </div>
</template>

<script type="text/babel">
import CampaignChart from '../../../Campaigns/_components/chart/_chart'
import LinkMetrics from '../../../Campaigns/_components/_LinkMetrics'
import CampaignDetails from '../../../Campaigns/_components/_CampaignDetails.vue'
import CampaignEmails from '../../../Campaigns/_components/_CampaignEmails.vue';
import Unsubscribers from '../../../Campaigns/_components/_Unsubscribers.vue'
import ReadableRecipients from '@/Pieces/ReadableRecipientTagger';

export default {
    name: 'RecurringEmailArchivedReport',
    props: ['campaign_id'],
    components: {
        CampaignChart,
        LinkMetrics,
        CampaignDetails,
        CampaignEmails,
        Unsubscribers,
        ReadableRecipients
    },
    data() {
        return {
            loading: false,
            request_counter: 1,
            campaign: null,
            stat: [],
            sent_count: 0,
            analytics: [],
            activeTab: 'campaign_details',
            app_loaded: false
        }
    },
    computed: {
        chartData() {
            if (!Object.keys(this.analytics).length) {
                //  return false;
            }
            const dataSet = [this.sent_count, parseInt(this.analytics.open?.total || 0), parseInt(this.analytics.click?.total || 0), parseInt(this.analytics.unsubscribe?.total || 0)];
            return {
                labels: [this.$t('Email Sent'), this.$t('Opens'), this.$t('Clicks'), this.$t('Unsubscribe')],
                datasets: [
                    {
                        label: this.$t('Emails'),
                        data: JSON.parse(JSON.stringify(dataSet)),
                        backgroundColor: ['#8154ce', '#a45892', '#6dcc3e', '#ff5b5b']
                    }
                ]
            }
        }
    },
    methods: {
        getCampaignStatus() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/status`, {
                request_counter: this.request_counter
            })
                .then(response => {
                    this.campaign = response.campaign;
                    this.stat = response.stat;
                    this.sent_count = response.sent_count;
                    this.analytics = response.analytics;
                    this.changeTitle(this.campaign.title + ' - Campaign');
                    if (response.campaign.status == 'working') {
                        this.fetchStatAgain();
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.app_loaded = true;
                });
        },
        fetchStatAgain() {
            setTimeout(() => {
                this.request_counter += 1;
                this.getCampaignStatus();
            }, 4000);
        },
        getPercent(number) {
            if (!this.sent_count) {
                return '--';
            }
            return parseFloat(number / this.sent_count * 100).toFixed(2) + '%';
        },
        getCampaignPercent() {
            return parseInt(this.sent_count / this.campaign.recipients_count * 100);
        }
    },
    mounted() {
        this.getCampaignStatus();
    }
}
</script>
