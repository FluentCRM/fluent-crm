<template>
    <div v-loading="fetching" class="fc_email_reports">
        <div class="campaigns-table">
            <div v-if="email_sequences.length" class="fluentcrm_title_cards">
                <div v-for="sequence in email_sequences" :key="sequence.id" class="fluentcrm_title_card">
                    <el-row :gutter="20" class="d-flex items-center justify-between">
                        <el-col :sm="24" :md="12">
                            <div class="fluentcrm_card_desc">
                                <div style="cursor: pointer;" @click="showEmailReport(sequence.campaign.id)" class="fluentcrm_card_title">
                                    {{ sequence.title }} ( {{sequence.campaign.subject}} )
                                </div>
                                <div class="fluentcrm_card_actions fluentcrm_card_actions_hidden">
                                    <el-button icon="el-icon-data-analysis" type="text" size="mini" @click="showEmailReport(sequence.campaign.id)">{{$t('Show Individual Emails')}}</el-button>
                                </div>
                            </div>
                        </el-col>
                        <el-col :sm="24" :md="12">
                            <div class="fluentcrm_card_stats">
                                <ul class="fluentcrm_inline_stats">
                                    <li style="cursor: pointer;" @click="showEmailReport(sequence.campaign.id)">
                                        <span class="fluentcrm_digit">{{ sequence.campaign.stats.sent || '--' }}</span>
                                        <p>{{$t('Sent')}}</p>
                                    </li>
                                    <li :title="sequence.campaign.stats.views">
                                        <span class="fluentcrm_digit">{{ getPercent(sequence.campaign.stats.views, sequence.campaign.stats.sent) }}</span>
                                        <p>{{$t('Opened')}}</p>
                                    </li>
                                    <li style="cursor: pointer;" @click="showLinkReport(sequence.campaign.id)" :title="sequence.campaign.stats.clicks">
                                        <span class="fluentcrm_digit">{{ getPercent(sequence.campaign.stats.clicks,sequence.campaign.stats.sent) }}</span>
                                        <p>{{$t('Clicked')}}</p>
                                    </li>
                                    <li :title="sequence.campaign.stats.unsubscribers">
                                        <span class="fluentcrm_digit">
                                            {{ getPercent(sequence.campaign.stats.unsubscribers , sequence.campaign.stats.sent) }}
                                        </span>
                                        <p>{{$t('Unsubscribed')}}</p>
                                    </li>
                                    <li v-if="sequence.campaign.stats.revenue">
                                        <span class="fluentcrm_digit">
                                            {{ sequence.campaign.stats.revenue.total }}
                                        </span>
                                        <p>{{sequence.campaign.stats.revenue.label}}</p>
                                    </li>
                                </ul>
                            </div>
                        </el-col>
                    </el-row>
                </div>
            </div>
            <h3 v-else>{{$t('Sorry, No emails found in this automation')}}</h3>
        </div>
        <el-dialog :close-on-click-modal="false"
                               @closed="show_email_report_id = ''" :title="$t('View Campaign Emails')" width="60%" :append-to-body="true"
                   :visible.sync="show_email_report">
            <campaign-emails :campaign_id="show_email_report_id" v-if="show_email_report_id" />
        </el-dialog>
        <el-dialog :close-on-click-modal="false"
                               @closed="link_click_id = ''" :title="$t('Link Metrics')" width="60%" :append-to-body="true"
                   :visible.sync="link_clicks_modal">
            <link-metrics :campaign_id="link_click_id" :hide_title="true" v-if="link_click_id" />
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import CampaignEmails from '@/Modules/Email/Campaigns/_components/_CampaignEmails';
import LinkMetrics from '@/Modules/Email/Campaigns/_components/_LinkMetrics';

export default {
    name: 'FunnelEmails',
    props: ['funnel_id'],
    components: {
        CampaignEmails,
        LinkMetrics
    },
    data() {
        return {
            email_sequences: [],
            fetching: false,
            show_email_report: false,
            show_email_report_id: false,
            link_clicks_modal: false,
            link_click_id: false
        }
    },
    methods: {
        fetchEmailSequences() {
            this.fetching = true;
            this.$get(`funnels/${this.funnel_id}/email_reports`)
                .then(response => {
                    this.email_sequences = response.email_sequences;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        },
        getPercent(number, total) {
            if (!total || !number) {
                return '--';
            }
            return parseFloat(number / total * 100).toFixed(2) + '%';
        },
        showEmailReport(campaignId) {
            this.show_email_report_id = campaignId;
            this.show_email_report = true;
        },
        showLinkReport(campaignId) {
            this.link_click_id = campaignId;
            this.link_clicks_modal = true;
        }
    },
    mounted() {
        this.fetchEmailSequences();
    }
}
</script>
