<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div v-if="campaign" class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom fc_breadcrumb_inline_edit" separator="/">
                    <el-breadcrumb-item :to="{ name: 'campaigns' }">
                        {{ $t('Campaigns') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item class="fc_breadcrumb_item">
                        <div v-if="!showInlineEditCampaignTitle" class="fc_breadcrumb_title">
                            {{ campaign.title }}
                            <span style="width: auto" class="status"> - {{ campaign.status }}</span>
                        </div>

                        <div class="fc_inline_editable">
                            <el-input
                                v-if="showInlineEditCampaignTitle"
                                :placeholder="$t('Internal Campaign Title')"
                                v-model="campaign.title"></el-input>
                            <el-button
                                v-if="showInlineEditCampaignTitle"
                                class="fc_primary_btn"
                                size="small"
                                type="success"
                                @click="updateCampaignSettings()"
                                v-loading="updating">{{ $t('Save') }}</el-button>
                            <el-button
                                v-if="showInlineEditCampaignTitle"
                                type="info" size="small"
                                @click="showInlineEditCampaignTitle = false">
                                {{ $t('Cancel') }}
                            </el-button>
                            <i v-if="!showInlineEditCampaignTitle" @click="showInlineEditCampaignTitle = true" class="el-icon-edit"></i>
                        </div>
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div v-if="campaign.status == 'working'" class="text-align-center" style="margin-right: 10px;">
                    <el-tooltip
                        class="item"
                        effect="dark"
                        :content="$t('Vie_Emails_asatm')"
                        placement="top">
                        <el-button type="danger" size="small" @click="pauseSending()">{{
                                $t('Pause Sending')
                            }}
                        </el-button>
                    </el-tooltip>
                </div>

                <el-button size="small" @click="backToCampaigns()">{{ $t('Back To Campaigns') }}</el-button>
                <el-button-group>
                    <el-button icon="el-icon-setting" size="small" @click="show_campaign_config = true"></el-button>
                    <el-button @click="getCampaignStatus" size="small"><i class="el-icon el-icon-refresh"></i></el-button>
                    <el-button v-if="campaign && (campaign.status == 'archived' || campaign.status == 'working')" @click="shareModal = true" size="small"><i class="el-icon el-icon-share"></i></el-button>
                </el-button-group>
            </div>
        </div>
        <div v-if="campaign" class="fluentcrm_body fluentcrm_body_boxed">
            <template v-if="campaign.status == 'pending-scheduled' || campaign.status == 'processing'">
                <campaign-email-process-stat @unscheduled="handleUnscheduled()" :campaign="campaign"/>
            </template>
            <template v-else>
                <div class="fc_highlight_gray text-align-center" v-if="campaign.status == 'paused'">
                    <h3>{{ $t('Vie_This_cino_sNEwbs') }}</h3>
                    <el-button @click="resumeSending()" size="small" type="danger">{{ $t('Resume Sending') }}
                    </el-button>
                </div>
                <template v-else-if="campaign.status == 'working'">
                    <div v-if="campaign.scheduling_range">
                        <p style="font-size: 16px;">Emails has been scheduled from {{ campaign.scheduling_range.start }}
                            to {{ campaign.scheduling_range.end }}. Scheduled emails will be sent automatically.</p>
                    </div>

                    <template v-else>
                        <h3>
                            {{ $t('Vie_Your_easrn') }} <span
                            v-loading="campaign.status == 'working'">{{ $t('Sending') }}</span>
                        </h3>
                        <el-progress :text-inside="true" :stroke-width="36" :percentage="getCampaignPercent()"
                                     status="success"></el-progress>
                    </template>
                </template>
                <template v-else-if="campaign.status == 'scheduled'">
                    <h3>{{ $t('Vie_This_chbs') }}</h3>
                    <p>{{ $t('Vie_The_ewbsboysdat') }}</p>
                    <pre>{{ campaign.scheduled_at }} ({{ campaign.scheduled_at|nsHumanDiffTime }})</pre>
                    <el-button @click="cancelSchedule()" type="danger" size="small">{{ $t('Cancel this schedule') }}
                    </el-button>
                </template>
                <template v-if="campaign.status == 'archived'">
                    <div class="mb-10"
                         v-if="!appVars.addons.email_open_tracking || !appVars.addons.email_click_tracking">
                        <el-alert class="mb-10" :closable="false" v-if="!appVars.addons.email_open_tracking"
                                  type="warning">{{ $t('Email Open tracking is disabled via PHP Hook') }}
                        </el-alert>
                        <el-alert :closable="false" v-if="!appVars.addons.email_click_tracking" type="warning">{{
                                $t('Email Click tracking is disabled via PHP Hook')
                            }}
                        </el-alert>
                    </div>
                    <el-row class="fc_campaign_archived_wrapper" :gutter="30">
                        <el-col :md="8" :sm="12" :xs="24">
                            <div class="fc_campaign_report">
                                <h3>{{ $t('Campaign Performance') }}</h3>
                                <ul>
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
                                        <span class="fc_report_title">{{ analytic.label }} <el-button v-if="analytic.type == 'revenue'" size="mini" :title="$t('Re-Sync Revenue')" :icon="loading ? 'el-icon-loading' : 'el-icon-refresh'" @click="handleResyncRevenue"></el-button></span>
                                        <template v-if="analytic.type == 'open'">
                                            <el-tooltip class="item" effect="dark"
                                                        :content="$t('Open_Rate_Info')"
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
                                    <campaign-chart v-if="chartData && !loading" :chart-data="chartData"/>
                                </div>
                            </div>
                        </el-col>
                        <el-col :md="8" :sm="12" :xs="24">
                            <div class="fc_campaign_report">
                                <h3>{{ $t('Link activity') }}</h3>
                                <div class="">
                                    <link-metrics v-if="!loading" :hide_title="true" :campaign_id="campaign.id"/>
                                </div>
                            </div>
                        </el-col>
                    </el-row>
                </template>
                <template v-else-if="campaign.status == 'working' && campaign.scheduling_range">
                    <intermediate-campaign-stat v-if="!loading" :campaign_id="campaign.id"/>
                    <div style="padding: 20px;" v-else>
                        <el-skeleton :rows="5" :animated="true"></el-skeleton>
                    </div>
                </template>
                <ul v-loading="loading" v-else style="min-height: 100px;" class="fluentcrm_stat_cards">
                    <li v-for="statItem in stat" :key="statItem.status">
                        <div class="fluentcrm_cart_counter">{{ statItem.total }}</div>
                        <h4>{{ statItem.status | ucFirst }} {{ $t('Emails') }}</h4>
                    </li>
                    <li>
                        <div class="fluentcrm_cart_counter">{{ campaign.recipients_count }}</div>
                        <h4>{{ $t('Total Emails') }}</h4>
                    </li>
                    <li v-for="analytic in analytics" :key="analytic.type" :class="'fc_camp_data_' + analytic.type">
                        <div class="fluentcrm_cart_counter">
                        <span v-if="analytic.is_percent">
                            {{ getPercent(analytic.total) }}
                        </span>
                            <span v-else>
                            {{ analytic.total }}
                        </span>
                        </div>
                        <h4>{{ analytic.label }}</h4>
                    </li>
                </ul>

                <el-tabs v-model="activeTab" type="border-card" tab-position="top" style="min-height:200px;">
                    <el-tab-pane name="campaign_details" :label="$t('Campaign Details')">
                        <campaign-summary-details :campaign="campaign"/>
                    </el-tab-pane>
                    <el-tab-pane :lazy="true" name="campaign_subscribers" :label="$t('Emails')">
                        <campaign-emails @fetchCampaign="getCampaignStatus()" :campaign_id="campaign.id"/>
                    </el-tab-pane>
                    <el-tab-pane v-if="analytics.unsubscribe" :lazy="true" name="campaign_unsubscribers"
                                 :label="$t('Unsubscribers')">
                        <unsubscribers :campaign_id="campaign.id"/>
                    </el-tab-pane>
                    <el-tab-pane v-if="subject_analytics.subjects" name="campaign_subject_analytics"
                                 :label="$t('A/B Testing Result')">
                        <subject-metrics :campaign="campaign" :metrics="subject_analytics"/>
                    </el-tab-pane>
                    <el-tab-pane v-if="analytics.revenue" name="campaign_revenue"
                                 :label="$t('Revenue Report')">
                        <revenue-report v-if="activeTab == 'campaign_revenue'" :campaign="campaign" />
                    </el-tab-pane>
                    <el-tab-pane :lazy="true" name="campaign_actions" :label="$t('Actions')">
                        <campaign-actions :campaign="campaign"/>
                    </el-tab-pane>
                    <el-tab-pane v-if="campaign" :lazy="true" name="campaign_selections"
                                 :label="$t('Contact Selections')">
                        <h2>Contact Selections</h2>
                        <readable-recipients :settings="campaign.settings"/>
                    </el-tab-pane>
                </el-tabs>
            </template>
        </div>
        <div v-else class="fluentcrm_body_boxed">
            <el-skeleton :rows="10" :animated="true"></el-skeleton>
        </div>

        <div v-if="loading && campaign && campaign.status != 'working'" class="fluentcrm_body_boxed"
             style="position: relative;">
            <div class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="7"></el-skeleton>
        </div>
        <el-dialog
            v-if="campaign"
            :title="$t('Campaign Settings')"
            :append-to-body="true"
            :close-on-click-modal="false"
            :visible.sync="show_campaign_config"
            width="60%">
            <el-form label-position="top" :data="campaign">
                <el-form-item :label="$t('Campaign Title')">
                    <el-input v-model="campaign.title" :placeholder="$t('Internal Campaign Title')"/>
                </el-form-item>

                <div v-if="campaign.status == 'scheduled'" class="fc_highlight_gray text-align-center">
                    <h3>Your campaign is currently on <b>scheduled</b> state</h3>
                    <el-form-item :label="$t('Schedule Time')">
                        <el-date-picker
                            value-format="yyyy-MM-dd HH:mm:ss"
                            type="datetime"
                            :picker-options="pickerOptions"
                            :placeholder="$t('Select date and time')"
                            v-model="campaign.scheduled_at"/>
                    </el-form-item>
                </div>

            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button v-loading="updating" type="success"
                           @click="updateCampaignSettings()">{{ $t('Save') }}</el-button>
            </span>
        </el-dialog>
        <el-dialog
            v-if="campaign"
            :title="$t('Share Newsletter via URL')"
            :append-to-body="true"
            :close-on-click-modal="false"
            :visible.sync="shareModal"
            width="60%">

            <view-newsletter v-if="shareModal" :campaign_id="campaign.id"/>

            <span slot="footer" class="dialog-footer">
                <el-button @click="shareModal = false">{{ $t('Close') }}</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import CampaignEmails from './_components/_CampaignEmails';
import LinkMetrics from './_components/_LinkMetrics';
import SubjectMetrics from './_components/_SubjectMetrics';
import RevenueReport from './_components/_RevenueReport';
import CampaignActions from './_components/CampaignActions';
import CampaignChart from './_components/chart/_chart'
import Unsubscribers from './_components/_Unsubscribers'
import ReadableRecipients from '@/Pieces/ReadableRecipientTagger';
import CampaignEmailProcessStat from './_components/CampaignEmailProcessStat';
import CampaignSummaryDetails from './_components/_CampaignDetails.vue';
import IntermediateCampaignStat from './_components/IntermediateCampaignStat.vue';
import ViewNewsletter from './_components/ViewNewsletter.vue';

export default {
    name: 'ViewCampaign',
    components: {
        CampaignEmails,
        LinkMetrics,
        SubjectMetrics,
        CampaignActions,
        CampaignChart,
        RevenueReport,
        Unsubscribers,
        ReadableRecipients,
        CampaignEmailProcessStat,
        CampaignSummaryDetails,
        IntermediateCampaignStat,
        ViewNewsletter
    },
    data() {
        return {
            activeTab: 'campaign_details',
            loading: true,
            campaign: null,
            emails: [],
            dialogVisible: false,
            sent_count: 0,
            repeatingCall: false,
            stat: [],
            analytics: {},
            request_counter: 1,
            subject_analytics: {},
            campaign_id: this.$route.params.id,
            show_campaign_config: false,
            fetch_status: true,
            pickerOptions: {
                disabledDate(date) {
                    return date.getTime() <= (Date.now() - 3600 * 1000 * 24);
                },
                shortcuts: [{
                    text: this.$t('After 1 Hour'),
                    onClick(picker) {
                        const date = new Date();
                        date.setTime(date.getTime() + 3600 * 1000 * 1);
                        picker.$emit('pick', date);
                    }
                }, {
                    text: this.$t('Tomorrow'),
                    onClick(picker) {
                        const date = new Date();
                        date.setTime(date.getTime() + 3600 * 1000 * 24 * 1);
                        picker.$emit('pick', date);
                    }
                }, {
                    text: this.$t('After 2 Days'),
                    onClick(picker) {
                        const date = new Date();
                        date.setTime(date.getTime() + 3600 * 1000 * 24 * 2);
                        picker.$emit('pick', date);
                    }
                }, {
                    text: this.$t('After 1 Week'),
                    onClick(picker) {
                        const date = new Date();
                        date.setTime(date.getTime() + 3600 * 1000 * 24 * 7);
                        picker.$emit('pick', date);
                    }
                }]
            },
            updating: false,
            shareModal: false,
            showInlineEditCampaignTitle: false
        };
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
        backToCampaigns() {
            this.$router.push({
                name: 'campaigns',
                query: {t: (new Date()).getTime()}
            });
        },
        getCampaignStatus() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/status`, {
                request_counter: this.request_counter
            })
                .then(response => {
                    if (!this.campaign && response.campaign.status === 'draft') {
                        this.$router.push({
                            name: 'campaign',
                            params: {
                                id: response.campaign.id
                            }
                        });
                        return;
                    }

                    this.campaign = response.campaign;
                    this.stat = response.stat;
                    this.sent_count = response.sent_count;
                    this.analytics = response.analytics;
                    this.subject_analytics = response.subject_analytics;

                    if (!response.campaign.scheduling_range && response.campaign.status === 'working') {
                        this.fetchStatAgain();
                    }
                    this.changeTitle(this.campaign.title + ' - Campaign');
                })
                .catch((errors) => {
                    if (this.campaign) {
                        this.fetchStatAgain();
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchStatAgain() {
            setTimeout(() => {
                this.request_counter += 1;
                this.getCampaignStatus();
            }, 4000);
        },
        scheduledAt(date) {
            if (date === null) {
                return this.$t('Not Scheduled');
            }
            return this.nsDateFormat(date, 'MMMM Do, YYYY [at] h:mm A');
        },
        getCampaignPercent() {
            return parseInt(this.sent_count / this.campaign.recipients_count * 100);
        },
        getPercent(number) {
            if (!this.sent_count) {
                return '--';
            }
            return parseFloat(number / this.sent_count * 100).toFixed(2) + '%';
        },
        updateCampaignSettings() {
            this.updating = true;
            this.$put(`campaigns/${this.campaign_id}/title`, {
                title: this.campaign.title,
                scheduled_at: this.campaign.scheduled_at
            })
                .then(response => {
                    this.campaign = response.campaign;
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                    this.show_campaign_config = false;
                    this.showInlineEditCampaignTitle = false;
                    this.getCampaignStatus();
                });
        },
        pauseSending() {
            this.updating = true;
            this.$post(`campaigns/${this.campaign_id}/pause`)
                .then(response => {
                    this.campaign = response.campaign;
                    this.$notify.success(response.message);
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                    this.show_campaign_config = false;
                });
        },
        resumeSending() {
            this.updating = true;
            this.$post(`campaigns/${this.campaign_id}/resume`)
                .then(response => {
                    this.campaign = response.campaign;
                    this.$notify.success(response.message);
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                    this.getCampaignStatus();
                });
        },
        cancelSchedule() {
            this.loading = true;
            this.$post(`campaigns/${this.campaign_id}/un-schedule`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'campaign',
                        params: {id: this.campaign_id},
                        query: {
                            t: (new Date()).getTime(),
                            step: 3
                        }
                    });
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.getCampaignStatus();
                });
        },
        handleUnscheduled() {
            this.$router.push({
                name: 'campaign',
                params: {id: this.campaign.id},
                query: {
                    t: (new Date()).getTime(),
                    step: 3
                }
            });
        },
        handleResyncRevenue() {
            this.loading = true;
            this.$post(`campaigns/${this.campaign.id}/revenues/resync`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.analytics.revenue.total = response.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                })
        }
    },
    mounted() {
        this.getCampaignStatus();
        this.changeTitle(this.$t('Campaign'));
    }
}
</script>
